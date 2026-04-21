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
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
