<style>
    /* Title */
    .swal-title-lg {
        font-size: 25px;
    }

    /* Nội dung */
    .swal-html-md {
        font-size: 18px;
        margin-top:8px;
        color:#6b7280;
    }
</style>
<?php
// Thông báo thành công
function showSuccessAlert($message) {
    if (!$message) return;
    ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'success',
        title: '<?php echo addslashes($message); ?>',
        showConfirmButton: false,
        timer: 5000,
        timerProgressBar: true,
    });
});
</script>
<?php
}
?>
<!-- Thông báo xóa -->
<script>
function setupDeleteConfirmation(selector, hrefTemplate, dataIdAttr = 'id', title = 'Bạn có chắc chắn?', text =
    'Bạn sẽ không thể khôi phục lại mục này!') {
    $(document).on('click', selector, function(e) {
        e.preventDefault();
        var itemId = $(this).data(dataIdAttr);
        Swal.fire({
            title: title,
            text: text,
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Vâng, xóa nó!"
        }).then((result) => {
            if (result.isConfirmed) {
                var href = hrefTemplate.replace('{id}', itemId);
                window.location.href = href;
            }
        });
    });
}

// Hàm hiển thị thông báo thành công tái sử dụng
function showSuccessNotification(title, subText = '') {
    return Swal.fire({
        icon: 'success',
        title: `<span class="swal-title-lg">${title}</span>`,
        html: subText ?
            `<p class="swal-html-md">
                    ${subText}
            </p>` : '',
        confirmButtonText: 'OK',
        confirmButtonColor: '#10b981'
    });
}

function setupApproveConfirmation(selector, options = {}) {
    const defaultOptions = {
        title: 'Xác nhận phê duyệt?',
        text: 'Bạn có chắc chắn muốn phê duyệt đơn này không?',
        confirmButtonText: 'Đồng ý, phê duyệt',
        cancelButtonText: 'Hủy bỏ',
        successMessage: 'Phê duyệt thành công!',
        successSubText: 'Email thông báo đã được gửi đến người đặt phòng'
    };

    const config = {
        ...defaultOptions,
        ...options
    };

    $(document).on('click', selector, function(e) {
        e.preventDefault();
        const form = $(this).closest('form');
        const bookingCode = form.find('input[name="booking_code"]').val();

        Swal.fire({
            title: config.title,
            html: `Bạn có chắc chắn muốn phê duyệt đơn <strong>${bookingCode}</strong> không?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#10b981',
            cancelButtonColor: '#d33',
            confirmButtonText: config.confirmButtonText,
            cancelButtonText: config.cancelButtonText,
        }).then((result) => {
            if (result.isConfirmed) {
                showSuccessNotification(
                    `Phê duyệt đơn ${bookingCode} thành công!`,
                    config.successSubText
                ).then(() => {
                    form.submit();
                });
            }
        });
    });
}

function setupRejectConfirmation(bookingCode) {
    Swal.fire({
        title: 'Từ chối đơn đặt phòng?',
        input: 'textarea',
        inputLabel: 'Lý do từ chối',
        inputPlaceholder: 'Nhập lý do từ chối đơn này...',
        inputAttributes: {
            'aria-label': 'Lý do từ chối'
        },
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Xác nhận từ chối',
        cancelButtonText: 'Hủy bỏ',
        inputValidator: (value) => {
            if (!value || !value.trim()) {
                return 'Vui lòng nhập lý do từ chối!';
            }
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const rejectionReason = result.value;

            showSuccessNotification(`Từ chối đơn ${bookingCode} thành công!`,
                'Email thông báo đã được gửi đến người đặt phòng').then(() => {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'handle-approve-room.php';
                form.innerHTML = `
                    <input type="hidden" name="booking_code" value="${bookingCode}">
                    <input type="hidden" name="action" value="reject">
                    <input type="hidden" name="rejection_reason" value="${rejectionReason.replace(/"/g, '&quot;')}">
                `;
                document.body.appendChild(form);
                form.submit();
            });
        }
    });
}
</script>