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

class Zendesk_Zendesk_Model_Api_Requesters extends Zendesk_Zendesk_Model_Api_Users
{
    public function create($email, $name)
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