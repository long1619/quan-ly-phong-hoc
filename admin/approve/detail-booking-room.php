Modal Chi tiết
<div class="modal-overlay" id="detailModal">
    <div class="modal-content">
        <div class="modal-header">
            <div class="modal-title" id="modalTitle">Chi tiết đơn đặt phòng</div>
            <button class="modal-close" onclick="closeModal()">×</button>
        </div>

        <div class="modal-body">
            <!-- Thông tin cơ bản -->
            <div class="detail-section">
                <div class="section-title">
                    <div class="section-icon primary">📋</div>
                    Thông tin đơn đặt phòng
                </div>
                <div class="detail-grid">
                    <div class="detail-item">
                        <div class="detail-label">Mã đơn</div>
                        <div class="detail-value" id="detailCode">BP002</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Trạng thái</div>
                        <div class="detail-value" id="detailStatus">
                            <span class="status-badge pending">⏳ Chờ duyệt</span>
                        </div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Ngày tạo</div>
                        <div class="detail-value">10/01/2025 09:30</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Số người tham gia</div>
                        <div class="detail-value" id="detailParticipants">30 người</div>
                    </div>
                </div>
            </div>

            <!-- Thông tin người đặt -->
            <div class="detail-section">
                <div class="section-title">
                    <div class="section-icon success">👤</div>
                    Thông tin người đặt
                </div>
                <div class="user-card">
                    <div class="user-avatar-large" id="detailUserAvatar">NA</div>
                    <div class="user-details">
                        <div class="user-name" id="detailUserName">Nguyễn Văn An</div>
                        <div class="user-role" id="detailUserRole">👨‍🎓 Sinh viên</div>
                        <div class="user-contact">
                            <div>📧 Email: <strong id="detailUserEmail">an.nv@student.edu.vn</strong></div>
                            <div>📞 SĐT: <strong id="detailUserPhone">0912 345 678</strong></div>
                            <div>🏢 Khoa: <strong id="detailUserDepartment">Công nghệ Thông tin</strong></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Thông tin phòng -->
            <div class="detail-section">
                <div class="section-title">
                    <div class="section-icon warning">🏫</div>
                    Thông tin phòng
                </div>
                <div class="room-preview">
                    <div class="room-code-large" id="detailRoomCode">A201</div>
                    <div class="room-name-large" id="detailRoomName">Lab CNTT 01</div>
                </div>
                <div class="detail-grid" style="margin-top: 16px;">
                    <div class="detail-item">
                        <div class="detail-label">Tòa nhà</div>
                        <div class="detail-value">Tòa A</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Tầng</div>
                        <div class="detail-value">Tầng 2</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Sức chứa</div>
                        <div class="detail-value">40 người</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Loại phòng</div>
                        <div class="detail-value">Lab thực hành</div>
                    </div>
                </div>
                <div class="facilities-list">
                    <span class="facility-tag">💻 40 máy tính</span>
                    <span class="facility-tag">📽️ Máy chiếu</span>
                    <span class="facility-tag">❄️ Điều hòa</span>
                    <span class="facility-tag">📱 Bảng thông minh</span>
                </div>
            </div>

            <!-- Thông tin thời gian & mục đích -->
            <div class="detail-section">
                <div class="section-title">
                    <div class="section-icon primary">🕐</div>
                    Thông tin đặt phòng
                </div>
                <div class="detail-grid">
                    <div class="detail-item">
                        <div class="detail-label">Ngày sử dụng</div>
                        <div class="detail-value" id="detailDate">Thứ 6, 12/01/2025</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Thời gian</div>
                        <div class="detail-value" id="detailTime">14:00 - 16:00 (2 giờ)</div>
                    </div>
                    <div class="detail-item" style="grid-column: 1 / -1;">
                        <div class="detail-label">Mục đích sử dụng</div>
                        <div class="detail-value" id="detailPurpose">
                            Thực hành lập trình Java cơ bản cho sinh viên năm 2.
                            Các nội dung bao gồm: OOP, Collections Framework, và Exception Handling.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lịch sử phê duyệt -->
            <div class="detail-section">
                <div class="section-title">
                    <div class="section-icon danger">📜</div>
                    Lịch sử phê duyệt
                </div>
                <div class="timeline" id="detailTimeline">
                    <div class="timeline-item created">
                        <div class="timeline-dot"></div>
                        <div class="timeline-action">🆕 Tạo đơn đặt phòng</div>
                        <div class="timeline-user">Bởi: Nguyễn Văn An</div>
                        <div class="timeline-time">10/01/2025 lúc 09:30</div>
                    </div>
                </div>
            </div>

            <!-- Lý do từ chối (nếu có) -->
            <div class="detail-section" id="rejectionSection" style="display: none;">
                <div class="rejection-reason">
                    <div class="rejection-label">LÝ DO TỪ CHỐI:</div>
                    <div class="rejection-text" id="detailRejectionReason"></div>
                </div>
            </div>
        </div>

        <div class="modal-footer" id="modalFooter">
            <button class="btn-large btn-close-modal" onclick="closeModal()">
                Đóng
            </button>
            <button class="btn-large btn-approve" id="btnModalApprove" onclick="approveFromModal()">
                ✓ Phê duyệt
            </button>
            <button class="btn-large btn-reject" id="btnModalReject" onclick="rejectFromModal()">
                ✕ Từ chối
            </button>
        </div>
    </div>
</div>