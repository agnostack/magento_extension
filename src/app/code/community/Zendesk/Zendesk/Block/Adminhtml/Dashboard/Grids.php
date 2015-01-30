<?php

/**
 * Copyright 2012 Zendesk.
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

class Zendesk_Zendesk_Block_Adminhtml_Dashboard_Grids extends Mage_Adminhtml_Block_Widget_Tabs {

    public function __construct() {
        parent::__construct();
        
        $this->setId('tickets_grid_tab');
        $this->setDestElementId('tickets_grid_tab_content');
        $this->setTemplate('widget/tabshoriz.phtml');
    }
    
    protected function _prepareLayout() {
        // Check if we are on the main admin dashboard and, if so, whether we should be showing the grid
        // Note: an additional check in the template is needed, but this will prevent unnecessary API calls to Zendesk
        if (!$this->getIsZendeskDashboard() && !Mage::getStoreConfig('zendesk/features/show_on_dashboard')) {
            return parent::_prepareLayout();
        }

        //check if module is setted up
        $configured     = (bool) Mage::getStoreConfig('zendesk/general/domain');
        $viewsIds       = Mage::getStoreConfig('zendesk/backend_features/show_views') ? Mage::helper('zendesk')->getChosenViews() : array(); 

        if( Mage::getStoreConfig('zendesk/backend_features/show_all') AND $configured) {
            $this->addTab('all-tickets', array(
                'label' => $this->__("All tickets"),
                'class' => 'ajax',
                'url'   => $this->getUrl('zendesk/adminhtml_zendesk/ticketsAll'),
            ));
        }

        if(count($viewsIds) AND $configured) {
            try {
                $allTicketView = Mage::getModel('zendesk/api_views')->active();
                $ticketsCounts = Mage::getModel('zendesk/api_views')->countByIds($viewsIds);

            } catch (Exception $ex) {
                $allTicketView = array();
            }
                
            foreach($viewsIds as $viewId) {
                $view = array_shift(array_filter($allTicketView, function($view) use($viewId) {
                    return $view['id'] === (int) $viewId;
                }));
                
                $count = array_shift(array_filter($ticketsCounts['view_counts'], function($view) use($viewId) {
                    return $view['view_id'] === (int) $viewId;
                }));
                    
                $label = $view['title'] . ' (' . $count['value'] . ')';
                
                if( $count['value'] ) {
                    $this->addTab($viewId, array(
                        'label' => $label,
                        'class' => 'ajax',
                        'url'   => $this->getUrl('zendesk/adminhtml_zendesk/ticketsView', array('id' => $viewId)),
                    ));
                }
                else {
                    Mage::unregister('zendesk_tickets_view');
                    Mage::register('zendesk_tickets_view', $viewId);

                    $this->addTab($viewId, array(
                        'label'     => $label,
                        'content'   => $this->getLayout()->createBlock('zendesk/adminhtml_dashboard_tab_tickets_grid_view')->toHtml()
                    ));
                    }

                }
            }
        else {
            if ($this->getIsZendeskDashboard()) {
                $block = $this->getLayout()->createBlock('core/template', 'zendesk_dashboard_empty')->setTemplate('zendesk/dashboard/empty.phtml');
                $this->getLayout()->getBlock('zendesk_dashboard')->append($block);
            }
        }

        return parent::_prepareLayout();
    }
    
    public function getIsZendeskDashboard() {
        return Mage::app()->getFrontController()->getRequest()->getControllerName() === 'zendesk';
    }

        }
