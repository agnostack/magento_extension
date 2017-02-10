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
            $this->formatSideloaded($response, $ticket);
        }

        return $ticket;
    }

    public function recent()
    {
        $response = $this->_call('tickets/recent.json', array('include' => 'users,groups'));

        return (isset($response['tickets']) ? $response['tickets'] : null);
    }

    public function all()
    {
        $response = $this->_call('tickets.json', array('include' => 'users,groups'));
        return (isset($response['tickets']) ? $response['tickets'] : null);
    }
    
    public function search($data)
    {
        $data['include'] = 'users,groups';
        return $this->_call('search/incremental', $data);
    }

    /**
     * Retrieves a Zendesk Support ticket associated to an order with a custom ticket field
     *
     * @param int $orderIncrementId
     * @return array|boolean
     */
    public function forOrder($orderIncrementId)
    {
        $fieldId = Mage::getStoreConfig('zendesk/frontend_features/order_field_id');
        if(!$fieldId) {
            return false;
        }

        if(!$orderIncrementId) {
            throw new InvalidArgumentException('Order Increment ID not valid');
        }

        $response = $this->_call('search.json',
            array(
                 'query' => "type:ticket fieldValue:{$orderIncrementId}",
                 'sort_order' => 'desc',
                 'sort_by' => 'updated_at',
            )
        );

        if(count($response['results'])) {
            return $response['results'];
        } else {
            return false;
        }
    }

    public function forRequester($customerEmail)
    {
        $user = Mage::getModel('zendesk/api_users')->find($customerEmail);
        if(isset($user['id'])) {
            $response = $this->_call(
                'users/' . $user['id'] . '/tickets/requested.json',
                array('include' => 'users,groups', 'sort_by' => 'updated_at', 'sort_order' => 'desc'),
                'GET',
                null,
                false
            );

            foreach ($response['tickets'] as &$request) {
                $request = $this->formatSideloaded($response, $request);
            }

            return $response['tickets'];
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
        
        return (isset($response['ticket']) ? $response['ticket'] : null);
    }

    private function formatSideloaded($response, $ticket)
    {
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

        return $ticket;
    }
}
