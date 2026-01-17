<?php
session_start();
require_once __DIR__ . '/../../config/connect.php';
require_once __DIR__ . '/config-ai.php';
require_once __DIR__ . '/database-context.php';

header('Content-Type: application/json');

// Kiểm tra đăng nhập
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Nhận input từ frontend
$input = json_decode(file_get_contents('php://input'), true);
$query = $input['query'] ?? '';
$projectMode = $input['projectMode'] ?? true;

if (empty($query)) {
    echo json_encode(['success' => false, 'error' => 'Empty query']);
    exit;
}

// RATE LIMITING - Kiểm tra số lần gọi API
if (!isset($_SESSION['api_calls'])) {
    $_SESSION['api_calls'] = [];
}

// Xóa các lần gọi cũ hơn 1 phút
$now = time();
$_SESSION['api_calls'] = array_filter($_SESSION['api_calls'], function($timestamp) use ($now) {
    return ($now - $timestamp) < 60; // Giữ lại các lần gọi trong 60 giây
});

// Kiểm tra giới hạn
if (count($_SESSION['api_calls']) >= 10) { // Giới hạn 10 lần/phút
    echo json_encode([
        'success' => false,
        'error' => 'Rate limit exceeded',
        'response' => '⏰ Bạn đã gọi quá nhiều lần. Vui lòng đợi 1 phút rồi thử lại.'
    ]);
    exit;
}

try {
    if ($projectMode) {
        // CHẾ ĐỘ QUẢN LÝ PHÒNG
        $dbContext = getDatabaseContext($conn, $query);

        $systemPrompt = "Bạn là trợ lý AI quản lý phòng học thông minh. Dựa vào dữ liệu sau từ database, hãy trả lời câu hỏi của người dùng một cách chính xác và hữu ích.\n\n";
        $systemPrompt .= "DỮ LIỆU HỆ THỐNG:\n";
        $systemPrompt .= json_encode($dbContext, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $systemPrompt .= "\n\nHãy trả lời bằng tiếng Việt, ngắn gọn, rõ ràng. Nếu có số liệu thì đưa ra cụ thể.";

        $userPrompt = $query;

    } else {
        // CHẾ ĐỘ AI ĐA NĂNG
        $systemPrompt = "Bạn là một trợ lý AI thông minh, có kiến thức rộng về mọi lĩnh vực. Hãy trả lời câu hỏi của người dùng một cách chính xác, hữu ích và thân thiện. Trả lời bằng tiếng Việt.";
        $userPrompt = $query;
    }

    // Gọi Gemini API với retry logic
    $response = callGeminiAPIWithRetry($systemPrompt, $userPrompt);

    // Lưu lần gọi API thành công
    $_SESSION['api_calls'][] = time();

    // Trả về kết quả
    echo json_encode([
        'success' => true,
        'response' => $response,
        'mode' => $projectMode ? 'project' : 'general',
        'rooms' => $projectMode ? extractRoomsFromContext($dbContext) : null
    ]);

} catch (Exception $e) {
    $errorMessage = $e->getMessage();

    // Xử lý lỗi 429 Rate Limit
    if (strpos($errorMessage, '429') !== false) {
        echo json_encode([
            'success' => false,
            'error' => 'Rate Limit',
            'response' => '⏰ API đang quá tải. Vui lòng đợi 30 giây và thử lại nhé!'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => $errorMessage,
            'response' => 'Đã xảy ra lỗi khi xử lý yêu cầu.'
        ]);
    }
}

/**
 * Gọi Gemini API với Retry Logic (thử lại nếu lỗi)
 */
function callGeminiAPIWithRetry($systemPrompt, $userPrompt, $maxRetries = 2) {
    $retryCount = 0;
    $lastError = null;

    while ($retryCount < $maxRetries) {
        try {
            return callGeminiAPI($systemPrompt, $userPrompt);
        } catch (Exception $e) {
            $lastError = $e;
            $retryCount++;

            // Nếu lỗi 429, đợi lâu hơn
            if (strpos($e->getMessage(), '429') !== false) {
                sleep(3); // Đợi 3 giây
            } else {
                sleep(1); // Đợi 1 giây
            }
        }
    }

    // Nếu thử hết lần vẫn lỗi
    throw $lastError;
}

/**
 * Gọi Gemini API
 */
function callGeminiAPI($systemPrompt, $userPrompt) {
    $apiKey = GEMINI_API_KEY;
    $endpoint = GEMINI_API_ENDPOINT . '?key=' . $apiKey;

    $data = [
        'contents' => [
            [
                'parts' => [
                    ['text' => $systemPrompt . "\n\nCâu hỏi: " . $userPrompt]
                ]
            ]
        ],
        'generationConfig' => [
            'temperature' => AI_TEMPERATURE,
            'maxOutputTokens' => AI_MAX_TOKENS,
        ]
    ];

    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, AI_TIMEOUT);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        curl_close($ch);
        throw new Exception('Curl error: ' . curl_error($ch));
    }

    curl_close($ch);

    if ($httpCode !== 200) {
        throw new Exception('API returned status code: ' . $httpCode);
    }

    $result = json_decode($response, true);

    if (!isset($result['candidates'][0]['content']['parts'][0]['text'])) {
        throw new Exception('Invalid API response format');
    }

    return $result['candidates'][0]['content']['parts'][0]['text'];
}

/**
 * Trích xuất thông tin phòng từ context để hiển thị
 */
function extractRoomsFromContext($context) {
    if (isset($context['rooms']) && is_array($context['rooms'])) {
        return array_slice($context['rooms'], 0, 5);
    }
    return null;
}