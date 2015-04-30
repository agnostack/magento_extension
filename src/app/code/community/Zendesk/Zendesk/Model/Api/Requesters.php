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

class Zendesk_Zendesk_Model_Api_Requesters extends Zendesk_Zendesk_Model_Api_Users
{
    public function create($email, $name = null)
    {
        if(!Zend_Validate::is($email, 'EmailAddress')) {
            throw new InvalidArgumentException('Invalid email address provided');
        }

        if(!Zend_Validate::is($name, 'NotEmpty')) {
            throw new InvalidArgumentException('No name provided');
        }

        $data = array(
            'user' => array(
                'email' => $email,
                'name' => $name,
                'role' => 'end-user',
            )
        );
        $response = $this->_call('users.json', null, 'POST', $data);

        return $response['user'];
    }
}