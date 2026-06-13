<?php

namespace Okay\Modules\ThreeAngle\AiContent\Init;

use Okay\Core\Modules\AbstractInit;
use Okay\Core\Modules\EntityField;
use Okay\Modules\ThreeAngle\AiContent\Entities\AiContentHistoryEntity;

class Init extends AbstractInit
{
    public function install()
    {
        $this->setBackendMainController('AiContentAdmin');
        $this->migrateHistoryTable();
    }

    public function update_1_0_1()
    {
        $this->migrateEntityField(
            AiContentHistoryEntity::class,
            (new EntityField('provider'))->setTypeVarchar(32)->setDefault('openai')
        );
    }

    private function migrateHistoryTable()
    {
        $this->migrateEntityTable(AiContentHistoryEntity::class, [
            (new EntityField('id'))->setIndexPrimaryKey()->setTypeInt(11, false)->setAutoIncrement(),
            (new EntityField('entity_type'))->setTypeVarchar(32),
            (new EntityField('entity_id'))->setTypeInt(11)->setNullable(),
            (new EntityField('action'))->setTypeVarchar(64),
            (new EntityField('language'))->setTypeVarchar(8),
            (new EntityField('provider'))->setTypeVarchar(32)->setDefault('openai'),
            (new EntityField('model'))->setTypeVarchar(64),
            (new EntityField('prompt'))->setTypeText(),
            (new EntityField('result'))->setTypeText()->setNullable(),
            (new EntityField('status'))->setTypeVarchar(32),
            (new EntityField('error'))->setTypeText()->setNullable(),
            (new EntityField('created_at'))->setTypeDatetime(),
        ]);
    }

    public function init()
    {
        $this->registerBackendController('AiContentAdmin');
        $this->addBackendControllerPermission('AiContentAdmin', 'threeangle__ai_content');

        $this->extendBackendMenu('ai_content_menu', [
            'ai_content_menu' => ['AiContentAdmin'],
        ]);
    }
}
