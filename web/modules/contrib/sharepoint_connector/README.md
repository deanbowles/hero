# Sharepoint Connector Drupal Module

## Introduction
The **Sharepoint Connector** module facilitates content synchronization between Drupal websites and Sharepoint lists using the Microsoft Graph API. It offers a robust and seamless way to sync your content and user submitted data directly to Sharepoint lists.

## Prerequisites
Before using this module, users need to generate Microsoft Graph API keys in order to send data to SharePoint. Follow the instructions below to generate keys via the Azure portal using Office 365 credentials. More information can be found on the Microsoft website.

- https://learn.microsoft.com/en-us/graph/auth
- https://learn.microsoft.com/en-us/graph/use-the-api

To send data to SharePoint Online using Microsoft's Graph API, you first need to register an app in the Azure Active Directory (AAD) to obtain a Client ID (also known as the Application ID), Client Secret and Tenant ID. Please follow these steps:

1. Log in to the Azure portal.

   - Access the Azure portal by going to https://portal.azure.com. Log in using your Office 365 credentials.

2. Access Azure Active Directory.

   - Click on "Azure Active Directory" in the sidebar.

3. Register a new application.

   - Click on "App registrations", and then on "New registration".
   - Fill out the form.
     - "Name": Give your app a name.
     - "Supported account types": Choose according to your requirements.
     - "Redirect URI": It's not necessary for this case, but you might need it for other scenarios. It could be something like "https://localhost".
   - Click "Register" to create the app.

4. Obtain your Client (Application) ID.

Once your app is registered, you'll be taken to the app's overview page. Here, you can find your "Application (client) ID". This is the client ID that you'll use to authenticate with the Microsoft Graph API.

5. Generate a new client secret.

   - Click on "Certificates & secrets" in the sidebar.
   - Click on "New client secret", give it a description and select the duration. Click "Add".
   - Once it's created, you'll see the value of the client secret. Copy it somewhere safe, because you won't be able to see it again after leaving this page.

6. Get your Directory (Tenant) ID.

Go back to the app's overview page. Here, you can also find your "Directory (tenant) ID". This is the tenant ID that you'll use to authenticate with the Microsoft Graph API.

7. Set permissions for the Graph API.

   - Click on "API permissions" in the sidebar.
   - Click on "Add a permission", select "Microsoft Graph", and then choose the type of permissions your app requires (for SharePoint Online, "Sites.ReadWrite.All" under "Application permissions" should suffice).
   - Click "Add permissions" to save your changes.
   - Finally, grant admin consent for the permissions (if required by your organization).

>**[!]**
> Remember to keep the client ID, client secret, and tenant ID private, as they can be used to access your resources on Microsoft Graph API.

### Retrieve Sharepoint List URL(s)

1. Retrieve Generated Access Token:
```
POST
https://login.microsoftonline.com/<TenantID>/oauth2/token?api-version=1.0
[
  'form_params' => [
    'client_id' => '<ClientID>',
    'client_secret' => '<ClientSecret>',
    'resource' => 'https://graph.microsoft.com/',
    'grant_type' => 'client_credentials',
  ]
]
```

2. Using the Authorization Bearer / Access Token retrieved above:
```
GET https://graph.microsoft.com/v1.0/sites/<SharepointDomain>:<SharepointSite>
```
This will return an array of site information including the Site ID (key: id)

3. Using the Site ID retrieved above:
```
GET https://graph.microsoft.com/v1.0/sites/<SiteID>/lists
```
This will return an array of lists with their information including the List ID (key: id)

Valid List URL:
```https://graph.microsoft.com/v1.0/sites/<SiteID>/lists/<ListID>/items```

## Installation
1. Download the Sharepoint Connector module from the Drupal repository or install with Composer.
2. Enable the module from the Extend page

## Configuration

### API Credentials
Navigate to the module's settings page.

1. **Input Credentials Directly**: 
    - Enter `Client ID`, `Client Secret`, `Tenant ID`, and the `Base URL` for your Sharepoint list.
    
    > **[!]** To enhance security, instead of storing the API keys in the database, you can use environment variables in the `settings.php` file.
    
2. **Using Environment Variables**: 
    - Ensure that the relevant environment variables are set to defined variables in the `settings.php` file.
    - The module will automatically load values into the following variables:

```
$config['sharepoint_connector.settings']['client_id'] = getenv('CLIENT_ID_ENV_VAR');
$config['sharepoint_connector.settings']['client_secret'] = getenv('CLIENT_SECRET_ENV_VAR');
$config['sharepoint_connector.settings']['tenant_id'] = getenv('TENANT_ID_ENV_VAR');
$config['sharepoint_connector.settings']['base_url'] = getenv('BASE_URL_ENV_VAR');
```
### Content Type Synchronization

1. Navigate to the Sharepoint Connector settings page from the Extend or Configuration page.
2. Click on the second tab on the module's settings page `Content Types`. Here, you'll see a list of all content types available on your Drupal website.
3. Click the `Add` button corresponding to a content type to configure field mapping between Drupal and Sharepoint.
    - **If a configuration already exists for a content type**, the `Add` button will be replaced by an `Edit` button. Clicking the dropdown arrow on the right of the button will also present an option to `Delete` the settings for that content type.
4. This will navigate you to a page where all fields for that content type are listed with checkboxes next to them.
5. Checking a box reveals a text field. Here, input the corresponding Sharepoint list column name where you want the Drupal field data to be sent.
6. Each content type can have a unique Sharepoint list URL, different from the default one set in the main settings page. This provides granular control over where each content type's data is sent.
7. After mapping all desired fields, save the settings.

### Webform Synchronization (for sites with the Webform module installed)

1. Navigate to the Sharepoint Connector settings page from the Extend or Configuration page.
2. Click on the second tab on the module's settings page `Webforms`. Here, you'll see a list of all webforms available on your Drupal website.
3. Click the `Add` button corresponding to a webform to configure field mapping between the webform's fields and Sharepoint.
    - **If a configuration already exists for a webform**, the `Add` button will be replaced by an `Edit` button. Clicking the dropdown arrow on the right of the button will also present an option to `Delete` the settings for that webform.
4. This will navigate you to a page where all fields for that webform are listed with checkboxes next to them.
5. Checking a box reveals a text field. Here, input the corresponding Sharepoint list column name where you want the webform field data to be sent.
6. Each webform can have its unique Sharepoint list URL, different from the default one set in the main settings page. This provides granular control over where each webform's submission data is sent.
7. After mapping all desired fields, save the settings.

## Usage

Once the module settings are configured and the fields are mapped and enabled data will automatically be sent to the configured SharePoint list(s) via the Microsoft Graph API:

- Whenever a content item of an enabled type is saved

- Whenever a submission of an enabled webform is saved
## Purpose

The Sharepoint Connector module provides a smooth and intuitive interface to bridge your Drupal website and Sharepoint lists. With field-level granularity, per-content type and per-webform configurations, it offers flexibility to manage data synchronization effectively.
<br/><br/>
>[!] **Remember to keep your API credentials secure, and ensure all mappings are correctly set to avoid data discrepancies.**
