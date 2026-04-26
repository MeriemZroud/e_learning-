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
use Nucleos\DompdfBundle\Wrapper\DompdfWrapperInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class StudentQuizController extends AbstractController
{
    public function __construct(
        private readonly DompdfWrapperInterface $dompdfWrapper,
    ) {
    }

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
            'lesson_summary' => null,
        ]);
    }

    #[Route('/student/courses/{id}/lesson-summary', name: 'app_student_course_lesson_summary', methods: ['GET'])]
    public function lessonSummary(int $id, CourseRepository $courseRepository, CourseQuizRepository $quizRepository, CourseQuizSubmissionRepository $submissionRepository, HttpClientInterface $httpClient): Response
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

        $lessonSummary = $this->generateLessonSummaryData($course, $quizzes, $httpClient);

        return $this->render('dashboard/student_course_detail.html.twig', [
            'student_name' => $this->getStudentName($student),
            'course' => $course,
            'quizzes' => $quizzes,
            'submission_map' => $submissionMap,
            'lesson_summary' => $lessonSummary,
        ]);
    }

    #[Route('/student/courses/{id}/lesson-summary/pdf', name: 'app_student_course_lesson_summary_pdf', methods: ['GET'])]
    public function downloadLessonSummaryPdf(int $id, CourseRepository $courseRepository, CourseQuizRepository $quizRepository, HttpClientInterface $httpClient): Response
    {
        $this->requireStudentUser();

        $course = $courseRepository->findOneBy([
            'id' => $id,
            'is_published' => true,
        ]);

        if (!$course instanceof Course) {
            throw $this->createNotFoundException('Course not found.');
        }

        $quizzes = $quizRepository->findPublishedByCourse($course);
        $lessonSummary = $this->generateLessonSummaryData($course, $quizzes, $httpClient);
        $generatedAt = new \DateTimeImmutable();

        $html = $this->renderView('dashboard/student_lesson_summary.pdf.twig', [
            'course' => $course,
            'lesson_summary' => $lessonSummary,
            'generated_at' => $generatedAt,
        ]);

        $pdfContent = $this->dompdfWrapper->getPdf($html, [
            'defaultFont' => 'DejaVu Sans',
        ]);

        $safeTitle = preg_replace('/[^a-z0-9\-]+/i', '-', (string) $course->getTitle()) ?: 'lesson';
        $filename = sprintf('lesson-summary-%s-%s.pdf', trim((string) $safeTitle, '-'), $generatedAt->format('Ymd-His'));

        $response = new Response($pdfContent);
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', sprintf('attachment; filename="%s"', $filename));

        return $response;
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

    /**
     * @param array<int, CourseQuiz> $quizzes
     * @return array{summary:string,keyPoints:array<int,string>,model:string}
     */
    private function generateLessonSummaryData(Course $course, array $quizzes, HttpClientInterface $httpClient): array
    {
        $apiKey = (string) ($_ENV['HUGGING_FACE_API_KEY'] ?? $_SERVER['HUGGING_FACE_API_KEY'] ?? getenv('HUGGING_FACE_API_KEY') ?: '');
        $model = trim((string) ($_ENV['HUGGING_FACE_MODEL_CHAPTER_SUMMARY'] ?? $_SERVER['HUGGING_FACE_MODEL_CHAPTER_SUMMARY'] ?? getenv('HUGGING_FACE_MODEL_CHAPTER_SUMMARY') ?: ($_ENV['HUGGING_FACE_MODEL'] ?? 'Qwen/Qwen2.5-7B-Instruct')));

        $fallback = $this->buildFallbackLessonSummaryData($course, $quizzes);
        if ($apiKey === '' || $model === '') {
            return $fallback + ['model' => 'fallback-local'];
        }

        $source = $this->buildLessonSourceText($course, $quizzes);
        $prompt = "Tu es un assistant pédagogique. Retourne uniquement du JSON valide avec cette forme: "
            . '{"summary":"...","keyPoints":["...","...","..."]}. '
            . "La summary doit être un résumé court de la leçon en français (80-140 mots). "
            . "keyPoints doit contenir 4 à 6 points clés de révision, concis et actionnables. "
            . "Leçon:\n" . $source;

        try {
            $result = $this->requestChatbotCompletion($httpClient, $apiKey, $model, $prompt);
            $content = $this->extractChatbotAnswer($result);

            if ($content === '') {
                return $fallback + ['model' => 'fallback-local'];
            }

            $payload = $this->decodeSummaryPayload($content);
            $summary = trim((string) ($payload['summary'] ?? ''));
            $rawPoints = is_array($payload['keyPoints'] ?? null) ? $payload['keyPoints'] : [];
            $keyPoints = [];

            foreach ($rawPoints as $point) {
                if (!is_string($point)) {
                    continue;
                }

                $value = trim(strip_tags($point));
                if ($value !== '') {
                    $keyPoints[] = $value;
                }

                if (count($keyPoints) >= 6) {
                    break;
                }
            }

            if ($summary === '' || count($keyPoints) < 3) {
                return $fallback + ['model' => 'fallback-local'];
            }

            return [
                'summary' => $summary,
                'keyPoints' => $keyPoints,
                'model' => $model,
            ];
        } catch (\Throwable) {
            return $fallback + ['model' => 'fallback-local'];
        }
    }

    /**
     * @param array<int, CourseQuiz> $quizzes
     */
    private function buildLessonSourceText(Course $course, array $quizzes): string
    {
        $quizHints = [];
        foreach (array_slice($quizzes, 0, 5) as $quiz) {
            $hint = trim((string) ($quiz->getSourceSummary() ?: $quiz->getTitle()));
            if ($hint !== '') {
                $quizHints[] = '- ' . $hint;
            }
        }

        return implode("\n", [
            'Titre du cours: ' . (string) ($course->getTitle() ?? ''),
            'Description: ' . ((string) ($course->getDescription() ?? '') !== '' ? (string) $course->getDescription() : 'Non fournie.'),
            'Ressource vidéo: ' . (string) ($course->getVideoUrl() ?? 'Non fournie.'),
            'PDF associé: ' . ($course->getPdfFile() ? ('uploads/courses/pdf/' . $course->getPdfFile()) : 'Aucun'),
            'Points évalués dans les quiz:',
            !empty($quizHints) ? implode("\n", $quizHints) : '- Aucun quiz publié pour ce cours.',
        ]);
    }

    private function decodeSummaryPayload(string $content): array
    {
        $clean = trim(str_replace(["```json", "```"], '', $content));
        $decoded = json_decode($clean, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/\{.*\}/s', $clean, $matches) === 1) {
            $decoded = json_decode($matches[0], true);
            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    /**
     * @param array<int, CourseQuiz> $quizzes
     * @return array{summary:string,keyPoints:array<int,string>}
     */
    private function buildFallbackLessonSummaryData(Course $course, array $quizzes): array
    {
        $title = trim((string) ($course->getTitle() ?? 'Cette leçon'));
        $description = trim((string) ($course->getDescription() ?? ''));
        $quizCount = count($quizzes);

        $summary = $description !== ''
            ? sprintf(
                'Cette leçon "%s" présente les notions essentielles du cours et les applique dans un contexte pratique. Elle met l\'accent sur la compréhension des concepts clés et la préparation aux évaluations. Utilise la vidéo, le support PDF et les quiz pour renforcer progressivement ta maîtrise du sujet.',
                $title
            )
            : sprintf(
                'Cette leçon "%s" te guide sur les notions principales à retenir. L\'objectif est de comprendre la base théorique, de repérer les points importants pour l\'examen et de t\'entraîner avec les ressources disponibles du cours.',
                $title
            );

        $keyPoints = [
            'Identifier et mémoriser les définitions principales de la leçon.',
            'Relier chaque concept à un exemple concret vu dans le cours.',
            'Revoir la vidéo en notant les étapes ou règles importantes.',
            'Faire les quiz publiés pour vérifier ta compréhension immédiate.',
        ];

        if ($quizCount > 0) {
            $keyPoints[] = sprintf('Planifier une révision active sur les %d quiz disponibles.', $quizCount);
        }

        return [
            'summary' => $summary,
            'keyPoints' => $keyPoints,
        ];
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
