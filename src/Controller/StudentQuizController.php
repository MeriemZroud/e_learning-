<?php

namespace App\Controller;

use App\Entity\Course;
use App\Entity\CourseQuiz;
use App\Entity\CourseQuizSubmission;
use App\Entity\User;
use App\Repository\CourseRepository;
use App\Repository\CourseQuizRepository;
use App\Repository\CourseQuizSubmissionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class StudentQuizController extends AbstractController
{
    #[Route('/student/quizzes', name: 'app_student_quiz_index', methods: ['GET'])]
    public function index(Request $request, CourseQuizRepository $quizRepository, CourseQuizSubmissionRepository $submissionRepository): Response
    {
        $student = $this->requireStudentUser();
        $search = trim((string) $request->query->get('search', ''));
        $quizzes = $quizRepository->findPublishedForStudents($search);

        $submissionMap = [];
        foreach ($quizzes as $quiz) {
            $submissionMap[(int) $quiz->getId()] = $submissionRepository->findOneByQuizAndStudent($quiz, $student);
        }

        return $this->render('dashboard/student_quiz_index.html.twig', [
            'student_name' => $this->getStudentName($student),
            'quizzes' => $quizzes,
            'submission_map' => $submissionMap,
            'search' => $search,
        ]);
    }

    #[Route('/student/courses/{id}', name: 'app_student_course_detail', methods: ['GET'])]
    public function courseDetail(int $id, CourseRepository $courseRepository, CourseQuizRepository $quizRepository, CourseQuizSubmissionRepository $submissionRepository): Response
    {
        $student = $this->requireStudentUser();
        $course = $courseRepository->findOneBy([
            'id' => $id,
            'is_published' => true,
        ]);

        if (!$course instanceof Course) {
            throw $this->createNotFoundException('Course not found.');
        }

        $quizzes = $quizRepository->findPublishedByCourse($course);
        $submissionMap = [];
        foreach ($quizzes as $quiz) {
            $submissionMap[(int) $quiz->getId()] = $submissionRepository->findOneByQuizAndStudent($quiz, $student);
        }

        return $this->render('dashboard/student_course_detail.html.twig', [
            'student_name' => $this->getStudentName($student),
            'course' => $course,
            'quizzes' => $quizzes,
            'submission_map' => $submissionMap,
        ]);
    }

    #[Route('/student/courses/{id}/chatbot', name: 'app_student_course_chatbot', methods: ['POST'])]
    public function courseChatbot(int $id, Request $request, CourseRepository $courseRepository, HttpClientInterface $httpClient): JsonResponse
    {
        $this->requireStudentUser();

        $course = $courseRepository->findOneBy([
            'id' => $id,
            'is_published' => true,
        ]);

        if (!$course instanceof Course) {
            return new JsonResponse(['error' => 'Course not found.'], Response::HTTP_NOT_FOUND);
        }

        $csrfToken = (string) $request->headers->get('X-CSRF-Token', '');
        if (!$this->isCsrfTokenValid('student_course_chatbot_' . $course->getId(), $csrfToken)) {
            return new JsonResponse(['error' => 'Invalid request token.'], Response::HTTP_FORBIDDEN);
        }

        $payload = json_decode((string) $request->getContent(), true);
        if (!is_array($payload)) {
            return new JsonResponse(['error' => 'Invalid request body.'], Response::HTTP_BAD_REQUEST);
        }

        $message = trim((string) ($payload['message'] ?? ''));
        if ($message === '') {
            return new JsonResponse(['error' => 'Message is required.'], Response::HTTP_BAD_REQUEST);
        }

        $history = is_array($payload['history'] ?? null) ? $payload['history'] : [];
        $apiKey = (string) ($_ENV['HUGGING_FACE_API_KEY'] ?? $_SERVER['HUGGING_FACE_API_KEY'] ?? getenv('HUGGING_FACE_API_KEY') ?: '');
        $models = $this->getChatbotModels();

        if ($apiKey === '') {
            return new JsonResponse(['error' => 'Hugging Face API key is not configured.'], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        $prompt = $this->buildCourseChatPrompt($course, $history, $message);
        $lastErrorMessage = '';

        foreach ($models as $model) {
            try {
                $result = $this->requestChatbotCompletion($httpClient, $apiKey, $model, $prompt);

                if (isset($result['error']) && is_string($result['error']) && trim($result['error']) !== '') {
                    $lastErrorMessage = trim($result['error']);

                    if (isset($result['estimated_time']) && is_numeric($result['estimated_time'])) {
                        $lastErrorMessage .= sprintf(' Please retry in about %d seconds.', (int) ceil((float) $result['estimated_time']));
                    }

                    continue;
                }

                $answer = $this->extractChatbotAnswer($result);

                if ($answer === '') {
                    $lastErrorMessage = 'Model returned an empty response.';
                    continue;
                }

                return new JsonResponse([
                    'answer' => $answer,
                    'model' => $model,
                ]);
            } catch (\Throwable) {
                $lastErrorMessage = 'Unable to contact Hugging Face endpoint.';
                continue;
            }
        }

        return new JsonResponse([
            'answer' => $this->buildFallbackChatbotAnswer($course, $message),
            'model' => 'fallback-local',
            'error' => $lastErrorMessage !== '' ? $lastErrorMessage : 'Remote model unavailable.',
        ]);
    }

    #[Route('/student/quizzes/{id}', name: 'app_student_quiz_take', methods: ['GET', 'POST'])]
    public function take(int $id, Request $request, CourseQuizRepository $quizRepository, CourseQuizSubmissionRepository $submissionRepository, EntityManagerInterface $entityManager): Response
    {
        $student = $this->requireStudentUser();
        $quiz = $quizRepository->find($id);

        if (!$quiz instanceof CourseQuiz || $quiz->getStatus() !== 'published') {
            throw $this->createNotFoundException('Quiz not found.');
        }

        $questions = $this->decodeQuestions($quiz->getQuestionsJson());
        if ($questions === []) {
            $this->addFlash('error', 'This quiz is currently unavailable.');

            return $this->redirectToRoute('app_student_quiz_index');
        }

        $submission = $submissionRepository->findOneByQuizAndStudent($quiz, $student);

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('student_quiz_submit_' . $quiz->getId(), (string) $request->request->get('_token'))) {
                $this->addFlash('error', 'Invalid request token.');

                return $this->redirectToRoute('app_student_quiz_take', ['id' => $quiz->getId()]);
            }

            $answers = $request->request->all('answers');
            $normalizedAnswers = [];
            $correctCount = 0;

            foreach ($questions as $index => $question) {
                $answerValue = isset($answers[$index]) ? (int) $answers[$index] : -1;
                $normalizedAnswers[] = $answerValue;

                if ($answerValue === (int) $question['correctOption']) {
                    $correctCount++;
                }
            }

            $score = count($questions) > 0 ? round(($correctCount / count($questions)) * 20, 2) : 0.0;

            if (!$submission instanceof CourseQuizSubmission) {
                $submission = new CourseQuizSubmission();
                $submission->setQuiz($quiz);
                $submission->setStudent($student);
                $entityManager->persist($submission);
            }

            $submission->setAnswersJson(json_encode($normalizedAnswers));
            $submission->setScore($score);
            $submission->setStatus('submitted');
            $submission->setSubmittedAt(new \DateTime());
            $entityManager->flush();

            $this->addFlash('success', sprintf('Quiz submitted successfully. Score: %s/20', number_format($score, 2)));

            return $this->redirectToRoute('app_student_quiz_index');
        }

        return $this->render('dashboard/student_quiz_take.html.twig', [
            'student_name' => $this->getStudentName($student),
            'quiz' => $quiz,
            'questions' => $questions,
            'submission' => $submission,
        ]);
    }

    /**
     * @return array<int, array{question:string,options:array<int,string>,correctOption:int,explanation:string}>
     */
    private function decodeQuestions(?string $questionsJson): array
    {
        if ($questionsJson === null || $questionsJson === '') {
            return [];
        }

        $decoded = json_decode($questionsJson, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function buildCourseChatPrompt(Course $course, array $history, string $message): string
    {
        $safeHistory = [];
        foreach (array_slice($history, -6) as $item) {
            if (!is_array($item)) {
                continue;
            }

            $role = (string) ($item['role'] ?? '');
            $content = trim((string) ($item['content'] ?? ''));

            if (!in_array($role, ['user', 'assistant'], true) || $content === '') {
                continue;
            }

            $safeHistory[] = sprintf('%s: %s', $role === 'assistant' ? 'Assistant' : 'Student', $content);
        }

        $contextLines = [
            'You are an educational chatbot for LearnWay students.',
            'Be clear, concise, and friendly. Keep answers in French unless the student asks another language.',
            'Focus on helping understand the course and solving learning questions step by step.',
            'If information is not in the course context, say it honestly and provide best-effort guidance.',
            'Course title: ' . (string) $course->getTitle(),
            'Course description: ' . ((string) $course->getDescription() !== '' ? (string) $course->getDescription() : 'No description provided.'),
        ];

        if ($safeHistory !== []) {
            $contextLines[] = 'Conversation history:';
            foreach ($safeHistory as $historyLine) {
                $contextLines[] = $historyLine;
            }
        }

        $contextLines[] = 'Student: ' . $message;
        $contextLines[] = 'Assistant:';

        return implode("\n", $contextLines);
    }

    private function buildFallbackChatbotAnswer(Course $course, string $message): string
    {
        $normalizedMessage = mb_strtolower(trim($message));
        $courseTitle = trim((string) $course->getTitle());
        $courseDescription = trim((string) $course->getDescription());

        if (preg_match('/\b(salut|bonjour|hello|hi)\b/u', $normalizedMessage)) {
            return sprintf(
                'Salut ! Je suis là pour t’aider sur %s. Tu peux me poser une question sur le cours, un chapitre ou un quiz.',
                $courseTitle !== '' ? $courseTitle : 'ce cours'
            );
        }

        if (preg_match('/\b(node\s*js|nodejs|node\.js)\b/u', $normalizedMessage)) {
            return 'Node.js est un environnement d’exécution JavaScript côté serveur. Il sert à créer des API, des sites dynamiques et des applications temps réel. Si tu veux, je peux t’expliquer la différence entre Node.js et JavaScript, ou te donner un exemple simple.';
        }

        if ($courseDescription !== '') {
            return sprintf(
                'Je ne peux pas joindre le modèle distant pour le moment, mais voici une aide rapide sur %s : %s. Pose-moi une question plus précise sur le chapitre, et je te guiderai étape par étape.',
                $courseTitle !== '' ? $courseTitle : 'ce cours',
                mb_strimwidth($courseDescription, 0, 160, '...')
            );
        }

        return sprintf(
            'Je ne peux pas joindre le modèle distant pour le moment, mais je peux t’aider sur %s. Dis-moi exactement ce que tu veux comprendre, et je te répondrai de façon simple.',
            $courseTitle !== '' ? $courseTitle : 'ce cours'
        );
    }

    /**
     * @return array<int, string>
     */
    private function getChatbotModels(): array
    {
        $primaryModel = trim((string) ($_ENV['HUGGING_FACE_MODEL_PRIMARY'] ?? $_SERVER['HUGGING_FACE_MODEL_PRIMARY'] ?? getenv('HUGGING_FACE_MODEL_PRIMARY') ?: ($_ENV['HUGGING_FACE_MODEL'] ?? $_SERVER['HUGGING_FACE_MODEL'] ?? getenv('HUGGING_FACE_MODEL') ?: 'Qwen/Qwen2.5-7B-Instruct')));
        $secondaryModel = trim((string) ($_ENV['HUGGING_FACE_MODEL_SECONDARY'] ?? $_SERVER['HUGGING_FACE_MODEL_SECONDARY'] ?? getenv('HUGGING_FACE_MODEL_SECONDARY') ?: 'microsoft/Phi-3-mini-4k-instruct'));

        $models = array_values(array_unique(array_filter([
            trim($primaryModel, " \t\n\r\0\x0B/"),
            trim($secondaryModel, " \t\n\r\0\x0B/"),
        ], static fn (string $model): bool => $model !== '')));

        return $models === [] ? ['Qwen/Qwen2.5-7B-Instruct', 'microsoft/Phi-3-mini-4k-instruct'] : $models;
    }

    private function requestChatbotCompletion(HttpClientInterface $httpClient, string $apiKey, string $model, string $prompt): array
    {
        $response = $httpClient->request('POST', 'https://router.huggingface.co/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => $model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are an educational assistant for LearnWay students. Be clear, concise, and helpful.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'temperature' => 0.3,
                'max_tokens' => 320,
            ],
            'timeout' => 40,
        ]);

        return $response->toArray(false);
    }

    private function extractChatbotAnswer(array $result): string
    {
        if (isset($result['error']) && is_string($result['error'])) {
            return '';
        }

        if (isset($result['choices'][0]['message']['content']) && is_string($result['choices'][0]['message']['content'])) {
            return trim($result['choices'][0]['message']['content']);
        }

        if (isset($result[0]['generated_text']) && is_string($result[0]['generated_text'])) {
            return trim($result[0]['generated_text']);
        }

        if (isset($result['generated_text']) && is_string($result['generated_text'])) {
            return trim($result['generated_text']);
        }

        return '';
    }

    private function requireStudentUser(): User
    {
        $this->denyAccessUnlessGranted('ROLE_STUDENT');

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Student account required.');
        }

        return $user;
    }

    private function getStudentName(User $student): string
    {
        $name = trim(sprintf('%s %s', (string) $student->getFirstName(), (string) $student->getLastName()));

        return $name !== '' ? $name : ((string) $student->getEmail() ?: 'Student');
    }
}
