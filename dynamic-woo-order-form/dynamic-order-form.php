<?php
/**
 * Plugin Name: Dynamic Product Order Form for WooCommerce
 * Plugin URI:  https://farsimeeting.com
 * Description: Dynamic order form with price calculation for printed products – customizable for each product individually.
 * Version:     3.6.8
 * Author:      Mervis
 * Author URI:  https://farsimeeting.com
 * Text Domain: wc-dynamic-form
 */

defined( 'ABSPATH' ) || exit;

define( 'MERVIS_FORM_PATH', plugin_dir_path( __FILE__ ) );
define( 'MERVIS_FORM_URL', plugin_dir_url( __FILE__ ) );

// شامل فایل‌های ادمین
if ( is_admin() ) {
    require_once MERVIS_FORM_PATH . 'admin/admin-menu.php';
    require_once MERVIS_FORM_PATH . 'admin/form-builder.php';
    require_once MERVIS_FORM_PATH . 'admin/save-fields.php';
}

// شامل فایل‌های فرانت
require_once MERVIS_FORM_PATH . 'frontend/display-form.php';
require_once MERVIS_FORM_PATH . 'frontend/add-to-cart-handler.php';

// ============================================
// تابع کمکی برای یافتن فرم محصول
// ============================================
function mervis_get_form_id_for_product($product_id) {
    $product_cats = wp_get_post_terms($product_id, 'product_cat', array('fields' => 'ids'));
    
    if (empty($product_cats)) {
        return false;
    }
    
    $all_forms = get_posts(array(
        'post_type' => 'mervis_form',
        'posts_per_page' => -1,
        'post_status' => 'publish'
    ));
    
    foreach ($all_forms as $form) {
        $form_cats = get_post_meta($form->ID, '_mervis_associated_categories', true);
        if (!is_array($form_cats)) {
            $form_cats = maybe_unserialize($form_cats);
        }
        if (is_array($form_cats) && !empty(array_intersect($product_cats, $form_cats))) {
            return $form->ID;
        }
    }
    
    return false;
}

function mervis_product_has_form($product_id) {
    return mervis_get_form_id_for_product($product_id) !== false;
}

// ============================================
// تب‌های محصول
// ============================================
add_filter( 'woocommerce_product_tabs', 'mervis_add_custom_order_tab', 1, 1 );

function mervis_add_custom_order_tab( $tabs ) {
    global $product;
    
    if (!$product) {
        return $tabs;
    }
    
    $product_id = $product->get_id();
    $form_id = mervis_get_form_id_for_product($product_id);
    
    if ($form_id) {
        $GLOBALS['mervis_current_form_id'] = $form_id;
        
        $tabs['order_form'] = array(
            'title'    => __( 'محاسبه قیمت سفارش', 'wc-dynamic-form' ),
            'priority' => 1,
            'callback' => 'mervis_display_order_form_tab_content_wrapped'
        );
        
        // $tabs['download_template'] = array(
            // 'title'    => __( 'دانلود قالب', 'wc-dynamic-form' ),
            // 'priority' => 2,
            // 'callback' => 'mervis_download_template_tab_content_wrapped'
        // );
    }
    
    return $tabs;
}

function mervis_download_template_tab_content_wrapped() {
    global $product;
    $template_file = get_post_meta( $product->get_id(), '_mervis_template_file', true );
    if ( $template_file ) {
        echo '<div class="mervis-download-template">';
        echo '<p>قالب اختصاصی این محصول را دانلود کنید:</p>';
        echo '<a href="' . esc_url( wp_get_attachment_url( $template_file ) ) . '" class="button">دانلود فایل قالب</a>';
        echo '</div>';
    } else {
        echo '<p>فایل قالبی برای این محصول تعریف نشده است.</p>';
    }
}

function mervis_display_order_form_tab_content_wrapped() {
    // اطمینان از بارگذاری فایل price-calculator.js
    // wp_enqueue_script('mervis-price-calculator', MERVIS_FORM_URL . 'frontend/price-calculator.js', array('jquery'), '1.0', true);
    echo '<div class="mervis-tab-wrapper">';
    mervis_display_order_form_tab_content();
    echo '</div>';
}

 

// ============================================
// نمایش مستقیم در پایین صفحه
// ============================================
add_action('woocommerce_after_single_product_summary', 'mervis_display_form_directly', 5);

function mervis_display_form_directly() {
    global $product;
    
    if (!$product) {
        return;
    }
    
    $product_id = $product->get_id();
    $form_id = mervis_get_form_id_for_product($product_id);
    
    if ($form_id) {
        ?>
        <div class="mervis-direct-form-wrapper" style="clear:both; margin:60px 0 30px; padding:30px 0; border-top:2px solid #eee;">
            <div class="container">
                <h3 style="margin-bottom:30px; font-size:24px; font-weight:600; text-align:center;">📊 محاسبه قیمت سفارش</h3>
                <?php mervis_display_order_form_tab_content(); ?>
            </div>
        </div>
        <?php
    }
}

// ============================================
// شورت کد
// ============================================
add_shortcode('mervis_order_form', 'mervis_shortcode_display_form');

function mervis_shortcode_display_form($atts) {
    $atts = shortcode_atts(array(
        'product_id' => 0,
        'category_id' => 0
    ), $atts);
    
    global $product;
    $old_product = $product;
    
    if (!empty($atts['product_id'])) {
        $product = wc_get_product(intval($atts['product_id']));
    } elseif (!empty($atts['category_id'])) {
        // پیدا کردن یک محصول از دسته‌بندی
        $products = wc_get_products(array(
            'category' => array(intval($atts['category_id'])),
            'limit' => 1
        ));
        if (!empty($products)) {
            $product = $products[0];
        }
    }
    
    if (!$product) {
        $product = $old_product;
        return '<p>محصولی یافت نشد. لطفا product_id یا category_id را مشخص کنید.</p>';
    }
    
    ob_start();
    mervis_display_order_form_tab_content();
    $output = ob_get_clean();
    
    $product = $old_product;
    return $output;
}

// ============================================
// دیباگ برای ادمین
// ============================================
add_action('woocommerce_before_single_product', 'mervis_debug_info_for_admin');
function mervis_debug_info_for_admin() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    global $product;
    if (!$product) {
        return;
    }
    
    $product_id = $product->get_id();
    $form_id = mervis_get_form_id_for_product($product_id);
    
    echo '<div style="background:#f0f8ff; border:2px solid #2196f3; padding:15px; margin:15px 0; border-radius:8px; direction:rtl; font-family:inherit;">';
    echo '<strong>🔍 اطلاعات دیباگ (فقط ادمین):</strong><br>';
    echo 'محصول ID: ' . $product_id . '<br>';
    echo 'نام محصول: ' . $product->get_name() . '<br>';
    
    $cats = wp_get_post_terms($product_id, 'product_cat', array('fields' => 'all'));
    echo 'دسته‌بندی‌ها: ';
    if (!empty($cats)) {
        $cat_names = array();
        foreach ($cats as $cat) {
            $cat_names[] = $cat->name . ' (ID:' . $cat->term_id . ')';
        }
        echo implode(', ', $cat_names);
    } else {
        echo '<span style="color:red;">هیچ دسته‌بندی انتخاب نشده!</span>';
    }
    echo '<br>';
    
    if ($form_id) {
        echo '<span style="color:green; font-weight:bold;">✅ فرم با ID ' . $form_id . ' پیدا شد</span>';
    } else {
        echo '<span style="color:red; font-weight:bold;">❌ فرمی برای این محصول وجود ندارد</span>';
    }
    echo '</div>';
}

add_action('wp_ajax_mervis_get_cities', 'mervis_get_cities_callback');
add_action('wp_ajax_nopriv_mervis_get_cities', 'mervis_get_cities_callback');

function mervis_get_cities_callback() {
    $province_id = intval($_POST['province_id']);
    
    // اینجا می‌توانید از دیتابیس یا یک آرایه بزرگتر استفاده کنید. این یک نمونه ساختاری است:
    $cities_data = array(
        1 => array(array('id' => 11, 'name' => 'تهران'), array('id' => 12, 'name' => 'کرج')),
        2 => array(array('id' => 21, 'name' => 'اصفهان'), array('id' => 22, 'name' => 'کاشان'))
    );
    
    $cities = isset($cities_data[$province_id]) ? $cities_data[$province_id] : array();
    wp_send_json_success($cities);
}

?>