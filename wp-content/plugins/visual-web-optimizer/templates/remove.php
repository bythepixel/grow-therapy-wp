<?php defined('ABSPATH') or exit; ?>
$(document.body).off('click', '.remove').on('click', '.remove', function() {
    window.VWO = window.VWO || [];
    VWO.event = VWO.event || function() {
        VWO.push(["event"].concat([].slice.call(arguments)));
    };
   
    VWO.event('woocommerce.productRemovedFromCart', {
        productSku: $(this).data('product_sku') ? ('' + $(this).data('product_sku')) : ('#' + $(this).data('product_id')),
        productTitle: $(this).closest('tr').find('.product-name').text().trim() || '',
        quantity: $(this).closest('tr').find('.qty').val() ? parseInt($(this).closest('tr').find('.qty').val()): 1
    });
});
