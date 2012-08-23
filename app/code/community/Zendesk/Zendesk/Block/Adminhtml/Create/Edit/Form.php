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

class Zendesk_Zendesk_Block_Adminhtml_Create_Edit_form extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form(array(
            'id' => 'edit_form',
            'action' => $this->getData('action'),
            'method' => 'post'
        ));

        $fieldset = $form->addFieldset('base', array(
            'legend'=>Mage::helper('adminhtml')->__('New Ticket'),
            'class'=>'fieldset-wide'
        ));

        $fieldset->addField('requester', 'text', array(
            'name'     => 'requester',
            'label'    => Mage::helper('zendesk')->__('Requester Email'),
            'title'    => Mage::helper('zendesk')->__('Requester Email'),
            'required' => true,
            'class'    => 'requester'
        ));

        $fieldset->addField('requester_name', 'text', array(
            'name'     => 'requester_name',
            'label'    => Mage::helper('zendesk')->__('Requester Name'),
            'title'    => Mage::helper('zendesk')->__('Requester Name'),
            'required' => false,
            'class'    => 'requester'
        ));

        if(Mage::getModel('customer/customer')->getSharingConfig()->isWebsiteScope()) {
            $fieldset->addField('website_id', 'select', array(
                'name'      => 'website_id',
                'label'     => Mage::helper('zendesk')->__('Requester Website'),
                'title'     => Mage::helper('zendesk')->__('Requester Website'),
                'required'  => true,
                'values'    => Mage::getModel('adminhtml/system_config_source_website')->toOptionArray(),
            ));
        }

        $fieldset->addField('subject', 'text', array(
            'name'     => 'subject',
            'label'    => Mage::helper('zendesk')->__('Subject'),
            'title'    => Mage::helper('zendesk')->__('Subject'),
            'required' => true
        ));
        
        $fieldset->addField('status', 'select', array(
            'name'     => 'status',
            'label'    => Mage::helper('zendesk')->__('Status'),
            'title'    => Mage::helper('zendesk')->__('Status'),
            'required' => true,
            'values'   => array(
                array('label' => 'New', 'value' => 'new'),
                array('label' => 'Open', 'value' => 'open'),
                array('label' => 'Pending', 'value' => 'pending'),
                array('label' => 'Solved', 'value' => 'solved'),
            )
        ));
        
        $fieldset->addField('type', 'select', array(
            'name'     => 'type',
            'label'    => Mage::helper('zendesk')->__('Type'),
            'title'    => Mage::helper('zendesk')->__('Type'),
            'required' => false,
            'values'   => array(
                array('label' => '-', 'value' => ''),
                array('label' => 'Problem', 'value' => 'problem'),
                array('label' => 'Incident', 'value' => 'incident'),
                array('label' => 'Question', 'value' => 'question'),
                array('label' => 'Task', 'value' => 'task'),
            )
        ));
        
        $fieldset->addField('priority', 'select', array(
            'name'     => 'priority',
            'label'    => Mage::helper('zendesk')->__('Priority'),
            'title'    => Mage::helper('zendesk')->__('Priority'),
            'required' => false,
            'values'   => array(
                array('label' => 'Low', 'value' => 'low'),
                array('label' => 'Normal', 'value' => 'normal'),
                array('label' => 'High', 'value' => 'high'),
                array('label' => 'Urgent', 'value' => 'urgent'),
            )
        ));
        
        $fieldset->addField('order', 'text', array(
            'name'     => 'order',
            'label'    => Mage::helper('zendesk')->__('Order Number'),
            'title'    => Mage::helper('zendesk')->__('Order Number'),
            'required' => false
        ));

        $fieldset->addField('description', 'textarea', array(
            'name'     => 'description',
            'label'    => Mage::helper('zendesk')->__('Description'),
            'title'    => Mage::helper('zendesk')->__('Description'),
            'required' => true
        ));

        $form->setValues(Mage::registry('zendesk_create_data'));
        $form->setUseContainer(true);
        $form->setMethod('post');
        $this->setForm($form);
        return parent::_prepareForm();
    }

}
