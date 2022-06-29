<?php

include_once $_SERVER['DOCUMENT_ROOT'] . '/settings.php';

class Paypal
{
    private $name;
    private $client_id;
    private $secret;
    private string $currency;
    private string $return;
    private PDO $dbh;

    public function __construct($currency, $dbh) {
        $this->currency = $currency;
        $this->dbh = $dbh;
    }

    public function createOrder($value) {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://api-m.paypal.com/v2/checkout/orders');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, '{
        "intent": "CAPTURE",
        "application_context": {
             "return_url": "' . $this->return . '"
         },
        "purchase_units":   [
            {
                "amount": {
                    "currency_code": "' . $this->currency . '",
                    "value": "' . $value . '"
                }
            }
         ]
       }');

        $headers = array();
        $headers[] = 'Content-Type: application/json';
        $headers[] = 'Authorization: Bearer ' . $this->createToken();
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        curl_close($ch);
        return json_decode($result);
    }

    public function newShop($client_id, $secret) {
        $this->name = $name;
        $this->client_id = $client_id;
        $this->secret = $secret;
    }

    public function setReturnUrl($return) {
        $this->return = $return;
    }

    private function createToken() {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://api-m.paypal.com/v1/oauth2/token');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");
        curl_setopt($ch, CURLOPT_USERPWD, "{$this->client_id}:{$this->secret}");

        $headers = array();
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        curl_close($ch);
        return json_decode($result)->access_token;
    }

}
