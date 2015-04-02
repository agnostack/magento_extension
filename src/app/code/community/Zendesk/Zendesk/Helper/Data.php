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

    /**
     * Returns configured Zendesk Domain
     * format: company.zendesk.com
     *
     * @return mixed Zendesk Account Domain
     */
    public function getZendeskDomain()
    {
        return Mage::getStoreConfig('zendesk/general/domain');
    }
    
    
    /**
     * Returns if SSO is enabled for EndUsers
     * @return integer
     */
    public function isSSOEndUsersEnabled()
    {
        return Mage::getStoreConfig('zendesk/sso_frontend/enabled');
    }

    /**
     * Returns if SSO is enabled for Admin/Agent Users
     * @return integer
     */
    public function isSSOAdminUsersEnabled()
    {
        return Mage::getStoreConfig('zendesk/sso/enabled');
    }

    /**
     * Returns frontend URL where authentication process starts for EndUsers
     *
     * @return string SSO Url to auth EndUsers
     */
    public function getSSOAuthUrlEndUsers()
    {
        return Mage::getUrl('zendesk/sso/login');
    }

    /**
     * Returns backend URL where authentication process starts for Admin/Agents
     *
     * @return string SSO Url to auth Admin/Agents
     */
    public function getSSOAuthUrlAdminUsers()
    {
        return Mage::helper('adminhtml')->getUrl('*/zendesk/login');
    }

    /**
     * Returns Zendesk Account Login URL for normal access
     * format: https://<zendesk_account>/<route>
     *
     * @return string Zendesk Account login url
     */
    public function getZendeskAuthNormalUrl()
    {
        $protocol = 'https://';
        $domain = $this->getZendeskDomain();
        $route = '/access/normal';

        return $protocol . $domain . $route;
    }

    /**
     * Returns Zendesk Login Form unauthenticated URL
     * format: https://<zendesk_account>/<route>
     *
     * @return string Zendesk Account login unauthenticated form url
     */
    public function getZendeskUnauthUrl()
    {
        $protocol = 'https://';
        $domain = $this->getZendeskDomain();
        //Zendesk will automatically redirect to login if user is not logged in
        //previous URL followed to login page even if user has already logged in
        $route = '/home';

        return $protocol . $domain . $route;
    }
    
    public function getApiToken($generate = true)
    {
        // Grab any existing token from the admin scope
        $token = Mage::getStoreConfig('zendesk/api/token', 0);

        if( (!$token || strlen(trim($token)) == 0) && $generate) {
            $token = $this->setApiToken();
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

    /**
     * Returns the provisioning endpoint for new setups.
     *
     * This uses the config/zendesk/provision_url XML path to retrieve the setting, with a default value set in
     * the extension config.xml file. This can be overridden in your website's local.xml file.
     * @return null|string URL or null on failure
     */
    public function getProvisionUrl()
    {
        $config = Mage::getConfig();
        $data = $config->getNode('zendesk/provision_url');
        if(!$data) {
            return null;
        }
        return (string)$data;
    }

    public function getProvisionToken($generate = false)
    {
        $token = Mage::getStoreConfig('zendesk/hidden/provision_token', 0);

        if( (!$token || strlen(trim($token)) == 0) && $generate) {
            $token = $this->setProvisionToken();
        }

        return $token;
    }

    public function setProvisionToken($token = null)
    {
        if(!$token) {
            $token = md5(time());
        }

        Mage::getModel('core/config')->saveConfig('zendesk/hidden/provision_token', $token, 'default');
        Mage::getConfig()->removeCache();

        return $token;
    }

    public function getOrderDetail($order)
    {
        // if the admin site has a custom URL, use it
        $urlModel = Mage::getModel('adminhtml/url')->setStore('admin');

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
            'admin_url' => $urlModel->getUrl('adminhtml/sales_order/view', array('order_id' => $order->getId())),
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

    public function loadCustomer($email, $website = null)
    {
        $customer = null;

        if(Mage::getModel('customer/customer')->getSharingConfig()->isWebsiteScope()) {
            // Customer email address can be used in multiple websites so we need to
            // explicitly scope it
            if($website) {
                // We've been given a specific website, so try that
                $customer = Mage::getModel('customer/customer')
                    ->setWebsiteId($website)
                    ->loadByEmail($email);
            } else {
                // No particular website, so load all customers with the given email and then return a single object
                $customers = Mage::getModel('customer/customer')
                    ->getCollection()
                    ->addFieldToFilter('email', array('eq' => array($email)));
                if($customers->getSize()) {
                    $id = $customers->getLastItem()->getId();
                    $customer = Mage::getModel('customer/customer')->load($id);
                }
            }

        } else {
            // Customer email is global, so no scoping issues
            $customer = Mage::getModel('customer/customer')->loadByEmail($email);
        }

        return $customer;
    }

    /**
     * Retrieve Use External ID config option
     *
     * @return integer
     */
    public function isExternalIdEnabled()
    {
        return Mage::getStoreConfig('zendesk/general/use_external_id');
    }

    public function getTicketUrl($row, $link = false)
    {   
        $path = Mage::getSingleton('admin/session')->getUser() ? 'adminhtml/zendesk/login' : '*/sso/login';
        $url = Mage::helper('adminhtml')->getUrl($path, array("return_url" => Mage::helper('core')->urlEncode(Mage::helper('zendesk')->getUrl('ticket', $row['id']))));
        
        if ($link)
            return $url;
        
        $subject = $row['subject'] ? $row['subject'] : $this->__('No Subject');

        return '<a href="' . $url . '" target="_blank">' .  Mage::helper('core')->escapeHtml($subject) . '</a>';
    }
    
    public function getStatusMap()
    {
        return array(
            'new'       =>  'New',
            'open'      =>  'Open',
            'pending'   =>  'Pending',
            'solved'    =>  'Solved',
            'closed'    =>  'Closed',
            'hold'      =>  'Hold'
        );
    }
        
    public function getPriorityMap()
    {
        return array(
            'low'       =>  'Low',
            'normal'    =>  'Normal',
            'high'      =>  'High',
            'urgent'    =>  'Urgent'
        );
    }
    
    public function getTypeMap()
    {
        return array(
            'problem'   =>  'Problem',
            'incident'  =>  'Incident',
            'question'  =>  'Question',
            'task'      =>  'Task'
        );
    }
    
    public function getChosenViews() {
        $list = trim(trim(Mage::getStoreConfig('zendesk/backend_features/show_views')), ',');
        return explode(',', $list);
    }
    
    public function getFormatedDataForAPI($dateToFormat) {
        $myDateTime = DateTime::createFromFormat('d/m/Y', $dateToFormat);
        return $myDateTime->format('Y-m-d');
    }
    
    public function isValidDate($date) {
        if(is_string($date)) {
            $d = DateTime::createFromFormat('d/m/Y', $date);
            return $d && $d->format('d/m/Y') == $date;
        }
        
        return false;
    }
    
    public function getFormatedDateTime($dateToFormat) {
        return Mage::helper('core')->formatDate($dateToFormat, 'medium', true);
    }
    
    public function getConnectionStatus() {
        try {
            $user = Mage::getModel('zendesk/api_users')->me();
            
            if($user['id']) {
                return array(
                    'success'   => true,
                    'msg'       => Mage::helper('zendesk')->__('Connection to Zendesk API successful'),
                );
            }
            
            $error = Mage::helper('zendesk')->__('Connection to Zendesk API failed') .
                '<br />' . Mage::helper('zendesk')->__('Troubleshooting tips can be found at <a href=%s>%s</a>', 'https://support.zendesk.com/entries/26579987', 'https://support.zendesk.com/entries/26579987');
            
            return array(
                'success'   => false,
                'msg'       => $error,
            );
            
        } catch (Exception $ex) {
            $error = Mage::helper('zendesk')->__('Connection to Zendesk API failed') .
                '<br />' . $ex->getCode() . ': ' . $ex->getMessage() .
                '<br />' . Mage::helper('zendesk')->__('Troubleshooting tips can be found at <a href=%s>%s</a>', 'https://support.zendesk.com/entries/26579987', 'https://support.zendesk.com/entries/26579987');
            
            return array(
                'success'   => false,
                'msg'       => $error,
            );
        }
    }
    
}
