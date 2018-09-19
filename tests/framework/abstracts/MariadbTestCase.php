<?php

/* Copyright 2018 Sony Computer Science Laboratories, Inc. */

use PHPUnit\Framework\TestCase;

abstract class MariadbTestCase extends TestCase
{
    private static $connector = null;

    /**
     * @doesNotPerformAssertions
     */
    public static function getTestConnector(): \mysqli
    {
        if (self::$connector === null) {
            self::$connector = new \mysqli(
                $GLOBALS['MARIADB_HOST'],
                $GLOBALS['MARIADB_USER'],
                $GLOBALS['MARIADB_PASSWD'],
                $GLOBALS['MARIADB_DBNAME'],
                $GLOBALS['MARIADB_PORT']
            );
        }

        return self::$connector;
    }

    /**
     * @doesNotPerformAssertions
     */
    public function dbTestQuery(string $sql)
    {
        return self::getTestConnector()->query($sql);
    }

    /**
     * @doesNotPerformAssertions
     */
    public function dbTestInsert(string $table_name, array $values): bool
    {
        for ($i = 0; $i < count($values); $i++) {
            if (gettype($values[$i]) === 'string') {
                $values[$i] = "'".$values[$i]."'";
            }
        }
        return $this->dbTestQuery('INSERT INTO '.$table_name.' VALUES('.implode(', ', $values).')');
    }

    /**
     * @doesNotPerformAssertions
     */
    public function dbTestSelect(string $table_name, int $row = 0)
    {
        $q = $this->dbTestQuery('SELECT * FROM '.$table_name);
        for ($i = 0; $i < $row; $i++) {
            $r = $q->fetch_row();
        }
        return $q->fetch_row();
    }

    /**
     * @doesNotPerformAssertions
     */
    public function tableCleanUp(string $table_name)
    {
        self::getTestConnector()->query('SET FOREIGN_KEY_CHECKS = 0');
        self::getTestConnector()->query('TRUNCATE TABLE '.$table_name);
        self::getTestConnector()->query('SET FOREIGN_KEY_CHECKS = 1');
    }
}
