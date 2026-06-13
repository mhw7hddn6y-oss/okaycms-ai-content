# AI Content Studio MVP

## Scope

The MVP is a backend-only OkayCMS module for generating ecommerce content with OpenAI models.

Included:

- module settings: API key, model, language, tone, max tokens, temperature;
- product content generation by product ID;
- product SEO generation by product ID;
- applying generated product fields after manager review;
- blog article draft generation by topic;
- creating generated blog articles as hidden drafts;
- generation history table.

Deferred:

- image generation and image editing;
- bulk generation queues;
- direct buttons inside product/blog edit screens;
- cost accounting and token usage reports;
- Marketplace package docs/screenshots.

## Module

Path: `Okay/Modules/ThreeAngle/AiContent`

Backend controller: `ThreeAngle.AiContent.AiContentAdmin`

History table: `ok_threeangle__ai_content__history`
