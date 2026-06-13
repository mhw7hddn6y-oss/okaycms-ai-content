{if !empty($product->id)}
    <div class="mt-1">
        <button type="button" class="btn btn_small btn_blue fn_threeangle_ai_product_generate" data-ai-mode="description">
            AI descriptions
        </button>
        <button type="button" class="btn btn_small btn_blue fn_threeangle_ai_product_generate" data-ai-mode="seo">
            AI SEO draft
        </button>
        <span class="text_grey fn_threeangle_ai_product_status"></span>
    </div>

    <script>
        (function () {
            if (window.threeAngleAiProductInlineInit) {
                return;
            }
            window.threeAngleAiProductInlineInit = true;

            function setProductField(name, value) {
                if (typeof value === 'undefined' || value === null || value === '') {
                    return;
                }

                var field = $('[name="' + name + '"]');
                if (!field.length) {
                    return;
                }

                field.val(value).trigger('input').trigger('change');

                if (window.tinyMCE && field.attr('id')) {
                    var editor = tinyMCE.get(field.attr('id'));
                    if (editor) {
                        editor.setContent(value);
                        editor.fire('change');
                    }
                }
            }

            function fillProductFields(content, mode) {
                if (mode === 'seo') {
                    setProductField('meta_title', content.meta_title);
                    setProductField('meta_description', content.meta_description);
                    setProductField('meta_keywords', content.meta_keywords);
                    return;
                }

                setProductField('annotation', content.annotation);
                setProductField('description', content.description);
            }

            $(document).on('click', '.fn_threeangle_ai_product_generate', function () {
                var button = $(this);
                var form = button.closest('form');
                var status = form.find('.fn_threeangle_ai_product_status');
                var mode = button.data('ai-mode');
                var productId = form.find('[name="id"]').val();
                var sessionId = form.find('[name="session_id"]').val();
                var oldText = button.text();

                if (!productId) {
                    status.text('Save the product first.');
                    return;
                }

                button.prop('disabled', true).text('Generating...');
                status.text('');

                $.ajax({
                    url: 'index.php?controller=ThreeAngle.AiContent.AiContentProductAjaxAdmin',
                    method: 'POST',
                    dataType: 'json',
                    data: {
                        session_id: sessionId,
                        product_id: productId,
                        product_mode: mode
                    }
                }).done(function (response) {
                    if (!response || !response.success) {
                        status.text(response && response.error ? response.error : 'Generation failed.');
                        return;
                    }

                    fillProductFields(response.content || {}, mode);
                    status.text('Draft inserted. Review and save the product.');
                }).fail(function () {
                    status.text('Generation request failed.');
                }).always(function () {
                    button.prop('disabled', false).text(oldText);
                });
            });
        }());
    </script>
{/if}
