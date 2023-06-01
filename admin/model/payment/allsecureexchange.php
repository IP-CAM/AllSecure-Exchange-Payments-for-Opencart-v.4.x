<?php
namespace Opencart\Admin\Model\Extension\Allsecureexchange\Payment;

class Allsecureexchange extends \Opencart\System\Engine\Model
{
    /**
     * Module Installer
     * 
     * return void
     */
    public function install()
    {
        $this->db->query("
                CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "allsecureexchange_order` (
                  `order_id` int(11) NOT NULL,
                  transaction_mode varchar(100),
                  checkout_type varchar(100),
                  transaction_type varchar(100),
                  transaction_id varchar(255),
                  status varchar(100),
                  `response` TEXT
                ) ENGINE=MyISAM DEFAULT COLLATE=utf8_general_ci;");
        $this->load->model('setting/event');

        $eventData = [
            'code' => 'payment_allsecureexchange_admin_order_info',
            'description' => 'To display Allsecureexchange payment information',
            'trigger' => 'admin/view/sale/order_info/before',
            'action' => 'extension/allsecureexchange/payment/allsecureexchange|order_info',
            'status' => 1,
            'sort_order' => 1
        ];
        $this->model_setting_event->addEvent($eventData);

        $eventData = [
            'code' => 'payment_allsecureexchange_catalog_success_payment_info',
            'description' => 'To display Allsecureexchange payment information',
            'trigger' => 'catalog/view/common/success/after',
            'action' => 'extension/allsecureexchange/payment/allsecureexchange|success_payment_info',
            'status' => 1,
            'sort_order' => 1
        ];
        $this->model_setting_event->addEvent($eventData);
        
        $eventData = [
            'code' => 'payment_allsecureexchange_catalog_checkout_script',
            'description' => 'To add javascript into the page',
            'trigger' => 'catalog/view/checkout/checkout/after',
            'action' => 'extension/allsecureexchange/payment/allsecureexchange|checkout_after',
            'status' => 1,
            'sort_order' => 5
        ];
        $this->model_setting_event->addEvent($eventData);
        
        $eventData = [
            'code' => 'payment_allsecureexchange_catalog_cart_errormessage',
            'description' => 'To display error message into the page',
            'trigger' => 'catalog/view/checkout/cart/after',
            'action' => 'extension/allsecureexchange/payment/allsecureexchange|cart_after',
            'status' => 1,
            'sort_order' => 5
        ];
        $this->model_setting_event->addEvent($eventData);
        
        $eventData = [
            'code' => 'payment_allsecureexchange_catalog_error_errormessage',
            'description' => 'To display error message into the page',
            'trigger' => 'catalog/view/error/not_found/after',
            'action' => 'extension/allsecureexchange/payment/allsecureexchange|error_after',
            'status' => 1,
            'sort_order' => 5
        ];
        $this->model_setting_event->addEvent($eventData);
    }
    
    /**
     * Module Uninstaller
     * 
     * return void
     */
    public function uninstall()
    {
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "allsecureexchange_order`;");
        $this->load->model('setting/event');
        $this->model_setting_event->deleteEventByCode('payment_allsecureexchange_admin_order_info');
        $this->model_setting_event->deleteEventByCode('payment_allsecureexchange_catalog_success_payment_info');
        $this->model_setting_event->deleteEventByCode('payment_allsecureexchange_catalog_checkout_script');
        $this->model_setting_event->deleteEventByCode('payment_allsecureexchange_catalog_cart_errormessage');
        $this->model_setting_event->deleteEventByCode('payment_allsecureexchange_catalog_error_errormessage');
    }

        /**
     * Get Transaction By Order Id
     *
     * @param string $order_id
     * 
     * return string
     */
    public function getTransactionByOrderId($order_id)
    {
        $qry = $this->db->query("SELECT * FROM `" . DB_PREFIX . "allsecureexchange_order` WHERE order_id = '" . (int)$order_id . "' LIMIT 1");

        if ($qry->num_rows) {
            $row = $qry->row;
            return $row;
        } else {
            return false;
        }
    }

    /**
     * Update Transaction
     *
     * @param string $order_id
     * @param string $column
     * @param string $value
     * 
     * return void
     */
    public function updateTransaction($order_id, $column, $value)
    {
        $transaction = $this->getTransactionByOrderId($order_id);
        if ($transaction) {
            $this->db->query("UPDATE " . DB_PREFIX . "allsecureexchange_order"
                    . " SET {$column} = '" . $this->db->escape($value) . "'"
                    . " WHERE order_id = '" . (int)$order_id . "'");
        } else {
            $this->db->query("INSERT INTO `" . DB_PREFIX . "allsecureexchange_order`"
                . " SET {$column} = '" . $this->db->escape($value) . "', "
                . "order_id = {$order_id}"
            );
        }
    }
    
    /**
     * Get transaction single record
     *
     * @param string $order_id
     * @param string $column
     * 
     * return string
     */
    public function getTransactionSingle($order_id, $column)
    {
        $qry = $this->db->query('select '.$column.' FROM ' . DB_PREFIX.'allsecureexchange_order WHERE order_id='.(int)($order_id));
        if ($qry->num_rows) {
                $row = $qry->row;
                return $row[$column];
        } else {
                return false;
        }
    }
    
    /**
     * Get transaction response value
     *
     * @param string $order_id
     * @param string $key
     * 
     * return string
     */
    public function getTransactionResponseSingle($order_id, $key)
    {
        $response = $this->getTransactionSingle($order_id ,'response');
        if ($response) {
            $result = json_decode($response, true);
            if (isset($result[$key])) {
                return $result[$key];
            }
        }
        return false;
    }
    
    /**
     * Update transaction response value
     *
     * @param string $order_id
     * @param string $param
     * @param string $value
     * 
     * return void
     */
    public function updateTransactionResponse($order_id, $param, $value)
    {
        $metaData = $this->getTransactionSingle($order_id ,'response');
        if (!empty($metaData)) {
            $metaData = json_decode($metaData, true);
            $metaData[$param] = $value;
            $paymentData = json_encode($metaData);
            
            $this->db->query("UPDATE " . DB_PREFIX . "allsecureexchange_order"
                    . " SET response = '" . $this->db->escape($paymentData) . "'"
                    . " WHERE order_id = '" . (int)$order_id . "'");
        }
    }

    /**
     * Unset transaction response key
     *
     * @param string $order_id
     * @param string $param
     * 
     * return void
     */
    public function deleteTransactionResponse($order_id, $param)
    {
        $metaData = $this->getTransactionSingle($order_id ,'response');
        if (!empty($metaData)) {
            $metaData = json_decode($metaData, true);
            if (isset($metaData[$param])) {
                unset($metaData[$param]);
            }
            $paymentData = json_encode($metaData);
            
            $this->db->query("UPDATE " . DB_PREFIX . "allsecureexchange_order"
                    . " SET response = '" . $this->db->escape($paymentData) . "'"
                    . " WHERE order_id = '" . (int)$order_id . "'");
        }
    }

    /**
     * Logger
     *
     * @param mixed $content
     * 
     * return void
     */
    public function log($content)
    {
        $debug = $this->config->get('payment_allsecureexchange_logging');
        if ($debug == true) {
            $file = DIR_STORAGE.'logs/allsecureexchange.log';
            $fp = fopen($file, 'a+');
            if ($fp) {
                fwrite($fp, "\n");
                fwrite($fp, date("Y-m-d H:i:s").": ");
                fwrite($fp, print_r($content, true));
                fclose($fp);
            }
        }
    }
}