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

class Zendesk_Zendesk_ApiController extends Mage_Core_Controller_Front_Action
{

    public function _authorise()
    {
        // Perform some basic checks before running any of the API methods
        // Note that authorisation will accept either the provisioning or the standard API token, which facilitates API
        // methods being called during the setup process
        $tokenString = $this->getRequest()->getHeader('authorization');

        if(!$tokenString && isset($_SERVER['Authorization'])) {
            $tokenString = $_SERVER['Authorization'];
        }

        if(!$tokenString && isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $tokenString = $_SERVER['HTTP_AUTHORIZATION'];
        }
        
        if (!$tokenString && isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $tokenString = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        }

        if (!$tokenString) {
            // Certain server configurations fail to extract headers from the request, see PR #24.
            Mage::log('Unable to extract authorization header from request.', null, 'zendesk.log');

            $this->getResponse()
                ->setBody(json_encode(array('success' => false, 'message' => 'Unable to extract authorization header from request')))
                ->setHttpResponseCode(403)
                ->setHeader('Content-type', 'application/json', true);

            return false;
        }

        $tokenString = stripslashes($tokenString);

        $token = null;
        $matches = array();
        if(preg_match('/Token token="([a-z0-9]+)"/', $tokenString, $matches)) {
            $token = $matches[1];
        }

        $apiToken = Mage::helper('zendesk')->getApiToken(false);
        $provisionToken = Mage::helper('zendesk')->getProvisionToken(false);

        // Provisioning tokens are always accepted, hence why they are deleted after the initial process
        if(!$provisionToken || $token != $provisionToken) {
            // Use of the provisioning token "overrides" the configuration for the API, so we check this after
            // confirming the provisioning token has not been sent
            if(!Mage::getStoreConfig('zendesk/api/enabled')) {
                $this->getResponse()
                    ->setBody(json_encode(array('success' => false, 'message' => 'API access disabled')))
                    ->setHttpResponseCode(403)
                    ->setHeader('Content-type', 'application/json', true);

                Mage::log('API access disabled.', null, 'zendesk.log');

                return false;
            }

            // If the API is enabled then check the token
            if(!$token) {
                $this->getResponse()
                    ->setBody(json_encode(array('success' => false, 'message' => 'No authorisation token provided')))
                    ->setHttpResponseCode(401)
                    ->setHeader('Content-type', 'application/json', true);

                Mage::log('No authorisation token provided.', null, 'zendesk.log');

                return false;
            }

            if($token != $apiToken) {
                $this->getResponse()
                    ->setBody(json_encode(array('success' => false, 'message' => 'Not authorised')))
                    ->setHttpResponseCode(401)
                    ->setHeader('Content-type', 'application/json', true);

                Mage::log('Not authorised.', null, 'zendesk.log');

                return false;
            }
        }

        return true;
    }

    public function ordersAction($orderId)
    {
        if(!$this->_authorise()) {
            return $this;
        }

        $sections = explode('/', trim($this->getRequest()->getPathInfo(), '/'));
        $orderId = $sections[3];

        $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);

        if(!$order && !$order->getId()) {
            $this->getResponse()
                ->setBody(json_encode(array('success' => false, 'message' => 'Order does not exist')))
                ->setHttpResponseCode(404)
                ->setHeader('Content-type', 'application/json', true);
            return $this;
        }

        $info = Mage::helper('zendesk')->getOrderDetail($order);

        $this->getResponse()
            ->setBody(json_encode($info))
            ->setHttpResponseCode(200)
            ->setHeader('Content-type', 'application/json', true);
        return $this;
    }


    public function customersAction()
    {
        if(!$this->_authorise()) {
            return $this;
        }

        $sections = explode('/', trim($this->getRequest()->getPathInfo(), '/'));
        $email = $sections[3];

        // Get a list of all orders for the given email address
        // This is used to determine if a missing customer is a guest or if they really aren't a customer at all
        $orderCollection = Mage::getModel('sales/order')->getCollection()
            ->addFieldToFilter('customer_email', array('eq' => array($email)));
        $orders = array();
        if($orderCollection->getSize()) {
            foreach($orderCollection as $order) {
                $orders[] = Mage::helper('zendesk')->getOrderDetail($order);
            }
        }

        // Try to load a corresponding customer object for the provided email address
        $customer = Mage::helper('zendesk')->loadCustomer($email);

        // if the admin site has a custom URL, use it
        $urlModel = Mage::getModel('adminhtml/url')->setStore('admin');

        if($customer && $customer->getId()) {
            $info = array(
                'guest' => false,
                'id' => $customer->getId(),
                'name' => $customer->getName(),
                'email' => $customer->getEmail(),
                'active' => (bool)$customer->getIsActive(),
                'admin_url' => $urlModel->getUrl('adminhtml/zendesk/redirect', array('id' => $customer->getId(), 'type' => 'customer')),
                'created' => $customer->getCreatedAt(),
                'dob' => $customer->getDob(),
                'addresses' => array(),
                'orders' => $orders,
            );

            if($billing = $customer->getDefaultBillingAddress()) {
                $info['addresses']['billing'] = $billing->format('text');
            }

            if($shipping = $customer->getDefaultShippingAddress()) {
                $info['addresses']['shipping'] = $shipping->format('text');
            }

        } else {
            if(count($orders) == 0) {
                // The email address doesn't even correspond with a guest customer
                $this->getResponse()
                    ->setBody(json_encode(array('success' => false, 'message' => 'Customer does not exist')))
                    ->setHttpResponseCode(404)
                    ->setHeader('Content-type', 'application/json', true);
                return $this;
            }

            $info = array(
                'guest' => true,
                'orders' => $orders,
            );
        }

        $this->getResponse()
            ->setBody(json_encode($info))
            ->setHttpResponseCode(200)
            ->setHeader('Content-type', 'application/json', true);
        return $this;
    }

    public function usersAction()
    {
        if(!$this->_authorise()) {
            return $this;
        }

        $sections = explode('/', trim($this->getRequest()->getPathInfo(), '/'));
        $users = array();

        if(isset($sections[3])) {
            // Looking for a specific user
            $userId = $sections[3];

            $user = Mage::getModel('admin/user')->load($userId);

            if(!$user && !$user->getId()) {
                $this->getResponse()
                    ->setBody(json_encode(array('success' => false, 'message' => 'User does not exist')))
                    ->setHttpResponseCode(404)
                    ->setHeader('Content-type', 'application/json', true);
                return $this;
            }

            $users[] = array(
                'id' => $user->getId(),
                'given_name' => $user->getFirstname(),
                'family_name' => $user->getLastname(),
                'username' => $user->getUsername(),
                'email' => $user->getEmail(),
                'active' => (bool)$user->getIsActive(),
                'role' => $user->getRole()->getRoleName(),
            );

        } else {
            // Looking for a list of users
            $offset = $this->getRequest()->getParam('offset', 0);
            $page_size = $this->getRequest()->getParam('page_size', 100);
            $sort = $this->getRequest()->getParam('sort', 'firstname');

            switch($sort) {
                case 'given_name':
                case 'givenname':
                case 'first_name':
                    $sort = 'firstname';
                    break;

                case 'family_name':
                case 'familyname':
                case 'last_name':
                    $sort = 'lastname';
                    break;
            }

            $userCol = Mage::getModel('admin/user')->getCollection();
            $userCol->getSelect()->limit($page_size, ($offset * $page_size))->order($sort);

            foreach($userCol as $user) {
                $users[] = array(
                    'id' => $user->getId(),
                    'given_name' => $user->getFirstname(),
                    'family_name' => $user->getLastname(),
                    'username' => $user->getUsername(),
                    'email' => $user->getEmail(),
                    'active' => (bool)$user->getIsActive(),
                    'role' => $user->getRole()->getRoleName(),
                );
            }
        }

        $this->getResponse()
            ->setBody(json_encode(array('users' => $users)))
            ->setHttpResponseCode(200)
            ->setHeader('Content-type', 'application/json', true);

        return $this;
    }

    public function finaliseAction()
    {
        if(!$this->_authorise()) {
            return $this;
        }

        $data = $this->getRequest()->getPost();

        $missingFields = array();
        $configUpdates = array();

        // Required fields
        if(!isset($data['zendesk_domain'])) {
            $missingFields[] = 'zendesk_domain';
        } else {
            $configUpdates['zendesk/general/domain'] = $data['zendesk_domain'];
        }

        if(!isset($data['agent_email'])) {
            $missingFields[] = 'agent_email';
        } else {
            $configUpdates['zendesk/general/email'] = $data['agent_email'];
        }

        if(!isset($data['agent_token'])) {
            $missingFields[] = 'agent_token';
        } else {
            $configUpdates['zendesk/general/password'] = $data['agent_token'];
        }

        if(!isset($data['order_field_id'])) {
            $missingFields[] = 'order_field_id';
        } else {
            $configUpdates['zendesk/frontend_features/order_field_id'] = $data['order_field_id'];
        }

        // Check that the required fields were provided and send back an error if not
        if(count($missingFields)) {
            $this->getResponse()
                ->setBody(
                    json_encode(
                        array(
                             'success' => false,
                             'message' => 'Missing fields: ' . implode(',', $missingFields)
                        )
                    )
                )
                ->setHttpResponseCode(400)
                ->setHeader('Content-type', 'application/json', true);
            return $this;
        }

        // Optional fields
        if(!isset($data['zendesk_remote_auth_token'])) {
            $missingFields[] = 'zendesk_remote_auth_token';
        } else {
            $configUpdates['zendesk/sso/token'] = $data['zendesk_remote_auth_token'];
        }

        if(isset($data['single_sign_on'])) {
            $configUpdates['zendesk/sso/enabled'] = ($data['single_sign_on'] == 'true');
        }

        if(isset($data['magento_footer_link'])) {
            $configUpdates['zendesk/frontend_features/footer_link_enabled'] = ($data['magento_footer_link'] == 'true');
        }

        if(isset($data['email_forwarding'])) {
            $configUpdates['zendesk/frontend_features/contact_us'] = ($data['email_forwarding'] == 'true');

            // Process this now, since it otherwise won't be triggered until the config page is saved
            // Unlike in the observer, we only need to deal with the case where the setting is enabled
            if($configUpdates['zendesk/frontend_features/contact_us']) {

                $currentEmail = Mage::getStoreConfig('contacts/email/recipient_email');
                $zendeskEmail = 'support@' . $configUpdates['zendesk/general/domain'];

                // If the email is already set, then do nothing
                if($currentEmail !== $zendeskEmail) {
                    // Ensure the email address value exists and is valid
                    if(Zend_Validate::is($zendeskEmail, 'EmailAddress')) {
                        Mage::getModel('core/config')->saveConfig('zendesk/hidden/contact_email_old', $currentEmail);
                        Mage::getModel('core/config')->saveConfig('contacts/email/recipient_email', $zendeskEmail);
                    }
                }
            }
        }

        if(isset($data['web_widget_code_active'])) {
            $configUpdates['zendesk/frontend_features/web_widget_code_active'] = ($data['web_widget_code_active'] === 'true');
        }

        if(isset($data['web_widget_code_snippet'])) {
            $configUpdates['zendesk/frontend_features/web_widget_code_snippet'] = $data['web_widget_code_snippet'];
        }


        // Save all of the details sent
        foreach($configUpdates as $path => $value) {
            Mage::getModel('core/config')->saveConfig($path, $value, 'default');
        }

        // Clear the provisioning token so it can't be used any further
        Mage::getModel('core/config')->saveConfig('zendesk/hidden/provision_token', null, 'default');

        Mage::getConfig()->removeCache();

        $this->getResponse()
            ->setBody(json_encode(array('success' => true)))
            ->setHttpResponseCode(200)
            ->setHeader('Content-type', 'application/json', true);
        return $this;
    }
}
