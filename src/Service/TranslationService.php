<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class TranslationService
{
    public function __construct(private HttpClientInterface $httpClient)
    {
    }

    /**
     * Traduit un texte en utilisant Google Translate API
     * 
     * @param string $text Texte à traduire
     * @param string $sourceLang Code langue source (ex: 'en')
     * @param string $targetLang Code langue cible (ex: 'fr')
     * @return string Texte traduit
     */
    public function translate(string $text, string $sourceLang = 'en', string $targetLang = 'fr'): string
    {
        try {
            // Ne pas traduire si source === target
            if (strtolower($sourceLang) === strtolower($targetLang)) {
                return $text;
            }

            // Utiliser Google Translate via une requête GET simple
            $url = 'https://translate.googleapis.com/translate_a/single';
            
            $response = $this->httpClient->request('GET', $url, [
                'query' => [
                    'client' => 'gtx',
                    'sl' => strtolower($sourceLang),
                    'tl' => strtolower($targetLang),
                    'dt' => 't',
                    'q' => $text
                ],
                'timeout' => 4,
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0'
                ]
            ]);

            $result = $response->getContent();
            
            // Parser la réponse de Google Translate
            // La réponse est un array JSON complexe, on récupère la traduction
            if (preg_match('/\[\[\["([^"]+)"/', $result, $matches)) {
                return htmlspecialchars_decode($matches[1], ENT_QUOTES);
            }

            return $text;
        } catch (\Exception $e) {
            // Fallback: retourner le texte original
            return $text;
        }
    }

    /**
     * Traduit un tableau d'éléments
     */
    public function translateArray(array $items, string $sourceLang = 'en', string $targetLang = 'fr'): array
    {
        $translated = [];
        foreach ($items as $key => $item) {
            if (is_string($item)) {
                $translated[$key] = $this->translate($item, $sourceLang, $targetLang);
            } else {
                $translated[$key] = $item;
            }
        }
        return $translated;
    }
}
