# ANNOUNCING VERSION 3.0 :mega:

The new 3.0 version fixes several core issues (incorrect/missing order address, sporadic missing customer data, lack of order item quantities, etc.) and provides all new v2 endpoints, with the ability to retrieve additional information about customers and orders within your Magento store.

* Perform advanced Customer Search
* View real-time Shipping Status
* Access detailed Payment Status
* Enable direct Order Search by ID
* Access Order Messages and Notes


# Zendesk Extension for Magento

This extension makes Zendesk work seamlessly with Magento to enable stores to deliver great customer support. **Features include:**

- Enable Single Sign-on with Zendesk
- Create support tickets without leaving Magento
- Display relevant support tickets on order & customer dashboards
- Create support tickets from Contact Us requests
- Easily add the [Embeddables](https://www.zendesk.com/embeddables) Web Widget to your site

## API

### General Notes

The extension provides its own custom RESTful API, which is intended to be used by the [agnoStack app](https://www.zendesk.com/apps/support/agnostack-commerce---by-particular/). The custom API allows for a consistent interface across all Magento versions, regardless of whether they support XML-RPC, SOAP or REST interfaces, and provides exactly the data that the app requires.

The base URL of the API is `http://your_site_base_url/zendesk/api/`.

### Authentication

The API can be enabled in the Zendesk settings page in the Magento admin panel. Using it requires a token to be generated and be sent to the API in an `Authorization` header:

    Authorization: Token token="your token goes here"

### Single Sign-on (SSO)

The extension only accepts **one** type of SSO implementation, either for administrators/agents or end-users. Verify that you have selected the correct user group to enable SSO, on your Zendesk account go to **Settings** > **Security** and select Single sign-on for which users to enable SSO via Magento.

#### Administrators and agents
* **Remote login URL**: http://your_site_base_url/admin/zendesk/authenticate
* **Remote logout URL**: http://your_site_base_url/admin/zendesk/logout

#### End-users sign-in
* **Remote login URL**: http://your_site_base_url/zendesk/sso/login
* **Remote logout URL**: http://your_site_base_url/zendesk/sso/logout

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

#### GET /orders/`<order_increment_id>`

Will return details of an individual order based on the Magento order increment ID, which usually takes the form 100000321.


#### GET /users/`<user_id>`

Will return either a single Magento admin user, or a list of users if the `user_id` argument is left out. Admin user accounts have access to the admin panel and are different to customer accounts.

### Additional Available Methods (v3 and later)

#### GET /customer/`<customer_id>`

Will return customer information for the customer with the provided customer ID. If no customer record exists, this will return `null`. 

#### POST /searchCustomers

Will return customer information for the customers with the provided customer atrribute payload.

Example payload:

```
{
    "customer": {
        "email": "janedoe@example.com",
        "firstname": "Jane",
        "lastname": "Doe"
    }
}
```

#### POST /searchOrders

Will return order details for orders which match the provided order attribute payload.

Example payloads:

```
// Lookup by customer and product
{
    "customer": {
        "email": "janedoe@example.com"
    },
    "product": {
        "sku": "123"
    }
}

// Lookup by status
{
    "status": "complete"
}
```

#### GET /order/`<order_id>`

Will return details of an individual order based on the Magento order increment ID, which usually takes the form 100000321.  This endpoint will return enhanced data on top of what is prodived by the v2.x endpoint.

#### GET /shipping/`<order_id>`

Will return shipping related details of an individual order based on the Magento order increment ID, which usually takes the form 100000321.

#### GET /notes/`<order_id>`

Will return status history details of an individual order based on the Magento order increment ID, which usually takes the form 100000321.

## Local Development

### Resetting the extension

During development you may wish to clear out the configuration for the Magento extension. All settings are stored in the `core_config_data` table of the Magento database and can be removed with an SQL query:

    DELETE FROM `core_config_data` WHERE `path` LIKE 'zendesk/%';

## Contribution

Improvements to the extension are always welcome. To contribute, please submit detailed Pull Requests. To share any Issues or potential Bugs, please submit an Issue.

## Copyright and License

Copyright 2012-present, Zendesk Inc. + Copyright 2020-present, agnoStack, Inc. Licensed under the <a href="http://www.apache.org/licenses/LICENSE-2.0">Apache License Version 2.0</a>.
