# SimpleSaml plugin for CakePHP

## Installation

Until [this issue](https://github.com/simplesamlphp/simplesamlphp/issues/1273) is resolved, SimpleSAML is incompatible
with CakePHP's Bake package, so cakephp/bake must be removed before installing.
```
composer remove cakephp/bake
```
Then:
```
composer require group-name-pending/cakephp-simple-saml
```

## Add an authorization policy
- Add [an authorization policy class](https://book.cakephp.org/authorization/2/en/policies.html) under `/src/Policy`.
  ([example policy](https://github.com/BallStateCBER/datacenter-plugin-cakephp4/blob/master/src/Policy/RequestPolicy.php))

## Update `Application.php`
- Have the `Application` class implement `AuthorizationServiceProviderInterface`
- Add these lines to `Application::bootstrap()`:
    ```php
    $this->addPlugin('Authentication');
    $this->addPlugin('SimpleSaml');
    ```
- Add `getAuthorizationService()` and `getAuthenticationService()` methods, using the name of your policy class:
    ```php
    /**
     * Returns the authorization service
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request Server request
     * @return \Authorization\AuthorizationServiceInterface
     */
    public function getAuthorizationService(ServerRequestInterface $request): AuthorizationServiceInterface
    {
        $mapResolver = new MapResolver();
        $mapResolver->map(ServerRequest::class, YourPolicyClass::class);

        return new AuthorizationService($mapResolver);
    }

    /**
     * Returns a service provider instance.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request Request
     * @return \Authentication\AuthenticationServiceInterface
     */
    public function getAuthenticationService(ServerRequestInterface $request): AuthenticationServiceInterface
    {
        $service = new AuthenticationService();
        $loginUrl = '/login';

        // Define where users should be redirected to when they are not authenticated
        $service->setConfig([
            'unauthenticatedRedirect' => $loginUrl,
            'queryParam' => 'redirect',
        ]);

        $service->loadAuthenticator('Authentication.Session');
        $service->loadAuthenticator('SimpleSaml.SimpleSaml');

        // Load identifiers
        $service->loadIdentifier('Authentication.Token', [
            // The field in the database to check against
            'tokenField' => '',

            // The field in the passed data from the authenticator
            'dataField' => '',

            /* The OrmResolver will search the Users table for a record with a tokenField with the same value as
             * dataField */
            'resolver' => [
                'className' => OrmResolver::class,
                'userModel' => 'Users',
                'finder' => 'all',
            ]
        ]);

        return $service;
    }
    ```

## Update `AppController.php`
In `AppController::initialize()`, load the `SimpleSamlComponent` from the plugin:
```php
$this->loadComponent('SimpleSaml.SimpleSamlComponent', [
    //'authSource' => 'default-sp'
]);
```
Uncomment and change the value of `authSource` if needed.

## Update User model
Have `User` entity class
[implement `IdentityInterface`](https://book.cakephp.org/authentication/2/en/identity-object.html#implementing-the-identityinterface-on-your-user-class)
and add the `getIdentifier()` and `getOriginalData()` methods to it. ([example](https://github.com/BallStateCBER/datacenter-plugin-cakephp4/blob/master/src/Model/Entity/User.php))

## Get SimpleSAML's /www directory ready for being accessed
1. Set up a VirtualHost alias (or its equivalent in non-Apache servers) or a symlink for
   `/vendor/simplesamlphp/simplesamlphp/www`, named something like `/simplesaml`
2. Navigate to `/vendor/simplesamlphp/simplesamlphp` in the command line and run these two commands to download
   front-end dependencies and set up CSS and JS **(this assumes that NodeJS is installed on the server)**.
   ```
   npm install
   npm run build
   ```

# Configuration
1. Copy the SimpleSAML `/config-templates` directory to `/config/simplesaml` at the root of the project
2. Set the `SIMPLESAMLPHP_CONFIG_DIR` environment variable to the path to this new directory so SimpleSAML can access
   these config files.
   - If you're doing this via PHP, you would use `putenv('SIMPLESAMLPHP_CONFIG_DIR=' . CONFIG . 'simplesaml');`
   - Do not include a trailing slash in the path string
   - This can be placed in `/config/bootstrap.php`
3. Set `baseurlpath` value to the full URL path to access SimpleSAML's www directory, e.g.
   `'baseurlpath' => 'https://example.com/simplesaml-alias-name/'`
4. If SimpleSAML's metadata files need to be edited
   1. Copy the library's `/metadata-templates` directory to `/config/simplesaml/metadata` from the project's root
   2. Update the metadatadir value in `/config/simplesaml/config.php`
   ```php
   'metadatadir' => CONFIG . 'simplesaml' . DS . 'metadata'',
   ```

# Run checks
Open the SimpleSAML web-accessible directory in a browser to confirm that it’s installed and configured correctly.

# Using the component
All controllers should now have access to the `SimpleSaml` component, which provides these methods:
- `$this->SimpleSaml->isAuthenticated();` - Returns true if the user is logged in via SimpleSaml
- `$this->SimpleSaml->login($params);` - Starts the authentication process (`$params` is documented at
  `\SimpleSAML\Auth\Simple::login()`)
- `$this->SimpleSaml->logout();` - Logs the user out
- `$this->SimpleSaml->getUserAttributes();` - Returns the authenticated user's attributes from the SimpleSaml session, or an empty array if no user is authenticated
