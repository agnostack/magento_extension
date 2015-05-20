# Zendesk Extension for Magento

This extension makes Zendesk work seamlessly with Magento to enable stores to deliver great customer support. **Features include:**

- Enable Single Sign-on with Zendesk
- Create support tickets without leaving Magento
- Display relevant support tickets on order & customer dashboards
- Create support tickets from Contact Us requests
- Easily add the [Embeddables](https://www.zendesk.com/embeddables) Web Widget to your site

The latest stable version of the extension can be installed via the [Magento Connect](http://www.magentocommerce.com/magento-connect/catalog/product/view/id/15129/) marketplace.

## API

### General Notes

The extension provides its own custom RESTful API, which is intended to be used by the [Magento Zendesk App](https://github.com/zendesk/magento_app). The custom API allows for a consistent interface across all Magento versions, regardless of whether they support XML-RPC, SOAP or REST interfaces, and provides exactly the data that the app requires.

The base URL of the API is `http://your_site_base_url/zendesk/api/`.

### Authentication

The API can be enabled in the Zendesk settings page in the Magento admin panel. Using it requires a token to be generated and be sent to the API in an `Authorization` header:

    Authorization: Token token="your token goes here"

### Single Sign-on (SSO)

  **Remote login URL** http://your_site_base_url/zendesk/sso/login
  **Remote logout URL** http://your_site_base_url/zendesk/sso/logout

### Responses

You may receive the following errors from any of the API calls:

* 401 Not authorised

  Authentication token is not valid.

* 403 API access disabled

  API access has been disabled in the Magento admin panel. It will need to be enabled before using the API.

* 404 Resource does not exist

  The requested resource was not found.

All API methods return content as JSON objects following the format set out in the method definitions below.

### Available Methods

#### GET /customers/`<email>`

Will return customer information for the customer with the provided email address. If no customer record exists but orders have been placed then the email is treated as a guest and only the orders they have placed are returned. If no customer record exists and there are no orders using the email then a 404 error is returned.

Note that Magento allows scoping customers either globally or per website. If set to be scoped per website then this method will return the first customer which matches the email address, regardless of the website they belong to.

**Response Format**

Guest customers only have the `guest` and `orders` keys returned.

<table>
    <thead>
        <tr>
            <th>Name</th>
            <th>Type</th>
            <th>Comment</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>guest</td>
            <td>boolean</td>
            <td>Whether the customer is a guest (no customer record) or has a full customer record</td>
        </tr>
        <tr>
            <td>id</td>
            <td>integer</td>
            <td>Internal Magento ID for the customer</td>
        </tr>
        <tr>
            <td>name</td>
            <td>string</td>
            <td>Customer's full name</td>
        </tr>
        <tr>
            <td>email</td>
            <td>string</td>
            <td>Customer's email address</td>
        </tr>
        <tr>
            <td>active</td>
            <td>boolean</td>
            <td>Whether the customer is marked as active in Magento</td>
        </tr>
        <tr>
            <td>admin_url</td>
            <td>string</td>
            <td>URL to access the customer detail in the Magento admin panel</td>
        </tr>
        <tr>
            <td>created</td>
            <td>string</td>
            <td>Date and time the customer record was created</td>
        </tr>
        <tr>
            <td>dob</td>
            <td>string</td>
            <td>Date of birth</td>
        </tr>
        <tr>
            <td>addresses</td>
            <td>array</td>
            <td>List of addresses recorded on the customer account</td>
        </tr>
        <tr>
            <td>orders</td>
            <td>array</td>
            <td>List of orders placed by the customer (see the `orders` method for details)</td>
        </tr>
    </tbody>
</table>

#### GET /orders/`<order_increment_id>`

Will return details of an individual order based on the Magento order increment ID, which usually takes the form 100000321.

**Response Format**

<table>
    <thead>
        <tr>
            <th>Name</th>
            <th>Type</th>
            <th>Comment</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>id</td>
            <td>string</td>
            <td>The order ID displayed to the customer</td>
        </tr>
        <tr>
            <td>status</td>
            <td>string</td>
            <td>Current order status (e.g. Pending, Processing, Complete)</td>
        </tr>
        <tr>
            <td>created</td>
            <td>string</td>
            <td>Date and time the order was created</td>
        </tr>
        <tr>
            <td>updated</td>
            <td>string</td>
            <td>Date and time the order was last updated</td>
        </tr>
        <tr>
            <td>customer</td>
            <td>object</td>
            <td>Has the keys:
                <dl>
                    <dt>name</dt>
                    <dd>Customer's name</dd>
                    <dt>email</dt>
                    <dd>Customer's email address</dd>
                    <dt>ip</dt>
                    <dd>IP address the order was placed from</dd>
                    <dt>guest</dt>
                    <dd>Whether the customer placed the order as a guest</dd>
                </dl>
            </td>
        </tr>
        <tr>
            <td>store</td>
            <td>string</td>
            <td>Magento store that the order was placed in</td>
        </tr>
        <tr>
            <td>total</td>
            <td>string</td>
            <td>Total value of the order</td>
        </tr>
        <tr>
            <td>currency</td>
            <td>string</td>
            <td>Currency code (e.g. AUD, USD)</td>
        </tr>
        <tr>
            <td>items</td>
            <td>array</td>
            <td>List of items on the order; each item has the keys:
                <dl>
                    <dt>sku</dt>
                    <dd>Product's unique SKU</dd>
                    <dt>name</dt>
                    <dd>Product name</dd>
                </dl>
            </td>
        </tr>
        <tr>
            <td>admin_url</td>
            <td>string</td>
            <td>URL to access the order in the Magento admin panel</td>
        </tr>
    </tbody>
</table>

#### GET /users/`<user_id>`

Will return either a single Magento admin user, or a list of users if the `user_id` argument is left out. Admin user accounts have access to the admin panel and are different to customer accounts.

**Parameters**

<table>
    <thead>
        <tr>
            <th>Argument</th>
            <th>Default</th>
            <th>Comment</th>
        <tr>
    </thead>
    <tbody>
        <tr>
            <td>page_size</td>
            <td>100</td>
            <td>Number of results to be returned</td>
        </tr>
        <tr>
            <td>offset</td>
            <td>0</td>
            <td>Page number to return, based on `page_size`</td>
        </tr>
        <tr>
            <td>sort</td>
            <td>given_name</td>
            <td>Attribute to sort by</td>
        </tr>
    </tbody>
</table>

**Response Format**

<table>
    <thead>
        <tr>
            <th>Name</th>
            <th>Type</th>
            <th>Comment</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>id</td>
            <td>string</td>
            <td>Internal ID for the user</td>
        </tr>
        <tr>
            <td>given_name</td>
            <td>string</td>
            <td>User's first name</td>
        </tr>
        <tr>
            <td>family_name</td>
            <td>string</td>
            <td>User's surname</td>
        </tr>
        <tr>
            <td>username</td>
            <td>string</td>
            <td>Username used to log in to the Magento admin panel</td>
        </tr>
        <tr>
            <td>email</td>
            <td>string</td>
            <td>User's email address</td>
        </tr>
        <tr>
            <td>active</td>
            <td>boolean</td>
            <td>Whether the user is enabled and can log in</td>
        </tr>
        <tr>
            <td>role</td>
            <td>string</td>
            <td>User's role; used for ACLs in Magento</td>
        </tr>
    </tbody>
</table>

## Local Development

### Resetting the extension

During development you may wish to clear out the configuration for the Magento extension. All settings are stored in the `core_config_data` table of the Magento database and can be removed with an SQL query:

    DELETE FROM `core_config_data` WHERE `path` LIKE 'zendesk/%';

## Contribution

Improvements to the extension are always welcome. To contribute, please submit detailed Pull Requests.

## Bugs

Please submit bug reports to <a href="https://support.zendesk.com/requests/new">Zendesk</a>.

## Copyright and License

Copyright 2012-present, Zendesk Inc. Licensed under the <a href="http://www.apache.org/licenses/LICENSE-2.0">Apache License Version 2.0</a>.
