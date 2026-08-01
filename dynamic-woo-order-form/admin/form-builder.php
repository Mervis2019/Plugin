<?php
function mervis_form_builder_page() {
    $edit_id = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
    $form_title = '';
    $form_fields = array();
    $price_rule = '';
    $associated_categories = array();
    $sidebar_content = '';
    $button_type = 'add_to_cart';
    $inquiry_email = '';
    
    if ($edit_id) {
        $form_title = get_the_title($edit_id);
        $form_fields = get_post_meta($edit_id, '_mervis_form_fields', true);
        $price_rule = get_post_meta($edit_id, '_mervis_price_rule', true);
        $associated_categories = get_post_meta($edit_id, '_mervis_associated_categories', true);
        $sidebar_content = get_post_meta($edit_id, '_mervis_sidebar_content', true);
        $button_type = get_post_meta($edit_id, '_mervis_button_type', true);
        $inquiry_email = get_post_meta($edit_id, '_mervis_inquiry_email', true);
        
        if (!is_array($form_fields)) {
            $form_fields = array();
        }
        if (!is_array($associated_categories)) {
            $associated_categories = array();
        }
    }
    ?>
    <div class="wrap">
        <h1><?php echo $edit_id ? 'ویرایش فرم' : 'افزودن فرم جدید'; ?></h1>
        
        <form method="post" action="" id="mervis-form-builder">
            <?php wp_nonce_field('mervis_save_form', 'mervis_form_nonce'); ?>
            <input type="hidden" name="form_id" value="<?php echo $edit_id; ?>">
            
            <table class="form-table">
                <tr>
                    <th><label for="form_title">عنوان فرم <span style="color:red;">*</span></label></th>
                    <td><input type="text" name="form_title" id="form_title" value="<?php echo esc_attr($form_title); ?>" class="regular-text" required></td>
                </tr>
                <tr>
                    <th><label for="associated_categories">دسته‌بندی مرتبط <span style="color:red;">*</span></label></th>
                    <td>
                        <select name="associated_categories[]" id="associated_categories" multiple style="width:100%; min-width:300px; height:150px;" required>
                            <?php
                            $product_cats = get_terms(array(
                                'taxonomy' => 'product_cat',
                                'hide_empty' => false
                            ));
                            foreach ($product_cats as $cat) {
                                $selected = in_array($cat->term_id, $associated_categories) ? 'selected' : '';
                                echo '<option value="' . $cat->term_id . '" ' . $selected . '>' . $cat->name . '</option>';
                            }
                            ?>
                        </select>
                        <p class="description">برای انتخاب چندگانه Ctrl را نگه دارید.</p>
                    </td>
                </tr>
            </table>
            
            <h2>فیلدهای فرم</h2>
            <div id="form-fields-container">
                <?php
                if (!empty($form_fields)) {
                    foreach ($form_fields as $index => $field) {
                        mervis_render_field_row($index, $field);
                    }
                }
                ?>
            </div>
            
            <button type="button" class="button" id="add-new-field">+ افزودن فیلد جدید</button>
            
            <h2>قانون محاسبه قیمت</h2>
            <textarea name="price_rule" rows="5" cols="50" class="large-text" placeholder="مثال: {تعداد} * 1000 + {جنس}"><?php echo esc_textarea($price_rule); ?></textarea>
            <p class="description">از نام فیلدها داخل { } استفاده کنید.</p>
            
            <h2>تنظیمات دکمه سفارش</h2>
            <table class="form-table">
                <tr>
                    <th><label for="button_type">نوع دکمه</label></th>
                    <td>
                        <select name="button_type" id="button_type">
                            <option value="add_to_cart" <?php selected($button_type, 'add_to_cart'); ?>>افزودن به سبد خرید</option>
                            <option value="inquiry" <?php selected($button_type, 'inquiry'); ?>>استعلام قیمت</option>
                            <option value="order" <?php selected($button_type, 'order'); ?>>ثبت سفارش</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="inquiry_email">ایمیل دریافت استعلام</label></th>
                    <td>
                        <input type="email" name="inquiry_email" id="inquiry_email" value="<?php echo esc_attr($inquiry_email); ?>" class="regular-text">
                    </td>
                </tr>
            </table>
            
            <h2>محتوای سمت راست فرم</h2>
            <table class="form-table">
                <tr>
                    <td>
                        <?php
                        wp_editor($sidebar_content, 'mervis_sidebar_content', array(
                            'textarea_name' => 'mervis_sidebar_content',
                            'textarea_rows' => 10,
                            'media_buttons' => true
                        ));
                        ?>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <button type="submit" name="save_form" class="button-primary">ذخیره فرم</button>
            </p>
        </form>
    </div>
    
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
<script>
jQuery(document).ready(function($) {
    // فعال کردن sortable
    $('#form-fields-container').sortable({
        handle: '.drag-handle',
        placeholder: 'ui-state-highlight',
        axis: 'y'
    });
    
    // // افزودن فیلد جدید
    // $('#add-new-field').on('click', function() {
    //     var index = $('#form-fields-container .field-row').length;
    //     $.ajax({
    //         url: ajaxurl,
    //         type: 'POST',
    //         data: {
    //             action: 'mervis_get_empty_field_row',
    //             index: index
    //         },
    //         success: function(response) {
    //             $('#form-fields-container').append(response);
    //         }
    //     });
    // });

    // ============================================
    // افزودن فیلد جدید با جلوگیری از اجرای دوبار
    // ============================================
    $('#add-new-field').off('click').on('click', function(e) {
    e.preventDefault();
    var index = $('#form-fields-container .field-row').length;
    var edit_id = $('input[name="form_id"]').val();
    $.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            action: 'mervis_get_empty_field_row',
            index: index,
            edit_id: edit_id
        },
        success: function(response) {
            $('#form-fields-container').append(response);
        }
    });
});
    
    // حذف فیلد
    $(document).on('click', '.remove-field', function() {
        if ($('#form-fields-container .field-row').length > 1) {
            $(this).closest('.field-row').remove();
        } else {
            alert('حداقل یک فیلد باید باقی بماند');
        }
    });
    
    // تغییر نوع فیلد
    $(document).on('change', '.field-type', function() {
        var $row = $(this).closest('.field-row');
        var type = $(this).val();
        $row.find('.field-options').toggle(type === 'radio' || type === 'select' || type === 'select_custom');
        $row.find('.field-price-factor').toggle(type === 'text' || type === 'number');
        $row.find('.field-min-max').toggle(type === 'number');
        $row.find('.field-group-fields').toggle(type === 'group');
    });
    
    // ============================================
    // افزودن گزینه به فیلدهای رادیویی/انتخابگر
    // ============================================
    $(document).on('click', '.add-option-row', function() {
        var $list = $(this).closest('.field-options').find('.options-list');
        var $first = $list.find('.option-row:first');
        var textName = $first.find('input[type="text"]').attr('name');
        var priceName = $first.find('input[type="number"]').attr('name');
        
        if (!textName || !priceName) {
            // اگر ردیفی وجود ندارد، یک ردیف جدید با اندیس مناسب بساز
            var $parent = $(this).closest('.field-row');
            var fieldIndex = $parent.index();
            textName = 'fields[' + fieldIndex + '][options][]';
            priceName = 'fields[' + fieldIndex + '][option_prices][]';
        }
        
        var newRow = `
            <div class="option-row" style="display:flex; gap:10px; margin-bottom:8px; align-items:center;">
                <input type="text" name="${textName}" placeholder="نام گزینه" style="flex:2;">
                <input type="number" name="${priceName}" placeholder="قیمت اضافه" style="flex:1;">
                <button type="button" class="button remove-option" style="background:#dc2626; color:white;">حذف</button>
            </div>
        `;
        $list.append(newRow);
    });
    
    // حذف گزینه
    $(document).on('click', '.remove-option', function() {
        if ($(this).closest('.options-list').find('.option-row').length > 1) {
            $(this).closest('.option-row').remove();
        } else {
            alert('حداقل یک گزینه باید باقی بماند');
        }
    });
    
    // ============================================
    // افزودن زیرفیلد گروهی - نسخه اصلاح شده
    // ============================================
    $(document).on('click', '.add-subfield', function() {
        var $container = $(this).closest('.field-group-fields').find('.group-subfields');
        var $fieldRow = $(this).closest('.field-row');
        var fieldIndex = $fieldRow.index();
        var subCount = $container.find('.subfield-row').length;
        
        // ساخت name attribute با اندیس صحیح
        var newRow = `
            <div class="subfield-row" style="display:flex; gap:10px; margin-bottom:8px; flex-wrap:wrap; align-items:center; background:#f8fafc; padding:8px; border-radius:8px;">
                <input type="text" name="fields[${fieldIndex}][subfields][${subCount}][label]" placeholder="عنوان" style="flex:1; min-width:100px;">
                <input type="text" name="fields[${fieldIndex}][subfields][${subCount}][key]" placeholder="کلید" style="flex:1; min-width:80px;">
                <select name="fields[${fieldIndex}][subfields][${subCount}][type]" class="subfield-type" style="flex:1; min-width:80px;">
                    <option value="number">عدد</option>
                    <option value="text">متن</option>
                    <option value="select">انتخابگر</option>
                </select>
                <input type="number" name="fields[${fieldIndex}][subfields][${subCount}][min_value]" placeholder="حداقل" style="width:80px;">
                <input type="number" name="fields[${fieldIndex}][subfields][${subCount}][max_value]" placeholder="حداکثر" style="width:80px;">
                <button type="button" class="button remove-subfield" style="background:#dc2626; color:white; padding:0 10px;">×</button>
            </div>
        `;
        $container.append(newRow);
    });
    
    // حذف زیرفیلد
    $(document).on('click', '.remove-subfield', function() {
        if ($(this).closest('.group-subfields').find('.subfield-row').length > 1) {
            $(this).closest('.subfield-row').remove();
        } else {
            alert('حداقل یک زیرفیلد باید باقی بماند');
        }
    });
});
</script>
    <style>
        .ui-state-highlight { background: #f0f8ff; border: 2px dashed #2196f3; height: 60px; margin-bottom:15px; border-radius:8px; }
        .drag-handle { cursor: move; opacity:0.5; float:left; margin-right:10px; font-size:20px; }
        .drag-handle:hover { opacity:1; }
    </style>
    <?php
}

function mervis_render_field_row($index, $field, $edit_id = 0) {
    ?>
    <div class="field-row" style="border:1px solid #ddd; padding:15px; margin-bottom:15px; background:#f9f9f9;">
        <div class="drag-handle">☰</div>
        <button type="button" class="button remove-field" style="float:right;">حذف</button>
        
        <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:10px; margin-bottom:10px; margin-left:30px;">
            <div>
                <label>نوع فیلد</label>
<select name="fields[<?php echo $index; ?>][type]" class="field-type" style="width:100%;">
    <option value="text" <?php selected($field['type'] ?? '', 'text'); ?>>متن</option>
    <option value="number" <?php selected($field['type'] ?? '', 'number'); ?>>شماره</option>
    <option value="phone" <?php selected($field['type'] ?? '', 'phone'); ?>>شماره تلفن</option>
    <option value="group" <?php selected($field['type'] ?? '', 'group'); ?>>گروهی</option>
    <option value="dynamic_group" <?php selected($field['type'] ?? '', 'dynamic_group'); ?>>گروهی پویا</option> <!-- ✅ گزینه جدید -->
    <option value="radio" <?php selected($field['type'] ?? '', 'radio'); ?>>دکمه رادیویی</option>
    <option value="checkbox" <?php selected($field['type'] ?? '', 'checkbox'); ?>>چند انتخابی (چکباکس)</option>
    <option value="select" <?php selected($field['type'] ?? '', 'select'); ?>>انتخابگر</option>
    <option value="select_custom" <?php selected($field['type'] ?? '', 'select_custom'); ?>>انتخابگر + دلخواه</option>
    <option value="file" <?php selected($field['type'] ?? '', 'file'); ?>>آپلود فایل</option>
</select>
            </div>
            <div>
                <label>عنوان نمایشی</label>
                <input type="text" name="fields[<?php echo $index; ?>][label]" value="<?php echo esc_attr($field['label'] ?? ''); ?>" placeholder="عنوان فارسی" style="width:100%;">
            </div>
            <div>
                <label>کلید (انگلیسی)</label>
                <input type="text" name="fields[<?php echo $index; ?>][key]" value="<?php echo esc_attr($field['key'] ?? ''); ?>" placeholder="نام انگلیسی" style="width:100%;">
            </div>
        </div>
        
<div class="field-options" style="margin:10px 0 10px 30px; display:<?php echo (in_array($field['type'] ?? '', ['radio', 'select', 'select_custom', 'checkbox'])) ? 'block' : 'none'; ?>;">
    <div style="margin-bottom:10px;">
        <strong>گزینه‌ها و قیمت‌ها:</strong>
        <button type="button" class="button add-option-row" style="margin-right:10px;">+ افزودن گزینه</button>
    </div>
    <div class="options-list">
        <?php 
        $options = isset($field['options']) && is_array($field['options']) ? $field['options'] : array();
        $prices = isset($field['option_prices']) && is_array($field['option_prices']) ? $field['option_prices'] : array();
        $max = max(count($options), count($prices), 1);
        for ($i = 0; $i < $max; $i++): 
        ?>
        <div class="option-row" style="display:flex; gap:10px; margin-bottom:8px; align-items:center;">
            <input type="text" name="fields[<?php echo $index; ?>][options][]" value="<?php echo esc_attr($options[$i] ?? ''); ?>" placeholder="نام گزینه" style="flex:2;">
            <input type="number" name="fields[<?php echo $index; ?>][option_prices][]" value="<?php echo esc_attr($prices[$i] ?? 0); ?>" placeholder="قیمت اضافه" style="flex:1;">
            <button type="button" class="button remove-option" style="background:#dc2626; color:white; padding:0 10px;">حذف</button>
        </div>
        <?php endfor; ?>
    </div>
</div>

<!-- ============================================ -->
<!-- بخش تنظیمات وابستگی شرطی (برای همه فیلدها) -->
<!-- ============================================ -->
<div class="field-conditional-logic" style="margin:10px 0 0 30px; padding:10px; border:1px dashed #ccc; border-radius:5px; background:#f9f9f9;">
    <h4 style="margin:0 0 8px 0; font-size:13px;">🔗 وابستگی شرطی (اختیاری)</h4>
    <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
        <label style="font-weight:normal; margin:0;">نمایش این فیلد اگر:</label>
        <select name="fields[<?php echo $index; ?>][conditional_field]" style="flex:1; min-width:150px; padding:6px 10px; border:1px solid #ddd; border-radius:5px;">
            <option value="">-- وابسته به هیچ فیلدی نیست --</option>
            <?php
            // دریافت لیست فیلدهای فرم از متادیتا
            $form_fields_meta = get_post_meta($edit_id, '_mervis_form_fields', true);
            if (is_array($form_fields_meta)) {
                foreach ($form_fields_meta as $field_meta) {
                    // فقط فیلدهایی که می‌توانند منبع وابستگی باشند (مانند select, radio, checkbox)
                    if (in_array($field_meta['type'], ['select', 'radio', 'checkbox', 'select_custom'])) {
                        $field_key = $field_meta['key'] ?? sanitize_title($field_meta['label']);
                        $field_label = $field_meta['label'] ?? $field_key;
                        // اگر فیلد فعلی باشد، نمایش ندهد (نمی‌تواند به خودش وابسته باشد)
                        if ($field_key == $field['key'] ?? '') {
                            continue;
                        }
                        $selected = ($field['conditional_field'] ?? '') == $field_key ? 'selected' : '';
                        echo '<option value="' . esc_attr($field_key) . '" ' . $selected . '>' . esc_html($field_label) . '</option>';
                    }
                }
            }
            ?>
        </select>
        <span style="margin:0 5px;">برابر باشد با:</span>
        <input type="text" name="fields[<?php echo $index; ?>][conditional_value]" 
               value="<?php echo esc_attr($field['conditional_value'] ?? ''); ?>" 
               placeholder="مقدار مورد نظر" 
               style="flex:1; min-width:120px; padding:6px 10px; border:1px solid #ddd; border-radius:5px;">
    </div>
    <p style="font-size:12px; color:#666; margin:5px 0 0 0;">مثال: اگر فیلد "نوع چاپ" برابر با "برجسته" بود، این فیلد نمایش داده شود.</p>
</div>

<!-- ============================================ -->
<!-- بخش تنظیمات گروه پویا (Dynamic Group) -->
<!-- ============================================ -->
<div class="field-dynamic-group-config" style="margin:10px 0 10px 30px; display:<?php echo ($field['type'] ?? '') == 'dynamic_group' ? 'block' : 'none'; ?>; border:1px solid #e2e8f0; padding:15px; border-radius:10px; background:#f8fafc;">
    <h4 style="margin-top:0;">🔧 تنظیمات گروه پویا</h4>
    <p style="color:#666; font-size:13px; margin-bottom:15px;">برای هر گزینه، تعداد و نام زیرفیلدها را مشخص کنید. کاربر با انتخاب هر گزینه، زیرفیلدهای مربوطه را مشاهده می‌کند.</p>
    
    <div class="dynamic-group-options">
        <?php 
        $dynamic_configs = $field['dynamic_configs'] ?? array();
        if (empty($dynamic_configs)) {
            // یک نمونه پیش‌فرض
            $dynamic_configs = array(
                array(
                    'option_label' => '13x10x18',
                    'subfields' => array(
                        array('label' => 'طول', 'key' => 'length', 'type' => 'number'),
                        array('label' => 'عرض', 'key' => 'width', 'type' => 'number'),
                        array('label' => 'ارتفاع', 'key' => 'height', 'type' => 'number')
                    )
                ),
                array(
                    'option_label' => '20x15x10',
                    'subfields' => array(
                        array('label' => 'طول', 'key' => 'length', 'type' => 'number'),
                        array('label' => 'عرض', 'key' => 'width', 'type' => 'number'),
                        array('label' => 'ارتفاع', 'key' => 'height', 'type' => 'number')
                    )
                )
            );
        }
        
        foreach ($dynamic_configs as $d_idx => $config): 
        ?>
        <div class="dynamic-config-row" style="border:1px solid #ddd; padding:15px; margin-bottom:15px; border-radius:8px; background:#ffffff;">
            <div style="display:flex; gap:10px; align-items:center; margin-bottom:12px;">
                <input type="text" name="fields[<?php echo $index; ?>][dynamic_configs][<?php echo $d_idx; ?>][option_label]" 
                       value="<?php echo esc_attr($config['option_label'] ?? ''); ?>" 
                       placeholder="متن گزینه (مثلا: 13x10x18)" 
                       style="flex:2; padding:8px 12px; border:1px solid #ddd; border-radius:6px;">
                <button type="button" class="button remove-dynamic-config" style="background:#dc2626; color:white; border:none; padding:5px 15px; border-radius:6px; cursor:pointer;">🗑 حذف گزینه</button>
            </div>
            
            <div style="margin-right:20px;">
                <strong style="font-size:13px; display:block; margin-bottom:8px;">📋 زیرفیلدهای این گزینه:</strong>
                <div class="dynamic-subfields">
                    <?php 
                    $subfields = $config['subfields'] ?? array(array('label' => '', 'key' => '', 'type' => 'number'));
                    foreach ($subfields as $s_idx => $sub): 
                    ?>
                    <div class="dynamic-subfield-row" style="display:flex; gap:8px; margin-bottom:6px; flex-wrap:wrap; align-items:center;">
                        <input type="text" name="fields[<?php echo $index; ?>][dynamic_configs][<?php echo $d_idx; ?>][subfields][<?php echo $s_idx; ?>][label]" 
                               value="<?php echo esc_attr($sub['label'] ?? ''); ?>" 
                               placeholder="عنوان زیرفیلد" 
                               style="flex:1; min-width:100px; padding:6px 10px; border:1px solid #ddd; border-radius:5px;">
                        <input type="text" name="fields[<?php echo $index; ?>][dynamic_configs][<?php echo $d_idx; ?>][subfields][<?php echo $s_idx; ?>][key]" 
                               value="<?php echo esc_attr($sub['key'] ?? ''); ?>" 
                               placeholder="کلید (انگلیسی)" 
                               style="flex:1; min-width:80px; padding:6px 10px; border:1px solid #ddd; border-radius:5px;">
                        <select name="fields[<?php echo $index; ?>][dynamic_configs][<?php echo $d_idx; ?>][subfields][<?php echo $s_idx; ?>][type]" 
                                style="flex:1; min-width:80px; padding:6px 10px; border:1px solid #ddd; border-radius:5px; background:white;">
                            <option value="number" <?php selected($sub['type'] ?? 'number', 'number'); ?>>عدد</option>
                            <option value="text" <?php selected($sub['type'] ?? 'number', 'text'); ?>>متن</option>
                        </select>
                        <button type="button" class="button remove-dynamic-subfield" 
                                style="background:#dc2626; color:white; border:none; padding:2px 12px; border-radius:5px; cursor:pointer;">×</button>
                    </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="button add-dynamic-subfield" 
                        style="margin-top:8px; background:#3b82f6; color:white; border:none; padding:5px 15px; border-radius:6px; cursor:pointer;">+ افزودن زیرفیلد</button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <button type="button" class="button add-dynamic-config" 
            style="margin-top:5px; background:#22c55e; color:white; border:none; padding:8px 20px; border-radius:6px; cursor:pointer;">+ افزودن گزینه جدید</button>
</div>
        
        <div class="field-price-factor" style="margin:10px 0 10px 30px; display:<?php echo (in_array($field['type'] ?? '', ['text', 'number'])) ? 'block' : 'none'; ?>;">
            <label>ضریب قیمت: </label>
            <input type="number" step="0.01" name="fields[<?php echo $index; ?>][price_factor]" value="<?php echo esc_attr($field['price_factor'] ?? 1); ?>" style="width:100px;">
        </div>
        
        <div class="field-min-max" style="margin:10px 0 10px 30px; display:<?php echo ($field['type'] ?? '') == 'number' ? 'block' : 'none'; ?>;">
            <label>حداقل: <input type="number" name="fields[<?php echo $index; ?>][min_value]" value="<?php echo esc_attr($field['min_value'] ?? ''); ?>" style="width:80px;"></label>
            <label style="margin-right:15px;">حداکثر: <input type="number" name="fields[<?php echo $index; ?>][max_value]" value="<?php echo esc_attr($field['max_value'] ?? ''); ?>" style="width:80px;"></label>
        </div>
        
<div class="field-group-fields" style="margin:10px 0 10px 30px; display:<?php echo ($field['type'] ?? '') == 'group' ? 'block' : 'none'; ?>;">
    <h4 style="margin-bottom:10px;">زیر فیلدها:</h4>
    <div class="group-subfields">
        <?php 
        $subfields = $field['subfields'] ?? array();
        if (empty($subfields)) {
            $subfields = array(array('label' => '', 'key' => '', 'type' => 'number'));
        }
        foreach ($subfields as $s_idx => $sub): 
        ?>
        <div class="subfield-row" style="display:flex; gap:10px; margin-bottom:8px; flex-wrap:wrap; align-items:center; background:#f8fafc; padding:8px; border-radius:8px;">
            <input type="text" name="fields[<?php echo $index; ?>][subfields][<?php echo $s_idx; ?>][label]" value="<?php echo esc_attr($sub['label'] ?? ''); ?>" placeholder="عنوان" style="flex:1; min-width:100px;">
            <input type="text" name="fields[<?php echo $index; ?>][subfields][<?php echo $s_idx; ?>][key]" value="<?php echo esc_attr($sub['key'] ?? ''); ?>" placeholder="کلید" style="flex:1; min-width:80px;">
            <select name="fields[<?php echo $index; ?>][subfields][<?php echo $s_idx; ?>][type]" class="subfield-type" style="flex:1; min-width:80px;">
                <option value="number" <?php selected($sub['type'] ?? 'number', 'number'); ?>>عدد</option>
                <option value="text" <?php selected($sub['type'] ?? 'number', 'text'); ?>>متن</option>
                <option value="select" <?php selected($sub['type'] ?? 'number', 'select'); ?>>انتخابگر</option>
            </select>
            <input type="number" name="fields[<?php echo $index; ?>][subfields][<?php echo $s_idx; ?>][min_value]" value="<?php echo esc_attr($sub['min_value'] ?? ''); ?>" placeholder="حداقل" style="width:80px;">
            <input type="number" name="fields[<?php echo $index; ?>][subfields][<?php echo $s_idx; ?>][max_value]" value="<?php echo esc_attr($sub['max_value'] ?? ''); ?>" placeholder="حداکثر" style="width:80px;">
            <button type="button" class="button remove-subfield" style="background:#dc2626; color:white; padding:0 10px;">×</button>
        </div>
        <?php endforeach; ?>
    </div>
    <button type="button" class="button add-subfield" style="margin-top:10px;">+ افزودن زیر فیلد</button>
</div>


    </div>



	<?php
}

add_action('wp_ajax_mervis_get_empty_field_row', 'mervis_ajax_get_empty_field_row');
function mervis_ajax_get_empty_field_row() {
    $index = intval($_POST['index']);
    $edit_id = intval($_POST['edit_id'] ?? 0);
    mervis_render_field_row($index, array(
        'type' => 'text',
        'label' => '',
        'key' => '',
        'options' => array(),
        'option_prices' => array(),
        'price_factor' => 1,
        'min_value' => '',
        'max_value' => '',
        'subfields' => array(),
        'dynamic_configs' => array(),
        'conditional_field' => '',
        'conditional_value' => ''
    ), $edit_id);
    wp_die();
}
?>