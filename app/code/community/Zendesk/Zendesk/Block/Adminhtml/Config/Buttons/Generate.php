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

class Zendesk_Zendesk_Block_Adminhtml_Config_Buttons_Generate extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if (!$this->getTemplate()) {
            $this->setTemplate('zendesk/config/button-generate.phtml');
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
}