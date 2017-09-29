<?php

class WC_Gateway_Hepsipay extends WC_Payment_Gateway
{
    const INSTALLMENTS_TYPE_TABLE = "table";
    const INSTALLMENTS_TYPE_LIST = "list";

    protected static $_instance = null;

    private $_hepsipay;

    public $username = null;
    public $password = null;
    public $custom_css = null;
    public $endpoint = null;
    public $enable_3dSecure = 1;
    public $force_3dSecure  = 0;
    public $force_3dSecure_debit  = 0;
    public $enable_installment = 1;
    public $enable_commission = 0;
    public $enable_extra_installment = 0;
    public $enable_bkm = 0;
    public $currency_class;
    public $total_selector;
    /**
     * @var array the HTML attributes to resner the iframe
     */
    public $options = [];

    public function __construct($register_hooks=false)
    {
        $this->id = 'woo_gateway_hepsipay';
        $this->icon = plugins_url('woo-gateway-hepsipay/assets/img/icon.png');
        $this->has_fields = false;
        $this->method_title = __('Hepsipay', 'hepsipay');
        $this->method_description = __('Process payment via Hepsipay service.', 'hepsipay');
        $this->order_button_text = __('Proceed to Hepsipay', 'hepsipay');
        $this->supports = array(
            'products',
			//'default_credit_card_form',
			'refunds',
        );

        $this->init_form_fields();
        $this->init_settings();

        $this->title = $this->get_option( 'title' );
        $this->enabled = $this->get_option('enabled');
        $this->description = $this->get_option('description');
        $this->username = $this->get_option('username');
        $this->password = $this->get_option('password');
        $this->custom_css = $this->get_option('custom_css');
        $this->endpoint = $this->get_option('endpoint');
        $this->currency_class = $this->get_option('currency_class');
        $this->total_selector = $this->get_option('total_selector');
        $this->enable_3dSecure = $this->get_option('enable_3dSecure');
        $this->force_3dSecure = $this->get_option('force_3dSecure');
        $this->force_3dSecure_debit = $this->get_option('force_3dSecure_debit');
        $this->enable_installment = $this->get_option('enable_installment');
        $this->enable_commission = $this->get_option('enable_commission');
        //todo: hepsipay - extra int
        $this->enable_extra_installment = 0;
        //todo: hepsipay - bkm
        $this->enable_bkm = 0;

        if($register_hooks) {
            //$this->initApiService();
            add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'receipt_page' ) );
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
            add_action( 'woocommerce_api_'.strtolower(__CLASS__), array( &$this, 'check_payment_response' ) );
        }
    }

    public function version()
    {
        return "v1";
    }

    public function &hepsipay() {
        if(!$this->_hepsipay) {
            require_once 'HepsipayService.php';
            $lang = get_locale();
            $lang = explode('_', $lang);
            $this->_hepsipay = new HepsipayService([
                'username' => $this->username,
                'password' => $this->password,
                'endpoint' => $this->endpoint,
                'language' => $lang[0],
                'enable_commission' => $this->enable_commission,
            ]);
        }

        return $this->_hepsipay;
    }

    public function initApiService()
    {
        add_rewrite_tag( '%hepsipay-api%', '([^&]+)' );
        add_action( 'template_redirect', array($this, 'handleApiRequest'));
    }

    public function handleApiRequest()
    {
        global $wp_query;

        $hepsipay = $wp_query->get( 'hepsipay-api' );

        if ( ! $hepsipay ) {
            return;
        }
        $params = explode('/', $hepsipay);
        $version = $params[0];
        $data = $_POST;
        $result = null;

        if(!isset($data['command'])) {
            throw new Exception("Invalide request.");
        }
        if($version!="v1") {
            throw new Exception("unsupported version.");
        }

        $cmd = $data['command'];
        switch($cmd) {
            case 'bin':
                $result = $this->hepsipay()->bin($data['bin']);
                break;
            case 'banks':
                $result = $this->hepsipay()->banks($data);
                break;
            case 'extra_ins':
                $result = $this->hepsipay()->extraInstallments($data);
                break;
            default:
                $result = ['error' => true, 'message'=>'Unsupported command'];
                break;
        }

        wp_send_json( $result );
    }

    /**
     * override
     */
    public function init_settings()
    {
        parent::init_settings();
    }

    public function init_form_fields()
    {

        $serverIP = self::getAddresses_www($_SERVER['SERVER_NAME']);
        $serverIP = isset($serverIP['ip'])?$serverIP['ip']:$serverIP;

        $this->form_fields = [
            'enabled' => [
                'title' => __('Enabled', 'hepsipay'),
                'type' => 'checkbox',
                //'description' => __('Enable/Disable "Hepsipay" checkout.', 'hepsipay'),
                'default' => 'yes',
            ],
            'show_server_ip' => [
                'title' => __('Sunucunuzun IP\'si', 'hepsipay').': '.$serverIP,
                'type' => 'text',
                'description' => '',
                'default' => $serverIP,
                'class' => 'hidden',
            ],
            'title' => [
                'title' => __('Title', 'hepsipay'),
                'type' => 'text',
                'description' => __('The title which the user will see in the checkout page.', 'hepsipay'),
                'default' => __('Hepsipay Checkout', 'hepsipay'),
            ],
            'description' => array(
                'title' => __('Description', 'hepsipay'),
                'type' => 'textarea',
                'description' => __('The message to display during checkout.', 'hepsipay'),
                'default' => __('Pay via Hepsipay, pay safely with your credit card.', 'hepsipay'),
            ),
            'endpoint' => [
                'title' => __('Endpoint', 'hepsipay'),
                'type' => 'text',
                'description' => __('The api url to "Hepsipay" service.', 'hepsipay'),
                'default' => '',
                'class' => 'endpoint_field',
            ],
            'username' => array(
                'title' => __('ApiKey', 'hepsipay'),
                'type' => 'text',
                'default' => '',
                // 'description' => __('', 'hepsipay'),
            ),
            'password' => array(
                'title' => __('SecretKey', 'hepsipay'),
                'type' => 'text',
                'default' => '',
                // 'description' => __('', 'hepsipay'),
            ),
            'enable_3dSecure' => array(
                'title' => __('3D Secure', 'hepsipay'),
                'type' => 'select',
                'options'     => array(__( 'No', 'hepsipay' ),__( 'Yes', 'hepsipay' )),
                'description' => __('Choose whether to enable 3D secure payment option.', 'hepsipay'),
            ),
            'force_3dSecure' => array(
                'title' => __('Force 3D Secure', 'hepsipay'),
                'type' => 'select',
                'options'     => array(__( 'No', 'hepsipay' ),__( 'Yes', 'hepsipay' )),
                'description' => __('If 3D secure option is mandatory in Hepsipay side, this option must be enable. Otherwise your transactions will fail.', 'hepsipay'),
            ),
            'force_3dSecure_debit' => array(
                'title' => __('Force 3D secure for DEBIT cards', 'hepsipay'),
                'type' => 'select',
                'options'     => array(__( 'No', 'hepsipay' ),__( 'Yes', 'hepsipay' )),
                'description' => __('Choose whether to force 3D secure in debit card case.', 'hepsipay'),
                'class' => 'force_3dSecure_debit',
            ),
            'enable_installment' => array(
                'title' => __('Enable Installment', 'hepsipay'),
                'type' => 'select',
                'options'     => array(__( 'No', 'hepsipay' ),__( 'Yes', 'hepsipay' )),
                'description' => __('Choose whether to enable installment option.', 'hepsipay'),
            ),
            'enable_commission' => array(
                'title' => __('Enable Commission', 'hepsipay'),
                'type' => 'select',
                'options'     => array(__( 'No', 'hepsipay' ),__( 'Yes', 'hepsipay' )),
                'description' => __('Choose whether to enable commission option.', 'hepsipay'),
            ),
            //todo: hepsipay - extra int
            //todo: hepsipay - bkm
            'total_selector' => array(
                'title' => __('Total Selector', 'hepsipay'),
                'type' => 'text',
                'default' => '.order_details .amount',
                'description' => __('A jQuery selector of the HTML element that contains the total amount in checkout page.', 'hepsipay'),
            ),
            'currency_class' => array(
                'title' => __('Currency Class', 'hepsipay'),
                'type' => 'text',
                'default' => 'woocommerce-Price-currencySymbol',
                'description' => __('The CSS class(es) to be applied to the curreny on checkout page', 'hepsipay'),
            ),
            'custom_css' => [
                'title' => __('Custom Css', 'hepsipay'),
                'type' => 'textarea',
                'default' => file_get_contents (WP_PLUGIN_DIR. '/woo-gateway-hepsipay/assets/custom.css'),
                // 'description' => __('Customiz the installments table.', 'hepsipay'),
            ],
            'validate_merchant' => [
                'title'               => __( 'Validate Merchant Credentials', 'hepsipay' ),
                'type'                => 'button',
                'class'               => 'checkMerchant',
                'description'       => '',
                'desc_tip'          => true,
            ]
        ];
    }

    public function generate_button_html( $key, $data ) {
        $field    = $this->plugin_id . $this->id . '_' . $key;
        $defaults = array(
            'class'             => 'button-secondary',
            'css'               => '',
            'custom_attributes' => array(),
            'desc_tip'          => false,
            'description'       => '',
            'title'             => '',
        );

        $data = wp_parse_args( $data, $defaults );

        ob_start();
        ?>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr( $field ); ?>"><?php echo wp_kses_post( $data['title'] ); ?></label>
                <?php echo $this->get_tooltip_html( $data ); ?>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span><?php echo wp_kses_post( $data['title'] ); ?></span></legend>
                    <button class="<?php echo esc_attr( $data['class'] ); ?>" type="button" name="<?php echo esc_attr( $field ); ?>" id="<?php echo esc_attr( $field ); ?>" style="<?php echo esc_attr( $data['css'] ); ?>" <?php echo $this->get_custom_attribute_html( $data ); ?>><?php echo wp_kses_post( $data['title'] ); ?></button>
                    <?php echo $this->get_description_html( $data ); ?>
                </fieldset>
            </td>
        </tr>
        <script type="text/javascript">
            document.getElementsByClassName('checkMerchant')[0].addEventListener('click', checkMerchant, false);
            document.getElementsByClassName('force_3dSecure_debit')[0].value = '1';
            document.getElementsByClassName('endpoint_field')[0].value = 'https://pluginmanager.hepsipay.com/portal/web/api/v1';

            document.getElementsByClassName('force_3dSecure_debit')[0].disabled = 'disabled';
            document.getElementsByClassName('endpoint_field')[0].disabled = 'disabled';
            /*
             crypto-sha1-hmac.js
             CryptoJS v3.1.2
             code.google.com/p/crypto-js
             (c) 2009-2013 by Jeff Mott. All rights reserved.
             code.google.com/p/crypto-js/wiki/License
             */
            var CryptoJS=CryptoJS||function(g,l){var e={},d=e.lib={},m=function(){},k=d.Base={extend:function(a){m.prototype=this;var c=new m;a&&c.mixIn(a);c.hasOwnProperty("init")||(c.init=function(){c.$super.init.apply(this,arguments)});c.init.prototype=c;c.$super=this;return c},create:function(){var a=this.extend();a.init.apply(a,arguments);return a},init:function(){},mixIn:function(a){for(var c in a)a.hasOwnProperty(c)&&(this[c]=a[c]);a.hasOwnProperty("toString")&&(this.toString=a.toString)},clone:function(){return this.init.prototype.extend(this)}},
                    p=d.WordArray=k.extend({init:function(a,c){a=this.words=a||[];this.sigBytes=c!=l?c:4*a.length},toString:function(a){return(a||n).stringify(this)},concat:function(a){var c=this.words,q=a.words,f=this.sigBytes;a=a.sigBytes;this.clamp();if(f%4)for(var b=0;b<a;b++)c[f+b>>>2]|=(q[b>>>2]>>>24-8*(b%4)&255)<<24-8*((f+b)%4);else if(65535<q.length)for(b=0;b<a;b+=4)c[f+b>>>2]=q[b>>>2];else c.push.apply(c,q);this.sigBytes+=a;return this},clamp:function(){var a=this.words,c=this.sigBytes;a[c>>>2]&=4294967295<<
                        32-8*(c%4);a.length=g.ceil(c/4)},clone:function(){var a=k.clone.call(this);a.words=this.words.slice(0);return a},random:function(a){for(var c=[],b=0;b<a;b+=4)c.push(4294967296*g.random()|0);return new p.init(c,a)}}),b=e.enc={},n=b.Hex={stringify:function(a){var c=a.words;a=a.sigBytes;for(var b=[],f=0;f<a;f++){var d=c[f>>>2]>>>24-8*(f%4)&255;b.push((d>>>4).toString(16));b.push((d&15).toString(16))}return b.join("")},parse:function(a){for(var c=a.length,b=[],f=0;f<c;f+=2)b[f>>>3]|=parseInt(a.substr(f,
                            2),16)<<24-4*(f%8);return new p.init(b,c/2)}},j=b.Latin1={stringify:function(a){var c=a.words;a=a.sigBytes;for(var b=[],f=0;f<a;f++)b.push(String.fromCharCode(c[f>>>2]>>>24-8*(f%4)&255));return b.join("")},parse:function(a){for(var c=a.length,b=[],f=0;f<c;f++)b[f>>>2]|=(a.charCodeAt(f)&255)<<24-8*(f%4);return new p.init(b,c)}},h=b.Utf8={stringify:function(a){try{return decodeURIComponent(escape(j.stringify(a)))}catch(c){throw Error("Malformed UTF-8 data");}},parse:function(a){return j.parse(unescape(encodeURIComponent(a)))}},
                    r=d.BufferedBlockAlgorithm=k.extend({reset:function(){this._data=new p.init;this._nDataBytes=0},_append:function(a){"string"==typeof a&&(a=h.parse(a));this._data.concat(a);this._nDataBytes+=a.sigBytes},_process:function(a){var c=this._data,b=c.words,f=c.sigBytes,d=this.blockSize,e=f/(4*d),e=a?g.ceil(e):g.max((e|0)-this._minBufferSize,0);a=e*d;f=g.min(4*a,f);if(a){for(var k=0;k<a;k+=d)this._doProcessBlock(b,k);k=b.splice(0,a);c.sigBytes-=f}return new p.init(k,f)},clone:function(){var a=k.clone.call(this);
                        a._data=this._data.clone();return a},_minBufferSize:0});d.Hasher=r.extend({cfg:k.extend(),init:function(a){this.cfg=this.cfg.extend(a);this.reset()},reset:function(){r.reset.call(this);this._doReset()},update:function(a){this._append(a);this._process();return this},finalize:function(a){a&&this._append(a);return this._doFinalize()},blockSize:16,_createHelper:function(a){return function(b,d){return(new a.init(d)).finalize(b)}},_createHmacHelper:function(a){return function(b,d){return(new s.HMAC.init(a,
                    d)).finalize(b)}}});var s=e.algo={};return e}(Math);
            (function(){var g=CryptoJS,l=g.lib,e=l.WordArray,d=l.Hasher,m=[],l=g.algo.SHA1=d.extend({_doReset:function(){this._hash=new e.init([1732584193,4023233417,2562383102,271733878,3285377520])},_doProcessBlock:function(d,e){for(var b=this._hash.words,n=b[0],j=b[1],h=b[2],g=b[3],l=b[4],a=0;80>a;a++){if(16>a)m[a]=d[e+a]|0;else{var c=m[a-3]^m[a-8]^m[a-14]^m[a-16];m[a]=c<<1|c>>>31}c=(n<<5|n>>>27)+l+m[a];c=20>a?c+((j&h|~j&g)+1518500249):40>a?c+((j^h^g)+1859775393):60>a?c+((j&h|j&g|h&g)-1894007588):c+((j^h^
                g)-899497514);l=g;g=h;h=j<<30|j>>>2;j=n;n=c}b[0]=b[0]+n|0;b[1]=b[1]+j|0;b[2]=b[2]+h|0;b[3]=b[3]+g|0;b[4]=b[4]+l|0},_doFinalize:function(){var d=this._data,e=d.words,b=8*this._nDataBytes,g=8*d.sigBytes;e[g>>>5]|=128<<24-g%32;e[(g+64>>>9<<4)+14]=Math.floor(b/4294967296);e[(g+64>>>9<<4)+15]=b;d.sigBytes=4*e.length;this._process();return this._hash},clone:function(){var e=d.clone.call(this);e._hash=this._hash.clone();return e}});g.SHA1=d._createHelper(l);g.HmacSHA1=d._createHmacHelper(l)})();
            (function(){var g=CryptoJS,l=g.enc.Utf8;g.algo.HMAC=g.lib.Base.extend({init:function(e,d){e=this._hasher=new e.init;"string"==typeof d&&(d=l.parse(d));var g=e.blockSize,k=4*g;d.sigBytes>k&&(d=e.finalize(d));d.clamp();for(var p=this._oKey=d.clone(),b=this._iKey=d.clone(),n=p.words,j=b.words,h=0;h<g;h++)n[h]^=1549556828,j[h]^=909522486;p.sigBytes=b.sigBytes=k;this.reset()},reset:function(){var e=this._hasher;e.reset();e.update(this._iKey)},update:function(e){this._hasher.update(e);return this},finalize:function(e){var d=
                this._hasher;e=d.finalize(e);d.reset();return d.finalize(this._oKey.clone().concat(e))}})})();


            END_POINT = 'woocommerce_woo_gateway_hepsipay_endpoint';
            MERCHANT  = 'woocommerce_woo_gateway_hepsipay_username';
            PASSWORD  = 'woocommerce_woo_gateway_hepsipay_password';
            FormId    = 'mainform';

            function checkMerchant() {
                var endPoint = document.getElementById(END_POINT).value;
                var merchant = document.getElementById(MERCHANT).value;
                var password = document.getElementById(PASSWORD).value;

                //build params array
                var params = {
                    client_ip: "::1",//there is no client ip yet
                    language: "tr",
                    merchant: merchant,
                    type: "Echo",
                };

                //generate hash code
                var hashString = "";
                for (var index in params) {
                    var value = params[index];
                    hashString = hashString + value.length + value;
                }
                params["hash"] = CryptoJS.HmacSHA1(hashString, password);

                // construct a form with hidden inputs
                var form = document.createElement("form");
                form.target = FormId+Math.random();
                form.action = endPoint+'/html?r='+Math.random();
                form.method = "POST";

                // hidden inputs
                for (var index in params) {
                    var value = params[index];
                    var input = document.createElement("input");
                    input.type = "hidden";
                    input.name = index;
                    input.value = value;
                    form.appendChild(input);
                }

                document.body.appendChild(form);
                form.submit();
            }

        </script>
        <?php
        return ob_get_clean();
    }

    function process_payment( $order_id ) {
    	global $woocommerce;
        $order      = wc_get_order( $order_id );

        if(!$order) {
            wc_add_notice( __('Failed to process the payment because of invalid order', 'hepsipay'), 'error' );
            return array(
                'result' => 'error'
            );
        }

        if($order) {
            $checkout_payment_url = $order->get_checkout_payment_url(true);

            return array(
                'result' => 'success',
                'redirect' => add_query_arg(
                    array(
                        'order-pay' => $order->id,
                        'key' => $order->order_key,
                    ),
                    $checkout_payment_url
                ),
            );
        }

        wc_add_notice( __('Failed to process the payment because of invalid order', 'hepsipay'), 'error' );
        return null;

    }

    public function process_refund( $order_id, $amount = null,$reason = '' )
    {

        if(!isset($amount)) {
            return false;
        }
        $order = wc_get_order($order_id);

        if($order) {
            // $xid = $order->get_transaction_id();
            $xid = get_post_meta( $order->id, '_hepsipay_transaction_id', true );
            if(empty($xid)) {
                $order->add_order_note(__('Can not refund this order because the transaction id is missing.', 'hepsipay'));
                return false;
            }

            $crcy = $order->get_order_currency();
            $response = $this->hepsipay()->refund($xid, $amount);
            if(isset($response['status']) && $response['status']) {
                $order->add_order_note("Refunding {$crcy} {$amount} succeeded. Transaction Id: ".$response['transaction_id']);
                return true;
            }
            $error = $this->getErrorMessage($response,"Unknown error occured");
            $order->add_order_note("Refunding {$crcy} {$amount} failed. ".$error);
        }

        return false;
    }

    public function receipt_page($order_id)
    {
        $o = new WC_Order;
        $order = wc_get_order(isset($order_id) ? $order_id : false);
        if($order===false) {
            throw new \Exception('Invalid request, the order is not recognized.');
        }

        $data = [];
        do_action( 'woocommerce_credit_card_form_start', $this->id );

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $_POST;
            array_walk_recursive($data, function(&$item) {
                $item = sanitize_text_field($item);
            });

            $errors = $this->validatePaymentForm($data);
            if($errors !== true) {
                foreach ($errors as $err) {
                    wc_add_notice($err, 'error');
                }
            } else {
                $this->sendPayment($order, $data);
            }
        }

        $this->renderView('views/payment-form.php', [
            'this'=>$this,
            'id' => esc_attr($this->id),
            'order' => $order,
            'args' => isset($args)?$args:[],
            'form' => $data,
            'total_selector' => $this->get_option('total_selector'),
            'currency_class' => $this->get_option('currency_class'),
            'currency_symbol' => get_woocommerce_currency_symbol($order->get_order_currency()),
            'custom_css' => $this->get_option('custom_css'),
            'enable_3dSecure' => intval($this->enable_3dSecure) === 1,
            'force_3dSecure' => intval($this->force_3dSecure) === 1,
            'force_3dSecure_debit' => intval($this->force_3dSecure_debit) === 1,
            'enable_installment' => intval($this->enable_installment)===1,
            'enable_commission' => intval($this->enable_commission)===1,
            //todo: hepsipay - extra int
            'enable_extra_installment' => false,
            //todo: hepsipay - bkm
            'enable_bkm' => false,
        ]);
        do_action( 'woocommerce_credit_card_form_end', $this->id );
    }

    protected function sendPayment($order, $data)
    {
        $use3d               = 0;
        $installments        = 1;
        $card                = isset($data['card']) ? $data['card'] : null;
        $extraInsCampaignId  = isset($data['campaign_id']) ? $data['campaign_id'] : null;

        if($this->enable_3dSecure && isset($data['use3d'])) {
            $use3d = ($data['use3d']=="true");
        }

        if($this->force_3dSecure) {
            $use3d = true;
        }

        if($this->force_3dSecure_debit) {
            $bin = str_replace(' ', '', $card['pan']);
            $bin = substr($bin, 0, 6);
            $cardInfo = $this->hepsipay()->bin($bin);
            if($cardInfo['status']){
                $cardInfo = $cardInfo['data'];
                if(
                    !isset($cardInfo['type']) OR
                    $cardInfo['type'] != 'CREDIT' OR
                    $cardInfo['type'] == null
                ) $use3d = true;
            }else{
                $use3d = true;
            }
        }

        if($this->enable_installment && isset($data['installment'])) {
            $installments = intval($data['installment']);
            $installments = $installments <=0 ? 1 : $installments;
        }

        $fname = $order->billing_first_name;
        $lname = $order->billing_last_name;
        $order->update_status('wc-pending', 'Process payment by Hepsipay');

        $request = [
            'total'                 => $order->get_total(),
            'currency'              => $order->get_order_currency(),
            'installments'          => $installments,
            'passive_data'          => $order->id,//json_encode(['order-id' => $order->id]),
            'cc_name'               => $card['holder'],
            'cc_number'             => str_replace(' ', '', $card['pan']),
            'cc_month'              => $card['month'],
            'cc_year'               => $card['year'],
            'cc_cvc'                => $card['cvc'],
            'customer_firstname'    => $fname,
            'customer_lastname'     => $lname,
            'customer_email'        => $order->billing_email,
            'customer_phone'        => $order->billing_phone,
            'payment_title'         => "{$fname} {$lname} | order $order->id | ".$order->get_total().$order->get_order_currency(),
        ];

        $bank_id = isset($data['bank']) ? $data['bank'] : null;
        $gateway = isset($data['gateway']) ? $data['gateway'] : null;

        if(!isset($gateway, $bank_id) AND $installments > 1) {
            wc_add_notice( __('Invalid installment information.', 'hepsipay'), 'error' );
            return;
        }

        $total = $order->get_total();
        if($this->enable_commission){
            $fee = $this->hepsipay()->getCommission($total, $bank_id, $installments);
            WC()->session->set('installment_fee',    $fee );
            $total = $total + $fee;
            $total = number_format($total, 2, '.', '');
            $request['total'] = $total;
        }else{
            WC()->session->set('installment_fee',    0);
        }

        if($bank_id != '')              $request['bank_id']     = $bank_id;
        if($gateway != '')              $request['gateway']     = $gateway;
        if(isset($extraInsCampaignId))  $request['campaign_id'] = $extraInsCampaignId;

        if($use3d) {
            $checkout_url = $order->get_checkout_payment_url(true);
            $return_url = add_query_arg(['order-id'=>$order->id, 'wc-api'=>'WC_Gateway_Hepsipay'], $checkout_url);

            $request['use3d'] = 1;
            $request['return_url'] = $return_url;
        }

        //todo: hepsipay - bkm
        $data["useBKM"] = 0;
        if($data["useBKM"]){
        }


        $response = $this->hepsipay()->send('Sale', $request, true);

        if($response == null){
            $response = ['ErrorMSG' => $this->getErrorMessage($response,__('Invalid response received.', 'hepsipay'))];
            $message = $response['ErrorMSG'];
            wc_add_notice($message, 'error');
            return;
        }

        if(!$this->processPaymentResponse($order, $response)) {
            $message = $response['ErrorMSG'];
            wc_add_notice($message, 'error');
            return;
        }

        //todo: hepsipay - bkm
        if($use3d) {
            echo $response['html'];
        }else{
            $message = __('Alışverişinizde bizi tercih ettiğiniz için teşekkür ederiz. Ödemeniz başarıyla alınmıştır.', 'hepsipay');
            wc_add_notice($message);
            $thank_url = $order->get_checkout_order_received_url();
            wp_redirect($thank_url);
        }
        exit;
    }

    public function check_payment_response()
    {
        global $woocommerce;

        if ( ! defined( 'ABSPATH' ) ) {
            throw new \Exception('Wordpress is not running.');
        }
        if(!defined('WOOCOMMERCE_VERSION')) {
            throw new \Exception('WooCommerce is not running.');
        }

        $type       = "error";
        $title      = __('Bad request', 'hepsipay');
        $data       = $_POST;

        array_walk_recursive($data, function(&$item) {
            $item  = sanitize_text_field($item);
        });

        $tx             = isset($data['transaction_id']) ? $data['transaction_id'] : false;
        $order_id       = isset($data['passive_data']) ? $data['passive_data'] : (isset($_GET['order-id']) ? $_GET['order-id'] : null);
        $order          = wc_get_order($order_id);
        $redirect_url   = $woocommerce->cart->get_checkout_url();

        if(!isset($order)) {
            $message = __('Order not found.', 'hepsipay');
            if($tx) {
                $message = printf(__('The payment is done but your order not found. Your transaction id is "%1$s"', 'hepsipay'), $tx);
            }
        }
        else {
            if($this->processPaymentResponse($order, $data)) {
                $message = __('Transaction is succeeded.', 'hepsipay');
                wc_add_notice($message);
                $redirect_url = $order->get_checkout_order_received_url();
                wp_redirect($redirect_url);
                exit;
            }
            else {
                $order->update_status('wc-failed', '3D Payment failed');
                $message = $this->getErrorMessage($data,__('Unexpected error occurred while processing your request.', 'hepsipay'));
                $order->add_order_note($message);
            }
        }
        // error happened:
        wc_add_notice($message, 'error');
        wp_redirect($redirect_url);
    }

    protected function generateHash($params, $password){
        $arr = [];

        if(isset($params['hash']))  unset($params['hash']);
        if(isset($params['_csrf'])) unset($params['_csrf']);
        if(isset($params['data']))  unset($params['data']);
        if(isset($params['stack-trace']))  return '';

        foreach($params as $param_key=>$param_val){$arr[strtolower($param_key)]=$param_val;}
        ksort($arr);
        $hashString_char_count = "";

        foreach ($arr as $key=>$val) {
            if(!is_string($val)) continue;

            $l =  mb_strlen($val);
            if($l) $hashString_char_count .= $l . $val;
        }
        $hashString_char_count      = strtolower(hash_hmac("sha1", $hashString_char_count, $password));
        return $hashString_char_count;
    }

    protected function processPaymentResponse($order, $response)
    {
        $hash = $this->generateHash($response, $this->password);

        if(isset($response['status']) && $response['status']) {
            $xid = $response['transaction_id'];
            if(empty($xid)) {
                $order->add_order_note("Invalid response: Transaction id is missing.");
                return false;
            }//check hash only in latest response as 3D or normal response of normal trx
            elseif($hash != $response['hash'] AND !isset($response['html'])){
                $order->add_order_note("Invalid hash code.");
                return false;
            }

            $order->add_order_note("Payment Via Hepsipay, Transaction ID: {$xid}");

            $installments      = isset($response['installments'])?$response['installments']:1;
            $extraInstallments = isset($response['extra_installments'])?$response['extra_installments']:'';

            $this->saveOrderCommission($order, WC()->session->get('installment_fee'), $installments, $extraInstallments);
            unset(WC()->session->installment_fee); // there is no need any more

            $order->update_status('wc-processing', "Payment succeeded. Transaction ID: {$xid}");
            //$order->reduce_order_stock();
            $order->payment_complete($xid);
            WC()->cart->empty_cart();
            update_post_meta( $order->id, '_hepsipay_transaction_id', $xid );
            return true;
        } else {
            return false;
        }
    }

    protected function getErrorMessage($response, $default)
    {
        if(isset($response['ErrorMSG']) && strlen($response['ErrorMSG']))
            return $response['ErrorMSG'];
        return $default;
    }

    /**
     * @return boolyean|array true on success otherwise it resturns array of errors
     */
    protected function validatePaymentForm($form)
    {
        $errors = [];
        if(!isset($form['card']['holder']) || empty($form['card']['holder'])) {
            $errors[] = __('Card holder is invalid.', 'hepsipay');
        }
        if(!isset($form['card']['pan']) || empty($form['card']['pan'])) {
            $errors[] = __('Card number is invalid.', 'hepsipay');
        }elseif(!$this->checkCCNumber($form['card']['pan'])){
            $errors[] = __('Card number is invalid.', 'hepsipay');
        }

        if(!isset($form['card']['year']) || empty($form['card']['year'])) {
            $errors[] = __('The expiration date is invalid.', 'hepsipay');
        } else {
            $y = intval($form['card']['year']);
            $y += ($y>0 && $y < 99) ? 2000 : 0;
            if($y < date('Y')) {
                $errors[] = __('The expiration date is invalid.', 'hepsipay');
            }
        }

        if(!isset($form['card']['month']) || empty($form['card']['month'])) {
            $errors[] = __('The expiration date is invalid.', 'hepsipay');
        }else {
            $m = intval($form['card']['month']);
            if($m<1 || $m > 12) {
                $errors[] = __('The expiration date is invalid.', 'hepsipay');
            }
        }
        if(!$this->checkCCEXPDate($form['card']['month'], $form['card']['year'])){
            $errors[] = __('The expiration date is invalid.', 'hepsipay');
        }

        if(!isset($form['card']['cvc']) || empty($form['card']['cvc'])) {
            $errors[] = __('Card CVC cannot be empty.', 'hepsipay');
        }elseif(isset($form['card']['pan']) AND !$this->checkCCCVC($form['card']['pan'], $form['card']['cvc'])){
            $errors[] = __('Card CVC code is invalid.', 'hepsipay');
        }

        if($this->enable_installment && (!isset($form['installment']) || intval($form['installment'])<1)) {
            $errors[] = __('The installment value must be a positive integer.', 'hepsipay');
        }

        if(!$this->enable_bkm AND isset($form['useBKM']) AND $form['useBKM']) {
            $errors[] = __('BKM Express is inactive.', 'hepsipay');
        }

        if($this->enable_bkm AND isset($form['useBKM']) AND $form['useBKM']) {
            $errors = [];
        }


        return count($errors) ? $errors : true;
    }

    protected function saveOrderCommission($order, $amount, $installments, $extraInstallments)
    {
        if(!$this->enable_commission) return;

        //todo: hepsipay - extra int
        $installments  = __('Commission', 'hepsipay');

        $fee            = new stdClass();
        $fee->tax       = 0;
        $fee->tax_data  = [];
        $fee->amount    = $amount;
        $fee->taxable   = false;
        $fee->name      = $installments;
        $order->add_fee($fee);
        $order->calculate_totals();
    }

    protected function checkCCEXPDate($month, $year){
        if(strtotime('30-'.$month.'-'.$year) <= time()){
            return false;
        }
        return true;
    }

    protected function checkCCNumber($cardNumber){
        $cardNumber = preg_replace('/\D/', '', $cardNumber);
        $len = strlen($cardNumber);
        if ($len < 15 || $len > 16) {
            return false;
        }else {
            switch($cardNumber) {
                case(preg_match ('/^4/', $cardNumber) >= 1):
                    return true;
                    break;
                case(preg_match ('/^5[1-5]/', $cardNumber) >= 1):
                    return true;
                    break;
                case(preg_match ('/^6/', $cardNumber) >= 1):
                    return true;
                    break;
                case(preg_match ('/^9/', $cardNumber) >= 1):
                    return true;
                    break;
                default:
                    return false;
                    break;
            }
        }
    }

    protected function checkCCCVC($cardNumber, $cvc){
        // Get the first number of the credit card so we know how many digits to look for
        $firstnumber = (int) substr($cardNumber, 0, 1);
        if ($firstnumber === 3){
            if (!preg_match("/^\d{4}$/", $cvc)){
                // The credit card is an American Express card but does not have a four digit CVV code
                return false;
            }
        }
        else if (!preg_match("/^\d{3}$/", $cvc)){
            // The credit card is a Visa, MasterCard, or Discover Card card but does not have a three digit CVV code
            return false;
        }
        return true;
    }

    protected  function renderView($_viewFile_,$_data_=null,$_return_=false)
    {
        if(is_array($_data_)) {
			extract($_data_,EXTR_PREFIX_SAME,'data');
        } else {
			$data=$_data_;
        }
		if($_return_) {
			ob_start();
			ob_implicit_flush(false);
			require($_viewFile_);
			return ob_get_clean();
		}
		else {
			require($_viewFile_);
        }
    }

    protected static function getAddresses($domain) {
        $records = @dns_get_record($domain);
        $res = array();
        if(!is_array($records)) return $res;
        foreach ($records as $r) {
            if ($r['host'] != $domain) continue; // glue entry
            if (!isset($r['type'])) continue; // DNSSec

            if ($r['type'] == 'A') $res['ip'] = $r['ip'];
            if ($r['type'] == 'AAAA') $res['ipv6'] = $r['ipv6'];
        }
        return $res;
    }

    protected static function getAddresses_www($domain) {
        $res = self::getAddresses($domain);
        if (count($res) == 0) {
            $res = self::getAddresses('www.' . $domain);
        }

        if (count($res) == 0) {
            $res = 'www.' . $domain;
        }

        return $res;
    }

}
