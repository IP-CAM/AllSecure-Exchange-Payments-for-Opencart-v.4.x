<?php
namespace Opencart\Catalog\Controller\Extension\Allsecureexchange\Payment;

require_once DIR_EXTENSION.'allsecureexchange/system/library/psr/log/initClientAutoload.php';
require_once DIR_EXTENSION.'allsecureexchange/system/library/exchange-php-client/initClientAutoload.php';

use Exchange\Client\Client as AllsecureClient;
use Exchange\Client\Data\Customer as AllsecureCustomer;
use Exchange\Client\Transaction\Debit as AllsecureDebit;
use Exchange\Client\Transaction\Preauthorize as AllsecurePreauthorize;
use Exchange\Client\Transaction\Result as AllsecureResult;
use Exchange\Client\Callback\Result as AllsecureCallbackResult;
use Exchange\Client\StatusApi\StatusRequestData;

class Allsecureexchange extends \Opencart\System\Engine\Controller
{
    const STATE_VOID = 16;
    const STATE_REFUND = 11;
    const STATE_FAILED = 10;
    
    /**
     * @var $payment
     */
    protected $payment;
    
    public function index()
    {
        $this->load->language('extension/allsecureexchange/payment/allsecureexchange');

        $data['button_confirm'] = $this->language->get('button_confirm');

        $data['action'] = $this->url->link('extension/allsecureexchange/payment/allsecureexchange|pay', '', true);

        $checkout_type = $this->config->get('payment_allsecureexchange_checkout');
        if ($checkout_type == 'paymentjs') {
            $testMode = 0;
            if ($this->config->get('payment_allsecureexchange_mode') == 'test') {
                $testMode = 1;
            }
            
            $card_supported = $this->config->get('payment_allsecureexchange_card_supported');
            if ($card_supported && is_array($card_supported)) {
                $card_supported = implode(',', $card_supported);
            } else {
                
            }

            $data['test_mode'] = $testMode;
            $data['public_integration_key'] = $this->config->get('payment_allsecureexchange_integrator_key');
            $data['card_supported'] = strtolower($card_supported);
            $data['image_path'] = HTTP_SERVER .'extension/allsecureexchange/catalog/view/image/';
            $data['text_credit_card_information'] = $this->language->get('text_credit_card_information');
            $data['text_card_holder'] = $this->language->get('text_card_holder');
            $data['text_card_number'] = $this->language->get('text_card_number');
            $data['text_expiration_date'] = $this->language->get('text_expiration_date');
            $data['text_cvv'] = $this->language->get('text_cvv');
            $data['error_required_field'] = $this->language->get('error_required_field');
            $data['error_invalid_holder_name'] = $this->language->get('error_invalid_holder_name');
            $data['error_invalid_card_number'] = $this->language->get('error_invalid_card_number');
            $data['error_card_not_supported'] = $this->language->get('error_card_not_supported');
            $data['error_incorrect_card_expire_date'] = $this->language->get('error_incorrect_card_expire_date');
            $data['error_invalid_cvv'] = $this->language->get('error_invalid_cvv');

            return $this->load->view('extension/allsecureexchange/payment/paymentjs', $data);
        } else {
            return $this->load->view('extension/allsecureexchange/payment/fullpageredirect', $data);
        }
    }
        
    public function pay()
    {
        $json = array();
        $this->load->language('extension/allsecureexchange/payment/allsecureexchange');
        $this->load->model('checkout/order');
        $this->load->model('extension/allsecureexchange/payment/allsecureexchange');
        
        $this->payment = $this->model_extension_allsecureexchange_payment_allsecureexchange;

        try {
            $order_id = (int)($this->session->data['order_id']);

            $order = $this->model_checkout_order->getOrder($order_id);

            if ($order && $order['order_id'] > 0) {
                $transaction_token = '';

                $checkoutType = $this->config->get('payment_allsecureexchange_checkout');
                $action = $this->config->get('payment_allsecureexchange_transaction_type');

                if ($checkoutType == 'paymentjs') {
                    if (isset($this->request->post['allsecurepay_transaction_token'])) {
                        $transaction_token = $this->request->post['allsecurepay_transaction_token'];
                        if (!empty($transaction_token)) {
                            $transaction_token  = trim($transaction_token);
                            $transaction_token = strip_tags($transaction_token);
                        }
                    }

                    if (empty($transaction_token)) {
                        throw new \Exception($this->language->get('error_invalid_transaction_token'));
                    }
                }

                if ($action == 'debit') {
                    $result = $this->debitTransaction($order, $transaction_token);
                } else {
                    $result = $this->preauthorizeTransaction($order, $transaction_token);
                }

                // handle the result
                if ($result->isSuccess()) {
                    $gatewayReferenceId = $result->getUuid();

                    $this->payment->updateTransaction($order_id, 'transaction_id', $gatewayReferenceId);
                    $this->payment->updateTransaction($order_id, 'transaction_mode', $this->config->get('payment_allsecureexchange_mode'));
                    $this->payment->updateTransaction($order_id, 'checkout_type', $checkoutType);
                    $this->payment->updateTransaction($order_id, 'transaction_type', $action);
                    $this->payment->updateTransaction($order_id, 'response', json_encode($result->toArray()));

                    // handle result based on it's returnType
                    if ($result->getReturnType() == AllsecureResult::RETURN_TYPE_ERROR) {
                        //error handling
                        $this->payment->updateTransaction($order_id, 'status', 'error');
                        $error = $result->getFirstError();
                        $errorCode = $error->getCode();
                        if (empty($errorCode)) {
                            $errorCode = $error->getAdapterCode();
                        }
                        $errorMessage = $this->getErrorMessageByCode($errorCode);
                        throw new \Exception($errorMessage);
                    } elseif ($result->getReturnType() == AllsecureResult::RETURN_TYPE_REDIRECT) {
                        //redirect the user
                        $this->payment->updateTransaction($order_id, 'status', 'redirected');
                        $redirectLink = $result->getRedirectUrl();
                        $json['redirect'] = $redirectLink;
                    } elseif ($result->getReturnType() == AllsecureResult::RETURN_TYPE_PENDING) {
                        //payment is pending, wait for callback to complete
                        $this->payment->updateTransaction($order_id, 'status', 'pending');
                        if ($action == 'debit') {
                            $comment1 = $this->language->get('status_pending_debt');
                        } else {
                            $comment1 = $this->language->get('status_pending_preauthorize');
                        }

                        $comment2 = $this->language->get('text_transaction_id').': ' .$gatewayReferenceId;
                        $comment = $comment1.$comment2;
                        
                        $paymentInfo = $this->get_payment_info($gatewayReferenceId);
                        if ($paymentInfo) {
                            $comment = $comment.$paymentInfo;
                        }

                        $order_status_id = 1;
                        $this->model_checkout_order->addHistory((int)$order_id, $order_status_id, $comment, true);
                        $json['redirect'] = $this->url->link('checkout/success', 'order_id=' . $order_id, true);
                    } elseif ($result->getReturnType() == AllsecureResult::RETURN_TYPE_FINISHED) {
                        //payment is finished, update your cart/payment transaction
                        if ($action == 'debit') {
                            $this->payment->updateTransaction($order_id, 'status', 'debited');
                            $comment1 = $this->language->get('status_finished_debt');
                            $comment2 = $this->language->get('text_transaction_id').': ' .$gatewayReferenceId;
                            $comment = $comment1.$comment2;
                            
                            $paymentInfo = $this->get_payment_info($gatewayReferenceId);
                            if ($paymentInfo) {
                                $comment = $comment.$paymentInfo;
                            }
                            
                            $order_status_id = (int)$this->config->get('payment_allsecureexchange_order_status_id');
                            $this->model_checkout_order->addHistory((int)$order_id, $order_status_id, $comment, true);
                            $json['redirect'] = $this->url->link('checkout/success', 'order_id=' . $order_id, true);
                        } else {
                            $this->payment->updateTransaction($order_id, 'status', 'preauthorized');
                            $comment1 = $this->language->get('status_finished_preauthorize');
                            $comment2 = $this->language->get('text_transaction_id').': ' .$gatewayReferenceId;
                            $comment = $comment1.$comment2;
                            
                            $paymentInfo = $this->get_payment_info($gatewayReferenceId);
                            if ($paymentInfo) {
                                $comment = $comment.$paymentInfo;
                            }
                            
                            $order_status_id = 1;
                            $this->model_checkout_order->addHistory((int)$order_id, $order_status_id, $comment, true);
                            $json['redirect'] = $this->url->link('checkout/success', 'order_id=' . $order_id, true);
                        }
                    }
                } else {
                    // handle error
                    $errorCode = $result->getErrorCode();
                    $errorMessage = $this->getErrorMessageByCode($errorCode);
                    throw new \Exception($errorMessage);
                }
            } else {
                throw new \Exception($this->language->get('error_no_order_data'));
            }
        } catch (\Exception $e) {
            //echo $e->getTraceAsString();exit;
            $errorMessage = $e->getMessage();
            $this->payment->log('Payment Create Catch: '.$errorMessage);
            $message = $this->language->get('error_payment_failed').' '.$errorMessage;
            $json['error'] = $message;
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));		
    }
    
    public function cancel()
    {
        $this->load->language('extension/allsecureexchange/payment/allsecureexchange');
        $this->load->model('checkout/order');
        $this->load->model('extension/allsecureexchange/payment/allsecureexchange');
        
        $this->payment = $this->model_extension_allsecureexchange_payment_allsecureexchange;

        try {
            $this->payment->log('Cancel URL Called');
            $order_id = false;
            if (isset($this->session->data['order_id']) && !empty($this->session->data['order_id'])) {
                $order_id = (int)($this->session->data['order_id']);
            } else if (isset($this->request->get['order_id'])) {
                $order_id = (int)($this->request->get['order_id']);
            }

            $order = $this->model_checkout_order->getOrder($order_id);

            if ($order_id && $order && $order['order_id'] > 0) {
                throw new \Exception($this->language->get('status_cancel'));
            } else {
                throw new \Exception($this->language->get('error_no_order_data'));
            }
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            $this->payment->log('Cancel URL Catch: '.$errorMessage);
            $message = $this->language->get('error_payment_failed').' '.$errorMessage;
            $this->session->data['error'] = $message;
            $this->response->redirect($this->url->link('checkout/cart','egassemrorre=' . base64_encode($message))); 
        }
    }
    
    public function error()
    {
        $this->load->language('extension/allsecureexchange/payment/allsecureexchange');
        $this->load->model('checkout/order');
        $this->load->model('extension/allsecureexchange/payment/allsecureexchange');
        
        $this->payment = $this->model_extension_allsecureexchange_payment_allsecureexchange;

        try {
            $this->payment->log('Error URL Called');
            $order_id = false;
            if (isset($this->session->data['order_id']) && !empty($this->session->data['order_id'])) {
                $order_id = (int)($this->session->data['order_id']);
            } else if (isset($this->request->get['order_id'])) {
                $order_id = (int)($this->request->get['order_id']);
            }
            $order = $this->model_checkout_order->getOrder($order_id);

            if ($order_id && $order && $order['order_id'] > 0) {
                $uuid = $this->payment->getTransactionResponseSingle($order_id, 'uuid');

                $client = $this->getClient();

                $statusRequestData = new StatusRequestData();
                $statusRequestData->setUuid($uuid);
                $statusResult = $client->sendStatusRequest($statusRequestData);

                $params = array();
                if ($statusResult->hasErrors()) {
                    $errors = $statusResult->getErrors();
                    $error = $statusResult->getFirstError();

                    $errorCode = $error->getCode();
                    if (empty($errorCode)) {
                        $errorCode = $error->getAdapterCode();
                    }
                    $errorMessage = $this->getErrorMessageByCode($errorCode);

                    throw new \Exception($errorMessage);
                } else {
                    throw new \Exception($this->language->get('status_error'));
                }
            } else {
                throw new \Exception($this->language->get('error_no_order_data'));
            }
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            $this->payment->log('Error URL Catch: '.$errorMessage);
            $message = $this->language->get('error_payment_failed').' '.$errorMessage;
            $this->session->data['error'] = $message;
            $this->response->redirect($this->url->link('checkout/cart','egassemrorre=' . base64_encode($message))); 
        }
    }

    public function return()
    {
        $this->load->language('extension/allsecureexchange/payment/allsecureexchange');
        $this->load->model('checkout/order');
        $this->load->model('extension/allsecureexchange/payment/allsecureexchange');
        
        $this->payment = $this->model_extension_allsecureexchange_payment_allsecureexchange;
        try {
            $this->payment->log('Return URL Called');
            $order_id = false;
            if (isset($this->session->data['order_id']) && !empty($this->session->data['order_id'])) {
                $order_id = (int)($this->session->data['order_id']);
            } else if (isset($this->request->get['order_id'])) {
                $order_id = (int)($this->request->get['order_id']);
            }

            if ($order_id) {
                $order_info = $this->model_checkout_order->getOrder($order_id);
                if ($order_info) {
                    $order_status_id = (int)$order_info['order_status_id'];
                    if ($order_status_id == 0) {
                        $comment = $this->language->get('status_webhook_wait');
                        $this->model_checkout_order->addHistory((int)$order_id, 1, $comment);
                    }
                }
                $this->response->redirect($this->url->link('checkout/success', 'order_id=' . $order_id, true));
            } else {
                throw new \Exception($this->language->get('error_no_order_data'));
            }
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            $this->payment->log('Return URL Catch: '.$errorMessage);
            $message = $this->language->get('error_payment_failed').' '.$errorMessage;
            $this->session->data['error'] = $message;
            $this->response->redirect($this->url->link('checkout/cart','egassemrorre=' . base64_encode($message))); 
        }
    }

    public function webhook()
    {
        $this->load->language('extension/allsecureexchange/payment/allsecureexchange');
        $this->load->model('checkout/order');
        $this->load->model('extension/allsecureexchange/payment/allsecureexchange');
        
        $this->payment = $this->model_extension_allsecureexchange_payment_allsecureexchange;
        try {
            $this->payment->log('Webhook URL Called');
            $client = $this->getClient();
            
            if (!$client->validateCallbackWithGlobals()) {
                throw new \Exception($this->language->get('error_callback_validation_failed'));
            }
            
            $callbackResult = $client->readCallback(file_get_contents('php://input'));
            $this->payment->log((array)($callbackResult));
            $order_id = (int)($this->request->get['order_id']);
            if ($order_id > 0) {
                $order = $this->model_checkout_order->getOrder($order_id);
                if ($order && $order['order_id'] > 0) {
                    $merchantTransactionId = $callbackResult->getMerchantTransactionId();
                    $decodedOrderId = $this->decodeOrderId($merchantTransactionId);
                    $this->payment->log('Concerning order:'.$order_id);

                    if ($order['order_id'] !== $decodedOrderId) {
                       throw new \Exception($this->language->get('error_merchant_transaction_validation_failed'));
                    }

                    // handle result based on it's returnType
                    if ($callbackResult->getResult() == AllsecureCallbackResult::RESULT_OK) {
                        //result success
                        $gatewayReferenceId = $callbackResult->getUuid();
                        if ($callbackResult->getTransactionType() == AllsecureCallbackResult::TYPE_DEBIT) {
                            //result debit
                            if ( isset($callbackResult->getExtraData()['authCode']) ) {
                                $this->payment->updateTransactionResponse($order_id, 'AuthCode', $callbackResult->getExtraData()['authCode']);
                            } elseif (isset($callbackResult->getExtraData()['adapterReferenceId']) ) {
                                $this->payment->updateTransactionResponse($order_id, 'AuthCode', $callbackResult->getExtraData()['adapterReferenceId']);
                            }

                            $cardData = (array)($callbackResult->getReturnData());
                            $this->payment->updateTransactionResponse($order_id, 'CardData', json_encode($cardData));
                            
                            $this->payment->updateTransaction($order_id, 'status', 'debited');
                            $comment1 = $this->language->get('status_finished_debt');
                            $comment2 = $this->language->get('text_transaction_id').': ' .$gatewayReferenceId;
                            $comment = $comment1.$comment2;
                            
                            $paymentInfo = $this->get_payment_info($gatewayReferenceId);
                            if ($paymentInfo) {
                                $comment = $comment.$paymentInfo;
                            }
                        
                            $order_status_id = (int)$this->config->get('payment_allsecureexchange_order_status_id');
                            $this->model_checkout_order->addHistory((int)$order_id, $order_status_id, $comment, true);
                        } else if ($callbackResult->getTransactionType() == AllsecureCallbackResult::TYPE_CAPTURE) {
                            //result capture
                            $this->payment->updateTransaction($order_id, 'status', 'captured');
                            $comment1 = $this->language->get('status_finished_capture');
                            $comment2 = $this->language->get('text_transaction_id').': ' .$gatewayReferenceId;
                            $comment = $comment1.$comment2;
                            $order_status_id = (int)$this->config->get('payment_allsecureexchange_order_status_id');
                            $this->model_checkout_order->addHistory((int)$order_id, $order_status_id, $comment);
                        } else if ($callbackResult->getTransactionType() == AllsecureCallbackResult::TYPE_VOID) {
                            //result void
                            $this->payment->updateTransaction($order_id, 'status', 'voided');
                            $comment1 = $this->language->get('status_finished_void');
                            $comment2 = $this->language->get('text_transaction_id').': ' .$gatewayReferenceId;
                            $comment = $comment1.$comment2;
                            $order_status_id = self::STATE_VOID;
                            $this->model_checkout_order->addHistory((int)$order_id, $order_status_id, $comment);
                        } else if ($callbackResult->getTransactionType() == AllsecureCallbackResult::TYPE_PREAUTHORIZE) {
                            //result preauthorize
                            if ( isset($callbackResult->getExtraData()['authCode']) ) {
                                $this->payment->updateTransactionResponse($order_id, 'AuthCode', $callbackResult->getExtraData()['authCode']);
                            } elseif (isset($callbackResult->getExtraData()['adapterReferenceId']) ) {
                                $this->payment->updateTransactionResponse($order_id, 'AuthCode', $callbackResult->getExtraData()['adapterReferenceId']);
                            }

                            $cardData = (array)($callbackResult->getReturnData());
                            $this->payment->updateTransactionResponse($order_id, 'CardData', json_encode($cardData));
                            $this->payment->updateTransaction($order_id, 'status', 'preauthorized');

                            $comment1 = $this->language->get('status_finished_preauthorize');
                            $comment2 = $this->language->get('text_transaction_id').': ' .$gatewayReferenceId;
                            $comment = $comment1.$comment2;
                            
                            $paymentInfo = $this->get_payment_info($gatewayReferenceId);
                            if ($paymentInfo) {
                                $comment = $comment.$paymentInfo;
                            }
                            
                            $order_status_id = 1;
                            $this->model_checkout_order->addHistory((int)$order_id, $order_status_id, $comment, true);
                        }
                    } elseif ($callbackResult->getResult() == AllsecureCallbackResult::RESULT_ERROR) {
                        //payment error
                        $cardData = (array)($callbackResult->getReturnData());
                        $this->payment->updateTransactionResponse($order_id, 'CardData', json_encode($cardData));
                        $this->payment->updateTransaction($order_id, 'status', 'error');
                        $error = $callbackResult->getFirstError();
                        $errorData = array();
                        $errorData["message"] = $error->getMessage();
                        $errorData["code"] = $error->getCode();
                        $errorData["adapterCode"] = $error->getAdapterCode();
                        $errorData["adapterMessage"] = $error->getAdapterMessage();
                        $this->payment->updateTransactionResponse($order_id, 'errorData', json_encode($errorData));
                        
                        $errorCode = $error->getCode();
                        if (empty($errorCode)) {
                            $errorCode = $error->getAdapterCode();
                        }
                        
                        $this->payment->updateTransaction($order_id, 'status', 'error');
                        /*$comment1 = $this->language->get('status_error');
                        $comment2 = $this->getErrorMessageByCode($errorCode);
                        $comment = $comment1.$comment2;
                        $order_status_id = self::STATE_FAILED;
                        $this->model_checkout_order->addHistory((int)$order_id, $order_status_id, $comment);*/
                    } else {
                        throw new \Exception($this->language->get('error_unknown'));
                    }
                } else {
                    throw new \Exception($this->language->get('error_no_order_data'));
                }
            } else {
                throw new \Exception($this->language->get('error_no_order_data'));
            }
            echo 'OK';
            exit;
        } catch (\Exception $e) {
            $this->payment->log('Webhook Catch: '.$e->getMessage());
            echo 'OK';
            exit;
        }
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
     * Process Transaction
     *
     * @param $order
     * @param string $token
     * @param string $action
     * @return $this
     */
    public function processTransaction($order, $token, $action)
    {
        $client = $this->getClient();
        
        $customer = new AllsecureCustomer();
        $customer->setIdentification($order['customer_id'])
                ->setFirstName($order['payment_firstname'])
                ->setLastName($order['payment_lastname'])
                ->setEmail($order['email'])
                ->setBillingAddress1($order['payment_address_1'])
                ->setBillingCity($order['payment_city'])
                ->setBillingCountry($order['payment_iso_code_2'])
                ->setBillingPhone($order['telephone'])
                ->setBillingPostcode($order['payment_postcode'])
                ->setBillingState($order['payment_zone'])
                ->setIpAddress($order['ip']);
        
        if (!empty($order['payment_address_2'])) {
            $customer->setBillingAddress2($order['payment_address_2']);
        }
        
        if (!empty($order['payment_company'])) {
            $customer->setCompany($order['payment_company']);
        }
        
        if (!empty($order['shipping_iso_code_2'])) {
            $customer->setShippingFirstName($order['shipping_firstname'])
                ->setShippingLastName($order['shipping_lastname'])    
                ->setShippingAddress1($order['shipping_address_1'])
                ->setShippingCity($order['shipping_city'])
                ->setShippingCountry($order['shipping_iso_code_2'])
                ->setShippingPhone($order['telephone'])
                ->setShippingPostcode($order['shipping_postcode'])
                ->setShippingState($order['shipping_zone']);
            
            if (!empty($order['shipping_address_2'])) {
                $customer->setShippingAddress2($order['shipping_address_2']);
            }

            if (!empty($order['shipping_company'])) {
                $customer->setShippingCompany($order['shipping_company']);
            }
        }
        
        $amount = (float)$this->currency->format($order['total'], $order['currency_code'], $order['currency_value'], false);
        $amount = round($amount, 2);
        
        if ($action == 'debit') {
            $transasction = new AllsecureDebit();
        } else {
            $transasction = new AllsecurePreauthorize();
        }
        
        $order_id = $order['order_id'];
        $merchantTransactionId = $this->encodeOrderId($order_id);

        $transasction->setMerchantTransactionId($merchantTransactionId)
            ->setAmount($amount)
            ->setCurrency($order['currency_code'])
            ->setCustomer($customer)
            ->setCallbackUrl($this->url->link('extension/allsecureexchange/payment/allsecureexchange|webhook', 'order_id='.$order_id, true))
            ->setCancelUrl($this->url->link('extension/allsecureexchange/payment/allsecureexchange|cancel', 'order_id='.$order_id, true))
            ->setSuccessUrl($this->url->link('extension/allsecureexchange/payment/allsecureexchange|return', 'order_id='.$order_id, true))
            ->setErrorUrl($this->url->link('extension/allsecureexchange/payment/allsecureexchange|error', 'order_id='.$order_id, true));
        
        if (isset($token)) {
            $transasction->setTransactionToken($token);
        }

        if ($action == 'debit') {
            $this->payment->log('Debit Transaction');
            $this->payment->log((array)($transasction));
            $result = $client->debit($transasction);
        } else {
            $this->payment->log('PreAuthorize Transaction');
            $this->payment->log((array)($transasction));
            $result = $client->preauthorize($transasction);
        }
        
        return $result;
    }

    /**
     * Debit Transaction
     *
     * @param $order
     * @param string $token
     * 
     * @return $this
     */
    public function debitTransaction($order, $token)
    {
        return @$this->processTransaction($order, $token, 'debit');
    }

    /**
     * Preauthorize Transaction
     *
     * @param $order
     * @param string $token
     * 
     * @return $this
     */
    public function preauthorizeTransaction($order, $token)
    {
        return @$this->processTransaction($order, $token, 'preauthorize');
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
     * Checking the current order is placed using this payment method
     * 
     * @return bool|array
     */
    public function isAllsecureExchangeOrder()
    {
        $order = false;
        $order_id = 0;
        if (isset($this->request->get['order_id'])) {
            $order_id = (int)($this->request->get['order_id']);
        } else if (isset($this->request->get['amp;order_id'])) {
            $order_id = (int)($this->request->get['amp;order_id']);
        }

        if ($order_id > 0) {
            $this->load->model('checkout/order');
            $order = $this->model_checkout_order->getOrder($order_id);

            if ($order && $order['payment_code'] != 'allsecureexchange') {
                $order = false;
            }
        }
        return $order;
    }
    
    /**
     * Add payment information to the success page
     *
     * @param mixed $route
     * @param mixed $data
     * @param mixed $output
     * 
     * @return void
     */
    public function success_payment_info(&$route, &$data, &$output)
    {
        $this->load->model('setting/setting');
        $setting = $this->model_setting_setting;

         // In case the extension is disabled, do nothing
        if (!$setting->getValue('payment_allsecureexchange_status')) {
            return;
        }
        
        if (!$setting->getValue('payment_allsecureexchange_transaction_confirmation_page')) {
            return;
        }
        
        $order = $this->isAllsecureExchangeOrder();

        if (!$order) {
            return;
        }

        $this->load->language('extension/allsecureexchange/payment/allsecureexchange');
        $this->load->model('extension/allsecureexchange/payment/allsecureexchange');
        
        $this->payment = $this->model_extension_allsecureexchange_payment_allsecureexchange;
        
        $params = [
            'text_transaction_details' => $this->language->get('text_transaction_details'),
            'text_transaction_codes' => $this->language->get('text_transaction_codes'),
            'text_card_type' => $this->language->get('text_card_type'),
            'text_payment_type' => $this->language->get('text_payment_type'),
            'text_transaction_time' => $this->language->get('text_transaction_time'),
            'text_transaction_id' => $this->language->get('text_transaction_id'),
            'status_failed' => $this->language->get('status_failed'),
            'text_payment_currency' => $this->language->get('text_payment_currency'),
            'text_payment_amountpaid' => $this->language->get('text_payment_amountpaid'),
        ]; 
        
        try {
            $order_id = $order['order_id'];
            $uuid = $this->payment->getTransactionResponseSingle($order_id, 'uuid');
            $client = $this->getClient();

            $statusRequestData = new StatusRequestData();
            $statusRequestData->setUuid($uuid);
            $statusResult = $client->sendStatusRequest($statusRequestData);

            if ($statusResult->hasErrors()) {
                $errors = $statusResult->getErrors();
                $error = $statusResult->getFirstError();

                $errorCode = $error->getCode();
                if (empty($errorCode)) {
                    $errorCode = $error->getAdapterCode();
                }
                $errorMessage = $this->getErrorMessageByCode($errorCode);

                throw new \Exception($errorMessage);
            } else {
                $params['responseStatus'] = 'success';
                    
                $result = $statusResult->getTransactionStatus();
                $transactionType = $statusResult->getTransactionType();
                $amount = $statusResult->getAmount();
                $currency = $statusResult->getCurrency();
                $cardData = $statusResult->getreturnData();
                $cardHolder = $cardData->getcardHolder();
                $binBrand = strtoupper($cardData->getType());
                $expiryMonth = $cardData->getexpiryMonth();
                $expiryYear = $cardData->getexpiryYear();
                $firstSixDigits = $cardData->getfirstSixDigits();
                $lastFourDigits = $cardData->getlastFourDigits();
                $transactionId = $statusResult->getTransactionUuid() ?? NULL;
                $extraData = $statusResult->getextraData();

                if ( isset($extraData['authCode']) ) {
                        $authCode = $extraData['authCode'];		
                } elseif (isset($extraData['adapterReferenceId']) ) {
                        $authCode = $extraData['adapterReferenceId'];	
                } else {
                        $authCode = NULL;	
                }
                $timestamp = date("Y-m-d H:i:s");

                $params['lastFourDigits'] = $lastFourDigits;
                $params['transactionType'] = $transactionType;
                $params['binBrand'] = $binBrand;
                $params['authCode'] = $authCode;
                $params['transactionId'] = $transactionId;
                $params['timestamp'] = $timestamp;
                $params['transactionCurrency'] = $currency;
                $params['transactionAmount'] = $amount;
            }
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            $params['responseStatus'] = 'error';
            $params['errorMessage'] = $errorMessage;
        }
     
        $content = $this->load->view('extension/allsecureexchange/payment/success_payment_info', $params);
        
        $find = '<p>Thanks for shopping with us online!</p>';
        $output = str_replace($find, $find.$content, $output);
    }
    
    /**
     * Get payment information
     *
     * @param string $uuid
     * 
     * @return mixed
     */
    public function get_payment_info($uuid)
    {
        $this->load->model('setting/setting');
        $setting = $this->model_setting_setting;
        
        if (!$setting->getValue('payment_allsecureexchange_transaction_email')) {
            return false;
        }

        $this->load->language('extension/allsecureexchange/payment/allsecureexchange');
        
        $params = [
            'text_transaction_details' => $this->language->get('text_transaction_details'),
            'text_transaction_codes' => $this->language->get('text_transaction_codes'),
            'text_card_type' => $this->language->get('text_card_type'),
            'text_payment_type' => $this->language->get('text_payment_type'),
            'text_transaction_time' => $this->language->get('text_transaction_time'),
            'text_transaction_id' => $this->language->get('text_transaction_id'),
            'status_failed' => $this->language->get('status_failed'),
            'text_payment_currency' => $this->language->get('text_payment_currency'),
            'text_payment_amountpaid' => $this->language->get('text_payment_amountpaid'),
        ]; 
        
        try {
            $client = $this->getClient();

            $statusRequestData = new StatusRequestData();
            $statusRequestData->setUuid($uuid);
            $statusResult = $client->sendStatusRequest($statusRequestData);
            
            if ($statusResult->hasErrors()) {
                return false;
            } else {
                $params['responseStatus'] = 'success';
                    
                $result = $statusResult->getTransactionStatus();
                $transactionType = $statusResult->getTransactionType();
                $amount = $statusResult->getAmount();
                $currency = $statusResult->getCurrency();
                $cardData = $statusResult->getreturnData();
                $cardHolder = $cardData->getcardHolder();
                $binBrand = strtoupper($cardData->getType());
                $expiryMonth = $cardData->getexpiryMonth();
                $expiryYear = $cardData->getexpiryYear();
                $firstSixDigits = $cardData->getfirstSixDigits();
                $lastFourDigits = $cardData->getlastFourDigits();
                $transactionId = $statusResult->getTransactionUuid() ?? NULL;
                $extraData = $statusResult->getextraData();

                if ( isset($extraData['authCode']) ) {
                        $authCode = $extraData['authCode'];		
                } elseif (isset($extraData['adapterReferenceId']) ) {
                        $authCode = $extraData['adapterReferenceId'];	
                } else {
                        $authCode = NULL;	
                }
                $timestamp = date("Y-m-d H:i:s");

                $params['lastFourDigits'] = $lastFourDigits;
                $params['transactionType'] = $transactionType;
                $params['binBrand'] = $binBrand;
                $params['authCode'] = $authCode;
                $params['transactionId'] = $transactionId;
                $params['timestamp'] = $timestamp;
                $params['transactionCurrency'] = $currency;
                $params['transactionAmount'] = $amount;
            }
        } catch (\Exception $e) {
            return false;
        }
     
        $content = $this->load->view('extension/allsecureexchange/payment/payment_info', $params);
        $content = str_replace(array("\n", "\r"), '', $content);
        
        return $content;
    }
    
    /**
     * Add script to the checkout page
     *
     * @param mixed $route
     * @param mixed $data
     * @param mixed $output
     * 
     * @return void
     */
    public function checkout_after(&$route, &$data, &$output)
    {
        // In case the extension is disabled, do nothing
        if (!$this->config->get('payment_allsecureexchange_status')) {
            return;
        }
        
        $testMode = 0;
        if ($this->config->get('payment_allsecureexchange_mode') == 'test') {
            $testMode = 1;
        }
        
        $params['test_mode'] = $testMode;
        $content = $this->load->view('extension/allsecureexchange/payment/addscript', $params);

        $find = '</footer>';
        $output = str_replace($find, $find.$content, $output);
    }
    
    /**
     * Display error message
     *
     * @param mixed $route
     * @param mixed $data
     * @param mixed $output
     * 
     * @return void
     */
    public function cart_after(&$route, &$data, &$output)
    {
         // In case the extension is disabled, do nothing
        if (!$this->config->get('payment_allsecureexchange_status')) {
            return;
        }

        $egassemrorre = '';
        if (isset($this->request->get['egassemrorre'])) {
            $egassemrorre = $this->request->get['egassemrorre'];
        } else if (isset($this->request->get['amp;egassemrorre'])) {
            $egassemrorre = $this->request->get['amp;egassemrorre'];
        }

        if (!empty($egassemrorre)) {
            if (isset($this->session->data['error'])) {
                $params['message'] = base64_decode($egassemrorre);
                $content = $this->load->view('extension/allsecureexchange/payment/errormessage', $params);

                $find = '<ul class="breadcrumb">';
                //$output = str_replace($find, $content.$find, $output);
            }
        }
    }
    
    /**
     * Display error message
     *
     * @param mixed $route
     * @param mixed $data
     * @param mixed $output
     * 
     * @return void
     */
    public function error_after(&$route, &$data, &$output)
    {
        // In case route is not checkout/cart, do nothing
        if ($route == 'checkout/cart') {
            return;
        }
        
        // In case the extension is disabled, do nothing
        if (!$this->config->get('payment_allsecureexchange_status')) {
            return;
        }

        $egassemrorre = '';
        if (isset($this->request->get['egassemrorre'])) {
            $egassemrorre = $this->request->get['egassemrorre'];
        } else if (isset($this->request->get['amp;egassemrorre'])) {
            $egassemrorre = $this->request->get['amp;egassemrorre'];
        }

        if (!empty($egassemrorre)) {
            $params['message'] = base64_decode($egassemrorre);
            $content = $this->load->view('extension/allsecureexchange/payment/errormessage', $params);

            $find = '<ul class="breadcrumb">';
            $output = str_replace($find, $content.$find, $output);
        }
    }
}