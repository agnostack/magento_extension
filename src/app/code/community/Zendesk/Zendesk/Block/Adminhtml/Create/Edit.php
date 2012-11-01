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

class Zendesk_Zendesk_Block_Adminhtml_Create_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    protected function _preparelayout()
    {
        $this->removeButton('delete');
        $this->removeButton('back');
        $this->removeButton('reset');
        $this->removeButton('save');

        if(Mage::registry('zendesk_create_data')) {
            $data = Mage::registry('zendesk_create_data');

            if(isset($data['order_id'])) {
                $this->_addButton('back', array(
                     'label'     => Mage::helper('adminhtml')->__('Back'),
                     'onclick'   => 'setLocation(\'' . $this->getBackUrl($data['order_id']) . '\')',
                     'class'     => 'back',
                ), -1);
            }
        }

        $this->_addButton('save', array(
                'label'     => Mage::helper('zendesk')->__('Create Ticket'),
                'onclick'   => 'editForm.submit();',
                'class'     => 'save',
            ), 1);
        $this->setChild('form', $this->getLayout()->createBlock('zendesk/adminhtml_create_edit_form'));
        return parent::_prepareLayout();
    }
    
    public function getFormHtml()
    {
        $formHtml = parent::getFormHtml();
        return $formHtml;
    }
    
    public function getHeaderText()
    {
        return Mage::helper('zendesk')->__('New Ticket');
    }

    public function getBackUrl($orderId)
    {
        if (Mage::getSingleton('admin/session')->isAllowed('sales/order/actions/view')) {
            return $this->getUrl('adminhtml/sales_order/view', array('order_id' => $orderId));
        }
        return false;
    }

    public function getSaveUrl()
    {
        return $this->getUrl('*/*/save', array('_current' => true, 'back' => null));
    }
}
