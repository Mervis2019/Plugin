jQuery(document).ready(function($) {
    // فعال کردن sortable برای فیلدها
    if ($('#form-fields-container').length) {
        $('#form-fields-container').sortable({
            handle: '.drag-handle',
            placeholder: 'ui-state-highlight',
            axis: 'y'
        });
    }

    // ============================================
    // مدیریت افزودن فیلد جدید
    // ============================================
    $(document).off('click', '#add-new-field').on('click', '#add-new-field', function(e) {
        e.preventDefault(); // جلوگیری از رفتار پیش‌فرض (مثل رفرش صفحه یا پرش)
        
        // جلوگیری از کلیک‌های سریع و پشت سر هم (Double Click)
        if ($(this).hasClass('is-loading')) return;
        $(this).addClass('is-loading');
    
        var index = $('#form-fields-container .field-row').length;
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'mervis_get_empty_field_row',
                index: index
            },
            success: function(response) {
                $('#form-fields-container').append(response);
            },
            error: function(xhr, status, error) {
                console.error('خطا در افزودن فیلد:', error);
            },
            complete: function() {
                $('#add-new-field').removeClass('is-loading');
            }
        });
    });

    // ============================================
    // مدیریت حذف فیلد
    // ============================================
    $(document).on('click', '.remove-field', function() {
        if ($('#form-fields-container .field-row').length > 1) {
            $(this).closest('.field-row').remove();
        } else {
            alert('حداقل یک فیلد باید باقی بماند');
        }
    });

    // ============================================
    // مدیریت تغییر نوع فیلد
    // ============================================
    $(document).on('change', '.field-type', function() {
        var $row = $(this).closest('.field-row');
        var type = $(this).val();
        
        // نمایش/مخفی‌سازی بخش‌های مختلف
        $row.find('.field-options').toggle(type === 'radio' || type === 'select' || type === 'select_custom' || type === 'checkbox');
        $row.find('.field-price-factor').toggle(type === 'text' || type === 'number');
        $row.find('.field-min-max').toggle(type === 'number');
        $row.find('.field-group-fields').toggle(type === 'group');
        $row.find('.field-dynamic-group-config').toggle(type === 'dynamic_group');
    });

    // ============================================
    // مدیریت گزینه‌های فیلد (radio, select, checkbox)
    // ============================================
    $(document).on('click', '.add-option-row', function() {
        var $list = $(this).closest('.field-options').find('.options-list');
        var $first = $list.find('.option-row:first');
        var textName = $first.find('input[type="text"]').attr('name');
        var priceName = $first.find('input[type="number"]').attr('name');
        
        if (!textName || !priceName) {
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

    $(document).on('click', '.remove-option', function() {
        if ($(this).closest('.options-list').find('.option-row').length > 1) {
            $(this).closest('.option-row').remove();
        } else {
            alert('حداقل یک گزینه باید باقی بماند');
        }
    });

    // ============================================
    // مدیریت گروه پویا (Dynamic Group)
    // ============================================
    $(document).on('click', '.add-dynamic-config', function() {
        var $container = $(this).closest('.field-dynamic-group-config').find('.dynamic-group-options');
        var $fieldRow = $(this).closest('.field-row');
        var fieldIndex = $fieldRow.index();
        var configCount = $container.find('.dynamic-config-row').length;
        
        var newRow = `
            <div class="dynamic-config-row" style="border:1px solid #ddd; padding:12px; margin-bottom:12px; border-radius:8px; background:#f9fafb;">
                <div style="display:flex; gap:10px; align-items:center; margin-bottom:10px;">
                    <input type="text" name="fields[${fieldIndex}][dynamic_configs][${configCount}][option_label]" placeholder="متن گزینه (مثلا: 13x10x18)" style="flex:2;">
                    <button type="button" class="button remove-dynamic-config" style="background:#dc2626; color:white;">حذف گزینه</button>
                </div>
                <div style="margin-right:20px;">
                    <strong style="font-size:13px;">زیرفیلدهای این گزینه:</strong>
                    <div class="dynamic-subfields">
                        <div class="dynamic-subfield-row" style="display:flex; gap:8px; margin-bottom:6px; flex-wrap:wrap;">
                            <input type="text" name="fields[${fieldIndex}][dynamic_configs][${configCount}][subfields][0][label]" placeholder="عنوان زیرفیلد" style="flex:1; min-width:80px;">
                            <input type="text" name="fields[${fieldIndex}][dynamic_configs][${configCount}][subfields][0][key]" placeholder="کلید" style="flex:1; min-width:80px;">
                            <select name="fields[${fieldIndex}][dynamic_configs][${configCount}][subfields][0][type]" style="flex:1; min-width:80px;">
                                <option value="number">عدد</option>
                                <option value="text">متن</option>
                            </select>
                            <button type="button" class="button remove-dynamic-subfield" style="background:#dc2626; color:white; padding:0 10px;">×</button>
                        </div>
                    </div>
                    <button type="button" class="button add-dynamic-subfield" style="margin-top:5px;">+ افزودن زیرفیلد</button>
                </div>
            </div>
        `;
        $container.append(newRow);
    });

    $(document).on('click', '.remove-dynamic-config', function() {
        if ($(this).closest('.dynamic-group-options').find('.dynamic-config-row').length > 1) {
            $(this).closest('.dynamic-config-row').remove();
        } else {
            alert('حداقل یک گزینه باید باقی بماند');
        }
    });

    $(document).on('click', '.add-dynamic-subfield', function() {
        var $container = $(this).closest('.dynamic-config-row').find('.dynamic-subfields');
        var $configRow = $(this).closest('.dynamic-config-row');
        var fieldIndex = $configRow.closest('.field-row').index();
        var configIndex = $configRow.index();
        var subCount = $container.find('.dynamic-subfield-row').length;
        
        var newRow = `
            <div class="dynamic-subfield-row" style="display:flex; gap:8px; margin-bottom:6px; flex-wrap:wrap;">
                <input type="text" name="fields[${fieldIndex}][dynamic_configs][${configIndex}][subfields][${subCount}][label]" placeholder="عنوان زیرفیلد" style="flex:1; min-width:80px;">
                <input type="text" name="fields[${fieldIndex}][dynamic_configs][${configIndex}][subfields][${subCount}][key]" placeholder="کلید" style="flex:1; min-width:80px;">
                <select name="fields[${fieldIndex}][dynamic_configs][${configIndex}][subfields][${subCount}][type]" style="flex:1; min-width:80px;">
                    <option value="number">عدد</option>
                    <option value="text">متن</option>
                </select>
                <button type="button" class="button remove-dynamic-subfield" style="background:#dc2626; color:white; padding:0 10px;">×</button>
            </div>
        `;
        $container.append(newRow);
    });

    $(document).on('click', '.remove-dynamic-subfield', function() {
        if ($(this).closest('.dynamic-subfields').find('.dynamic-subfield-row').length > 1) {
            $(this).closest('.dynamic-subfield-row').remove();
        } else {
            alert('حداقل یک زیرفیلد باید باقی بماند');
        }
    });
});