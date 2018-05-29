<?php
/**
 * Created by PhpStorm.
 * User: o5k4r1n
 * Date: 29-05-18
 * Time: 10:44 AM
 */
class Zendesk_Zendesk_Helper_Sync extends Mage_Core_Helper_Abstract {

    public function getCustomerData($customer){
        if(!Mage::getStoreConfig('zendesk/general/customer_sync'))
            return;

        $user = null;
        //$customer = $event->getCustomer();
        $email = $customer->getEmail();
        $orig_email = $customer->getOrigData();
        $orig_email = $orig_email['email'];
        echo "correo: ".$email;
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
                ->addFieldToFilter('customer_id', $customer->getId())
                ->addFieldToFilter('status', Mage_Sales_Model_Order::STATE_COMPLETE);

            $order_totals->getSelect()
                ->reset(Zend_Db_Select::COLUMNS)
                ->columns(new Zend_Db_Expr("SUM(grand_total) as total"))
                ->columns(new Zend_Db_Expr("AVG(grand_total) as avg_total"))
                ->group('customer_id');

            if (count($order_totals) > 0) {
                $sum = (float) $order_totals->getFirstItem()->getTotal();
                $avg = (float) $order_totals->getFirstItem()->getAvgTotal();

                $lifetime_sale = Mage::helper('core')->currency($sum, true, false);
                $average_sale = Mage::helper('core')->currency($avg, true, false);
            }
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
            $this->syncData($info);
        } else {
            $info['user']['verified'] = true;
            $user = Mage::getModel('zendesk/api_users')->create($info);
        }
        return $user;
    }
    public function syncData($info)
    {
        Mage::getModel('zendesk/api_users')->create($info);
    }
}