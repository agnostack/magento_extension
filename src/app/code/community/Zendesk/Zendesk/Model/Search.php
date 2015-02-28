<?php

/**
 * Copyright 2013 Zendesk.
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

class Zendesk_Zendesk_Model_Search {
    const TYPE_TICKET = 'ticket';
    const TYPE_USER = 'user';
    
    protected $type;
    protected $separator = ' ';
    protected $fields = array();
    
    public function __construct($type) {
        $this->setType($type);
    }
    
    public function setType($type) {
        $this->type = $type;
    }
    
    public function addField(Zendesk_Zendesk_Model_Search_Field $field) {
        $this->fields[] = $field;
    }
    
    public function addFields(array $fields) {
        foreach($fields as $field) {
            if($field instanceof Zendesk_Zendesk_Model_Search_Field) {
                $this->addField($field);
            }
        }
    }
    
    public function getString() {
        return 'type:' . $this->type . $this->separator . implode($this->separator, $this->fields);
    }
}
