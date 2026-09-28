<?php
declare(strict_types=1);

/**
 * Tiny PDO wrapper. All queries use prepared statements.
 */
final class DB
{
    private static ?PDO $pdo = null;

    public static function pdo(bool $withDatabase = true): PDO
    {
        if ($withDatabase && self::$pdo) return self::$pdo;
        $c = config('db');
        $dsn = sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $c['host'], (int) $c['port']);
        if ($withDatabase) $dsn .= ';dbname=' . $c['name'];
        $pdo = new PDO($dsn, $c['user'], $c['pass'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_STRINGIFY_FETCHES  => false,
        ]);
        $pdo->exec("SET time_zone = '" . date('P') . "'");
        if ($withDatabase) self::$pdo = $pdo;
        return $pdo;
    }

    public static function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = self::pdo()->prepare($sql);
        foreach ($params as $k => $v) {
            $key = is_int($k) ? $k + 1 : (str_starts_with($k, ':') ? $k : ":$k");
            $type = is_int($v) ? PDO::PARAM_INT : (is_bool($v) ? PDO::PARAM_BOOL : ($v === null ? PDO::PARAM_NULL : PDO::PARAM_STR));
            $stmt->bindValue($key, $v, $type);
        }
        $stmt->execute();
        return $stmt;
    }

    public static function all(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll();
    }

    public static function one(string $sql, array $params = []): ?array
    {
        $row = self::query($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    public static function value(string $sql, array $params = [])
    {
        $v = self::query($sql, $params)->fetchColumn();
        return $v === false ? null : $v;
    }

    public static function insert(string $table, array $data): int
    {
        $cols = array_keys($data);
        $sql = sprintf(
            'INSERT INTO `%s` (%s) VALUES (%s)',
            $table,
            implode(', ', array_map(fn($c) => "`$c`", $cols)),
            implode(', ', array_map(fn($c) => ":$c", $cols))
        );
        self::query($sql, $data);
        return (int) self::pdo()->lastInsertId();
    }

    public static function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $set = implode(', ', array_map(fn($c) => "`$c` = :set_$c", array_keys($data)));
        $params = [];
        foreach ($data as $k => $v) $params["set_$k"] = $v;
        return self::query("UPDATE `$table` SET $set WHERE $where", array_merge($params, $whereParams))->rowCount();
    }

    public static function transaction(callable $fn)
    {
        $pdo = self::pdo();
        $pdo->beginTransaction();
        try {
            $result = $fn();
            $pdo->commit();
            return $result;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /** True when the database exists and the schema has been installed. */
    public static function isInstalled(bool $fresh = false): bool
    {
        static $cached = null;
        if ($cached !== null && !$fresh) return $cached;
        try {
            self::$pdo = null;
            self::pdo()->query('SELECT 1 FROM users LIMIT 1');
            return $cached = true;
        } catch (Throwable $e) {
            return $cached = false;
        }
    }
}
