<?php
namespace Opencart\Catalog\Model\Extension\Allsecureexchange\Payment;

class Allsecureexchange extends \Opencart\System\Engine\Model
{
    
    /**
     * Payment Module method handler
     *
     * @param object $address
     * 
     * return array
     */
    public function getMethod($address)
    {
        $this->load->language('extension/allsecureexchange/payment/allsecureexchange');

        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)$this->config->get('payment_hitpay_geo_zone_id') . "' AND country_id = '" . (int)$address['country_id'] . "' AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')");

        $status = $this->config->get('payment_allsecureexchange_status');
        if ($status) {
            if (!$this->config->get('payment_allsecureexchange_geo_zone_id')) {
                    $status = true;
            } elseif ($query->num_rows) {
                    $status = true;
            } else {
                    $status = false;
            }
        }

        $method_data = array();

        $title = $this->config->get('payment_allsecureexchange_title');
        $title = trim($title);
        if (empty($title)) {
            $title = $this->language->get('text_title');
        }

        if ($status) {
            $method_data = array(
                'code'       => 'allsecureexchange',
                'title'      => $title,
                'terms'      => '',
                'sort_order' => $this->config->get('payment_allsecureexchange_sort_order')
            );
        }

        return $method_data;
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