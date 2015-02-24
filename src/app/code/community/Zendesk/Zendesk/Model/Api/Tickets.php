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

class Zendesk_Zendesk_Model_Api_Tickets extends Zendesk_Zendesk_Model_Api_Abstract
{
    public function get($id, $sideload = false)
    {
        if(!$id) {
            throw new InvalidArgumentException('Ticket ID not valid');
        }

        $include = '';
        if($sideload) {
            $include = '?include=' . implode(',', array('users', 'groups'));
        }

        $response = $this->_call('tickets/' . $id . '.json' . $include);
        $ticket = $response['ticket'];

        if($sideload) {
            // Sideload user information
            if(isset($response['users'])) {
                // Generate the list of user IDs from the users provided
                $users = array();
                foreach($response['users'] as $user) {
                    $users[$user['id']] = $user;
                }

                // Use the list of generated users to attach additional details to the ticket
                if(isset($ticket['requester_id'])) {
                    if(isset($users[$ticket['requester_id']])) {
                        $ticket['requester'] = $users[$ticket['requester_id']];
                    }
                }

                if(isset($ticket['submitter_id'])) {
                    if(isset($users[$ticket['submitter_id']])) {
                        $ticket['submitter'] = $users[$ticket['submitter_id']];
                    }
                }

                if(isset($ticket['assignee_id'])) {
                    if(isset($users[$ticket['assignee_id']])) {
                        $ticket['assignee'] = $users[$ticket['assignee_id']];
                    }
                }
            }

            // Sideload group information
            if(isset($response['groups'])) {
                // Generate the list of group IDs from the users provided
                $groups = array();
                foreach($response['groups'] as $group) {
                    $groups[$group['id']] = $group;
                }

                // Use the list of generated groups to attach additional details to the ticket
                if(isset($ticket['group_id'])) {
                    if(isset($groups[$ticket['group_id']])) {
                        $ticket['group'] = $groups[$ticket['group_id']];
                    }
                }
            }
        }

        return $ticket;
    }

    public function recent()
    {
        $response = $this->_call('tickets/recent.json');

        return $response['tickets'];
    }

    public function all()
    {
        $response = $this->_call('tickets.json');
        return $response['tickets'];
    }
    
    public function search($data)
    {
        return $this->_call('search.json', $data);
    }
        
    public function forOrder($orderIncrementId)
    {
        if(!$orderIncrementId) {
            throw new InvalidArgumentException('Order Increment ID not valid');
        }

        $response = $this->_call('search.json',
            array(
                 'query' => 'type:ticket ' . $orderIncrementId,
                 'sort_order' => 'desc',
                 'sort_by' => 'updated_at',
            )
        );

        // Now check through the tickets to make sure the appropriate field has been filled out with the order number
        $tickets = array();
        $fieldId = Mage::getStoreConfig('zendesk/frontend_features/order_field_id');

        if(!$fieldId) {
            return false;
        }

        foreach($response['results'] as $ticket) {
            foreach($ticket['fields'] as $field) {
                if($field['id'] == $fieldId) {
                    // Check if the value matches our order number
                    if($field['value'] == $orderIncrementId) {
                        $tickets[] = $ticket;
                    }

                    // Regardless of whether the value matches, this is the correct field, so move to the next ticket
                    continue;
                }
            }
        }

        if(count($tickets)) {
            return $tickets;
        } else {
            return false;
        }
    }

    public function forRequester($customerEmail)
    {
        $user = Mage::getModel('zendesk/api_users')->find($customerEmail);
        if(isset($user['id'])) {
            $response = $this->_call('users/' . $user['id'] . '/requests.json', null, 'GET', null, false);
            return $response['requests'];
        } else {
            return array();
        }
    }
    
    public function bulkDelete($data)
    {
        if (is_array($data)) {
            $params['ids'] = implode(",",$data);
            return $this->_call('tickets/destroy_many.json', $params, 'DELETE');
        }
    }
    
    public function updateMany($ids, $data) {
        if(is_array($ids)) {
            $params['ids'] = implode(",", $ids);
            $ticket['ticket'] = $data;
            
            return $this->_call('tickets/update_many.json', $params, 'PUT', $ticket);
        }
    }
    
    public function getJobStatus($url)
    {
        $parts = explode("/", $url);
        $link =  'job_statuses/'.end($parts);
        return $this->_call($link);
    }
    
    public function bulkMarkAsSpam($data)
    {
        if (is_array($data)) {
            $params['ids'] = implode(",",$data);
            return $this->_call('tickets/mark_many_as_spam.json', $params, 'PUT');
        }
    }
    
    public function create($data)
    {
        $response = $this->_call('tickets.json', null, 'POST', $data);
        
        return $response['ticket'];
    }

}