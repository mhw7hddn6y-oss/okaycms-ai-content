<?php

namespace Okay\Modules\ThreeAngle\AiContent\Providers\Text;

class AiTextProviderFactory
{
    private OpenAiCompatibleTextProvider $openAiCompatibleTextProvider;

    public function __construct(OpenAiCompatibleTextProvider $openAiCompatibleTextProvider)
    {
        $this->openAiCompatibleTextProvider = $openAiCompatibleTextProvider;
    }

    public function create(string $provider): AiTextProviderInterface
    {
        switch ($provider) {
            case 'openai':
            case 'openrouter':
            case 'groq':
            case 'gemini':
                return $this->openAiCompatibleTextProvider;
            default:
                throw new \RuntimeException('Unsupported AI provider.');
        }
    }
}
