<?php defined( 'ABSPATH' ) or exit; ; ?>
window.VWO = window.VWO || [];
VWO.event = VWO.event || function () {VWO.push(["event"].concat([].slice.call(arguments)))};
VWO.event( 'woocommerce.productViewed', {
    variantId: '<?php echo $variantId; ?>',
    productSku: '<?php echo $productSku; ?>',
    productId: '<?php echo $productId; ?>',
    productTitle: '<?php echo $productTitle; ?>',
    productCategory: <?php echo json_encode($productCategory) ?>,
    currency: '<?php echo $currency; ?>',
    price: <?php echo $price; ?>,
    quantity: <?php echo $quantity; ?>,
    productUrl:'<?php echo $productUrl; ?>'
});