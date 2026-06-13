# AI Content Studio MVP

## Scope

The MVP is a backend-only OkayCMS module for generating ecommerce content with configurable AI text providers.

Included:

- module settings: text provider, API key, model, language, tone, max tokens, temperature;
- OpenAI-compatible text provider layer for OpenAI, OpenRouter, Groq, and Google Gemini compatibility endpoints;
- product content generation by product ID;
- product SEO generation by product ID;
- applying generated product fields after manager review;
- blog article draft generation by topic;
- creating generated blog articles as hidden drafts;
- generation history table.

Deferred:

- image provider layer for image generation and image editing;
- bulk generation queues;
- direct buttons inside product/blog edit screens;
- cost accounting and token usage reports;
- Marketplace package docs/screenshots.

## Module

Path: `Okay/Modules/ThreeAngle/AiContent`

Backend controller: `ThreeAngle.AiContent.AiContentAdmin`

History table: `ok_threeangle__ai_content__history`

## Provider presets

- OpenAI: `gpt-4o-mini`
- OpenRouter: `openrouter/free`
- Groq: `llama-3.1-8b-instant`
- Google Gemini: `gemini-2.5-flash-lite`
