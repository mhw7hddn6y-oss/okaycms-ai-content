<?php

namespace Okay\Modules\ThreeAngle\AiContent\Helpers;

use Okay\Core\EntityFactory;
use Okay\Core\Image;
use Okay\Core\Settings;
use Okay\Core\Translit;
use Okay\Entities\BlogEntity;
use Okay\Entities\FeaturesEntity;
use Okay\Entities\FeaturesValuesEntity;
use Okay\Entities\ImagesEntity;
use Okay\Entities\ProductsEntity;
use Okay\Modules\ThreeAngle\AiContent\Entities\AiContentHistoryEntity;
use Okay\Modules\ThreeAngle\AiContent\Providers\Text\AiTextProviderFactory;

class AiContentHelper
{
    private const DEFAULT_PROVIDER = 'openai';
    private const DEFAULT_MODEL = 'gpt-4o-mini';

    private Settings $settings;
    private EntityFactory $entityFactory;
    private AiTextProviderFactory $textProviderFactory;
    private Image $imageCore;

    public function __construct(
        Settings $settings,
        EntityFactory $entityFactory,
        AiTextProviderFactory $textProviderFactory,
        Image $imageCore
    )
    {
        $this->settings = $settings;
        $this->entityFactory = $entityFactory;
        $this->textProviderFactory = $textProviderFactory;
        $this->imageCore = $imageCore;
    }

    public function saveSettings(array $settings): void
    {
        $provider = $this->normalizeProvider((string)($settings['provider'] ?? self::DEFAULT_PROVIDER));
        $model = trim((string)($settings['model'] ?? ''));

        $this->settings->set('threeangle_ai_content_provider', $provider);
        $this->settings->set('threeangle_ai_content_api_key', trim((string)($settings['api_key'] ?? '')));
        $this->settings->set('threeangle_ai_content_model', $model !== '' ? $model : $this->getProviderDefaultModel($provider));
        $this->settings->set('threeangle_ai_content_language', trim((string)($settings['language'] ?? 'uk')));
        $this->settings->set('threeangle_ai_content_tone', trim((string)($settings['tone'] ?? 'expert')));
        $this->settings->set('threeangle_ai_content_max_tokens', (int)($settings['max_tokens'] ?? 1400));
        $this->settings->set('threeangle_ai_content_temperature', (float)($settings['temperature'] ?? 0.7));
    }

    public function getSettings(): array
    {
        $provider = $this->normalizeProvider((string)$this->settings->get('threeangle_ai_content_provider'));
        $model = (string)$this->settings->get('threeangle_ai_content_model');

        return [
            'provider' => $provider,
            'provider_label' => $this->getProviderLabel($provider),
            'base_url' => $this->getProviderBaseUrl($provider),
            'api_key' => (string)$this->settings->get('threeangle_ai_content_api_key'),
            'model' => $model !== '' ? $model : $this->getProviderDefaultModel($provider),
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

    public function generateProductBuilder(int $productId): array
    {
        /** @var ProductsEntity $productsEntity */
        $productsEntity = $this->entityFactory->get(ProductsEntity::class);
        $product = $productsEntity->get($productId);
        if (empty($product)) {
            throw new \RuntimeException('Product not found.');
        }

        $imageUrls = $this->getProductImageUrls($productId);
        $prompt = $this->buildProductBuilderPrompt($product, !empty($imageUrls));
        $result = $this->requestJson($prompt, $imageUrls);

        $this->log('product', $productId, 'builder', $prompt, json_encode($result, JSON_UNESCAPED_UNICODE), 'success');
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

    private function buildProductBuilderPrompt($product, bool $hasImage): string
    {
        $settings = $this->getSettings();

        $imageInstruction = $hasImage
            ? 'Use the attached product image to infer visible product type, style, color, shape, and material. Mark uncertain visual assumptions as conservative.'
            : 'No product image is attached. Use only the product name and existing text.';

        return $this->jsonInstruction() . "\n"
            . "Language: {$settings['language']}\n"
            . "Tone: {$settings['tone']}\n"
            . "Task: Build a complete ecommerce product draft from minimal source data.\n"
            . "{$imageInstruction}\n"
            . "Product name: {$product->name}\n"
            . "Existing short description: " . strip_tags((string)$product->annotation) . "\n"
            . "Existing description: " . strip_tags((string)$product->description) . "\n\n"
            . "Do not invent hidden specifications such as exact dimensions, weight, warranty, certificates, country, or delivery terms unless visibly obvious or present in text.\n"
            . "For characteristics, propose only useful ecommerce attributes. Keep values short. If uncertain, omit the feature.\n"
            . "Return JSON keys: annotation, description, meta_title, meta_description, meta_keywords, features.\n"
            . "features must be an array of objects with keys: name, value.";
    }

    private function requestJson(string $prompt, array $imageUrls = []): array
    {
        $settings = $this->getSettings();
        return $this->textProviderFactory->create($settings['provider'])->requestJson($prompt, $settings, $imageUrls);
    }

    private function getProductImageUrls(int $productId): array
    {
        /** @var ImagesEntity $imagesEntity */
        $imagesEntity = $this->entityFactory->get(ImagesEntity::class);
        $images = $imagesEntity->find([
            'product_id' => $productId,
            'limit' => 1,
        ]) ?: [];

        $urls = [];
        foreach ($images as $image) {
            if (!empty($image->filename)) {
                $urls[] = $this->imageCore->getResizeModifier($image->filename, 800, 800);
            }
        }

        return $urls;
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
            'provider' => $settings['provider'],
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

    private function normalizeProvider(string $provider): string
    {
        $provider = trim($provider);
        return in_array($provider, ['openai', 'openrouter', 'groq', 'gemini'], true) ? $provider : self::DEFAULT_PROVIDER;
    }

    private function getProviderLabel(string $provider): string
    {
        $labels = [
            'openai' => 'OpenAI',
            'openrouter' => 'OpenRouter',
            'groq' => 'Groq',
            'gemini' => 'Google Gemini',
        ];

        return $labels[$provider] ?? 'OpenAI';
    }

    private function getProviderBaseUrl(string $provider): string
    {
        $urls = [
            'openai' => 'https://api.openai.com/v1/chat/completions',
            'openrouter' => 'https://openrouter.ai/api/v1/chat/completions',
            'groq' => 'https://api.groq.com/openai/v1/chat/completions',
            'gemini' => 'https://generativelanguage.googleapis.com/v1beta/openai/chat/completions',
        ];

        return $urls[$provider] ?? $urls[self::DEFAULT_PROVIDER];
    }

    private function getProviderDefaultModel(string $provider): string
    {
        $models = [
            'openai' => self::DEFAULT_MODEL,
            'openrouter' => 'openrouter/free',
            'groq' => 'llama-3.1-8b-instant',
            'gemini' => 'gemini-2.5-flash-lite',
        ];

        return $models[$provider] ?? self::DEFAULT_MODEL;
    }
}
