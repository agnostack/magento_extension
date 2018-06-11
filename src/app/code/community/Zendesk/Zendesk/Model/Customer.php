<?php

class Zendesk_Zendesk_Model_Customer extends Mage_Core_Model_Abstract{

    public function syncronize(){
        Mage::log('Cron Working', null, 'cron.log', true);
        $customers = Mage::getModel('customer/customer')
            ->getCollection()->setPageSize(90)->setCurPage(1);
        $customers->addAttributeToSelect(array('firstname', 'lastname', 'email'))
            ->addAttributeToFilter('zendesk_id', array('or'=> array(
                0 => array('is' => new Zend_Db_Expr('null')))
            ), 'left');
        foreach($customers as $customer){
            Mage::log('Synchronization started', null, 'zendesk.log');
            try {
                Mage::log('Synchronizing customer with id '.$customer->getId(), null, 'zendesk.log');
                $customerData = Mage::helper('zendesk/sync')->getCustomerData($customer);
                $zendeskId = $customerData['id'];
                $customer->setZendeskId($zendeskId);
                $customer->save();
            }
            catch (Exception $ex) {
                Mage::log('Synchronization failed: '.$ex->getMessage(), null, 'zendesk.log');

                return;
            }
            Mage::log('Synchronization completed successfully', null, 'zendesk.log');


        }
    }
}
