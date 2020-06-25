<?php
require "InvoiceSDK/RestClient.php";
require "InvoiceSDK/common/SETTINGS.php";
require "InvoiceSDK/common/ORDER.php";
require "InvoiceSDK/CREATE_TERMINAL.php";
require "InvoiceSDK/CREATE_PAYMENT.php";

class nc_payment_system_invoice extends nc_payment_system {

    protected $accepted_currencies = array('RUB');
    protected $currency_map = array('RUR' => 'RUB');

    protected $settings = array(
        'API_KEY' => null,
        'LOGIN' => null,
    );

    public function execute_payment_request(nc_payment_invoice $invoice) {
        ob_end_clean();
        header("Location: " . $this->get_pay_request_url($invoice));
        exit;
    }

    /**
     * @param nc_payment_invoice $invoice
     * @return string
     * @throws Exception
     */
    protected function get_pay_request_url(nc_payment_invoice $invoice) {

        $sum = $invoice->get_amount('%0.2F');

        $client = $this->getRestClient();
        $order = new INVOICE_ORDER($sum);
        $order->id = $invoice->get_id();
        $settings = new SETTINGS($this->checkOrCreateTerminal());

        $request = new CREATE_PAYMENT($order, $settings, []);
        $response = $client->CreatePayment($request);

        if($response == null or isset($response->error)) throw new Exception("Payment Error: " . $response->description);

        return $response->payment_url;
    }

    /**
     * @param array $params
     * @return string
     */
    protected function make_query_string($params) {
        return http_build_query($params, '', '&');
    }

    public function validate_payment_request_parameters() {

    }

    /**
     * @param nc_payment_invoice $invoice
     */
    public function on_response(nc_payment_invoice $invoice = null) {
        $postData = file_get_contents('php://input');
        $notification = json_decode($postData, true);

        $type = $notification["notification_type"];
        $id = $notification["order"]["id"];

        $signature = $notification["signature"];

        if($signature != $this->getSignature($notification["id"], $notification["status"], $this->getRestClient()->apiKey)) {
            echo "Wrong signature";
            return;
        }

        if($type == "pay") {

            if($notification["status"] == "successful") {
                $this->pay($invoice);
                echo "OK";
                return;
            }
            if($notification["status"] == "error") {
                $this->error($invoice);
                echo "ERROR";
                return;
            }
        }
    }

    /**
     * @param nc_payment_invoice $invoice
     */
    public function validate_payment_callback_response(nc_payment_invoice $invoice = null) {
    }

    public function pay($invoice) {
        $invoice->set('status', nc_payment_invoice::STATUS_SUCCESS);
        $invoice->save();

        $this->on_payment_success($invoice);
    }

    public function error($invoice) {
        $invoice->set('status', nc_payment_invoice::STATUS_CALLBACK_ERROR);
        $invoice->save();

        $this->on_payment_failure($invoice);
    }

    public function load_invoice_on_callback() {
        $postData = file_get_contents('php://input');
        $notification = json_decode($postData, true);

        $id = $notification["order"]["id"];
        return $this->load_invoice($id);
    }

    public function createTerminal() {
        $client = $this->getRestClient();
        $request = new CREATE_TERMINAL("NetCat Terminal");

        $response = $client->CreateTerminal($request);

        if($response == null) throw new Exception("Terminal Error");
        if(isset($response->error)) throw new Exception("Terminal Error: ".$response->error);

        $this->saveTerminal($response->id);

        return $response->id;
    }

    public function checkOrCreateTerminal() {
        $tid = $this->getTerminal();

        if($tid == null or empty($tid) or $tid == false) {
            $tid = $this->createTerminal();
        }

        return $tid;
    }

    public function getRestClient() {
        return new RestClient($this->get_setting('LOGIN'), $this->get_setting('API_KEY'));
    }

    public function saveTerminal($id) {
        file_put_contents("invoice_tid", $id);
    }

    public function getTerminal() {
        if(!file_exists("invoice_tid")) return "";
        return file_get_contents("invoice_tid");
    }

    public function getSignature($id, $status, $key) {
        return md5($id.$status.$key);
    }
}