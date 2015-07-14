<?php

class Zendesk_Zendesk_Model_Api_SupportAddresses extends Zendesk_Zendesk_Model_Api_Users
{
    public function all()
    {
        $page = 1;
        $addresses = array();

        while ($page && $response = $this->_call('recipient_addresses.json?page=' . $page)) {
            $addresses = array_merge($addresses, $response['recipient_addresses']);
            $page      = is_null($response['next_page']) ? 0 : $page + 1;
        }

        return $addresses;
    }

    /**
     * Gets the default support address.
     * @return array The default support address email.
     */
    public function getDefault()
    {
        $address = null;

        foreach ($this->all() as $recipient_address) {
            if ($recipient_address['default']) {
                $address = $recipient_address;
                break;
            }
        }

        return $address;
    }
}
