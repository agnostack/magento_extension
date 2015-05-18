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

class Zendesk_Zendesk_Block_Adminhtml_Create_Customer extends Mage_Adminhtml_Block_Widget_Form_Container
{

    public function __construct()
    {
        $this->_controller = false;
        parent::__construct();
        $this->setId('zendesk_create_customer_search');
    }

    public function getHeaderText()
    {
        return Mage::helper('zendesk')->__('Please Select User to Add');
    }
    
    public function getButtonsHtml($area = null)
    {
        $addButtonData = array(
            'label' => Mage::helper('zendesk')->__('Select User'),
            'onclick' => 'showUsers()',
            'id'    =>  'show-users'
        );
        return $this->getLayout()->createBlock('adminhtml/widget_button')->setData($addButtonData)->toHtml();
    }

    public function getHeaderCssClass()
    {
        return 'head-catalog-customer';
    }

}
