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

class Zendesk_Zendesk_Block_Adminhtml_Menu extends Mage_Adminhtml_Block_Template
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('page_tabs');
        $this->setTemplate('zendesk/left-menu.phtml');
    }

    public function isAllowed($target)
    {
        try {
            if ($target == 'settings') {
                return Mage::getSingleton('admin/session')->isAllowed('admin/system/config/zendesk');
            } else {
                return Mage::getSingleton('admin/session')->isAllowed('admin/zendesk/zendesk_' . $target);
            }
        } catch (Exception $e) {
            return false;
        }
    }
}
