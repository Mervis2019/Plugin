<?php
if (!defined('ABSPATH')) {
    exit;
}

 
add_action('admin_enqueue_scripts', 'mervis_admin_scripts');

function mervis_admin_scripts($hook) {
    // فقط در صفحات پلاگین بارگذاری شود
    if (strpos($hook, 'mervis-dynamic-forms') === false && strpos($hook, 'mervis-add-form') === false) {
        return;
    }
    wp_enqueue_script('jquery-ui-sortable');
    wp_enqueue_script('mervis-admin-scripts', MERVIS_FORM_URL . 'admin/admin-scripts.js', array('jquery', 'jquery-ui-sortable'), '1.0', true);
}

add_action('admin_menu', 'mervis_add_admin_menu');

function mervis_add_admin_menu() {
    add_menu_page(
        'فرم‌های سفارش داینامیک',
        'فرم‌های سفارش',
        'manage_options',
        'mervis-dynamic-forms',
        'mervis_forms_list_page',
        'dashicons-feedback',
        30
    );
    
    add_submenu_page(
        'mervis-dynamic-forms',
        'لیست فرم‌ها',
        'لیست فرم‌ها',
        'manage_options',
        'mervis-dynamic-forms',
        'mervis_forms_list_page'
    );
    
    add_submenu_page(
        'mervis-dynamic-forms',
        'افزودن فرم جدید',
        'افزودن فرم',
        'manage_options',
        'mervis-add-form',
        'mervis_add_form_page'
    );
    
    add_submenu_page(
        'mervis-dynamic-forms',
        'درخواست‌های سفارش',
        'درخواست‌ها',
        'manage_options',
        'edit.php?post_type=mervis_order_request',
        null
    );
}

function mervis_forms_list_page() {
    if (!current_user_can('manage_options')) {
        wp_die('شما دسترسی لازم را ندارید.');
    }
    
    if (isset($_GET['delete']) && isset($_GET['_wpnonce'])) {
        if (wp_verify_nonce($_GET['_wpnonce'], 'delete_form_' . $_GET['delete'])) {
            wp_delete_post(intval($_GET['delete']), true);
            echo '<div class="notice notice-success"><p>فرم با موفقیت حذف شد.</p></div>';
        }
    }
    
    if (isset($_GET['saved'])) {
        echo '<div class="notice notice-success"><p>فرم با موفقیت ذخیره شد.</p></div>';
    }
    
    $forms = get_posts(array(
        'post_type' => 'mervis_form',
        'posts_per_page' => -1,
        'post_status' => 'any'
    ));
    ?>
    <div class="wrap">
        <h1>فرم‌های سفارش داینامیک</h1>
        <a href="<?php echo admin_url('admin.php?page=mervis-add-form'); ?>" class="page-title-action">افزودن فرم جدید</a>
        
        <?php if (empty($forms)): ?>
            <div class="notice notice-warning">
                <p>هیچ فرمی ایجاد نشده است.</p>
            </div>
        <?php else: ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th width="50">شناسه</th>
                        <th>عنوان فرم</th>
                        <th>دسته‌بندی مرتبط</th>
                        <th>تعداد فیلدها</th>
                        <th>نوع دکمه</th>
                        <th width="150">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($forms as $form): 
                        $fields = get_post_meta($form->ID, '_mervis_form_fields', true);
                        $field_count = is_array($fields) ? count($fields) : 0;
                        $categories = get_post_meta($form->ID, '_mervis_associated_categories', true);
                        $cat_names = array();
                        if (!empty($categories) && is_array($categories)) {
                            foreach ($categories as $cat_id) {
                                $cat = get_term($cat_id, 'product_cat');
                                if ($cat && !is_wp_error($cat)) {
                                    $cat_names[] = $cat->name;
                                }
                            }
                        }
                        $button_type = get_post_meta($form->ID, '_mervis_button_type', true);
                        $button_text = $button_type == 'inquiry' ? 'استعلام قیمت' : ($button_type == 'order' ? 'ثبت سفارش' : 'افزودن به سبد');
                        $delete_nonce = wp_create_nonce('delete_form_' . $form->ID);
                    ?>
                        <tr>
                            <td><?php echo $form->ID; ?></td>
                            <td><?php echo esc_html($form->post_title); ?></td>
                            <td><?php echo !empty($cat_names) ? implode(', ', $cat_names) : '<span style="color:red;">هیچ</span>'; ?></td>
                            <td><?php echo $field_count; ?></td>
                            <td><?php echo esc_html($button_text); ?></td>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=mervis-add-form&edit=' . $form->ID); ?>" class="button button-small">ویرایش</a>
                                <a href="<?php echo admin_url('admin.php?page=mervis-dynamic-forms&delete=' . $form->ID . '&_wpnonce=' . $delete_nonce); ?>" class="button button-small" onclick="return confirm('آیا مطمئن هستید؟')">حذف</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <?php
}

function mervis_add_form_page() {
    if (!current_user_can('manage_options')) {
        wp_die('شما دسترسی لازم را ندارید.');
    }
    
    $builder_file = MERVIS_FORM_PATH . 'admin/form-builder.php';
    if (file_exists($builder_file)) {
        require_once $builder_file;
        if (function_exists('mervis_form_builder_page')) {
            mervis_form_builder_page();
        }
    }
}
?>