<?php
/**
 * Created by PhpStorm.
 * User: o5k4r1n
 * Date: 25-05-18
 * Time: 05:10 PM
 */

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
                $customer_data = Mage::helper('zendesk/sync')->getCustomerData($customer);
                $zendesk_id = $customer_data['id'];
                $customer->setZendeskId($zendesk_id);
                $customer->save();
                //Zend_Debug::dump($customer_data);
            }
            catch (Exception $ex) {
                Mage::log('Synchronization failed: '.$ex->getMessage(), null, 'zendesk.log');

                return;
            }
            Mage::log('Synchronization completed successfully', null, 'zendesk.log');


        }
    }
}