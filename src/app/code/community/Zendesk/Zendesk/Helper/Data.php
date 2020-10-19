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

class Zendesk_Zendesk_Helper_Data extends Mage_Core_Helper_Abstract
{

    public function getUrl($object = '', $id = null, $format = 'old')
    {
        $protocol = 'https://';
        $domain = Mage::getStoreConfig('zendesk/general/domain');
        $root = ($format === 'old') ? '' : '/agent';

        $base = $protocol . $domain . $root;
        $hc = $protocol . $domain . '/hc';

        switch($object) {
            case '':
                return $base;
                break;

            case 'ticket':
                return $base . '/tickets/' . $id;
                break;

            case 'user':
                return $base . '/users/' . $id;
                break;

            case 'raw':
                return $protocol . $domain . '/' . $id;
                break;

            case 'request':
                return $hc . '/requests/' . $id;
                break;
        }
    }

    /**
     * Returns configured Zendesk Domain
     * format: company.zendesk.com
     *
     * @return mixed Zendesk Account Domain
     */
    public function getZendeskDomain()
    {
        return Mage::getStoreConfig('zendesk/general/domain');
    }


    /**
     * Returns if SSO is enabled for EndUsers
     * @return integer
     */
    public function isSSOEndUsersEnabled()
    {
        return Mage::getStoreConfig('zendesk/sso_frontend/enabled');
    }

    /**
     * Returns if SSO is enabled for Admin/Agent Users
     * @return integer
     */
    public function isSSOAdminUsersEnabled()
    {
        return Mage::getStoreConfig('zendesk/sso/enabled');
    }

    /**
     * Returns frontend URL where authentication process starts for EndUsers
     *
     * @return string SSO Url to auth EndUsers
     */
    public function getSSOAuthUrlEndUsers()
    {
        return Mage::getUrl('zendesk/sso/login');
    }

    /**
     * Returns backend URL where authentication process starts for Admin/Agents
     *
     * @return string SSO Url to auth Admin/Agents
     */
    public function getSSOAuthUrlAdminUsers()
    {
        return Mage::helper('adminhtml')->getUrl('*/zendesk/login');
    }

    /**
     * Returns Zendesk Account Login URL for normal access
     * format: https://<zendesk_account>/<route>
     *
     * @return string Zendesk Account login url
     */
    public function getZendeskAuthNormalUrl()
    {
        $protocol = 'https://';
        $domain = $this->getZendeskDomain();
        $route = '/access/normal';

        return $protocol . $domain . $route;
    }

    /**
     * Returns Zendesk Login Form unauthenticated URL
     * format: https://<zendesk_account>/<route>
     *
     * @return string Zendesk Account login unauthenticated form url
     */
    public function getZendeskUnauthUrl()
    {
        $protocol = 'https://';
        $domain = $this->getZendeskDomain();
        //Zendesk will automatically redirect to login if user is not logged in
        //previous URL followed to login page even if user has already logged in
        $route = '/home';

        return $protocol . $domain . $route;
    }

    public function getApiToken($generate = true)
    {
        // Grab any existing token from the admin scope
        $token = Mage::getStoreConfig('zendesk/api/token', 0);

        if( (!$token || strlen(trim($token)) == 0) && $generate) {
            $token = $this->setApiToken();
        }

        return $token;
    }

    public function setApiToken($token = null)
    {
        if(!$token) {
            $token = hash('sha256', Mage::helper('oauth')->generateToken());
        }
        Mage::getModel('core/config')->saveConfig('zendesk/api/token', $token, 'default');

        return $token;
    }

    public function getOrderDetail($order)
    {
        // if the admin site has a custom URL, use it
        $urlModel = Mage::getModel('adminhtml/url')->setStore('admin');

        $orderInfo = array(
            'id' => $order->getIncrementId(),
            'status' => $order->getStatus(),
            'created' => $order->getCreatedAt(),
            'updated' => $order->getUpdatedAt(),
            'customer' => array(
                'name' => $order->getCustomerName(),
                'email' => $order->getCustomerEmail(),
                'ip' => $order->getRemoteIp(),
                'guest' => (bool)$order->getCustomerIsGuest(),
            ),
            'store' => $order->getStoreName(),
            'total' => $order->getGrandTotal(),
            'currency' => $order->getOrderCurrencyCode(),
            'items' => array(),
            'admin_url' => $urlModel->getUrl('adminhtml/sales_order/view', array('order_id' => $order->getId())),
        );

        foreach($order->getItemsCollection(array(), true) as $item) {
            $orderInfo['items'][] = array(
                'sku' => $item->getSku(),
                'name' => $item->getName(),
            );
        }

        return $orderInfo;
    }

    public function getSupportEmail($store = null)
    {
        // Serves as the dafault email
        $domain = Mage::getStoreConfig('zendesk/general/domain', $store);
        $email = 'support@' . $domain;

        // Get the actual default email from the API, return the default if somehow none is found
        $defaultRecipient = Mage::getModel('zendesk/api_supportAddresses')->getDefault();

        if (!is_null($defaultRecipient)) {
            $email = $defaultRecipient['email'];
        }

        return $email;
    }

    public function loadCustomer($email, $website = null)
    {
        $customer = null;

        if(Mage::getModel('customer/customer')->getSharingConfig()->isWebsiteScope()) {
            // Customer email address can be used in multiple websites so we need to
            // explicitly scope it
            if($website) {
                // We've been given a specific website, so try that
                $customer = Mage::getModel('customer/customer')
                    ->setWebsiteId($website)
                    ->loadByEmail($email);
            } else {
                // No particular website, so load all customers with the given email and then return a single object
                $customers = Mage::getModel('customer/customer')
                    ->getCollection()
                    ->addFieldToFilter('email', array('eq' => array($email)));
                if($customers->getSize()) {
                    $id = $customers->getLastItem()->getId();
                    $customer = Mage::getModel('customer/customer')->load($id);
                }
            }

        } else {
            // Customer email is global, so no scoping issues
            $customer = Mage::getModel('customer/customer')->loadByEmail($email);
        }

        return $customer;
    }

    /**
     * Retrieve Use External ID config option
     *
     * @return integer
     */
    public function isExternalIdEnabled()
    {
        return Mage::getStoreConfig('zendesk/general/use_external_id');
    }

    public function getTicketUrl($row, $link = false)
    {
        if ($this->isAdmin()) {
            $path = 'adminhtml/zendesk/login';
            $object = 'ticket';
        } else {
            $path = '*/sso/login';
            $object = 'request';
        }
        $path = Mage::getSingleton('admin/session')->getUser() ? 'adminhtml/zendesk/login' : '*/sso/login';

        $url = Mage::helper('adminhtml')->getUrl($path, array("return_url" => Mage::helper('core')->urlEncode(Mage::helper('zendesk')->getUrl($object, $row['id']))));

        if ($link)
            return $url;

        $subject = $row['subject'] ? $row['subject'] : $this->__('No Subject');

        return '<a href="' . $url . '" target="_blank">' .  Mage::helper('core')->escapeHtml($subject) . '</a>';
    }

    public function getStatusMap()
    {
        return array(
            'new'       =>  'New',
            'open'      =>  'Open',
            'pending'   =>  'Pending',
            'solved'    =>  'Solved',
            'closed'    =>  'Closed',
            'hold'      =>  'Hold'
        );
    }


    public function getPriorityMap()
    {
        return array(
            'low'       =>  'Low',
            'normal'    =>  'Normal',
            'high'      =>  'High',
            'urgent'    =>  'Urgent'
        );
    }

    public function getTypeMap()
    {
        return array(
            'problem'   =>  'Problem',
            'incident'  =>  'Incident',
            'question'  =>  'Question',
            'task'      =>  'Task'
        );
    }

    public function getChosenViews() {
        $list = trim(trim(Mage::getStoreConfig('zendesk/backend_features/show_views')), ',');
        return explode(',', $list);
    }

    public function getFormatedDataForAPI($dateToFormat) {
        $myDateTime = DateTime::createFromFormat('d/m/Y', $dateToFormat);
        return $myDateTime->format('Y-m-d');
    }

    public function isValidDate($date) {
        if(is_string($date)) {
            $d = DateTime::createFromFormat('d/m/Y', $date);
            return $d && $d->format('d/m/Y') == $date;
        }

        return false;
    }

    public function getFormatedDateTime($dateToFormat) {
        return Mage::helper('core')->formatDate($dateToFormat, 'medium', true);
    }

    /**
     * Tests if the provided username and password is correct. If either is empty the database values will be used.
     *
     * @param  string $domain
     * @param  string $username
     * @param  string $password
     * @return array
     */
    public function getConnectionStatus($domain = null, $username = null, $password = null) {
        try {
            $usersApi = Mage::getModel('zendesk/api_users');

            $usersApi->setUsername($username);
            $usersApi->setPassword($password);
            $usersApi->setDomain($domain);

            $user = $usersApi->me();

            if(isset($user['id'])) {
                return array(
                    'success'   => true,
                    'msg'       => Mage::helper('zendesk')->__('Connection to Zendesk API successful'),
                );
            }

            $error = Mage::helper('zendesk')->__('Connection to Zendesk API failed') .
                '<br />' . Mage::helper('zendesk')->__("Click 'Save Config' and try again. If the issue persist, check if the entered Agent Email Address and Agent Token combination is correct.");

            return array(
                'success'   => false,
                'msg'       => $error,
            );

        } catch (Exception $ex) {
            $error = Mage::helper('zendesk')->__('Connection to Zendesk API failed') .
                '<br />' . $ex->getCode() . ': ' . $ex->getMessage() .
                '<br />' . Mage::helper('zendesk')->__("Click 'Save Config' and try again. If the issue persist, check if the entered Agent Email Address and Agent Token combination is correct.");

            return array(
                'success'   => false,
                'msg'       => $error,
            );
        }
    }

    /**
     * Checks if the current connection details are valid.
     *
     * @return boolean
     */
    public function isConnected() {
        $connection = $this->getConnectionStatus();
        return $connection['success'];
    }

    public function storeDependenciesInCachedRegistry() {
        $cache = Mage::app()->getCache();

        if (null == Mage::registry('zendesk_groups')) {
            if( $cache->load('zendesk_groups') === false) {
                $groups = serialize( Mage::getModel('zendesk/api_groups')->all() );
                $cache->save($groups, 'zendesk_groups', array('zendesk', 'zendesk_groups'), 1200);
            }

            $groups = unserialize( $cache->load('zendesk_groups') );
            Mage::register('zendesk_groups', $groups);
        }
    }

    /**
     * Checks whether the user is in an admin page.
     *
     * @return boolean
     */
    public function isAdmin()
    {
        return (
            Mage::getSingleton('admin/session')->getUser() &&
            (Mage::app()->getStore()->isAdmin() || Mage::getDesign()->getArea() == 'adminhtml')
        );
    }

    #region version 3.0 or later

    protected function formatPrice($price, $currency)
    {
        return array(
            'amount' => $price * 100,
            'currency' => $currency
        );
    }

    protected function formatCustomer($order)
    {
        $isGuest = (bool)$order->getCustomerIsGuest();
        $id = $order->getCustomerId(); // TODO: should this be customerId or entity id??
        $email = $order->getCustomerEmail();
        $customer = array();

        if ($isGuest){
            $customer['type'] = 'guest';
        } else {
            $customer['type'] = 'customer';
        }
        
        if (!empty($id)) {
            $customer['id'] = $id;
        }
        if (!empty($email)) {
            $customer['email'] = $email;
        }
        // TODO: can we get customer URL or timestamps??

        return $customer;
    }

    protected function formatAddress($address)
    {
        if ($address) {
            $addressData = array(
                'type' => 'address',
                'first_name' => $address->getFirstname(),
                'last_name' => $address->getLastname(),
                'city' => $address->getCity(),
                'county' => $address->getRegion(),
                'postcode' => $address->getPostcode(),
                'country' => $address->getCountryId(),
                'phone' => $address->getTelephone()
            );
    
            $entityId = $address->getEntityId();
            $addressId = $address->getCustomerAddressId();
            $addressData['id'] = $addressId ?: $entityId;
    
            $street = $address->getStreet();
            $addressData['line_1'] = $street[0] ?: '';
            $addressData['line_2'] = $street[1] ?: '';
        }

        return $addressData;
    }

    public function getShipments($order)
    {
        $shipments = array();
        $orderStatus = $order->getStatus();
        $serviceCode = $order->getShippingDescription();
        $tracks = $order->getTracksCollection();
        $shippingMethod = $order->getShippingMethod();
        $orderShippingAddress = $order->getShippingAddress();

        foreach($order->getShipmentsCollection() as $shipment) {
            $shipmentId = $shipment->getEntityId();
            $shippingAddress = $shipment->getShippingAddress();
            if ($shipmentId) {
                if (count($tracks) > 0) {
                    foreach($tracks as $track) {
                        if ($shipmentId == $track->getParentId()) {
                            $shipment = array(
                                'id' => $track->getEntityId(),
                                'carrier' => $track->getTitle(),
                                'carrier_code' => $track->getCarrierCode(),
                                'service_code' => $serviceCode,
                                'shipping_description' => $track->getDescription() ?: '',
                                'created_at' => $track->getCreatedAt(),
                                'updated_at' => $track->getUpdatedAt(),
                                'tracking_number' => $track->getTrackNumber(),
                                'order_status' => $orderStatus,
                            );
                            if ($shippingAddress) {
                                $shipment['shipping_address'] = $this->formatAddress($shippingAddress);
                            }
                            $shipments[] = $shipment;
                         }
                    }
                } else {
                    $shipment = array(
                        'service_code' => $serviceCode,
                        'carrier_code' => $shippingMethod,
                        'order_status' => $orderStatus,
                    );
                    if ($shippingAddress) {
                        $shipment['shipping_address'] = $this->formatAddress($shippingAddress);
                    }
                    $shipments[] = $shipment;
                }
            } else {
                if ($orderShippingAddress) {
                    $shipments[] = array(
                        'shipping_address' => $this->formatAddress($orderShippingAddress),
                    );
                }
            }
        }

        return $shipments;
    }

    public function getOrderDetailBasic($order)
    {
        // if the admin site has a custom URL, use it
        $urlModel = Mage::getModel('adminhtml/url')->setStore('admin');

        $currency = $order->getOrderCurrencyCode();
        $shippingAddress = $order->getShippingAddress();
        $shippingWithTax = $order->getShippingInclTax();
        $shippingMethod = $order->getShippingMethod();
        $billingAddress = $order->getBillingAddress();

        $orderInfo = array(
            'id' => $order->getIncrementId(),
            'url' => $urlModel->getUrl('adminhtml/sales_order/view', array('order_id' => $order->getId())),
            'transaction_id' => $order->getIncrementId(),
            'status' => $order->getStatus(),
            'meta' => array(
                'store_info' => array(
                    'type' => 'store_info',
                    'name' => $order->getStoreName()
                ),
                'display_price' => array(
                    'with_tax' => $this->formatPrice($order->getGrandTotal() + $order->getTaxAmount(), $currency),
                    'without_tax' => $this->formatPrice($order->getGrandTotal(), $currency),
                    'tax' => $this->formatPrice($order->getTaxAmount(), $currency)
                ),
                'timestamps' => array(
                    'created_at' => $order->getCreatedAt(),
                    'updated_at' => $order->getUpdatedAt(),
                )
            ),
            'relationships' => array(
                'customer' => array(
                    'data' => $this->formatCustomer($order)
                ),
                'items' => array(
                    'data' => array()
                ),
            ),
            'shipments' => array(),
        );
        if ($billingAddress) {
            $orderInfo['billing_address'] = $this->formatAddress($billingAddress);
        }

        foreach($order->getItemsCollection(array(), true) as $item) {
            $itemWithoutTax = $item->getRowTotal();
            $itemTax = $item->getTaxAmount();

            $productId = $item->getProductId();
            $product = Mage::getModel('catalog/product')->load($productId);

            $adminUrl = $urlModel->getUrl('adminhtml/zendesk/redirect', array('id' => $productId, 'type' => 'product'));

            $orderInfo['relationships']['items']['data'][] = array(
                'type' => 'order_item',
                'id' => $item->getItemId(),
                'product_id' => $item->getProductId(),
                'name' => $item->getName(),
                'sku' => $item->getSku(),
                'quantity' => intval($item->getQtyOrdered()),
                'refunded' => intval($item->getQtyRefunded()),
                'meta' => array(
                    'display_price' => array(
                        'with_tax' => $this->formatPrice($itemWithoutTax + $itemTax, $currency),
                        'without_tax' => $this->formatPrice($itemWithoutTax, $currency),
                        'tax' => $this->formatPrice($iitemTax, $currency)
                    ),
                    'timestamps' => array(
                        'created_at' => $item->getCreatedAt(),
                        'updated_at' => $item->getUpdatedAt(),
                    ),
                    'product' => array(
                        'status' => $product->getStatus(),
                        'type' => $product->getTypeId(),
                        'path' => $product->getUrlPath(),
                        'image' => $product->getThumbnail(),
                        'description' => $product->getDescription(),
                    )
                )
            );
        }

        if ($shippingWithTax && $shippingMethod) {
            $shippingTax = $order->getShippingTaxAmount();
            $shippingItem = array(
                'type' => 'custom_item',
                'id' => 'shipping--'.$order->getEntityId(),
                'product_id' => $order->getEntityId(),
                'name' => 'shipping--'.$order->getShippingDescription(),
                'sku' => $shippingMethod,
                'quantity' => 1,
                'refunded' => 0,
                'meta' => array(
                    'display_price' => array(
                        'with_tax' => $this->formatPrice($shippingWithTax, $currency),
                        'without_tax' => $this->formatPrice($shippingWithTax - $shippingTax, $currency),
                        'tax' => $this->formatPrice($shippingTax, $currency)
                    ),
                    'timestamps' => array(
                        'created_at' => $order->getCreatedAt(),
                        'updated_at' => $order->getUpdatedAt(),
                    )
                )
            );
            array_push($orderInfo['relationships']['items']['data'], $shippingItem);
        }

        $orderInfo['shipments'] = $this->getShipments($order);

        return $orderInfo;
    }

    public function getOrderDetailExtended($order)
    {
        // if the admin site has a custom URL, use it
        $urlModel = Mage::getModel('adminhtml/url')->setStore('admin');

        $currency = $order->getOrderCurrencyCode();

        $orderInfo = $this->getOrderDetailBasic($order);

        foreach($order->getPaymentsCollection() as $payment) {
            $transactionAmount = $payment->getAmountAuthorized() ?: $payment->getAmountOrdered();
            $gateway = $payment->getMethod();

            $lastTransId = $payment->getLastTransId();

            if (!$lastTransId && $gateway == 'authorizenet') {
                $additionalInformation = $payment->getAdditionalInformation();
                $authorizeCards = $additionalInformation['authorize_cards'];
                $authorizeCardKeys = array_keys($authorizeCards);
                $authorizeCard = $authorizeCards[$authorizeCardKeys[0]];
                $lastTransId = $authorizeCard['last_trans_id'];
            }

            if (null != $lastTransId) {
                $transaction = $payment->lookupTransaction($lastTransId, 'capture'); // TODO grab authorization as well
            }

            if (!empty($transaction)) {
                $transactionData = $transaction->getData();
            }

            $orderInfo['relationships']['transactions']['data'][] = array(
                // 'DATA' => $payment->getData(), // TEMP
                // 'METHODS' => get_class_methods($payment), // TEMP
                'id' => $transactionData['transaction_id'], //TODO validate this is the correct value
                'type' => $transactionData['txn_type'], //TODO is this only always payment?  or can this be refund?
                'reference' => $transactionData['txn_id'], //TODO validate this is the correct value
                'gateway' => $gateway, //TODO validate this is the correct value
                'status' => $payment->getCcCidStatus(), //TODO validate this is the correct value
                'meta' => array(
                    'display_price' => $this->formatPrice($transactionAmount, $currency),
                    'timestamps' => array(
                        'created_at' => $order->getCreatedAt(),
                        'updated_at' => $order->getUpdatedAt(),
                    )
                ),
                'relationships' => array(
                    'charges' => array(
                        'data' => []
                    )
                )
            );
        }
        
        // NOTE this is invoices
        // $orderInfo['invoices'] = array();
        // foreach($order->getInvoiceCollection() as $invoice) {
        //     $orderInfo['invoices'][] = $invoice->getData();
        // }
            
        // NOTE this is refunds
        // $orderInfo['creditmemos'] = array();
        // foreach($order->getCreditmemosCollection() as $creditmemo) {
        //     $orderInfo['creditmemos'][] = $creditmemo->getData();
        // }

        return $orderInfo;
    }

    public function getOrderNotes($order)
    {
        $notesInfo = array();

        foreach($order->getStatusHistoryCollection() as $note) {
            $noteInfo = array(
                'id' => $note->getEntityId(),
                'type' => 'order_note',
                'source' => 'magento',
                'status' => $note->getStatus() ?: '',
                'created_at' => $note->getCreatedAt(),
                'entity_name' => $note->getEntityName()
            );

            $comment = $note->getComment();
            if ($comment) {
                $noteInfo['messages'][] = $comment;
            }

            $notesInfo[] = $noteInfo;
        }

        return $notesInfo;
    }

    public function getCustomer($customer)
    {
        $customerId = $customer->getEntityId();
        $urlModel = Mage::getModel('adminhtml/url')->setStore('admin');
        $adminUrl = $urlModel->getUrl('adminhtml/zendesk/redirect', array('id' => $customerId, 'type' => 'customer'));

        $info = array(
            'id' => $customerId,
            'type' => 'customer',
            'url' => $adminUrl,
            'email' => $customer->getEmail(),
            'first_name' => $customer->getFirstname(),
            'last_name' => $customer->getLastname(),
            'created_at' => $customer->getCreatedAt(),
            'updated_at' => $customer->getUpdatedAt(),
            'addresses' => array()
        );

        foreach($customer->getAddressesCollection() as $address) {
            if ($address) {
                $info['addresses'][] = $this->formatAddress($address);
            }
        }

        return $info;
    }

    public function getFilteredOrders($customerFilters, $genericFilters)
    {
        // Get a list of all orders for the given email address
        // This is used to determine if a missing customer is a guest or if they really aren't a customer at all
        $orderCollection = Mage::getModel('sales/order')->getCollection();

        foreach($customerFilters as $customerKey => $customerValue) {
            $orderCollection->addFieldToFilter('customer_'.$customerKey, $customerValue);
        }

        foreach($genericFilters as $genericKey => $genericValue) {
            $orderCollection->addFieldToFilter($genericKey, $genericValue);
        }
        
        $orders = array();
        
        if($orderCollection->getSize()) {
            foreach($orderCollection as $order) {
                $orders[] = $this->getOrderDetailBasic($order);
            }
        }

        return $orders;
    }

    public function getFilteredOrdersByProduct($customerFilters, $productFilters)
    {
        $emailKey = 'email';
        $email = $customerFilters->$emailKey;

        $orderItemCollection = Mage::getResourceModel('sales/order_item_collection');

        foreach($productFilters as $key => $value) {
            $orderItemCollection->addAttributeToFilter($key, $value);
        }   
        $orderItemCollection->load();

        $ordersData = array();

        foreach($orderItemCollection as $orderItem) {
            $orderId = $orderItem->getOrderId();
            $ordersData[$orderId] = array(
                'email' => $orderItem->getOrder()->getCustomerEmail(),
                'order' => $orderItem->getOrder()
            );
        }

        if ($email) {
            $filteredOrdersData = array_filter(array_values($ordersData), function ($orderData) use ($email) {
                return ($orderData['email'] == $email);
            });
        } else {
            $filteredOrdersData = $ordersData;
        }

        $orders = array();

        foreach($filteredOrdersData as $filteredData) {
            $orders[] = $this->getOrderDetailBasic($filteredData['order']);
        }

        return $orders;
    }

    #endregion version 3.0 or later
}
 