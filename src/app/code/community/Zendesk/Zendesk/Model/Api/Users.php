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

        return (isset($response['user']) ? $response['user'] : null);
    }

    public function get($id)
    {
        if(!Zend_Validate::is($id, 'NotEmpty')) {
            throw new InvalidArgumentException('No ID value provided');
        }

        $response = $this->_call('users/' . $id . '.json');

        return (isset($response['user']) ? $response['user'] : null);
    }

    public function all()
    {
        $page = 1;
        $users = array();
        
        while($page && $response = $this->_call('users.json?page=' . $page)) {
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
        
        return (isset($response['user']) ? $response['user'] : null);
    }
    
    public function getIdentities($id)
    {
        $response = $this->_call('users/' . $id . '/identities.json');
        return (isset($response['identities']) ? $response['identities'] : null);
    }
    
    public function setPrimaryIdentity($user_id, $identity_id)
    {
        $response = $this->_call('users/' . $user_id . '/identities/'.$identity_id.'/make_primary.json', null, 'PUT', null, true);
        return (isset($response['identities']) ? $response['identities'] : null);
    }
    
    public function addIdentity($user_id, $data)
    {
        $response = $this->_call('users/' . $user_id . '/identities.json', null, 'POST', $data, true);
        return (isset($response['identity']) ? $response['identity'] : null);
    }
    
    public function update($user_id, $user)
    {
        $response = $this->_call('users/' . $user_id . '.json', null, 'PUT', $user, true);
        return (isset($response['user']) ? $response['user'] : null);
    }
    
    public function create($user)
    {
        $response = $this->_call('users.json', null, 'POST', $user, true);
        return (isset($response['user']) ? $response['user'] : null);
    }
    
    public function createUserField($field)
    {
        $response = $this->_call('user_fields.json', null, 'POST', $field, true);

        if(!isset($response['user_field'])) {
            throw new Exception('No User Field specified.');
        }
 
        return $response['user_field'];
    }

    /**
     * Fetch all user fields
     * 
     * @return array $userFields
     */
    public function getUserFields()
    {
        $page = 1;
        $userFields = array();
        while($page && $response = $this->_call('user_fields.json?page=' . $page)) {
            $userFields = array_merge($userFields, $response['user_fields']);
            $page       = is_null($response['next_page']) ? 0 : $page + 1;
        }
    
        return $userFields;
    }
}
