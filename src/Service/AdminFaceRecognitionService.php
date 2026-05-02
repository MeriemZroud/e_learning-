<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AdminFaceRecognitionService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $faceApiUrl,
        private readonly float $faceTolerance,
    ) {
    }

    /**
     * @return array{ok: bool, match: bool, message: string, distance: float|null}
     */
    public function verify(string $capturedImageBase64, string $referenceImageBase64): array
    {
        try {
            $response = $this->httpClient->request('POST', $this->faceApiUrl, [
                'json' => [
                    'captured_image_base64' => $capturedImageBase64,
                    'reference_image_base64' => $referenceImageBase64,
                    'tolerance' => $this->faceTolerance,
                ],
                'timeout' => 12,
            ]);

            $status = $response->getStatusCode();
            $data = $response->toArray(false);
            $match = (bool) ($data['match'] ?? false);
            $distance = isset($data['distance']) ? (float) $data['distance'] : null;
            $message = (string) ($data['reason'] ?? 'Face verification failed.');

            if ($status >= 400) {
                return [
                    'ok' => false,
                    'match' => false,
                    'message' => $message,
                    'distance' => $distance,
                ];
            }

            return [
                'ok' => true,
                'match' => $match,
                'message' => $match ? 'Face verified.' : 'Face does not match.',
                'distance' => $distance,
            ];
        } catch (ExceptionInterface|\Throwable) {
            return [
                'ok' => false,
                'match' => false,
                'message' => 'Face API unavailable. Please try again.',
                'distance' => null,
            ];
        }
    }
}
