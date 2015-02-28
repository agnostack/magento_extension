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

class Zendesk_Zendesk_Block_Customer_Tickets_List extends Mage_Core_Block_Template
{
    public function __construct()
    {
        parent::__construct();

        $key = array(
            'ZendeskCustomerTickets',
            $this->getCustomer()->getId()
        );

        $this->addData(array(
            'cache_lifetime' => 60 * 5,
            'cache_tags' => array('Zendesk_Customer_Tickets'),
            'cache_key' => implode('_', $key)

        ));

        $this->setTemplate('zendesk/customer/tickets/list.phtml');
    }


    protected function _getCustomerSession()
    {
        return Mage::getSingleton('customer/session');
    }


    public function getCustomer()
    {
        $session = $this->_getCustomerSession();
        $customer = false;

        if($session) {
            $customer = $session->getCustomer();
        }

        return $customer;
    }


    public function getList()
    {
        $customer = $this->getCustomer();
        $tickets = null;

        if($customer && $customer->getEmail()) {
            $tickets = Mage::getModel('zendesk/api_tickets')->forRequester($customer->getEmail());
        }

        return $tickets;
    }
}