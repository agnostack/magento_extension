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

class Zendesk_Zendesk_Model_Observer
{
    public function setHook(Varien_Event_Observer $observer)
    {
        if (Mage::app()->getFrontController()->getAction()->getFullActionName() === 'adminhtml_dashboard_index')
        {
            $block = $observer->getBlock();
            if ($block->getNameInLayout() === 'dashboard')
            {
                $block->getChild('totals')->setUseAsDashboardHook(true);
            }
        }
    }

    public function insertBlock(Varien_Event_Observer $observer)
    {
        if (Mage::app()->getFrontController()->getAction()->getFullActionName() === 'adminhtml_dashboard_index')
        {
            if ($observer->getBlock()->getUseAsDashboardHook())
            {
                $html = $observer->getTransport()->getHtml();
                $zendeskDash = $observer->getBlock()->getLayout()
                    ->createBlock('zendesk/adminhtml_dashboard')
                    ->setName('zendesk_dashboard');
                $zendeskGrid = $zendeskDash->getLayout()
                    ->createBlock('zendesk/adminhtml_dashboard_grids')
                    ->setName('zendesk_dashboard_grids');
                $zendeskDash->setChild('zendesk_dashboard_grids', $zendeskGrid);
                $html .= $zendeskDash->toHtml();
                $observer->getTransport()->setHtml($html);
            }
        }
    }

    public function saveConfig(Varien_Event_Observer $observer)
    {
        // Defaults for "global" scope
        $scope = 'default';
        $scopeId = 0;

        $websiteCode = $observer->getWebsite();
        $storeCode = $observer->getStore();

        if($websiteCode) {
            $scope = 'website';
            $website = Mage::getModel('core/website')->load($websiteCode);
            $scopeId = $website->getId();
        }

        if($storeCode) {
            $scope = 'store';
            $store = Mage::getModel('core/store')->load($storeCode);
            $scopeId = $store->getId();
        }

        $enableEmail = Mage::getStoreConfig('zendesk/frontend_features/contact_us', $storeCode);
        $currentEmail = Mage::getStoreConfig('contacts/email/recipient_email', $storeCode);
        $oldEmail = Mage::getStoreConfig('zendesk/hidden/contact_email_old', $storeCode);
        $zendeskEmail = Mage::helper('zendesk')->getSupportEmail($storeCode);

        if($enableEmail) {
            // If the email is already set, then do nothing
            if($currentEmail !== $zendeskEmail) {
                // Ensure the email address value exists and is valid
                if(Zend_Validate::is($zendeskEmail, 'EmailAddress')) {
                    Mage::getModel('core/config')->saveConfig('zendesk/hidden/contact_email_old', $currentEmail, $scope, $scopeId);
                    Mage::getModel('core/config')->saveConfig('contacts/email/recipient_email', $zendeskEmail, $scope, $scopeId);
                }
            }
        } else {
            // If the email hasn't been set, then we don't need to restore anything, otherwise overwrite the current
            // email address with the saved one
            if($currentEmail === $zendeskEmail) {
                // If the old email is the Zendesk email then we still need to disable it, so set it to the "general"
                // contact email address
                if($oldEmail === $zendeskEmail) {
                    $oldEmail = Mage::getStoreConfig('trans_email/ident_general/email', $storeCode);
                }
                Mage::getModel('core/config')->saveConfig('contacts/email/recipient_email', $oldEmail, $scope, $scopeId);
            }
        }

        // If the zendesk domain is not found in the web widget snippet (wrapped with quotes), generate it again
        $zDomain = Mage::getStoreConfig('zendesk/general/domain', $storeCode);
        $widgetSnippet = Mage::getStoreConfig('zendesk/frontend_features/web_widget_code_snippet', $storeCode);
        // Case insensitive search with single and double quotes, still better performance than 1 regexp search
        if($zDomain && stripos($widgetSnippet, "'{$zDomain}'") === false && stripos($widgetSnippet, '"'.$zDomain.'"') === false) {
            $webWidgetSnippet=<<<EOJS
<!-- Start of Zendesk Widget script -->
<script>/*<![CDATA[*/window.zEmbed||function(e,t){var n,o,d,i,s,a=[],r=document.createElement("iframe");window.zEmbed=function(){a.push(arguments)},window.zE=window.zE||window.zEmbed,r.src="javascript:false",r.title="",r.role="presentation",(r.frameElement||r).style.cssText="display: none",d=document.getElementsByTagName("script"),d=d[d.length-1],d.parentNode.insertBefore(r,d),i=r.contentWindow,s=i.document;try{o=s}catch(c){n=document.domain,r.src='javascript:var d=document.open();d.domain="'+n+'";void(0);',o=s}o.open()._l=function(){var o=this.createElement("script");n&&(this.domain=n),o.id="js-iframe-async",o.src=e,this.t=+new Date,this.zendeskHost=t,this.zEQueue=a,this.body.appendChild(o)},o.write('<body onload="document._l();">'),o.close()}("https://assets.zendesk.com/embeddable_framework/main.js","{$zDomain}");/*]]>*/</script>
<!-- End of Zendesk Widget script -->
EOJS;

            Mage::getModel('core/config')->saveConfig('zendesk/frontend_features/web_widget_code_active', 1);
            Mage::getModel('core/config')->saveConfig('zendesk/frontend_features/web_widget_code_snippet', $webWidgetSnippet);
        } elseif (empty($zDomain)) {
            Mage::getModel('core/config')->saveConfig('zendesk/frontend_features/web_widget_code_snippet', '');
        }
    }

    public function addTicketButton(Varien_Event_Observer $event)
    {
        $block = $event->getBlock();
        if ($block instanceof Mage_Adminhtml_Block_Sales_Order_View && Mage::getStoreConfig('zendesk/backend_features/show_on_order')) {
            $block->addButton('ticket_new', array(
             'label'     => Mage::helper('zendesk')->__('Create Ticket'),
             'onclick'   => 'setLocation(\'' . $block->getUrl('adminhtml/zendesk/create') . '\')',
             'class'     => 'zendesk',
            ));
        }
    }
    
    public function changeIdentity(Varien_Event_Observer $event)
    {
        if(!Mage::getStoreConfig('zendesk/general/customer_sync'))
            return;
        
        $user = null;
        $customer = $event->getCustomer();
        $email = $customer->getEmail();
        $orig_email = $customer->getOrigData();
        $orig_email = $orig_email['email'];
        
        //Get Customer Group
        $group_id = $customer->getGroupId();
        $group = Mage::getModel('customer/group')->load($group_id);
        
        //Get Customer Last Login Date
        $log_customer = Mage::getModel('log/customer')->loadByCustomer($customer); 
        if ($log_customer->getLoginAt())
            $logged_in = date("Y-m-d\TH:i:s\Z",strtotime($log_customer->getLoginAt()));
        else
            $logged_in = "";
        
        //Get Customer Sales Statistics
        $order_totals = Mage::getResourceModel('sales/order_collection');
        $lifetime_sale = 0;
        $average_sale = 0;
        
        if (is_object($order_totals)) {
            $order_totals
            ->addFieldToSelect('*')
            ->addFieldToFilter('customer_id', $customer->getId())
            ->addFieldToFilter('status', Mage_Sales_Model_Order::STATE_COMPLETE)
            ->addAttributeToSelect('grand_total')
            ->getColumnValues('grand_total');
            
            $sum = 0;
            foreach ($order_totals as $total) {
                if (isset($total['grand_total']))
                    $sum += (float)$total['grand_total'];
            }
            
            $lifetime_sale = Mage::helper('core')->currency($sum, true, false);
            $average_sale = Mage::helper('core')->currency($sum / count($order_totals), true, false);
        }
        
        $info['user'] = array(
                "name"          =>  $customer->getFirstname() . " " . $customer->getLastname(),
                "email"         =>  $email,
                "user_fields"       =>  array(
                    "group"         =>  $group->getCode(),
                    "name"          =>  $customer->getFirstname() . " " . $customer->getLastname(),
                    "id"            =>  $customer->getId(),
                    "logged_in"     =>  $logged_in,
                    "average_sale"  =>  $average_sale,
                    "lifetime_sale" =>  $lifetime_sale
                )
            ); 
        
        if($orig_email && $orig_email !== $email) {
            $user = Mage::getModel('zendesk/api_users')->find($orig_email);
            
            if(isset($user['id'])) {
                $data['identity'] = array(
                    'type'      =>  'email',
                    'value'     =>  $email,
                    'verified'  =>  true
                );
                $identity = Mage::getModel('zendesk/api_users')->addIdentity($user['id'],$data);
                if(isset($identity['id'])) {
                    Mage::getModel('zendesk/api_users')->setPrimaryIdentity($user['id'], $identity['id']);
                }
            }
        }

        if(!$user) {
            $user = Mage::getModel('zendesk/api_users')->find($email);
        }
        
        if(isset($user['id'])) {
            $this->syncData($user['id'], $info);
        } else {
            $info['user']['verified'] = true;
            $this->createAccount($info);
        }
    }
    
    public function syncData($user_id, $data)
    {
        Mage::getModel('zendesk/api_users')->update($user_id, $data);
    }
    
    public function createAccount($data)
    {
        Mage::getModel('zendesk/api_users')->create($data);
    }
    
    public function checkSsoRedirect($user)
    {
        if (
            Mage::helper('zendesk')->isSSOAdminUsersEnabled() && 
            Mage::app()->getRequest()->getControllerName() === 'zendesk' && 
            Mage::app()->getRequest()->getActionName() === 'authenticate'
        ) {
            Mage::app()->getResponse()
                ->setRedirect(Mage::helper('adminhtml')->getUrl('*/zendesk/authenticate'))
                ->sendHeaders()
                ->sendResponse();
            exit();
        }
    }
}
