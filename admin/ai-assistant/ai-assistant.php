<?php
session_start();
require_once __DIR__ . '/../../config/connect.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../auth/login.php');
    exit;
}
include __DIR__ . '/../common/header.php';
require_once __DIR__ . '/../../helpers/helpers.php';
$userRole = $_SESSION['user_role'] ?? '';

// Kiểm tra quyền sử dụng AI
if (!checkPermission($conn, $userRole, 'use_ai')) {
    echo "<script>alert('Bạn không có quyền sử dụng Trợ lý AI!'); window.location.href='../dashboard/index.php';</script>";
    exit;
}
?>

<style>
/* AI Assistant Styles */
.ai-container {
    max-width: 1400px;
    margin: 0 auto;
}

/* Header Section */
.ai-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 25px;
    border-radius: 20px;
    color: white;
    margin-bottom: 10px;
    margin-top: -10px;
    box-shadow: 0 10px 40px rgba(102, 126, 234, 0.3);
    position: relative;
    overflow: hidden;
}

.ai-header::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -10%;
    width: 400px;
    height: 400px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
    animation: float 6s ease-in-out infinite;
}

@keyframes float {
    0%, 100% { transform: translateY(0px) rotate(0deg); }
    50% { transform: translateY(-20px) rotate(5deg); }
}

.ai-header-content {
    position: relative;
    z-index: 2;
}

.ai-title {
    font-size: 36px;
    font-weight: 700;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 16px;
}

.ai-icon {
    width: 60px;
    height: 60px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

.ai-subtitle {
    font-size: 16px;
    opacity: 0.95;
    margin-left: 76px;
}

/* Main Chat Container */
.chat-container {
    display: grid;
    grid-template-columns: 1fr 350px;
    gap: 24px;
    margin-bottom: 30px;
}

/* Chat Box */
.chat-box {
    background: white;
    border-radius: 20px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    display: flex;
    flex-direction: column;
    height: 750px;
    overflow: hidden;
}

.chat-header {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    padding: 24px;
    border-bottom: 2px solid #e2e8f0;
}

.chat-status {
    display: flex;
    align-items: center;
    gap: 12px;
}

.status-dot {
    width: 12px;
    height: 12px;
    background: #10b981;
    border-radius: 50%;
    animation: blink 2s ease-in-out infinite;
}

@keyframes blink {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

.status-text {
    font-weight: 600;
    color: #1a202c;
    font-size: 16px;
}

/* Messages Area */
.chat-messages {
    flex: 1;
    padding: 24px;
    overflow-y: auto;
    background: #f8f9fa;
    background-image:
        radial-gradient(circle at 20% 50%, rgba(102, 126, 234, 0.03) 0%, transparent 50%),
        radial-gradient(circle at 80% 80%, rgba(118, 75, 162, 0.03) 0%, transparent 50%);
}

.message {
    margin-bottom: 20px;
    display: flex;
    gap: 12px;
    animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.message-avatar {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
}

.message.user .message-avatar {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.message.ai .message-avatar {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
}

.message-content {
    flex: 1;
}

.message-bubble {
    padding: 16px 20px;
    border-radius: 16px;
    max-width: 80%;
    word-wrap: break-word;
}

.message.user .message-bubble {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-bottom-right-radius: 4px;
}

.message.ai .message-bubble {
    background: white;
    color: #1a202c;
    border: 2px solid #e2e8f0;
    border-bottom-left-radius: 4px;
}

.message-time {
    font-size: 12px;
    color: #999;
    margin-top: 6px;
    margin-left: 8px;
}

/* Room Results */
.room-result {
    background: white;
    border-radius: 12px;
    padding: 16px;
    margin-top: 12px;
    border: 2px solid #e2e8f0;
    transition: all 0.3s;
}

.room-result:hover {
    border-color: #667eea;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
}

.room-result-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}

.room-code {
    font-size: 18px;
    font-weight: 700;
    color: #667eea;
}

.room-status {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.room-status.available {
    background: #d1fae5;
    color: #059669;
}

.room-status.pending {
    background: #fef3c7;
    color: #d97706;
}

.room-status.approved {
    background: #dbeafe;
    color: #1e40af;
}

.room-info {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 8px;
    font-size: 13px;
    color: #666;
}

/* Chat Input */
.chat-input-container {
    padding: 20px 24px;
    background: white;
    border-top: 2px solid #e2e8f0;
}

.chat-input-wrapper {
    display: flex;
    gap: 12px;
    align-items: center;
}

.chat-input {
    flex: 1;
    padding: 14px 20px;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    font-size: 15px;
    transition: all 0.3s;
    outline: none;
}

.chat-input:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
}

.send-btn {
    padding: 14px 28px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    gap: 8px;
}

.send-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
}

.send-btn:active {
    transform: translateY(0);
}

/* Suggestions Sidebar */
.suggestions-sidebar {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.suggestion-card {
    background: white;
    border-radius: 16px;
    padding: 24px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.suggestion-title {
    font-size: 16px;
    font-weight: 700;
    color: #1a202c;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.suggestion-icon {
    width: 28px;
    height: 28px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 14px;
}

.quick-question {
    padding: 12px 16px;
    background: #f8f9fa;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    margin-bottom: 10px;
    cursor: pointer;
    transition: all 0.3s;
    font-size: 14px;
    color: #4a5568;
    display: flex;
    align-items: center;
    gap: 10px;
}

.quick-question:hover {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-color: #667eea;
    transform: translateX(5px);
}

.quick-question i {
    font-size: 16px;
}

/* Stats Cards */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
    margin-top: 16px;
}

.stat-mini {
    padding: 16px;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 12px;
    border-left: 4px solid;
    transition: all 0.3s;
}

.stat-mini:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.stat-mini.available {
    border-left-color: #10b981;
}

.stat-mini.pending {
    border-left-color: #f59e0b;
}

.stat-label {
    font-size: 11px;
    color: #6b7280;
    text-transform: uppercase;
    font-weight: 600;
    margin-bottom: 6px;
}

.stat-value {
    font-size: 24px;
    font-weight: 700;
    color: #1a202c;
}

/* Loading Animation */
.typing-indicator {
    display: flex;
    gap: 6px;
    padding: 16px 20px;
}

.typing-dot {
    width: 8px;
    height: 8px;
    background: #cbd5e0;
    border-radius: 50%;
    animation: typing 1.4s ease-in-out infinite;
}

.typing-dot:nth-child(2) {
    animation-delay: 0.2s;
}

.typing-dot:nth-child(3) {
    animation-delay: 0.4s;
}

@keyframes typing {
    0%, 60%, 100% { transform: translateY(0); }
    30% { transform: translateY(-10px); }
}

/* Responsive */
@media (max-width: 1024px) {
    .chat-container {
        grid-template-columns: 1fr;
    }

    .suggestions-sidebar {
        flex-direction: row;
        overflow-x: auto;
    }

    .suggestion-card {
        min-width: 300px;
    }
}

/* Custom Scrollbar */
.chat-messages::-webkit-scrollbar {
    width: 8px;
}

.chat-messages::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

.chat-messages::-webkit-scrollbar-thumb {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 10px;
}

.chat-messages::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
}

/* AI Mode Switch */
.chat-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.ai-mode-switch {
    display: flex;
    align-items: center;
    gap: 10px;
}

.switch-label {
    position: relative;
    display: inline-block;
    width: 50px;
    height: 24px;
    margin-bottom: 0;
}

.switch-label input {
    opacity: 0;
    width: 0;
    height: 0;
}

.slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: .4s;
}

.slider:before {
    position: absolute;
    content: "";
    height: 16px;
    width: 16px;
    left: 4px;
    bottom: 4px;
    background-color: white;
    transition: .4s;
}

input:checked + .slider {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

input:focus + .slider {
    box-shadow: 0 0 1px #2196F3;
}

input:checked + .slider:before {
    transform: translateX(26px);
}

.slider.round {
    border-radius: 34px;
}

.slider.round:before {
    border-radius: 50%;
}

.mode-text {
    font-size: 14px;
    color: #4a5568;
}

/* Enhanced Markdown Styling */
.markdown-content h1, .markdown-content h2, .markdown-content h3 {
    margin-top: 1.5rem;
    margin-bottom: 1rem;
    font-weight: 700;
    color: #1a202c;
    border-bottom: 2px solid #edf2f7;
    padding-bottom: 0.5rem;
}

.markdown-content h3 {
    font-size: 1.1rem;
    display: inline-block;
    background: #f1f5f9;
    padding: 4px 12px;
    border-radius: 8px;
    border-bottom: none;
}

.markdown-content p {
    margin-bottom: 1rem;
    line-height: 1.7;
}

.markdown-content ul, .markdown-content ol {
    margin-bottom: 1rem;
    padding-left: 1.5rem;
}

.markdown-content li {
    margin-bottom: 0.5rem;
}

.markdown-content strong {
    color: #4a5568;
}

.message.ai .markdown-content blockquote {
    border-left: 4px solid #667eea;
    padding-left: 1rem;
    color: #4a5568;
    margin: 1rem 0;
    background: #f8fafc;
    padding: 10px 15px;
    border-radius: 0 8px 8px 0;
}

.markdown-content table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 1rem;
    background: white;
    border-radius: 8px;
    overflow: hidden;
}

.markdown-content th, .markdown-content td {
    padding: 12px;
    border: 1px solid #e2e8f0;
    text-align: left;
}

.markdown-content th {
    background: #f8fafc;
    font-weight: 600;
}

.markdown-content code {
    background: #f1f5f9;
    padding: 2px 6px;
    border-radius: 4px;
    font-family: 'Monaco', 'Consolas', monospace;
    font-size: 0.9em;
}

/* Section Card Effect for AI Responses */
.message.ai .message-bubble hr {
    border: none;
    border-top: 2px dashed #e2e8f0;
    margin: 20px 0;
}

/* Custom "Card" look for sections starting with bold titles or headers */
.message.ai .markdown-content h3 {
    color: #667eea;
    border-left: 3px solid #667eea;
    border-radius: 0 4px 4px 0;
    margin-left: -20px;
    padding-left: 17px;
}
</style>

</head>

<body>
    <!-- Layout wrapper -->
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
            <!-- Menu -->
            <?php include __DIR__ . '/../common/menu-sidebar.php'; ?>

            <!-- Layout container -->
            <div class="layout-page">
                <?php include __DIR__ . '/../common/navbar.php'; ?>

                <!-- Content wrapper -->
                <div class="content-wrapper">
                    <!-- Content -->
                    <div class="container-xxl flex-grow-1 container-p-y">
                        <div class="ai-container">
                            <!-- Header -->
                            <div class="ai-header">
                                <div class="ai-header-content">
                                    <div class="ai-title">
                                        <div class="ai-icon">
                                            <i class='bx bx-bot'></i>
                                        </div>
                                        Trợ lý AI - Hỗ trợ Quản lý Phòng
                                    </div>
                                    <p class="ai-subtitle">
                                        Hỏi tôi về trạng thái phòng, lịch đặt phòng, hoặc tìm phòng phù hợp với yêu cầu của bạn
                                    </p>
                                </div>
                            </div>

                            <!-- Main Chat Interface -->
                            <div class="chat-container">
                                <!-- Chat Box -->
                                <div class="chat-box">
                                    <!-- Chat Header -->
                                    <div class="chat-header">
                                        <div class="chat-status">
                                            <div class="status-dot"></div>
                                            <span class="status-text">AI Assistant đang hoạt động</span>
                                        </div>
                                        <div class="ai-mode-switch">
                                            <label class="switch-label">
                                                <input type="checkbox" id="aiModeSwitch" checked onchange="toggleAiMode()">
                                                <span class="slider round"></span>
                                            </label>
                                            <span class="mode-text" id="modeText">Chế độ: <b>Quản lý phòng</b> 🏫</span>
                                        </div>
                                    </div>

                                    <!-- Messages -->
                                    <div class="chat-messages" id="chatMessages">
                                        <!-- Welcome Message -->
                                        <div class="message ai">
                                            <div class="message-avatar">🤖</div>
                                            <div class="message-content">
                                                <div class="message-bubble">
                                                    <strong>Xin chào!</strong> 👋<br><br>
                                                    Tôi là trợ lý AI của hệ thống quản lý phòng. Tôi có thể giúp bạn:
                                                    <ul style="margin: 12px 0 0 0; padding-left: 20px;">
                                                        <li>Kiểm tra trạng thái phòng</li>
                                                        <li>Tìm phòng trống theo thời gian</li>
                                                        <li>Xem lịch đặt phòng</li>
                                                        <li>Thống kê tình hình sử dụng phòng</li>
                                                    </ul>
                                                    <br>
                                                    Hãy cho tôi biết bạn cần gì nhé! 😊
                                                </div>
                                                <div class="message-time">Vừa xong</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Input -->
                                    <div class="chat-input-container">
                                        <div class="chat-input-wrapper">
                                            <input
                                                type="text"
                                                class="chat-input"
                                                id="chatInput"
                                                placeholder="Gõ câu hỏi của bạn..."
                                                onkeypress="handleKeyPress(event)"
                                            >
                                            <button class="send-btn" onclick="sendMessage()">
                                                <i class='bx bx-send'></i>
                                                Gửi
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Suggestions Sidebar -->
                                <div class="suggestions-sidebar">
                                    <!-- Quick Questions -->
                                    <div class="suggestion-card">
                                        <div class="suggestion-title">
                                            <div class="suggestion-icon">
                                                <i class='bx bx-chat'></i>
                                            </div>
                                            Câu hỏi gợi ý
                                        </div>
                                        <div class="quick-question" onclick="askQuestion('Có bao nhiêu phòng đang trống trong ngày hôm nay (Từ 7h sáng đến 9h tối)?')">
                                            <i class='bx bx-check-circle'></i>
                                            Có bao nhiêu phòng đang trống trong ngày hôm nay? (Từ 7h sáng đến 9h tối)
                                        </div>
                                        <div class="quick-question" onclick="askQuestion('Phòng nào có sức chứa trên 50 người?')">
                                            <i class='bx bx-group'></i>
                                            Phòng nào có sức chứa trên 50 người?
                                        </div>
                                        <?php if ($userRole === 'admin'): ?>
                                        <div class="quick-question" onclick="askQuestion('Hiện có bao nhiêu đơn chờ duyệt?')">
                                            <i class='bx bx-time-five'></i>
                                            Hiện có bao nhiêu đơn chờ duyệt?
                                        </div>
                                        <?php endif; ?>
                                        <div class="quick-question" onclick="askQuestion('Có tất cả bao nhiêu phòng học?')">
                                            <i class='bx bx-buildings'></i>
                                            Có tất cả bao nhiêu phòng học?
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- / Content -->
                    <!-- Footer -->
                    <?php include __DIR__ . '/../common/footer.php'; ?>
                    <!-- / Footer -->
                </div>
                <!-- Content wrapper -->
            </div>
            <!-- / Layout container -->
        </div>

        <!-- Overlay -->
    </div>
    <!-- / Layout wrapper -->

    <!-- Core JS -->
    <script src="../../assets/vendor/libs/jquery/jquery.js"></script>
    <script src="../../assets/vendor/libs/popper/popper.js"></script>
    <script src="../../assets/vendor/js/bootstrap.js"></script>
    <script src="../../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
    <script src="../../assets/vendor/js/menu.js"></script>

    <!-- Marked.js for Markdown Rendering -->
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>

    <!-- Main JS -->
    <script src="../../assets/js/main.js"></script>

    <script>
        // Load initial stats
        document.addEventListener('DOMContentLoaded', function() {
            // Stats UI was removed
        });

        // Handle Enter key press
        function handleKeyPress(event) {
            if (event.key === 'Enter') {
                sendMessage();
            }
        }

        // Ask pre-defined question
        function askQuestion(question) {
            document.getElementById('chatInput').value = question;
            sendMessage();
        }

        // Toggle AI Mode
        function toggleAiMode() {
            const isChecked = document.getElementById('aiModeSwitch').checked;
            const modeText = document.getElementById('modeText');

            if (isChecked) {
                modeText.innerHTML = 'Chế độ: <b>Quản lý phòng</b> 🏫';
                addMessage('ai', 'Đã chuyển sang chế độ **Quản lý phòng**. Tôi sẽ tập trung hỗ trợ bạn về đặt phòng và tra cứu dữ liệu.');
            } else {
                modeText.innerHTML = 'Chế độ: <b>AI Đa năng</b> 🌍';
                addMessage('ai', 'Đã chuyển sang chế độ **AI Đa năng**. Bạn có thể hỏi tôi về mọi chủ đề trong cuộc sống!');
            }
        }

        // Send message
        function sendMessage() {
            const input = document.getElementById('chatInput');
            const message = input.value.trim();
            const isProjectMode = document.getElementById('aiModeSwitch').checked;

            if (!message) return;

            // Add user message to chat
            addMessage('user', message);

            // Clear input
            input.value = '';

            // Show typing indicator
            showTypingIndicator();

            // Send to backend
            fetch('process-query.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    query: message,
                    projectMode: isProjectMode
                })
            })
            .then(response => response.json())
            .then(data => {
                hideTypingIndicator();

                if (data.success) {
                    addMessage('ai', data.response, data.rooms);
                } else {
                    const errorMsg = data.error ? `\n(Lỗi: ${data.error})` : '';
                    addMessage('ai', 'Xin lỗi, đã có lỗi xảy ra. ' + (data.response || 'Vui lòng thử lại.') + errorMsg);
                }
            })
            .catch(error => {
                hideTypingIndicator();
                addMessage('ai', 'Xin lỗi, không thể kết nối đến server. Vui lòng kiểm tra lại.');
                console.error('Error:', error);
            });
        }

        // Add message to chat
        function addMessage(type, text, rooms = null) {
            const messagesContainer = document.getElementById('chatMessages');
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${type}`;

            const time = new Date().toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });

            let roomsHTML = '';
            if (rooms && rooms.length > 0) {
                roomsHTML = `
                    <div class="rooms-container mt-3">
                        ${rooms.map(room => `
                            <div class="room-result">
                                <div class="room-result-header">
                                    <span class="room-code">${room.room_code}</span>
                                    <span class="room-status ${room.status}">${room.status_text}</span>
                                </div>
                                <div class="room-info">
                                    <div><i class='bx bx-buildings'></i> ${room.room_name}</div>
                                    <div><i class='bx bx-group'></i> Sức chứa: ${room.capacity}</div>
                                    <div><i class='bx bx-category'></i> ${room.type_name}</div>
                                    <div><i class='bx bx-map'></i> ${room.location || 'N/A'}</div>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                `;
            }

            // Parse markdown for AI messages
            let formattedContent = text;
            if (type === 'ai') {
                // Configure marked
                marked.setOptions({
                    breaks: true,
                    gfm: true
                });
                formattedContent = `<div class="markdown-content">${marked.parse(text)}</div>`;
            } else {
                formattedContent = text.replace(/\n/g, '<br>');
            }

            messageDiv.innerHTML = `
                <div class="message-avatar">${type === 'user' ? '👤' : '🤖'}</div>
                <div class="message-content">
                    <div class="message-bubble">
                        ${formattedContent}
                        ${roomsHTML}
                    </div>
                    <div class="message-time">${time}</div>
                </div>
            `;

            messagesContainer.appendChild(messageDiv);
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }

        // Show typing indicator
        function showTypingIndicator() {
            const messagesContainer = document.getElementById('chatMessages');
            const typingDiv = document.createElement('div');
            typingDiv.className = 'message ai';
            typingDiv.id = 'typingIndicator';
            typingDiv.innerHTML = `
                <div class="message-avatar">🤖</div>
                <div class="message-content">
                    <div class="message-bubble">
                        <div class="typing-indicator">
                            <div class="typing-dot"></div>
                            <div class="typing-dot"></div>
                            <div class="typing-dot"></div>
                        </div>
                    </div>
                </div>
            `;
            messagesContainer.appendChild(typingDiv);
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }

        // Hide typing indicator
        function hideTypingIndicator() {
            const typingIndicator = document.getElementById('typingIndicator');
            if (typingIndicator) {
                typingIndicator.remove();
            }
        }
    </script>
</body>
</html>
