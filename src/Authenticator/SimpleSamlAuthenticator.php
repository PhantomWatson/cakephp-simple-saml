<?php
declare(strict_types=1);

namespace SimpleSaml\Authenticator;

use Authentication\Authenticator\AbstractAuthenticator;
use Authentication\Authenticator\Result;
use Authentication\Authenticator\ResultInterface;
use Authentication\Identifier\IdentifierInterface;
use Psr\Http\Message\ServerRequestInterface;
use SimpleSAML\Auth\Simple;

/**
 * Class SimpleSamlAuthenticator
 * @package SimpleSaml\Authenticator
 * @property \SimpleSAML\Auth\Simple $AuthSource
 */
class SimpleSamlAuthenticator extends AbstractAuthenticator
{
    private $AuthSource;

    /**
     * Constructor
     *
     * @param \Authentication\Identifier\IdentifierInterface $identifier Identifier or identifiers collection.
     * @param array $config Configuration settings.
     */
    public function __construct(IdentifierInterface $identifier, array $config = [])
    {
        parent::__construct($identifier, $config);
        $authSource = $config['authSource'] ?? 'default-sp';
        $this->AuthSource = new Simple($authSource);
    }

    /**
     * Returns a result if the user is already authenticated and begins the authentication process otherwise
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @return \Authentication\Authenticator\ResultInterface
     */
    public function authenticate(ServerRequestInterface $request): ResultInterface
    {
        if (!$this->AuthSource->isAuthenticated()) {
            // Note that this method redirects the current request upon completion instead of returning anything
            $this->AuthSource->login();
        }

        if ($this->AuthSource->isAuthenticated()) {
            $user = $this->AuthSource->getAttributes();

            return new Result($user, Result::SUCCESS);
        }

        /* This line should theoretically never be reached, since unauthenticated users with login failures get
         * redirected to an error page */
        return new Result(null, Result::FAILURE_OTHER);
    }
}
