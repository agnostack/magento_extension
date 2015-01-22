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

class Zendesk_Zendesk_Model_Settings extends Mage_Core_Model_Abstract {
    
    protected function _construct()
    {
        $this->_init('zendesk/settings');
    }

    public function loadByAdminId($adminId)
    {
        $this->getResource()->loadByAdminId($this, $adminId);
        return $this;
    }

    public function usesGlobalSettings()
    {
        return $this->getData('use_global_settings') == 1;
    }

    public function isConfigured()
    {
        $username = $this->getData('username');
        $password = $this->getData('password');

        return !(empty($username) OR empty($password) );
    }

}
