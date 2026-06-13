<?php

namespace Okay\Modules\ThreeAngle\AiContent;

use Okay\Core\EntityFactory;
use Okay\Core\Image;
use Okay\Core\OkayContainer\Reference\ServiceReference as SR;
use Okay\Core\Settings;
use Okay\Modules\ThreeAngle\AiContent\Helpers\AiContentHelper;
use Okay\Modules\ThreeAngle\AiContent\Providers\Text\AiTextProviderFactory;
use Okay\Modules\ThreeAngle\AiContent\Providers\Text\OpenAiCompatibleTextProvider;

return [
    OpenAiCompatibleTextProvider::class => [
        'class' => OpenAiCompatibleTextProvider::class,
    ],
    AiTextProviderFactory::class => [
        'class' => AiTextProviderFactory::class,
        'arguments' => [
            new SR(OpenAiCompatibleTextProvider::class),
        ],
    ],
    AiContentHelper::class => [
        'class' => AiContentHelper::class,
        'arguments' => [
            new SR(Settings::class),
            new SR(EntityFactory::class),
            new SR(AiTextProviderFactory::class),
            new SR(Image::class),
        ],
    ],
];
