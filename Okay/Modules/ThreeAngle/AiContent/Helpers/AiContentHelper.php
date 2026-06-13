<?php

namespace Okay\Modules\ThreeAngle\AiContent\Helpers;

use Okay\Core\EntityFactory;
use Okay\Core\Settings;
use Okay\Core\Translit;
use Okay\Entities\BlogEntity;
use Okay\Entities\FeaturesEntity;
use Okay\Entities\FeaturesValuesEntity;
use Okay\Entities\ProductsEntity;
use Okay\Modules\ThreeAngle\AiContent\Entities\AiContentHistoryEntity;
use Orhanerday\OpenAi\OpenAi;

class AiContentHelper
{
    private const DEFAULT_MODEL = 'gpt-4o-mini';

    private Settings $settings;
    private EntityFactory $entityFactory;

    public function __construct(Settings $settings, EntityFactory $entityFactory)
    {
        $this->settings = $settings;
        $this->entityFactory = $entityFactory;
    }

    public function saveSettings(array $settings): void
    {
        $this->settings->set('threeangle_ai_content_api_key', trim((string)($settings['api_key'] ?? '')));
        $this->settings->set('threeangle_ai_content_model', trim((string)($settings['model'] ?? self::DEFAULT_MODEL)));
        $this->settings->set('threeangle_ai_content_language', trim((string)($settings['language'] ?? 'uk')));
        $this->settings->set('threeangle_ai_content_tone', trim((string)($settings['tone'] ?? 'expert')));
        $this->settings->set('threeangle_ai_content_max_tokens', (int)($settings['max_tokens'] ?? 1400));
        $this->settings->set('threeangle_ai_content_temperature', (float)($settings['temperature'] ?? 0.7));
    }

    public function getSettings(): array
    {
        return [
            'api_key' => (string)$this->settings->get('threeangle_ai_content_api_key'),
            'model' => (string)$this->settings->get('threeangle_ai_content_model') ?: self::DEFAULT_MODEL,
            'language' => (string)$this->settings->get('threeangle_ai_content_language') ?: 'uk',
            'tone' => (string)$this->settings->get('threeangle_ai_content_tone') ?: 'expert',
            'max_tokens' => (int)$this->settings->get('threeangle_ai_content_max_tokens') ?: 1400,
            'temperature' => (float)$this->settings->get('threeangle_ai_content_temperature') ?: 0.7,
        ];
    }

    public function generateProductContent(int $productId, string $mode): array
    {
        /** @var ProductsEntity $productsEntity */
        $productsEntity = $this->entityFactory->get(ProductsEntity::class);
        $product = $productsEntity->get($productId);
        if (empty($product)) {
            throw new \RuntimeException('Product not found.');
        }

        $prompt = $this->buildProductPrompt($product, $mode);
        $result = $this->requestJson($prompt);

        $this->log('product', $productId, $mode, $prompt, json_encode($result, JSON_UNESCAPED_UNICODE), 'success');
        return $result;
    }

    public function applyProductContent(int $productId, array $content): void
    {
        /** @var ProductsEntity $productsEntity */
        $productsEntity = $this->entityFactory->get(ProductsEntity::class);

        $update = [];
        foreach (['annotation', 'description', 'meta_title', 'meta_description', 'meta_keywords'] as $field) {
            if (!empty($content[$field])) {
                $update[$field] = trim((string)$content[$field]);
            }
        }

        if (!empty($update)) {
            $productsEntity->update($productId, $update);
        }
    }

    public function generateBlogDraft(string $topic): array
    {
        $topic = trim($topic);
        if ($topic === '') {
            throw new \RuntimeException('Blog topic is required.');
        }

        $prompt = $this->buildBlogPrompt($topic);
        $result = $this->requestJson($prompt);
        $this->log('blog', null, 'blog_draft', $prompt, json_encode($result, JSON_UNESCAPED_UNICODE), 'success');
        return $result;
    }

    public function createBlogDraft(array $content): int
    {
        /** @var BlogEntity $blogEntity */
        $blogEntity = $this->entityFactory->get(BlogEntity::class);

        $name = trim((string)($content['name'] ?? 'AI draft'));
        $url = Translit::translit($name);
        $url = str_replace('.', '', $url);

        return (int)$blogEntity->add([
            'name' => $name,
            'url' => $url,
            'meta_title' => trim((string)($content['meta_title'] ?? $name)),
            'meta_description' => trim((string)($content['meta_description'] ?? '')),
            'annotation' => trim((string)($content['annotation'] ?? '')),
            'description' => trim((string)($content['description'] ?? '')),
            'visible' => 0,
            'date' => date('Y-m-d H:i:s'),
        ]);
    }

    public function getRecentHistory(int $limit = 20): array
    {
        /** @var AiContentHistoryEntity $historyEntity */
        $historyEntity = $this->entityFactory->get(AiContentHistoryEntity::class);
        return $historyEntity->find(['limit' => $limit]) ?: [];
    }

    private function buildProductPrompt($product, string $mode): string
    {
        $settings = $this->getSettings();
        $facts = [
            'Name: ' . $product->name,
            'Short description: ' . strip_tags((string)$product->annotation),
            'Description: ' . strip_tags((string)$product->description),
        ];

        $features = $this->getProductFeatures((int)$product->id);
        if ($features !== '') {
            $facts[] = "Features:\n" . $features;
        }

        $task = $mode === 'seo'
            ? 'Create SEO fields for this product.'
            : 'Create a concise selling short description and a complete product description.';

        return $this->jsonInstruction() . "\n"
            . "Language: {$settings['language']}\n"
            . "Tone: {$settings['tone']}\n"
            . "Task: {$task}\n"
            . "Use only the facts below. Do not invent specifications, materials, certifications, delivery terms, or warranty.\n\n"
            . implode("\n", $facts) . "\n\n"
            . "Return JSON keys: annotation, description, meta_title, meta_description, meta_keywords.";
    }

    private function buildBlogPrompt(string $topic): string
    {
        $settings = $this->getSettings();

        return $this->jsonInstruction() . "\n"
            . "Language: {$settings['language']}\n"
            . "Tone: {$settings['tone']}\n"
            . "Task: Create a blog article draft for an ecommerce website.\n"
            . "Topic: {$topic}\n"
            . "Avoid unsupported claims and medical/legal/financial advice unless explicitly present in the topic.\n"
            . "Return JSON keys: name, annotation, description, meta_title, meta_description.";
    }

    private function requestJson(string $prompt): array
    {
        $settings = $this->getSettings();
        if ($settings['api_key'] === '') {
            throw new \RuntimeException('OpenAI API key is empty.');
        }

        $openAi = new OpenAi($settings['api_key']);
        $response = $openAi->chat([
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
        ]);

        $decoded = json_decode($response, true);
        $content = $decoded['choices'][0]['message']['content'] ?? null;
        if (!$content) {
            $message = $decoded['error']['message'] ?? 'OpenAI returned an empty response.';
            throw new \RuntimeException($message);
        }

        $json = json_decode($this->stripJsonFence($content), true);
        if (!is_array($json)) {
            throw new \RuntimeException('AI response is not valid JSON.');
        }

        return $json;
    }

    private function getProductFeatures(int $productId): string
    {
        /** @var FeaturesValuesEntity $featuresValuesEntity */
        $featuresValuesEntity = $this->entityFactory->get(FeaturesValuesEntity::class);
        /** @var FeaturesEntity $featuresEntity */
        $featuresEntity = $this->entityFactory->get(FeaturesEntity::class);

        $featuresValues = [];
        foreach ($featuresValuesEntity->find(['product_id' => $productId]) ?: [] as $fv) {
            $featuresValues[$fv->feature_id][] = $fv->value;
        }

        if (empty($featuresValues)) {
            return '';
        }

        $lines = [];
        foreach ($featuresEntity->find(['id' => array_keys($featuresValues)]) ?: [] as $feature) {
            $lines[] = $feature->name . ': ' . implode(', ', $featuresValues[$feature->id]);
        }

        return implode("\n", $lines);
    }

    private function log(string $entityType, ?int $entityId, string $action, string $prompt, ?string $result, string $status, ?string $error = null): void
    {
        /** @var AiContentHistoryEntity $historyEntity */
        $historyEntity = $this->entityFactory->get(AiContentHistoryEntity::class);
        $settings = $this->getSettings();
        $historyEntity->add([
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'action' => $action,
            'language' => $settings['language'],
            'model' => $settings['model'],
            'prompt' => $prompt,
            'result' => $result,
            'status' => $status,
            'error' => $error,
            'created_at' => 'NOW()',
        ]);
    }

    private function jsonInstruction(): string
    {
        return 'Respond with a single valid JSON object. Do not wrap JSON in markdown.';
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
