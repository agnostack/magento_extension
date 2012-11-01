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
