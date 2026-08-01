/**
 * price-calculator.js - نسخه نهایی و کامل
 */
jQuery(document).ready(function($) {
    'use strict';

    // تابع تبدیل اعداد فارسی به انگلیسی
    function parsePersianFloat(str) {
        if (!str) return NaN;
        var cleanStr = String(str).replace(/[۰-۹]/g, function(w) { return String.fromCharCode(w.charCodeAt(0) - 1728); })
                                  .replace(/[٠-٩]/g, function(w) { return String.fromCharCode(w.charCodeAt(0) - 1584); })
                                  .trim();
        return parseFloat(cleanStr);
    }

    function normalizeText(text) {
        if (typeof text !== 'string') return '';
        return text.replace(/\u200C/g, ' ').replace(/\u00A0/g, ' ').replace(/\s+/g, ' ').trim();
    }

    // ============================================
    // 1. جلوگیری از تایپ حروف در فیلدهای عددی
    // ============================================
    $(document).on('keypress', 'input[type="number"]', function(e) {
        var charCode = e.which ? e.which : e.keyCode;
        // اجازه اعداد (48-57)، نقطه (46)، بک‌اسپیس (8)، تب (9)
        if (charCode > 31 && (charCode < 48 || charCode > 57) && charCode !== 46) {
            e.preventDefault();
        }
    });

    // ============================================
    // 2. تابع جهانی اعتبارسنجی (با لیبل دقیق)
    // ============================================
    window.mervis_validate_form = function() {
        var errors = [];

        $('.mervis-form-modern input, .mervis-form-modern select, .mervis-form-modern textarea').each(function() {
            var $el = $(this);
            if ($el.is(':hidden') || $el.is(':disabled') || $el.closest('.mervis-conditional-field').css('display') === 'none') return;

            var rawVal = $el.val();
            // اگر فیلد عددی است و خالی است، خطا بده
if (!rawVal || String(rawVal).trim() === '') {
    if ($el.attr('type') === 'number') {
        errors.push('لطفا مقدار «' + label + '» را وارد کنید.');
    }
    return;
} // فیلدهای خالی رد می‌شوند (اختیاری)

            var name = $el.attr('name');
            
            // یافتن لیبل دقیق: اولویت با لیبل چسبیده به فیلد (در گروه‌ها)، سپس لیبل والد
            var label = $el.siblings('label').first().text().trim() || 
                        $el.closest('.form-row-modern, .form-full-width, .mervis-dynamic-group-item').find('label').first().text().trim() || 
                        name;

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

        // بررسی انتخابگر سفارشی
        $('.mervis-select-custom').each(function() {
            if ($(this).val() === 'custom') {
                var $customInput = $(this).next('.mervis-custom-input').find('input');
                if ($customInput.length && !$customInput.is(':hidden')) {
                    var rawCustomVal = $customInput.val();
                    if (!rawCustomVal || String(rawCustomVal).trim() === '') return;

                    var customVal = parsePersianFloat(rawCustomVal);
                    var label = $(this).siblings('label').first().text().trim() || 'فیلد سفارشی';
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
    // 3. مدیریت فیلدهای شرطی (پشتیبانی از Value و Text)
    // ============================================
    window.applyConditionalLogic = function() {
        $('.mervis-conditional-field').each(function() {
            var $row = $(this);
            var parentKey = String($row.data('conditional-parent') || '').trim();
            var targetValue = normalizeText($row.data('conditional-value'));
            
            if (!parentKey || !targetValue) {
                $row.css('display', '').find('input, select, textarea').prop('disabled', false);
                return;
            }
            
            var $parentInputs = $('[name="' + parentKey + '"], [name="' + parentKey + '[]"]');
            var isMatch = false;
            
            if ($parentInputs.length > 0) {
                var $checked = $parentInputs.filter(':checked');
                var currentVal = $checked.length > 0 ? $checked.val() : $parentInputs.first().val();
                var currentText = '';
                
                // اگر سلکت است، متن گزینه انتخاب شده را هم بگیر
                if ($parentInputs.is('select')) {
                    currentText = $parentInputs.find('option:selected').text();
                }

                if (normalizeText(currentVal) === targetValue || normalizeText(currentText) === targetValue) {
                    isMatch = true;
                }
            }
            
            if (isMatch) {
                $row.slideDown(200).find('input, select, textarea').prop('disabled', false);
            } else {
                $row.slideUp(200).find('input, select, textarea').prop('disabled', true).val('');
            }
        });
    };

    // محاسبه قیمت
    function calculatePrice() {
        var total = parseFloat(window.mervis_product_price) || 0;
        var formValues = {};
        
        $('.mervis-form-modern input, .mervis-form-modern select').each(function() {
            var $this = $(this);
            if ($this.is(':hidden') || $this.css('display') === 'none') return;
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
            for (var key in formValues) formula = formula.replace(new RegExp('\\{' + key + '\\}', 'g'), formValues[key]);
            formula = formula.replace(/\{[^}]+\}/g, '0');
            try {
                var safeFormula = formula.replace(/[^0-9+\-*/().]/g, '');
                if (safeFormula !== '') total = eval(safeFormula);
            } catch(e) {}
        }
        
        $('#mervis-total-price').text(Math.max(0, Math.floor(total)).toLocaleString());
        $('#mervis-calculated-price').val(Math.max(0, Math.floor(total)));
    }

    // رویدادها
    window.applyConditionalLogic();
    calculatePrice();
    $(document).on('change keyup', '.mervis-form-modern input, .mervis-form-modern select', function() {
        window.applyConditionalLogic();
        calculatePrice();
    });
});