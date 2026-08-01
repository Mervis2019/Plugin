<?php
add_action('wp_ajax_mervis_submit_inquiry', 'mervis_handle_inquiry');
add_action('wp_ajax_nopriv_mervis_submit_inquiry', 'mervis_handle_inquiry');

function mervis_handle_inquiry() {
    if (!check_ajax_referer('mervis_add_to_cart', 'nonce', false)) {
        wp_send_json_error('خطای امنیتی');
        return;
    }
    
    $product_id = intval($_POST['product_id']);
    $form_id = intval($_POST['form_id']);
    $calculated_price = floatval($_POST['calculated_price'] ?? 0);
    
    $form_data = array();
    foreach ($_POST['form_data'] ?? array() as $key => $value) {
        if (in_array($key, ['action', 'nonce', 'product_id', 'form_id', 'calculated_price', 'mervis_form_nonce'])) continue;
        $form_data[$key] = is_array($value) ? implode(', ', array_map('sanitize_text_field', $value)) : sanitize_text_field($value);
    }
    
    $product = wc_get_product($product_id);
    $product_title = $product ? $product->get_name() : 'نامشخص';
    
    $request_id = wp_insert_post(array(
        'post_title' => 'استعلام - ' . $product_title . ' - ' . current_time('Y-m-d H:i:s'),
        'post_type' => 'mervis_order_request',
        'post_status' => 'pending',
        'post_content' => json_encode($form_data, JSON_UNESCAPED_UNICODE),
        'meta_input' => array(
            '_request_type' => 'inquiry',
            '_request_product_id' => $product_id,
            '_request_product_title' => $product_title,
            '_request_form_id' => $form_id,
            '_request_form_data' => $form_data,
            '_request_calculated_price' => $calculated_price,
            '_request_user_ip' => $_SERVER['REMOTE_ADDR'],
            '_request_date' => current_time('Y-m-d H:i:s')
        )
    ));
    
    if (!$request_id || is_wp_error($request_id)) {
        wp_send_json_error('خطا در ثبت درخواست');
        return;
    }
    
    // ارسال ایمیل
    $email = get_post_meta($form_id, '_mervis_inquiry_email', true) ?: get_option('admin_email');
    wp_mail($email, 'درخواست استعلام جدید - ' . $product_title, "درخواست جدید ثبت شد.\nشناسه: $request_id\nمحصول: $product_title\nقیمت: " . number_format($calculated_price) . " تومان\n\n" . admin_url('edit.php?post_type=mervis_order_request'));
    
    wp_send_json_success('درخواست شما با موفقیت ارسال شد. کد پیگیری: ' . $request_id);
}

add_action('wp_ajax_mervis_submit_order', 'mervis_handle_order');
add_action('wp_ajax_nopriv_mervis_submit_order', 'mervis_handle_order');

function mervis_handle_order() {
    if (!check_ajax_referer('mervis_add_to_cart', 'nonce', false)) {
        wp_send_json_error('خطای امنیتی');
        return;
    }
    
    $product_id = intval($_POST['product_id']);
    $form_id = intval($_POST['form_id']);
    $calculated_price = floatval($_POST['calculated_price'] ?? 0);
    
    $form_data = array();
    foreach ($_POST['form_data'] ?? array() as $key => $value) {
        if (in_array($key, ['action', 'nonce', 'product_id', 'form_id', 'calculated_price', 'mervis_form_nonce'])) continue;
        $form_data[$key] = is_array($value) ? implode(', ', array_map('sanitize_text_field', $value)) : sanitize_text_field($value);
    }
    
    $product = wc_get_product($product_id);
    $product_title = $product ? $product->get_name() : 'نامشخص';
    
    $request_id = wp_insert_post(array(
        'post_title' => 'سفارش - ' . $product_title . ' - ' . current_time('Y-m-d H:i:s'),
        'post_type' => 'mervis_order_request',
        'post_status' => 'pending',
        'post_content' => json_encode($form_data, JSON_UNESCAPED_UNICODE),
        'meta_input' => array(
            '_request_type' => 'order',
            '_request_product_id' => $product_id,
            '_request_product_title' => $product_title,
            '_request_form_id' => $form_id,
            '_request_form_data' => $form_data,
            '_request_calculated_price' => $calculated_price,
            '_request_user_ip' => $_SERVER['REMOTE_ADDR'],
            '_request_date' => current_time('Y-m-d H:i:s')
        )
    ));
    
    if (!$request_id || is_wp_error($request_id)) {
        wp_send_json_error('خطا در ثبت سفارش');
        return;
    }
    
    wp_mail(get_option('admin_email'), 'سفارش جدید - ' . $product_title, "سفارش جدید ثبت شد.\nشناسه: $request_id\nمحصول: $product_title\nقیمت: " . number_format($calculated_price) . " تومان\n\n" . admin_url('post.php?post=' . $request_id . '&action=edit'));
    
    wp_send_json_success('سفارش شما با موفقیت ثبت شد. کد پیگیری: ' . $request_id);
}
?>