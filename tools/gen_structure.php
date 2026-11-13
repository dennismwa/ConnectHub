<?php
/**
 * Writes database/structure.json: every table with its CREATE statement and its columns. The admin "Database check" tool
 * compares a live database against this file and can add what is missing. Run by tools/package.sh against the release database.
 * Usage: CH_CONFIG=... php tools/gen_structure.php
 */
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';
$out = [];
foreach (col('SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE() AND table_type = \'BASE TABLE\' ORDER BY table_name') as $t) {
    $create = (string)(row('SHOW CREATE TABLE `' . str_replace('`', '', $t) . '`')['Create Table'] ?? '');
    $create = preg_replace('/ AUTO_INCREMENT=\d+/', '', $create);
    $cols = [];
    foreach (rows('SELECT column_name, column_type, is_nullable, column_default, extra, character_set_name, collation_name FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = :t ORDER BY ordinal_position', ['t' => $t]) as $c) {
        $def = $c['column_type'];
        if ($c['collation_name']) { $def .= ' COLLATE ' . $c['collation_name']; }
        $def .= $c['is_nullable'] === 'YES' ? ' NULL' : ' NOT NULL';
        $d = $c['column_default'];
        if ($d !== null && stripos((string)$c['extra'], 'auto_increment') === false) {
            // MariaDB reports literals quoted and expressions bare; MySQL reports text unquoted
            $isExpr = preg_match('/^(current_timestamp(\(\d*\))?|now\(\)|curdate\(\)|NULL)$/i', (string)$d) === 1;
            $def .= ' DEFAULT ' . ($isExpr ? ($d === 'NULL' ? 'NULL' : $d) : (preg_match("/^'.*'$/s", (string)$d) ? $d : "'" . str_replace("'", "''", (string)$d) . "'"));
        }
        $extra = trim(preg_replace('/\b(DEFAULT_GENERATED|auto_increment)\b/i', '', (string)$c['extra']));
        if ($extra !== '') { $def .= ' ' . $extra; }
        $cols[$c['column_name']] = ['def' => $def, 'auto' => stripos((string)$c['extra'], 'auto_increment') !== false];
    }
    $out[$t] = ['create' => $create, 'columns' => $cols];
}
file_put_contents(dirname(__DIR__) . '/database/structure.json', json_encode(['version' => CH_SCHEMA_VERSION, 'tables' => $out], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
echo count($out) . " tables written\n";
