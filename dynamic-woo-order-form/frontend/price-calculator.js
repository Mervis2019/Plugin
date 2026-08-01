/**
 * price-calculator.js
 * محاسبه قیمت فرم سفارش داینامیک - نسخه نهایی
 * (فقط کدهای مربوط به فرانت‌اند)
 */

jQuery(document).ready(function($) {
    'use strict';
    
    console.log('✅ price-calculator.js بارگذاری شد!');
    
    // ============================================
    // تابع اصلی محاسبه قیمت
    // ============================================
    function calculatePrice() {
        var total = parseFloat(window.mervis_product_price) || 0;
        var formValues = {};
        
        // جمع‌آوری مقادیر
        $('.mervis-form-modern input, .mervis-form-modern select').each(function() {
            var $this = $(this);
            var name = $this.attr('name');
            var val = $this.val();
            
            if (!$this.is(':visible')) return;
            if ($this.is(':radio') && !$this.is(':checked')) return;
            if ($this.is('select') && !val) return;
            if (val === '' || val === undefined) return;
            
            var numericVal = 0;
            
            // دکمه‌های رادیویی
            if ($this.is(':radio')) {
                numericVal = parseFloat($this.data('price')) || 0;
            }
            // چک‌باکس‌ها
            else if ($this.is('input[type="checkbox"]')) {
                if ($this.is(':checked')) {
                    var totalCheckbox = 0;
                    $('input[name="' + name + '"]:checked').each(function() {
                        totalCheckbox += parseFloat($(this).data('price')) || 0;
                    });
                    numericVal = totalCheckbox;
                }
            }
            // سلکت باکس
            else if ($this.is('select')) {
                numericVal = parseFloat($this.find('option:selected').data('price')) || 0;
            }
            // فیلد عددی
            else if ($this.is('input[type="number"]')) {
                var num = parseFloat(val) || 0;
                var factor = parseFloat($this.data('price-factor')) || 1;
                numericVal = num * factor;
            }
            // فیلد متنی
            else if ($this.is('input[type="text"]') && !$this.hasClass('mervis-phone-input')) {
                var num = parseFloat(val) || 0;
                var factor = parseFloat($this.data('price-factor')) || 1;
                numericVal = num * factor;
            }
            
            if (name) {
                formValues[name] = numericVal;
            }
        });
        
        // اعمال قانون قیمت
        var priceRule = window.mervis_price_rule;
        if (priceRule && priceRule.trim() !== '') {
            var formula = priceRule;
            for (var key in formValues) {
                var regex = new RegExp('\\{' + key + '\\}', 'g');
                formula = formula.replace(regex, formValues[key]);
            }
            formula = formula.replace(/\{[^}]+\}/g, '0');
            
            try {
                var safeFormula = formula.replace(/[^0-9+\-*/().]/g, '');
                if (safeFormula !== '') {
                    var calculated = eval(safeFormula);
                    if (!isNaN(calculated) && isFinite(calculated)) {
                        total = calculated;
                    }
                }
            } catch(e) {
                console.log('خطا در محاسبه فرمول:', e);
            }
        }
        
        var finalPrice = Math.max(0, Math.floor(total));
        $('#mervis-total-price').text(finalPrice.toLocaleString());
        $('#mervis-calculated-price').val(finalPrice);
    }
    
    // ============================================
    // مدیریت گروه پویا در فرانت‌اند
    // ============================================
    function handleDynamicGroup($select) {
        console.log('🔄 handleDynamicGroup اجرا شد!');
        
        var key = $select.data('key');
        var $container = $('.mervis-dynamic-group-container[data-key="' + key + '"]');
        var selectedIndex = $select.val();
        
        console.log('  کلید:', key);
        console.log('  مقدار انتخاب شده:', selectedIndex);
        console.log('  Container پیدا شد?', $container.length > 0);
        
        $container.empty();
        
        if (selectedIndex === '' || selectedIndex === undefined) {
            console.log('  ⚠️ هیچ گزینه‌ای انتخاب نشده');
            return;
        }
        
        // پیدا کردن زیرفیلدها از option انتخاب شده
        var subfields = [];
        $select.find('option:selected').each(function() {
            var data = $(this).data('subfields');
            console.log('  داده subfields از گزینه:', data);
            if (data && typeof data === 'object') {
                subfields = data;
            }
        });
        
        if (!subfields || subfields.length === 0) {
            console.log('  ⚠️ هیچ زیرفیلدی برای این گزینه وجود ندارد');
            return;
        }
        
        console.log('  تعداد زیرفیلدها:', subfields.length);
        
        // ساخت زیرفیلدها
        var html = '';
        $.each(subfields, function(idx, sub) {
            var inputName = key + '_' + (sub.key || 'field_' + (idx + 1));
            var inputType = sub.type === 'number' ? 'number' : 'text';
            var placeholder = sub.label || 'فیلد ' + (idx + 1);
            
            html += '<div class="mervis-dynamic-group-item" style="flex:1; min-width:80px;">';
            html += '<input type="' + inputType + '" name="' + inputName + '" ';
            html += 'placeholder="' + placeholder + '" ';
            html += 'data-label="' + placeholder + '" ';
            html += 'data-price-factor="1" ';
            html += 'class="mervis-dynamic-input" ';
            html += 'style="width:100%; padding:10px 14px; border:1.5px solid #e2e8f0; border-radius:10px; font-size:14px; background:white;">';
            html += '</div>';
        });
        
        $container.html(html);
        console.log('  ✅ زیرفیلدها ساخته شدند!');
    }
    
    // ============================================
    // رویداد تغییر برای گروه پویا
    // ============================================
    $(document).on('change', '.mervis-dynamic-select', function() {
        console.log('🔄 رویداد change روی .mervis-dynamic-select اجرا شد!');
        handleDynamicGroup($(this));
        calculatePrice();
    });
    
    // ============================================
    // رویدادهای کلیدی برای محاسبه خودکار
    // ============================================
    $(document).on('change keyup', '.mervis-form-modern input, .mervis-form-modern select', function() {
        // select_custom
        if ($(this).hasClass('mervis-select-custom')) {
            var $custom = $(this).closest('.form-row-modern, .form-full-width').find('.mervis-custom-input');
            if ($(this).val() === 'custom') {
                $custom.show();
            } else {
                $custom.hide().find('input').val('');
            }
        }
        calculatePrice();
    });
    
    // ============================================
    // آپلود فایل
    // ============================================
    $(document).on('change', '.mervis-file-input input[type="file"]', function() {
        var fileName = $(this).val().split('\\').pop();
        $(this).closest('.form-full-width').find('.mervis-file-name').text('فایل: ' + fileName);
    });
    
    // ============================================
    // مقداردهی اولیه در هنگام لود صفحه
    // ============================================
    setTimeout(function() {
        console.log('🔍 مقداردهی اولیه گروه‌های پویا...');
        $('.mervis-dynamic-select').each(function() {
            handleDynamicGroup($(this));
        });
        calculatePrice();
        console.log('✅ مقداردهی اولیه کامل شد!');
    }, 500);
    
    // ============================================
    // اعتبارسنجی
    // ============================================
    window.mervis_validate_form = function() {
        var errors = [];
        $('.mervis-form-modern .form-row-modern, .mervis-form-modern .form-full-width').each(function() {
            var $row = $(this);
            var $label = $row.find('label');
            var $input = $row.find('input, select');
            
            if ($input.attr('type') === 'file') return;
            
            // رادیویی
            if ($row.find('.mervis-radio-group').length) {
                if (!$row.find('input:checked').length) {
                    errors.push('لطفا "' + $label.text().trim() + '" را انتخاب کنید');
                    $row.css({'border':'2px solid #ef4444','background':'#fef2f2','padding':'10px','border-radius':'8px'});
                } else {
                    $row.css({'border':'none','background':'transparent','padding':'0'});
                }
                return;
            }
            
            // سلکت
            if ($input.is('select')) {
                if (!$input.val() || $input.val() === '') {
                    errors.push('لطفا "' + $label.text().trim() + '" را انتخاب کنید');
                    $input.css({'border-color':'#ef4444','background':'#fef2f2'});
                } else {
                    $input.css({'border-color':'#e2e8f0','background':'white'});
                }
                return;
            }
            
            // تلفن
            if ($input.is('.mervis-phone-input')) {
                var phone = $input.val().trim();
                var phoneRegex = /^[0-9]{10,11}$/;
                if (!phoneRegex.test(phone)) {
                    errors.push('لطفا شماره تماس معتبر وارد کنید (10 یا 11 رقم)');
                    $input.css({'border-color':'#ef4444','background':'#fef2f2'});
                } else {
                    $input.css({'border-color':'#e2e8f0','background':'white'});
                }
                return;
            }
            
            // متنی و عددی
            if ($input.is('input[type="text"]') || $input.is('input[type="number"]')) {
                var value = $input.val().trim();
                if (value === '' || value === '0') {
                    var labelText = $label.text().trim() || $input.attr('placeholder') || 'فیلد';
                    errors.push('لطفا "' + labelText + '" را وارد کنید');
                    $input.css({'border-color':'#ef4444','background':'#fef2f2'});
                } else {
                    $input.css({'border-color':'#e2e8f0','background':'white'});
                }
            }
        });
        return errors;
    };
    
 
function updateConditionalFields() {
    $('.conditional-field').each(function() {
        var $field = $(this);
        var fieldName = $field.data('conditional-field');
        var fieldValue = $field.data('conditional-value');
        
        // پیدا کردن مقدار انتخاب‌شده در فیلد مرجع
        var $sourceField = $('select[name="' + fieldName + '"], input[name="' + fieldName + '"]:checked');
        var selectedValue = '';
        if ($sourceField.is('select')) {
            selectedValue = $sourceField.val();
        } else if ($sourceField.is(':radio') || $sourceField.is(':checkbox')) {
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

// صدا زدن تابع در رویدادهای تغییر و بارگذاری صفحه
$(document).on('change keyup', '.mervis-form-modern select, .mervis-form-modern input', function() {
    updateConditionalFields();
    calculatePrice(); // محاسبه مجدد قیمت
});

// اجرای اولیه در هنگام لود
$(document).ready(function() {
    updateConditionalFields();
});


    // ============================================
    // نمایش تعداد المان‌ها در کنسول (برای دیباگ)
    // ============================================
    console.log('🔍 المان‌های موجود در صفحه:');
    console.log('  .mervis-dynamic-select:', $('.mervis-dynamic-select').length);
    console.log('  .mervis-dynamic-group-container:', $('.mervis-dynamic-group-container').length);
    console.log('  .mervis-form-modern:', $('.mervis-form-modern').length);
});