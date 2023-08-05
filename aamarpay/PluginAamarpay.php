<?php
require_once 'modules/admin/models/GatewayPlugin.php';
class PluginAamarpay extends GatewayPlugin
{
    function getVariables()
    {
        $variables = array(
            lang("Plugin Name") => array(
                "type"          => "hidden",
                "description"   => "",
                "value"         => "aamarPay"
            ),
            lang('Signup Name') => array(
                'type'        => 'text',
                'description' => lang('Select the name to display in the signup process for this payment type. Example: eCheck or Credit Card.'),
                'value'       => 'aamarPay'
            ),
            lang("Store Id") => array(
                "type"          => "text",
                "description"   => "Enter you Store Id Here",
                "value"         => ""
            ),
            lang("Signature Key") => array(
                "type"          => "text",
                "description"   => "Enter you Signature Key Here",
                "value"         => ""
            ),
            lang("Test Mode") => array(
                "type"          => "yesno",
                "description"   => "Enable Test Mode",
                "value"         => ""
            ),
        );
        return $variables;
    }
    function singlepayment($params)
    {
        
        // var_dump($params);
        // die();
        $query = "SELECT * FROM currency WHERE abrv = '" . $params['userCurrency'] . "'";
        $result = $this->db->query($query);
        $row = $result->fetch();
        $prefix = $row['symbol'];

        $invoiceId = $params['invoiceNumber'];
        $store_id=$params["plugin_aamarpay_Store Id"];
        $signature_key=$params["plugin_aamarpay_Signature Key"];
        $description = $params['invoiceDescription'];
        $amount = sprintf("%01.2f", round($params["invoiceTotal"], 2));
        $systemUrl = $params['companyURL'];
        $firstname = $params['userFirstName'];
        $lastname = $params['userLastName'];
        $email = $params['userEmail'];
        $address1=$params["userAddress"];
        $city=$params["userCity"];
        $state=$params["userState"];
        $postcode=$params["userZipcode"];
        $country=$params["userCountry"];
        $phone=$params["userPhone"];
        $bar = "/";
        
        if (substr(CE_Lib::getSoftwareURL(), -1) == "/") {
            $bar = "";
        }
        $baseURL = CE_Lib::getSoftwareURL() . $bar;
        // $CallbackURL = $baseURL . "plugins/gateways/aamarPay/callback.php";

        $currency = $params['userCurrency'];

        $TestMode = $params['plugin_aamarpay_Test Mode'];

        $cancel_url = $params['invoiceviewURLCancel'];

        $sanbox_url  = 'https://sandbox.aamarpay.com/jsonpost.php';
        $live_url    = 'https://secure.aamarpay.com/jsonpost.php';

        if ($TestMode == 1) {
            $payment_url = $sanbox_url;
        } else {
            $payment_url = $live_url;
        }

        $CallbackURL = "https://manage.networxhost.com/plugins/gateways/aamarpay/callback.php";
        $cancel_url=$params["invoiceviewURLCancel"];
        
        // var_dump($invoiceId);
        // die();
        $data = [
            "store_id" => $store_id,
            "tran_id" => $invoiceId,
            "success_url" => $CallbackURL,
            "fail_url" => $CallbackURL,
            "cancel_url" => $cancel_url,
            "amount" => $amount,
            "currency" => $currency,
            "signature_key" => $signature_key,
            "desc" => $description,
            "cus_name" => $firstname . " " . $lastname,
            "cus_email" => $email,
            "cus_add1" => $address1,
            "cus_add2" => " ",
            "cus_city" => $city,
            "cus_state" => $state,
            "cus_postcode" => $postcode,
            "cus_country" => $country,
            "cus_phone" => $phone,
            "type" => "json"
        ];
       
        $json_data = json_encode($data);


        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $payment_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $json_data,
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json"
            ],
        ]);

        $response = curl_exec($curl);

        curl_close($curl);
        
        // var_dump($response);
        // die();

        $responseObj = json_decode($response, true);
        if (isset($responseObj["payment_url"]) && !empty($responseObj["payment_url"])) {
            // Payment URL is available, so redirect to it
            $paymentUrl = $responseObj["payment_url"];
            header('Location: ' . $paymentUrl);
            exit;
        } else {
            // No payment URL found, return the original response
            echo $response;
            exit;
        }
       
       
       
    }
    function credit($params)
    {
    }
    function get_client_ip()
    {
        $ipaddress = '';
        if (getenv('HTTP_CLIENT_IP')) {
            $ipaddress = getenv('HTTP_CLIENT_IP');
        } elseif (getenv('HTTP_X_FORWARDED_FOR')) {
            $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
        } elseif (getenv('HTTP_X_FORWARDED')) {
            $ipaddress = getenv('HTTP_X_FORWARDED');
        } elseif (getenv('HTTP_FORWARDED_FOR')) {
            $ipaddress = getenv('HTTP_FORWARDED_FOR');
        } elseif (getenv('HTTP_FORWARDED')) {
            $ipaddress = getenv('HTTP_FORWARDED');
        } elseif (getenv('REMOTE_ADDR')) {
            $ipaddress = getenv('REMOTE_ADDR');
        } else {
            $ipaddress = 'UNKNOWN';
        }
        return $ipaddress;
    }
   
}