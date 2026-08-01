<?php
// این فایل به عنوان قالب تب در صفحه محصول استفاده می‌شود
// اما ما از تابع main استفاده می‌کنیم، این فایل برای بارگذاری سفارشی است

function mervis_render_form_in_template($product_id, $form_id, $fields, $price_rule) {
    ?>
    <style>
        .mervis-form {
            direction: rtl;
            text-align: right;
            font-family: inherit;
        }
        .mervis-form .form-row {
            margin-bottom: 20px;
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        .mervis-form label {
            display: block;
            font-weight: bold;
            margin-bottom: 8px;
            font-size: 14px;
        }
        .mervis-form input[type="text"],
        .mervis-form input[type="number"],
        .mervis-form select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .mervis-form input[type="radio"] {
            margin-left: 10px;
        }
        .mervis-form .radio-group {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }
        .price-result {
            font-size: 20px;
            font-weight: bold;
            color: #2196F3;
            padding: 15px;
            background: #f5f5f5;
            margin: 20px 0;
            text-align: center;
        }
        .mervis-form button {
            background: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        .mervis-form button:hover {
            background: #45a049;
        }
        .mervis-download-template {
            text-align: center;
            padding: 30px;
        }
        .mervis-download-template .button {
            background: #ff5722;
            color: white;
            padding: 12px 25px;
            text-decoration: none;
            display: inline-block;
            border-radius: 5px;
        }
    </style>
    
    <form id="mervis-dynamic-order-form" class="mervis-form" method="post" enctype="multipart/form-data">
        <?php wp_nonce_field('mervis_add_to_cart', 'mervis_form_nonce'); ?>
        <input type="hidden" name="product_id" value="<?php echo esc_attr($product_id); ?>">
        <input type="hidden" name="form_id" value="<?php echo esc_attr($form_id); ?>">
        <input type="hidden" id="mervis-calculated-price" name="calculated_price" value="0">
        
        <?php foreach ($fields as $field): ?>
<div class="form-row-modern">
    <label><?php echo esc_html($field['label']); ?></label>
    
    <?php if ($field['type'] == 'radio'): ?>
        <div class="mervis-radio-group">
            <?php foreach ($field['options'] as $idx => $option): ?>
                <label>
                    <input type="radio" name="<?php echo sanitize_title($field['label']); ?>" 
                           value="<?php echo esc_attr($option); ?>"
                           data-price="<?php echo esc_attr($field['option_prices'][$idx] ?? 0); ?>">
                    <?php echo esc_html($option); ?>
                    <?php if (!empty($field['option_prices'][$idx])): ?>
                        <span style="color:#e67e22;"> (+<?php echo number_format($field['option_prices'][$idx]); ?>ت)</span>
                    <?php endif; ?>
                </label>
            <?php endforeach; ?>
        </div>
    <?php elseif ($field['type'] == 'file'): ?>
        <div class="mervis-file-input" onclick="document.getElementById('file_<?php echo sanitize_title($field['label']); ?>').click();">
            📁 برای آپلود فایل کلیک کنید یا بکشید
            <input type="file" id="file_<?php echo sanitize_title($field['label']); ?>" 
                   name="<?php echo sanitize_title($field['label']); ?>" style="display:none;">
        </div>
        <div class="mervis-file-name" id="file-name-display-<?php echo sanitize_title($field['label']); ?>"></div>
        <small>فرمتهای مجاز: JPG, PNG, PDF, ZIP, AI, CDR (حداکثر 10MB)</small>
    <?php else: ?>
        <input type="<?php echo $field['type']; ?>" name="<?php echo sanitize_title($field['label']); ?>" 
               data-price-factor="<?php echo esc_attr($field['price_factor'] ?? 1); ?>"
               placeholder="مقدار را وارد کنید...">
    <?php endif; ?>
</div>
        <?php endforeach; ?>
        
        <div class="price-result">
            قیمت نهایی: <span id="mervis-total-price">0</span>
        </div>
        
        <button type="button" id="calc-price-btn">محاسبه مجدد قیمت</button>
        <button type="submit" name="add-to-cart">افزودن به سبد خرید</button>
    </form>
    
    <script>
    window.mervis_product_price = <?php echo floatval(wc_get_product($product_id)->get_price()); ?>;
    window.mervis_price_rule = <?php echo json_encode($price_rule); ?>;
    </script>
    
    <script src="<?php echo MERVIS_FORM_URL . 'frontend/price-calculator.js'; ?>"></script>
    <?php
}
?>