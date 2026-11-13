<?php
/**
 * ConnectHub installer. Open /install in your browser.
 * It checks the server, creates the tables, loads the starter content, creates your
 * admin account and writes config/config.php. It refuses to run once installed.
 */

declare(strict_types=1);

define('CH_INSTALLER', true);
require dirname(__DIR__) . '/includes/bootstrap.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('chinstall');
    session_start();
}

// the same file the application reads (honours the CH_CONFIG override), so "already installed" is decided consistently
$configFile = function_exists('config_file') ? config_file() : ROOT . '/config/config.php';
$installed = is_file($configFile);
$errors = [];
$done = false;

function inst_h($s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function requirements(): array
{
    $dirs = ['config' => ROOT . '/config', 'storage' => ROOT . '/storage', 'uploads' => ROOT . '/uploads'];
    foreach ($dirs as $d) {
        if (!is_dir($d)) {
            @mkdir($d, 0775, true);
        }
    }
    $r = [];
    $r[] = ['PHP 8.1 or newer', version_compare(PHP_VERSION, '8.1.0', '>='), 'You have ' . PHP_VERSION];
    foreach (['pdo_mysql' => 'MySQL driver (pdo_mysql)', 'mbstring' => 'Multibyte strings (mbstring)', 'gd' => 'Image processing (gd)', 'fileinfo' => 'File type detection (fileinfo)',
        'dom' => 'XML/HTML (dom)', 'openssl' => 'Encryption (openssl)', 'curl' => 'Web requests for M-Pesa (curl)'] as $ext => $label) {
        $r[] = [$label, extension_loaded($ext), extension_loaded($ext) ? 'Installed' : 'Missing, ask your host to enable it'];
    }
    $r[] = ['JPEG and WebP support in GD', function_exists('imagecreatefromjpeg') && function_exists('imagewebp'), 'Needed for photos'];
    foreach ($dirs as $name => $d) {
        $r[] = ['Folder "' . $name . '" is writable', is_writable($d), is_writable($d) ? 'Yes' : 'Set permissions to 775 (or 755 with the right owner)'];
    }
    return $r;
}

function detect_site_url(): string
{
    return rtrim(site_origin() . base_path(), '/');
}

if ($installed) {
    // Already installed: show a notice only.
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        $errors[] = 'Your session expired. Reload the page and try again.';
    } else {
        $in = [
            'db_host' => trim($_POST['db_host'] ?? 'localhost'), 'db_port' => (int)($_POST['db_port'] ?? 3306) ?: 3306,
            'db_name' => trim($_POST['db_name'] ?? ''), 'db_user' => trim($_POST['db_user'] ?? ''), 'db_pass' => (string)($_POST['db_pass'] ?? ''),
            'site_name' => trim($_POST['site_name'] ?? 'ConnectHub'), 'site_url' => rtrim(trim($_POST['site_url'] ?? ''), '/'),
            'admin_name' => trim($_POST['admin_name'] ?? ''), 'admin_email' => strtolower(trim($_POST['admin_email'] ?? '')),
            'admin_pass' => (string)($_POST['admin_pass'] ?? ''), 'skip_import' => !empty($_POST['skip_import']), 'demo' => !empty($_POST['demo']),
        ];
        // The address the installer was opened on is detected on every request, so only an address that differs is stored.
        // That keeps the site working on a test domain, the final domain and with or without www.
        if (strcasecmp($in['site_url'], detect_site_url()) === 0) {
            $in['site_url'] = '';
        }
        if ($in['db_name'] === '' || $in['db_user'] === '') {
            $errors[] = 'Enter the database name and user.';
        }
        if (!preg_match('/^[A-Za-z0-9_$-]+$/', $in['db_name'])) {
            $errors[] = 'The database name can only contain letters, numbers, underscores and hyphens.';
        }
        if (!$in['skip_import']) {
            if ($in['admin_name'] === '') {
                $errors[] = 'Enter a name for the admin account.';
            }
            if (!filter_var($in['admin_email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Enter a valid admin email address.';
            }
            if (strlen($in['admin_pass']) < 10) {
                $errors[] = 'Choose an admin password of at least 10 characters.';
            }
        }
        foreach (requirements() as $req) {
            if (!$req[1] && strpos($req[0], 'curl') === false) {
                $errors[] = 'Fix this first: ' . $req[0] . '.';
                break;
            }
        }
        if (!$errors) {
            try {
                $dsnBase = 'mysql:host=' . $in['db_host'] . ';port=' . $in['db_port'] . ';charset=utf8mb4';
                $opts = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC];
                try {
                    $pdo = new PDO($dsnBase . ';dbname=' . $in['db_name'], $in['db_user'], $in['db_pass'], $opts);
                } catch (PDOException $e) {
                    if ((int)$e->getCode() === 1049) { // unknown database: try to create it
                        $root = new PDO($dsnBase, $in['db_user'], $in['db_pass'], $opts);
                        $root->exec('CREATE DATABASE `' . $in['db_name'] . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
                        $pdo = new PDO($dsnBase . ';dbname=' . $in['db_name'], $in['db_user'], $in['db_pass'], $opts);
                    } else {
                        throw $e;
                    }
                }
                $pdo->exec("SET NAMES utf8mb4");
                $existing = (int)$pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'users'")->fetchColumn();
                if (!$in['skip_import']) {
                    if ($existing) {
                        throw new RuntimeException('This database already has ConnectHub tables. Use an empty database, or tick "I already imported connecthub.sql".');
                    }
                    @set_time_limit(300);
                    $files = array_merge(glob(ROOT . '/database/schema/*.sql') ?: [], glob(ROOT . '/database/seed/*.sql') ?: []);
                    sort($files);
                    foreach ($files as $f) {
                        $sql = (string)file_get_contents($f);
                        foreach (sql_split($sql) as $stmt) {
                            $pdo->exec($stmt);
                        }
                    }
                    // Admin account
                    $roleId = $pdo->query("SELECT id FROM admin_roles WHERE slug = 'super_admin'")->fetchColumn();
                    $st = $pdo->prepare("INSERT INTO users (email, password_hash, role, admin_role_id, status, email_verified_at, must_change_password) VALUES (?, ?, 'admin', ?, 'active', NOW(), 0)");
                    $st->execute([$in['admin_email'], password_hash($in['admin_pass'], PASSWORD_BCRYPT, ['cost' => 12]), $roleId ?: null]);
                    $uid = (int)$pdo->lastInsertId();
                    $pdo->prepare("INSERT INTO user_profiles (user_id, display_name, onboarding_completed) VALUES (?, ?, 1)")->execute([$uid, $in['admin_name']]);
                    $set = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
                    $set->execute(['site_name', $in['site_name'] ?: 'ConnectHub']);
                    $set->execute(['site_url', $in['site_url']]);
                    $set->execute(['mail_driver', 'mail']);
                } elseif (!$existing) {
                    throw new RuntimeException('No tables found. Import the connecthub.sql file first, or untick the box to let the installer create them.');
                }
                $cfg = [
                    'db' => ['host' => $in['db_host'], 'port' => $in['db_port'], 'name' => $in['db_name'], 'user' => $in['db_user'], 'pass' => $in['db_pass'], 'charset' => 'utf8mb4'],
                    'app_key' => bin2hex(random_bytes(32)), 'debug' => false, 'base_path' => null, 'installed' => true,
                ];
                $php = "<?php\n// Written by the ConnectHub installer on " . date('Y-m-d H:i:s') . ". Keep this file private.\nreturn " . var_export($cfg, true) . ";\n";
                if (@file_put_contents($configFile, $php, LOCK_EX) === false) {
                    throw new RuntimeException('Could not write config/config.php. Make the config folder writable and try again.');
                }
                @chmod($configFile, 0640);
                // a "database is up to date" marker left by an earlier attempt must not hide the upgrade steps of this new database
                foreach (glob(ROOT . '/storage/cache/schema_*.ok') ?: [] as $marker) {
                    @unlink($marker);
                }
                $done = true;
                $_SESSION = [];
            } catch (Throwable $e) {
                $msg = $e->getMessage();
                $errors[] = $e instanceof PDOException ? 'Database error: ' . $msg : $msg;
            }
        }
    }
}
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}
$reqs = requirements();
$reqOk = true;
foreach ($reqs as $r) {
    if (!$r[1] && strpos($r[0], 'curl') === false) {
        $reqOk = false;
    }
}
$v = fn(string $k, string $d = '') => inst_h($_POST[$k] ?? $d);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Install ConnectHub</title>
<meta name="robots" content="noindex">
<style>
:root{--c:#E8523F;--c2:#F4A63A;--ink:#2A2230;--mute:#7A7084;--bg:#FBF6F1;--card:#fff;--line:#EADFD6;--ok:#1C8C5A;--bad:#C83A2D}
*{box-sizing:border-box}body{margin:0;font:16px/1.55 system-ui,-apple-system,"Segoe UI",sans-serif;color:var(--ink);background:radial-gradient(900px 500px at 10% -10%,#FFE3CF 0,transparent 60%),radial-gradient(800px 500px at 100% 0,#F3D9E6 0,transparent 55%),var(--bg);min-height:100vh}
.wrap{max-width:720px;margin:0 auto;padding:40px 20px 80px}
h1{font-size:30px;letter-spacing:-.02em;margin:0 0 6px}h2{font-size:18px;margin:32px 0 12px}p.lead{color:var(--mute);margin:0 0 28px}
.card{background:var(--card);border:1px solid var(--line);border-radius:20px;padding:24px;box-shadow:0 12px 40px -24px rgba(90,40,20,.35)}
label{display:block;font-weight:600;font-size:14px;margin:14px 0 6px}input[type=text],input[type=email],input[type=password],input[type=number]{width:100%;padding:12px 14px;border:1px solid var(--line);border-radius:12px;font:inherit;background:#fff;color:var(--ink)}
input:focus{outline:3px solid rgba(232,82,63,.25);border-color:var(--c)}.row{display:grid;grid-template-columns:1fr 1fr;gap:14px}@media(max-width:560px){.row{grid-template-columns:1fr}}
.help{font-size:13px;color:var(--mute);margin-top:4px}.btn{display:inline-block;margin-top:24px;background:linear-gradient(135deg,var(--c),var(--c2));color:#fff;border:0;border-radius:14px;padding:14px 28px;font:600 16px system-ui;cursor:pointer}
.btn:disabled{opacity:.5;cursor:not-allowed}ul.req{list-style:none;margin:0;padding:0}ul.req li{display:flex;gap:10px;align-items:baseline;padding:6px 0;border-bottom:1px dashed var(--line);font-size:14px}
.ok{color:var(--ok);font-weight:700}.bad{color:var(--bad);font-weight:700}.err{background:#FDECEA;color:#8E241A;border:1px solid #F6C9C3;border-radius:12px;padding:12px 16px;margin-bottom:16px}
.chk{display:flex;gap:10px;align-items:flex-start;margin-top:16px;font-weight:500}.chk input{margin-top:4px}code{background:#F3EBE4;padding:2px 6px;border-radius:6px;font-size:90%}
.brand{display:flex;align-items:center;gap:10px;margin-bottom:28px;font-weight:800;font-size:20px;letter-spacing:-.02em}.mark{width:36px;height:36px;border-radius:12px;background:linear-gradient(135deg,var(--c),var(--c2));display:grid;place-items:center;color:#fff;font-size:20px}
</style>
</head>
<body>
<div class="wrap">
  <div class="brand"><span class="mark">♥</span> ConnectHub</div>

<?php if ($installed && !$done): ?>
  <h1>Already installed</h1>
  <p class="lead">This site is set up. For safety, delete the <code>install</code> folder from your server.</p>
  <div class="card"><a class="btn" style="margin:0" href="<?= inst_h(url('/login')) ?>">Go to sign in</a></div>

<?php elseif ($done): ?>
  <h1>You are all set</h1>
  <p class="lead">ConnectHub is installed. Sign in as admin to finish setting up the site.</p>
  <div class="card">
    <ul class="req">
      <li><span class="ok">1</span> Sign in with the admin email and password you just chose.</li>
      <li><span class="ok">2</span> Open <strong>Settings</strong> in the admin panel: upload your logo, set contact details, payments and email.</li>
      <li><span class="ok">3</span> <strong>Delete the <code>install</code> folder</strong> from your server.</li>
      <li><span class="ok">4</span> Add a cron job that runs <code>php <?= inst_h(ROOT) ?>/cron/run.php</code> every 5 minutes (see docs/DEPLOY.md).</li>
    </ul>
    <a class="btn" href="<?= inst_h(url('/login')) ?>">Sign in to admin</a>
  </div>

<?php else: ?>
  <h1>Install ConnectHub</h1>
  <p class="lead">Takes about a minute. You need an empty MySQL or MariaDB database and its login details.</p>
  <?php foreach ($errors as $e): ?><div class="err"><?= inst_h($e) ?></div><?php endforeach; ?>

  <div class="card">
    <h2 style="margin-top:0">1. Server check</h2>
    <ul class="req">
      <?php foreach ($reqs as $r): ?>
        <li><span class="<?= $r[1] ? 'ok' : 'bad' ?>"><?= $r[1] ? '✓' : '✕' ?></span><span><strong><?= inst_h($r[0]) ?></strong> <span style="color:var(--mute)">· <?= inst_h($r[2]) ?></span></span></li>
      <?php endforeach; ?>
    </ul>
  </div>

  <form method="post" autocomplete="off" class="card" style="margin-top:20px">
    <input type="hidden" name="csrf" value="<?= inst_h($_SESSION['csrf']) ?>">
    <h2 style="margin-top:0">2. Database</h2>
    <div class="row"><div><label>Host</label><input type="text" name="db_host" value="<?= $v('db_host', 'localhost') ?>" required></div><div><label>Port</label><input type="number" name="db_port" value="<?= $v('db_port', '3306') ?>"></div></div>
    <label>Database name</label><input type="text" name="db_name" value="<?= $v('db_name', 'connecthub') ?>" required>
    <div class="row"><div><label>Database user</label><input type="text" name="db_user" value="<?= $v('db_user') ?>" required></div><div><label>Password</label><input type="password" name="db_pass" value=""></div></div>
    <label class="chk"><input type="checkbox" name="skip_import" value="1" <?= !empty($_POST['skip_import']) ? 'checked' : '' ?>> <span>I already imported <code>connecthub.sql</code> (skips creating tables and the admin account)</span></label>

    <h2>3. Your site</h2>
    <div class="row"><div><label>Site name</label><input type="text" name="site_name" value="<?= $v('site_name', 'ConnectHub') ?>"></div><div><label>Site address</label><input type="text" name="site_url" value="<?= $v('site_url', '') ?>" placeholder="<?= e(detect_site_url()) ?>"><div class="help">Leave empty to use the address you are on now. It also keeps working if you change domain later.</div></div></div>

    <h2>4. Admin account</h2>
    <div class="row"><div><label>Your name</label><input type="text" name="admin_name" value="<?= $v('admin_name') ?>"></div><div><label>Email</label><input type="email" name="admin_email" value="<?= $v('admin_email') ?>"></div></div>
    <label>Password</label><input type="password" name="admin_pass" minlength="10" autocomplete="new-password">
    <div class="help">At least 10 characters. You will use this to sign in at <code>/login</code>.</div>

    <button class="btn" type="submit" <?= $reqOk ? '' : 'disabled' ?>>Install ConnectHub</button>
  </form>
<?php endif; ?>
</div>
</body>
</html>
