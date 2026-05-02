<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Service\AdminFaceRecognitionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/face-verify')]
class AdminFaceVerificationController extends AbstractController
{
    #[Route('', name: 'app_admin_face_verify', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $isVerified = (bool) $request->getSession()->get('admin_face_verified', false);
        if ($isVerified) {
            return $this->redirectToRoute('app_admin_dashboard');
        }

        return $this->render('admin/face_verify.html.twig', [
            'has_reference_image' => $user->getProfileImage() !== null,
            'profile_url' => $this->generateUrl('app_admin_profile'),
        ]);
    }

    #[Route('/check', name: 'app_admin_face_check', methods: ['POST'])]
    public function check(
        Request $request,
        AdminFaceRecognitionService $faceRecognitionService,
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        $payload = json_decode((string) $request->getContent(), true);
        $capturedImageBase64 = is_array($payload) ? trim((string) ($payload['image'] ?? '')) : '';

        if ($capturedImageBase64 === '') {
            return $this->json(['success' => false, 'message' => 'Captured image is required.'], 400);
        }

        $profileImage = $user->getProfileImage();
        if ($profileImage === null || $profileImage === '') {
            return $this->json([
                'success' => false,
                'message' => 'No reference face image found. Upload a profile photo first.',
                'profileUrl' => $this->generateUrl('app_admin_profile'),
            ], 422);
        }

        $referencePath = $this->getParameter('kernel.project_dir') . '/public/uploads/profile/' . $profileImage;
        if (!is_file($referencePath)) {
            return $this->json([
                'success' => false,
                'message' => 'Reference face image file is missing on server.',
                'profileUrl' => $this->generateUrl('app_admin_profile'),
            ], 422);
        }

        $referenceRaw = file_get_contents($referencePath);
        if ($referenceRaw === false) {
            return $this->json(['success' => false, 'message' => 'Unable to read reference image.'], 500);
        }

        $referenceImageBase64 = base64_encode($referenceRaw);

        $result = $faceRecognitionService->verify($capturedImageBase64, $referenceImageBase64);
        if (!$result['ok']) {
            return $this->json([
                'success' => false,
                'message' => $result['message'],
            ], 502);
        }

        if (!$result['match']) {
            $request->getSession()->set('admin_face_verified', false);

            return $this->json([
                'success' => false,
                'message' => 'Face mismatch. Access denied.',
                'distance' => $result['distance'],
            ], 403);
        }

        $request->getSession()->set('admin_face_verified', true);

        return $this->json([
            'success' => true,
            'message' => 'Face verified. Access granted.',
            'redirect' => $this->generateUrl('app_admin_dashboard'),
            'distance' => $result['distance'],
        ]);
    }
}
