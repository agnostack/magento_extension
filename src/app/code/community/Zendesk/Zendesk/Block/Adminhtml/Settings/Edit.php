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
