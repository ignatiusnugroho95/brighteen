<?php

namespace App\Models;

use PDO;

abstract class Model
{
    protected static $table;

    public static function getTable()
    {
        return static::$table;
    }

    public static function all($orderBy = 'id DESC')
    {
        $table = static::$table;
        $stmt = db()->query("SELECT * FROM `{$table}` ORDER BY {$orderBy}");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function find($id)
    {
        $table = static::$table;
        $stmt = db()->prepare("SELECT * FROM `{$table}` WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function where($column, $value, $operator = '=')
    {
        $table = static::$table;
        $stmt = db()->prepare("SELECT * FROM `{$table}` WHERE `{$column}` {$operator} ?");
        $stmt->execute([$value]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function findBy($column, $value)
    {
        $table = static::$table;
        $stmt = db()->prepare("SELECT * FROM `{$table}` WHERE `{$column}` = ? LIMIT 1");
        $stmt->execute([$value]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function create(array $data)
    {
        $table = static::$table;
        $columns = array_keys($data);
        $fields = '`' . implode('`, `', $columns) . '`';
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));

        $sql = "INSERT INTO `{$table}` ({$fields}) VALUES ({$placeholders})";
        $stmt = db()->prepare($sql);
        $stmt->execute(array_values($data));

        return db()->lastInsertId();
    }

    public static function update($id, array $data)
    {
        $table = static::$table;
        $setClauses = [];
        $values = [];

        foreach ($data as $column => $value) {
            $setClauses[] = "`{$column}` = ?";
            $values[] = $value;
        }

        $values[] = $id;
        $sql = "UPDATE `{$table}` SET " . implode(', ', $setClauses) . " WHERE id = ?";
        $stmt = db()->prepare($sql);
        return $stmt->execute($values);
    }

    public static function delete($id)
    {
        $table = static::$table;
        $stmt = db()->prepare("DELETE FROM `{$table}` WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public static function count($whereClause = '1=1', $params = [])
    {
        $table = static::$table;
        $stmt = db()->prepare("SELECT COUNT(*) as total FROM `{$table}` WHERE {$whereClause}");
        $stmt->execute($params);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($res['total'] ?? 0);
    }
}
