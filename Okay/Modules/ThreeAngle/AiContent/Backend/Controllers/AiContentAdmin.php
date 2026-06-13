<?php

namespace Okay\Modules\ThreeAngle\AiContent\Backend\Controllers;

use Okay\Admin\Controllers\IndexAdmin;
use Okay\Modules\ThreeAngle\AiContent\Helpers\AiContentHelper;

class AiContentAdmin extends IndexAdmin
{
    public function fetch(AiContentHelper $aiContentHelper)
    {
        $messageSuccess = null;
        $messageError = null;
        $generated = null;

        if ($this->request->method('post')) {
            try {
                $action = $this->request->post('action');
                if ($action === 'save_settings') {
                    $aiContentHelper->saveSettings($this->request->post('settings'));
                    $messageSuccess = 'settings_saved';
                } elseif ($action === 'generate_product') {
                    $productId = $this->request->post('product_id', 'integer');
                    $mode = $this->request->post('product_mode', 'string') ?: 'description';
                    $generated = [
                        'type' => 'product',
                        'entity_id' => $productId,
                        'content' => $aiContentHelper->generateProductContent($productId, $mode),
                    ];
                } elseif ($action === 'apply_product') {
                    $productId = $this->request->post('product_id', 'integer');
                    $aiContentHelper->applyProductContent($productId, $this->request->post('content'));
                    $messageSuccess = 'product_updated';
                } elseif ($action === 'generate_blog') {
                    $generated = [
                        'type' => 'blog',
                        'content' => $aiContentHelper->generateBlogDraft((string)$this->request->post('blog_topic')),
                    ];
                } elseif ($action === 'create_blog') {
                    $blogId = $aiContentHelper->createBlogDraft($this->request->post('content'));
                    $messageSuccess = 'blog_created_' . $blogId;
                }
            } catch (\Throwable $e) {
                $messageError = $e->getMessage();
            }
        }

        $this->design->assign('ai_settings', $aiContentHelper->getSettings());
        $this->design->assign('ai_history', $aiContentHelper->getRecentHistory());
        $this->design->assign('prefill_product_id', $this->request->get('product_id', 'integer'));
        $this->design->assign('prefill_product_mode', $this->request->get('product_mode', 'string') ?: 'description');
        $this->design->assign('generated', $generated);
        $this->design->assign('message_success', $messageSuccess);
        $this->design->assign('message_error', $messageError);

        $this->response->setContent($this->design->fetch('ai_content.tpl'));
    }
}
