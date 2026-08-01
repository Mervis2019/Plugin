/**
 * price-calculator.js
 * Dynamic order form price calculator
 */
(function($) {
    'use strict';

    var FORM_SELECTOR = '.mervis-form-modern';
    var FIELD_SELECTOR = FORM_SELECTOR + ' input, ' + FORM_SELECTOR + ' select';
    var DEBUG = Boolean(window.mervis_price_debug);

    function debugLog() {
        if (DEBUG && window.console && typeof window.console.log === 'function') {
            window.console.log.apply(window.console, arguments);
        }
    }

    function toNumber(value, fallback) {
        var parsed = parseFloat(value);
        return isNaN(parsed) || !isFinite(parsed) ? fallback : parsed;
    }

    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function escapeAttribute(value) {
        return escapeHtml(value);
    }

    function escapeRegExp(value) {
        return String(value).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    function sanitizeTitle(str) {
        return String(str || '')
            .replace(/\s+/g, '_')
            .replace(/[^a-zA-Z0-9_]/g, '')
            .toLowerCase();
    }

    function safeCalculateExpression(expression) {
        var formula = String(expression || '').replace(/\s+/g, '');

        if (!formula) {
            return null;
        }

        if (/[^0-9+\-*/().]/.test(formula)) {
            throw new Error('Formula contains invalid characters.');
        }

        var tokens = formula.match(/\d+(?:\.\d+)?|[+\-*/()]|\.\d+/g) || [];
        var position = 0;

        function peek() {
            return tokens[position];
        }

        function consume(expected) {
            var token = tokens[position];
            if (expected && token !== expected) {
                throw new Error('Expected "' + expected + '" but found "' + token + '".');
            }
            position += 1;
            return token;
        }

        function parseExpression() {
            var value = parseTerm();
            while (peek() === '+' || peek() === '-') {
                var operator = consume();
                var right = parseTerm();
                value = operator === '+' ? value + right : value - right;
            }
            return value;
        }

        function parseTerm() {
            var value = parseFactor();
            while (peek() === '*' || peek() === '/') {
                var operator = consume();
                var right = parseFactor();
                if (operator === '/') {
                    if (right === 0) {
                        throw new Error('Division by zero.');
                    }
                    value = value / right;
                } else {
                    value = value * right;
                }
            }
            return value;
        }

        function parseFactor() {
            var token = peek();

            if (token === '+') {
                consume('+');
                return parseFactor();
            }

            if (token === '-') {
                consume('-');
                return -parseFactor();
            }

            if (token === '(') {
                consume('(');
                var value = parseExpression();
                consume(')');
                return value;
            }

            if (/^(?:\d+(?:\.\d+)?|\.\d+)$/.test(token || '')) {
                consume();
                return parseFloat(token);
            }

            throw new Error('Unexpected token "' + token + '".');
        }

        var result = parseExpression();
        if (position !== tokens.length) {
            throw new Error('Unexpected token "' + tokens[position] + '".');
        }

        return result;
    }

    function collectFormValues() {
        var formValues = {};
        var debugInfo = [];
        var processedCheckboxGroups = {};

        $(FIELD_SELECTOR).each(function() {
            var $field = $(this);
            var name = $field.attr('name');
            var value = $field.val();
            var numericValue = 0;
            var priceData = 0;

            if (!name) {
                return;
            }

            if ($field.is(':disabled')) {
                return;
            }

            if ($field.is(':radio') && !$field.is(':checked')) {
                return;
            }

            if ($field.is('input[type="checkbox"]')) {
                if (processedCheckboxGroups[name]) {
                    return;
                }

                processedCheckboxGroups[name] = true;

                $('input[name="' + name.replace(/"/g, '\\"') + '"]:checked').each(function() {
                    numericValue += toNumber($(this).data('price'), 0);
                });

                value = $('input[name="' + name.replace(/"/g, '\\"') + '"]:checked').map(function() {
                    return $(this).val();
                }).get();

                formValues[name] = {
                    value: value,
                    numeric: numericValue,
                    priceData: numericValue
                };

                debugInfo.push('checkbox ' + name + ' = ' + value.join(', ') + ' (price: ' + numericValue + ')');
                return;
            }

            if ($field.is('select') && !value) {
                return;
            }

            if (value === '' || value === undefined || value === null) {
                return;
            }

            if ($field.is(':radio')) {
                priceData = toNumber($field.data('price'), 0);
                numericValue = priceData;
                debugInfo.push('radio ' + name + ' = ' + value + ' (price: ' + priceData + ')');
            } else if ($field.is('select')) {
                var $selected = $field.find('option:selected');
                priceData = toNumber($selected.data('price'), 0);
                numericValue = priceData;
                debugInfo.push('select ' + name + ' = ' + value + ' (price: ' + priceData + ')');
            } else if ($field.is('input[type="number"], input[type="text"]')) {
                var numberValue = toNumber(value, 0);
                var factor = toNumber($field.data('price-factor'), 1);
                numericValue = numberValue * factor;
                debugInfo.push('input ' + name + ' = ' + value + ' (factor: ' + factor + ', numeric: ' + numericValue + ')');
            } else if ($field.is('input[type="tel"], input[type="file"], input[type="email"], textarea')) {
                numericValue = 0;
                debugInfo.push('ignored ' + name + ' = ' + value);
            }

            formValues[name] = {
                value: value,
                numeric: numericValue,
                priceData: priceData
            };
        });

        return {
            values: formValues,
            debugInfo: debugInfo
        };
    }

    function applyPriceRule(basePrice, formValues) {
        var priceRule = String(window.mervis_price_rule || '').trim();
        var formula = priceRule;

        if (!priceRule) {
            debugLog('No price rule defined. Using base price:', basePrice);
            return basePrice;
        }

        Object.keys(formValues).forEach(function(key) {
            var replaceValue = toNumber(formValues[key].numeric, 0);
            formula = formula.replace(new RegExp('\\{' + escapeRegExp(key) + '\\}', 'g'), String(replaceValue));
            debugLog('{' + key + '} -> ' + replaceValue);
        });

        formula = formula.replace(/\{[^}]+\}/g, '0');
        debugLog('Formula after replacement:', formula);

        var calculated = safeCalculateExpression(formula);
        if (calculated === null || isNaN(calculated) || !isFinite(calculated)) {
            return basePrice;
        }

        return calculated;
    }

    function calculatePrice() {
        var total = toNumber(window.mervis_product_price, 0);
        var collected = collectFormValues();

        debugLog('Price calculator fields:', collected.debugInfo);
        debugLog('Price rule:', window.mervis_price_rule);

        try {
            total = applyPriceRule(total, collected.values);
            debugLog('Calculated total:', total);
        } catch (error) {
            debugLog('Price calculation error:', error.message);
        }

        var finalPrice = Math.max(0, Math.floor(total));
        $('#mervis-total-price').text(finalPrice.toLocaleString('fa-IR'));
        $('#mervis-calculated-price').val(finalPrice);

        return finalPrice;
    }

    function toggleCustomSelect($field) {
        if (!$field.hasClass('mervis-select-custom')) {
            return;
        }

        var $custom = $field.closest('.form-row-modern, .form-full-width').find('.mervis-custom-input');
        if ($field.val() === 'custom') {
            $custom.show();
        } else {
            $custom.hide().find('input').val('');
        }
    }

    function buildDynamicSubfield(fieldIndex, configIndex, subIndex) {
        var baseName = 'fields[' + fieldIndex + '][dynamic_configs][' + configIndex + '][subfields][' + subIndex + ']';

        return '' +
            '<div class="dynamic-subfield-row" style="display:flex; gap:8px; margin-bottom:6px; flex-wrap:wrap; align-items:center;">' +
                '<input type="text" name="' + baseName + '[label]" placeholder="عنوان زیرفیلد" style="flex:1; min-width:100px; padding:6px 10px; border:1px solid #ddd; border-radius:5px;">' +
                '<input type="text" name="' + baseName + '[key]" placeholder="کلید (انگلیسی)" style="flex:1; min-width:80px; padding:6px 10px; border:1px solid #ddd; border-radius:5px;">' +
                '<select name="' + baseName + '[type]" style="flex:1; min-width:80px; padding:6px 10px; border:1px solid #ddd; border-radius:5px; background:white;">' +
                    '<option value="number">عدد</option>' +
                    '<option value="text">متن</option>' +
                '</select>' +
                '<button type="button" class="button remove-dynamic-subfield" style="background:#dc2626; color:white; border:none; padding:2px 12px; border-radius:5px; cursor:pointer;">×</button>' +
            '</div>';
    }

    function buildDynamicConfig(fieldIndex, configIndex) {
        var baseName = 'fields[' + fieldIndex + '][dynamic_configs][' + configIndex + ']';

        return '' +
            '<div class="dynamic-config-row" style="border:1px solid #ddd; padding:15px; margin-bottom:15px; border-radius:8px; background:#ffffff;">' +
                '<div style="display:flex; gap:10px; align-items:center; margin-bottom:12px;">' +
                    '<input type="text" name="' + baseName + '[option_label]" placeholder="متن گزینه (مثلا: 13x10x18)" style="flex:2; padding:8px 12px; border:1px solid #ddd; border-radius:6px;">' +
                    '<button type="button" class="button remove-dynamic-config" style="background:#dc2626; color:white; border:none; padding:5px 15px; border-radius:6px; cursor:pointer;">حذف گزینه</button>' +
                '</div>' +
                '<div style="margin-right:20px;">' +
                    '<strong style="font-size:13px; display:block; margin-bottom:8px;">زیرفیلدهای این گزینه:</strong>' +
                    '<div class="dynamic-subfields">' + buildDynamicSubfield(fieldIndex, configIndex, 0) + '</div>' +
                    '<button type="button" class="button add-dynamic-subfield" style="margin-top:8px; background:#3b82f6; color:white; border:none; padding:5px 15px; border-radius:6px; cursor:pointer;">+ افزودن زیرفیلد</button>' +
                '</div>' +
            '</div>';
    }

    function getFieldIndex($fieldRow) {
        var explicitIndex = $fieldRow.data('field-index');
        return explicitIndex !== undefined ? explicitIndex : $fieldRow.index();
    }

    function getConfigIndex($configRow) {
        var explicitIndex = $configRow.data('config-index');
        return explicitIndex !== undefined ? explicitIndex : $configRow.index();
    }

    $(function() {
        $(document).on('change keyup', FIELD_SELECTOR, function() {
            toggleCustomSelect($(this));
            calculatePrice();
        });

        $(document).on('change', '.field-type', function() {
            var $row = $(this).closest('.field-row');
            var type = $(this).val();

            $row.find('.field-options').toggle(type === 'radio' || type === 'select' || type === 'select_custom' || type === 'checkbox');
            $row.find('.field-price-factor').toggle(type === 'text' || type === 'number');
            $row.find('.field-min-max').toggle(type === 'number');
            $row.find('.field-group-fields').toggle(type === 'group');
            $row.find('.field-dynamic-group-config').toggle(type === 'dynamic_group');
        });

        $(document).on('click', '.add-dynamic-config', function() {
            var $container = $(this).closest('.field-dynamic-group-config').find('.dynamic-group-options');
            var $fieldRow = $(this).closest('.field-row');
            var fieldIndex = getFieldIndex($fieldRow);
            var configIndex = $container.find('.dynamic-config-row').length;

            $container.append(buildDynamicConfig(fieldIndex, configIndex));
        });

        $(document).on('click', '.remove-dynamic-config', function() {
            var $container = $(this).closest('.dynamic-group-options');
            if ($container.find('.dynamic-config-row').length > 1) {
                $(this).closest('.dynamic-config-row').remove();
            } else {
                window.alert('حداقل یک گزینه باید باقی بماند');
            }
        });

        $(document).on('click', '.add-dynamic-subfield', function() {
            var $configRow = $(this).closest('.dynamic-config-row');
            var $container = $configRow.find('.dynamic-subfields');
            var fieldIndex = getFieldIndex($configRow.closest('.field-row'));
            var configIndex = getConfigIndex($configRow);
            var subIndex = $container.find('.dynamic-subfield-row').length;

            $container.append(buildDynamicSubfield(fieldIndex, configIndex, subIndex));
        });

        $(document).on('click', '.remove-dynamic-subfield', function() {
            var $container = $(this).closest('.dynamic-subfields');
            if ($container.find('.dynamic-subfield-row').length > 1) {
                $(this).closest('.dynamic-subfield-row').remove();
            } else {
                window.alert('حداقل یک زیرفیلد باید باقی بماند');
            }
        });

        $(document).on('change', '.mervis-dynamic-select', function() {
            var $select = $(this);
            var key = $select.data('key');
            var $container = $('.mervis-dynamic-group-container[data-key="' + String(key).replace(/"/g, '\\"') + '"]');
            var selectedValue = $select.val();
            var subfields = $select.find('option:selected').data('subfields') || [];
            var html = '';

            $container.empty();

            if (selectedValue === '' || selectedValue === undefined || !$.isArray(subfields) || subfields.length === 0) {
                calculatePrice();
                return;
            }

            $.each(subfields, function(index, subfield) {
                var label = subfield && subfield.label ? subfield.label : 'فیلد ' + (index + 1);
                var inputName = String(key || '') + '_' + (subfield && subfield.key ? subfield.key : sanitizeTitle(label));
                var inputType = subfield && subfield.type === 'number' ? 'number' : 'text';

                html += '<div class="mervis-dynamic-group-item" style="flex:1; min-width:80px;">';
                html += '<input type="' + escapeAttribute(inputType) + '" name="' + escapeAttribute(inputName) + '" ';
                html += 'placeholder="' + escapeAttribute(label) + '" ';
                html += 'data-label="' + escapeAttribute(label) + '" ';
                html += 'data-price-factor="1" ';
                html += 'style="width:100%; padding:10px 14px; border:1.5px solid #e2e8f0; border-radius:10px; font-size:14px; background:white;">';
                html += '</div>';
            });

            $container.html(html);
            calculatePrice();
        });

        $(document).on('change', '.mervis-file-input input[type="file"]', function() {
            var fileName = ($(this).val() || '').split('\\').pop();
            $(this).closest('.form-full-width').find('.mervis-file-name').text(fileName ? 'فایل: ' + fileName : '');
        });

        window.mervis_validate_form = function() {
            var errors = [];

            $(FORM_SELECTOR + ' .form-row-modern, ' + FORM_SELECTOR + ' .form-full-width').each(function() {
                var $row = $(this);
                var $label = $row.find('label').first();
                var labelText = $.trim($label.text()) || 'این فیلد';
                var $inputs = $row.find('input, select').not(':disabled');
                var $input = $inputs.first();

                if (!$input.length || $input.attr('type') === 'file') {
                    return;
                }

                if ($row.find('.mervis-radio-group').length) {
                    if (!$row.find('input[type="radio"]:checked').length) {
                        errors.push('لطفا "' + labelText + '" را انتخاب کنید');
                        $row.css({'border': '2px solid #ef4444', 'background': '#fef2f2', 'padding': '10px', 'border-radius': '8px'});
                    } else {
                        $row.css({'border': 'none', 'background': 'transparent', 'padding': '0'});
                    }
                    return;
                }

                if ($input.is('select')) {
                    if (!$input.val()) {
                        errors.push('لطفا "' + labelText + '" را انتخاب کنید');
                        $input.css({'border-color': '#ef4444', 'background': '#fef2f2'});
                    } else {
                        $input.css({'border-color': '#e2e8f0', 'background': 'white'});
                    }
                    return;
                }

                if ($input.is('.mervis-phone-input, input[type="tel"]')) {
                    var phone = $.trim($input.val());
                    var phoneRegex = /^[0-9]{10,11}$/;
                    if (!phoneRegex.test(phone)) {
                        errors.push('لطفا شماره تماس معتبر وارد کنید (10 یا 11 رقم)');
                        $input.css({'border-color': '#ef4444', 'background': '#fef2f2'});
                    } else {
                        $input.css({'border-color': '#e2e8f0', 'background': 'white'});
                    }
                    return;
                }

                if ($input.is('input[type="text"], input[type="number"]')) {
                    var value = $.trim($input.val());
                    if (value === '') {
                        errors.push('لطفا "' + labelText + '" را وارد کنید');
                        $input.css({'border-color': '#ef4444', 'background': '#fef2f2'});
                    } else {
                        $input.css({'border-color': '#e2e8f0', 'background': 'white'});
                    }
                }
            });

            return errors;
        };

        window.mervis_calculate_price = calculatePrice;

        setTimeout(calculatePrice, 500);
    });
})(jQuery);
