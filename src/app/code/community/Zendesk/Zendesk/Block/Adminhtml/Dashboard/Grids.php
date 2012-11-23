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

class Zendesk_Zendesk_Block_Adminhtml_Dashboard_Grids extends Mage_Adminhtml_Block_Widget_Tabs
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('tickets_grid_tab');
        $this->setDestElementId('tickets_grid_tab_content');
        $this->setTemplate('widget/tabshoriz.phtml');
    }
    
    protected function _prepareLayout()
    {
        // Check if we are on the main admin dashboard and, if so, whether we should be showing the grid
        // Note: an additional check in the template is needed, but this will prevent unnecessary API calls to Zendesk
        if(!$this->getIsZendeskDashboard() && !Mage::getStoreConfig('zendesk/features/show_on_dashboard')) {
            return parent::_prepareLayout();
        }

        $views = null;
        $first = true;

        if(Mage::getStoreConfig('zendesk/features/show_views')) {
            $list = trim(trim(Mage::getStoreConfig('zendesk/features/show_views')), ',');
            $views = explode(',', $list);
        }

        if($views && count($views)) {
            foreach($views as $viewId) {
                try {
                    $view = Mage::getModel('zendesk/api_views')->get($viewId);

                    $tab = array(
                        'label'     => $this->__($view['title']),
                        'content'   => $this->getLayout()->createBlock('zendesk/adminhtml_dashboard_tab_view')->setView($view)->toHtml(),
                    );

                    if($first) {
                        $tab['active'] = true;
                        $first = false;
                    }

                    $this->addTab($viewId, $tab);

                } catch(Exception $e) {
                    // Just don't add the tab
                }
            }
        } else {
            if($this->getIsZendeskDashboard()) {
                $block = $this->getLayout()->createBlock('core/template', 'zendesk_dashboard_empty')->setTemplate('zendesk/dashboard/empty.phtml');
                $this->getLayout()->getBlock('zendesk_dashboard')->append($block);
            }
        }

        return parent::_prepareLayout();
    }

    public function getIsZendeskDashboard()
    {
        $controller = Mage::app()->getFrontController()->getRequest()->getControllerName();

        if($controller == 'zendesk') {
            return true;
        } else {
            return false;
        }
    }
}
