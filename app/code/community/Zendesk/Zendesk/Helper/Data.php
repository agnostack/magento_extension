<?php
/**
 * Zendesk Magento integration
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to The MIT License (MIT) that is bundled with
 * this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/mit-license.php
 *
 * @copyright Copyright (c) 2012 Zendesk (www.zendesk.com)
 * @license http://opensource.org/licenses/mit-license.php The MIT License (MIT)
 */

class Zendesk_Zendesk_Helper_Data extends Mage_Core_Helper_Abstract
{

    public function getUrl($object = '', $id = null, $format = 'old')
    {
        $protocol = 'https://';
        $domain = Mage::getStoreConfig('zendesk/general/domain');
        $root = ($format === 'old') ? '' : '/agent/#';

        $base = $protocol . $domain . $root;

        switch($object) {
            case '':
                return $base;
                break;

            case 'ticket':
                return $base . '/tickets/' . $id;
                break;

            case 'user':
                return $base . '/users/' . $id;
                break;

            case 'raw':
                return $protocol . $domain . '/' . $id;
                break;
        }
    }

    public function getApiToken($generate = true)
    {
        // Grab any existing token from the admin scope
        $token = Mage::getStoreConfig('zendesk/api/token', 0);

        if(!$token && $generate) {
            // If no token exists currently, then generate a new one
            $token = md5(time());
            Mage::getModel('core/config')->saveConfig('zendesk/api/token', $token, 'default');
        }

        return $token;
    }

    public function setApiToken($token = null)
    {
        if(!$token) {
            $token = md5(time());
        }
        Mage::getModel('core/config')->saveConfig('zendesk/api/token', $token, 'default');

        return $token;
    }

    public function getProvisionToken($generate = false)
    {
        $token = Mage::getStoreConfig('zendesk/hidden/provision_token', 0);

        if(!$token && $generate) {
            // If no token exists currently, then generate a new one
            $token = md5(time());
            Mage::getModel('core/config')->saveConfig('zendesk/hidden/provision_token', $token, 'default');
        }

        return $token;
    }

    public function setProvisionToken($token = null)
    {
        if(!$token) {
            $token = md5(time());
        }

        Mage::getModel('core/config')->saveConfig('zendesk/hidden/provision_token', $token, 'default');

        return $token;
    }

    public function getOrderDetail($order)
    {
        $orderInfo = array(
            'id' => $order->getIncrementId(),
            'status' => $order->getStatus(),
            'created' => $order->getCreatedAt(),
            'updated' => $order->getUpdatedAt(),
            'customer' => array(
                'name' => $order->getCustomerName(),
                'email' => $order->getCustomerEmail(),
                'ip' => $order->getRemoteIp(),
                'guest' => (bool)$order->getCustomerIsGuest(),
            ),
            'store' => $order->getStoreName(),
            'total' => $order->getGrandTotal(),
            'currency' => $order->getOrderCurrencyCode(),
            'items' => array(),
            'admin_url' => Mage::helper('adminhtml')->getUrl('adminhtml/sales_order/view', array('order_id' => $order->getId())),
        );

        foreach($order->getItemsCollection(array(), true) as $item) {
            $orderInfo['items'][] = array(
                'sku' => $item->getSku(),
                'name' => $item->getName(),
            );
        }

        return $orderInfo;
    }

    public function getSupportEmail($store = null)
    {
        $domain = Mage::getStoreConfig('zendesk/general/domain', $store);
        $email = 'support@' . $domain;

        return $email;
    }
} 
