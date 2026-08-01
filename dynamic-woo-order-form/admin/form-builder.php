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
        
        if (!is_array($form_fields)) $form_fields = array();
        if (!is_array($associated_categories)) $associated_categories = array();
    }
    ?>
    <div class="wrap">
        <h1><?php echo $edit_id ? 'ویرایش فرم' : 'افزودن فرم جدید'; ?></h1>
        
        <form method="post" action="" id="mervis-form-builder">
            <?php wp_nonce_field('mervis_save_form', 'mervis_form_nonce'); ?>
            <!-- خط اصلاح شده در زیر (حذف علامت $ اضافی قبل از esc_attr) -->
            <input type="hidden" name="form_id" value="<?php echo esc_attr($edit_id); ?>">
            
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
                            $product_cats = get_terms(array('taxonomy' => 'product_cat', 'hide_empty' => false));
                            foreach ($product_cats as $cat) {
                                $selected = in_array($cat->term_id, $associated_categories) ? 'selected' : '';
                                echo '<option value="' . $cat->term_id . '" ' . $selected . '>' . esc_html($cat->name) . '</option>';
                            }
                            ?>
                        </select>
                        <p class="description">برای انتخاب چندگانه کلید Ctrl (یا Cmd در مک) را نگه دارید.</p>
                    </td>
                </tr>
            </table>
            
            <h2>فیلدهای فرم</h2>
            <div id="form-fields-container">
                <?php
                if (!empty($form_fields)) {
                    foreach ($form_fields as $index => $field) {
                        mervis_render_field_row($index, $field, $form_fields, $edit_id);
                    }
                }
                ?>
            </div>
            
            <button type="button" class="button button-primary" id="add-new-field" style="margin-top:10px;">+ افزودن فیلد جدید</button>
            
            <h2>قانون محاسبه قیمت</h2>
            <textarea name="price_rule" rows="5" cols="50" class="large-text" placeholder="مثال: {تعداد} * 1000 + {جنس}"><?php echo esc_textarea($price_rule); ?></textarea>
            <p class="description">از کلید (key) فیلدها داخل { } استفاده کنید.</p>
            
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
                        <!-- چک‌باکس اجباری بودن فیلد -->
<div style="margin:10px 0 10px 30px;">
    <label style="cursor:pointer; display:inline-flex; align-items:center; gap:8px; font-weight:normal;">
        <input type="checkbox" name="fields[<?php echo $index; ?>][required]" value="1" <?php checked(!empty($field['required']), 1); ?>>
        <span>این فیلد اجباری است (کاربر حتماً باید آن را پر کند)</span>
    </label>
</div>
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
                <button type="submit" name="save_form" class="button-primary button button-hero">ذخیره فرم</button>
            </p>
        </form>
    </div>
    
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <script>
    jQuery(document).ready(function($) {
        $('#form-fields-container').sortable({
            handle: '.drag-handle',
            placeholder: 'ui-state-highlight',
            axis: 'y'
        });
        
        // 1. جلوگیری قطعی از اجرای دوباره و ایجاد دو فیلد
        $(document).off('click', '#add-new-field').on('click', '#add-new-field', function(e) {
            e.preventDefault();
            var $btn = $(this);
            
            if ($btn.hasClass('is-loading')) return;
            $btn.addClass('is-loading').text('در حال افزودن...');
            
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
                    $('#form-fields-container .field-row:last').find('.field-type').trigger('change');
                },
                complete: function() {
                    $btn.removeClass('is-loading').text('+ افزودن فیلد جدید');
                }
            });
        });

        $(document).on('click', '.remove-field', function() {
            if ($('#form-fields-container .field-row').length > 1) {
                if(confirm('آیا از حذف این فیلد اطمینان دارید؟')) {
                    $(this).closest('.field-row').remove();
                }
            } else {
                alert('حداقل یک فیلد باید در فرم باقی بماند.');
            }
        });
        
        $(document).on('change', '.field-type', function() {
            var $row = $(this).closest('.field-row');
            var type = $(this).val();
            
            $row.find('.field-options').toggle(type === 'radio' || type === 'select' || type === 'select_custom' || type === 'checkbox');
            $row.find('.field-display-options').toggle(type === 'radio' || type === 'checkbox');
            $row.find('.field-price-factor').toggle(type === 'text' || type === 'number');
            $row.find('.field-min-max').toggle(type === 'number');
            $row.find('.field-group-fields').toggle(type === 'group');
            $row.find('.field-dynamic-group-config').toggle(type === 'dynamic_group');
        });
        
        $(document).on('click', '.add-option-row', function() {
            var $list = $(this).closest('.field-options').find('.options-list');
            var fieldIndex = $(this).closest('.field-row').index();
            
            var newRow = `
                <div class="option-row" style="display:flex; gap:10px; margin-bottom:8px; align-items:center;">
                    <input type="text" name="fields[${fieldIndex}][options][]" placeholder="نام گزینه" style="flex:2;">
                    <input type="number" name="fields[${fieldIndex}][option_prices][]" placeholder="قیمت اضافه" style="flex:1;">
                    <button type="button" class="button remove-option" style="background:#dc2626; color:white;">حذف</button>
                </div>
            `;
            $list.append(newRow);
        });
        
        $(document).on('click', '.remove-option', function() {
            if ($(this).closest('.options-list').find('.option-row').length > 1) {
                $(this).closest('.option-row').remove();
            } else {
                alert('حداقل یک گزینه باید باقی بماند.');
            }
        });
        
        $(document).on('click', '.add-subfield', function() {
            var $container = $(this).closest('.field-group-fields').find('.group-subfields');
            var fieldIndex = $(this).closest('.field-row').index();
            var subCount = $container.find('.subfield-row').length;
            
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
        
        $(document).on('click', '.remove-subfield', function() {
            if ($(this).closest('.group-subfields').find('.subfield-row').length > 1) {
                $(this).closest('.subfield-row').remove();
            } else {
                alert('حداقل یک زیرفیلد باید باقی بماند.');
            }
        });

        $(document).on('click', '.add-dynamic-config', function() {
            var $container = $(this).closest('.field-dynamic-group-config').find('.dynamic-group-options');
            var fieldIndex = $(this).closest('.field-row').index();
            var configCount = $container.find('.dynamic-config-row').length;
            
            var newRow = `
                <div class="dynamic-config-row" style="border:1px solid #ddd; padding:15px; margin-bottom:15px; border-radius:8px; background:#ffffff;">
                    <div style="display:flex; gap:10px; align-items:center; margin-bottom:12px;">
                        <input type="text" name="fields[${fieldIndex}][dynamic_configs][${configCount}][option_label]" placeholder="متن گزینه" style="flex:2; padding:8px 12px; border:1px solid #ddd; border-radius:6px;">
                        <button type="button" class="button remove-dynamic-config" style="background:#dc2626; color:white; border:none; padding:5px 15px; border-radius:6px; cursor:pointer;">🗑 حذف</button>
                    </div>
                    <div style="margin-right:20px;">
                        <strong style="font-size:13px; display:block; margin-bottom:8px;">📋 زیرفیلدها:</strong>
                        <div class="dynamic-subfields">
                            <div class="dynamic-subfield-row" style="display:flex; gap:8px; margin-bottom:6px; flex-wrap:wrap; align-items:center;">
                                <input type="text" name="fields[${fieldIndex}][dynamic_configs][${configCount}][subfields][0][label]" placeholder="عنوان" style="flex:1; min-width:100px; padding:6px 10px; border:1px solid #ddd; border-radius:5px;">
                                <input type="text" name="fields[${fieldIndex}][dynamic_configs][${configCount}][subfields][0][key]" placeholder="کلید" style="flex:1; min-width:80px; padding:6px 10px; border:1px solid #ddd; border-radius:5px;">
                                <select name="fields[${fieldIndex}][dynamic_configs][${configCount}][subfields][0][type]" style="flex:1; min-width:80px; padding:6px 10px; border:1px solid #ddd; border-radius:5px; background:white;">
                                    <option value="number">عدد</option>
                                    <option value="text">متن</option>
                                </select>
                                <button type="button" class="button remove-dynamic-subfield" style="background:#dc2626; color:white; border:none; padding:2px 12px; border-radius:5px; cursor:pointer;">×</button>
                            </div>
                        </div>
                        <button type="button" class="button add-dynamic-subfield" style="margin-top:8px; background:#3b82f6; color:white; border:none; padding:5px 15px; border-radius:6px; cursor:pointer;">+ افزودن زیرفیلد</button>
                    </div>
                </div>
            `;
            $container.append(newRow);
        });

        $(document).on('click', '.remove-dynamic-config', function() {
            $(this).closest('.dynamic-config-row').remove();
        });

        $(document).on('click', '.add-dynamic-subfield', function() {
            var $container = $(this).closest('.dynamic-config-row').find('.dynamic-subfields');
            var fieldIndex = $(this).closest('.field-row').index();
            var configIndex = $(this).closest('.dynamic-config-row').index();
            var subCount = $container.find('.dynamic-subfield-row').length;
            
            var newRow = `
                <div class="dynamic-subfield-row" style="display:flex; gap:8px; margin-bottom:6px; flex-wrap:wrap; align-items:center;">
                    <input type="text" name="fields[${fieldIndex}][dynamic_configs][${configIndex}][subfields][${subCount}][label]" placeholder="عنوان" style="flex:1; min-width:100px; padding:6px 10px; border:1px solid #ddd; border-radius:5px;">
                    <input type="text" name="fields[${fieldIndex}][dynamic_configs][${configIndex}][subfields][${subCount}][key]" placeholder="کلید" style="flex:1; min-width:80px; padding:6px 10px; border:1px solid #ddd; border-radius:5px;">
                    <select name="fields[${fieldIndex}][dynamic_configs][${configIndex}][subfields][${subCount}][type]" style="flex:1; min-width:80px; padding:6px 10px; border:1px solid #ddd; border-radius:5px; background:white;">
                        <option value="number">عدد</option>
                        <option value="text">متن</option>
                    </select>
                    <button type="button" class="button remove-dynamic-subfield" style="background:#dc2626; color:white; border:none; padding:2px 12px; border-radius:5px; cursor:pointer;">×</button>
                </div>
            `;
            $container.append(newRow);
        });

        $(document).on('click', '.remove-dynamic-subfield', function() {
            if ($(this).closest('.dynamic-subfields').find('.dynamic-subfield-row').length > 1) {
                $(this).closest('.dynamic-subfield-row').remove();
            } else {
                alert('حداقل یک زیرفیلد باید باقی بماند.');
            }
        });
    });
    </script>
    <style>
        .ui-state-highlight { background: #f0f8ff; border: 2px dashed #2196f3; height: 60px; margin-bottom:15px; border-radius:8px; }
        .drag-handle { cursor: move; opacity:0.5; float:left; margin-right:10px; font-size:20px; }
        .drag-handle:hover { opacity:1; }
        #add-new-field.is-loading { opacity: 0.7; cursor: not-allowed; }
    </style>
    <?php
}

function mervis_render_field_row($index, $field, $all_fields = array(), $edit_id = 0) {
    $field_type = $field['type'] ?? 'text';
    ?>
    <div class="field-row" style="border:1px solid #ddd; padding:15px; margin-bottom:15px; background:#f9f9f9; border-radius:8px;">
        <div class="drag-handle">☰</div>
        <button type="button" class="button remove-field" style="float:right; background:#dc2626; color:white; border-color:#dc2626;">حذف فیلد</button>
        
        <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:10px; margin-bottom:10px; margin-left:30px;">
            <div>
                <label style="font-weight:600; display:block; margin-bottom:5px;">نوع فیلد</label>
                <select name="fields[<?php echo $index; ?>][type]" class="field-type" style="width:100%; padding:6px;">
                    <option value="text" <?php selected($field_type, 'text'); ?>>متن</option>
                    <option value="number" <?php selected($field_type, 'number'); ?>>شماره (عدد)</option>
                    <option value="phone" <?php selected($field_type, 'phone'); ?>>شماره تلفن</option>
                    <option value="group" <?php selected($field_type, 'group'); ?>>گروهی (ثابت)</option>
                    <option value="dynamic_group" <?php selected($field_type, 'dynamic_group'); ?>>گروهی پویا</option>
                    <option value="radio" <?php selected($field_type, 'radio'); ?>>دکمه رادیویی</option>
                    <option value="checkbox" <?php selected($field_type, 'checkbox'); ?>>چند انتخابی</option>
                    <option value="select" <?php selected($field_type, 'select'); ?>>انتخابگر</option>
                    <option value="select_custom" <?php selected($field_type, 'select_custom'); ?>>انتخابگر + دلخواه</option>
                    <option value="file" <?php selected($field_type, 'file'); ?>>آپلود فایل</option>
                </select>
            </div>
            <div>
                <label style="font-weight:600; display:block; margin-bottom:5px;">عنوان نمایشی</label>
                <input type="text" name="fields[<?php echo $index; ?>][label]" value="<?php echo esc_attr($field['label'] ?? ''); ?>" placeholder="عنوان فارسی" style="width:100%; padding:6px;">
            </div>
            <div>
                <label style="font-weight:600; display:block; margin-bottom:5px;">کلید (انگلیسی)</label>
                <input type="text" name="fields[<?php echo $index; ?>][key]" value="<?php echo esc_attr($field['key'] ?? ''); ?>" placeholder="نام انگلیسی" style="width:100%; padding:6px;">
            </div>
        </div>
        
        <div class="field-options" style="margin:10px 0 10px 30px; display:<?php echo in_array($field_type, ['radio', 'select', 'select_custom', 'checkbox']) ? 'block' : 'none'; ?>;">
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
                    <input type="text" name="fields[<?php echo $index; ?>][options][]" value="<?php echo esc_attr($options[$i] ?? ''); ?>" placeholder="نام گزینه" style="flex:2; padding:6px;">
                    <input type="number" name="fields[<?php echo $index; ?>][option_prices][]" value="<?php echo esc_attr($prices[$i] ?? 0); ?>" placeholder="قیمت" style="flex:1; padding:6px;">
                    <button type="button" class="button remove-option" style="background:#dc2626; color:white; padding:0 10px;">حذف</button>
                </div>
                <?php endfor; ?>
            </div>
            
            <div class="field-display-options" style="margin-top:15px; padding:10px; background:#fff; border:1px solid #e2e8f0; border-radius:6px; display:<?php echo in_array($field_type, ['radio', 'checkbox']) ? 'block' : 'none'; ?>;">
                <label style="cursor:pointer; display:flex; align-items:center; gap:8px; font-weight:normal;">
                    <input type="checkbox" name="fields[<?php echo $index; ?>][display_horizontal]" value="1" <?php checked(!empty($field['display_horizontal']), 1); ?>>
                    <span>نمایش گزینه‌ها به صورت افقی (سطری) در فرم نهایی</span>
                </label>
            </div>
        </div>

        <div class="field-conditional-logic" style="margin:10px 0 10px 30px; padding:12px; border:1px dashed #3b82f6; border-radius:8px; background:#eff6ff;">
            <h4 style="margin:0 0 10px 0; font-size:14px; color:#1e40af;">🔗 وابستگی شرطی (نمایش هوشمند)</h4>
            <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
                <label style="font-weight:normal; margin:0; white-space:nowrap;">این فیلد نمایش داده شود اگر:</label>
                <select name="fields[<?php echo $index; ?>][conditional_parent]" style="flex:1; min-width:180px; padding:6px 10px; border:1px solid #cbd5e1; border-radius:5px;">
                    <option value="">-- بدون شرط (همیشه نمایش داده شود) --</option>
                    <?php
                    if (is_array($all_fields)) {
                        foreach ($all_fields as $f_idx => $f_data) {
                            if ($f_idx != $index && in_array($f_data['type'] ?? '', ['select', 'radio', 'checkbox', 'select_custom'])) {
                                $f_key = $f_data['key'] ?? sanitize_title($f_data['label']);
                                $f_label = $f_data['label'] ?: $f_key;
                                $selected = ($field['conditional_parent'] ?? '') == $f_key ? 'selected' : '';
                                echo '<option value="' . esc_attr($f_key) . '" ' . $selected . '>' . esc_html($f_label) . ' (' . esc_html($f_key) . ')</option>';
                            }
                        }
                    }
                    ?>
                </select>
                <span style="margin:0 5px; white-space:nowrap;">برابر باشد با:</span>
                <input type="text" name="fields[<?php echo $index; ?>][conditional_value]" 
                       value="<?php echo esc_attr($field['conditional_value'] ?? ''); ?>" 
                       placeholder="مقدار دقیق گزینه" 
                       style="flex:1; min-width:150px; padding:6px 10px; border:1px solid #cbd5e1; border-radius:5px;">
            </div>
            <p style="font-size:12px; color:#64748b; margin:8px 0 0 0;">💡 مثال: اگر فیلد "نوع چاپ" برابر با "برجسته" بود، این فیلد نمایش داده شود.</p>
        </div>

        <div class="field-price-factor" style="margin:10px 0 10px 30px; display:<?php echo in_array($field_type, ['text', 'number']) ? 'block' : 'none'; ?>;">
            <label style="font-weight:600;">ضریب قیمت: </label>
            <input type="number" step="0.01" name="fields[<?php echo $index; ?>][price_factor]" value="<?php echo esc_attr($field['price_factor'] ?? 1); ?>" style="width:100px; padding:6px;">
        </div>
        
        <div class="field-min-max" style="margin:10px 0 10px 30px; display:<?php echo $field_type == 'number' ? 'block' : 'none'; ?>;">
            <label style="font-weight:600;">حداقل: <input type="number" name="fields[<?php echo $index; ?>][min_value]" value="<?php echo esc_attr($field['min_value'] ?? ''); ?>" style="width:80px; padding:6px;"></label>
            <label style="margin-right:15px; font-weight:600;">حداکثر: <input type="number" name="fields[<?php echo $index; ?>][max_value]" value="<?php echo esc_attr($field['max_value'] ?? ''); ?>" style="width:80px; padding:6px;"></label>
        </div>
        
        <div class="field-group-fields" style="margin:10px 0 10px 30px; display:<?php echo $field_type == 'group' ? 'block' : 'none'; ?>;">
            <h4 style="margin-bottom:10px;">زیر فیلدهای این گروه:</h4>
            <div class="group-subfields">
                <?php 
                $subfields = $field['subfields'] ?? array();
                if (empty($subfields)) {
                    $subfields = array(array('label' => '', 'key' => '', 'type' => 'number'));
                }
                foreach ($subfields as $s_idx => $sub): 
                ?>
                <div class="subfield-row" style="display:flex; gap:10px; margin-bottom:8px; flex-wrap:wrap; align-items:center; background:#fff; padding:8px; border-radius:8px; border:1px solid #e2e8f0;">
                    <input type="text" name="fields[<?php echo $index; ?>][subfields][<?php echo $s_idx; ?>][label]" value="<?php echo esc_attr($sub['label'] ?? ''); ?>" placeholder="عنوان" style="flex:1; min-width:100px; padding:6px;">
                    <input type="text" name="fields[<?php echo $index; ?>][subfields][<?php echo $s_idx; ?>][key]" value="<?php echo esc_attr($sub['key'] ?? ''); ?>" placeholder="کلید" style="flex:1; min-width:80px; padding:6px;">
                    <select name="fields[<?php echo $index; ?>][subfields][<?php echo $s_idx; ?>][type]" class="subfield-type" style="flex:1; min-width:80px; padding:6px;">
                        <option value="number" <?php selected($sub['type'] ?? 'number', 'number'); ?>>عدد</option>
                        <option value="text" <?php selected($sub['type'] ?? 'number', 'text'); ?>>متن</option>
                        <option value="select" <?php selected($sub['type'] ?? 'number', 'select'); ?>>انتخابگر</option>
                    </select>
                    <input type="number" name="fields[<?php echo $index; ?>][subfields][<?php echo $s_idx; ?>][min_value]" value="<?php echo esc_attr($sub['min_value'] ?? ''); ?>" placeholder="حداقل" style="width:80px; padding:6px;">
                    <input type="number" name="fields[<?php echo $index; ?>][subfields][<?php echo $s_idx; ?>][max_value]" value="<?php echo esc_attr($sub['max_value'] ?? ''); ?>" placeholder="حداکثر" style="width:80px; padding:6px;">
                    <button type="button" class="button remove-subfield" style="background:#dc2626; color:white; padding:0 10px;">×</button>
                </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="button add-subfield" style="margin-top:10px;">+ افزودن زیر فیلد</button>
        </div>

        <div class="field-dynamic-group-config" style="margin:10px 0 10px 30px; display:<?php echo $field_type == 'dynamic_group' ? 'block' : 'none'; ?>; border:1px solid #e2e8f0; padding:15px; border-radius:10px; background:#f8fafc;">
            <h4 style="margin-top:0;">🔧 تنظیمات گروه پویا</h4>
            <p style="color:#64748b; font-size:13px; margin-bottom:15px;">برای هر گزینه، زیرفیلدهای مختص به آن را تعریف کنید.</p>
            
            <div class="dynamic-group-options">
                <?php 
                $dynamic_configs = $field['dynamic_configs'] ?? array();
                if (empty($dynamic_configs)) {
                    $dynamic_configs = array(
                        array(
                            'option_label' => 'گزینه پیش‌فرض',
                            'subfields' => array(array('label' => 'زیرفیلد ۱', 'key' => 'sub1', 'type' => 'number'))
                        )
                    );
                }
                
                foreach ($dynamic_configs as $d_idx => $config): 
                ?>
                <div class="dynamic-config-row" style="border:1px solid #cbd5e1; padding:15px; margin-bottom:15px; border-radius:8px; background:#ffffff;">
                    <div style="display:flex; gap:10px; align-items:center; margin-bottom:12px;">
                        <input type="text" name="fields[<?php echo $index; ?>][dynamic_configs][<?php echo $d_idx; ?>][option_label]" 
                               value="<?php echo esc_attr($config['option_label'] ?? ''); ?>" 
                               placeholder="متن گزینه" 
                               style="flex:2; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;">
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
                                       style="flex:1; min-width:100px; padding:6px 10px; border:1px solid #cbd5e1; border-radius:5px;">
                                <input type="text" name="fields[<?php echo $index; ?>][dynamic_configs][<?php echo $d_idx; ?>][subfields][<?php echo $s_idx; ?>][key]" 
                                       value="<?php echo esc_attr($sub['key'] ?? ''); ?>" 
                                       placeholder="کلید (انگلیسی)" 
                                       style="flex:1; min-width:80px; padding:6px 10px; border:1px solid #cbd5e1; border-radius:5px;">
                                <select name="fields[<?php echo $index; ?>][dynamic_configs][<?php echo $d_idx; ?>][subfields][<?php echo $s_idx; ?>][type]" 
                                        style="flex:1; min-width:80px; padding:6px 10px; border:1px solid #cbd5e1; border-radius:5px; background:white;">
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

    </div>
    <?php
}

add_action('wp_ajax_mervis_get_empty_field_row', 'mervis_ajax_get_empty_field_row');
function mervis_ajax_get_empty_field_row() {
    $index = intval($_POST['index']);
    $edit_id = intval($_POST['edit_id'] ?? 0);
    
    $all_fields = array();
    if ($edit_id) {
        $all_fields = get_post_meta($edit_id, '_mervis_form_fields', true);
        if (!is_array($all_fields)) $all_fields = array();
    }
    
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
        'conditional_parent' => '',
        'conditional_value' => '',
        'display_horizontal' => 0
    ), $all_fields, $edit_id);
    
    wp_die();
}
?>