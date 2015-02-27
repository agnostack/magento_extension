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

class Zendesk_Zendesk_Block_Adminhtml_Create_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    protected function _construct()
    {
        parent::_construct(); 
    }

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
                     'onclick'   => 'setLocation(\'' . $this->getZdBackUrl($data['order_id']) . '\')',
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

    public function getZdBackUrl($orderId)
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
