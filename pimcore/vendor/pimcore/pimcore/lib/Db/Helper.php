<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Db;

use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\Types\Type;
use Pimcore\Model\Element\ValidationException;

class Helper
{
    /**
     *
     * @param array<string, mixed> $data The data to be inserted or updated into the database table.
     * Array key corresponds to the database column, array value to the actual value.
     * @param string[] $keys If the table needs to be updated, the columns listed in this parameter will be used as criteria/condition for the where clause.
     * Typically, these are the primary key columns.
     * The values for the specified keys are read from the $data parameter.
     */
    public static function upsert(ConnectionInterface|\Doctrine\DBAL\Connection $connection, string $table, array $data, array $keys, bool $quoteIdentifiers = true): int|string
    {
        try {
            $data = $quoteIdentifiers ? self::quoteDataIdentifiers($connection, $data) : $data;

            return $connection->insert($table, $data);
        } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $exception) {
            $critera = [];
            foreach ($keys as $key) {
                $key = $quoteIdentifiers ? $connection->quoteIdentifier($key) : $key;
                $critera[$key] = $data[$key] ?? throw new \LogicException(sprintf('Key "%s" passed for upsert not found in data', $key));
            }

            return $connection->update($table, $data, $critera);
        }
    }

    /**
     * @deprecated will be removed in Pimcore 11. Use Pimcore\Db\Helper::upsert instead.
     *
     * @param ConnectionInterface|\Doctrine\DBAL\Connection $connection
     * @param string $table
     * @param array $data
     *
     * @return int|string
     */
    public static function insertOrUpdate(ConnectionInterface|\Doctrine\DBAL\Connection $connection, $table, array $data)
    {
        trigger_deprecation(
            'pimcore/pimcore',
            '10.6.0',
            sprintf('%s is deprecated and will be removed in Pimcore 11. Use Pimcore\Db\Helper::upsert() instead.', __METHOD__)
        );

        // extract and quote col names from the array keys
        $i = 0;
        $bind = [];
        $cols = [];
        $vals = [];
        foreach ($data as $col => $val) {
            $cols[] = $connection->quoteIdentifier($col);
            $bind[':col' . $i] = $val;
            $vals[] = ':col' . $i;
            $i++;
        }

        // build the statement
        $set = [];
        foreach ($cols as $i => $col) {
            $set[] = sprintf('%s = %s', $col, $vals[$i]);
        }

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s) ON DUPLICATE KEY UPDATE %s;',
            $connection->quoteIdentifier($table),
            implode(', ', $cols),
            implode(', ', $vals),
            implode(', ', $set)
        );

        $bind = array_merge($bind, $bind);

        return $connection->executeStatement($sql, $bind);
    }

    /**
     * @param ConnectionInterface|\Doctrine\DBAL\Connection $db
     * @param string $sql
     * @param array $params
     * @param array $types
     *
     * @return array
     */
    public static function fetchPairs(ConnectionInterface|\Doctrine\DBAL\Connection $db, $sql, array $params = [], $types = [])
    {
        $stmt = $db->executeQuery($sql, $params, $types);
        $data = [];
        if ($stmt instanceof Result) {
            while ($row = $stmt->fetchNumeric()) {
                $data[$row[0]] = $row[1];
            }
        }

        return $data;
    }

    /**
     * @param ConnectionInterface|\Doctrine\DBAL\Connection $db
     * @param string $table
     * @param string $idColumn
     * @param string $where
     *
     * @return void
     */
    public static function selectAndDeleteWhere(ConnectionInterface|\Doctrine\DBAL\Connection $db, $table, $idColumn = 'id', $where = '')
    {
        $sql = 'SELECT ' . $db->quoteIdentifier($idColumn) . '  FROM ' . $table;

        if ($where) {
            $sql .= ' WHERE ' . $where;
        }

        $idsForDeletion = $db->fetchFirstColumn($sql);

        if (!empty($idsForDeletion)) {
            $chunks = array_chunk($idsForDeletion, 1000);
            foreach ($chunks as $chunk) {
                $idString = implode(',', array_map([$db, 'quote'], $chunk));
                $db->executeStatement('DELETE FROM ' . $table . ' WHERE ' . $idColumn . ' IN (' . $idString . ')');
            }
        }
    }

    /**
     * @param ConnectionInterface|\Doctrine\DBAL\Connection $db
     * @param string $sql
     * @param array $exclusions
     *
     * @return \Doctrine\DBAL\Result|\Doctrine\DBAL\Driver\ResultStatement|null
     *
     * @throws ValidationException
     */
    public static function queryIgnoreError(ConnectionInterface|\Doctrine\DBAL\Connection $db, $sql, $exclusions = [])
    {
        try {
            return $db->executeQuery($sql);
        } catch (\Exception $e) {
            foreach ($exclusions as $exclusion) {
                if ($e instanceof $exclusion) {
                    throw new ValidationException($e->getMessage(), 0, $e);
                }
            }
            // we simply ignore the error
        }

        return null;
    }

    /**
     * @param ConnectionInterface|\Doctrine\DBAL\Connection $db
     * @param string $text
     * @param mixed $value
     * @param int|string|Type|null $type
     * @param int|null $count
     *
     * @return array|string
     */
    public static function quoteInto(ConnectionInterface|\Doctrine\DBAL\Connection $db, $text, $value, $type = null, $count = null)
    {
        if ($count === null) {
            return str_replace('?', $db->quote($value, $type), $text);
        }

        return implode($db->quote($value, $type), explode('?', $text, $count + 1));
    }

    public static function escapeLike(string $like): string
    {
        return str_replace(['_', '%'], ['\\_', '\\%'], $like);
    }

    public static function quoteDataIdentifiers(ConnectionInterface|\Doctrine\DBAL\Connection $db, array $data): array
    {
        $newData = [];
        foreach ($data as $key => $value) {
            $newData[$db->quoteIdentifier($key)] = $value;
        }

        return $newData;
    }
}
