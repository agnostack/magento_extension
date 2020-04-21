<?php

class Zendesk_Zendesk_Helper_Sync extends Mage_Core_Helper_Abstract {

    public function syncCustomer($customer){
        if(!Mage::getStoreConfig('zendesk/general/customer_sync'))
            return;

        $user = null;
        $currentEmail = $customer->getEmail();
        $previousCustomerData = $customer->getOrigData();
        $previousEmail = $previousCustomerData['email'];
        //Get Customer Group
        $groupId = $customer->getGroupId();
        $group = Mage::getModel('customer/group')->load($groupId);

        //Get Customer Last Login Date
        $logCustomer = Mage::getModel('log/customer')->loadByCustomer($customer);
        if ($logCustomer->getLoginAt())
            $loggedIn = date("Y-m-d\TH:i:s\Z",strtotime($logCustomer->getLoginAt()));
        else
            $loggedIn = "";

        //Get Customer Sales Statistics
        $orderTotals = Mage::getResourceModel('sales/order_collection');
        $lifetimeSale = 0;
        $averageSale = 0;

        if (is_object($orderTotals)) {
            $orderTotals
                ->addFieldToFilter('customer_id', $customer->getId())
                ->addFieldToFilter('status', Mage_Sales_Model_Order::STATE_COMPLETE);

            $orderTotals->getSelect()
                ->reset(Zend_Db_Select::COLUMNS)
                ->columns(new Zend_Db_Expr("SUM(grand_total) as total"))
                ->columns(new Zend_Db_Expr("AVG(grand_total) as avg_total"))
                ->group('customer_id');

            if (count($orderTotals) > 0) {
                $sum = (float) $orderTotals->getFirstItem()->getTotal();
                $avg = (float) $orderTotals->getFirstItem()->getAvgTotal();

                $lifetimeSale = Mage::helper('core')->currency($sum, true, false);
                $averageSale = Mage::helper('core')->currency($avg, true, false);
            }
        }

        $info['user'] = array(
            "name"          =>  $customer->getFirstname() . " " . $customer->getLastname(),
            "email"         =>  $currentEmail,
            "user_fields"       =>  array(
                "group"         =>  $group->getCode(),
                "name"          =>  $customer->getFirstname() . " " . $customer->getLastname(),
                "id"            =>  $customer->getId(),
                "logged_in"     =>  $loggedIn,
                "average_sale"  =>  $averageSale,
                "lifetime_sale" =>  $lifetimeSale
            )
        );

        $user = Mage::getModel('zendesk/api_users')->find($currentEmail);
        if($previousEmail !== $currentEmail) {
            if(!isset($user['id'])) {
                $user = $this->createAccount($info);
            }
        }

        return $user;
    }

    private function createAccount($data)
    {
        $data['user']['verified'] = false;
        $user = Mage::getModel('zendesk/api_users')->create($data);
        return $user;
    }
}
