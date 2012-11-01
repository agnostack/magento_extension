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

class Zendesk_Zendesk_Block_Supporttab extends Mage_Core_Block_Template
{
    protected function _toHtml()
    {
        if(!Mage::getStoreConfig('zendesk/features/feedback_tab_code_active')) {
            return '';
        }

        return Mage::getStoreConfig('zendesk/features/feedback_tab_code');
    }
}