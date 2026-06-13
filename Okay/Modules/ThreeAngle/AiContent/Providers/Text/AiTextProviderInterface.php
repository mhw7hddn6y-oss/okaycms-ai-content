<?php

namespace Okay\Modules\ThreeAngle\AiContent\Providers\Text;

interface AiTextProviderInterface
{
    public function requestJson(string $prompt, array $settings): array;
}
