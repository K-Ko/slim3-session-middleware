<?php

namespace Session;

use ArrayAccess;
use Exception;

class Helper implements ArrayAccess
{
    public function __get(string $key)
    {
        return $this->get($key);
    }

    public function __isset(string $key): bool
    {
        return $this->has($key);
    }

    public function __set(string $key, $value)
    {
        $this->set($key, $value);
    }

    public function __unset(string $key)
    {
        $this->remove($key);
    }

    public function regenerate(bool $delete_old_session = false): bool
    {
        return session_regenerate_id($delete_old_session);
    }

    /**
     * Destroys all data registered to a session.
     */
    public function destroy(): bool
    {
        // Unset all of the session variables.
        $_SESSION = [];

        // If it's desired to kill the session, also delete the session cookie.
        // Note: This will destroy the session, and not just the session data!
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 86400, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }

        // Finally, destroy the session.
        return session_destroy();
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $_SESSION ?? []);
    }

    public function get(string $key, $default = null)
    {
        return $this->has($key) ? $_SESSION[$key] : $default;
    }

    /**
     * Take a value from session
     */
    public function take(string $key, $default = null)
    {
        $value = $this->get($key, $default);

        $this->remove($key);

        return $value;
    }

    public function remove(string $key): bool
    {
        if ($this->has($key)) {
            unset($_SESSION[$key]);

            return true;
        }

        return false;
    }

    public function set(string $key, $value): Helper
    {
        $_SESSION[$key] = $value;

        return $this;
    }

    /**
     * Increment a value
     */
    public function inc(string $key, int $step = 1): int
    {
        $_SESSION[$key] = (int) ($_SESSION[$key] ?? 0) + $step;

        return $_SESSION[$key];
    }

    /**
     * Decrement a value
     */
    public function dec(string $key, int $step = 1): int
    {
        $_SESSION[$key] = (int) ($_SESSION[$key] ?? 0) - $step;

        return $_SESSION[$key];
    }

    /*
     * ArrayAccess
     */

    public function offsetSet($offset, $value): void
    {
        if (is_null($offset)) {
            throw new Exception('Missing key', 1);
        }

        $this->set($offset, $value);
    }

    public function offsetExists($offset): bool
    {
        return $this->has($offset);
    }

    public function offsetUnset($offset): void
    {
        $this->remove($offset);
    }

    public function offsetGet($offset)
    {
        return $this->get($offset);
    }
}
