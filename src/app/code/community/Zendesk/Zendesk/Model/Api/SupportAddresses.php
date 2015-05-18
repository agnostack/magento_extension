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

class Zendesk_Zendesk_Model_Api_SupportAddresses extends Zendesk_Zendesk_Model_Api_Users
{
    public function all()
    {
        $page = 1;
        $addresses = array();

        while ($page) {
            $response  = $this->_call('recipient_addresses.json?page=' . $page);
            $addresses = array_merge($addresses, $response['recipient_addresses']);
            $page      = is_null($response['next_page']) ? 0 : $page + 1;
        }

        return $addresses;
    }

    /**
     * Gets the default support address.
     * @return array The default support address email.
     */
    public function getDefault()
    {
        $address = null;

        foreach ($this->all() as $recipient_address) {
            if ($recipient_address['default']) {
                $address = $recipient_address;
                break;
            }
        }

        return $address;
    }
}