<?php

/* * ********************************************************************
 * Customization Services by ModulesGarden.com
 * Copyright (c) ModulesGarden, INBS Group Brand, All Rights Reserved 
 * (2014-05-13, 12:05:12)
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
 * @author Maksym Minenko <max.mi@modulesgarden.com>
 */
class Zendesk_Zendesk_Model_Resource_Tickets_Collection extends Varien_Data_Collection
{
    protected $_count;


    public function __construct($data)
    {      
        $all = Mage::getModel('zendesk/api_tickets')->search($data);
        foreach ( $all['results'] as $ticket )
        {
            $obj = new Varien_Object();
            $obj->setData($ticket);
            $this->addItem($obj);
        }

        $this->setPageSize($data['per_page']);
        $this->setCurPage($data['page']);
        $this->setOrder($data['sort_by'],$data['sort_order']);
        $this->_count = $all['count'];
        
        //Save the total tickets count value to make new request unnecessary
        Mage::register('zendesk_tickets_count', $all['count']);
    }
    
    /**
     * Retrieve collection all items count
     *
     * @return int
     */
    public function getSize()
    {
        return $this->_count;
    }

}
