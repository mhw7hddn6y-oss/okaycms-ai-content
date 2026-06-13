<?php

namespace Okay\Modules\ThreeAngle\AiContent\Entities;

use Okay\Core\Entity\Entity;

class AiContentHistoryEntity extends Entity
{
    protected static $fields = [
        'id',
        'entity_type',
        'entity_id',
        'action',
        'language',
        'provider',
        'model',
        'prompt',
        'result',
        'status',
        'error',
        'created_at',
    ];

    protected static $defaultOrderFields = [
        'id DESC',
    ];

    protected static $table = '__threeangle__ai_content__history';
    protected static $tableAlias = 'taic_h';
}
