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

class Zendesk_Zendesk_Model_Api_Views extends Zendesk_Zendesk_Model_Api_Abstract
{
    public function active()
    {
        $response = $this->_call('views/active.json');
        return $response['views'];
    }

    public function get($id)
    {
        if(!Zend_Validate::is($id, 'NotEmpty')) {
            throw new InvalidArgumentException('View ID not provided');
        }

        $response = $this->_call('views/' . $id . '.json');
        return $response['view'];
    }

    public function execute($id, array $params = array())
    {
        if(!Zend_Validate::is($id, 'NotEmpty')) {
            throw new InvalidArgumentException('View ID not provided');
        }

        $paramsString = count($params) ? '?' . http_build_query($params) : '';

        $response = $this->_call('views/' . $id . '/execute.json' . $paramsString);
        return $response;
    }
    
    public function countByIds(array $ids) {
        if(empty($ids)) {
            throw new InvalidArgumentException('View ID not provided');
}
        
        $response = $this->_call('views/count_many.json?ids=' . implode(',', $ids));
        
        return $response;
    }
}
