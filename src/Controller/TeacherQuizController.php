<?php

namespace App\Controller;

use App\Entity\Course;
use App\Entity\CourseQuiz;
use App\Entity\CourseQuizSubmission;
use App\Entity\User;
use App\Repository\CourseQuizRepository;
use App\Repository\CourseQuizSubmissionRepository;
use App\Repository\CourseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Routing\Attribute\Route;

class TeacherQuizController extends AbstractController
{
    #[Route('/teacher/submissions', name: 'app_teacher_submissions', methods: ['GET'])]
    public function submissions(CourseQuizSubmissionRepository $submissionRepository): Response
    {
        $teacher = $this->requireTeacherUser();
        $submissions = $submissionRepository->findAllByTeacher($teacher);

        $submissionRows = [];
        foreach ($submissions as $submission) {
            $student = $submission->getStudent();
            $quiz = $submission->getQuiz();
            $course = $quiz?->getCourse();

            $submissionRows[] = [
                'submission_id' => $submission->getId(),
                'student_name' => $student instanceof User ? $this->getTeacherName($student) : 'Unknown student',
                'student_email' => $student instanceof User ? (string) $student->getEmail() : '-',
                'course_title' => $course?->getTitle() ?? '-',
                'quiz_title' => $quiz?->getTitle() ?? '-',
                'score' => $submission->getScore(),
                'submitted_at' => $submission->getSubmittedAt(),
                'status' => $submission->getStatus(),
            ];
        }

        return $this->render('dashboard/teacher_submissions.html.twig', [
            'teacher_name' => $this->getTeacherName($teacher),
            'submission_rows' => $submissionRows,
        ]);
    }

    #[Route('/teacher/submissions/{id}', name: 'app_teacher_submission_detail', methods: ['GET'])]
    public function submissionDetail(int $id, CourseQuizSubmissionRepository $submissionRepository): Response
    {
        $teacher = $this->requireTeacherUser();
        $submission = $submissionRepository->find($id);

        if (!$submission instanceof CourseQuizSubmission) {
            throw $this->createNotFoundException('Submission not found.');
        }

        $quiz = $submission->getQuiz();
        if (!$quiz instanceof CourseQuiz || $quiz->getTeacher()?->getId() !== $teacher->getId()) {
            throw $this->createAccessDeniedException('You cannot view this submission.');
        }

        $course = $quiz->getCourse();
        $student = $submission->getStudent();
        $questions = $this->decodeQuestions($quiz->getQuestionsJson());
        $answerIndexes = $this->decodeSubmittedAnswerIndexes($submission->getAnswersJson());

        $reviewRows = [];
        foreach ($questions as $index => $question) {
            $selectedIndex = $answerIndexes[$index] ?? null;
            $options = $question['options'] ?? [];
            $correctIndex = isset($question['correctOption']) ? (int) $question['correctOption'] : -1;

            $reviewRows[] = [
                'question_number' => $index + 1,
                'question' => (string) ($question['question'] ?? ''),
                'selected_index' => $selectedIndex,
                'selected_option' => is_int($selectedIndex) && isset($options[$selectedIndex]) ? (string) $options[$selectedIndex] : 'No answer',
                'correct_index' => $correctIndex,
                'correct_option' => $correctIndex >= 0 && isset($options[$correctIndex]) ? (string) $options[$correctIndex] : '-',
                'is_correct' => is_int($selectedIndex) && $selectedIndex === $correctIndex,
                'explanation' => (string) ($question['explanation'] ?? ''),
            ];
        }

        return $this->render('dashboard/teacher_submission_detail.html.twig', [
            'teacher_name' => $this->getTeacherName($teacher),
            'student_name' => $student instanceof User ? $this->getTeacherName($student) : 'Unknown student',
            'student_email' => $student instanceof User ? (string) $student->getEmail() : '-',
            'course_title' => $course?->getTitle() ?? '-',
            'quiz_title' => $quiz->getTitle() ?? '-',
            'score' => $submission->getScore(),
            'submitted_at' => $submission->getSubmittedAt(),
            'status' => $submission->getStatus(),
            'review_rows' => $reviewRows,
        ]);
    }

    #[Route('/teacher/courses/{id}/quiz', name: 'app_teacher_course_quiz', methods: ['GET'])]
    public function show(int $id, CourseRepository $courseRepository, CourseQuizRepository $quizRepository, CourseQuizSubmissionRepository $submissionRepository): Response
    {
        $teacher = $this->requireTeacherUser();
        $course = $courseRepository->find($id);

        if (!$course instanceof Course || $course->getTeacher()?->getId() !== $teacher->getId()) {
            throw $this->createNotFoundException('Course not found.');
        }

        $quiz = $quizRepository->findLatestByCourseAndTeacher($course, $teacher);
        $questions = $this->decodeQuestions($quiz?->getQuestionsJson());
        $submissionRows = [];

        if ($quiz instanceof CourseQuiz) {
            $submissions = $submissionRepository->findByQuizWithStudents($quiz);
            foreach ($submissions as $submission) {
                $student = $submission->getStudent();
                $answers = $this->decodeSubmittedAnswers($submission->getAnswersJson());

                $submissionRows[] = [
                    'student_name' => $student instanceof User ? $this->getTeacherName($student) : 'Unknown student',
                    'student_email' => $student instanceof User ? (string) $student->getEmail() : '-',
                    'score' => $submission->getScore(),
                    'submitted_at' => $submission->getSubmittedAt(),
                    'status' => $submission->getStatus(),
                    'answers' => $answers,
                ];
            }
        }

        return $this->render('dashboard/teacher_course_quiz.html.twig', [
            'teacher_name' => $this->getTeacherName($teacher),
            'course' => $course,
            'quiz' => $quiz,
            'questions' => $questions,
            'submission_rows' => $submissionRows,
        ]);
    }

    #[Route('/teacher/courses/{id}/quiz/generate', name: 'app_teacher_course_quiz_generate', methods: ['POST'])]
    public function generate(int $id, Request $request, CourseRepository $courseRepository, CourseQuizRepository $quizRepository, EntityManagerInterface $entityManager): Response
    {
        $teacher = $this->requireTeacherUser();
        $course = $courseRepository->find($id);

        if (!$course instanceof Course || $course->getTeacher()?->getId() !== $teacher->getId()) {
            throw $this->createNotFoundException('Course not found.');
        }

        if (!$this->isCsrfTokenValid('teacher_quiz_generate_' . $course->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid request token.');

            return $this->redirectToRoute('app_teacher_course_quiz', ['id' => $course->getId()]);
        }

        $quizPayload = $this->generateQuizWithAi($course);
        if ($quizPayload === null) {
            $this->addFlash('error', 'Quiz generation failed. Please try again.');

            return $this->redirectToRoute('app_teacher_course_quiz', ['id' => $course->getId()]);
        }

        $quiz = $quizRepository->findLatestByCourseAndTeacher($course, $teacher);
        if (!$quiz instanceof CourseQuiz) {
            $quiz = new CourseQuiz();
            $quiz->setCourse($course);
            $quiz->setTeacher($teacher);
            $quiz->setCreatedAt(new \DateTime());
            $entityManager->persist($quiz);
        }

        $quiz->setTitle((string) ($quizPayload['title'] ?? ('AI Quiz - ' . $course->getTitle())));
        $quiz->setSourceSummary((string) ($quizPayload['sourceSummary'] ?? 'Generated from course content.'));
        $quiz->setQuestionsJson(json_encode($quizPayload['questions'], JSON_UNESCAPED_UNICODE));
        $quiz->setStatus('published');
        $quiz->setUpdatedAt(new \DateTime());

        $entityManager->flush();
        $this->addFlash('success', 'AI quiz generated and published for students.');

        return $this->redirectToRoute('app_teacher_course_quiz', ['id' => $course->getId()]);
    }

    private function generateQuizWithAi(Course $course): ?array
    {
        $apiKey = trim((string) ($_ENV['HUGGING_FACE_API_KEY'] ?? ''));
        $model = trim((string) ($_ENV['HUGGING_FACE_MODEL'] ?? 'meta-llama/Llama-3.1-8B-Instruct'));

        if ($apiKey === '') {
            return null;
        }

        $sourceSummary = sprintf(
            "Course title: %s\nDescription: %s\nVideo URL: %s\nPDF: %s",
            (string) $course->getTitle(),
            (string) ($course->getDescription() ?? ''),
            (string) ($course->getVideoUrl() ?? ''),
            $course->getPdfFile() ? ('uploads/courses/pdf/' . $course->getPdfFile()) : 'No PDF provided'
        );

        $prompt = "Generate exactly 5 multiple-choice quiz questions in French for students. "
            . "Use the course source below. Return ONLY JSON with this shape: "
            . "{\"title\":\"...\",\"sourceSummary\":\"...\",\"questions\":[{\"question\":\"...\",\"options\":[\"...\",\"...\",\"...\",\"...\"],\"correctOption\":0,\"explanation\":\"...\"}]}. "
            . "correctOption must be an integer between 0 and 3.\n\n"
            . $sourceSummary;

        try {
            $client = HttpClient::create();
            $response = $client->request('POST', 'https://router.huggingface.co/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                ],
                'json' => [
                    'model' => $model,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are a quiz generator for e-learning assignments.',
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt,
                        ],
                    ],
                    'temperature' => 0.4,
                    'max_tokens' => 1000,
                ],
                'timeout' => 45,
            ]);

            $payload = json_decode($response->getContent(false), true);
            $content = (string) ($payload['choices'][0]['message']['content'] ?? '');

            $content = trim($content);
            $content = preg_replace('/^```json\s*|\s*```$/', '', $content) ?? $content;

            $quiz = json_decode($content, true);
            if (!is_array($quiz) || !isset($quiz['questions']) || !is_array($quiz['questions'])) {
                return null;
            }

            $normalizedQuestions = [];
            foreach ($quiz['questions'] as $item) {
                if (!is_array($item)) {
                    continue;
                }

                $question = trim((string) ($item['question'] ?? ''));
                $options = $item['options'] ?? null;
                $correct = isset($item['correctOption']) ? (int) $item['correctOption'] : -1;

                if ($question === '' || !is_array($options) || count($options) !== 4 || $correct < 0 || $correct > 3) {
                    continue;
                }

                $normalizedOptions = array_map(static fn ($value) => trim((string) $value), $options);
                if (in_array('', $normalizedOptions, true)) {
                    continue;
                }

                $normalizedQuestions[] = [
                    'question' => $question,
                    'options' => array_values($normalizedOptions),
                    'correctOption' => $correct,
                    'explanation' => trim((string) ($item['explanation'] ?? '')),
                ];
            }

            if (count($normalizedQuestions) < 3) {
                return null;
            }

            return [
                'title' => trim((string) ($quiz['title'] ?? ('Quiz - ' . $course->getTitle()))),
                'sourceSummary' => trim((string) ($quiz['sourceSummary'] ?? 'Generated from course resources.')),
                'questions' => $normalizedQuestions,
            ];
        } catch (\Throwable) {
            return null;
        }
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

    /**
     * @return array<int, int>
     */
    private function decodeSubmittedAnswers(?string $answersJson): array
    {
        if ($answersJson === null || $answersJson === '') {
            return [];
        }

        $decoded = json_decode($answersJson, true);
        if (!is_array($decoded)) {
            return [];
        }

        $normalized = [];
        foreach ($decoded as $value) {
            $normalized[] = ((int) $value) + 1;
        }

        return $normalized;
    }

    /**
     * @return array<int, int>
     */
    private function decodeSubmittedAnswerIndexes(?string $answersJson): array
    {
        if ($answersJson === null || $answersJson === '') {
            return [];
        }

        $decoded = json_decode($answersJson, true);
        if (!is_array($decoded)) {
            return [];
        }

        $normalized = [];
        foreach ($decoded as $value) {
            $normalized[] = (int) $value;
        }

        return $normalized;
    }

    private function requireTeacherUser(): User
    {
        $this->denyAccessUnlessGranted('ROLE_TEACHER');

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Teacher account required.');
        }

        return $user;
    }

    private function getTeacherName(User $teacher): string
    {
        $name = trim(sprintf('%s %s', (string) $teacher->getFirstName(), (string) $teacher->getLastName()));

        return $name !== '' ? $name : ((string) $teacher->getEmail() ?: 'Teacher');
    }
}
