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

class Zendesk_Zendesk_Adminhtml_ZendeskController extends Mage_Adminhtml_Controller_Action
{
    protected $_publicActions = array('redirect', 'authenticate');

    public function indexAction()
    {
        if (!$this->_domainConfigured()) {
            return;
        }

        $connection = Mage::helper('zendesk')->getConnectionStatus();
        
        if(!$connection['success']) {
            $this->setFlag('', 'no-dispatch', true);
            Mage::getSingleton('adminhtml/session')->addError( $connection['msg'] );
            $this->_redirect('adminhtml/dashboard');
            return;
        }

        Mage::helper('zendesk')->storeDependenciesInCachedRegistry();
        
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
     * Only works for admin panel users, not for customers.
     */
    public function authenticateAction()
    {
        if(!Mage::getStoreConfig('zendesk/sso/enabled')) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('zendesk')->__('Single sign-on disabled.'));
            $this->_redirect(Mage::getSingleton('admin/session')->getUser()->getStartupPageUrl());
        }

        $domain = Mage::getStoreConfig('zendesk/general/domain');
        $token = Mage::getStoreConfig('zendesk/sso/token');
        $return_url = Mage::helper('core')->urlDecode($this->getRequest()->getParam('return_url', ""));

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
        $email = Mage::getStoreConfig('zendesk/general/email');
        $externalId = $user->getId();

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

        Mage::log('Admin JWT: ' . var_export($payload, true), null, 'zendesk.log');

        $jwt = JWT::encode($payload, $token);
        $return = $return_url ? "&return_to=".$return_url : "";
        
        $url = "https://".$domain."/access/jwt?jwt=" . $jwt . $return;

        Mage::log('Admin URL: ' . $url, null, 'zendesk.log');

        $this->_redirectUrl($url);
    }
    
    /**
     * Wrapper for the existing authenticate action. Mirrors the login/logout actions available for customers.
     */
    public function loginAction()
    {
        if(!Mage::getStoreConfig('zendesk/sso/enabled')) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('zendesk')->__('Single sign-on disabled.'));
            $url = Mage::helper('core')->urlDecode($this->getRequest()->getParam('return_url', ""));
            $this->_redirectUrl($url);
            return;
        }
        $this->authenticateAction();
    }

    /**
     * Log out action for SSO support.
     */
    public function logoutAction()
    {
        // Admin sessions do not currently have an explicit "logout" method (unlike customer sessions) so do this
        // manually with the session object
        $adminSession = Mage::getSingleton('admin/session');
        $adminSession->unsetAll();
        $adminSession->getCookie()->delete($adminSession->getSessionName());
        $adminSession->addSuccess(Mage::helper('adminhtml')->__('You have logged out.'));

        $this->_redirect('adminhtml/zendesk/*');
    }

    public function createAction()
    {
        if (!$this->_domainConfigured()) {
            return;
        }
        
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
        $domain = $this->_domainConfigured();
        if (!$domain) {
            return;
        }
        
        $sso = Mage::getStoreConfig('zendesk/sso/enabled');
        
        if (!$sso) {
            $url = "https://".$domain;
        } elseif(Mage::helper('zendesk')->isSSOAdminUsersEnabled()) {
            $url = Mage::helper('zendesk')->getSSOAuthUrlAdminUsers();
        } else {
            $url = Mage::helper('zendesk')->getZendeskUnauthUrl();
        }

        $this->_redirectUrl($url);
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
                $admin = Mage::getModel('zendesk/api_users')->me();
                $ticket = array(
                    'ticket' => array(
                        'requester_id' => $requesterId,
                        'submitter_id' => $admin['id'],
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

                if( ($fieldId = Mage::getStoreConfig('zendesk/frontend_features/order_field_id')) && isset($data['order']) && strlen(trim($data['order'])) > 0) {
                    $ticket['ticket']['fields'] = array(
                        'id' => $fieldId,
                        'value' => $data['order']
                    );
                }

                $response = Mage::getModel('zendesk/api_tickets')->create($ticket);

                $text = Mage::helper('zendesk')->__('Ticket #%s Created', $response['id']);
                $text .= ' <a href="' . Mage::helper('zendesk')->getTicketUrl($response, true) . '" target="_blank">';
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
            Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('zendesk')->__('Successfully generated a new API token'));
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

    public function logAction()
    {
        $path = Mage::helper('zendesk/log')->getLogPath();

        if(!file_exists($path)) {
            if (is_writable($path)) {
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('zendesk')->__('Creating a Zendesk log file.'));
                file_put_contents(" ",$path);
            } else {
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('zendesk')->__('Magento logging has been disabled or Magento log folder has incorrect permissions.'));
            }
        }

        if(Mage::helper('zendesk/log')->isLogTooLarge()) {
            Mage::getSingleton('adminhtml/session')->addNotice(Mage::helper('zendesk')->__("File size too large - only showing the last %s lines. Click Download to retrieve the entire file.", Mage::helper('zendesk/log')->getTailSize()));
        }

        $this->_title($this->__('Zendesk Log Viewer'));
        $this->loadLayout();
        $this->_setActiveMenu('zendesk/zendesk_log');
        $this->renderLayout();
    }

    public function downloadAction()
    {
        $this->_prepareDownloadResponse('zendesk.log', Mage::helper('zendesk/log')->getLogContents(false));
    }

    public function clearLogAction()
    {
        Mage::helper('zendesk/log')->clear();
        $this->_redirect('*/*/log');
    }

    public function checkOutboundAction()
    {    
        $connection = Mage::helper('zendesk')->getConnectionStatus();
            
        $this->getResponse()->clearHeaders()->setHeader('Content-type','application/json', true);
        $this->getResponse()->setBody(json_encode($connection));
        } 
            
    /**
     * Loading page block
     */
    public function loadBlockAction()
    {
        $request = $this->getRequest();

        $block = $request->getParam('block');
        $update = $this->getLayout()->getUpdate();


        $update->addHandle('adminhtml_zendesk_create_load_block_'.$block);

        $this->loadLayoutUpdates()->generateLayoutXml()->generateLayoutBlocks();
        $result = $this->getLayout()->getBlock('content')->toHtml();
        if ($request->getParam('as_js_varname')) {
            Mage::getSingleton('adminhtml/session')->setUpdateResult($result);
            $this->_redirect('*/*/showUpdateResult');
        } else {
            $this->getResponse()->setBody($result);
        }
    }
    
    public function getUserAction()
    {
        $request = $this->getRequest();
        $id = $request->getParam('id');
        
        $user = Mage::getModel('customer/customer')->load($id);
        
        $this->getResponse()->clearHeaders()->setHeader('Content-type','application/json',true);
        
        if($user->getId()) {
            $this->getResponse()->setBody(json_encode(array('success'=>true, 'usr'=> array(
                'firstname' =>  $user->getFirstname(),
                'lastname'  =>  $user->getLastname(),
                'email'     =>  $user->getEmail()
            ))));
        } else {
            $this->getResponse()->setBody(json_encode(array('success'=>false, 'msg'=>Mage::helper('zendesk')->__('User does not exist'))));
        }
    }
        
    public function getOrderAction()
    {
        $request = $this->getRequest();
        $id= $request->getParam('id');
        
        $order = Mage::getModel('sales/order')->load($id);
        
        $this->getResponse()->clearHeaders()->setHeader('Content-type','application/json',true);
        if($order->getId()) {
            $this->getResponse()->setBody(json_encode(array('success'=>true, 'order'=> array(
                'number' =>  $order->getIncrementId(),
            ))));
        } else {
            $this->getResponse()->setBody(json_encode(array('success'=>false, 'msg'=>Mage::helper('zendesk')->__('Order does not exist'))));
        }
    }
    
    public function syncAction()
    {
        $this->getResponse()->clearHeaders()->setHeader('Content-type','application/json',true);
        Mage::log('Synchronization started', null, 'zendesk.log');
        try {           
            $user = Mage::getModel('zendesk/api_users')->all();
            if (is_null($user))
                throw new Exception("Connection Failed");
            
            $data = array();
            $data[] = array(
                'user_field' => array(
                    'type'          =>  'integer',
                    'title'         =>  'ID',
                    'description'   =>  'Magento Customer Id',
                    'position'      =>  0,
                    'active'        =>  true,
                    'key'           =>  'id'
                )
            );
            $data[] = array(
                'user_field' => array(
                    'type'          =>  'text',
                    'title'         =>  'Name',
                    'description'   =>  'Magento Customer Name',
                    'position'      =>  1,
                    'active'        =>  true,
                    'key'           =>  'name'
                )
            );
            $data[] = array(
                'user_field' => array(
                    'type'          =>  'text',
                    'title'         =>  'Group',
                    'description'   =>  'Magento Customer Group',
                    'position'      =>  2,
                    'active'        =>  true,
                    'key'           =>  'group'
                )
            );
            $data[] = array(
                'user_field' => array(
                    'type'          =>  'text',
                    'title'         =>  'Lifetime Sale',
                    'description'   =>  'Magento Customer Lifetime Sale',
                    'position'      =>  3,
                    'active'        =>  true,
                    'key'           =>  'lifetime_sale'
                )
            );
            $data[] = array(
                'user_field' => array(
                    'type'          =>  'text',
                    'title'         =>  'Average Sale',
                    'description'   =>  'Magento Customer Average Sale',
                    'position'      =>  4,
                    'active'        =>  true,
                    'key'           =>  'average_sale'
                )
            );
            $data[] = array(
                'user_field' => array(
                    'type'          =>  'date',
                    'title'         =>  'Last Logged In',
                    'description'   =>  'Last Logged In',
                    'position'      =>  5,
                    'active'        =>  true,
                    'key'           =>  'logged_in'
                )
            );

            foreach($data as $field) {
                $response = Mage::getModel('zendesk/api_users')->createUserField($field);
                if (!isset($response['active']) || $response['active'] === false)
                    Mage::log('Unable to create User Field with key '.$field['user_field']['key'], null, 'zendesk.log');
            }
            
            $customers = Mage::getModel('customer/customer')->getCollection();
            $customers->addAttributeToSelect(array('firstname', 'lastname', 'email'));
            foreach($customers as $customer) {
                Mage::log('Synchronizing customer with id '.$customer->getId(), null, 'zendesk.log');
                Mage::dispatchEvent('customer_save_commit_after', array('customer' => $customer));
            }
            
        } catch (Exception $ex) {
            Mage::log('Synchronization failed: '.$ex->getMessage(), null, 'zendesk.log');
            $this->getResponse()->setBody(json_encode(array('success'=>false, 'msg'=>Mage::helper('zendesk')->__('Synchronization failed: ').$ex->getMessage())));
            return;
        }
        Mage::log('Synchronization completed successfully', null, 'zendesk.log');
        $this->getResponse()->setBody(json_encode(array('success'=>true, 'msg'=>Mage::helper('zendesk')->__('Customers synchronization finished successfuly'))));
    }

    public function bulkDeleteAction()
    {
        $ids = $this->getRequest()->getParam('id');
        try {
            $response = Mage::getModel('zendesk/api_tickets')->bulkDelete($ids);
            $message = '%d out of %d ticket(s) were deleted.';
            $this->getMassActionResponse($response, $ids, $message);
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }
        $this->_redirectReferer();
    }
    
    public function bulkChangeStatusAction()
    {
        $ids = $this->getRequest()->getParam('id');
        $status = $this->getRequest()->getParam('status');
        
        try {
            $response = Mage::getModel('zendesk/api_tickets')->updateMany($ids, compact('status'));
            $this->getMassActionResponse($response, $ids);
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }
        $this->_redirect('adminhtml/zendesk/');
    }
    
    public function bulkChangePriorityAction()
    {
        $ids = $this->getRequest()->getParam('id');
        $priority = $this->getRequest()->getParam('priority');
        
        try {
            $response = Mage::getModel('zendesk/api_tickets')->updateMany($ids, compact('priority'));
            $this->getMassActionResponse($response, $ids);
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }
        $this->_redirect('adminhtml/zendesk/');
    }
    
    public function bulkChangeTypeAction()
    {
        $ids = $this->getRequest()->getParam('id');
        $type = $this->getRequest()->getParam('type');
        
        try {
            $response = Mage::getModel('zendesk/api_tickets')->updateMany($ids, compact('type'));
            $this->getMassActionResponse($response, $ids);
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }
        $this->_redirect('adminhtml/zendesk/');
    }
    
    public function bulkMarkSpamAction()
    {
        $ids = $this->getRequest()->getParam('id');
        
        try {
            $response = Mage::getModel('zendesk/api_tickets')->bulkMarkAsSpam($ids);
            $message = '%d out of %d ticket(s) were marked as spam.';
            $this->getMassActionResponse($response, $ids, $message);
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }
        $this->_redirectReferer();
    }

    public function ticketsAllAction() {
        $isAjax = Mage::app()->getRequest()->isAjax();
        
        if ($isAjax) {
            Mage::helper('zendesk')->storeDependenciesInCachedRegistry();
            $this->getResponse()->setBody($this->getLayout()->createBlock('zendesk/adminhtml_dashboard_tab_tickets_grid_all')->toHtml());
        }
    }
    
    public function ticketsViewAction() {
        $isAjax = Mage::app()->getRequest()->isAjax();

        if ($isAjax) {
            Mage::helper('zendesk')->storeDependenciesInCachedRegistry();
            $viewId = (int) $this->getRequest()->getParam('viewid');
            Mage::register('zendesk_tickets_view', $viewId);
            
            $this->getResponse()->setBody($this->getLayout()->createBlock('zendesk/adminhtml_dashboard_tab_tickets_grid_view')->toHtml());
        }
    }
    
    protected function getMassActionResponse($response, $ids, $message = '%d out of %d ticket(s) were updated.')
    {
        if (isset($response['job_status']) && isset($response['job_status']['url'])) {
            $job_status = Mage::getModel('zendesk/api_tickets')->getJobStatus($response['job_status']['url']);
            
            $parsed = array();
            $parsed['errors'] = array();
            $parsed['success'] = 0;
            
            if (isset($job_status['job_status']['results'])) {
                foreach ($job_status['job_status']['results'] as $result) {
                    if ($result['success']) {
                        $parsed['success'] ++;
                        continue;
                    }

                    if ($result['errors'])
                        $parsed['errors'][] = $result['errors']." (ID:".$result['id'].")";
                }
            }

            Mage::getSingleton('adminhtml/session')->addSuccess(
                Mage::helper('zendesk')->__(
                    $message, 
                    $parsed['success'], 
                    count($ids)
                )
            );

            foreach ($parsed['errors'] as $error) {
                Mage::getSingleton('adminhtml/session')->addError(
                        Mage::helper('zendesk')->__($error)
                );
            }
        } else {
            Mage::getSingleton('adminhtml/session')->addError(
                    Mage::helper('zendesk')->__(
                            'Request failed for %d ticket(s).', count($ids)
                    )
            );
        }
    }
    
    private function _domainConfigured()
    {
        $domain = Mage::getStoreConfig('zendesk/general/domain');
        if(!$domain) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('zendesk')->__('Please set up Zendesk connection.'));
            $this->_redirect('adminhtml/dashboard');
            return false;
        } else {
            return $domain;
        }
    }

}
