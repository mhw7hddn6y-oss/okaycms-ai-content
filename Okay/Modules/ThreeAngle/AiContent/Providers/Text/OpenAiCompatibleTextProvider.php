<?php

namespace Okay\Modules\ThreeAngle\AiContent\Providers\Text;

class OpenAiCompatibleTextProvider implements AiTextProviderInterface
{
    public function requestJson(string $prompt, array $settings): array
    {
        if ($settings['api_key'] === '') {
            throw new \RuntimeException($settings['provider_label'] . ' API key is empty.');
        }

        $payload = [
            'model' => $settings['model'],
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are an ecommerce content assistant. Return valid JSON only.',
                ],
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ],
            'temperature' => $settings['temperature'],
            'max_tokens' => $settings['max_tokens'],
        ];

        $response = $this->postJson($settings['base_url'], $settings['api_key'], $payload, $settings['provider']);
        $content = $response['choices'][0]['message']['content'] ?? null;
        if (!$content) {
            $message = $response['error']['message'] ?? $settings['provider_label'] . ' returned an empty response.';
            throw new \RuntimeException($message);
        }

        $json = json_decode($this->stripJsonFence($content), true);
        if (!is_array($json)) {
            throw new \RuntimeException('AI response is not valid JSON.');
        }

        return $json;
    }

    private function postJson(string $url, string $apiKey, array $payload, string $provider): array
    {
        $headers = [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json',
        ];

        if ($provider === 'openrouter') {
            $headers[] = 'HTTP-Referer: https://okay-cms.com/';
            $headers[] = 'X-Title: OkayCMS AI Content Studio';
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));

        $rawResponse = curl_exec($ch);
        $curlError = curl_error($ch);
        $statusCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($rawResponse === false) {
            throw new \RuntimeException('AI request failed: ' . $curlError);
        }

        $decoded = json_decode($rawResponse, true);
        if (!is_array($decoded)) {
            throw new \RuntimeException('AI provider returned an invalid response.');
        }

        if ($statusCode >= 400) {
            $message = $decoded['error']['message'] ?? 'AI provider request failed with HTTP ' . $statusCode . '.';
            throw new \RuntimeException($message);
        }

        return $decoded;
    }

    private function stripJsonFence(string $content): string
    {
        $content = trim($content);
        $content = preg_replace('/^```json\s*/i', '', $content);
        $content = preg_replace('/^```\s*/', '', $content);
        $content = preg_replace('/\s*```$/', '', $content);
        return trim($content);
    }
}
