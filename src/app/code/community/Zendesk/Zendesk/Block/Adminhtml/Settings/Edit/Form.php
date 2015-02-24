<?php
/**
 * Copyright 2015 Zendesk
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
        
        if(Mage::getSingleton('admin/session')->isAllowed('zendesk/main_api_credentials')) {
            $fieldset->addField('use_global_settings', 'select', array(
                'name'      => 'use_global_settings',
                'label'     => Mage::helper('zendesk')->__('Use Global Settings'),
                'options'   => Mage::getModel('adminhtml/system_config_source_yesno')->toArray(),
                'value'     => 1
            ));
        } else {
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
