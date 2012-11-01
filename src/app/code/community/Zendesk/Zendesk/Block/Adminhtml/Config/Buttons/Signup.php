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