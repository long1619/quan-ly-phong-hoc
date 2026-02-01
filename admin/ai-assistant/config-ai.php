<?php
/**
 * AI Assistant Configuration
 * Gemini API Integration
 */

// Gemini API Configuration
define('GEMINI_API_KEY', 'AIzaSyDiukWejXHudeM1ck4Adb_HqW8aHl6Mtzs');
define(
    'GEMINI_API_ENDPOINT',
    'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent'
);

// API Settings
define('AI_MAX_TOKENS', 5000);
define('AI_TEMPERATURE', 0.7);
define('AI_TIMEOUT', 60);

// System Configuration
define('AI_LANGUAGE', 'vi');        // Vietnamese
define('AI_MAX_HISTORY', 10);        // Số message chat history giữ lại
define('AI_RESPONSE_FORMAT', 'markdown');

return [
    'api_key' => GEMINI_API_KEY,
    'endpoint' => GEMINI_API_ENDPOINT,
    'max_tokens' => AI_MAX_TOKENS,
    'temperature' => AI_TEMPERATURE,
    'timeout' => AI_TIMEOUT,
    'language' => AI_LANGUAGE,
    'max_history' => AI_MAX_HISTORY,
    'response_format' => AI_RESPONSE_FORMAT,
];
