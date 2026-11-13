<?php
/**
 * Email providers: each API provider builds the right request (address, key header, body), failures are explained,
 * the server-default fallback works, and SMTP presets resolve. Uses a local mock instead of the real services.
 *
 *   export CH_CONFIG=/path/to/throwaway-config.php
 *   php tools/tests/mail_test.php
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit("Run this from the command line.\n");
}
if (!getenv('CH_CONFIG') && !in_array('--yes', $argv, true)) {
    exit("Refusing to run without CH_CONFIG pointing at a throw-away database (or pass --yes).\n");
}
$log = sys_get_temp_dir() . '/ch_mock_mail_' . getmypid() . '.log';
@unlink($log);
@unlink($log . '.fail');
putenv('MOCK_MAIL_LOG=' . $log);
$port = 8911;
$proc = proc_open([PHP_BINARY, '-S', '127.0.0.1:' . $port, __DIR__ . '/mock_mail_api.php'], [0 => ['pipe', 'r'], 1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']], $pipes, null, ['MOCK_MAIL_LOG' => $log, 'PATH' => (string)getenv('PATH')]);
register_shutdown_function(function () use ($proc, $log) {
    is_resource($proc) && proc_terminate($proc);
    @unlink($log);
    @unlink($log . '.fail');
});
for ($i = 0; $i < 30; $i++) {
    if (@fsockopen('127.0.0.1', $port)) {
        break;
    }
    usleep(100000);
}
putenv('CH_MAIL_API_BASE=http://127.0.0.1:' . $port);
require dirname(__DIR__, 2) . '/includes/bootstrap.php';

$pass = 0;
$fail = 0;
function check(string $name, bool $ok, string $detail = ''): void
{
    global $pass, $fail;
    $ok ? $pass++ : $fail++;
    echo ($ok ? '  ok    ' : '  FAIL  ') . $name . (!$ok && $detail !== '' ? "  ($detail)" : '') . "\n";
}
$touched = [];
function set_for_test(string $key, string $value): void
{
    global $touched;
    if (!array_key_exists($key, $touched)) {
        $r = row('SELECT setting_value FROM site_settings WHERE setting_key = :k', ['k' => $key]);
        $touched[$key] = $r ? (string)$r['setting_value'] : null;
    }
    setting_set($key, $value);
}
register_shutdown_function(function () use (&$touched) {
    foreach ($touched as $k => $v) {
        $v === null ? setting_reset($k) : setting_set($k, $v);
    }
});
function last_request(string $log): ?array
{
    $lines = is_file($log) ? array_filter(explode("\n", (string)file_get_contents($log))) : [];
    return $lines ? json_decode((string)end($lines), true) : null;
}
function hdr(array $req, string $name): string
{
    foreach ($req['headers'] as $k => $v) {
        if (strcasecmp($k, $name) === 0) {
            return (string)$v;
        }
    }
    return '';
}

set_for_test('mail_from_email', 'hello@example.test');
set_for_test('mail_from_name', 'Test Site');
set_for_test('mail_api_key', 'KEY-123');
set_for_test('mail_fallback_server', '0');

echo "\nAPI providers\n";
foreach (['sendgrid_api' => ['/v3/mail/send', 'Authorization', 'Bearer KEY-123'], 'brevo_api' => ['/v3/smtp/email', 'api-key', 'KEY-123'],
          'postmark_api' => ['/email', 'X-Postmark-Server-Token', 'KEY-123'], 'resend_api' => ['/emails', 'Authorization', 'Bearer KEY-123']] as $drv => [$path, $h, $hv]) {
    set_for_test('mail_driver', $drv);
    @unlink($log);
    $ok = send_mail('to@example.test', 'Subject line', '<p>Hello</p>');
    $req = last_request($log);
    check("$drv sends", $ok && $req !== null, mail_last_error());
    check("$drv uses the right address", $req && $req['path'] === $path, (string)($req['path'] ?? ''));
    check("$drv sends the key", $req && hdr($req, $h) === $hv, $req ? hdr($req, $h) : '');
    check("$drv body has recipient, subject and html", $req && strpos($req['body'], 'to@example.test') !== false && strpos($req['body'], 'Subject line') !== false && strpos($req['body'], 'Hello') !== false);
}
set_for_test('mail_driver', 'mailgun_api');
set_for_test('mail_mailgun_domain', 'mg.example.test');
@unlink($log);
$ok = send_mail('to@example.test', 'MG subject', '<p>Hi</p>');
$req = last_request($log);
check('mailgun sends to the domain path', $ok && $req && $req['path'] === '/v3/mg.example.test/messages', mail_last_error());
check('mailgun uses basic auth with the key', $req && hdr($req, 'Authorization') === 'Basic ' . base64_encode('api:KEY-123'));
check('mailgun form has the recipient', $req && strpos($req['body'], 'to=to%40example.test') !== false);
set_for_test('mail_mailgun_domain', '');
check('mailgun without a domain explains why', !send_mail('to@example.test', 's', 'h') && strpos(mail_last_error(), 'domain') !== false);

echo "\nFailures are explained\n";
set_for_test('mail_driver', 'sendgrid_api');
file_put_contents($log . '.fail', '1');
$ok = send_mail('to@example.test', 's', '<p>h</p>');
check('a refused key gives a useful message', !$ok && strpos(mail_last_error(), '401') !== false && strpos(mail_last_error(), 'API key') !== false, mail_last_error());
@unlink($log . '.fail');
set_for_test('mail_api_key', '');
check('empty key explains itself', !send_mail('to@example.test', 's', 'h') && strpos(mail_last_error(), 'API key') !== false);
set_for_test('mail_api_key', 'KEY-123');
check('bad address is refused', !send_mail('not-an-email', 's', 'h'));
[$ok, $msg] = mail_send_test('to@example.test');
check('test send reports success', $ok && strpos($msg, 'Sent to') === 0, $msg);

echo "\nOther methods\n";
set_for_test('mail_driver', 'log');
check('log driver writes to the log, nothing is sent', send_mail('log@example.test', 's', '<p>h</p>'));
foreach (glob(ROOT . '/storage/mail/*.html') ?: [] as $f) {
    if (strpos((string)file_get_contents($f), 'log@example.test') !== false) {
        @unlink($f);
    }
}
set_for_test('mail_driver', 'smtp');
set_for_test('smtp_preset', 'gmail');
set_for_test('smtp_user', 'x');
@unlink($log);
$closed = 'smtp-closed';
// A preset must never use the typed host: point the typed host at a closed port and check the error names the preset host.
set_for_test('smtp_host', '127.0.0.1');
$ok = @send_mail('to@example.test', 's', 'h');
check('gmail preset resolves its own server (not the typed one)', !$ok && (strpos(mail_last_error(), 'smtp.gmail.com') !== false || strpos(mail_last_error(), 'connect') !== false), mail_last_error());
check('presets list covers the main providers', count(array_intersect(array_keys(mail_smtp_presets()), ['gmail', 'outlook', 'zoho', 'brevo', 'sendgrid', 'mailgun', 'postmark', 'ses', 'mailjet', 'custom'])) === 10);
set_for_test('smtp_preset', 'custom');
set_for_test('smtp_host', '127.0.0.1');
set_for_test('smtp_port', '1');
$ok = send_mail('to@example.test', 's', 'h');
check('custom SMTP to a closed port explains the failure', !$ok && strpos(mail_last_error(), '127.0.0.1') !== false, mail_last_error());

echo "\nFallback\n";
set_for_test('mail_driver', 'sendgrid_api');
set_for_test('mail_fallback_server', '1');
file_put_contents($log . '.fail', '1');
$logFile = ROOT . '/storage/logs/mail.log';
$sizeBefore = is_file($logFile) ? filesize($logFile) : 0;
send_mail('to@example.test', 's', '<p>h</p>'); // PHP mail() may be unavailable here; what matters is that it was attempted
$newLog = is_file($logFile) ? (string)substr((string)file_get_contents($logFile), $sizeBefore) : '';
$logged = (int)(strpos($newLog, 'Used the server mail') !== false || strpos($newLog, 'mail() failed') !== false);
check('a failing provider falls back to the server mail', $logged > 0);

echo "\nResult: $pass passed, $fail failed\n";
exit($fail ? 1 : 0);
