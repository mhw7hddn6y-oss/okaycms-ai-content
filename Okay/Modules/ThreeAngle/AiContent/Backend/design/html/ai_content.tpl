{$meta_title = 'AI Content Studio' scope=global}

{if $message_success}
    <div class="message message_success">{$message_success|escape}</div>
{/if}
{if $message_error}
    <div class="message message_error">{$message_error|escape}</div>
{/if}

<div class="row">
    <div class="col-lg-12 col-md-12">
        <div class="wrap_heading">
            <div class="box_heading heading_page">AI Content Studio</div>
        </div>
    </div>
</div>

<div class="boxed">
    <form method="post">
        <input type="hidden" name="session_id" value="{$smarty.session.id}">
        <input type="hidden" name="action" value="save_settings">
        <div class="heading_box">Settings</div>
        <div class="row">
            <div class="col-lg-6 col-md-12">
                <div class="mb-1">
                    <div class="heading_label">OpenAI API key</div>
                    <input class="form-control" type="password" name="settings[api_key]" value="{$ai_settings.api_key|escape}">
                </div>
                <div class="mb-1">
                    <div class="heading_label">Model</div>
                    <input class="form-control" type="text" name="settings[model]" value="{$ai_settings.model|escape}">
                </div>
                <div class="mb-1">
                    <div class="heading_label">Language</div>
                    <select class="selectpicker form-control" name="settings[language]">
                        <option value="uk" {if $ai_settings.language == 'uk'}selected{/if}>Ukrainian</option>
                        <option value="ru" {if $ai_settings.language == 'ru'}selected{/if}>Russian</option>
                        <option value="en" {if $ai_settings.language == 'en'}selected{/if}>English</option>
                    </select>
                </div>
            </div>
            <div class="col-lg-6 col-md-12">
                <div class="mb-1">
                    <div class="heading_label">Tone</div>
                    <select class="selectpicker form-control" name="settings[tone]">
                        <option value="expert" {if $ai_settings.tone == 'expert'}selected{/if}>Expert</option>
                        <option value="selling" {if $ai_settings.tone == 'selling'}selected{/if}>Selling</option>
                        <option value="neutral" {if $ai_settings.tone == 'neutral'}selected{/if}>Neutral</option>
                    </select>
                </div>
                <div class="mb-1">
                    <div class="heading_label">Max tokens</div>
                    <input class="form-control" type="number" min="300" max="4000" name="settings[max_tokens]" value="{$ai_settings.max_tokens|escape}">
                </div>
                <div class="mb-1">
                    <div class="heading_label">Temperature</div>
                    <input class="form-control" type="number" min="0" max="2" step="0.1" name="settings[temperature]" value="{$ai_settings.temperature|escape}">
                </div>
            </div>
        </div>
        <button type="submit" class="btn btn_small btn_blue">Save settings</button>
    </form>
</div>

<div class="row">
    <div class="col-lg-6 col-md-12">
        <div class="boxed">
            <form method="post">
                <input type="hidden" name="session_id" value="{$smarty.session.id}">
                <input type="hidden" name="action" value="generate_product">
                <div class="heading_box">Generate product content</div>
                <div class="mb-1">
                    <div class="heading_label">Product ID</div>
                    <input class="form-control" type="number" name="product_id" min="1" required>
                </div>
                <div class="mb-1">
                    <div class="heading_label">Mode</div>
                    <select class="selectpicker form-control" name="product_mode">
                        <option value="description">Descriptions</option>
                        <option value="seo">SEO fields</option>
                    </select>
                </div>
                <button type="submit" class="btn btn_small btn_blue">Generate</button>
            </form>
        </div>
    </div>
    <div class="col-lg-6 col-md-12">
        <div class="boxed">
            <form method="post">
                <input type="hidden" name="session_id" value="{$smarty.session.id}">
                <input type="hidden" name="action" value="generate_blog">
                <div class="heading_box">Generate blog article</div>
                <div class="mb-1">
                    <div class="heading_label">Topic</div>
                    <textarea class="form-control" name="blog_topic" rows="5" required></textarea>
                </div>
                <button type="submit" class="btn btn_small btn_blue">Generate</button>
            </form>
        </div>
    </div>
</div>

{if $generated}
    <div class="boxed">
        <div class="heading_box">Generated draft</div>
        <form method="post">
            <input type="hidden" name="session_id" value="{$smarty.session.id}">
            {if $generated.type == 'product'}
                <input type="hidden" name="action" value="apply_product">
                <input type="hidden" name="product_id" value="{$generated.entity_id|escape}">
                {foreach $generated.content as $field => $value}
                    <div class="mb-1">
                        <div class="heading_label">{$field|escape}</div>
                        <textarea class="form-control" name="content[{$field|escape}]" rows="{if $field == 'description'}8{else}3{/if}">{$value|escape}</textarea>
                    </div>
                {/foreach}
                <button type="submit" class="btn btn_small btn_blue">Apply to product</button>
            {else}
                <input type="hidden" name="action" value="create_blog">
                {foreach $generated.content as $field => $value}
                    <div class="mb-1">
                        <div class="heading_label">{$field|escape}</div>
                        <textarea class="form-control" name="content[{$field|escape}]" rows="{if $field == 'description'}10{else}3{/if}">{$value|escape}</textarea>
                    </div>
                {/foreach}
                <button type="submit" class="btn btn_small btn_blue">Create hidden blog draft</button>
            {/if}
        </form>
    </div>
{/if}

<div class="boxed">
    <div class="heading_box">Recent generations</div>
    {if $ai_history}
        <div class="okay_list">
            <div class="okay_list_head">
                <div class="okay_list_heading">Date</div>
                <div class="okay_list_heading">Entity</div>
                <div class="okay_list_heading">Action</div>
                <div class="okay_list_heading">Model</div>
                <div class="okay_list_heading">Status</div>
            </div>
            <div class="okay_list_body">
                {foreach $ai_history as $item}
                    <div class="okay_list_body_item">
                        <div class="okay_list_row">
                            <div class="okay_list_boding">{$item->created_at|escape}</div>
                            <div class="okay_list_boding">{$item->entity_type|escape} {if $item->entity_id}#{$item->entity_id|escape}{/if}</div>
                            <div class="okay_list_boding">{$item->action|escape}</div>
                            <div class="okay_list_boding">{$item->model|escape}</div>
                            <div class="okay_list_boding">{$item->status|escape}</div>
                        </div>
                    </div>
                {/foreach}
            </div>
        </div>
    {else}
        <div class="text_grey">No generations yet.</div>
    {/if}
</div>
