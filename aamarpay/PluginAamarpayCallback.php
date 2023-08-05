<?php
require_once 'modules/admin/models/PluginCallback.php';
require_once 'modules/billing/models/class.gateway.plugin.php';
require_once 'modules/billing/models/Invoice.php';

class PluginAamarpayCallback extends PluginCallback
{

    function processCallback()
    {
        // var_dump($_REQUEST);
        // die();
        if (isset($_REQUEST['mer_txnid']) && !empty($_REQUEST['mer_txnid'])) {
            $request_id=$_REQUEST['mer_txnid'];
            $cPlugin = new Plugin('', 'aamarpay', $this->user);
            $TestMode = trim($cPlugin->GetPluginVariable("plugin_aamarpay_Test Mode"));
            $store_id = trim($cPlugin->GetPluginVariable("plugin_aamarpay_Store Id"));
            $store_passwd = trim($cPlugin->GetPluginVariable("plugin_aamarpay_Signature Key"));
        // var_dump($store_passwd);
        // die();
            if ($TestMode == 1) {
                $check_url = "https://sandbox.aamarpay.com";
            } else {
                $check_url = "https://secure.aamarpay.com";
            }
            
            $curl = curl_init();
            $url = $check_url . "/api/v1/trxcheck/request.php?request_id=$request_id&store_id=$store_id&signature_key=$store_passwd&type=json";
            curl_setopt_array($curl, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
            ]);
    
            $response = curl_exec($curl);
    
            curl_close($curl);
            // var_dump($response);
            // die();
            $data = json_decode($response, true);
    
            $status_code = $data["status_code"];
            $pay_status = $data["pay_status"];
            $price = $data["amount"]." ".$data["currency"];
            $amount=$data["amount"];
            // var_dump($pay_status);
            // die();
            $cPlugin = new Plugin($request_id, 'aamarpay', $this->user);
            $cPlugin->setAmount($amount);
            $cPlugin->setAction('charge');
            // var_dump($cPlugin->IsUnpaid());
            // die();
                    
            if ($pay_status === "Successful") {

                if ($cPlugin->IsUnpaid() == 1) {
                    $transaction = " aamarpay payment of $price Successful (Order ID: " . $request_id . ")";
                    $cPlugin->PaymentAccepted($amount, $transaction);
                    $returnURL = CE_Lib::getSoftwareURL() . "/index.php?fuse=billing&paid=1&controller=invoice&view=invoice&id=" . $request_id;
                    header("Location: " . $returnURL);
                    exit;
                } else {
                    echo "Invoice already paid";
                    return;
                }
            } else {
                $transaction = " aamarpay payment of $price Failed (Order ID: " . $request_id . ")";
                $cPlugin->PaymentRejected($transaction);
                $returnURL = CE_Lib::getSoftwareURL() ."/index.php?fuse=billing&controller=invoice&view=invoice&id=".$request_id;
                header("Location: " . $returnURL);
                return;
            }
            return;
           
        }
        return;
    }
    
}