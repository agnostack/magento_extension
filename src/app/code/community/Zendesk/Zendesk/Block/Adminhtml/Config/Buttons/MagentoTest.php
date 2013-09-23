<?php
/**
 * Copyright 2013 Zendesk.
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

class Zendesk_Zendesk_Block_Adminhtml_Config_Buttons_MagentoTest extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if (!$this->getTemplate()) {
            $this->setTemplate('zendesk/config/button-test-magento.phtml');
        }
        return $this;
    }

    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $originalData = $element->getOriginalData();
        $this->addData(array(
            'button_label' => Mage::helper('zendesk')->__($originalData['button_label']),
            'html_id' => $element->getHtmlId(),
            'url' => Mage::getSingleton('adminhtml/url')->getUrl('*/setup/start')
        ));

        return $this->_toHtml();
    }

    public function getTestUrl()
    {
        return Mage::getUrl('zendesk/api/users/1');
    }

    public function getAuthHeader()
    {
        return 'Token token="' . Mage::helper('zendesk')->getApiToken(false) . '"';
    }
}