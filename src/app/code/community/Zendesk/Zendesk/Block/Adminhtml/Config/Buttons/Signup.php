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

class Zendesk_Zendesk_Block_Adminhtml_Config_Buttons_Signup extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if (!$this->getTemplate()) {
            $this->setTemplate('zendesk/config/button-signup.phtml');
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

    public function getPostUrl()
    {
        return Mage::helper('zendesk')->getProvisionUrl();
    }

    public function getPostInfo()
    {
        $info = array(
            'magento_domain' => Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB),
            'magento_current_user_id' => Mage::getSingleton('admin/session')->getUser()->getUserId(),
            'magento_user_count' => Mage::getModel('admin/user')->getCollection()->getSize(),
            'magento_auth_token' => Mage::helper('zendesk')->getProvisionToken(true),
            'magento_callback' => Mage::helper('adminhtml')->getUrl('adminhtml/zendesk/redirect', array('type' => 'settings', 'id' => 'zendesk')),
            'magento_locale' => Mage::getStoreConfig('general/locale/code'),
            'magento_timezone' => Mage::getStoreConfig('general/locale/timezone'),
        );

        $storeName = Mage::getStoreConfig('general/store_information/name');

        if(!$storeName) {
            $websites = Mage::getModel('core/website')->getCollection();
            foreach($websites as $website) {
                // Skip admin website
                if($website->getName() == 'Admin' || $website->getName() == 'Main Website') continue;

                $storeName = $website->getName();
            }
        }

        $info['magento_store_name'] = (string)$storeName;

        return $info;
    }
}