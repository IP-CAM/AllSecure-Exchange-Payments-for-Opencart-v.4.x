<?php
namespace Opencart\Admin\Controller\Extension\Allsecureexchange\Payment;

require_once DIR_EXTENSION.'allsecureexchange/system/library/psr/log/initClientAutoload.php';
require_once DIR_EXTENSION.'allsecureexchange/system/library/exchange-php-client/initClientAutoload.php';

use Exchange\Client\Client as AllsecureClient;
use Exchange\Client\Transaction\Capture as AllsecureCapture;
use Exchange\Client\Transaction\Refund as AllsecureRefund;
use Exchange\Client\Transaction\VoidTransaction as AllsecureVoidTransaction;
use Exchange\Client\Transaction\Result as AllsecureResult;

class Allsecureexchange extends \Opencart\System\Engine\Controller {
    private $error = array();
    const STATE_VOID = 16;
    const STATE_REFUND = 11;

    public function index() {
        $this->load->language('extension/allsecureexchange/payment/allsecureexchange');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('payment_allsecureexchange', $this->request->post);
            $this->session->data['success'] = $this->language->get('text_success');
            $data['success'] = $this->session->data['success'];
        }

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }
        
        if (isset($this->error['api_user'])) {
            $data['error_api_user'] = $this->error['api_user'];
        } else {
            $data['error_api_user'] = '';
        }
        
        if (isset($this->error['api_password'])) {
            $data['error_api_password'] = $this->error['api_password'];
        } else {
            $data['error_api_password'] = '';
        }
        
        if (isset($this->error['api_key'])) {
            $data['error_api_key'] = $this->error['api_key'];
        } else {
            $data['error_api_key'] = '';
        }

        if (isset($this->error['api_secret'])) {
            $data['error_api_secret'] = $this->error['api_secret'];
        } else {
            $data['error_api_secret'] = '';
        }

        if (isset($this->error['integrator_key'])) {
            $data['error_integrator_key'] = $this->error['integrator_key'];
        } else {
            $data['error_integrator_key'] = '';
        }

        if (isset($this->error['type_supported'])) {
            $data['error_card_supported'] = $this->error['type_supported'];
        } else {
            $data['error_card_supported'] = '';
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/allsecureexchange/payment/allsecureexchange', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['action'] = $this->url->link('extension/allsecureexchange/payment/allsecureexchange', 'user_token=' . $this->session->data['user_token'], true);

        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);
        
        if (isset($this->request->post['payment_allsecureexchange_api_user'])) {
            $data['payment_allsecureexchange_api_user'] = $this->request->post['payment_allsecureexchange_api_user'];
        } else {
            $data['payment_allsecureexchange_api_user'] = $this->config->get('payment_allsecureexchange_api_user');
        }

        if (isset($this->request->post['payment_allsecureexchange_api_password'])) {
            $data['payment_allsecureexchange_api_password'] = $this->request->post['payment_allsecureexchange_api_password'];
        } else {
            $data['payment_allsecureexchange_api_password'] = $this->config->get('payment_allsecureexchange_api_password');
        }

        if (isset($this->request->post['payment_allsecureexchange_api_key'])) {
            $data['payment_allsecureexchange_api_key'] = $this->request->post['payment_allsecureexchange_api_key'];
        } else {
            $data['payment_allsecureexchange_api_key'] = $this->config->get('payment_allsecureexchange_api_key');
        }

        if (isset($this->request->post['payment_allsecureexchange_api_secret'])) {
            $data['payment_allsecureexchange_api_secret'] = $this->request->post['payment_allsecureexchange_api_secret'];
        } else {
            $data['payment_allsecureexchange_api_secret'] = $this->config->get('payment_allsecureexchange_api_secret');
        }
        
        if (isset($this->request->post['payment_allsecureexchange_integrator_key'])) {
            $data['payment_allsecureexchange_integrator_key'] = $this->request->post['payment_allsecureexchange_integrator_key'];
        } else {
            $data['payment_allsecureexchange_integrator_key'] = $this->config->get('payment_allsecureexchange_integrator_key');
        }

        if (isset($this->request->post['payment_allsecureexchange_mode'])) {
            $data['payment_allsecureexchange_mode'] = $this->request->post['payment_allsecureexchange_mode'];
        } else {
            $data['payment_allsecureexchange_mode'] = $this->config->get('payment_allsecureexchange_mode');
        }

        if (isset($this->request->post['payment_allsecureexchange_order_status_id'])) {
            $data['payment_allsecureexchange_order_status_id'] = $this->request->post['payment_allsecureexchange_order_status_id'];
        } else {
            $data['payment_allsecureexchange_order_status_id'] = $this->config->get('payment_allsecureexchange_order_status_id');
        }

        if (isset($this->request->post['payment_allsecureexchange_logging'])) {
            $data['payment_allsecureexchange_logging'] = $this->request->post['payment_allsecureexchange_logging'];
        } else {
            $data['payment_allsecureexchange_logging'] = $this->config->get('payment_allsecureexchange_logging');
        }
        if (isset($this->request->post['payment_allsecureexchange_title'])) {
            $data['payment_allsecureexchange_title'] = $this->request->post['payment_allsecureexchange_title'];
        } else {
            $data['payment_allsecureexchange_title'] = $this->config->get('payment_allsecureexchange_title');
        }

        $data['cards_supported'] = $this->getCardsSupported();
        if (isset($this->request->post['payment_allsecureexchange_card_supported'])) {
            $data['payment_allsecureexchange_card_supported'] = $this->request->post['payment_allsecureexchange_card_supported'];
        } else {
            $payment_allsecureexchange_card_supported = $this->config->get('payment_allsecureexchange_card_supported');
            if (empty($payment_allsecureexchange_card_supported)) {
                $payment_allsecureexchange_card_supported = [];
            }
            $data['payment_allsecureexchange_card_supported'] = $payment_allsecureexchange_card_supported;
        }

        $this->load->model('localisation/order_status');

        $data['order_statuses'] = $this->getNewOrderStatuses($this->model_localisation_order_status->getOrderStatuses());

        if (isset($this->request->post['payment_allsecureexchange_geo_zone_id'])) {
            $data['payment_allsecureexchange_geo_zone_id'] = $this->request->post['payment_allsecureexchange_geo_zone_id'];
        } else {
            $data['payment_allsecureexchange_geo_zone_id'] = $this->config->get('payment_allsecureexchange_geo_zone_id');
        }

        $this->load->model('localisation/geo_zone');

        $data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

        if (isset($this->request->post['payment_allsecureexchange_status'])) {
            $data['payment_allsecureexchange_status'] = $this->request->post['payment_allsecureexchange_status'];
        } else {
            $data['payment_allsecureexchange_status'] = $this->config->get('payment_allsecureexchange_status');
        }

        if (isset($this->request->post['payment_allsecureexchange_sort_order'])) {
            $data['payment_allsecureexchange_sort_order'] = $this->request->post['payment_allsecureexchange_sort_order'];
        } else {
            $data['payment_allsecureexchange_sort_order'] = $this->config->get('payment_allsecureexchange_sort_order');
        }
        
        if (isset($this->request->post['payment_allsecureexchange_checkout'])) {
            $data['payment_allsecureexchange_checkout'] = $this->request->post['payment_allsecureexchange_checkout'];
        } else {
            $data['payment_allsecureexchange_checkout'] = $this->config->get('payment_allsecureexchange_checkout');
        }
        
        if (isset($this->request->post['payment_allsecureexchange_transaction_type'])) {
            $data['payment_allsecureexchange_transaction_type'] = $this->request->post['payment_allsecureexchange_transaction_type'];
        } else {
            $data['payment_allsecureexchange_transaction_type'] = $this->config->get('payment_allsecureexchange_transaction_type');
        }
        
        if (isset($this->request->post['payment_allsecureexchange_transaction_email'])) {
            $data['payment_allsecureexchange_transaction_email'] = $this->request->post['payment_allsecureexchange_transaction_email'];
        } else {
            $data['payment_allsecureexchange_transaction_email'] = $this->config->get('payment_allsecureexchange_transaction_email');
        }
        
        if (isset($this->request->post['payment_allsecureexchange_transaction_confirmation_page'])) {
            $data['payment_allsecureexchange_transaction_confirmation_page'] = $this->request->post['payment_allsecureexchange_transaction_confirmation_page'];
        } else {
            $data['payment_allsecureexchange_transaction_confirmation_page'] = $this->config->get('payment_allsecureexchange_transaction_confirmation_page');
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');


        $this->response->setOutput($this->load->view('extension/allsecureexchange/payment/allsecureexchange_settings', $data));
    }

    protected function validate()
    {
        if (!$this->user->hasPermission('modify', 'extension/allsecureexchange/payment/allsecureexchange')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        
        if (!$this->request->post['payment_allsecureexchange_api_user']) {
            $this->error['api_user'] = $this->language->get('error_api_user');
        }

        if (!$this->request->post['payment_allsecureexchange_api_password']) {
            $this->error['api_password'] = $this->language->get('error_api_password');
        }

        if (!$this->request->post['payment_allsecureexchange_api_key']) {
            $this->error['api_key'] = $this->language->get('error_api_key');
        }

        if (!$this->request->post['payment_allsecureexchange_api_secret']) {
            $this->error['api_secret'] = $this->language->get('error_api_secret');
        }
        
        if (!$this->request->post['payment_allsecureexchange_integrator_key']) {
            $this->error['integrator_key'] = $this->language->get('error_integrator_key');
        }

        if (!isset($_POST['payment_allsecureexchange_card_supported'])) {
            $this->error['type_supported'] = $this->language->get('error_card_supported');
        }

        return !$this->error;
    }
    
    public function getCardsSupported()
    {
        $this->load->language('extension/allsecureexchange/payment/allsecureexchange');
        $list = [
            [
                'value' => 'VISA',
                'label' => $this->language->get('text_VISA')
            ],
            [
                'value' => 'MASTERCARD',
                'label' => $this->language->get('text_MASTERCARD')
            ],
            [
                'value' => 'MAESTRO',
                'label' => $this->language->get('text_MAESTRO')
            ],
            [
                'value' => 'AMEX',
                'label' => $this->language->get('text_AMEX')
            ],
            [
                'value' => 'DINERS',
                'label' => $this->language->get('text_DINERS')
            ],
            [
                'value' => 'JCB',
                'label' => $this->language->get('text_JCB')
            ],
            [
                'value' => 'DINACARD',
                'label' => $this->language->get('text_DINA')
            ],
            [
                'value' => 'DISCOVER',
                'label' => $this->language->get('text_DISCOVER')
            ],
        ];
        return $list;
    }
    
    public function getNewOrderStatuses($statuses)
    {
        $result = array();
        $skipStatuses = array(
            'Canceled',
            'Canceled Reversal',
            'Chargeback',
            'Denied',
            'Expired',
            'Failed',
            'Refunded',
            'Reversed',
            'Voided',
            'Complete',
            'Shipped'
        );
        foreach ($statuses as $key => $status) {
            if (!in_array($status['name'], $skipStatuses)) {
                $result[] = $status;
            }
        }
        return $result;
    }
    
    public function install()
    {
        $this->load->model('extension/allsecureexchange/payment/allsecureexchange');
        $this->model_extension_allsecureexchange_payment_allsecureexchange->install();
    }

    public function uninstall()
    {
        $this->load->model('extension/allsecureexchange/payment/allsecureexchange');
        $this->model_extension_allsecureexchange_payment_allsecureexchange->uninstall();
    }
    
    /**
     * Get Allsecure API Client
     *
     * @return AllsecureClient
     */
    
    public function getClient()
    {
        $testMode = false;
        if ($this->config->get('payment_allsecureexchange_mode') == 'test') {
            $testMode = true;
        }
        
        if ($testMode) {
            AllsecureClient::setApiUrl($this->getTestApiUrl());
        } else {
            AllsecureClient::setApiUrl($this->getLiveApiUrl());
        }
        
        $api_user = $this->config->get('payment_allsecureexchange_api_user');
        if (!empty($api_user)) {
            $api_user = trim($api_user);
        }
        
        $api_password = $this->config->get('payment_allsecureexchange_api_password');
        if (!empty($api_password)) {
            $api_password = htmlspecialchars_decode($api_password);
        }
        
        $api_key = $this->config->get('payment_allsecureexchange_api_key');
        if (!empty($api_key)) {
            $api_key = trim($api_key);
        }
        
        $api_secret = $this->config->get('payment_allsecureexchange_api_secret');
        if (!empty($api_secret)) {
            $api_secret = trim($api_secret);
        }

        $client = new AllsecureClient(
            $api_user, 
            $api_password, 
            $api_key,
            $api_secret, 
            null,
            $testMode
        );

        return $client;
    }
   
    /**
     * Get Test API URL
     *
     * @return string
     */
    public function getTestApiUrl()
    {
        return 'https://asxgw.paymentsandbox.cloud/';
    }

    /**
     * Get Live API URL
     *
     * @return string
     */
    public function getLiveApiUrl()
    {
        return 'https://asxgw.com/';
    }
    
    /**
     * Get Encoded Order Id
     *
     * @param string $orderId
     * return string
     */
    public function encodeOrderId($orderId)
    {
        return $orderId . '-' . date('YmdHis') . substr(sha1(uniqid()), 0, 10);
    }

    /**
     * Get Decoded Order Id
     *
     * @param string $orderId
     * return string
     */
    public function decodeOrderId($orderId)
    {
        if (strpos($orderId, '-') === false) {
            return $orderId;
        }

        $orderIdParts = explode('-', $orderId);

        if(count($orderIdParts) === 2) {
            $orderId = $orderIdParts[0];
        }

        if(count($orderIdParts) === 3) {
            $orderId = $orderIdParts[1];
        }

        return $orderId;
    }
    
    /**
     * Add payment information to admin order view page
     * 
     * @param $route
     * @param $data
     * @param $output
     * 
     * @return mixed
     */
    public function order_info(&$route, &$data, &$output)
    {
        $order_id = $this->request->get['order_id'];
        
        $this->load->model('sale/order');
        $this->load->model('localisation/order_status');
        $this->load->language('extension/allsecureexchange/payment/allsecureexchange');
        $this->load->model('extension/allsecureexchange/payment/allsecureexchange');
        
        $this->payment = $this->model_extension_allsecureexchange_payment_allsecureexchange;
        
        $is_allsecureexchange_order = false;
        $tab_key = -1;
        if ($this->payment->isVersion402()) {
            $data['tabs'][] = array('code' => 'allsecureexchange', 'content' => '', 'title' => $this->language->get('heading_title'));
        }
        
        if (isset($data['tabs'])) {
            foreach ($data['tabs'] as $key => $tabCol) {
                if ($tabCol['code'] == 'allsecureexchange') {
                    
                    $order = $this->model_sale_order->getOrder($order_id);
                    $current_order_status_id = $order['order_status_id'];

                    $allowed_order_statuses = [];
                    $order_statuses = $this->getNewOrderStatuses($this->model_localisation_order_status->getOrderStatuses());
                    foreach ($order_statuses as $status) {
                        $allowed_order_statuses[] = $status['order_status_id'];
                    }
                    
                    if (in_array($current_order_status_id, $allowed_order_statuses)){
                        $tab_key = $key;
                        $is_allsecureexchange_order = true;
                        break;
                    } else {
                        unset($data['tabs'][$key]);
                    }
                }
            }
        }

        $content = '';
        if ($order_id >0 && $is_allsecureexchange_order) {
            $status = $this->payment->getTransactionSingle($order_id, 'status');
            $uuid = $this->payment->getTransactionSingle($order_id, 'transaction_id');
            
            if ($status == 'debited' || $status == 'captured') {
                //Refund
                $params['user_token'] = $this->session->data['user_token'];
                $params['order_id'] = $order_id;
                $params['status'] = $status;
                $params['uuid'] = $uuid;
                $params['text_payment_status'] = $this->language->get('text_payment_status');
                $params['text_transaction_id'] = $this->language->get('text_transaction_id');
                $params['text_refund'] = $this->language->get('text_refund');
                
                $refundUrl = $this->payment->getCompatibleRoute('extension/allsecureexchange/payment/allsecureexchange','refund');
                $params['refund_action'] = $refundUrl;
                
                $content .= $this->load->view('extension/allsecureexchange/payment/allsecureexchange_refund', $params);
                $data['tabs'][$tab_key]['content'] .= $content;
                
            } else if ($status == 'preauthorized') {
                //Capture and Void
                $params['user_token'] = $this->session->data['user_token'];
                $params['order_id'] = $order_id;
                $params['status'] = $status;
                $params['uuid'] = $uuid;
                $params['text_payment_status'] = $this->language->get('text_payment_status');
                $params['text_transaction_id'] = $this->language->get('text_transaction_id');
                $params['text_capture'] = $this->language->get('text_capture');
                $params['text_void'] = $this->language->get('text_void');
                
                $captureUrl = $this->payment->getCompatibleRoute('extension/allsecureexchange/payment/allsecureexchange','capture');
                $params['capture_action'] = $captureUrl;
                
                $voidUrl = $this->payment->getCompatibleRoute('extension/allsecureexchange/payment/allsecureexchange','void');
                $params['void_action'] = $voidUrl;
                
                $content .= $this->load->view('extension/allsecureexchange/payment/allsecureexchange_capturevoid', $params);

                $data['tabs'][$tab_key]['content'] .= $content;
            }
        }
    }
    
    /**
     * Get Language specific error message
     *
     * @param string $code
     * 
     * @return string
     */
    public function getErrorMessageByCode($code)
    {
        $this->load->language('extension/allsecureexchange/payment/allsecureexchange');
        $message = $this->language->get('error_unknown');
        if($this->language->get('error_'.$code)) {
            $message = $this->language->get('error_'.$code);
        }
        return $message;
    }
    
     /**
     * Capture payment
     *
     * @return $json
     */

    public function capture()
    {
        $this->load->model('sale/order');
        $this->load->model('localisation/order_status');
        $this->load->language('extension/allsecureexchange/payment/allsecureexchange');
        $this->load->model('extension/allsecureexchange/payment/allsecureexchange');
        
        $this->payment = $this->model_extension_allsecureexchange_payment_allsecureexchange;
        
        $response = array();
        $status = 0;
        $message = '';

        try {
            $this->payment->log('capture triggered');
            if (isset($this->request->post['order_id'])) {
                $order_id = $this->request->post['order_id'];
            } else {
                $order_id = 0;
            }

            $order = $this->model_sale_order->getOrder($order_id);

            $amount = (float)$this->currency->format($order['total'], $order['currency_code'], $order['currency_value'], false);
            $amount = round($amount, 2);

            $transaction_id = $this->payment->getTransactionSingle($order_id, 'transaction_id');

            $merchantTransactionId = 'capture-'.$this->encodeOrderId($order_id);

            $client = $this->getClient();

            $capture = new AllsecureCapture();
            $capture->setTransactionId($merchantTransactionId)
                    ->setAmount($amount)
                    ->setCurrency($order['currency_code'])
                    ->setReferenceTransactionId($transaction_id);
            
            $this->payment->log('capture request');
            $this->payment->log((array)($capture));
            $result = $client->Capture($capture);
            $this->payment->log('capture response');
            $this->payment->log((array)($result));
            
            if ($result->getReturnType() == AllsecureResult::RETURN_TYPE_FINISHED) {
                $gatewayReferenceId = $result->getUuid();
                $this->payment->updateTransaction($order_id, 'status', 'captured');
                $this->payment->updateTransaction($order_id, 'transaction_id', $gatewayReferenceId);
                $this->payment->updateTransactionResponse($order_id, 'Authorized_Id', $transaction_id);
                $comment1 = $this->language->get('status_finished_capture');
                $comment2 = $this->language->get('text_transaction_id').': ' .$gatewayReferenceId;
                $comment = $comment1.$comment2;
                $message = $comment;
                $order_status_id = (int)$this->config->get('payment_allsecureexchange_order_status_id');
                $store = $this->createStoreInstance();
                $store->load->model('checkout/order');
                $store->model_checkout_order->addHistory((int)$order_id, $order_status_id, $comment);
                $status = 1;
            } elseif ($result->getReturnType() == AllsecureResult::RETURN_TYPE_ERROR) {
                $error = $result->getFirstError();
                $errorCode = $error->getCode();
                if (empty($errorCode)) {
                    $errorCode = $error->getAdapterCode();
                }
                $errorMessage = $this->getErrorMessageByCode($errorCode);
                throw new \Exception($errorMessage);
            } else {
                throw new \Exception($this->language->get('error_unknown'));
            }
        } catch (\Exception $e) {
            $message = $e->getMessage();
            $this->payment->log('Capture Catch: '.$message);
        }

        $response['status'] = $status;
        $response['message'] = $message;

        echo json_encode($response);
        exit;
    }
    
     /**
     * Void payment
     *
     * @return $json
     */
    
    public function void()
    {
        $this->load->model('sale/order');
        $this->load->model('localisation/order_status');
        $this->load->language('extension/allsecureexchange/payment/allsecureexchange');
        $this->load->model('extension/allsecureexchange/payment/allsecureexchange');
        
        $this->payment = $this->model_extension_allsecureexchange_payment_allsecureexchange;
        
        $response = array();
        $status = 0;

        try {
            $this->payment->log('void triggered');
            if (isset($this->request->post['order_id'])) {
                $order_id = $this->request->post['order_id'];
            } else {
                $order_id = 0;
            }

            $order = $this->model_sale_order->getOrder($order_id);

            $amount = $order['total'];

            $transaction_id = $this->payment->getTransactionSingle($order_id, 'transaction_id');

            $merchantTransactionId = 'void-'.$this->encodeOrderId($order_id);

            $client = $this->getClient();

           $void = new AllsecureVoidTransaction();
           $void->setMerchantTransactionId($merchantTransactionId)
                ->setReferenceUuid($transaction_id);
            
            $this->payment->log('void request');
            $this->payment->log((array)($void));
            $result = $client->void($void);
            $this->payment->log('void response');
            $this->payment->log((array)($result));
            
            if ($result->getReturnType() == AllsecureResult::RETURN_TYPE_FINISHED) {
                $gatewayReferenceId = $result->getUuid();
                $this->payment->updateTransaction($order_id, 'status', 'voided');
                $this->payment->updateTransaction($order_id, 'transaction_id', $gatewayReferenceId);
                $this->payment->updateTransactionResponse($order_id, 'Authorized_Id', $transaction_id);
                $comment1 = $this->language->get('status_finished_void');
                $comment2 = $this->language->get('text_transaction_id').': ' .$gatewayReferenceId;
                $comment = $comment1.$comment2;
                $message = $comment;
                $order_status_id = self::STATE_VOID;
                $store = $this->createStoreInstance();
                $store->load->model('checkout/order');
                $store->model_checkout_order->addHistory((int)$order_id, $order_status_id, $comment);
                $status = 1;
            } elseif ($result->getReturnType() == AllsecureResult::RETURN_TYPE_ERROR) {
                $error = $result->getFirstError();
                $errorCode = $error->getCode();
                if (empty($errorCode)) {
                    $errorCode = $error->getAdapterCode();
                }
                $errorMessage = $this->getErrorMessageByCode($errorCode);
                throw new \Exception($errorMessage);
            } else {
                throw new \Exception($this->language->get('error_unknown'));
            }
        } catch (\Exception $e) {
            $message = $e->getMessage();
            $this->payment->log('Void Catch: '.$message);
        }

        $response['status'] = $status;
        $response['message'] = $message;

        echo json_encode($response);
        exit;
    }
    
     /**
     * Refund payment
     *
     * @return $json
     */
    
    public function refund()
    {
        $this->load->model('sale/order');
        $this->load->model('localisation/order_status');
        $this->load->language('extension/allsecureexchange/payment/allsecureexchange');
        $this->load->model('extension/allsecureexchange/payment/allsecureexchange');
        
        $this->payment = $this->model_extension_allsecureexchange_payment_allsecureexchange;
        
        $response = array();
        $status = 0;

        try {
            $this->payment->log('refund triggered');
            if (isset($this->request->post['order_id'])) {
                $order_id = $this->request->post['order_id'];
            } else {
                $order_id = 0;
            }

            $order = $this->model_sale_order->getOrder($order_id);

            $amount = (float)$this->currency->format($order['total'], $order['currency_code'], $order['currency_value'], false);
            $amount = round($amount, 2);

            $transaction_id = $this->payment->getTransactionSingle($order_id, 'transaction_id');

            $merchantTransactionId = 'refund-'.$this->encodeOrderId($order_id);

            $client = $this->getClient();

            $refund = new AllsecureRefund();
            $refund->setMerchantTransactionId($merchantTransactionId)
                    ->setAmount($amount)
                    ->setCurrency($order['currency_code'])
                    ->setReferenceUuid($transaction_id);
            
            $this->payment->log('refund request');
            $this->payment->log((array)($refund));
            $result = $client->refund($refund);
            $this->payment->log('refund response');
            $this->payment->log((array)($result));
            
            if ($result->getReturnType() == AllsecureResult::RETURN_TYPE_FINISHED) {
                $gatewayReferenceId = $result->getUuid();
                $this->payment->updateTransaction($order_id, 'status', 'refunded');
                $this->payment->updateTransaction($order_id, 'transaction_id', $gatewayReferenceId);
                $this->payment->updateTransactionResponse($order_id, 'Captured_Id', $transaction_id);
                $comment1 = $this->language->get('status_finished_refund');
                $comment2 = $this->language->get('text_transaction_id').': ' .$gatewayReferenceId;
                $comment = $comment1.$comment2;
                $message = $comment;
                $order_status_id = self::STATE_REFUND;
                
                $store = $this->createStoreInstance();
                $store->load->model('checkout/order');
                $store->model_checkout_order->addHistory((int)$order_id, $order_status_id, $comment);
                $status = 1;
            } elseif ($result->getReturnType() == AllsecureResult::RETURN_TYPE_ERROR) {
                $error = $result->getFirstError();
                $errorCode = $error->getCode();
                if (empty($errorCode)) {
                    $errorCode = $error->getAdapterCode();
                }
                $errorMessage = $this->getErrorMessageByCode($errorCode);
                throw new \Exception($errorMessage);
            } else {
                throw new \Exception($this->language->get('error_unknown'));
            }
        } catch (\Exception $e) {
            $message = $e->getMessage();
            $this->payment->log('Refund Catch: '.$message);
        }

        $response['status'] = $status;
        $response['message'] = $message;

        echo json_encode($response);
        exit;
    }
    
    private function createStoreInstance(): object
    {
        // Autoloader
        $autoloader = new \Opencart\System\Engine\Autoloader();
        $autoloader->register('Opencart\Catalog', DIR_CATALOG);
        $autoloader->register('Opencart\Extension', DIR_EXTENSION);
        $autoloader->register('Opencart\System', DIR_SYSTEM);

        // Registry
        $registry = new \Opencart\System\Engine\Registry();
        $registry->set('autoloader', $autoloader);

        // Config
        $config = new \Opencart\System\Engine\Config();
        $config->addPath(DIR_CONFIG);
        $registry->set('config', $config);

        // Load the default config
        $config->load('default');
        $config->load('catalog');
        $config->set('application', 'Catalog');

        // Logging
        $registry->set('log', $this->log);

        // Event
        $event = new \Opencart\System\Engine\Event($registry);
        $registry->set('event', $event);

        // Event Register
        if ($config->has('action_event')) {
                foreach ($config->get('action_event') as $key => $value) {
                        foreach ($value as $priority => $action) {
                                $event->register($key, new \Opencart\System\Engine\Action($action), $priority);
                        }
                }
        }

        // Loader
        $loader = new \Opencart\System\Engine\Loader($registry);
        $registry->set('load', $loader);

        // Create a dummy request class so we can feed the data to the order editor
        $request = new \stdClass();
        $request->get = [];
        $request->post = [];
        $request->server = $this->request->server;
        $request->cookie = [];

        // Request
        $registry->set('request', $request);

        // Response
        $response = new \Opencart\System\Library\Response();
        $registry->set('response', $response);

        // Database
        $registry->set('db', $this->db);

        // Cache
        $registry->set('cache', $this->cache);

        // Session
        $session = new \Opencart\System\Library\Session($config->get('session_engine'), $registry);
        $registry->set('session', $session);

        if (isset($this->session->data['api_session'])) {
                $session_id = $this->session->data['api_session'];
        } else {
                $session_id = '';
        }

        $session->start($session_id);

        $this->session->data['api_session'] = $session->getId();

        // To use the order API it requires an API ID.
        $session->data['api_id'] = (int)$this->config->get('config_api_id');

        // Template
        $template = new \Opencart\System\Library\Template($config->get('template_engine'));
        $template->addPath(DIR_CATALOG . 'view/template/');
        $registry->set('template', $template);

        // Language
        if (isset($session->data['language'])) {
                $language_code = $session->data['language'];
        } else {
                $language_code = $this->config->get('config_language');
        }

        // Catalog uses language key in URLs
        $request->get['language'] = $language_code;

        $this->load->model('localisation/language');

        $language_info = $this->model_localisation_language->getLanguageByCode($language_code);

        if ($language_info) {
                $config->set('config_language_id', $language_info['language_id']);
                $config->set('config_language', $language_info['code']);
        } else {
                $config->set('config_language_id', $this->config->get('config_language_id'));
                $config->set('config_language', $language_code);
        }

        $language = new \Opencart\System\Library\Language($language_code);

        if (!$language_info['extension']) {
                $language->addPath(DIR_CATALOG . 'language/');
        } else {
                $language->addPath(DIR_EXTENSION . $language_info['extension'] . '/catalog/language/');
        }

        $language->load($language_code);
        $registry->set('language', $language);

        // Currency
        if (!isset($session->data['currency'])) {
                $session->data['currency'] = $this->config->get('config_currency');
        }

        // Store
        if (isset($session->data['store_id'])) {
                $config->set('config_store_id', $session->data['store_id']);
        } else {
                $config->set('config_store_id', 0);
        }

        // Url
        $registry->set('url', new \Opencart\System\Library\Url($config->get('site_url')));

        // Document
        $registry->set('document', new \Opencart\System\Library\Document());

        // 3. Add the default API ID otherwise will not get a response.
        $session->data['api_id'] = $this->config->get('config_api_id');

        // 4. Run pre actions to load key settings and classes.
        $pre_actions = [
                'startup/setting',
                'startup/extension',
                'startup/customer',
                'startup/tax',
                'startup/currency',
                'startup/application',
                'startup/startup',
                'startup/event'
        ];

        // Pre Actions
        foreach ($pre_actions as $pre_action) {
                $loader->controller($pre_action);
        }

        // Customer
        $customer = new \Opencart\System\Library\Cart\Customer($this->registry);
        $registry->set('customer', $customer);

        return $registry;
}
}