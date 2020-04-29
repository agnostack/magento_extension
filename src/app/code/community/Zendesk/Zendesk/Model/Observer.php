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
            $scope = 'stores';
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

        if (! empty($zDomain)) {
            if ((bool) Mage::getStoreConfig('zendesk/frontend_features/web_widget_code_active')) {
                $embeds = Mage::getModel('zendesk/api_configSets')->find();
                if (! isset($embeds['launcher'])) {
                    Mage::getModel('zendesk/api_configSets')->initialize();
                }
            }

            // Case insensitive search with single and double quotes, still better performance than 1 regexp search
            $missingSnippet = stripos($widgetSnippet, "'{$zDomain}'") === false && stripos($widgetSnippet, '"'.$zDomain.'"') === false;
            if ($missingSnippet) {
                $webWidgetSnippet=<<<EOJS
<!-- Start of Zendesk Widget script -->
<script>/*<![CDATA[*/window.zEmbed||function(e,t){var n,o,d,i,s,a=[],r=document.createElement("iframe");window.zEmbed=function(){a.push(arguments)},window.zE=window.zE||window.zEmbed,r.src="javascript:false",r.title="",r.role="presentation",(r.frameElement||r).style.cssText="display: none",d=document.getElementsByTagName("script"),d=d[d.length-1],d.parentNode.insertBefore(r,d),i=r.contentWindow,s=i.document;try{o=s}catch(c){n=document.domain,r.src='javascript:var d=document.open();d.domain="'+n+'";void(0);',o=s}o.open()._l=function(){var o=this.createElement("script");n&&(this.domain=n),o.id="js-iframe-async",o.src=e,this.t=+new Date,this.zendeskHost=t,this.zEQueue=a,this.body.appendChild(o)},o.write('<body onload="document._l();">'),o.close()}("https://assets.zendesk.com/embeddable_framework/main.js","{$zDomain}");/*]]>*/</script>
<!-- End of Zendesk Widget script -->
EOJS;
                Mage::getModel('core/config')->saveConfig('zendesk/frontend_features/web_widget_code_snippet', $webWidgetSnippet);
            }
        } else {
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
        $customer = $event->getCustomer();
        Mage::helper('zendesk/sync')->syncCustomer($customer);
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
