<?php

/* * ********************************************************************
 * Customization Services by ModulesGarden.com
 * Copyright (c) ModulesGarden, INBS Group Brand, All Rights Reserved 
 * 
 *
 *  CREATED BY MODULESGARDEN       ->        http://modulesgarden.com
 *  CONTACT                        ->       contact@modulesgarden.com
 *
 *
 *
 *
 * This software is furnished under a license and may be used and copied
 * only  in  accordance  with  the  terms  of such  license and with the
 * inclusion of the above copyright notice.  This software  or any other
 * copies thereof may not be provided or otherwise made available to any
 * other person.  No title to and  ownership of the  software is  hereby
 * transferred.
 *
 *
 * ******************************************************************** */

/**
 * @author Marcin Kozak <marcin.ko@modulesgarden.com>
 */

class Zendesk_Zendesk_Block_Adminhtml_Settings_Edit_Form extends Mage_Adminhtml_Block_Widget_Form {
    protected $_settings;

    public function _construct() {
        $this->_settings = Mage::registry('zendesk_settings');
        parent::_construct();
    }

    protected function _prepareForm() {
  
        $renderer = new Zendesk_Zendesk_Block_Adminhtml_Widget_Form_Renderer_Fieldset;

        $renderer->setMessage( $this->getMessage() );

        $form = new Varien_Data_Form();
        $form->setUseContainer(true);
        $form->setId('edit_form');
        $this->setForm($form);
        $form->setFieldsetRenderer($renderer);

        $fieldset = $form->addFieldset('zendesk_form', array('legend' => Mage::helper('zendesk')->__('API Connection')));

        $fieldset->addField('admin_user_id', 'hidden', array(
            'name'  => 'admin_user_id',
            'value' => Mage::getSingleton('admin/session')->getUser()->getUserId()
        ));
        
        if( Mage::getSingleton('admin/session')->isAllowed('zendesk/main_api_credentials') ) {
            $fieldset->addField('use_global_settings', 'select', array(
                'name'      => 'use_global_settings',
                'label'     => Mage::helper('zendesk')->__('Use Global Settings'),
                'options'   => Mage::getModel('adminhtml/system_config_source_yesno')->toArray(),
                'value'     => 1
            ));
        }
        else {
            $fieldset->addField('use_global_settings', 'hidden', array(
                'name'      => 'use_global_settings',
                'value'     => 0
            ));
        }
        
        $fieldset->addField('username', 'text', array(
            'name'  => 'username',
            'label' => Mage::helper('zendesk')->__('Username'),
            'class' => 'validate-require-if-not-global',
        ));
        
        $fieldset->addField('password', 'password', array(
            'name'  => 'password',
            'label' => Mage::helper('zendesk')->__('Password'),
            'class' => 'validate-require-if-not-global',
        ));
        
        if ($this->_settings->getId()) {
            $fieldset->addField('id', 'hidden', array(
                'name'  => 'id',
                'value' => $this->_settings->getId()
            ));
            
            $fieldset->addField('check_connection', 'note', array(
                'text' => '<a href="' . $this->getUrl('*/*/checkConnection') . '">' . Mage::helper('zendesk')->__('Check Connection') . '</a>',
            ));
            
            $form->setValues($this->_settings->getData());
        }

        $form->setAction($this->getUrl('*/*/savesettings'))->setMethod('post');
                  
        return parent::_prepareForm();
    }
    

    
    public function getMessage() {
        return Mage::helper('zendesk')->__('In this settings you can set up an access to your individual Zendesk account. Otherwise, you can use default account set up in General Zendesk Configuration.');
    }

}
