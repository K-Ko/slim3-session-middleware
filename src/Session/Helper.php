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
    /**
     * @param string $key
     *
     * @return mixed
     */
    public function __get(string $key)
    {
        return $this->get($key);
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function __isset(string $key)
    {
        return $this->has($key);
    }

    /**
     * @param string $key
     * @param mixed  $value
     *
     * @return mixed
     */
    public function __set(string $key, $value)
    {
        return $this->set($key, $value);
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function __unset(string $key): bool
    {
        return $this->remove($key);
    }

    /**
     * @param string $key
     *
     * @return bool
     */
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

    /**
     * @param string $key
     *
     * @return bool
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $_SESSION);
    }

    /**
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        if ($this->has($key)) {
            return $_SESSION[$key];
        }

        return $default;
    }

    /**
     * Take a value from session
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public function take(string $key, $default = null)
    {
        $result = $this->get($key, $default);

        $this->remove($key);

        return $result;
    }

    /**
     * @param string $key
     * @param mixed  $value
     *
     * @return Session\Helper For fluid interface
     */
    public function set(string $key, $value): Helper
    {
        $_SESSION[$key] = $value;

        return $this;
    }

    /**
     * ArrayAccess
     */
    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            throw new Exception('Missing key', 1);
        }

        $this->set($offset, $value);
    }

    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    public function offsetUnset($offset)
    {
        $this->remove($offset);
    }

    public function offsetGet($offset)
    {
        return $this->get($offset);
    }
}
