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

require_once( Mage::getModuleDir('base'). 'community/Zendesk/Zendesk/Helper/JWT.php');

class Zendesk_Zendesk_Adminhtml_ZendeskController extends Mage_Adminhtml_Controller_Action
{
    protected $_publicActions = array('redirect', 'authenticate');

    public function indexAction()
    {
        $this->_title($this->__('Zendesk Dashboard'));
        $this->loadLayout();
        $this->_setActiveMenu('zendesk/zendesk_dashboard');
        $this->renderLayout();
    }

    public function redirectAction()
    {
        $type = $this->getRequest()->getParam('type');
        $id = $this->getRequest()->getParam('id');

        if($id && $type && in_array($type, array('customer','order','settings'))) {
            switch($type) {
                case 'settings':
                    $this->_redirect('adminhtml/system_config/edit/section/zendesk');
                    break;

                case 'customer':
                    $this->_redirect('adminhtml/customer/edit', array('id' => $id));
                    break;

                case 'order':
                    $this->_redirect('adminhtml/sales_order/view', array('order_id' => $id));
                    break;
            }
        } else {
            $this->_redirect(Mage::getSingleton('admin/session')->getUser()->getStartupPageUrl());
        }
    }

    /**
     * Used by the Zendesk single sign on functionality to authenticate users.
     * Currently only works for admin panel users, not for customers.
     */
    public function authenticateAction()
    {
        if(!Mage::getStoreConfig('zendesk/sso/enabled')) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('zendesk')->__('Single sign-on disabled.'));
            $this->_redirect(Mage::getSingleton('admin/session')->getUser()->getStartupPageUrl());
        }

        $domain = Mage::getStoreConfig('zendesk/general/domain');
        $token = Mage::getStoreConfig('zendesk/sso/token');

        if(!Zend_Validate::is($domain, 'NotEmpty')) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('zendesk')->__('Zendesk domain not set. Please add this to the settings page.'));
            $this->_redirect(Mage::getSingleton('admin/session')->getUser()->getStartupPageUrl());
        }

        if(!Zend_Validate::is($token, 'NotEmpty')) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('zendesk')->__('Zendesk SSO token not set. Please add this to the settings page.'));
            $this->_redirect(Mage::getSingleton('admin/session')->getUser()->getStartupPageUrl());
        }

        $now = time();
        $jti = md5($now . rand());

        $user = Mage::getSingleton('admin/session')->getUser();
        $name = $user->getName();
        $email = $user->getEmail();
        $externalId = $user->getId();

        $payload = array(
          "iat" => $now,
          "jti" => $jti,
          "name" => $name,
          "email" => $email,
          "external_id" => $externalId
        );

        Mage::log(var_export($payload, true), null, 'zendesk.log');

        $jwt = JWT::encode($payload, $token);

        $url = "http://".$domain."/access/jwt?jwt=" . $jwt;

        Mage::log(var_export($url, true), null, 'zendesk.log');

        $this->_redirectUrl($url);
    }

    public function createAction()
    {
        // Check if we have been passed an order ID, in which case we can preload some of the form details
        if($orderId = $this->getRequest()->getParam('order_id')) {
            $order = Mage::getModel('sales/order')->load($orderId);
            $store = Mage::getModel('core/store')->load($order->getStoreId());
            $data = array(
                'order_id' => $orderId,
                'order' => $order->getIncrementId(),
                'requester' => $order->getCustomerEmail(),
                'requester_name' => $order->getCustomerName(),
                'website_id' => $store->getWebsiteId(),
            );

            Mage::register('zendesk_create_data', $data, true);
        }

        $this->_title($this->__('Zendesk Create Ticket'));
        $this->loadLayout();
        $this->_setActiveMenu('zendesk/zendesk_create');

        // Add the custom JavaScript for the customer autocomplete
        $block = $this->getLayout()->createBlock(
            'Mage_Core_Block_Template',
            'customer_email_autocomplete',
            array('template' => 'zendesk/autocomplete.phtml')
        );
        $this->getLayout()->getBlock('js')->append($block);

        $this->renderLayout();
    }

    public function launchAction()
    {
        $this->_redirectUrl(Mage::helper('zendesk')->getUrl());
    }

    public function saveAction()
    {
        if ($data = $this->getRequest()->getPost()) {

            if($orderId = $this->getRequest()->getParam('order_id')) {
                $data['order_id'] = $orderId;
            }

            // Look up customer details to see if there's an existing requester_id assigned
            $requesterId = null;
            $requesterEmail = trim($data['requester']);
            $requesterName = trim($data['requester_name']);

            $customer = null;
            if(Mage::getModel('customer/customer')->getSharingConfig()->isWebsiteScope()) {
                // Customer email address can be used in multiple websites so we need to
                // explicitly scope it
                $customer = Mage::getModel('customer/customer')
                    ->setWebsiteId($data['website_id'])
                    ->loadByEmail($data['requester']);
            } else {
                // Customer email is global, so no scoping issues
                $customer = Mage::getModel('customer/customer')
                    ->loadByEmail($data['requester']);
            }

            // Check if a valid customer has been loaded
            if($customer->getId()) {
                // Provided for future expansion, where we might want to store the customer's requester ID for
                // convenience; for now it simply returns null
                $requesterId = $customer->getZendeskRequesterId();

                // If the requester name hasn't already been set, then set it to the customer name
                if(strlen($requesterName) == 0) {
                    $requesterName = $customer->getName();
                }
            }

            if($requesterId == null) {
                // See if the requester already exists in Zendesk
                try {
                    $user = Mage::getModel('zendesk/api_requesters')->find($requesterEmail);
                } catch (Exception $e) {
                    // Continue on, no need to show an alert for this
                    $user = null;
                }

                if($user) {
                    $requesterId = $user['id'];
                } else {
                    // Create the requester as they obviously don't exist in Zendesk yet
                    try {
                        // First check if the requesterName has been provided, since we need that to create a new
                        // user (but if one exists already then it doesn't need to be filled out in the form)
                        if(strlen($requesterName) == 0) {
                            throw new Exception('Requester name not provided for new user');
                        }

                        // All the data we need seems to exist, so let's create a new user
                        $user = Mage::getModel('zendesk/api_requesters')->create($requesterEmail, $requesterName);
                        $requesterId = $user['id'];
                    } catch(Exception $e) {
                        Mage::getSingleton('adminhtml/session')->addError($e->getCode() . ': ' . $e->getMessage());
                        Mage::register('zendesk_create_data', $data, true);
                        $this->_redirect('*/*/create');
                    }
                }
            }

            try {
                $ticket = array(
                    'ticket' => array(
                        'requester_id' => $requesterId,
                        'subject' => $data['subject'],
                        'status' => $data['status'],
                        'priority' => $data['priority'],
                        'comment' => array(
                            'value' => $data['description']
                        )
                    )
                );

                if(isset($data['type']) && strlen(trim($data['type'])) > 0) {
                    $ticket['ticket']['type'] = $data['type'];
                }

                if( ($fieldId = Mage::getStoreConfig('zendesk/features/order_field_id')) && isset($data['order']) && strlen(trim($data['order'])) > 0) {
                    $ticket['ticket']['fields'] = array(
                        'id' => $fieldId,
                        'value' => $data['order']
                    );
                }

                $response = Mage::getModel('zendesk/api_tickets')->create($ticket);

                $text = Mage::helper('zendesk')->__('Ticket #%s Created.', $response['id']);
                $text .= ' <a href="' . Mage::helper('zendesk')->getUrl('ticket', $response['id']) . '" target="_blank">';
                $text .= Mage::helper('zendesk')->__('View ticket in Zendesk');
                $text .= '</a>';

                Mage::getSingleton('adminhtml/session')->addSuccess($text);
            } catch(Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getCode() . ': ' . $e->getMessage());
                Mage::register('zendesk_create_data', $data, true);
            }
        }
        $this->_redirect('*/*/create');
    }

    public function generateAction()
    {
        try {
            Mage::helper('zendesk')->setApiToken();
            Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('zendesk')->__('Successfully generated new API token'));
        } catch(Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getCode() . ': ' . $e->getMessage());
        }

        $this->_redirect('adminhtml/system_config/edit/section/zendesk');
    }

    /*
     * Sends back an HTML unordered list for use in the Scriptaculous Autcomplete call.
     */
    public function autocompleteAction()
    {
        $query = $this->getRequest()->getParam('requester');

        $customers = Mage::getModel('customer/customer')
            ->getCollection()
            ->addNameToSelect()
            ->addFieldToFilter('email', array('like' => '%' . $query . '%'));

        $output = '<ul>';
        if($customers->getSize()) {
            foreach($customers as $customer) {
                $id = $customer->getId();
                $name = $customer->getName();
                $email = $customer->getEmail();
                $output .= '<li id="customer-' . $id . '" data-email="' . $email . '" data-name="' . $name . '">' . $name . ' &lt;' . $email . '&gt;</li>';
            }
        }
        $output .= '</ul>';

        $this->getResponse()->setBody($output);
    }
}
