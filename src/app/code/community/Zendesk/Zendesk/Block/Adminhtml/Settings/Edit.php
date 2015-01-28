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

class Zendesk_Zendesk_Block_Adminhtml_Settings_Edit extends Mage_Adminhtml_Block_Widget_Form_Container { 
    
    public function __construct() {
        parent::__construct();
        $this->_blockGroup  = 'zendesk';
        $this->_controller  = 'adminhtml_settings';
        $this->_headerText  = Mage::helper('zendesk')->__('Edit Settings');
        
        $this->_removeButton('back');
        $this->_removeButton('delete');
        $this->_removeButton('reset');
        
        $this->setTemplate('zendesk/widget/form/container.phtml');
        $this->setSkipHeaderCopy(true);
    }
    
    protected function _toHtml() {
        return '<div class="main-col-inner">' . parent::_toHtml() . '</div>';
    }
    
}
