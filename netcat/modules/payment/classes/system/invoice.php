<?php
require_once "InvoiceSDK/CREATE_PAYMENT.php";
require_once "InvoiceSDK/CREATE_TERMINAL.php";
require_once "InvoiceSDK/common/SETTINGS.php";
require_once "InvoiceSDK/common/ORDER.php";
require_once "InvoiceSDK/RestClient.php";

class nc_payment_system_invoice extends nc_payment_system
{
    /**
     * @var RestClient $invoiceClient
     */
    private $invoiceClient;

    protected $accepted_currencies = array('RUR' => 'RUR');
    protected $currency_map = array('RUR' => 'RUR');

    protected $settings = array(
        'API_KEY' => null,
        'LOGIN' => null,
    );

    public function execute_payment_request(nc_payment_invoice $invoice)
    {
        ob_end_clean();
        header("Location: " . $this->get_pay_request_url($invoice));
        exit;
    }

    /**
     * @param nc_payment_invoice $invoice
     * @return string
     * @throws Exception
     */
    protected function get_pay_request_url(nc_payment_invoice $invoice)
    {
        $this->getRestClient();
        $this->checkOrCreateTerminal();

        $sum = $invoice->get_amount('%0.2F');

        $request = new CREATE_PAYMENT();
        $request->order = $this->getOrder($sum, $invoice);
        $request->settings = $this->getSettings();
        $request->receipt = $this->getReceipt();

        $response = $this->invoiceClient->CreatePayment($request);

        if ($response == null or isset($response->error)) throw new Exception("Payment Error: " . $response->description);

        return $response->payment_url;
    }

    /**
     * @return INVOICE_ORDER
     */
    public function getOrder($sum, $invoice)
    {
        $order = new INVOICE_ORDER();
        $order->amount = $sum;
        $order->id = $invoice->get_id() . "-" . bin2hex(random_bytes(5));
        $order->currency = "RUB";

        return $order;
    }

    /**
     * @return SETTINGS
     */
    private function getSettings()
    {
        $url = ((!empty($_SERVER['HTTPS'])) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];

        $settings = new SETTINGS();
        $settings->terminal_id = $this->checkOrCreateTerminal();
        $settings->success_url = $url;
        $settings->fail_url = $url;

        return $settings;
    }

    /**
     * @return ITEM
     */
    private function getReceipt()
    {
        $receipt = array();
        return $receipt;
    }

    /**
     * @param array $params
     * @return string
     */
    protected function make_query_string($params)
    {
        return http_build_query($params, '', '&');
    }

    public function validate_payment_request_parameters()
    {
    }

    /**
     * @param nc_payment_invoice $invoice
     */
    public function on_response(nc_payment_invoice $invoice = null)
    {
        $this->getRestClient();

        $postData = file_get_contents('php://input');
        $notification = json_decode($postData, true);

        $type = $notification["notification_type"];
        $id = strstr($notification["order"]["id"], "-", true);

        $signature = $notification["signature"];

        if ($signature != $this->getSignature($notification["id"], $notification["status"], $this->invoiceClient->apiKey)) {
            echo "Wrong signature";
            return;
        }

        if ($type == "pay") {

            if ($notification["status"] == "successful") {
                $this->pay($invoice);
                echo "OK";
                return;
            }
            if ($notification["status"] == "error") {
                $this->error($invoice);
                echo "ERROR";
                return;
            }
        }
    }

    /**
     * @param nc_payment_invoice $invoice
     */
    public function validate_payment_callback_response(nc_payment_invoice $invoice = null)
    {
    }

    public function pay($invoice)
    {
        $invoice->set('status', nc_payment_invoice::STATUS_SUCCESS);
        $invoice->save();

        $this->on_payment_success($invoice);
    }

    public function error($invoice)
    {
        $invoice->set('status', nc_payment_invoice::STATUS_CALLBACK_ERROR);
        $invoice->save();

        $this->on_payment_failure($invoice);
    }

    public function load_invoice_on_callback()
    {
        $postData = file_get_contents('php://input');
        $notification = json_decode($postData, true);

        $id = strstr($notification["order"]["id"], "-", true);
        return $this->load_invoice($id);
    }

    /**
     * @return CREATE_TERMINAL
     */
    public function createTerminal()
    {
        $this->getRestClient();

        $create_terminal = new CREATE_TERMINAL();
        $create_terminal->name = "NetCat";
        $create_terminal->description = "NetCat Terminal";
        $create_terminal->defaultPrice = 10;
        $create_terminal->type = "dynamical";

        $response = $this->invoiceClient->CreateTerminal($create_terminal);

        if ($response == null) throw new Exception("Terminal Error");
        if (isset($response->error)) throw new Exception("Terminal Error: " . $response->error);

        $this->saveTerminal($response->id);

        return $response->id;
    }

    public function checkOrCreateTerminal()
    {
        $tid = $this->getTerminal();

        if ($tid == null or empty($tid) or $tid == false) {
            $tid = $this->createTerminal();
        }

        return $tid;
    }

    public function getRestClient()
    {
        $this->invoiceClient = new RestClient($this->get_setting('LOGIN'), $this->get_setting('API_KEY'));
    }

    public function saveTerminal($id)
    {
        file_put_contents("invoice_tid", $id);
    }

    public function getTerminal()
    {
        if (!file_exists("invoice_tid")) return "";
        return file_get_contents("invoice_tid");
    }

    public function getSignature($id, $status, $key)
    {
        return md5($id . $status . $key);
    }
}
