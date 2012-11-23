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

class Zendesk_Zendesk_Model_Api_Users extends Zendesk_Zendesk_Model_Api_Abstract
{
    public function find($email)
    {
        if(!Zend_Validate::is($email, 'EmailAddress')) {
            throw new InvalidArgumentException('Invalid email address provided');
        }

        $response = $this->_call('users/search.json', array('query' => $email, 'per_page' => 30));

        if($response['count'] > 0) {
            $user = array_shift($response['users']);
            return $user;
        } else {
            return false;
        }
    }

    public function get($id)
    {
        if(!Zend_Validate::is($id, 'NotEmpty')) {
            throw new InvalidArgumentException('No ID value provided');
        }

        $response = $this->_call('users/' . $id . '.json');

        return $response['user'];
    }
}