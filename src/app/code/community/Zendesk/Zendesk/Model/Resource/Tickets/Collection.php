<?php
/**
 * Copyright 2015 Zendesk
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
