<?php

namespace Okay\Modules\ThreeAngle\AiContent;

use Okay\Core\EntityFactory;
use Okay\Core\OkayContainer\Reference\ServiceReference as SR;
use Okay\Core\Settings;
use Okay\Modules\ThreeAngle\AiContent\Helpers\AiContentHelper;

return [
    AiContentHelper::class => [
        'class' => AiContentHelper::class,
        'arguments' => [
            new SR(Settings::class),
            new SR(EntityFactory::class),
        ],
    ],
];
