# Zendesk Extension for Magento

This extension makes Zendesk work seamlessly with Magento to enable stores to deliver great customer support. **Features include:**

- Enable Single Sign-on with Zendesk
- Create support tickets without leaving Magento
- Display relevant support tickets on order & customer dashboards
- Create support tickets from Contact Us requests
- Easily add a feedback tab to your site

The latest stable version of the extension can be installed via the [Magento Connect](http://www.magentocommerce.com/magento-connect/catalog/product/view/id/15129/) marketplace.

## API

The extension provides its own custom RESTful API, which is intended to be used by the [Magento Zendesk App](https://github.com/zendesk/magento_app). The custom API allows for a consistent interface across all Magento versions, regardless of whether they support XML-RPC, SOAP or REST interfaces, and provides exactly the data that the app requires.

The API can be enabled in the Zendesk settings page in the Magento admin panel. Using it requires a token to be generated and be sent to the API in an `Authorization` header:

    Authorization: Token token="your token goes here"

The base URL of the API is `http://your_site_base_url/zendesk/api/`.

## Local Development

### Resetting the extension

During development you may wish to clear out the configuration for the Magento extension. All settings are stored in the `core_config_data` table of the Magento database and can be removed with an SQL query:

    DELETE FROM `core_config_data` WHERE `path` LIKE 'zendesk/%';

## Contribution

Improvements to the extension are always welcome. To contribute, please submit detailed Pull Requests.

## Bugs

Please submit bug reports to <a href="https://support.zendesk.com/requests/new">Zendesk</a>.

## Copyright and License

Copyright 2012, Zendesk Inc. Licensed under the <a href="http://www.apache.org/licenses/LICENSE-2.0">Apache License Version 2.0</a>.