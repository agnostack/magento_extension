<?php
class Zendesk_Zendesk_Block_Adminhtml_Dashboard extends Mage_Adminhtml_Block_Template
{

    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('zendesk/dashboard/index.phtml');
    }

    public function getIsZendeskDashboard()
    {
        $controller = Mage::app()->getFrontController()->getRequest()->getControllerName();

        if($controller == 'zendesk') {
            return true;
        } else {
            return false;
        }
    }
}
