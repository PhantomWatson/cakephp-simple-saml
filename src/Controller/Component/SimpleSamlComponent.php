<?php
declare(strict_types=1);

namespace SimpleSaml\Controller\Component;

use Cake\Controller\Component;
use SimpleSAML\Auth\Simple;

/**
 * Class SimpleSamlComponent
 * @package SimpleSaml\Controller\Component
 * @property \SimpleSAML\Auth\Simple $AuthSource
 */
class SimpleSamlComponent extends Component
{
    private $AuthSource;

    /**
     * Initialize callback
     *
     * @param array $config Configuration settings
     */
    public function initialize(array $config = []): void
    {
        parent::initialize($config);
        $authSource = $config['authSource'] ?? 'default-sp';
        $this->AuthSource = new Simple($authSource);
    }

    /**
     * Returns TRUE if the user is authenticated
     *
     * @return bool
     */
    public function isAuthenticated() {
        return $this->AuthSource->isAuthenticated();
    }

    /**
     * Starts the authentication process.
     *
     * @param mixed $params Params to pass to SimpleSamlPHP.
     * @link https://simplesamlphp.org/docs/stable/simplesamlphp-sp-api#section_4
     * @return void
     */
    public function login($params = []) {
        $this->AuthSource->login($params);
    }

    /**
     * Logs the user out.
     *
     * @param mixed $params Params to pass to SimpleSamlPHP.
     * @link https://simplesamlphp.org/docs/stable/simplesamlphp-sp-api#section_5
     * @return void
     */
    public function logout($params = []) {
        $this->AuthSource->logout($params);
    }

    /**
     * Retrieve the attributes of the current user. If the user isn't authenticated, an empty array will be returned.
     *
     * @return array
     */
    public function getUserAttributes() {
        return $this->AuthSource->getAttributes();
    }
}
