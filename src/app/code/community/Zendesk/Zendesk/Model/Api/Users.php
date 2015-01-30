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

    public function me()
    {
        $response = $this->_call('users/me.json');

        return $response['user'];
    }

    public function get($id)
    {
        if(!Zend_Validate::is($id, 'NotEmpty')) {
            throw new InvalidArgumentException('No ID value provided');
        }

        $response = $this->_call('users/' . $id . '.json');

        return $response['user'];
    }

    public function all()
    {
        $page = 1;
        $users = array();
        
        while($page) {
            $response   = $this->_call('users.json?page=' . $page);
            $users      = array_merge($users, $response['users']);
            $page       = is_null($response['next_page']) ? 0 : $page + 1;
    }
    
        return $users;
    }
    
    public function end($id)
    {
        if(!Zend_Validate::is($id, 'NotEmpty')) {
            throw new InvalidArgumentException('No ID value provided');
        }
        
        $response = $this->_call('end_users/'. $id .'.json');
        
        return $response['user'];
    }
    
    public function getIdentities($id)
    {
        $response = $this->_call('users/' . $id . '/identities.json');
        return $response['identities'];
    }
    
    public function setPrimaryIdentity($user_id, $identity_id)
    {
        $response = $this->_call('users/' . $user_id . '/identities/'.$identity_id.'/make_primary.json', null, 'PUT', null, true);
        return $response['identities'];
    }
    
    public function addIdentity($user_id, $data)
    {
        $response = $this->_call('users/' . $user_id . '/identities.json', null, 'POST', $data, true);
        return $response['identity'];
    }
    
    public function update($user_id, $user)
    {
        $response = $this->_call('users/' . $user_id . '.json', null, 'PUT', $user, true);
        return $response['user'];
    }
    
    public function create($user)
    {
        $response = $this->_call('users.json', null, 'POST', $user, true);
        return $response['user'];
    }
    
    public function createUserField($field)
    {
        $response = $this->_call('user_fields.json', null, 'POST', $field, true);
        return $response['user_field'];
    }
}