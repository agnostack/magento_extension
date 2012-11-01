<?php
/**
 * Zendesk Magento integration
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to The MIT License (MIT) that is bundled with
 * this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/mit-license.php
 *
 * @copyright Copyright (c) 2012 Zendesk (www.zendesk.com)
 * @license http://opensource.org/licenses/mit-license.php The MIT License (MIT)
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

    public function execute($id)
    {
        if(!Zend_Validate::is($id, 'NotEmpty')) {
            throw new InvalidArgumentException('View ID not provided');
        }

        $response = $this->_call('views/' . $id . '/execute.json');
        return $response;
    }
}