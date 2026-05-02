<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class RecaptchaService
{
    private string $secret;
    private ?HttpClientInterface $client;

    public function __construct(string $recaptchaSecret, ?HttpClientInterface $client = null)
    {
        $this->secret = $recaptchaSecret;
        $this->client = $client;
    }

    public function verify(string $token, ?string $remoteIp = null): bool
    {
        if ($token === '') {
            return false;
        }

        $url = 'https://www.google.com/recaptcha/api/siteverify';
        $params = http_build_query([
            'secret' => $this->secret,
            'response' => $token,
            'remoteip' => $remoteIp,
        ]);

        if ($this->client !== null) {
            try {
                $response = $this->client->request('POST', $url, [
                    'body' => $params,
                    'headers' => [
                        'Content-Type' => 'application/x-www-form-urlencoded',
                    ],
                ]);

                $content = $response->getContent();
                $data = json_decode($content, true);

                return !empty($data['success']);
            } catch (\Throwable) {
                return false;
            }
        }

        $opts = [
            'http' => [
                'method' => 'POST',
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'content' => $params,
                'timeout' => 5,
            ],
        ];

        $context = stream_context_create($opts);
        $result = @file_get_contents($url, false, $context);
        if ($result === false) {
            return false;
        }

        $data = json_decode($result, true);

        return !empty($data['success']);
    }
}
