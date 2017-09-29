<?php
$visa_img_path = plugins_url( 'images/hepsipay_creditcard_visa.png', __FILE__ );
$master_img_path = plugins_url( 'images/hepsipay_creditcard_master.png', __FILE__ );
$not_supported_img_path = plugins_url( 'images/hepsipay_creditcard_not_supported.png', __FILE__ );
$logo_image = plugins_url( 'woo-gateway-hepsipay/assets/img/logo.png');

?>
<style type="text/css">

.input-cc-number-visa {
    background: rgba(0, 0, 0, 0) url("<?php echo $visa_img_path; ?>") no-repeat scroll right center / 8% auto !important;
    float: left;
}

.input-cc-number-master {
    background: rgba(0, 0, 0, 0) url("<?php echo $master_img_path; ?>") no-repeat scroll right center / 7% auto !important;
    float: left;
}

.input-cc-number-maestro {
    background: rgba(0, 0, 0, 0) url("<?php echo $maestro_img_path; ?>") no-repeat scroll right center / 12% auto;
    float: left;
}

.input-cc-number-troy {
    background: rgba(0, 0, 0, 0) url("<?php echo $troy_img_path; ?>") no-repeat scroll right center / 12% auto;
    float: left;
}

.input-cc-number-not-supported {
    background: rgba(0, 0, 0, 0) url("<?php echo $not_supported_img_path; ?>") no-repeat scroll right center / 8% auto !important;
    float: left;
}

.logo_image {
    background: rgba(0, 0, 0, 0) url("<?php echo $logo_image; ?>") no-repeat scroll right center / 100% auto !important;
    float: left;
    height: 50px;
    margin: 0 0 10px 0;
    padding: 0;
    width: 125px;
}

.joker {
    border-radius: 25px;
    font-weight: 600;
    padding: 3px 10px;
    background: #ff9800;
    color: white;
    text-transform: uppercase;
}

.hepsipay-month-select-p,
.hepsipay-year-select-p{
    float: left;
    width: 50%;
}
</style>