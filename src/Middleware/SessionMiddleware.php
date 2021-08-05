<?php

namespace Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class SessionMiddleware
 */
class SessionMiddleware
{
    /**
     * @param array $settings
     */
    public function __construct(array $settings = [])
    {
        // Parse the settings, update the middleware properties.
        foreach ($settings as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }

        // Parse the time to live value into a Unix timestamp if it is desined
        // as a string.
        if ($this->lifetime && is_string($ttl = $this->lifetime)) {
            $this->lifetime = strtotime($ttl) - time();
        }

        // Set the configuration options.
        $this->configure($this->ini_settings);

        // If the maxlifetime is less than the ttl, update the configuration of
        // the maxlifetime to bt the value of tll multiplied.
        if ($this->lifetime && ini_get('session.gc_maxlifetime') < $this->lifetime) {
            $this->configure([
                'session.gc_maxlifetime' => $this->lifetime * 2,
            ]);
        }
    }

    /**
     * @param Psr\Http\Message\ServerRequestInterface $request  PSR7 request
     * @param Psr\Http\Message\ResponseInterface      $response PSR7 response
     * @param callable                                $next     Next middleware
     *
     * @return Response
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        // Set the session cookie parameters.
        session_set_cookie_params($this->lifetime, $this->path, $this->domain, $this->secure, $this->httponly);

        // Refresh session cookie when sessions are enabled, but none exists.
        $sessionsEnabledNoneExists = PHP_SESSION_NONE === session_status();

        if ($sessionsEnabledNoneExists && $this->autorefresh && isset($_COOKIE[$this->name])) {
            setcookie(
                $this->name,
                $_COOKIE[$this->name],
                $this->lifetime ? time() + $this->lifetime : 0,
                $this->path,
                $this->domain,
                $this->secure,
                $this->httponly
            );
        }

        // Set the current session name.
        session_name($this->name);

        // Set the user-level session storage functions
        if ($this->handler) {
            if (!($this->handler instanceof \SessionHandlerInterface)) {
                $this->handler = new $this->handler();
            }
            session_set_save_handler($this->handler, true);
        }

        // Set the current cache limiter
        session_cache_limiter(false);

        // Start a session if sessions are enabled, but none exist.
        if ($sessionsEnabledNoneExists) {
            session_start();
            $this->autorefresh && session_regenerate_id(true);
        }

        return $next($request, $response);
    }

    // ----------------------------------------------------------------------
    // PROTECTED
    // ----------------------------------------------------------------------

    /**
     * @var bool
     */
    protected $autorefresh = false;

    /**
     * @var mixed
     */
    protected $domain = null;

    /**
     * @var mixed
     */
    protected $handler = null;

    /**
     * @var bool
     */
    protected $httponly = false;

    /**
     * @var array
     */
    protected $ini_settings = [];

    /**
     * @var string
     */
    protected $name = 'session';

    /**
     * @var string
     */
    protected $path = '/';

    /**
     * @var bool
     */
    protected $secure = false;

    /**
     * 0 = Session only
     *
     * @var mixed
     */
    protected $lifetime = 0;

    // ----------------------------------------------------------------------
    // PRIVATE
    // ----------------------------------------------------------------------

    /**
     * @param array $settings
     *
     * @return $this
     */
    private function configure(array $settings)
    {
        foreach ($settings as $key => $value) {
            if (0 === strpos($key, 'session.')) {
                ini_set($key, $value);
            }
        }

        return $this;
    }
}
