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

        $enableEmail = Mage::getStoreConfig('zendesk/features/contact_us', $storeCode);
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
    }
}
