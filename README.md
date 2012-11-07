Zendesk Magento Integration
===========================

## API Usage

The extension provides its own custom RESTful API, which is intended to be used by the Magento app in the Zendesk store. The custom API allows for a consistent interface across all Magento versions, regardless of whether they support XML-RPC, SOAP or REST interfaces, and provides exactly the data that the app requires.

The API can be enabled in the Zendesk settings page in the Magento admin panel. Using it requires a token to be generated and be sent to the API in an `Authorization` header like so:

    Authorization: Token token="your token goes here"

The base URL of the API is `http://your_site_base_url/zendesk/api/`.


## Local Development

### Resetting the extension

During development you may wish to clear out the configuration for the Magento extension. All settings are stored in the `core_config_data` table of the Magento database and can be removed with an SQL query:

    DELETE FROM `core_config_data` WHERE `path` LIKE 'zendesk/%';

### Using a different provisioning URL

To override the provisioning URL for development, you can set a new URL in your local.xml file (under app/etc). Simply add the following inside the main <config> tag:

    <zendesk>
        <provision_url>https://signup.localhost.com/provisioning/magento/welcome</provision_url>
    </zendesk>

If placed at the end of the file, this will result in something like this:

    <config>
        *snip*
        <admin>
            <routers>
                <adminhtml>
                    <args>
                        <frontName><![CDATA[admin]]></frontName>
                    </args>
                </adminhtml>
            </routers>
        </admin>
        <zendesk>
            <provision_url>https://signup.localhost.com/provisioning/magento/welcome</provision_url>
        </zendesk>
    </config>
