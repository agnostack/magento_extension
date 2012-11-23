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

class Zendesk_Zendesk_Block_Adminhtml_Order_View extends Mage_Adminhtml_Block_Sales_Order_View
{
    public function __construct()
    {
        parent::__construct();

        if(Mage::getStoreConfig('zendesk/features/show_on_order')) {
            $this->_addButton('ticket_new', array(
                 'label'     => Mage::helper('zendesk')->__('Create Ticket'),
                 'onclick'   => 'setLocation(\'' . $this->getTicketUrl() . '\')',
                 'class'     => 'zendesk',
            ));
        }
    }
    
    public function getTicketUrl()
    {
        // Since we're subclassing from the Sales_Order_View block, the order_id is already
        // being passed in as a URL parameter so there's no need for us to do it
        return $this->getUrl('adminhtml/zendesk/create');
    }
}
