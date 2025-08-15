<?php defined('ABSPATH') or exit; ?>
$('.single_add_to_cart_button').on('click', function() {
    window.VWO = window.VWO || [];
    VWO.event = VWO.event || function() {
        VWO.push(["event"].concat([].slice.call(arguments)));
    };
    var productSku = '<?php echo $productSku ?>';
    var productTitle = '<?php echo $productTitle ?>';
    var price = <?php echo $price ?> ;
    var productId = '<?php echo $productId ?>';
    var productCategory = '<?php echo json_encode($productCategory) ?>';
    var productUrl = '<?php echo $productUrl?>';

    if ($('input[name="variation_id"]').val()) {
        var variant = $('input[name="variation_id"]').val();

        $.each($('.variations_form').data('product_variations'), function(index, product) {
            if (product.variation_id == variant) {
                if (product.hasOwnProperty('sku')) {
                    productSku = product.sku;
                }
                if (product.hasOwnProperty('display_price')) {
                    price = product.display_price;
                }
                if (product.hasOwnProperty('attributes')) {
                    productTitle += ' - ';
                    var attributes = Object.values(product.attributes).join(', ');
                    productTitle += attributes;
                }
            }
        });
    }

    VWO.event("woocommerce.productAddedToCart", {
        "productTitle": productTitle,
        "productId": productId,
        "quantity": $('input.qty').val() ? parseInt($('input.qty').val()) : 1,
        "price": price,
        "productCategory": productCategory,
        "productUrl": productUrl,
        "variantId": productSku,
        "productSku": productSku
    });
});
