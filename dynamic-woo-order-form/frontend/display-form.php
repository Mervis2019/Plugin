<?php
function mervis_display_order_form_tab_content() {
    global $product;
    $product_id = $product->get_id();
    $form_id = $GLOBALS['mervis_current_form_id'] ?? null;
    
    if (!$form_id) $form_id = mervis_get_form_id_for_product($product_id);
    
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
    
    $button_text = $button_type == 'inquiry' ? '📞 استعلام قیمت' : ($button_type == 'order' ? '✅ ثبت سفارش' : '➕ افزودن به سبد خرید');
    ?>
    
    <style>
        /* ============================================
           استایل‌های پایه فرم
           ============================================ */
        .mervis-layout-2cols { display:flex; gap:30px; flex-wrap:wrap; }
        .mervis-form-col { flex:2; min-width:250px; }
        .mervis-sidebar-col { flex:1; min-width:200px; background:#f8fafc; padding:20px; border-radius:16px; position:sticky; top:20px; align-self:flex-start; }
        @media(max-width:768px) { 
            .mervis-layout-2cols { flex-direction:column; } 
            .mervis-sidebar-col { position:static; order:-1; } 
        }
        
        .mervis-form-modern .form-grid-4cols { display:grid; grid-template-columns:repeat(4,1fr); gap:20px; }
        .mervis-form-modern .form-full-width { grid-column:span 4; }
        @media(min-width:481px) and (max-width:768px) { 
            .mervis-form-modern .form-grid-4cols { grid-template-columns:repeat(2,1fr); } 
            .mervis-form-modern .form-full-width { grid-column:span 2; } 
        }
        @media(max-width:480px) { 
            .mervis-form-modern .form-grid-4cols { display:block; } 
        }
        
        .mervis-form-modern .form-row-modern { margin-bottom:20px; }
        .mervis-form-modern .form-row-modern label { display:block; font-weight:600; margin-bottom:8px; color:#1e293b; font-size:14px; }
        .mervis-form-modern input[type="text"], 
        .mervis-form-modern input[type="number"], 
        .mervis-form-modern input[type="tel"], 
        .mervis-form-modern select { 
            width:100%; padding:10px 14px; border:1.5px solid #e2e8f0; border-radius:10px; 
            font-size:14px; background:white; box-sizing:border-box; 
        }
        .mervis-form-modern input:focus, .mervis-form-modern select:focus { 
            border-color:#3b82f6; outline:none; box-shadow:0 0 0 3px rgba(59,130,246,0.1); 
        }
        
        /* نمایش افقی گزینه‌ها */
        .mervis-horizontal-options { display:flex !important; flex-direction:row !important; flex-wrap:wrap !important; gap:10px !important; }
        .mervis-horizontal-options .mervis-option-label { 
            display:inline-flex !important; align-items:center; gap:8px; cursor:pointer; 
            background:white; padding:8px 18px; border-radius:50px; border:1.5px solid #e2e8f0; 
        }
        
        /* فایل آپلود */
        .mervis-file-input { border:2px dashed #cbd5e1; border-radius:16px; padding:20px; text-align:center; background:#fff; cursor:pointer; }
        .mervis-file-name { margin-top:10px; font-size:12px; color:#10b981; text-align:center; }
        
        /* جعبه قیمت */
        .mervis-price-box { background:linear-gradient(135deg,#1e293b,#0f172a); color:#fff; padding:20px; border-radius:16px; text-align:center; margin:25px 0; }
        .mervis-price-box .price-value { font-size:32px; font-weight:bold; }
        
        /* دکمه‌ها */
        .mervis-btn { width:100%; border:none; padding:14px; font-size:16px; font-weight:600; border-radius:40px; cursor:pointer; margin-top:10px; color:white; background:#3C224B; }
        
        /* گروه‌ها */
        .mervis-group-row { display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:15px; }
        .mervis-group-item label { font-size:12px; font-weight:normal; color:#64748b; margin-bottom:4px; display:block; }
        
    /* ============================================
       ✅ استایل حیاتی برای فیلدهای شرطی - نسخه ضدگلوله
       ============================================ */
       .mervis-conditional-field {
        display: none !important;
        visibility: hidden !important;
        opacity: 0 !important;
        height: 0 !important;
        overflow: hidden !important;
        margin: 0 !important;
        padding: 0 !important;
        position: absolute !important;
        left: -9999px !important;
    }
    
    .mervis-conditional-field.mervis-visible {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
        height: auto !important;
        overflow: visible !important;
        position: static !important;
        left: auto !important;
    }
    
    .mervis-conditional-field.form-full-width.mervis-visible {
        display: block !important;
    }
    </style>
    
    <div class="mervis-layout-2cols">
        <div class="mervis-form-col">
            <div class="mervis-form-modern">
                <form id="mervis-dynamic-order-form" method="post" enctype="multipart/form-data">
                    <?php wp_nonce_field('mervis_add_to_cart', 'mervis_form_nonce'); ?>
                    <input type="hidden" name="product_id" value="<?php echo esc_attr($product_id); ?>">
                    <input type="hidden" name="form_id" value="<?php echo esc_attr($form_id); ?>">
                    <input type="hidden" id="mervis-calculated-price" name="calculated_price" value="0">
                    
                    <div class="form-grid-4cols">
                        <?php foreach ($fields as $field): 
                            $key = $field['key'] ?? sanitize_title($field['label']);
                            $label = $field['label'] ?? '';
                            $type = $field['type'] ?? 'text';
                            $is_full_width = in_array($type, ['file', 'group', 'dynamic_group']);
                            
                            // منطق شرطی
                            $cond_parent = $field['conditional_parent'] ?? '';
                            $cond_value = $field['conditional_value'] ?? '';
                            $is_conditional = !empty($cond_parent) && !empty($cond_value);
                            
                            $cond_class = $is_conditional ? 'mervis-conditional-field' : '';
                            $cond_attrs = $is_conditional ? 'data-conditional-parent="' . esc_attr($cond_parent) . '" data-conditional-value="' . esc_attr($cond_value) . '"' : '';
                            $row_class = $is_full_width ? 'form-full-width' : 'form-row-modern';
                        ?>
                            <div class="<?php echo esc_attr($row_class . ' ' . $cond_class); ?>" <?php echo $cond_attrs; ?> <?php if ($is_conditional): ?>style="display:none;"<?php endif; ?>>
                            
                            <?php if ($type == 'text'): ?>
                                <label><?php echo esc_html($label); ?></label>
                                <input type="text" name="<?php echo esc_attr($key); ?>" data-price-factor="<?php echo esc_attr($field['price_factor'] ?? 1); ?>" placeholder="مقدار را وارد کنید...">
                                
                            <?php elseif ($type == 'number'): ?>
                                <label><?php echo esc_html($label); ?></label>
                                <input type="number" name="<?php echo esc_attr($key); ?>" data-price-factor="<?php echo esc_attr($field['price_factor'] ?? 1); ?>" 
                                    <?php if (!empty($field['min_value'])): ?>min="<?php echo esc_attr($field['min_value']); ?>" data-min="<?php echo esc_attr($field['min_value']); ?>"<?php endif; ?> 
                                    <?php if (!empty($field['max_value'])): ?>max="<?php echo esc_attr($field['max_value']); ?>" data-max="<?php echo esc_attr($field['max_value']); ?>"<?php endif; ?> 
                                    placeholder="عدد را وارد کنید...">
                                
                            <?php elseif ($type == 'phone'): ?>
                                <label><?php echo esc_html($label); ?></label>
                                <input type="tel" name="<?php echo esc_attr($key); ?>" class="mervis-phone-input" placeholder="مثال: 09123456789" pattern="[0-9]{10,11}" maxlength="11">
                                
                            <?php elseif ($type == 'group'): ?>
                                <label class="mervis-group-label"><?php echo esc_html($label); ?></label>
                                <div class="mervis-group-row">
                                    <?php foreach ($field['subfields'] ?? array() as $sub): 
                                        $sk = $sub['key'] ?? sanitize_title($sub['label']);
                                        $stype = $sub['type'] ?? 'number';
                                        $s_label = $sub['label'] ?? '';
                                    ?>
                                        <div class="mervis-group-item">
                                            <label><?php echo esc_html($s_label); ?></label>
                                            <?php if ($stype == 'number'): ?>
                                                <input type="number" name="<?php echo esc_attr($key . '_' . $sk); ?>" 
                                                    <?php if (!empty($sub['min_value'])): ?>min="<?php echo esc_attr($sub['min_value']); ?>" data-min="<?php echo esc_attr($sub['min_value']); ?>"<?php endif; ?>
                                                    <?php if (!empty($sub['max_value'])): ?>max="<?php echo esc_attr($sub['max_value']); ?>" data-max="<?php echo esc_attr($sub['max_value']); ?>"<?php endif; ?>>
                                            <?php elseif ($stype == 'select'): ?>
                                                <select name="<?php echo esc_attr($key . '_' . $sk); ?>">
                                                    <option value="">انتخاب کنید...</option>
                                                    <?php foreach ($sub['options'] ?? array() as $o_idx => $opt): ?>
                                                        <option value="<?php echo esc_attr($opt); ?>" data-price="<?php echo esc_attr($sub['option_prices'][$o_idx] ?? 0); ?>"><?php echo esc_html($opt); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            <?php else: ?>
                                                <input type="text" name="<?php echo esc_attr($key . '_' . $sk); ?>">
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                            <?php elseif ($type == 'radio'): ?>
                                <label><?php echo esc_html($label); ?></label>
                                <div class="mervis-options-group <?php echo !empty($field['display_horizontal']) ? 'mervis-horizontal-options' : ''; ?>">
                                    <?php foreach ($field['options'] ?? array() as $idx => $opt): ?>
                                        <label class="mervis-option-label">
                                            <input type="radio" name="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($opt); ?>" data-price="<?php echo esc_attr($field['option_prices'][$idx] ?? 0); ?>">
                                            <span><?php echo esc_html($opt); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                                
                            <?php elseif ($type == 'checkbox'): ?>
                                <label><?php echo esc_html($label); ?></label>
                                <div class="mervis-options-group <?php echo !empty($field['display_horizontal']) ? 'mervis-horizontal-options' : ''; ?>">
                                    <?php foreach ($field['options'] ?? array() as $idx => $opt): ?>
                                        <label class="mervis-option-label">
                                            <input type="checkbox" name="<?php echo esc_attr($key); ?>[]" value="<?php echo esc_attr($opt); ?>" data-price="<?php echo esc_attr($field['option_prices'][$idx] ?? 0); ?>">
                                            <span><?php echo esc_html($opt); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>

                            <?php elseif ($type == 'dynamic_group'): ?>
                                <label><?php echo esc_html($label); ?></label>
                                <select class="mervis-dynamic-select" data-key="<?php echo esc_attr($key); ?>">
                                    <option value="">-- انتخاب کنید --</option>
                                    <?php foreach ($field['dynamic_configs'] ?? array() as $idx => $config): ?>
                                        <option value="<?php echo esc_attr($idx); ?>" data-subfields='<?php echo json_encode($config['subfields'] ?? [], JSON_UNESCAPED_UNICODE); ?>'>
                                            <?php echo esc_html($config['option_label'] ?? ''); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="mervis-dynamic-group-container" data-key="<?php echo esc_attr($key); ?>" style="margin-top:15px; display:flex; flex-wrap:wrap; gap:15px;"></div>
                                
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
                                    <div class="mervis-custom-input" style="display:none; margin-top:10px;">
                                        <input type="text" name="<?php echo esc_attr($key); ?>_custom" placeholder="مقدار دلخواه را وارد کنید...">
                                    </div>
                                <?php endif; ?>
                                
                            <?php elseif ($type == 'file'): ?>
                                <label><?php echo esc_html($label); ?></label>
                                <div class="mervis-file-input" onclick="document.getElementById('file_<?php echo esc_attr($key); ?>').click();">
                                    📁 برای آپلود فایل کلیک کنید
                                    <input type="file" id="file_<?php echo esc_attr($key); ?>" name="<?php echo esc_attr($key); ?>" style="display:none;" onchange="document.getElementById('file-name-display-<?php echo esc_attr($key); ?>').innerText = this.files[0] ? this.files[0].name : '';">
                                </div>
                                <div class="mervis-file-name" id="file-name-display-<?php echo esc_attr($key); ?>"></div>
                            <?php endif; ?>
                            
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="mervis-price-box">
                        <div class="price-label">قیمت نهایی سفارش شما</div>
                        <div class="price-value"><span id="mervis-total-price">0</span> تومان</div>
                    </div>
                    
                    <?php if ($button_type == 'inquiry'): ?>
                        <button type="button" id="mervis-inquiry-submit" class="mervis-btn"><?php echo esc_html($button_text); ?></button>
                    <?php elseif ($button_type == 'order'): ?>
                        <button type="submit" name="submit_order" class="mervis-btn"><?php echo esc_html($button_text); ?></button>
                    <?php else: ?>
                        <button type="submit" name="add-to-cart" class="mervis-btn"><?php echo esc_html($button_text); ?></button>
                    <?php endif; ?>
                </form>
            </div>
        </div>
        
        <?php if (!empty($sidebar_content)): ?>
            <div class="mervis-sidebar-col"><?php echo apply_filters('the_content', $sidebar_content); ?></div>
        <?php endif; ?>
    </div>
    
    <script>
    (function($) {
        'use strict';
        
        window.mervis_product_price = <?php echo floatval($product->get_price() ?: 0); ?>;
        window.mervis_price_rule = <?php echo json_encode($price_rule ?: ''); ?>;
        
        // ============================================
        // توابع کمکی
        // ============================================
        function parsePersianFloat(str) {
            if (!str) return NaN;
            return parseFloat(String(str)
                .replace(/[۰-۹]/g, function(w) { return String.fromCharCode(w.charCodeAt(0) - 1728); })
                .replace(/[٠-٩]/g, function(w) { return String.fromCharCode(w.charCodeAt(0) - 1584); })
                .trim());
        }
        
        function normalizeText(text) {
            if (typeof text !== 'string') return '';
            return text.replace(/\u200C/g, ' ').replace(/\u00A0/g, ' ').replace(/\s+/g, ' ').trim();
        }
        
        // ============================================
        // 1. جلوگیری از تایپ حروف در فیلد عددی
        // ============================================
        $(document).on('keypress', 'input[type="number"]', function(e) {
            var charCode = e.which || e.keyCode;
            if (charCode > 31 && (charCode < 48 || charCode > 57) && charCode !== 46) {
                e.preventDefault();
            }
        });
        
        // ============================================
        // 2. ✅ مدیریت فیلدهای شرطی (با کلاس CSS - ضد قالب)
        // ============================================
        window.applyConditionalLogic = function() {
    $('.mervis-conditional-field').each(function() {
        var $row = $(this);
        var parentKey = String($row.data('conditional-parent') || '').trim();
        var targetValue = normalizeText($row.data('conditional-value'));
        
        // اگر شرطی نیست، نمایش بده
        if (!parentKey || !targetValue) {
            $row.addClass('mervis-visible').removeAttr('style').find('input, select, textarea').prop('disabled', false);
            return;
        }
        
        var $parentInputs = $('[name="' + parentKey + '"], [name="' + parentKey + '[]"]');
        var isMatch = false;
        
        if ($parentInputs.length > 0) {
            var $checked = $parentInputs.filter(':checked');
            var currentVal = $checked.length > 0 ? $checked.val() : $parentInputs.first().val();
            var currentText = $parentInputs.is('select') ? $parentInputs.find('option:selected').text() : '';
            
            if (normalizeText(currentVal) === targetValue || normalizeText(currentText) === targetValue) {
                isMatch = true;
            }
        }
        
        if (isMatch) {
            $row.addClass('mervis-visible').removeAttr('style').find('input, select, textarea').prop('disabled', false);
        } else {
            $row.removeClass('mervis-visible').attr('style', 'display:none !important;').find('input, select, textarea').prop('disabled', true).val('');
            $row.find('input[type="checkbox"], input[type="radio"]').prop('checked', false);
        }
    });
};
        
        // ============================================
        // 3. اعتبارسنجی هوشمند
        // ============================================
        window.mervis_validate_form = function() {
            var errors = [];
            
            $('.mervis-form-modern input, .mervis-form-modern select, .mervis-form-modern textarea').each(function() {
                var $el = $(this);
                
                // نادیده گرفتن فیلدهای مخفی (با بررسی کلاس و visibility)
                if ($el.is(':hidden') || $el.is(':disabled')) return;
                if ($el.closest('.mervis-conditional-field').length && !$el.closest('.mervis-conditional-field').hasClass('mervis-visible')) return;
                
                var rawVal = $el.val();
                if (!rawVal || String(rawVal).trim() === '') return; // فیلدهای خالی = اختیاری
                
                var name = $el.attr('name');
                var label = $el.closest('.form-row-modern, .form-full-width, .mervis-group-item').find('label').first().text().trim() || name;
                var val = parsePersianFloat(rawVal);
                
                // بررسی Min/Max
                if ($el.attr('type') === 'number' || $el.data('min') !== undefined || $el.data('max') !== undefined) {
                    var min = parseFloat($el.data('min'));
                    var max = parseFloat($el.data('max'));
                    
                    if (!isNaN(val)) {
                        if (!isNaN(min) && val < min) errors.push('مقدار «' + label + '» نباید کمتر از ' + min + ' باشد.');
                        if (!isNaN(max) && val > max) errors.push('مقدار «' + label + '» نباید بیشتر از ' + max + ' باشد.');
                    }
                }
            });
            
            // انتخابگر سفارشی
            $('.mervis-select-custom').each(function() {
                if ($(this).val() === 'custom') {
                    var $customInput = $(this).next('.mervis-custom-input').find('input');
                    if ($customInput.length && !$customInput.is(':hidden')) {
                        var rawCustomVal = $customInput.val();
                        if (!rawCustomVal || String(rawCustomVal).trim() === '') return;
                        
                        var customVal = parsePersianFloat(rawCustomVal);
                        var label = $(this).closest('.form-row-modern').find('label').first().text().trim() || 'فیلد سفارشی';
                        var maxOption = -Infinity;
                        
                        $(this).find('option').each(function() {
                            var optVal = parsePersianFloat($(this).val());
                            if (!isNaN(optVal) && optVal > maxOption) maxOption = optVal;
                        });
                        
                        if (maxOption !== -Infinity && customVal < maxOption) {
                            errors.push('مقدار «' + label + '» باید حداقل ' + maxOption + ' باشد.');
                        }
                    }
                }
            });
            
            return errors;
        };
        
        // ============================================
        // 4. محاسبه قیمت
        // ============================================
        function calculatePrice() {
            var total = parseFloat(window.mervis_product_price) || 0;
            var formValues = {};
            
            $('.mervis-form-modern input, .mervis-form-modern select').each(function() {
                var $this = $(this);
                if ($this.is(':hidden') || $this.closest('.mervis-conditional-field').length && !$this.closest('.mervis-conditional-field').hasClass('mervis-visible')) return;
                
                var name = $this.attr('name');
                var val = $this.val();
                if (!val) return;
                
                var numericVal = 0;
                if ($this.is(':radio') && $this.is(':checked')) numericVal = parseFloat($this.data('price')) || 0;
                else if ($this.is('input[type="checkbox"]') && $this.is(':checked')) numericVal = parseFloat($this.data('price')) || 0;
                else if ($this.is('select')) numericVal = parseFloat($this.find('option:selected').data('price')) || 0;
                else if ($this.is('input[type="number"]')) numericVal = (parsePersianFloat(val) || 0) * (parseFloat($this.data('price-factor')) || 1);
                
                if (name) formValues[name] = numericVal;
            });
            
            var priceRule = window.mervis_price_rule;
            if (priceRule && priceRule.trim() !== '') {
                var formula = priceRule;
                for (var key in formValues) {
                    formula = formula.replace(new RegExp('\\{' + key + '\\}', 'g'), formValues[key]);
                }
                formula = formula.replace(/\{[^}]+\}/g, '0');
                try {
                    var safeFormula = formula.replace(/[^0-9+\-*/().]/g, '');
                    if (safeFormula !== '') total = eval(safeFormula);
                } catch(e) {}
            }
            
            var finalPrice = Math.max(0, Math.floor(total));
            $('#mervis-total-price').text(finalPrice.toLocaleString());
            $('#mervis-calculated-price').val(finalPrice);
        }
        
        // ============================================
        // 5. گروه پویا
        // ============================================
        $(document).on('change', '.mervis-dynamic-select', function() {
            var $select = $(this);
            var key = $select.data('key');
            var $container = $select.next('.mervis-dynamic-group-container');
            var subfields = $select.find('option:selected').data('subfields');
            
            $container.empty();
            if (subfields && Array.isArray(subfields)) {
                $.each(subfields, function(idx, sub) {
                    var inputName = key + '_' + (sub.key || 'field_' + idx);
                    var minAttr = (sub.min_value !== undefined && sub.min_value !== null && sub.min_value !== '') ? 'min="' + sub.min_value + '" data-min="' + sub.min_value + '"' : '';
                    var maxAttr = (sub.max_value !== undefined && sub.max_value !== null && sub.max_value !== '') ? 'max="' + sub.max_value + '" data-max="' + sub.max_value + '"' : '';
                    
                    var html = '<div class="mervis-group-item" style="flex:1; min-width:150px;">';
                    html += '<label>' + (sub.label || '') + '</label>';
                    html += '<input type="' + (sub.type === 'number' ? 'number' : 'text') + '" name="' + inputName + '" placeholder="' + (sub.label || '') + '" ' + minAttr + ' ' + maxAttr + ' style="width:100%; padding:10px; border:1.5px solid #e2e8f0; border-radius:10px;">';
                    html += '</div>';
                    $container.append(html);
                });
            }
            calculatePrice();
        });
        
        // انتخابگر سفارشی
        $(document).on('change', '.mervis-select-custom', function() {
            var $custom = $(this).next('.mervis-custom-input');
            if ($(this).val() === 'custom') $custom.show();
            else $custom.hide().find('input').val('');
        });
        
        // رویدادهای عمومی
        $(document).on('change keyup', '.mervis-form-modern input, .mervis-form-modern select', function() {
            window.applyConditionalLogic();
            calculatePrice();
        });
        
        // ============================================
        // 6. جمع‌آوری داده و ارسال
        // ============================================
        function getFormData($form) {
            var formData = {};
            $form.find('input, select, textarea').each(function() {
                var name = $(this).attr('name');
                if (!name || $(this).is(':disabled')) return;
                if ($(this).closest('.mervis-conditional-field').length && !$(this).closest('.mervis-conditional-field').hasClass('mervis-visible')) return;
                
                if ($(this).is(':checkbox')) {
                    if ($(this).is(':checked')) {
                        if (!formData[name]) formData[name] = [];
                        formData[name].push($(this).val());
                    }
                } else if ($(this).is(':radio')) {
                    if ($(this).is(':checked')) formData[name] = $(this).val();
                } else {
                    formData[name] = $(this).val();
                }
            });
            return formData;
        }
        
        $('#mervis-inquiry-submit, button[name="submit_order"]').on('click', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var $form = $('#mervis-dynamic-order-form');
            
            var errors = window.mervis_validate_form();
            if (errors && errors.length > 0) {
                alert('❌ لطفا خطاها را برطرف کنید:\n\n- ' + errors.join('\n- '));
                return;
            }
            
            var formData = getFormData($form);
            var actionName = $btn.attr('id') === 'mervis-inquiry-submit' ? 'mervis_submit_inquiry' : 'mervis_submit_order';
            var originalText = $btn.text();
            
            $btn.prop('disabled', true).text('در حال ارسال...');
            
            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: actionName,
                    nonce: $('#mervis_form_nonce').val(),
                    product_id: $('input[name="product_id"]').val(),
                    form_id: $('input[name="form_id"]').val(),
                    form_data: formData,
                    calculated_price: $('#mervis-calculated-price').val()
                },
                success: function(response) {
                    $btn.prop('disabled', false).text(originalText);
                    if (response.success) {
                        alert('✅ ' + response.data);
                        $form[0].reset();
                        $('.mervis-file-name').text('');
                        $('.mervis-dynamic-group-container').empty();
                        $('.mervis-custom-input').hide();
                        window.applyConditionalLogic();
                        calculatePrice();
                    } else {
                        alert('❌ ' + response.data);
                    }
                },
                error: function() {
                    $btn.prop('disabled', false).text(originalText);
                    alert('❌ خطا در ارتباط با سرور.');
                }
            });
        });
        
        // اجرای اولیه
        $(document).ready(function() {
            // اجرای فوری
            window.applyConditionalLogic();
            calculatePrice();
            
            // اجرای مجدد با تأخیر برای اطمینان
            setTimeout(function() {
                window.applyConditionalLogic();
                calculatePrice();
            }, 100);
            
            setTimeout(function() {
                window.applyConditionalLogic();
            }, 500);
        });
        
    })(jQuery);
    </script>
    <?php
}