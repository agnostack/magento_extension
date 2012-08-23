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


class Zendesk_Zendesk_Model_Api_Abstract extends Mage_Core_Model_Abstract
{
    protected function _getUrl($path)
    {
        $base_url = 'https://' . Mage::getStoreConfig('zendesk/general/domain') . '/api/v2';
        $path = trim($path, '/');
        return $base_url . '/' . $path;
    }

    protected function _call($endpoint, $params = null, $method = 'GET', $data = null)
    {
        if($params && is_array($params) && count($params) > 0) {
            $args = array();
            foreach($params as $arg => $val) {
                $args[] = urlencode($arg) . '=' . urlencode($val);
            }
            $endpoint .= '?' . implode('&', $args);
        }

        $url = $this->_getUrl($endpoint);

        $method = strtoupper($method);

        $client = new Zend_Http_Client($url);
        $client->setMethod($method);
        $client->setHeaders(
            array(
                 'Accept' => 'application/json',
                 'Content-Type' => 'application/json'
            )
        );
        $client->setAuth(
            Mage::getStoreConfig('zendesk/general/email') . '/token',
            Mage::getStoreConfig('zendesk/general/password')
        );

        if($method == 'POST') {
            $client->setRawData(json_encode($data), 'application/json');
        }

        Mage::log(
            print_r(
                array(
                   'url' => $url,
                   'method' => $method,
                   'data' => json_encode($data),
                ),
                true
            ),
            null,
            'zendesk.log'
        );

        $response = $client->request();
        $body = json_decode($response->getBody(), true);

        Mage::log(var_export($body, true), null, 'zendesk.log');

        if($response->isError()) {
            if(is_array($body) && isset($body['error'])) {
                throw new Exception($body['error']['title'], $response->getStatus());
            } else {
                throw new Exception($body, $response->getStatus());
            }
        }

        return $body;
    }
}