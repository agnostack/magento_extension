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

class Zendesk_Zendesk_Block_Adminhtml_Order_View_Tickets extends Mage_Adminhtml_Block_Sales_Order_Abstract
{
    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('zendesk/order/tickets.phtml');
    }
    
    public function getTickets($orderId)
    {
        return array(
            array(
                'id' => '',
                'url' => '',
                'subject' => '',
                'status' => '',
                'updated_at' => '',
            )
        );
    }
}
