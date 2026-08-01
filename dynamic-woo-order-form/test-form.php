<?php
// test-form.php
require_once('../../../wp-load.php');

if (!current_user_can('manage_options')) {
    die('دسترسی غیرمجاز');
}

echo '<h2>🔍 دیباگ فرم‌ها</h2>';

// محصول تست
$product_id = 10840; 
$product = wc_get_product($product_id);

if ($product) {
    echo '<h3>محصول: ' . $product->get_name() . ' (ID: ' . $product_id . ')</h3>';
    
    $cats = wp_get_post_terms($product_id, 'product_cat', array('fields' => 'all'));
    echo '<p>دسته‌بندی‌های محصول:</p><ul>';
    foreach ($cats as $cat) {
        echo '<li>' . $cat->name . ' (ID: ' . $cat->term_id . ')</li>';
    }
    echo '</ul>';
    
    echo '<h3>فرم‌های موجود:</h3>';
    $forms = get_posts(array('post_type' => 'mervis_form', 'posts_per_page' => -1));
    if (empty($forms)) {
        echo '<p style="color:red;">هیچ فرمی ساخته نشده است!</p>';
    } else {
        echo '<ul>';
        foreach ($forms as $form) {
            $cats = get_post_meta($form->ID, '_mervis_associated_categories', true);
            $cat_ids = is_array($cats) ? implode(', ', $cats) : 'هیچ';
            echo '<li>فرم: ' . $form->post_title . ' - دسته‌بندی‌های متصل: ' . $cat_ids . '</li>';
        }
        echo '</ul>';
    }
} else {
    echo '<p style="color:red;">محصول با ID ' . $product_id . ' پیدا نشد!</p>';
}
?>