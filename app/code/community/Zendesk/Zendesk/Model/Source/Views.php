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

class Zendesk_Zendesk_Model_Source_Views
{
    protected $_options;

    public function toOptionArray($isMultiselect=false)
    {
        if (!$this->_options) {
            try {
                $views = Mage::getModel('zendesk/api_views')->active();
                foreach($views as $view) {
                    $this->_options[] = array(
                        'value' => $view['id'],
                        'label' => $view['title'],
                    );
                }
            } catch(Exception $e) {
                // Just don't display anything
            }

        }

        $options = $this->_options;

        if(!$isMultiselect){
            array_unshift($options, array('value'=>'', 'label'=> Mage::helper('adminhtml')->__('--Please Select--')));
        }

        return $options;
    }
}