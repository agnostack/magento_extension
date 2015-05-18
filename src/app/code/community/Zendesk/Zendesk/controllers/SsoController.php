<?php
/**
 * Copyright 2013 Zendesk.
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

require_once(Mage::getModuleDir('', 'Zendesk_Zendesk') . DS . 'Helper' . DS . 'JWT.php');

class Zendesk_Zendesk_SsoController extends Mage_Core_Controller_Front_Action
{
    /**
     * Used by the Zendesk single sign on functionality to authenticate users.
     * This method is used for end-users, not administrators.
     */
    public function loginAction()
    {
        $return_url = Mage::helper('core')->urlDecode($this->getRequest()->getParam('return_url', ""));
        if(!Mage::getStoreConfig('zendesk/sso_frontend/enabled')) {
            $this->_redirectUrl($return_url ? $return_url : Mage::helper('zendesk')->getZendeskUnauthUrl());
            return $this;
        }

        $domain = Mage::getStoreConfig('zendesk/general/domain');
        $token = Mage::getStoreConfig('zendesk/sso_frontend/token');
        
        if(!Zend_Validate::is($domain, 'NotEmpty')) {
            Mage::log(Mage::helper('zendesk')->__('Zendesk domain not set. Please add this to the settings page.'), null, 'zendesk.log');
            $this->_redirect('/');
            return $this;
        }

        if(!Zend_Validate::is($token, 'NotEmpty')) {
            Mage::log(Mage::helper('zendesk')->__('Zendesk SSO token not set. Please add this to the settings page.'), null, 'zendesk.log');
            $this->_redirect('/');
            return $this;
        }

        // Attempt to authenticate the customer, which will try and log them in, if they aren't already.
        // If the customer is not logged in they should be redirected to the login form, then redirected back here
        // on success.
        if (!Mage::getSingleton('customer/session')->authenticate($this)) {
            $this->setFlag('', self::FLAG_NO_DISPATCH, true);
            return $this;
        }

        $now = time();
        $jti = md5($now . rand());

        $user = Mage::getSingleton('customer/session')->getCustomer();
        $name = $user->getName();
        $email = $user->getEmail();
        $externalId = $user->getEntityId();

        $payload = array(
            "iat" => $now,
            "jti" => $jti,
            "name" => $name,
            "email" => $email
        );

        // Validate if we need to include external_id param
        $externalIdEnabled = Mage::helper('zendesk')->isExternalIdEnabled();
        if($externalIdEnabled) {
            $payload['external_id'] = $user->getId();
        }

        Mage::log('End-user JWT: ' . var_export($payload, true), null, 'zendesk.log');

        $jwt = JWT::encode($payload, $token);
        $return_url = $return_url ? "&return_to=".$return_url : "";
        
        $url = "https://".$domain."/access/jwt?jwt=" . $jwt.$return_url;

        Mage::log('End-user URL: ' . $url, null, 'zendesk.log');

        $this->_redirectUrl($url);
    }

    public function logoutAction()
    {
        // The logout method should already be doing standard checks for whether the customer is already logged in
        Mage::getSingleton('customer/session')->logout();
        $this->_redirect('/');
    }
}
