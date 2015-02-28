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

class Zendesk_Zendesk_Block_Adminhtml_Dashboard_Tab_Tickets_Grid_All extends Zendesk_Zendesk_Block_Adminhtml_Dashboard_Tab_Tickets_Grid_Abstract {

    public function __construct($attributes = array()) {
        $this->setViewId('all');

        parent::__construct($attributes);
    }

    protected function _getCollection($collection) {
        return $collection->getCollection($this->getGridParams());
    }

    public function getGridUrl() {
        return $this->getUrl('*/*/ticketsAll', array('_current' => true));
    }

    protected function _prepareColumns() {
        $this->addColumn('id', array(
            'header'    => Mage::helper('zendesk')->__('Ticket ID'),
            'sortable'  => false,
            'align'     => 'right',
            'width'     => '30px',
            'index'     => 'id',
        ));

        $this->addColumn('subject', array(
            'header'    => Mage::helper('zendesk')->__('Subject'),
            'sortable'  => false,
            'index'     => 'description',
            'type'      => 'text',
            'renderer'  => 'zendesk/adminhtml_dashboard_tab_tickets_grid_renderer_action',
        ));

        $this->addColumn('requester_id', array(
            'header'    => Mage::helper('zendesk')->__('Email'),
            'width'     => '60',
            'renderer'  => 'zendesk/adminhtml_dashboard_tab_tickets_grid_renderer_email',
            'index'     => 'requester_id',
            'sortable'  => false,
        ));

        $this->addColumn('type', array(
            'header'    => Mage::helper('zendesk')->__('Type'),
            'width'     => '100',
            'type'      => 'options',
            'options'   => Mage::helper('zendesk')->getTypeMap(),
            'index'     => 'type',
            'sortable'  => false,
        ));

        $this->addColumn('status', array(
            'header'    => Mage::helper('zendesk')->__('Status'),
            'sortable'  => true,
            'width'     => '100px',
            'index'     => 'status',
            'type'      => 'options',
            'options'   => Mage::helper('zendesk')->getStatusMap(),
        ));

        $this->addColumn('priority', array(
            'header'    => Mage::helper('zendesk')->__('Priority'),
            'sortable'  => true,
            'width'     => '100px',
            'index'     => 'priority',
            'type'      => 'options',
            'options'   => Mage::helper('zendesk')->getPriorityMap(),
        ));

        $this->addColumn('created_at', array(
            'header'    => Mage::helper('zendesk')->__('Requested'),
            'sortable'  => true,
            'width'     => '160px',
            'index'     => 'created_at',
            'type'      => 'datetime',
        ));

        $this->addColumn('updated_at', array(
            'header'    => Mage::helper('zendesk')->__('Updated'),
            'sortable'  => true,
            'width'     => '160px',
            'index'     => 'updated_at',
            'type'      => 'datetime',
        ));

        return parent::_prepareColumns();
    }

}
