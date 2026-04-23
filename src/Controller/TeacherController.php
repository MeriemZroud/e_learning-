<?php

namespace App\Controller;

use App\Entity\Course;
use App\Entity\User;
use App\Repository\CourseRepository;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Attribute\Route;

class TeacherController extends AbstractController
{
    #[Route('/teacher/courses', name: 'app_teacher_course_index', methods: ['GET'])]
    public function index(CourseRepository $courseRepository, NotificationRepository $notificationRepository): Response
    {
        $teacher = $this->requireTeacherUser();

        return $this->render('dashboard/teacher_courses.html.twig', [
            'teacher_name' => $this->getTeacherName($teacher),
            'courses' => $courseRepository->findByTeacher($teacher),
            'unreadNotifications' => $notificationRepository->findUnreadByUser($teacher),
        ]);
    }

    #[Route('/teacher/courses/new', name: 'app_teacher_course_new', methods: ['GET', 'POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager, NotificationRepository $notificationRepository): Response
    {
        $teacher = $this->requireTeacherUser();
        $course = new Course();
        $course->setIsPublished(true);

        if ($request->isMethod('POST')) {
            $error = $this->hydrateCourseFromRequest($course, $request);
            if ($error !== null) {
                $this->addFlash('error', $error);
            } else {
                $course->setTeacher($teacher);
                $course->setCreatedAt(new \DateTime());
                $course->setUpdatedAt(new \DateTime());

                $entityManager->persist($course);
                $entityManager->flush();

                $this->addFlash('success', 'Course created successfully.');

                return $this->redirectToRoute('app_teacher_course_index');
            }
        }

        return $this->render('dashboard/teacher_course_form.html.twig', [
            'teacher_name' => $this->getTeacherName($teacher),
            'course' => $course,
            'is_edit' => false,
            'unreadNotifications' => $notificationRepository->findUnreadByUser($teacher),
        ]);
    }

    #[Route('/teacher/courses/{id}/edit', name: 'app_teacher_course_edit', methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request, CourseRepository $courseRepository, EntityManagerInterface $entityManager, NotificationRepository $notificationRepository): Response
    {
        $teacher = $this->requireTeacherUser();
        $course = $courseRepository->find($id);

        if (!$course instanceof Course || $course->getTeacher()?->getId() !== $teacher->getId()) {
            throw $this->createNotFoundException('Course not found.');
        }

        if ($request->isMethod('POST')) {
            $error = $this->hydrateCourseFromRequest($course, $request);
            if ($error !== null) {
                $this->addFlash('error', $error);
            } else {
                $course->setUpdatedAt(new \DateTime());
                $entityManager->flush();

                $this->addFlash('success', 'Course updated successfully.');

                return $this->redirectToRoute('app_teacher_course_index');
            }
        }

        return $this->render('dashboard/teacher_course_form.html.twig', [
            'teacher_name' => $this->getTeacherName($teacher),
            'course' => $course,
            'is_edit' => true,
            'unreadNotifications' => $notificationRepository->findUnreadByUser($teacher),
        ]);
    }

    #[Route('/teacher/courses/{id}/delete', name: 'app_teacher_course_delete', methods: ['POST'])]
    public function delete(int $id, Request $request, CourseRepository $courseRepository, EntityManagerInterface $entityManager): RedirectResponse
    {
        $teacher = $this->requireTeacherUser();
        $course = $courseRepository->find($id);

        if (!$course instanceof Course || $course->getTeacher()?->getId() !== $teacher->getId()) {
            $this->addFlash('error', 'Course not found.');

            return $this->redirectToRoute('app_teacher_course_index');
        }

        if (!$this->isCsrfTokenValid('teacher_course_delete_' . $course->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid request token.');

            return $this->redirectToRoute('app_teacher_course_index');
        }

        $this->deletePdfFile($course->getPdfFile());

        $entityManager->remove($course);
        $entityManager->flush();

        $this->addFlash('success', 'Course deleted.');

        return $this->redirectToRoute('app_teacher_course_index');
    }

    private function hydrateCourseFromRequest(Course $course, Request $request): ?string
    {
        if (!$this->isCsrfTokenValid('teacher_course_save', (string) $request->request->get('_token'))) {
            return 'Invalid form submission.';
        }

        $title = trim((string) $request->request->get('title', ''));
        $description = trim((string) $request->request->get('description', ''));
        $videoUrl = trim((string) $request->request->get('video_url', ''));
        $thumbnailUrl = trim((string) $request->request->get('thumbnail_url', ''));
        $isPublished = $request->request->getBoolean('is_published', true);
        $pdfFile = $request->files->get('pdf_file');

        if ($title === '' || $videoUrl === '') {
            return 'Title and video URL are required.';
        }

        if (filter_var($videoUrl, FILTER_VALIDATE_URL) === false) {
            return 'Please provide a valid video URL.';
        }

        if ($thumbnailUrl !== '' && filter_var($thumbnailUrl, FILTER_VALIDATE_URL) === false) {
            return 'Please provide a valid thumbnail URL.';
        }

        if ($pdfFile instanceof UploadedFile) {
            if (strtolower((string) $pdfFile->getClientOriginalExtension()) !== 'pdf') {
                return 'Only PDF files are allowed.';
            }

            if ($pdfFile->getSize() !== null && $pdfFile->getSize() > 10 * 1024 * 1024) {
                return 'PDF file is too large. Maximum size is 10 MB.';
            }

            try {
                $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/courses/pdf';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0775, true);
                }

                $fileName = bin2hex(random_bytes(16)) . '.pdf';
                $pdfFile->move($uploadDir, $fileName);

                $this->deletePdfFile($course->getPdfFile());
                $course->setPdfFile($fileName);
            } catch (\Throwable|FileException $e) {
                return 'Unable to upload PDF file. Please try again.';
            }
        }

        $course->setTitle($title);
        $course->setDescription($description !== '' ? $description : null);
        $course->setVideoUrl($videoUrl);
        $course->setThumbnailUrl($thumbnailUrl !== '' ? $thumbnailUrl : null);
        $course->setIsPublished($isPublished);

        return null;
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

    private function deletePdfFile(?string $fileName): void
    {
        if ($fileName === null || $fileName === '') {
            return;
        }

        $filePath = $this->getParameter('kernel.project_dir') . '/public/uploads/courses/pdf/' . $fileName;
        if (is_file($filePath)) {
            @unlink($filePath);
        }
    }
}
