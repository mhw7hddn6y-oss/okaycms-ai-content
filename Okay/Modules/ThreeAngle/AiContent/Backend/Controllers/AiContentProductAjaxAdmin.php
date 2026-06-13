<?php

namespace Okay\Modules\ThreeAngle\AiContent\Backend\Controllers;

use Okay\Admin\Controllers\IndexAdmin;
use Okay\Modules\ThreeAngle\AiContent\Helpers\AiContentHelper;

class AiContentProductAjaxAdmin extends IndexAdmin
{
    public function fetch(AiContentHelper $aiContentHelper)
    {
        $result = [
            'success' => false,
            'error' => 'Request failed.',
        ];

        try {
            $productId = $this->request->post('product_id', 'integer');
            $mode = $this->request->post('product_mode', 'string') ?: 'description';

            if (empty($productId)) {
                throw new \RuntimeException('Product ID is required.');
            }

            if ($mode === 'builder') {
                $content = $aiContentHelper->generateProductBuilder($productId);
            } else {
                $content = $aiContentHelper->generateProductContent($productId, $mode);
            }

            $result = [
                'success' => true,
                'content' => $content,
            ];
        } catch (\Throwable $e) {
            $result['error'] = $e->getMessage();
        }

        $this->response->setContent(json_encode($result, JSON_UNESCAPED_UNICODE), RESPONSE_JSON);
    }
}
