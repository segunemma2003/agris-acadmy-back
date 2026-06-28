<?php

return [
    'api_key' => env('ANTHROPIC_API_KEY'),
    'api_url' => 'https://api.anthropic.com/v1/messages',
    'api_version' => '2023-06-01',
    'model' => env('CLAUDE_MODEL', 'claude-haiku-4-5-20251001'),
    'max_tokens' => (int) env('CLAUDE_MAX_TOKENS', 1024),
    'max_history_messages' => (int) env('CLAUDE_MAX_HISTORY', 20),
];
