<?php

/**
 *
 */

namespace Session;

/**
 *
 */

use ArrayAccess;
use Exception;

/**
 * Class Helper.
 */
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

    public function remove(string $key): bool
    {
        if ($this->has($key)) {
            unset($_SESSION[$key]);

            return true;
        }

        return false;
    }

    /**
     * Destroys all data registered to a session.
     */
    public function destroy()
    {
        session_destroy();
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $_SESSION);
    }

    public function get(string $key, $default = null)
    {
        if ($this->has($key)) {
            return $_SESSION[$key];
        }

        return $default;
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
