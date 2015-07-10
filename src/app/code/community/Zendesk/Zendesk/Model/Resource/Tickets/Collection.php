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

class Zendesk_Zendesk_Model_Resource_Tickets_Collection extends Varien_Data_Collection {

    protected $_count;
    protected $_search;
    protected $_viewColumns = array();
    protected static $_excludedColumns = array('score');

    public function __construct() {
        $this->_search = new Zendesk_Zendesk_Model_Search( Zendesk_Zendesk_Model_Search::TYPE_TICKET );
    }

    public function addFieldToFilter($fieldName, $condition = null) {
        if(is_string($condition) OR is_array($condition)) {
            
            $searchFields = array();
            
            switch($fieldName) {
                case 'subject':
                    $searchFields[] = array(
                        'field' => 'subject',
                        'value' => '"'.$condition.'"'
                    );
                    break;
                case 'requester':
                case 'requester_id':
                    if (is_array($condition)) {
                        break;
                    }

                    $searchFields[] = array(
                        'field' => 'requester',
                        'value' => '*' . $condition,
                    );

                    $searchFields[] = array(
                        'field' => 'requester',
                        'value' => $condition . '*',
                    );
                    break;
                case 'tags':
                case 'status':
                case 'priority':
                case 'status':
                case 'group':
                case 'assignee':
                    $searchFields[] = array(
                        'field' => $fieldName,
                        'value' => $condition
                    );
                    break;
                case 'type':
                    $searchFields[] = array(
                        'field' => 'ticket_type',
                        'value' => $condition
                    );
                    break;
                case 'id':
                    $searchFields[] = array(
                        'field'     =>  '',
                        'value'     =>  $condition,
                        'operator'  =>  ''
                    );
                    break;
                case 'created_at':
                case 'updated_at':
                    $fieldName  = substr($fieldName, 0, -3);
                    
                    if( isset($condition['from']) AND Mage::helper('zendesk')->isValidDate($condition['from']) ) {
                        $value = Mage::helper('zendesk')->getFormatedDataForAPI( $condition['from'] );
                        $searchFields[] = array(
                            'field'     =>  $fieldName,
                            'value'     =>  $value,
                            'operator'  =>  '>'
                        );
                    }
                    
                    if( isset($condition['to']) AND Mage::helper('zendesk')->isValidDate($condition['to']) ) {
                        $value = Mage::helper('zendesk')->getFormatedDataForAPI( $condition['to'] );
                        $searchFields[] = array(
                            'field'     =>  $fieldName,
                            'value'     =>  $value,
                            'operator'  =>  '<'
                        );
                    }
                    break;
            }
            foreach ($searchFields as $field) {
                $operator = isset($field['operator']) ? $field['operator'] : ":";
                $value = isset($field['value']) ? $field['value'] : "none";
                $this->_search->addField( new Zendesk_Zendesk_Model_Search_Field($field['field'], $value, $operator));
            }
        }

        return $this;
    }
    
    public function getCollection(array $params = array()) {
        $searchQuery = array(
            'query' => $this->_search->getString(),
        );
        
        $params = array_merge($searchQuery, $params);
        
        $all = Mage::getModel('zendesk/api_tickets')->search($params);

        foreach ($all['results'] as $ticket) {
            $obj = new Varien_Object();
            $obj->setData($ticket);
            $this->addItem($obj);
        }

        $this->setPageSize($params['per_page']);
        $this->setCurPage($params['page']);
        $this->setOrder($params['sort_by'], $params['sort_order']);
        $this->_count = $all['count'];
        
        Mage::unregister('zendesk_tickets_all');
        Mage::register('zendesk_tickets_all', $all['count']);
        
        return $this;
    }
    
    public function getCollectionFromView($viewId, array $params = array()) {
        $view = Mage::getModel('zendesk/api_views')->execute($viewId, $params);
        if (is_array($view['rows'])) {
            foreach ($view['rows'] as $row) {
                $ticket = array_merge($row, $row['ticket']);
                
                $this->appendParamsWithoutIdPostfix($ticket, array('requester', 'assignee', 'group'));
                
                $obj = new Varien_Object();
                $obj->setData($ticket);
                $this->addItem($obj);
            }
        }
        
        $this->_viewColumns = $view['columns'] ? $view['columns'] : array();

        $this->setPageSize($params['per_page']);
        $this->setCurPage($params['page']);
        $this->setOrder($params['sort_by'], $params['sort_order']);
        $this->_count = $view['count'];
            
        Mage::unregister('zendesk_tickets_view_'.$viewId);
        Mage::register('zendesk_tickets_view_'.$viewId, $view['count']);
        
        return $this;
    }
    
    protected function appendParamsWithoutIdPostfix(& $item, array $params = array()) {
        foreach($params as $param) {
            $name = $param . '_id';
            
            if(isset($item[$name])) {
                $item[$param] = $item[$name];
            }
        }
    }
    
    public function getColumnsForView() {
        $excludedColumns = static::$_excludedColumns;

        return array_filter($this->_viewColumns, function($column) use($excludedColumns) {
            return ! in_array($column['id'], $excludedColumns);
        });
    }

    /**
     * Retrieve collection all items count
     *
     * @return int
     */
    public function getSize() {
        return (int) $this->_count;
    }

}
