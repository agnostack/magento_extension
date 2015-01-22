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

class Zendesk_Zendesk_Block_Adminhtml_Settings_Button extends Mage_Adminhtml_Block_System_Config_Form_Field {  
    
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element) {
        $this->setElement($element);
        $url = $this->getUrl('zendesk/adminhtml_settings/checkConfigurationConnection'); //

        return $this->getLayout()->createBlock('adminhtml/widget_button')
                    ->setType('button')
                    ->setClass('scalable')
                    ->setLabel( Mage::helper('zendesk')->__('Check Connection') )
                    ->setOnClick("setLocation('$url')")
                    ->toHtml();
    }

}
