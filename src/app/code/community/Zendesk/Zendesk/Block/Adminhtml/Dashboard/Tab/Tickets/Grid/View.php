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

class Zendesk_Zendesk_Block_Adminhtml_Dashboard_Tab_Tickets_Grid_View extends Zendesk_Zendesk_Block_Adminhtml_Dashboard_Tab_Tickets_Grid_Abstract {
    
    public function __construct($attributes = array()) {
        $viewId = Mage::registry('zendesk_tickets_view');
        $this->setViewId($viewId);
        
        parent::__construct($attributes);
    }
    
    protected function _getCollection($collection) {
        return $collection->getCollectionFromView($this->_viewId, $this->getGridParams());
    }
    
    public function getGridUrl() {
        return $this->getUrl('*/*/ticketsView', array('_current' => true));
    }
    
    protected function _prepareGrid() {
        parent::_prepareGrid();
        $this->_prepareDynamicColumns();
        $this->_prepareCollection();
        
        return $this;
    }
    
    protected function _prepareColumns() {
        $this->addColumn('id', array(
            'header'    => Mage::helper('zendesk')->__('Ticket ID'),
            'sortable'  => true,
            'filter'    => false,
            'align'     => 'right',
            'width'     => '30px',
            'index'     => 'id',
        ));

        return parent::_prepareColumns();
    }
    
    protected function _prepareDynamicColumns() {
        $viewColumns = $this->getCollection()->getColumnsForView();
        
        foreach($viewColumns as $column) {
            $this->addColumnBasedOnType($column['id'], $column['title']);
        }
    }

}