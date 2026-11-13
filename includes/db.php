<?php
/**
 * Database layer: a thin PDO wrapper.
 *
 * q($sql, $params) accepts named (:name) or positional (?) parameters. Named
 * parameters may be repeated in one statement and array values expand into
 * IN lists (":ids" => [1,2,3]), which PDO's native prepares do not allow.
 */

declare(strict_types=1);

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $c = config('db');
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $c['host'], (int)($c['port'] ?? 3306), $c['name'], $c['charset'] ?? 'utf8mb4');
    $pdo = new PDO($dsn, $c['user'], $c['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4, sql_mode='STRICT_TRANS_TABLES,NO_ENGINE_SUBSTITUTION'",
    ]);
    // Keep NOW() and PHP date() in the same time zone.
    $offset = (new DateTime('now', new DateTimeZone(date_default_timezone_get())))->format('P');
    $pdo->exec("SET time_zone = '" . $offset . "'");
    return $pdo;
}

function db_connect_check(): bool
{
    try {
        db();
        return true;
    } catch (Throwable $e) {
        return false;
    }
}

/**
 * Rewrite a statement so every named parameter occurrence is unique and array
 * values are expanded. Quoted strings and backtick identifiers are skipped.
 *
 * @return array [string $sql, array $params]
 */
function db_expand(string $sql, array $params): array
{
    $named = false;
    foreach ($params as $k => $_) {
        if (!is_int($k)) {
            $named = true;
            break;
        }
    }
    if (!$named) {
        // Positional: expand array values into repeated placeholders.
        $out = '';
        $flat = [];
        $i = 0;
        $len = strlen($sql);
        $quote = null;
        for ($p = 0; $p < $len; $p++) {
            $ch = $sql[$p];
            if ($quote !== null) {
                $out .= $ch;
                if ($ch === '\\' && $quote !== '`') {
                    $out .= $sql[++$p] ?? '';
                } elseif ($ch === $quote) {
                    $quote = null;
                }
                continue;
            }
            if ($ch === "'" || $ch === '"' || $ch === '`') {
                $quote = $ch;
                $out .= $ch;
                continue;
            }
            if ($ch === '?') {
                $v = $params[$i++] ?? null;
                if (is_array($v)) {
                    if (!$v) {
                        $out .= 'NULL';
                    } else {
                        $out .= implode(',', array_fill(0, count($v), '?'));
                        foreach ($v as $item) {
                            $flat[] = $item;
                        }
                    }
                } else {
                    $out .= '?';
                    $flat[] = $v;
                }
                continue;
            }
            $out .= $ch;
        }
        return [$out, $flat];
    }

    $bound = [];
    $counter = [];
    $out = '';
    $len = strlen($sql);
    $quote = null;
    for ($p = 0; $p < $len; $p++) {
        $ch = $sql[$p];
        if ($quote !== null) {
            $out .= $ch;
            if ($ch === '\\' && $quote !== '`') {
                $out .= $sql[++$p] ?? '';
            } elseif ($ch === $quote) {
                $quote = null;
            }
            continue;
        }
        if ($ch === "'" || $ch === '"' || $ch === '`') {
            $quote = $ch;
            $out .= $ch;
            continue;
        }
        if ($ch === ':' && $p + 1 < $len && (ctype_alpha($sql[$p + 1]) || $sql[$p + 1] === '_') && ($p === 0 || $sql[$p - 1] !== ':')) {
            $q = $p + 1;
            while ($q < $len && (ctype_alnum($sql[$q]) || $sql[$q] === '_')) {
                $q++;
            }
            $name = substr($sql, $p + 1, $q - $p - 1);
            if (array_key_exists($name, $params) || array_key_exists(':' . $name, $params)) {
                $v = array_key_exists($name, $params) ? $params[$name] : $params[':' . $name];
                $counter[$name] = ($counter[$name] ?? 0) + 1;
                if (is_array($v)) {
                    if (!$v) {
                        $out .= 'NULL';
                    } else {
                        $ph = [];
                        foreach (array_values($v) as $n => $item) {
                            $key = $name . '__' . $counter[$name] . '_' . $n;
                            $ph[] = ':' . $key;
                            $bound[$key] = $item;
                        }
                        $out .= implode(',', $ph);
                    }
                } else {
                    $key = $name . '__' . $counter[$name];
                    $out .= ':' . $key;
                    $bound[$key] = $v;
                }
                $p = $q - 1;
                continue;
            }
        }
        $out .= $ch;
    }
    return [$out, $bound];
}

function q(string $sql, array $params = []): PDOStatement
{
    [$sql2, $bound] = db_expand($sql, $params);
    $st = db()->prepare($sql2);
    foreach ($bound as $k => $v) {
        $param = is_int($k) ? $k + 1 : ':' . $k;
        if (is_int($v) || is_bool($v)) {
            $st->bindValue($param, (int)$v, PDO::PARAM_INT);
        } elseif ($v === null) {
            $st->bindValue($param, null, PDO::PARAM_NULL);
        } else {
            $st->bindValue($param, (string)$v, PDO::PARAM_STR);
        }
    }
    $st->execute();
    return $st;
}

/** First row or null. */
function row(string $sql, array $params = []): ?array
{
    $r = q($sql, $params)->fetch();
    return $r === false ? null : $r;
}

/** All rows. */
function rows(string $sql, array $params = []): array
{
    return q($sql, $params)->fetchAll();
}

/** First column of first row, or $default. */
function val(string $sql, array $params = [], $default = null)
{
    $r = q($sql, $params)->fetch(PDO::FETCH_NUM);
    return $r === false ? $default : $r[0];
}

/** First column of every row as a flat list. */
function col(string $sql, array $params = []): array
{
    return q($sql, $params)->fetchAll(PDO::FETCH_COLUMN);
}

/** Rows keyed by the first column (value = remaining columns or the second column when only two). */
function pairs(string $sql, array $params = []): array
{
    return q($sql, $params)->fetchAll(PDO::FETCH_KEY_PAIR);
}

function db_ident(string $name): string
{
    return '`' . str_replace('`', '', $name) . '`';
}

/** INSERT a row from an associative array. Returns the new auto-increment id (0 when none). */
function insert(string $table, array $data): int
{
    $cols = array_map('db_ident', array_keys($data));
    $ph = [];
    $params = [];
    foreach ($data as $k => $v) {
        $ph[] = ':' . $k;
        $params[$k] = $v;
    }
    q('INSERT INTO ' . db_ident($table) . ' (' . implode(',', $cols) . ') VALUES (' . implode(',', $ph) . ')', $params);
    return (int)db()->lastInsertId();
}

/** UPDATE rows matching an equality $where array. Returns the number of rows changed. */
function update(string $table, array $data, array $where): int
{
    if (!$data || !$where) {
        return 0;
    }
    $set = [];
    $params = [];
    foreach ($data as $k => $v) {
        $set[] = db_ident($k) . ' = :s_' . $k;
        $params['s_' . $k] = $v;
    }
    $cond = [];
    foreach ($where as $k => $v) {
        if ($v === null) {
            $cond[] = db_ident($k) . ' IS NULL';
        } else {
            $cond[] = db_ident($k) . ' = :w_' . $k;
            $params['w_' . $k] = $v;
        }
    }
    return q('UPDATE ' . db_ident($table) . ' SET ' . implode(', ', $set) . ' WHERE ' . implode(' AND ', $cond), $params)->rowCount();
}

function delete_rows(string $table, array $where): int
{
    if (!$where) {
        return 0;
    }
    $cond = [];
    $params = [];
    foreach ($where as $k => $v) {
        $cond[] = db_ident($k) . ' = :w_' . $k;
        $params['w_' . $k] = $v;
    }
    return q('DELETE FROM ' . db_ident($table) . ' WHERE ' . implode(' AND ', $cond), $params)->rowCount();
}

/** Run $fn inside a transaction; rolls back and rethrows on failure. */
function db_transaction(callable $fn)
{
    $pdo = db();
    if ($pdo->inTransaction()) {
        return $fn();
    }
    $pdo->beginTransaction();
    try {
        $result = $fn();
        $pdo->commit();
        return $result;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

/**
 * Split an SQL script into statements, respecting quotes and comments.
 * Used by the installer and updater.
 */
function sql_split(string $sql): array
{
    $statements = [];
    $buf = '';
    $len = strlen($sql);
    $quote = null;
    for ($i = 0; $i < $len; $i++) {
        $ch = $sql[$i];
        $next = $i + 1 < $len ? $sql[$i + 1] : '';
        if ($quote !== null) {
            $buf .= $ch;
            if ($ch === '\\' && $quote !== '`') {
                $buf .= $next;
                $i++;
            } elseif ($ch === $quote) {
                if ($next === $quote) { // doubled quote
                    $buf .= $next;
                    $i++;
                } else {
                    $quote = null;
                }
            }
            continue;
        }
        if ($ch === '-' && $next === '-' && ($i + 2 >= $len || ctype_space($sql[$i + 2]))) {
            while ($i < $len && $sql[$i] !== "\n") {
                $i++;
            }
            $buf .= "\n";
            continue;
        }
        if ($ch === '#') {
            while ($i < $len && $sql[$i] !== "\n") {
                $i++;
            }
            $buf .= "\n";
            continue;
        }
        if ($ch === '/' && $next === '*' && ($sql[$i + 2] ?? '') !== '!') {
            $end = strpos($sql, '*/', $i + 2);
            $i = $end === false ? $len : $end + 1;
            continue;
        }
        if ($ch === "'" || $ch === '"' || $ch === '`') {
            $quote = $ch;
            $buf .= $ch;
            continue;
        }
        if ($ch === ';') {
            if (trim($buf) !== '') {
                $statements[] = trim($buf);
            }
            $buf = '';
            continue;
        }
        $buf .= $ch;
    }
    if (trim($buf) !== '') {
        $statements[] = trim($buf);
    }
    return $statements;
}
