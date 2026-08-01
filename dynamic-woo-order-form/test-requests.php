<?php
// test-requests.php - برای دیباگ
require_once('../../../wp-load.php');

if (!current_user_can('manage_options')) {
    die('دسترسی غیرمجاز');
}

echo '<h2>بررسی درخواست‌های ذخیره شده</h2>';

$requests = get_posts(array(
    'post_type' => 'mervis_order_request',
    'posts_per_page' => -1,
    'post_status' => 'any'
));

echo '<p>تعداد درخواست‌ها: ' . count($requests) . '</p>';

if (!empty($requests)) {
    echo '<table border="1" cellpadding="10">';
    echo '<tr><th>ID</th><th>عنوان</th><th>نوع</th><th>تاریخ</th><th>وضعیت</th></tr>';
    foreach ($requests as $req) {
        $type = get_post_meta($req->ID, '_request_type', true);
        echo '<tr>';
        echo '<td>' . $req->ID . '</td>';
        echo '<td>' . $req->post_title . '</td>';
        echo '<td>' . ($type ?: 'نامشخص') . '</td>';
        echo '<td>' . $req->post_date . '</td>';
        echo '<td>' . $req->post_status . '</td>';
        echo '</tr>';
    }
    echo '</table>';
} else {
    echo '<p style="color:red;">هیچ درخواستی در دیتابیس یافت نشد!</p>';
}

// بررسی کنید که post type ثبت شده است
$post_types = get_post_types(array(), 'objects');
echo '<h3>پست تایپ‌های ثبت شده:</h3>';
echo '<ul>';
foreach ($post_types as $pt) {
    if (strpos($pt->name, 'mervis') !== false) {
        echo '<li>' . $pt->name . ' - ' . ($pt->public ? 'عمومی' : 'خصوصی') . '</li>';
    }
}
echo '</ul>';
?>