<?php
/**
 * Copyright 2012 Zendesk.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

class Zendesk_Zendesk_Model_Api_Abstract extends Mage_Core_Model_Abstract
{
    protected function _getUrl($path)
    {
        if(strpos($path, 'update_number') === false) {
            $base_url = 'https://' . Mage::getStoreConfig('zendesk/general/domain') . '/api/v2';
        }
        else {
            $base_url = 'https://' . Mage::getStoreConfig('zendesk/general/domain');
        }
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

        if($method == 'POST' || $method == 'PUT') {
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
                if(is_array($body['error']) && isset($body['error']['title'])) {
                    throw new Exception($body['error']['title'], $response->getStatus());
                } else if(is_array($body) && isset($body[0][1]) && strpos($endpoint, 'update_number') !== false) {
                throw new Exception($body[0][1]);
            	} else {
                    throw new Exception($body['error'], $response->getStatus());
                }
            } else {
                throw new Exception($body, $response->getStatus());
            }
        }

        return $body;
    }
}