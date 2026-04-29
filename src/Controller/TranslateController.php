<?php

namespace App\Controller;

use App\Service\TranslationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class TranslateController extends AbstractController
{
    public function __construct(private TranslationService $translationService)
    {
    }

    #[Route('/api/translate', name: 'api_translate', methods: ['POST'])]
    public function translate(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Validation des données
        if (!isset($data['text']) || !isset($data['targetLang'])) {
            return $this->json([
                'success' => false,
                'message' => 'Paramètres manquants (text et targetLang requis)'
            ], 400);
        }

        $text = $data['text'];
        $sourceLang = $data['sourceLang'] ?? 'en'; // Par défaut anglais
        $targetLang = $data['targetLang'];

        try {
            $translatedText = $this->translationService->translate($text, $sourceLang, $targetLang);

            return $this->json([
                'success' => true,
                'original' => $text,
                'translated' => $translatedText,
                'sourceLang' => $sourceLang,
                'targetLang' => $targetLang
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la traduction: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/api/translate-batch', name: 'api_translate_batch', methods: ['POST'])]
    public function translateBatch(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['texts']) || !is_array($data['texts']) || !isset($data['targetLang'])) {
            return $this->json([
                'success' => false,
                'message' => 'Paramètres invalides (texts[], targetLang requis)'
            ], 400);
        }

        $texts = $data['texts'];
        $sourceLang = $data['sourceLang'] ?? 'en';
        $targetLang = $data['targetLang'];

        // Limiter le nombre de textes à traduire pour éviter le timeout
        if (count($texts) > 30) {
            $texts = array_slice($texts, 0, 30);
        }

        try {
            $translations = [];
            $startTime = microtime(true);
            $maxTime = 12; // 12 secondes max

            foreach ($texts as $text) {
                // Vérifier le timeout
                if ((microtime(true) - $startTime) > $maxTime) {
                    break;
                }

                $translated = $this->translationService->translate($text, $sourceLang, $targetLang);
                $translations[] = $translated;
            }

            return $this->json([
                'success' => true,
                'translations' => $translations,
                'targetLang' => $targetLang,
                'count' => count($translations)
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la traduction: ' . $e->getMessage()
            ], 500);
        }
    }
}
