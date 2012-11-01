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