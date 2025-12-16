<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class GeminiChatService
{
    private const API_URL = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent';
    private string $apiKey;

    public function __construct(string $gemini_api_key, private HttpClientInterface $client)
    {
        $this->apiKey = $gemini_api_key;
    }

    public function getResponse(string $userMessage): JsonResponse
    {
        $systemPrompt = "You are OneLoc AI, a helpful assistant for the OneLoc real estate app in Tunisia. "
            . "Users can list properties, search homes, make reservations, and contact owners. "
            . "Answer in short, clear English. Be friendly. If unsure, say: 'Contact support@oneloc.tn'.";

        $payload = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [['text' => $systemPrompt . "\n\nUser: " . $userMessage]]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.7,
                'maxOutputTokens' => 150,
                'topP' => 1,
            ]
        ];

        try {
            $response = $this->client->request('POST', self::API_URL . '?key=' . $this->apiKey, [
                'json' => $payload,
                'headers' => ['Content-Type' => 'application/json']
            ]);

            $data = $response->toArray();

            $aiText = $data['candidates'][0]['content']['parts'][0]['text'] ?? 'Sorry, I couldn\'t respond.';

            return new JsonResponse(['success' => true, 'message' => $aiText]);
        } catch (\Exception $e) {
            // Log the actual error for debugging
            error_log('Gemini API Error: ' . $e->getMessage());
            return new JsonResponse([
                'success' => false, 
                'message' => 'AI is unavailable. Try later.',
                'error' => $e->getMessage() // Temporary for debugging
            ], 500);
        }
    }
}