<?php
add_action('admin_init', 'mervis_save_form_data');

function mervis_save_form_data() {
    if (!isset($_POST['save_form']) || !wp_verify_nonce($_POST['mervis_form_nonce'], 'mervis_save_form')) {
        return;
    }
    
    $form_id = intval($_POST['form_id']);
    $form_title = sanitize_text_field($_POST['form_title']);
    $price_rule = sanitize_textarea_field($_POST['price_rule']);
    $button_type = sanitize_text_field($_POST['button_type'] ?? 'add_to_cart');
    $inquiry_email = sanitize_email($_POST['inquiry_email'] ?? '');
    $sidebar_content = wp_kses_post($_POST['mervis_sidebar_content'] ?? '');
    
    $associated_categories = isset($_POST['associated_categories']) ? array_map('intval', $_POST['associated_categories']) : array();
    
    if (empty($associated_categories)) {
        wp_die('لطفاً حداقل یک دسته‌بندی را انتخاب کنید.');
    }
    
    $fields = array();
    if (isset($_POST['fields']) && is_array($_POST['fields'])) {
        foreach ($_POST['fields'] as $field) {
            $field_type = sanitize_text_field($field['type']);
            $field_label = sanitize_text_field($field['label']);
            $field_key = !empty($field['key']) ? sanitize_key($field['key']) : sanitize_title($field_label);
            
            $options = array();
            $option_prices = array();
            $option_keys = array();
            $subfields = array();
            
if (in_array($field_type, ['radio', 'select', 'select_custom', 'checkbox'])) {
    if (isset($field['options']) && is_array($field['options'])) {
        $options = array_map('sanitize_text_field', $field['options']);
        $options = array_filter($options);
    }
    if (isset($field['option_prices']) && is_array($field['option_prices'])) {
        $option_prices = array_map('floatval', $field['option_prices']);
    }
    foreach ($options as $opt) {
        $option_keys[] = sanitize_title($opt);
    }
}

// در بخش پردازش فیلدها، بعد از subfields
if ($field_type == 'dynamic_group' && isset($field['dynamic_configs']) && is_array($field['dynamic_configs'])) {
    $dynamic_configs = array();
    foreach ($field['dynamic_configs'] as $config) {
        $subfields = array();
        if (isset($config['subfields']) && is_array($config['subfields'])) {
            foreach ($config['subfields'] as $sub) {
                $subfields[] = array(
                    'label' => sanitize_text_field($sub['label'] ?? ''),
                    'key' => !empty($sub['key']) ? sanitize_key($sub['key']) : sanitize_title($sub['label']),
                    'type' => sanitize_text_field($sub['type'] ?? 'number')
                );
            }
        }
        $dynamic_configs[] = array(
            'option_label' => sanitize_text_field($config['option_label'] ?? ''),
            'subfields' => $subfields
        );
    }
    
    $fields[] = array(
        'type' => $field_type,
        'label' => $field_label,
        'key' => $field_key,
        'dynamic_configs' => $dynamic_configs,
        'options' => array_column($dynamic_configs, 'option_label'),
        'option_keys' => array_map('sanitize_title', array_column($dynamic_configs, 'option_label')),
        'conditional_field' => sanitize_text_field($field['conditional_field'] ?? ''),
        'conditional_value' => sanitize_text_field($field['conditional_value'] ?? ''),
    );
    continue;
}
            
// در تابع mervis_save_form_data - بخش پردازش فیلدها
if ($field_type == 'group' && isset($field['subfields']) && is_array($field['subfields'])) {
    $subfields = array();
    foreach ($field['subfields'] as $sub) {
        // پردازش گزینه‌ها برای زیرفیلد نوع select
        $sub_options = array();
        $sub_option_prices = array();
        if (isset($sub['options']) && is_array($sub['options'])) {
            $sub_options = array_map('sanitize_text_field', $sub['options']);
            $sub_options = array_filter($sub_options);
        }
        if (isset($sub['option_prices']) && is_array($sub['option_prices'])) {
            $sub_option_prices = array_map('floatval', $sub['option_prices']);
        }
        
        $subfields[] = array(
            'label' => sanitize_text_field($sub['label'] ?? ''),
            'key' => !empty($sub['key']) ? sanitize_key($sub['key']) : sanitize_title($sub['label']),
            'type' => sanitize_text_field($sub['type'] ?? 'number'),
            'min_value' => isset($sub['min_value']) && $sub['min_value'] !== '' ? floatval($sub['min_value']) : null,
            'max_value' => isset($sub['max_value']) && $sub['max_value'] !== '' ? floatval($sub['max_value']) : null,
            'options' => array_values($sub_options),
            'option_prices' => array_values($sub_option_prices)
        );
    }
    
    // فیلد گروهی با زیرفیلدها ذخیره می‌شود
    $fields[] = array(
        'type' => $field_type,
        'label' => $field_label,
        'key' => $field_key,
        'subfields' => $subfields,
        // بقیه فیلدها برای گروهی استفاده نمی‌شوند
        'options' => array(),
        'option_prices' => array(),
        'price_factor' => 1,
        'min_value' => null,
        'max_value' => null
    );
    continue; // مهم: ادامه حلقه برای جلوگیری از ذخیره دوباره
}
            
            $fields[] = array(
                'type' => $field_type,
                'label' => $field_label,
                'key' => $field_key,
                'options' => array_values($options),
                'option_keys' => array_values($option_keys),
                'option_prices' => array_values($option_prices),
                'price_factor' => isset($field['price_factor']) ? floatval($field['price_factor']) : 1,
                'min_value' => isset($field['min_value']) && $field['min_value'] !== '' ? floatval($field['min_value']) : null,
                'max_value' => isset($field['max_value']) && $field['max_value'] !== '' ? floatval($field['max_value']) : null,
                'subfields' => $subfields
            );
        }
    }
    
    $post_data = array(
        'post_title' => $form_title,
        'post_type' => 'mervis_form',
        'post_status' => 'publish'
    );
    
    $saved_id = $form_id > 0 ? wp_update_post(array_merge($post_data, array('ID' => $form_id))) : wp_insert_post($post_data);
    
    if ($saved_id && !is_wp_error($saved_id)) {
        update_post_meta($saved_id, '_mervis_form_fields', $fields);
        update_post_meta($saved_id, '_mervis_price_rule', $price_rule);
        update_post_meta($saved_id, '_mervis_associated_categories', $associated_categories);
        update_post_meta($saved_id, '_mervis_button_type', $button_type);
        update_post_meta($saved_id, '_mervis_inquiry_email', $inquiry_email);
        update_post_meta($saved_id, '_mervis_sidebar_content', $sidebar_content);
        
        wp_redirect(admin_url('admin.php?page=mervis-dynamic-forms&saved=1'));
        exit;
    }
}

add_action('init', 'mervis_register_post_types');
function mervis_register_post_types() {
    register_post_type('mervis_form', array(
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => false,
        'supports' => array('title'),
        'capability_type' => 'post',
        'capabilities' => array(
            'edit_post' => 'manage_options',
            'delete_post' => 'manage_options',
            'edit_posts' => 'manage_options',
            'delete_posts' => 'manage_options',
            'create_posts' => 'manage_options',
        ),
        'map_meta_cap' => false,
        'labels' => array('name' => 'فرم‌های داینامیک', 'singular_name' => 'فرم')
    ));
    
    register_post_type('mervis_order_request', array(
        'labels' => array(
            'name' => 'درخواست‌های سفارش',
            'singular_name' => 'درخواست سفارش',
            'menu_name' => 'درخواست‌های سفارش',
            'all_items' => 'همه درخواست‌ها'
        ),
        'public' => true,
        'publicly_queryable' => false,
        'show_ui' => true,
        'show_in_menu' => 'mervis-dynamic-forms',
        'supports' => array('title', 'editor'),
        'capability_type' => 'post',
        'capabilities' => array(
            'edit_post' => 'manage_options',
            'delete_post' => 'manage_options',
            'edit_posts' => 'manage_options',
            'delete_posts' => 'manage_options',
            'create_posts' => 'manage_options',
            'read_post' => 'manage_options',
        ),
        'map_meta_cap' => false,
    ));
}

// متاباکس برای نمایش جزئیات درخواست
add_action('add_meta_boxes', 'mervis_add_request_meta_boxes');
function mervis_add_request_meta_boxes() {
    add_meta_box('mervis_request_details', 'جزئیات درخواست سفارش', 'mervis_request_details_callback', 'mervis_order_request', 'normal', 'high');
}

function mervis_request_details_callback($post) {
    $form_data = get_post_meta($post->ID, '_request_form_data', true);
    $product_id = get_post_meta($post->ID, '_request_product_id', true);
    $calculated_price = get_post_meta($post->ID, '_request_calculated_price', true);
    $user_ip = get_post_meta($post->ID, '_request_user_ip', true);
    ?>
    <style>.mervis-details-table{width:100%;border-collapse:collapse}.mervis-details-table th,.mervis-details-table td{padding:12px;text-align:right;border-bottom:1px solid #eee;vertical-align:top}.mervis-details-table th{width:200px;background:#f9f9f9;font-weight:bold}.mervis-price-box{background:#e8f5e9;padding:15px;margin-top:20px;border-radius:8px;font-size:18px;font-weight:bold;text-align:center}</style>
    <table class="mervis-details-table">
        <tr><th>شناسه درخواست</th><td><?php echo $post->ID; ?></td></tr>
        <tr><th>تاریخ ثبت</th><td><?php echo get_post_meta($post->ID, '_request_date', true) ?: $post->post_date; ?></td></tr>
        <tr><th>آی پی کاربر</th><td><?php echo esc_html($user_ip ?: 'نامشخص'); ?></td></tr>
        <tr><th>محصول</th><td><?php $p = wc_get_product($product_id); echo $p ? '<a href="' . get_permalink($product_id) . '" target="_blank">' . $p->get_name() . '</a>' : 'محصول حذف شده'; ?></td></tr>
        <?php if (!empty($form_data) && is_array($form_data)): ?>
            <tr><th colspan="2" style="background:#f0f0f0;">📋 اطلاعات فرم سفارش</th></tr>
            <?php 
            $label_map = array('name'=>'نام و نام خانوادگی', 'phone'=>'شماره تماس', 'mobile'=>'شماره موبایل', 'customer_name'=>'نام مشتری', 'customer_phone'=>'شماره تماس', 'email'=>'ایمیل', 'address'=>'آدرس', 'city'=>'شهر', 'state'=>'استان', 'zip'=>'کد پستی');
            foreach ($form_data as $key => $value):
                if (in_array($key, ['product_id', 'form_id', 'calculated_price', 'add-to-cart', 'mervis_form_nonce', 'action', 'nonce'])) continue;
                $display_key = $label_map[$key] ?? ucwords(str_replace(['_', '-'], ' ', $key));
                if (is_array($value)) $value = json_encode($value);
            ?>
                <tr><th><?php echo esc_html($display_key); ?></th><td><?php echo nl2br(esc_html($value)); ?></td></tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </table>
    <div class="mervis-price-box">💰 قیمت نهایی: <?php echo number_format($calculated_price); ?> تومان</div>
    <?php
}

// ============================================
// ستون‌های سفارشی - اصلاح شده
// ============================================
add_filter('manage_mervis_order_request_posts_columns', 'mervis_custom_request_columns');
function mervis_custom_request_columns($columns) {
    $new = array();
    foreach ($columns as $k => $v) {
        $new[$k] = $v;
        if ($k == 'title') {
            $new['request_type'] = 'نوع';
            $new['customer_name'] = 'نام مشتری';
            $new['customer_phone'] = 'شماره تماس';
            $new['request_price'] = 'قیمت';
        }
    }
    return $new;
}

add_action('manage_mervis_order_request_posts_custom_column', 'mervis_custom_request_column_content', 10, 2);
function mervis_custom_request_column_content($column, $post_id) {
    $data = get_post_meta($post_id, '_request_form_data', true);
    
    switch ($column) {
        case 'request_type':
            $type = get_post_meta($post_id, '_request_type', true);
            echo $type == 'inquiry' ? '📞 استعلام' : ($type == 'order' ? '✅ سفارش' : '-');
            break;
            
        case 'customer_name':
            $name = '';
            if (is_array($data)) {
                // لیست کلیدهای احتمالی برای نام
                $name_keys = ['name', 'customer_name', 'full_name', 'نام', 'نام_کاربر', 'نام_و_نام_خانوادگی'];
                foreach ($name_keys as $key) {
                    if (!empty($data[$key])) {
                        $name = $data[$key];
                        break;
                    }
                }
                // اگر پیدا نشد، اولین مقدار متنی غیر از کلیدهای سیستمی را بگیر
                if (empty($name)) {
                    $exclude_keys = ['product_id', 'form_id', 'calculated_price', 'wp_http_referer', 'action', 'nonce', 'add-to-cart'];
                    foreach ($data as $key => $value) {
                        if (in_array($key, $exclude_keys)) continue;
                        if (is_string($value) && strlen($value) > 2 && strlen($value) < 50 && !is_numeric($value)) {
                            $name = $value;
                            break;
                        }
                    }
                }
            }
            echo esc_html($name ?: '---');
            break;
            
        case 'customer_phone':
            $phone = '';
            if (is_array($data)) {
                // لیست کلیدهای احتمالی برای شماره تماس
                $phone_keys = ['phone', 'mobile', 'customer_phone', 'شماره', 'تلفن', 'شماره_تماس', 'موبایل'];
                foreach ($phone_keys as $key) {
                    if (!empty($data[$key])) {
                        $phone = $data[$key];
                        break;
                    }
                }
                // اگر پیدا نشد، اولین مقدار عددی با طول مناسب را بگیر
                if (empty($phone)) {
                    $exclude_keys = ['product_id', 'form_id', 'calculated_price','fullname', 'full_name', 'full-namme', 'wp_http_referer', 'action', 'nonce'];
                    foreach ($data as $key => $value) {
                        if (in_array($key, $exclude_keys)) continue;
                        if (is_numeric($value) && strlen($value) >= 10) {
                            $phone = $value;
                            break;
                        }
                    }
                }
            }
            echo esc_html($phone ?: '---');
            break;
            
        case 'request_price':
            echo number_format(get_post_meta($post_id, '_request_calculated_price', true)) . ' تومان';
            break;
    }
}
?>