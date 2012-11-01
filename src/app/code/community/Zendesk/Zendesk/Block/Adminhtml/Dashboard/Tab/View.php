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

class Zendesk_Zendesk_Block_Adminhtml_Dashboard_Tab_View extends Mage_Adminhtml_Block_Widget_Container
{
    protected $_view = null;

    public function setView($view)
    {
        $this->_view = $view;
        $this->setId('view-' . $view['id']);

        return $this;
    }

    public function getView()
    {
        return $this->_view;
    }

    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('zendesk/dashboard/tabs/view.phtml');
    }
}
