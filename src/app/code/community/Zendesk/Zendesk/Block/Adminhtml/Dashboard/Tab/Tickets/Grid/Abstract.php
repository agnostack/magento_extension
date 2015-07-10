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

abstract class Zendesk_Zendesk_Block_Adminhtml_Dashboard_Tab_Tickets_Grid_Abstract extends Mage_Adminhtml_Block_Widget_Grid {
    protected $_page;
    protected $_limit;
    protected $_viewId;
    
    protected $_defaultLimit    = 20;
    protected $_defaultPage     = 1;
    protected $_defaultSort     = 'created_at';
    protected $_defaultDir      = 'desc';
    
    protected abstract function _getCollection($collection);
    
    protected function _getCollectionModel() {
        return Mage::getModel('zendesk/resource_tickets_collection');
    }

    public function setViewId($id = null) {
        $this->_viewId = (is_null($id) ? uniqid() : $id);
    }

    public function __construct($attributes = array()) {
        parent::__construct($attributes);
        
        $this->_defaultSort = Mage::getStoreConfig('zendesk/backend_features/default_sort');
        $this->_defaultDir = Mage::getStoreConfig('zendesk/backend_features/default_sort_dir');
        
        $this->setTemplate('zendesk/widget/grid.phtml');
        
        $this->_emptyText   = Mage::helper('zendesk')->__('No tickets found');
    }
    
    protected function _construct() {
        parent::_construct();

        $this->setMassactionBlockName('zendesk/adminhtml_dashboard_tab_tickets_grid_massaction');
        $this->setId('zendesk_tab_tickets_grid_' . $this->_viewId);
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        
        if ($this->getRequest()->getParam('collapse')) {
            $this->setIsCollapsed(true);
        }
        
        $this->_page    = (int) $this->getParam( $this->getVarNamePage(), $this->_defaultPage);
        $this->_limit   = (int) $this->getParam( $this->getVarNameLimit(), $this->_defaultLimit);
    }
    
    protected function _preparePage() {
        parent::_preparePage();

        $this->_page = (int) $this->getParam($this->getVarNamePage(), $this->_defaultPage);
        $this->_limit = (int) $this->getParam($this->getVarNameLimit(), $this->_defaultLimit);
    }

    protected function _prepareCollection() {
        if( ! $this->getCollection() ) {
            $collection     = $this->_getCollectionModel();
            $filter         = $this->getParam('filter');
            $filterData     = Mage::helper('adminhtml')->prepareFilterString($filter);

            foreach($filterData as $fieldName => $value) {
                $collection->addFieldToFilter($fieldName, $value);
            }

            $this->setDefaultLimit( $this->getParam('limit', $this->_defaultLimit) );
            $this->setCollection( $this->_getCollection($collection) );
        }
        
        return parent::_prepareCollection();
    }
    
    protected function _prepareMassaction() {
        parent::_prepareMassaction();

        // Disable mass actions if not allowed for the current user's role
        if ( ! Mage::getSingleton('admin/session')->isAllowed('zendesk/zendesk_dashboard/bulk_actions')) {
            return;
        }

        $this->setMassactionIdField('id');
        $this->getMassactionBlock()->setFormFieldName('id');
        
        $formKey = Mage::getSingleton('core/session')->getFormKey();
        
        $this->getMassactionBlock()->addItem('delete', array(
            'label'         => Mage::helper('zendesk')->__('Delete'),
            'url'           => $this->getUrl('adminhtml/zendesk/bulkDelete', array('form_key' => $formKey, '_current' => true)),
            'confirm'       => Mage::helper('zendesk')->__('Are you sure you want to delete selected tickets?')
        ));
       
        $this->getMassactionBlock()->addItem('change_status', array(
            'label'         => Mage::helper('zendesk')->__('Change Status'),
            'url'           => $this->getUrl('adminhtml/zendesk/bulkChangeStatus', array('form_key' => $formKey, '_current' => true)),
            'confirm'       => Mage::helper('zendesk')->__('Are you sure you want to change status of selected tickets?'),
            'additional'    => array(
                'visibility'    => array(
                    'name'          => 'status',
                    'type'          => 'select',
                    'class'         => 'required-entry',
                    'label'         => Mage::helper('zendesk')->__('Status'),
                    'values'        => Mage::helper('zendesk')->getStatusMap()
                )
            )
        ));
        
        $this->getMassactionBlock()->addItem('change_priority', array(
            'label'         => Mage::helper('zendesk')->__('Change Priority'),
            'url'           => $this->getUrl('adminhtml/zendesk/bulkChangePriority', array('form_key' => $formKey, '_current' => true)),
            'confirm'       => Mage::helper('zendesk')->__('Are you sure you want to change priority of selected tickets?'),
            'additional'    => array(
                'visibility'    => array(
                    'name'          => 'priority',
                    'type'          => 'select',
                    'class'         => 'required-entry',
                    'label'         => Mage::helper('zendesk')->__('Priority'),
                    'values'        => Mage::helper('zendesk')->getPriorityMap()
                )
            )
        ));
        
        $this->getMassactionBlock()->addItem('change_type', array(
            'label'         => Mage::helper('zendesk')->__('Change Type'),
            'url'           => $this->getUrl('adminhtml/zendesk/bulkChangeType', array('form_key' => $formKey, '_current' => true)),
            'confirm'       => Mage::helper('zendesk')->__('Are you sure you want to change type of selected tickets?'),
            'additional'    => array(
                'visibility'    => array(
                    'name'          => 'type',
                    'type'          => 'select',
                    'class'         => 'required-entry',
                    'label'         => Mage::helper('zendesk')->__('Type'),
                    'values'        => Mage::helper('zendesk')->getTypeMap()
                )
            )
        ));
        
        $this->getMassactionBlock()->addItem('mark_as_spam', array(
            'label'         => Mage::helper('zendesk')->__('Mark as Spam'),
            'url'           => $this->getUrl('adminhtml/zendesk/bulkMarkSpam', array('form_key' => $formKey, '_current' => true)),
            'confirm'       => Mage::helper('zendesk')->__('Are you sure you want to mark as spam selected tickets?'),
        ));
        
        return $this;
    }
    
    protected function getNoFilterMassactionColumn(){
        return true;
    }
    
    protected function addColumnBasedOnType($index, $title, $filter = false, $sortable = true) {
        $column = array(
            'header'    => Mage::helper('zendesk')->__($title),
            'sortable'  => $sortable,
            'filter'    => $filter,
            'index'     => $index,
            'type'      => $this->getColumnType($index),
        );
        
        $renderer = $this->getColumnRenderer($index);
        
        if($renderer !== null) {
            $column['renderer'] = $renderer;
        }
        
        $this->addColumn($index, $column);
    }
    
    protected function getColumnType($index) {
        switch($index) {
            case 'created_at':
            case 'created':
            case 'requested':
            case 'updated_at':
            case 'updated':
                return 'datetime';
            default:
                return 'text';
        }
    }
    
    protected function getColumnRenderer($index) {
        switch($index) {
            case 'requester':
            case 'assignee':
                return 'zendesk/adminhtml_dashboard_tab_tickets_grid_renderer_user';
            case 'subject':
                return 'zendesk/adminhtml_dashboard_tab_tickets_grid_renderer_action';
            case 'group':
                return 'zendesk/adminhtml_dashboard_tab_tickets_grid_renderer_group';
            default:
                return null;
        }
    }
    
    protected function getGridParams() {
        return array(
            'page'          => $this->_page,
            'per_page'      => $this->_limit,
            'sort_order'    => $this->getParam( $this->getVarNameDir(), $this->_defaultDir),
            'sort_by'       => $this->getParam( $this->getVarNameSort(), $this->_defaultSort),
        );
    }
    
    public function getGridJavascript()
    {
        $js = $this->getJsObjectName()."= new varienGrid('".$this->getId()."', '".$this->getGridUrl()."', '".$this->getVarNamePage()."', '".$this->getVarNameSort()."', '".$this->getVarNameDir()."', '".$this->getVarNameFilter()."');";
        $js .= $this->getJsObjectName() .".useAjax = '".$this->getUseAjax()."';";
        
        if($this->getRowClickCallback())
            $js .= $this->getJsObjectName() .".rowClickCallback = ".$this->getRowClickCallback().";";
  
        if($this->getCheckboxCheckCallback())
            $js .= $this->getJsObjectName().".checkboxCheckCallback = ".$this->getCheckboxCheckCallback().";";
        
        if($this->getRowInitCallback()) {
            $js .= $this->getJsObjectName().".initRowCallback = ".$this->getRowInitCallback().";";
            $js .= $this->getJsObjectName().".initGridRows();";
        }
        
        if($this->getMassactionBlock()->isAvailable())
            $js .= $this->getMassactionBlock()->getJavaScript();
        
        $js .= $this->getAdditionalJavaScript();
        
        return $js;
    }
    
}
