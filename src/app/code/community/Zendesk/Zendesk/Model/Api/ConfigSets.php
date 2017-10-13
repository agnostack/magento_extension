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

class Zendesk_Zendesk_Model_Api_ConfigSets extends Zendesk_Zendesk_Model_Api_Abstract
{
    CONST DEFAULT_WIDGET_COLOR = '#78a300';

    CONST DEFAULT_WIDGET_POSITION = 'right';

    /**
     * Finds the embeddable config sets in the account
     *
     * @return array
     */
    public function find()
    {
        $response = $this->_call('embeddable/config');

        return $response ? $response['embeds'] : [];
    }

    /**
     * Initializes Zendesk's WebWidget
     *
     * @param array $config
     */
    public function initialize($config = [])
    {
        $config = array_merge([], $this->_getDefaultWidgetConfig());

        return $this->_call('embeddable/api/config_sets.json', null, 'POST', [
            'config_set' => $config,
        ]);
    }

    /**
     * Override the _getUrl method to prevent appending the api/v2 base path
     *
     * @param string $path
     * @return string
     */
    protected function _getUrl($path)
    {
        return 'https://' . $this->getDomain() . '/' . trim($path, '/');
    }

    /**
     * Returns the default widget config
     *
     * @return array
     */
    private function _getDefaultWidgetConfig()
    {
        return [
            'color' => self::DEFAULT_WIDGET_COLOR,
            'position' => self::DEFAULT_WIDGET_POSITION,
        ];
    }
}
