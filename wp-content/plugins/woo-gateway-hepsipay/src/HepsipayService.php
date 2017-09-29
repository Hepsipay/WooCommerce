<?php
/**
 * Description of HepsipayService
 *
 * @author hepsipay
 */
class HepsipayService {
    public $username;
    public $password;
    public $endpoint;
    public $language;
    public $client_ip;
    public $enable_commission;
    const END_POINT = 'https://pluginmanager.hepsipay.com/portal/web/api/v1';

    public function __construct($config=[]) {
        if (!empty($config)) {
            foreach ($config as $name => $value) {
                if(!property_exists($this, $name)) {
                    throw new Exception(strtr('Property "{class}.{property}" is not defined.', array(
                        '{class}' => get_class($this),
                        '{property}' => $name,
                    )));
                }
                $this->$name = $value;
            }
        }
        $this->endpoint = self::END_POINT;
    }

    public function bin($bin)
    {
        return $this->send('Get', [
            'get_param' => 'Issuer',
            'bin' => $bin
        ]);
    }

    public function banks($data = [])
    {
        $installments = $this->send('Get', [
            'get_param' => 'Installments',
        ]);

        if($this->enable_commission){
            $installments['oneShotCommission'] = $this->oneShotCommission();
        }else{
            $installments['oneShotCommission'] = 0;
        }


        if(count($data)){
            if(!$this->enable_commission){
                foreach($installments["data"] as $bankItemIndex=>$bankItem){
                    foreach($bankItem['installments'] as $installmentIndex=>$installmentItem){
                        $installments["data"][$bankItemIndex]['installments'][$installmentIndex]['commission'] = 0;
                        $installments["data"][$bankItemIndex]['installments'][$installmentIndex]['percentage'] = '0%';
                    }
                }
            }

            $extraInstallmentsList = $this->extraInstallmentsList($data['currency']);
            if(isset($extraInstallmentsList["data"]["campaigns"])){
                foreach($extraInstallmentsList["data"]["campaigns"] as $extra_installments_row){
                    foreach($installments["data"] as $installmentsKey=>$installment_row){
                        foreach($installment_row['installments'] as $installment_row_inst_key=>$installment_row_inst){
                            if(
                                $extra_installments_row['bank_id']           == $installment_row['bank'] AND
                                $extra_installments_row['min_amount']        < ($data['total']*$extraInstallmentsList['data']['exchange_rate']) AND
                                $extra_installments_row['base_installments'] == $installment_row_inst['count'] AND
                                $extra_installments_row['status']            == 1 AND
                                $extra_installments_row['gateway']           == $installment_row['gateway']
                            ){
                                $installments["data"][$installmentsKey]['installments'][$installment_row_inst_key]['hasExtra'] = 1;
                            }else{
                                $installments["data"][$installmentsKey]['installments'][$installment_row_inst_key]['hasExtra'] = 0;
                            }

                        }

                    }
                }
            }
        }
        return $installments;
    }

    public function oneShotCommission()
    {
        $oneShotCommission = 0;
        return $oneShotCommission;
    }

    public function extraInstallments($data)
    {
        return $this->send('Get', [
            'get_param'       => 'ExtraInstallments',
            "total"           => $data['total'],
            "currency"        => $data['currency'],
            "installments"    => $data['count'],
            "bank_id"         => $data['bank'],
            "gateway"         => $data['gateway'],
        ]);
    }

    public function extraInstallmentsList($currency = false)
    {
        if($currency){
            return $this->send('Get', [
                'get_param'       => 'ExtraInstallmentsList',
                "exchange_rate"   => '1',
                "currency"        => $currency,
            ]);
        }else{
            return $this->send('Get', [
                'get_param'       => 'ExtraInstallmentsList',
            ]);
        }

    }

    public function getCommission($amount, $bankId, $installmentCount)
    {
        if(!$this->enable_commission) return 0;

        if($installmentCount===1) {
            return $this->oneShotCommission();
        }

        $bankId = strtolower($bankId);

        $banks = $this->banks();
        $valid = isset($banks['status'], $banks['data']) && $banks['status'] && is_array($banks['data']);
        if(!$valid) { return 0; }

        foreach ($banks['data'] as $b) {
            if(strtolower($b['bank']) === $bankId) {
                $installments = isset($b['installments']) ? $b['installments'] : null;
                if(!is_array($installments)) { return 0; }
                foreach($installments as $ins) {
                    if(isset($ins['count']) && $ins['count']==$installmentCount) {
                        $precentage = isset($ins['commission']) ? floatval(strtr($ins['commission'], ['%'=>''])) : 0;
                        return  floatVal($amount)*$precentage/100;
                    }
                }
                return 0;
            }
        }
        return 0;
    }

    public function refund($transaction_id, $amount)
    {
        return $this->send('Return', [
            'transaction_id' => $transaction_id,
            'total' => $amount
        ]);
    }

    public function send($op, $data, $return_json=true)
    {
        if(empty($this->client_ip)) {
            $this->client_ip = $_SERVER['REMOTE_ADDR'] ;
        }
        $data['type'] = $op;
        $data['merchant'] = $this->username;
        $data['language'] = $this->language;
        $data['client_ip'] = $this->client_ip;
        //return $data;
        $data['hash'] = $this->hash($data);
//        print_r($data);
//        exit;
        $content = self::post($this->endpoint, $data);

        if($return_json){
            return json_decode($content, true);
        }
        return $content;
    }

    private function hash($data)
    {
        $message = '';
        ksort($data);
        foreach($data as $key=>$value) {
            $message .= mb_strlen($value).$value;
        }
        $hash = hash_hmac('sha1', $message, $this->password);

        return $hash;
    }

    public static function post($url, $data=array())
    {
        $options = array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_ENCODING       => "",
            CURLOPT_USERAGENT      => "curl",
            CURLOPT_AUTOREFERER    => true,
            CURLOPT_CONNECTTIMEOUT => 120,
            CURLOPT_TIMEOUT        => 120,
            CURLOPT_CUSTOMREQUEST  => "POST",
        );

        $curl = curl_init($url);
        @curl_setopt_array($curl, $options);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));

        //ssl
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);

        $content  = curl_exec($curl);
        $error = curl_error($curl);
        curl_close($curl);

        if($content === false) {
            $content = json_encode(
                [
                'status'   => 0,
                'ErrorMSG' => 'Hepsipay gateway connection error',
                'data'     => []
                ]
            );
        }

        return $content;
    }

}
