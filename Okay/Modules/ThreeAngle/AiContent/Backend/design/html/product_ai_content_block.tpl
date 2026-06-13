{if !empty($product->id)}
    <div class="mt-1">
        <a class="btn btn_small btn_blue" href="index.php?controller=ThreeAngle.AiContent.AiContentAdmin&product_id={$product->id|escape}&product_mode=description">
            AI descriptions
        </a>
        <a class="btn btn_small btn_blue" href="index.php?controller=ThreeAngle.AiContent.AiContentAdmin&product_id={$product->id|escape}&product_mode=seo">
            AI SEO draft
        </a>
    </div>
{/if}
