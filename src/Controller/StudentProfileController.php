<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/student/profile')]
class StudentProfileController extends AbstractController
{
    #[Route('', name: 'app_student_profile', methods: ['GET', 'POST'])]
    public function profile(Request $request, EntityManagerInterface $entityManager): Response|RedirectResponse
    {
        $user = $this->requireStudent();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $profileUser = $entityManager->getRepository(User::class)->find($user->getId()) ?? $user;

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('student_profile_update', (string) $request->request->get('_token'))) {
                $this->addFlash('error', 'Invalid request token.');

                return $this->redirectToRoute('app_student_profile');
            }

            $firstName = trim((string) $request->request->get('first_name', ''));
            $lastName = trim((string) $request->request->get('last_name', ''));
            $phone = trim((string) $request->request->get('phone', ''));
            $gender = trim((string) $request->request->get('gender', ''));
            $dateOfBirthRaw = trim((string) $request->request->get('date_of_birth', ''));
            $profileImageFile = $request->files->get('profile_image');

            if ($dateOfBirthRaw !== '') {
                $dateOfBirth = \DateTime::createFromFormat('Y-m-d', $dateOfBirthRaw);
                $dateErrors = \DateTime::getLastErrors();
                $warningCount = is_array($dateErrors) ? (int) ($dateErrors['warning_count'] ?? 0) : 0;
                $errorCount = is_array($dateErrors) ? (int) ($dateErrors['error_count'] ?? 0) : 0;

                if (!$dateOfBirth || $warningCount > 0 || $errorCount > 0) {
                    $this->addFlash('error', 'Date of birth is invalid.');

                    return $this->redirectToRoute('app_student_profile');
                }

                $profileUser->setDateOfBirth($dateOfBirth);
            }

            if ($firstName !== '') {
                $profileUser->setFirstName($firstName);
            }

            if ($lastName !== '') {
                $profileUser->setLastName($lastName);
            }

            if ($phone !== '') {
                $profileUser->setPhone($phone);
            }

            if ($gender !== '') {
                $profileUser->setGender($gender);
            }

            if ($profileImageFile instanceof UploadedFile) {
                if ($profileImageFile->getError() !== UPLOAD_ERR_OK) {
                    $this->addFlash('error', 'Image upload failed. Please try again.');

                    return $this->redirectToRoute('app_student_profile');
                }

                if ($profileImageFile->getSize() > 2 * 1024 * 1024) {
                    $this->addFlash('error', 'Image is too large. Maximum size is 2 MB.');

                    return $this->redirectToRoute('app_student_profile');
                }

                $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
                $mimeType = (string) $profileImageFile->getMimeType();

                if (!in_array($mimeType, $allowedMimeTypes, true)) {
                    $this->addFlash('error', 'Unsupported image format. Use JPG, PNG, WEBP or GIF.');

                    return $this->redirectToRoute('app_student_profile');
                }

                $extension = $profileImageFile->guessExtension() ?: 'bin';
                $newFilename = sprintf('user_%d_%s.%s', $profileUser->getId(), bin2hex(random_bytes(8)), $extension);

                $targetDirectory = $this->getParameter('kernel.project_dir') . '/public/uploads/profile';
                if (!is_dir($targetDirectory)) {
                    mkdir($targetDirectory, 0775, true);
                }

                $profileImageFile->move($targetDirectory, $newFilename);

                $oldImage = $profileUser->getProfileImage();
                if ($oldImage) {
                    $oldImagePath = $targetDirectory . '/' . $oldImage;
                    if (is_file($oldImagePath)) {
                        @unlink($oldImagePath);
                    }
                }

                $profileUser->setProfileImage($newFilename);
            }

            $entityManager->flush();

            $this->addFlash('success', 'Profile updated successfully.');

            return $this->redirectToRoute('app_student_profile');
        }

        return $this->render('dashboard/student_profile.html.twig', [
            'user' => $profileUser,
        ]);
    }

    private function requireStudent(): ?User
    {
        $user = $this->getUser();

        return $user instanceof User ? $user : null;
    }
}