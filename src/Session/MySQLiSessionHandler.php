<?php

/**
 * Based on
 * https://www.php.net/manual/en/function.session-set-save-handler.php
 *
 * Usage:
 * $handler = new \Session\MySQLiSessionHandler($mysqli);
 */

namespace Session;

use SessionHandlerInterface;
use SessionIdInterface;
use SessionUpdateTimestampHandlerInterface;
use mysqli;

final class MySQLiSessionHandler implements
    SessionIdInterface,
    SessionHandlerInterface,
    SessionUpdateTimestampHandlerInterface
{
    /** @var string $table Database table */
    public static $table = 'sessions';

    /**
     * Class constructor
     *
     * @param mysqli $db
     * @param bool   $closeDb Close DB here, set to false if it will be closed otherwise
     */
    public function __construct(mysqli $db, bool $closeDb = true)
    {
        $this->db = $db;
        $this->closeDb = $closeDb;

        // Set handler
        session_set_save_handler(
            [$this, 'open'],
            [$this, 'close'],
            [$this, 'read'],
            [$this, 'write'],
            [$this, 'destroy'],
            [$this, 'gc'],
            [$this, 'create_sid'],
            [$this, 'validateId'],
            [$this, 'updateTimestamp']
        );

        register_shutdown_function('session_write_close');

        ini_set('session.gc_probability', 1);
        ini_set('session.gc_divisor', 1);
        ini_set('session.gc_maxlifetime', 60 * 60); # 1 hour
    }

    public function __destruct()
    {
        $this->close();
    }

    public function open($path, $name): bool
    {
        // Create table if not exists
        $this->query(static::$createSQL, static::$table);
        return true;
    }

    public function read($id): string
    {
        // ini_get('session.gc_maxlifetime');
        return $this->queryOne('`data`', $id) ?: '';
    }

    public function write($id, $data): bool
    {
        return (bool) $this->query(
            'INSERT INTO `%s` VALUES ("%s", CURRENT_TIMESTAMP(), "%s")
                 ON DUPLICATE KEY UPDATE `last` = CURRENT_TIMESTAMP(), `data` = VALUES(`data`)',
            static::$table,
            $id,
            $this->db->real_escape_string($data)
        );
    }

    public function destroy($id): bool
    {
        return (bool) $this->query('DELETE FROM `%s` WHERE `id` = "%s" LIMIT 1', static::$table, $id);
    }

    public function close(): bool
    {
        return $this->closeDb ? $this->db->close() : true;
    }

    public function gc($max_lifetime): bool
    {
        return (bool) $this->query('DELETE FROM `%s` WHERE `last` < %d', static::$table, time() - $max_lifetime);
    }

    public function create_sid(): string
    {
        do {
            // Make sure, sid does not exists yet
            if (!$this->validateId($sid = sha1(uniqid(__FILE__, true)))) {
                return $sid;
            }
        } while (true);
    }

    public function validateId($id): bool
    {
        return 0 < (int) $this->queryOne('COUNT(1)', $id);
    }

    public function updateTimestamp($id, $data): bool
    {
        return (bool) $this->query(
            'UPDATE `%s` SET `last` = CURRENT_TIMESTAMP() WHERE `id` = "%s" LIMIT 1',
            static::$table,
            $id
        );
    }

    // ----------------------------------------------------------------------
    // PRIVATE
    // ----------------------------------------------------------------------

    /**
     * Statement to create session table
     */
    private static $createSQL = '
        CREATE TABLE IF NOT EXISTS `%s` (
            `id` char(40) NOT NULL PRIMARY KEY,
            `last` timestamp NULL DEFAULT NULL,
            `data` text DEFAULT NULL,
            KEY `last` (`last`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ';

    /** @var \mysqli */
    private $db;

    /** @var bool */
    private $closeDb;

    /**
     * Real DB query
     */
    private function query(string $query, ...$args)
    {
        return $this->db->query(vsprintf($query, $args));
    }

    /**
     * Query $what (single colunm only) for given session $id
     *
     * @return string|null
     */
    private function queryOne(string $what, string $id)
    {
        $query = sprintf('SELECT %s FROM `%s` WHERE `id` = "%s" LIMIT 1', $what, static::$table, $id);

        if (($res = $this->db->query($query)) && ($row = $res->fetch_row())) {
            return $row[0];
        }
    }
}
