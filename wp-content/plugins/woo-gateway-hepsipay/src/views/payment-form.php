<?php

/* @vat $this the instnce of WC_Gateway_Hepsipay */
wp_enqueue_script( 'wc-credit-card-form' );
$currency       = $currency_symbol;
$grandTotal     = $order->get_total();
$currencyAsText = $order->get_order_currency();

$bankImagesPath = plugins_url( 'images/', __FILE__ );

$IDS = [
    'bank'          => "{$id}-bank",
    'gateway'       => "{$id}-gateway",
    'cardset'       => "{$id}-cardset",
    'holder'        => "{$id}-card-holder",
    'pan'           => "{$id}-card-number",
    'month'         => "{$id}-card-month",
    'year'          => "{$id}-card-year",
    'cvc'           => "{$id}-card-cvc",
    'use3d-label'   => "{$id}-use3d-label",
    'use3d'         => "{$id}-use3d",
    'installment'   => "{$id}-installment",
    'use3d-row'     => "{$id}-use3d-row",
];

$LBLS = [
    'holder'        => __( 'Name on Card', 'hepsipay' ),
    'pan'           => __( 'Card Number', 'hepsipay' ),
    'month'         => __( 'Month', 'hepsipay' ),
    'year'          => __( 'Year', 'hepsipay' ),
    'cvc'           => __( 'CVC', 'hepsipay' ),
    'use3d'         => __( 'Use 3D secure Payments System', 'hepsipay' ),
    'installment'   => __("installment", "hepsipay"),
    'total'         => __("Total", "hepsipay"),
];

$VALS = [
    'bank'          => isset($form['bank']) ? $form['bank'] : '',
    'gateway'       => isset($form['gateway']) ? $form['gateway'] : '',
    'holder'        => isset($form['card']['holder']) ? $form['card']['holder'] : '',
    'pan'           => isset($form['card']['pan']) ? $form['card']['pan'] : '',
    'year'          => isset($form['card']['year']) ? $form['card']['year'] : '',
    'month'         => isset($form['card']['month']) ? $form['card']['month'] : '',
    'cvc'           => isset($form['card']['cvc']) ? $form['card']['cvc'] : '',
    'installment'   => isset($form["payment"]['installment']) ? $form["payment"]['installment'] : 1,
    'use3d'         => isset($form['use3d'])AND$form['use3d'] ? $form['use3d'] : 0,
    'campaign_id'   => isset($form['campaign_id'])AND$form['campaign_id'] ? $form['campaign_id'] : 0,
];


?>
<style>
    .install_body_label {float: left;width: 30%;height: 40px;text-align: center; border-bottom: 1px solid #d2d2d2;line-height: 40px;}
    .installment_row {/* padding-top: 10px;*/}
    .install_body_label.installment_radio, .installmet_head .install_head_label.add_space {height: 40px;text-align: center;width: 10%;line-height: 40px;}
    #installment_table_id {background-color: #eee;border: 1px solid;border-radius: 5px;padding: 10px;margin-top: 20px;}
    .installmet_head .install_head_label {float: left;font-weight: bold;text-align: center;width: 30%; height: 40px;line-height: 40px;border-bottom: 2px solid #d2d2d2; }
    .installment_body , .installment_footer {  clear: both; }
    .toatl_label {display:  none;}
    /* Style the list */
    ul.tab {  list-style-type: none;  margin: 0;  padding: 0;  overflow: hidden;  border: 1px solid #ccc;  background-color: #f1f1f1; height: 61px; }
    /* Float the list items side by side */
    ul.tab li {float: left; height: 61px;}
    /* Style the links inside the list items */
    ul.tab li a {  display: inline-block;  color: black;  text-align: center;  padding: 14px 16px;  text-decoration: none;  transition: 0.3s;  font-size: 17px; height: 61px; }
    /* Change background color of links on hover */
    ul.tab li a:hover {background-color: #ddd;}
    /* Create an active/current tablink class */
    ul.tab li a:focus, .active {background-color: #ccc;}
    /* Style the tab content */
    .tabcontent {  display: none;  padding: 6px 12px;  border: 1px solid #ccc;  border-top: none;  }
    .bkmImage {  height: 100% !important;  }
    .bkmTab {  padding: 2px !important;  }
    #woo_gateway_hepsipay-cardset{
        max-width: 500px;
    }
    #woo_gateway_hepsipay-cardset label{width: 100%}
    #woo_gateway_hepsipay-cardset input[type='text']{width: 100%; padding: 0.7em; border: 1px solid #afafaf;}
    #woo_gateway_hepsipay-cardset select{width: 98%; padding: 0.7em; border: 1px solid #afafaf;}
</style>
<form method="post" class="col-md-12">
    <div class="fieldset" id="<?php echo $IDS['cardset']; ?>">
        <?php //todo: hepsipay - bkm?>
        <?php //do_action( 'woocommerce_credit_card_form_start', $this->id ); ?>
        <?php //todo: hepsipay - bkm?>
                <p class="logo_image"></p>
                <div class="clear"></div>
                <p class="form-row form-row-wide">
                    <label for="<?php echo $IDS['holder']; ?>"><?php echo $LBLS['holder']; ?> <span class="required">*</span></label>
                    <input id="<?php echo $IDS['holder']; ?>" value="<?php echo $VALS['holder']; ?>" class="input-text wc-credit-card-form-card-holder" type="text" maxlength="20" autocomplete="off" placeholder="" name="card[holder]" />
                </p>
                <p class="form-row form-row-wide">
                    <label for="<?php echo $IDS['pan']; ?>"><?php echo $LBLS['pan']; ?> <span class="required">*</span></label>
                    <input value="<?php echo $VALS['pan']; ?>" id="<?php echo $IDS['pan']; ?>" data-value="<?php echo $VALS['pan']; ?>" class="input-text wc-credit-card-form-card-number input-cc-number-not-supported" type="text" maxlength="20" autocomplete="off" placeholder="•••• •••• •••• ••••" name="card[pan]" />
                </p>
                <div class="form-row form-row-wide">
                    <p class="form-row form-row-first hepsipay-month-select-p">
                        <label for="<?php echo $IDS['month']; ?>"><?php echo $LBLS['month']; ?> <span class="required">*</span></label>
                        <select id="<?php echo $IDS['month']; ?>" name="card[month]" class="input-text wc-credit-card-form-card-month">
                            <option value=""><?php echo __('Month', 'hepsipay'); ?></option>
                            <?php for($i=1;$i<=12;$i++) : ?>
                                <?php $i = (strlen($i) == 2)?$i:'0'.$i; ?>
                                <?php $selected = $i==$VALS['month'] ? 'selected' : ''; ?>
                                <option value="<?php echo $i;?>" <?php echo $selected; ?> ><?php echo $i;?></option>
                            <?php endfor; ?>
                        </select>
                    </p>

                    <p class="form-row form-row-last hepsipay-year-select-p">
                        <label for="<?php echo $IDS['year']; ?>"><?php echo $LBLS['year']; ?> <span class="required">*</span></label>
                        <select id="<?php echo $IDS['year']; ?>" name="card[year]" class="input-text wc-credit-card-form-card-year">
                            <option value=""><?php echo __('Year', 'hepsipay'); ?></option>
                            <?php for($i=0;$i<15;$i++) : ?>
                                <?php $year = date('Y') + $i; ?>
                                <?php $selected = $year==$VALS['year'] ? 'selected' : ''; ?>
                                <option value="<?php echo $year;?>" <?php echo $selected; ?> ><?php echo $year;?></option>
                            <?php endfor; ?>
                        </select>
                    </p>
                </div>
                <p class="form-row form-row-wide">
                    <label for="<?php echo $IDS['cvc']; ?>"><?php echo $LBLS['cvc']; ?> <span class="required">*</span></label>
                    <input id="<?php echo $IDS['cvc']; ?>" value="<?php echo $VALS['cvc']; ?>" class="input-text wc-credit-card-form-card-cvc" type="text" autocomplete="off" placeholder="" name="card[cvc]" />
                </p>
                <?php if($enable_installment) : ?>
                <p class="form-row installment">
                    <div id="installment_table_id">
                        <div class="installmet_head">
                            <div class="install_head_label add_space"><img style="display: none" class="bank_photo" data-src="<?php echo $bankImagesPath; ?>" src=""></div>
                            <div class="install_head_label"><?php echo __('installment', 'hepsipay') ?></div>
                            <div class="install_head_label"><?php echo __('Amount / Month', 'hepsipay') ?></div>
                            <div class="install_head_label"><?php echo __('Total', 'hepsipay') ?></div>
                        </div>
                        <div class="installment_body" id="installment_body">
                            <div class="installment_row">
                                <div class="install_body_label installment_radio"><input rel="1" type="radio" class="installment_radio" checked name="payment[installment]" value="1" /></div>
                                <div class="install_body_label installment_lable_code">1</div>
                                <div class="install_body_label"><?php echo $currency.' '.$grandTotal; ?></div>
                                <div class="install_body_label final_commi_price" rel="<?php echo $grandTotal; ?>"><?php echo $currency.' '.$grandTotal; ?></div>
                            </div>
                        </div>
                        <div class="installment_footer"></div>
                    </div>
                    <input id="<?php echo $IDS['bank']; ?>" type="hidden" name="bank" value="<?php echo $VALS['bank']; ?>" />
                    <input id="<?php echo $IDS['gateway']; ?>" type="hidden" name="gateway" value="<?php echo $VALS['gateway']; ?>" />
                    <input id="<?php echo $IDS['installment']; ?>" type="hidden" name="installment" value="<?php echo $VALS['installment']; ?>" />
                </p>
                <?php endif; ?>
                <?php //todo: hepsipay - extra int?>


                <?php if($force_3dSecure) : ?>
                    <br>
                    <p class="form-row form-row-wide hepsipay-3dsecure" id="<?php echo $IDS['use3d-row'] ?>">
                        <label for="<?php echo $IDS['use3d']; ?>">
                            <input data-forced="true" checked="checked" disabled="disabled" id="<?php echo $IDS['use3d']; ?>" class="input-checkbox hepsipay-options-use3d" type="checkbox" name="use3d" value="true" />
                            <?php echo $LBLS['use3d']; ?>
                        </label>
                    </p>
                <?php elseif($enable_3dSecure) : ?>
                    <br>
                    <p class="form-row form-row-wide hepsipay-3dsecure" id="<?php echo $IDS['use3d-row'] ?>">
                        <label for="<?php echo $IDS['use3d']; ?>">
                            <input data-forced="false" <?php if(isset($VALS['use3d'])AND$VALS['use3d']) echo 'checked'; ?> id="<?php echo $IDS['use3d']; ?>" class="input-checkbox hepsipay-options-use3d" type="checkbox" name="use3d" value="true" />
                            <?php echo $LBLS['use3d']; ?>
                        </label>
                    </p>
                <?php endif; ?>


        <?php //todo: hepsipay - bkm?>

        <div class="clear"></div>

        <p class="form-row form-row-wide">
        <input type="submit" value="<?php echo __('Checkout', 'hepsipay'); ?>" class="btn btn-button btn-primary button button-primary  button-default  button default">
        </p>
    </div>
</form>

<?php if($enable_installment) : ?>
<script type="text/javascript">
    (function ($) {
        window.hepsipay = {
            bin: false,
            banks: [],
            total: parseFloat('<?php echo $grandTotal;?>'),
            currency: "<?php echo $currency;?>",
            totalSelector: "<?php echo $total_selector;?>",
            currencyClass: "<?php echo $currency_class;?>",
            oneShotCommission: 0,


            loadBanks: function() {
                $.ajax({
                    url: "index.php?hepsipay-api=v1",
                    method: "POST",
                    data: { command:"banks" , total: hepsipay.total, currency:'<?php echo $currencyAsText; ?>'},
                    dataType: "json",
                    success: function (response) {
                        hepsipay.banks = response.data;
                        hepsipay.oneShotCommission = response.oneShotCommission;
                        <?php if(!empty($VALS['bank'])) : ?>
                        hepsipay.refreshInstallmentPlans("<?php echo $VALS['bank']; ?>");
                        <?php endif; ?>
                    }
                });
            },

            updateGrandTotal: function(total, currency) {
                total = Math.round(total * 100) / 100;
                $(this.totalSelector).html('<span clas="'+this.currencyClass+'">'+currency+'</span>&nbsp;'+total);
            },

            detectCardBrand: function($el) {
                var number = $el.val();
                $el.removeClass('input-cc-number-not-supported');

                var re_visa = new RegExp("^4");
                var re_master = new RegExp("^5");
                var re_maestro = new RegExp("^6");
                var re_troy = new RegExp("^9");

                if (number.match(re_visa) != null){
                    $el.addClass('input-cc-number-visa');
                    $el.removeClass('input-cc-number-master');
                    $el.removeClass('input-cc-number-maestro');
                    $el.removeClass('input-cc-number-troy');
                }else if (number.match(re_master) != null){
                    $el.removeClass('input-cc-number-visa');
                    $el.removeClass('input-cc-number-maestro');
                    $el.removeClass('input-cc-number-troy');
                    $el.addClass('input-cc-number-master');
                }else if (number.match(re_maestro) != null){
                    $el.removeClass('input-cc-number-visa');
                    $el.removeClass('input-cc-number-master');
                    $el.removeClass('input-cc-number-troy');
                    $el.addClass('input-cc-number-maestro');
                }else if (number.match(re_troy) != null){
                    $el.removeClass('input-cc-number-visa');
                    $el.removeClass('input-cc-number-maestro');
                    $el.removeClass('input-cc-number-master');
                    $el.addClass('input-cc-number-troy');
                }else{
                    $el.removeClass('input-cc-number-visa');
                    $el.removeClass('input-cc-number-master');
                    $el.addClass('input-cc-number-not-supported');
                }
            },

            onCardChanged: function (element) {
                var $bank_photo = $('.bank_photo');
                hepsipay.getExtraInstallments(1, 1, '', '');
                this.detectCardBrand($(element));
                var bin = $(element).val().replace(/\s/g, '').substr(0, 6);
                if (bin.length < 6) {
                    hepsipay.refreshInstallmentPlans('', '');
                    $bank_photo.hide();
                    this.bin = bin;
                    return;
                }
                if (bin == this.bin) { return; }
                this.bin = bin;

                var url = "index.php?hepsipay-api=v1";
                $.ajax({
                    url: url,
                    method: "POST",
                    data: { command:"bin", bin: bin },
                    dataType: "json",
                    success: function (response) {
                        var bank = response.data.bank_id;
                        if (bank) {
                            hepsipay.refreshInstallmentPlans(bank, response.data.type);
                        }else{
                            hepsipay.refreshInstallmentPlans('', response.data.type);
                        }

                        //force 3d for debit
                        <?php if($force_3dSecure_debit OR TRUE) : ?>
                        if(
                            response.data.type != 'CREDIT' &&
                            $('#<?php echo $IDS['use3d']; ?>').attr('data-forced') == 'false'
                        ){
                            $('#<?php echo $IDS['use3d']; ?>').attr('disabled', 'disabled');
                            $('#<?php echo $IDS['use3d']; ?>').prop("checked", true);

                        }else if($('#<?php echo $IDS['use3d']; ?>').attr('data-forced') == 'false'){
                                $('#<?php echo $IDS['use3d']; ?>').removeAttr('disabled');
                        }
                        <?php endif;?>

                        //show bank image
                        if(bank && bank.length){
                            if(response.data.type == 'CREDIT'){
                                $bank_photo.attr('src', $bank_photo.attr('data-src')+'networks/'+bank+'.png');
                            }else{
                                $bank_photo.attr('src', $bank_photo.attr('data-src')+'banks/'+bank+'.png');
                            }
                            $bank_photo.show();
                        }else{
                            $bank_photo.hide();
                        }
                    }
                });
            },

            show3D: function (val) {
                <?php if($enable_3dSecure) : ?>
                val ? $('#<?php echo $IDS['use3d-row']; ?>').show() : $('#<?php echo $IDS['use3d-row'] ?>').hide();
                if (!val) {
                    $('#<?php echo $IDS['use3d-row'] ?> input[type="checkbox"]').prop("checked", false);
                    $('#<?php echo $IDS['use3d-row'] ?> label').removeClass("checked");
                }
                <?php endif ?>
            },

            payWithInstallment: function (count, bank, gateway) {
                $('#<?php echo $IDS['installment'] ?>').val(count);
                $('#<?php echo $IDS['bank'] ?>').val(bank);
                $('#<?php echo $IDS['gateway'] ?>').val(gateway);
            },

            payOneShot: function () {
                this.show3D(true);
                this.payWithInstallment(1, '', '');
            },

            getInstallmentOption: function(count, amount, percentage, currency, has3d, bank, gateway, hasExtra) {
                var commission = percentage;//percentage.replace('%', '');
                var fee = amount * parseFloat(commission) / 100;
                var total = amount * (1 + parseFloat(commission) / 100);
                var pmon = total / count;
                var checked = count==1 ? 'checked' : '';

                if(count == '<?php echo $VALS['installment']?>'){
                    if(gateway != '') hepsipay.getExtraInstallments(total, count, bank, gateway);
                    checked = 'checked';
                    hepsipay.updateGrandTotal(total, hepsipay.currency);
                }

                var textOfCount = count==1 ? '<?php echo __('One Shot', 'hepsipay')?>' : count;
                if(' <?php echo $enable_extra_installment; ?>' == true){
                    textOfCount     = hasExtra=='1'?'<span class="joker">'+count+' + Joker</span>' : textOfCount;
                }


                return ''
                    + '<div class="installment_row">'
                    + '<div class="install_body_label installment_radio">'
                    + '<input rel="'+count+'" data-fee="'+fee.toFixed(2)+'" data-total="'+total.toFixed(2)+'" data-has3d="'+has3d+'" data-bank="'+bank+'" data-gateway="'+gateway+'" class="custom_field_installment_radio" type="radio" '+checked+' name="payment[installment]" value="'+count+'" />'
                    + '</div>'
                    + '<div class="install_body_label installment_lable_code">'+textOfCount+'</div>'
                    + '<div class="install_body_label">' + currency +' '+ pmon.toFixed(2) + '</div>'
                    + '<div rel="' + total + '" class="install_body_label final_commi_price">' + currency +' '+ parseFloat(total).toFixed(2) + '</div>'
                    + '</div>'
                ;
            },

            refreshInstallmentPlans: function (bankName, cardType) {
                this.payOneShot();

                var $e = $('#installment_body');
                $e.empty();
                var optEl = this.getInstallmentOption(1, this.total, hepsipay.oneShotCommission, this.currency, 1, '', '');
                $e.append(optEl);

                if(cardType != 'CREDIT'){
                    return;
                }
                for (var i in this.banks) {
                    var bank = this.banks[i];
                    if (bank.bank == bankName) {
                        var opt, t, fee;

                        var usedIntCount = [];
                        for (var j in bank.installments) {
                            opt = bank.installments[j];
                            if(opt.count < 2) continue;

                            if(usedIntCount[opt.count] != undefined) continue;

                            usedIntCount[opt.count] = opt.count;
                            fee = parseFloat(opt.commission);
                            t = Math.round(this.total * (1+fee)*100)/100;
                            optEl = this.getInstallmentOption(opt.count, this.total, fee, this.currency, bank.has3d, bank.bank, bank.gateway, opt.hasExtra) ;
                            $e.append(optEl);
                        }
                        break;
                    }
                }
            },

            getExtraInstallments: function (total, count, bank, gateway) {
                //todo: hepsipay - extra int
            },

            run: function () {

                this.loadBanks();
                this.detectCardBrand($('#<?php echo $IDS['pan'] ?>'));
                hepsipay.onCardChanged($('#<?php echo $IDS['pan'] ?>'));

                $('#<?php echo $IDS['pan'] ?>').keyup(function () {
                    hepsipay.onCardChanged(this);
                });

                $('body').on("change", '.custom_field_installment_radio', function () {
                    var $el = $(this);
                    var count = $el.attr('rel');
                    var total = $el.data('total');

                    hepsipay.updateGrandTotal(total, hepsipay.currency);
                    hepsipay.getExtraInstallments(total, count, $el.data('bank'), $el.data('gateway'));

                    if(count!=1) {
                        hepsipay.show3D($el.data('has3d'));
                        hepsipay.payWithInstallment(count, $el.data('bank'), $el.data('gateway'));
                    } else {
                        hepsipay.payOneShot();
                    }
                });

                if (this.init) {
                    this.init();
                }
            }
        };

    })(jQuery);
</script>
<?php else: ?>
    <script type="text/javascript">
        (function ($) {
            window.hepsipay = {
                bin: false,
                banks: [],
                total: parseFloat('<?php echo $grandTotal;?>'),
                currency: "<?php echo $currency;?>",
                totalSelector: "<?php echo $total_selector;?>",
                currencyClass: "<?php echo $currency_class;?>",

                detectCardBrand: function($el) {
                    var number = $el.val();
                    $el.removeClass('input-cc-number-not-supported');

                    var re_visa = new RegExp("^4");
                    var re_master = new RegExp("^5");
                    var re_maestro = new RegExp("^6");
                    var re_troy = new RegExp("^9");

                    if (number.match(re_visa) != null){
                        $el.addClass('input-cc-number-visa');
                        $el.removeClass('input-cc-number-master');
                        $el.removeClass('input-cc-number-maestro');
                        $el.removeClass('input-cc-number-troy');
                    }else if (number.match(re_master) != null){
                        $el.removeClass('input-cc-number-visa');
                        $el.removeClass('input-cc-number-maestro');
                        $el.removeClass('input-cc-number-troy');
                        $el.addClass('input-cc-number-master');
                    }else if (number.match(re_maestro) != null){
                        $el.removeClass('input-cc-number-visa');
                        $el.removeClass('input-cc-number-master');
                        $el.removeClass('input-cc-number-troy');
                        $el.addClass('input-cc-number-maestro');
                    }else if (number.match(re_troy) != null){
                        $el.removeClass('input-cc-number-visa');
                        $el.removeClass('input-cc-number-maestro');
                        $el.removeClass('input-cc-number-master');
                        $el.addClass('input-cc-number-troy');
                    }else{
                        $el.removeClass('input-cc-number-visa');
                        $el.removeClass('input-cc-number-master');
                        $el.addClass('input-cc-number-not-supported');
                    }

                },

                onCardChanged: function (element) {
                    var $bank_photo = $('.bank_photo');
                    this.detectCardBrand($(element));
                    var bin = $(element).val().replace(/\s/g, '').substr(0, 6);
                    if (bin.length < 6) {
                        return;
                    }
                    if (bin == this.bin) { return; }
                    this.bin = bin;

                    var url = "index.php?hepsipay-api=v1";
                    $.ajax({
                        url: url,
                        method: "POST",
                        data: { command:"bin", bin: bin },
                        dataType: "json",
                        success: function (response) {
                            var bank = response.data.bank_id;
                            if (bank) {
                                hepsipay.refreshInstallmentPlans(bank, response.data.type);
                            }

                            if(bank && bank.length){
                                if(response.data.type == 'CREDIT'){
                                    $bank_photo.attr('src', $bank_photo.attr('data-src')+'networks/'+bank+'.png');
                                }else{
                                    $bank_photo.attr('src', $bank_photo.attr('data-src')+'banks/'+bank+'.png');
                                }

                                $bank_photo.show();
                            }else{
                                $bank_photo.hide();
                            }
                        }
                    });
                },

                show3D: function (val) {
                    <?php if($enable_3dSecure) : ?>
                    val ? $('#<?php echo $IDS['use3d-row']; ?>').show() : $('#<?php echo $IDS['use3d-row'] ?>').hide();
                    if (!val) {
                        $('#<?php echo $IDS['use3d-row'] ?> input[type="checkbox"]').prop("checked", false);
                        $('#<?php echo $IDS['use3d-row'] ?> label').removeClass("checked");
                    }
                    <?php endif ?>
                },

                payOneShot: function () {
                    this.show3D(true);
                    this.payWithInstallment(1, '', '');
                },

                refreshInstallmentPlans: function (bankName, cardType) {
                },

                run: function () {
                    this.detectCardBrand($('#<?php echo $IDS['pan'] ?>'));

                    $('#<?php echo $IDS['pan'] ?>').keyup(function () {
                        hepsipay.onCardChanged(this);
                    });

                    $('body').on("change", '.custom_field_installment_radio', function () {
                        var $el = $(this);
                        var count = $el.attr('rel');
                        var total = $el.data('total');

                        hepsipay.updateGrandTotal(total, hepsipay.currency);
                        hepsipay.payOneShot();

                    });

                    if (this.init) {
                        this.init();
                    }
                }
            };

        })(jQuery);
    </script>
<?php endif; ?>

<script type="text/javascript">
    (function ($) {
        hepsipay.run();
    })(jQuery);
</script>
    <script type="text/javascript">
        (function ($) {
            $('.tablinks').click(function(evt){
                methodName = $(this).attr('data-method');
                // Declare all variables
                var i, tabcontent, tablinks;

                // Get all elements with class="tabcontent" and hide them
                tabcontent = document.getElementsByClassName("tabcontent");
                for (i = 0; i < tabcontent.length; i++) {
                    tabcontent[i].style.display = "none";
                }

                // Get all elements with class="tablinks" and remove the class "active"
                tablinks = document.getElementsByClassName("tablinks");
                for (i = 0; i < tablinks.length; i++) {
                    tablinks[i].className = tablinks[i].className.replace(" active", "");
                }

                //todo: hepsipay - bkm

            });
        })(jQuery);
    </script>


<?php $this->renderView(__DIR__."/card-brand.css.php");?>