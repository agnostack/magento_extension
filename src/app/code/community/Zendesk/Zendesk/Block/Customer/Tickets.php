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

class Zendesk_Zendesk_Block_Customer_Tickets extends Mage_Core_Block_Template
{
    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('zendesk/customer/tickets.phtml');
    }
    
    public function getSubmitAction() {
        if (!$return_url = Mage::getStoreConfig('zendesk/sso_frontend/new')) {
            $return_url = "http://".Mage::getStoreConfig('zendesk/general/domain')."/requests/new";
        }
        $url = Mage::helper('adminhtml')->getUrl('*/sso/login', array("return_url" => Mage::helper('core')->urlEncode($return_url)));

        return $url;
    }
}
