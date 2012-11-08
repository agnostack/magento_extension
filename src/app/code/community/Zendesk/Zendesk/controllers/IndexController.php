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

class Zendesk_Zendesk_IndexController extends Mage_Core_Controller_Front_Action
{
    /**
     * Redirects to the Zendesk support portal for this website
     */
    public function indexAction()
    {
        $url = Mage::helper('zendesk')->getUrl();
        $this->_redirectUrl($url);
    }
}