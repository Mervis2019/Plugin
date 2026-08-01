<?php
function mervis_display_order_form_tab_content() {
    global $product;
    $product_id = $product->get_id();
    $form_id = $GLOBALS['mervis_current_form_id'] ?? null;
    
    if (!$form_id) {
        $form_id = mervis_get_form_id_for_product($product_id);
    }
    
    if (!$form_id) {
        if (current_user_can('manage_options')) {
            echo '<div style="background:#fff3cd; padding:15px; border-radius:8px; direction:rtl;">⚠️ فرمی برای این محصول پیدا نشد.</div>';
        }
        return;
    }
    
    $fields = get_post_meta($form_id, '_mervis_form_fields', true);
    $price_rule = get_post_meta($form_id, '_mervis_price_rule', true);
    $sidebar_content = get_post_meta($form_id, '_mervis_sidebar_content', true);
    $button_type = get_post_meta($form_id, '_mervis_button_type', true);
    
    if (empty($fields) || !is_array($fields)) {
        echo '<p>فرم پیدا شد اما فیلدی ندارد.</p>';
        return;
    }
    
    wp_enqueue_style('mervis-form-style', MERVIS_FORM_URL . 'frontend/form-styles.css', array(), '1.0');
    wp_enqueue_script('mervis-price-calculator', MERVIS_FORM_URL . 'frontend/price-calculator.js', array('jquery'), '1.0', true);
    
    $button_text = $button_type == 'inquiry' ? '📞 استعلام قیمت' : ($button_type == 'order' ? '✅ ثبت سفارش' : '➕ افزودن به سبد خرید');
    ?>
    <style>
        .mervis-layout-2cols{display:flex;gap:30px;flex-wrap:wrap}
        .mervis-form-col{flex:2;min-width:250px}
        .mervis-sidebar-col{flex:1;min-width:200px;background:#f8fafc;padding:20px;border-radius:16px;position:sticky;top:20px;align-self:flex-start}
        @media(max-width:768px){.mervis-layout-2cols{flex-direction:column}.mervis-sidebar-col{position:static;order:-1}}
        .mervis-form-modern .form-grid-4cols{display:grid;grid-template-columns:repeat(4,1fr);gap:25px}
        .mervis-form-modern .form-full-width{grid-column:span 4}
        @media(min-width:481px) and (max-width:768px){.mervis-form-modern .form-grid-4cols{grid-template-columns:repeat(2,1fr)}.mervis-form-modern .form-full-width{grid-column:span 2}}
        @media(max-width:480px){.mervis-form-modern .form-grid-4cols{display:block}}
        .mervis-radio-group{display:flex;gap:15px;flex-wrap:wrap}
        .mervis-radio-group label{display:inline-flex;align-items:center;gap:8px;cursor:pointer;background:white;padding:8px 18px;border-radius:50px;border:1.5px solid #e2e8f0;transition:all .2s}
        .mervis-radio-group label:has(input:checked){background:#e8f4fd;border-color:#3b82f6;color:#1e40af}
        .mervis-file-input{border:2px dashed #cbd5e1;border-radius:16px;padding:20px;text-align:center;background:#fff;cursor:pointer;transition:all .25s}
        .mervis-file-input:hover{border-color:#3b82f6;background:#f0f9ff}
        .mervis-file-name{margin-top:10px;font-size:12px;color:#10b981;text-align:center}
        .mervis-price-box{background:linear-gradient(135deg,#1e293b,#0f172a);color:#fff;padding:20px;border-radius:16px;text-align:center;margin:25px 0}
        .mervis-price-box .price-value{font-size:32px;font-weight:bold}
        .mervis-calc-btn,.mervis-add-to-cart-btn,.mervis-inquiry-btn,.mervis-order-btn{width:100%;border:none;padding:12px;font-size:16px;font-weight:600;border-radius:40px;cursor:pointer;margin-top:10px;transition:all .2s}
        .mervis-calc-btn{background:#f1f5f9;color:#334155;border:1px solid #e2e8f0}
        .mervis-calc-btn:hover{background:#e2e8f0;transform:translateY(-2px)}
        .mervis-add-to-cart-btn,.mervis-inquiry-btn,.mervis-order-btn{background:linear-gradient(135deg,#10b981,#059669);color:white}
        .mervis-add-to-cart-btn:hover,.mervis-inquiry-btn:hover,.mervis-order-btn:hover{transform:translateY(-2px)}
        .mervis-custom-input{margin-top:10px}
        .mervis-group-field>div{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:10px}
        .mervis-group-field label{font-size:12px;font-weight:normal;color:#666}
        .mervis-form-modern .form-row-modern{margin-bottom:20px}
        .mervis-form-modern .form-row-modern label{display:block;font-weight:600;margin-bottom:8px;color:#1e293b;font-size:14px}
        .mervis-form-modern input[type="text"],.mervis-form-modern input[type="number"],.mervis-form-modern select{width:100%;padding:10px 14px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:14px;transition:all .25s;background:white}
        .mervis-form-modern input:focus,.mervis-form-modern select:focus{border-color:#3b82f6;outline:none;box-shadow:0 0 0 3px rgba(59,130,246,0.1)}
        
        /* استایل‌های وابستگی شرطی */
        .conditional-field {
            transition: all 0.3s ease;
        }
        .conditional-field-hidden {
            display: none !important;
        }
    </style>
    
    <div class="mervis-layout-2cols">
        <div class="mervis-form-col">
            <div class="mervis-form-modern">
                <form id="mervis-dynamic-order-form" method="post" enctype="multipart/form-data">
                    <?php wp_nonce_field('mervis_add_to_cart', 'mervis_form_nonce'); ?>
                    <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                    <input type="hidden" name="form_id" value="<?php echo $form_id; ?>">
                    <input type="hidden" id="mervis-calculated-price" name="calculated_price" value="0">
                    
                    <div class="form-grid-4cols">
                        <?php foreach ($fields as $field): 
                            $key = $field['key'] ?? sanitize_title($field['label']);
                            $label = $field['label'];
                            $type = $field['type'];
                            $is_full_width = ($type == 'file' || $type == 'group' || $type == 'dynamic_group');
                            
                            // ============================================
                            // وابستگی شرطی - آماده‌سازی داده‌ها
                            // ============================================
                            $conditional_class = '';
                            $conditional_attrs = '';
                            $conditional_style = '';
                            
                            if (!empty($field['conditional_field']) && !empty($field['conditional_value'])) {
                                $conditional_class = 'conditional-field';
                                $conditional_attrs = 'data-conditional-field="' . esc_attr($field['conditional_field']) . '" data-conditional-value="' . esc_attr($field['conditional_value']) . '"';
                                // ابتدا مخفی باشد تا بعداً توسط جاوااسکریپت نمایش داده شود
                                $conditional_style = 'display:none;';
                            }
                        ?>
                            <?php if ($is_full_width): ?>
                                <div class="form-full-width <?php echo $conditional_class; ?>" <?php echo $conditional_attrs; ?> style="<?php echo $conditional_style; ?>">
                            <?php else: ?>
                                <div class="form-row-modern <?php echo $conditional_class; ?>" <?php echo $conditional_attrs; ?> style="<?php echo $conditional_style; ?>">
                            <?php endif; ?>
                            
                            <?php if ($type == 'text'): ?>
                                <label><?php echo esc_html($label); ?></label>
                                <input type="text" name="<?php echo esc_attr($key); ?>" data-price-factor="<?php echo esc_attr($field['price_factor'] ?? 1); ?>" placeholder="مقدار را وارد کنید...">
                                
                            <?php elseif ($type == 'number'): ?>
                                <label><?php echo esc_html($label); ?></label>
                                <input type="number" name="<?php echo esc_attr($key); ?>" data-price-factor="<?php echo esc_attr($field['price_factor'] ?? 1); ?>" <?php if (!empty($field['min_value'])): ?>min="<?php echo $field['min_value']; ?>"<?php endif; ?> <?php if (!empty($field['max_value'])): ?>max="<?php echo $field['max_value']; ?>"<?php endif; ?> placeholder="عدد را وارد کنید...">
                                
                            <?php elseif ($type == 'phone'): ?>
                                <label><?php echo esc_html($label); ?></label>
                                <input type="tel" 
                                       name="<?php echo esc_attr($key); ?>" 
                                       class="mervis-phone-input" 
                                       placeholder="<?php echo esc_attr($label); ?>"
                                       pattern="[0-9]{10,11}" 
                                       maxlength="11"
                                       data-label="<?php echo esc_attr($label); ?>">
                                
                            <?php elseif ($type == 'group'): ?>
                                <label class="mervis-group-label"><?php echo esc_html($label); ?></label>
                                <div class="mervis-group-row">
                                    <?php foreach ($field['subfields'] ?? array() as $sub): 
                                        $sk = $sub['key'] ?? sanitize_title($sub['label']);
                                        $stype = $sub['type'] ?? 'number';
                                        $s_label = $sub['label'] ?? '';
                                    ?>
                                        <div class="mervis-group-item">
                                            <?php if ($stype == 'number'): ?>
                                                <input type="number" 
                                                       name="<?php echo esc_attr($key . '_' . $sk); ?>" 
                                                       placeholder="<?php echo esc_attr($s_label); ?>"
                                                       data-label="<?php echo esc_attr($s_label); ?>"
                                                       data-price-factor="1"
                                                       <?php if (!empty($sub['min_value'])): ?>min="<?php echo esc_attr($sub['min_value']); ?>"<?php endif; ?>
                                                       <?php if (!empty($sub['max_value'])): ?>max="<?php echo esc_attr($sub['max_value']); ?>"<?php endif; ?>>
                                            <?php elseif ($stype == 'select'): ?>
                                                <select name="<?php echo esc_attr($key . '_' . $sk); ?>" data-label="<?php echo esc_attr($s_label); ?>">
                                                    <option value=""><?php echo esc_html($s_label); ?></option>
                                                    <?php foreach ($sub['options'] ?? array() as $o_idx => $opt): ?>
                                                        <option value="<?php echo esc_attr($opt); ?>" data-price="<?php echo esc_attr($sub['option_prices'][$o_idx] ?? 0); ?>">
                                                            <?php echo esc_html($opt); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            <?php else: ?>
                                                <input type="text" 
                                                       name="<?php echo esc_attr($key . '_' . $sk); ?>" 
                                                       placeholder="<?php echo esc_attr($s_label); ?>"
                                                       data-label="<?php echo esc_attr($s_label); ?>">
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                            <?php elseif ($type == 'radio'): ?>
                                <label><?php echo esc_html($label); ?></label>
                                <div class="mervis-radio-group" style="display:flex; flex-wrap:wrap; gap:10px; align-items:center; width:100%;">
                                    <?php foreach ($field['options'] ?? array() as $idx => $opt): ?>
                                        <label class="mervis-radio-option" style="display:inline-flex !important; align-items:center; gap:8px; cursor:pointer; background:white; padding:8px 18px; border-radius:50px; border:2px solid #e2e8f0; transition:all 0.2s ease; margin:0 !important; font-weight:500; font-size:14px; color:#1e293b; flex:0 1 auto;">
                                            <input type="radio" 
                                                   name="<?php echo esc_attr($key); ?>" 
                                                   value="<?php echo esc_attr($opt); ?>" 
                                                   data-price="<?php echo esc_attr($field['option_prices'][$idx] ?? 0); ?>"
                                                   style="margin:0 !important; margin-left:8px !important; accent-color:#3b82f6; width:16px; height:16px; flex-shrink:0; cursor:pointer;">
                                            <span><?php echo esc_html($opt); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                                
                            <?php elseif ($type == 'checkbox'): ?>
                                <label><?php echo esc_html($label); ?></label>
                                <div class="mervis-checkbox-group" style="display:flex; flex-wrap:wrap; gap:10px; align-items:center; width:100%;">
                                    <?php foreach ($field['options'] ?? array() as $idx => $opt): ?>
                                        <label class="mervis-checkbox-option" style="display:inline-flex !important; align-items:center; gap:8px; cursor:pointer; background:white; padding:8px 18px; border-radius:50px; border:2px solid #e2e8f0; transition:all 0.2s ease; margin:0 !important; font-weight:500; font-size:14px; color:#1e293b; flex:0 1 auto;">
                                            <input type="checkbox" 
                                                   name="<?php echo esc_attr($key); ?>[]" 
                                                   value="<?php echo esc_attr($opt); ?>" 
                                                   data-price="<?php echo esc_attr($field['option_prices'][$idx] ?? 0); ?>"
                                                   style="margin:0 !important; margin-left:8px !important; accent-color:#3b82f6; width:16px; height:16px; flex-shrink:0; cursor:pointer;">
                                            <span><?php echo esc_html($opt); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>

                            <?php elseif ($type == 'dynamic_group'): ?>
                                <label><?php echo esc_html($label); ?></label>
                                <select class="mervis-dynamic-select" data-key="<?php echo esc_attr($key); ?>" 
                                        style="width:100%; padding:10px 14px; border:1.5px solid #e2e8f0; border-radius:10px; font-size:14px; background:white;">
                                    <option value="">-- انتخاب کنید --</option>
                                    <?php foreach ($field['dynamic_configs'] ?? array() as $idx => $config): ?>
                                        <option value="<?php echo $idx; ?>" data-subfields='<?php echo json_encode($config['subfields']); ?>'>
                                            <?php echo esc_html($config['option_label']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                
                                <div class="mervis-dynamic-group-container" data-key="<?php echo esc_attr($key); ?>" 
                                     style="margin-top:12px; display:flex; flex-wrap:wrap; gap:10px;">
                                    <!-- زیرفیلدها توسط جاوااسکریپت ساخته می‌شوند -->
                                </div>
                                
                            <?php elseif ($type == 'select' || $type == 'select_custom'): ?>
                                <label><?php echo esc_html($label); ?></label>
                                <select name="<?php echo esc_attr($key); ?>" class="<?php echo $type == 'select_custom' ? 'mervis-select-custom' : ''; ?>">
                                    <option value="">انتخاب کنید...</option>
                                    <?php foreach ($field['options'] ?? array() as $idx => $opt): ?>
                                        <option value="<?php echo esc_attr($opt); ?>" data-price="<?php echo esc_attr($field['option_prices'][$idx] ?? 0); ?>"><?php echo esc_html($opt); ?></option>
                                    <?php endforeach; ?>
                                    <?php if ($type == 'select_custom'): ?>
                                        <option value="custom">سایر (وارد کنید)</option>
                                    <?php endif; ?>
                                </select>
                                <?php if ($type == 'select_custom'): ?>
                                    <div class="mervis-custom-input" style="display:none;"><input type="text" name="<?php echo esc_attr($key); ?>_custom" placeholder="مقدار دلخواه را وارد کنید..."></div>
                                <?php endif; ?>
                                
                            <?php elseif ($type == 'file'): ?>
                                <label><?php echo esc_html($label); ?></label>
                                <div class="mervis-file-input" onclick="document.getElementById('file_<?php echo esc_attr($key); ?>').click();">📁 برای آپلود فایل کلیک کنید<input type="file" id="file_<?php echo esc_attr($key); ?>" name="<?php echo esc_attr($key); ?>" style="display:none;"></div>
                                <div class="mervis-file-name" id="file-name-display-<?php echo esc_attr($key); ?>"></div>
                            <?php endif; ?>
                            
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="mervis-price-box"><div class="price-label">قیمت نهایی سفارش شما</div><div class="price-value"><span id="mervis-total-price">0</span> تومان</div></div>
                    
                    <!-- دکمه‌ها با رنگ جدید #3C224B -->
                    <?php if ($button_type == 'inquiry'): ?>
                        <button type="button" id="mervis-inquiry-submit" class="mervis-inquiry-btn" style="background:#3C224B;"><?php echo $button_text; ?></button>
                    <?php elseif ($button_type == 'order'): ?>
                        <button type="submit" name="submit_order" class="mervis-order-btn" style="background:#3C224B;"><?php echo $button_text; ?></button>
                    <?php else: ?>
                        <button type="submit" name="add-to-cart" class="mervis-add-to-cart-btn" style="background:#3C224B;"><?php echo $button_text; ?></button>
                    <?php endif; ?>
                </form>
            </div>
        </div>
        <?php if (!empty($sidebar_content)): ?>
            <div class="mervis-sidebar-col"><?php echo apply_filters('the_content', $sidebar_content); ?></div>
        <?php endif; ?>
    </div>
    
    <script>
    window.mervis_product_price = <?php echo floatval($product->get_price()); ?>;
    window.mervis_price_rule = <?php echo json_encode($price_rule ?: ''); ?>;
    
    jQuery(document).ready(function($) {
        // ============================================
        // تابع به‌روزرسانی فیلدهای شرطی
        // ============================================
        function updateConditionalFields() {
            $('.conditional-field').each(function() {
                var $field = $(this);
                var fieldName = $field.data('conditional-field');
                var fieldValue = $field.data('conditional-value');
                
                if (!fieldName || !fieldValue) {
                    return;
                }
                
                // پیدا کردن مقدار انتخاب‌شده در فیلد مرجع
                var $sourceField = $('select[name="' + fieldName + '"], input[name="' + fieldName + '"]:checked');
                var selectedValue = '';
                
                if ($sourceField.is('select')) {
                    selectedValue = $sourceField.val();
                } else if ($sourceField.is(':radio')) {
                    selectedValue = $sourceField.val();
                } else if ($sourceField.is(':checkbox')) {
                    // برای چک‌باکس‌ها، اگر انتخاب شده باشد مقدارش را بگیر
                    if ($sourceField.is(':checked')) {
                        selectedValue = $sourceField.val();
                    }
                } else if ($sourceField.is('input[type="text"]') || $sourceField.is('input[type="number"]')) {
                    selectedValue = $sourceField.val();
                }
                
                // نمایش یا مخفی‌سازی بر اساس مقدار
                if (selectedValue === fieldValue) {
                    $field.show();
                } else {
                    $field.hide();
                }
            });
        }
        
        // ============================================
        // رویدادهای تغییر برای فیلدهای شرطی
        // ============================================
        $(document).on('change keyup', '.mervis-form-modern select, .mervis-form-modern input', function() {
            updateConditionalFields();
        });
        
        // ============================================
        // اجرای اولیه در هنگام لود
        // ============================================
        setTimeout(function() {
            updateConditionalFields();
        }, 200);
    });
    </script>
    
    <script>
    jQuery(document).ready(function($) {
        // ============================================
        // استعلام قیمت
        // ============================================
        $('#mervis-inquiry-submit').on('click', function() {
            var errors = window.mervis_validate_form();
            if (errors && errors.length) {
                alert('❌ لطفا خطاها را برطرف کنید:\n- ' + errors.join('\n- '));
                return;
            }
            
            var formData = {};
            $('#mervis-dynamic-order-form input, #mervis-dynamic-order-form select').each(function() {
                var name = $(this).attr('name');
                var val = $(this).val();
                if (name && val && !['action','nonce'].includes(name)) {
                    formData[name] = val;
                }
            });
            
            var btn = $(this);
            btn.prop('disabled', true).text('در حال ارسال...');
            
            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'mervis_submit_inquiry',
                    nonce: $('#mervis_form_nonce').val(),
                    product_id: $('input[name="product_id"]').val(),
                    form_id: $('input[name="form_id"]').val(),
                    form_data: formData,
                    calculated_price: $('#mervis-calculated-price').val()
                },
                success: function(response) {
                    btn.prop('disabled', false).text('📞 استعلام قیمت');
                    if (response.success) {
                        alert('✅ ' + response.data);
                        $('#mervis-dynamic-order-form')[0].reset();
                        $('.mervis-file-name').text('');
                        if (typeof calculatePrice === 'function') {
                            calculatePrice();
                        }
                    } else {
                        alert('❌ ' + response.data);
                    }
                },
                error: function() {
                    btn.prop('disabled', false).text('📞 استعلام قیمت');
                    alert('❌ خطا در ارتباط با سرور');
                }
            });
        });
        
        // ============================================
        // ثبت سفارش
        // ============================================
        $('#mervis-dynamic-order-form').on('submit', function(e) {
            var buttonType = '<?php echo $button_type; ?>';
            if (buttonType === 'order') {
                e.preventDefault();
                
                var errors = window.mervis_validate_form();
                if (errors && errors.length) {
                    alert('❌ لطفا خطاها را برطرف کنید:\n- ' + errors.join('\n- '));
                    return;
                }
                
                var formData = {};
                $(this).find('input, select').each(function() {
                    var name = $(this).attr('name');
                    var val = $(this).val();
                    if (name && val && !['action','nonce'].includes(name)) {
                        formData[name] = val;
                    }
                });
                
                var btn = $(this).find('button[type="submit"]');
                btn.prop('disabled', true).text('در حال ثبت...');
                
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'mervis_submit_order',
                        nonce: $('#mervis_form_nonce').val(),
                        product_id: $('input[name="product_id"]').val(),
                        form_id: $('input[name="form_id"]').val(),
                        form_data: formData,
                        calculated_price: $('#mervis-calculated-price').val()
                    },
                    success: function(response) {
                        btn.prop('disabled', false).text('✅ ثبت سفارش');
                        if (response.success) {
                            alert('✅ ' + response.data);
                            $('#mervis-dynamic-order-form')[0].reset();
                            $('.mervis-file-name').text('');
                            if (typeof calculatePrice === 'function') {
                                calculatePrice();
                            }
                        } else {
                            alert('❌ ' + response.data);
                        }
                    },
                    error: function() {
                        btn.prop('disabled', false).text('✅ ثبت سفارش');
                        alert('❌ خطا در ارتباط با سرور');
                    }
                });
            }
        });
    });
    </script>
    <?php
}
?>