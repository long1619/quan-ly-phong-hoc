<?php
/**
 * Hàm tính toán phân trang đơn giản
 */
if (!function_exists('paginate')) {
    function paginate($total_items, $current_page, $limit = 10) {
        $total_pages = ceil($total_items / $limit);
        if ($current_page < 1) $current_page = 1;
        if ($current_page > $total_pages && $total_pages > 0) $current_page = $total_pages;

        $offset = ($current_page - 1) * $limit;

        return [
            'total_pages' => (int)$total_pages,
            'current_page' => (int)$current_page,
            'limit' => (int)$limit,
            'offset' => (int)$offset
        ];
    }
}

/**
 * Hàm render giao diện phân trang đơn giản (Bootstrap 5)
 */
if (!function_exists('renderPagination')) {
    function renderPagination($total_pages, $current_page, $baseUrl) {
        if ($total_pages <= 1) return "";

        // Tách URL và params hiện tại để giữ lại các filter khác (như status)
        $url_parts = explode('?', $baseUrl);
        $path = $url_parts[0];
        parse_str($url_parts[1] ?? '', $params);

        $html = '<nav><ul class="pagination justify-content-center">';

        for ($i = 1; $i <= $total_pages; $i++) {
            $params['page'] = $i;
            $link = $path . '?' . http_build_query($params);
            $active = ($i == $current_page) ? 'active' : '';

            $html .= "<li class='page-item $active'><a class='page-link' href='$link'>$i</a></li>";
        }

        $html .= '</ul></nav>';
        return $html;
    }
}
?>