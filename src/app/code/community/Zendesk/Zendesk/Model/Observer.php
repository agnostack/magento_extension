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

    public function SyncronizeUsers(Varien_Event_Observer $observer)
    {
        $last_updated_time = Mage::getStoreConfig('zendesk/cronsyncusers/last_updated');

        $customers = Mage::getModel('customer/customer')->getCollection()->addNameToSelect();
        $customers->getSelect()
            ->joinLeft('customer_entity_varchar', 'e.entity_id = customer_entity_varchar.entity_id AND customer_entity_varchar.attribute_id = 983',array('value AS phone'))->limit(1);
        if(!empty($last_updated_time)) {
            $customers->getSelect()
                ->joinLeft(array('a' => 'appraisal'), 'a.customer_id = e.entity_id', array('a.update_time'))
                ->where("a.`update_time` >= '{$last_updated_time}' OR e.updated_at >= '{$last_updated_time}'")
                ->group('e.entity_id')
                ->order('e.updated_at ASC')->order('a.update_time ASC');
        }

        foreach ($customers as $customer) {

            $user = Mage::getModel('zendesk/api_users')->find($customer->getEmail());
            $q = array();

            $collection = Mage::getModel('appraisal/appraisal')->getCollection();
            $collection->addFieldToFilter('customer_id', $customer->getId());
            $collection->getSelect()->reset(Zend_Db_Select::COLUMNS)
                ->columns(array(
                    new Zend_Db_Expr('"all_items" AS name'),
                    'items_count'=>'count(*)',
                ));
            $q[] = new Zend_Db_Expr('(' . $collection->getSelect() . ')');

            $collection = Mage::getModel('appraisal/appraisal')->getCollection();
            $collection->addFieldToFilter('main_table.associated_product', array('gt' => 0));
            $associated = array();
            $collection->addFieldToFilter('main_table.customer_id', array('eq' => $customer->getId()));

            foreach ($collection->getData() as $collection) {
                $associated[] = $collection['associated_product'];
            }
            $_productCollection = Mage::getModel('sales/order_item')->getCollection()
                ->addFieldToFilter('product_id', array('in' => $associated))
                ->addFieldToSelect(array('product_id'))
                ->setOrder('order_id', 'desc');
            $products = array();
            foreach ($_productCollection as $prod_c) $products[] = $prod_c['product_id'];

            $collection = Mage::getModel('catalog/product')->getCollection()
                ->addAttributeToSelect('name')
                ->addFieldToFilter('entity_id', array('in' => (empty($products) ? array() : $products)));
            $collection->getSelect()->reset(Zend_Db_Select::COLUMNS)
                ->columns(array(
                    new Zend_Db_Expr('"sold_items" AS name'),
                    'items_count'=>'count(*)',
                ));
            $q[] = new Zend_Db_Expr('(' . $collection->getSelect() . ')');

            $collection = Mage::getModel('appraisal/appraisal')->getCollection();
            $collection->addFieldToFilter('customer_id', $customer->getId());
            $collection->addFieldToFilter('status', 10);
            $collection->getSelect()->reset(Zend_Db_Select::COLUMNS)
                ->columns(array(
                    new Zend_Db_Expr('"rtbs_items" AS name'),
                    'items_count'=>'count(*)',
                ));
            $q[] = new Zend_Db_Expr('(' . $collection->getSelect() . ')');

            $collection->getSelect()->reset()
                ->union($q);
            $counts = $collection->getData();

            $data["verified"] = true;
            $data["phone"] = $customer->getPhone();
            $data["external_id"] = $customer->getId();
            $data["user_fields"] = array(
                'no_of_submitted_items' => $counts[0]['items_count'],
                'no_of_items_ready_to_be_sold' => $counts[1]['items_count'],
                'items_sold' => $counts[2]['items_count'],
            );

            try {

                if(empty($user)) {
                    $response = Mage::getModel('zendesk/api_requesters')->create($customer->getEmail(), $customer->getName(), $data);
                }
                else {
                    $data["email"] = $customer->getEmail();
                    $data["name"] = $customer->getName();
                    $response = Mage::getModel('zendesk/api_requesters')->update($user['id'], $data);
                }

                $c_dates = $customer->getUpdatedAt();
                if($customer->getUpdatedTime() != '') $app_dates = $customer->getUpdatedTime();

                $last_date = max($c_dates, $app_dates);
            }
            catch (Exception $e) {
            }

        }

        if(isset($last_date)) {

            $cronsyncusers_ld = new Mage_Core_Model_Config();
            $cronsyncusers_ld ->saveConfig('zendesk/cronsyncusers/last_updated', $last_date, 'default', 0);
        }

        return true;
    }
}
