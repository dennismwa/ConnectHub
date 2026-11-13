<?php
/**
 * E-mail: PHP mail(), a small built-in SMTP client, or a log driver for testing.
 */

declare(strict_types=1);

/** SMTP providers with a built-in server address. Key => [label, host, port, security, note]. */
function mail_smtp_presets(): array
{
    return [
        'custom' => ['Another SMTP server (type the details)', '', 587, 'tls', ''],
        'gmail' => ['Gmail or Google Workspace', 'smtp.gmail.com', 587, 'tls', 'Use your full address as the username and an app password (not your normal password).'],
        'outlook' => ['Outlook or Microsoft 365', 'smtp.office365.com', 587, 'tls', 'Use your full address and password. SMTP sending must be allowed on the mailbox.'],
        'zoho' => ['Zoho Mail', 'smtp.zoho.com', 587, 'tls', 'Use your full address and an app password.'],
        'brevo' => ['Brevo (Sendinblue) SMTP', 'smtp-relay.brevo.com', 587, 'tls', 'Username is your Brevo login, password is the SMTP key.'],
        'sendgrid' => ['SendGrid SMTP', 'smtp.sendgrid.net', 587, 'tls', 'Username is the word apikey and the password is your API key.'],
        'mailgun' => ['Mailgun SMTP', 'smtp.mailgun.org', 587, 'tls', 'Username and password come from Sending, Domain settings, SMTP.'],
        'mailjet' => ['Mailjet SMTP', 'in-v3.mailjet.com', 587, 'tls', 'Username is the API key and the password is the secret key.'],
        'postmark' => ['Postmark SMTP', 'smtp.postmarkapp.com', 587, 'tls', 'Username and password are both the server token.'],
        'ses' => ['Amazon SES SMTP', 'email-smtp.{region}.amazonaws.com', 587, 'tls', 'Use the SMTP credentials from SES, not your AWS login. Set the region below.'],
    ];
}

/** Providers that are sent over their web API instead of SMTP. Key => [label, note]. */
function mail_api_providers(): array
{
    return [
        'sendgrid_api' => ['SendGrid (API key)', 'Create an API key with "Mail Send" permission. The From address must be a verified sender.'],
        'mailgun_api' => ['Mailgun (API key)', 'Needs your sending domain and the private API key.'],
        'brevo_api' => ['Brevo (API key)', 'Use a v3 API key. The From address must be a verified sender.'],
        'postmark_api' => ['Postmark (server token)', 'Use the server token. The From address must be a verified signature.'],
        'resend_api' => ['Resend (API key)', 'The From address must be on a domain you verified in Resend.'],
    ];
}

/** Every choice for the "Method" setting, for the admin form. */
function mail_driver_options(): array
{
    $o = ['mail' => 'Server default (PHP mail, works on most hosting)', 'smtp' => 'SMTP (Gmail, Outlook, Zoho, Brevo and others)'];
    foreach (mail_api_providers() as $k => $v) {
        $o[$k] = $v[0];
    }
    $o['log'] = 'Do not send, write to the log (testing)';
    return $o;
}

/** Why the last send failed, in words an admin can act on. */
function mail_last_error(?string $set = null): string
{
    static $err = '';
    if ($set !== null) {
        $err = $set;
    }
    return $err;
}

/** Send an HTML e-mail. Returns true on success. */
function send_mail(string $to, string $subject, string $html, string $text = ''): bool
{
    $subject = host_labels_swap($subject);
    $html = host_labels_swap_html($html);
    $text = host_labels_swap($text);
    mail_last_error('');
    if (!valid_email($to)) {
        mail_last_error('That email address is not valid.');
        return false;
    }
    $fromEmail = (string)setting('mail_from_email') ?: 'no-reply@' . preg_replace('/^www\./', '', (string)parse_url(site_origin(), PHP_URL_HOST));
    $fromName = (string)setting('mail_from_name') ?: site_name();
    if ($text === '') {
        $text = trim(html_entity_decode(strip_tags(preg_replace('#<(br|/p|/h\d|/li|/tr)>#i', "\n", $html) ?? $html), ENT_QUOTES, 'UTF-8'));
    }
    $driver = (string)setting('mail_driver');
    try {
        if ($driver === 'log') {
            return mail_write_log($to, $subject, $html);
        }
        $ok = false;
        if ($driver === 'smtp') {
            $ok = mail_smtp($to, $subject, $html, $text, $fromEmail, $fromName);
        } elseif (isset(mail_api_providers()[$driver])) {
            $ok = mail_via_api($driver, $to, $subject, $html, $text, $fromEmail, $fromName);
        } else {
            $ok = mail_server_default($to, $subject, $html, $text, $fromEmail, $fromName);
        }
        if (!$ok && $driver !== 'mail' && (int)setting('mail_fallback_server')) {
            $first = mail_last_error();
            $ok = mail_server_default($to, $subject, $html, $text, $fromEmail, $fromName);
            if ($ok) {
                app_log('mail', 'Used the server mail as a fallback because ' . $driver . ' failed: ' . $first);
            }
        }
        return $ok;
    } catch (Throwable $e) {
        mail_last_error($e->getMessage());
        app_log('mail', 'send failed: ' . $e->getMessage() . ' to=' . $to);
        return false;
    }
}

/** PHP mail(): the mail program of the web server. */
function mail_server_default(string $to, string $subject, string $html, string $text, string $fromEmail, string $fromName): bool
{
    $boundary = 'b' . random_token(12);
    $headers = [
        'From: ' . mail_encode_header($fromName) . ' <' . $fromEmail . '>',
        'Reply-To: ' . ((string)setting('support_email') ?: $fromEmail),
        'MIME-Version: 1.0',
        'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
    ];
    $body = mail_multipart($boundary, $text, $html);
    $ok = @mail($to, mail_encode_header($subject), $body, implode("\r\n", $headers));
    if (!$ok) {
        mail_last_error('The server mail program refused the message. Many hosts block it, so an SMTP or API provider is more reliable.');
        app_log('mail', 'mail() failed for ' . $to . ' subject=' . $subject);
    }
    return $ok;
}

/** POST to an HTTP API. Returns [status code, body]. */
function mail_http_post(string $url, array $headers, string $body, ?string $basicAuth = null): array
{
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        $opts = [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $body, CURLOPT_HTTPHEADER => $headers, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20, CURLOPT_CONNECTTIMEOUT => 10];
        if ($basicAuth !== null) {
            $opts[CURLOPT_USERPWD] = $basicAuth;
        }
        if ((string)(getenv('CH_MAIL_API_INSECURE') ?: '') === '1') {
            $opts[CURLOPT_SSL_VERIFYPEER] = false;
            $opts[CURLOPT_SSL_VERIFYHOST] = 0;
        }
        curl_setopt_array($ch, $opts);
        $res = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($res === false) {
            return [0, $err];
        }
        return [$code, (string)$res];
    }
    if ($basicAuth !== null) {
        $headers[] = 'Authorization: Basic ' . base64_encode($basicAuth);
    }
    $ctx = stream_context_create(['http' => ['method' => 'POST', 'header' => implode("\r\n", $headers), 'content' => $body, 'timeout' => 20, 'ignore_errors' => true]]);
    $res = @file_get_contents($url, false, $ctx);
    $code = 0;
    foreach ($http_response_header ?? [] as $h) {
        if (preg_match('#^HTTP/\S+ (\d{3})#', $h, $m)) {
            $code = (int)$m[1];
        }
    }
    return [$code, $res === false ? 'The provider could not be reached.' : (string)$res];
}

/** Base address of the API providers; a test can point it elsewhere through the CH_MAIL_API_BASE environment variable. */
function mail_api_base(string $default): string
{
    $o = (string)(getenv('CH_MAIL_API_BASE') ?: '');
    return $o !== '' ? rtrim($o, '/') : $default;
}

function mail_via_api(string $driver, string $to, string $subject, string $html, string $text, string $fromEmail, string $fromName): bool
{
    $key = trim((string)setting('mail_api_key'));
    if ($key === '') {
        mail_last_error('The API key is empty. Paste it under Email, Sending.');
        return false;
    }
    $json = ['Content-Type: application/json', 'Accept: application/json'];
    $replyTo = (string)setting('support_email') ?: $fromEmail;
    switch ($driver) {
        case 'sendgrid_api':
            $payload = ['personalizations' => [['to' => [['email' => $to]]]], 'from' => ['email' => $fromEmail, 'name' => $fromName], 'reply_to' => ['email' => $replyTo],
                'subject' => $subject, 'content' => [['type' => 'text/plain', 'value' => $text], ['type' => 'text/html', 'value' => $html]]];
            [$code, $res] = mail_http_post(mail_api_base('https://api.sendgrid.com') . '/v3/mail/send', array_merge($json, ['Authorization: Bearer ' . $key]), json_encode($payload));
            break;
        case 'brevo_api':
            $payload = ['sender' => ['email' => $fromEmail, 'name' => $fromName], 'to' => [['email' => $to]], 'replyTo' => ['email' => $replyTo], 'subject' => $subject, 'htmlContent' => $html, 'textContent' => $text];
            [$code, $res] = mail_http_post(mail_api_base('https://api.brevo.com') . '/v3/smtp/email', array_merge($json, ['api-key: ' . $key]), json_encode($payload));
            break;
        case 'postmark_api':
            $payload = ['From' => $fromName . ' <' . $fromEmail . '>', 'To' => $to, 'ReplyTo' => $replyTo, 'Subject' => $subject, 'HtmlBody' => $html, 'TextBody' => $text, 'MessageStream' => 'outbound'];
            [$code, $res] = mail_http_post(mail_api_base('https://api.postmarkapp.com') . '/email', array_merge($json, ['X-Postmark-Server-Token: ' . $key]), json_encode($payload));
            break;
        case 'resend_api':
            $payload = ['from' => $fromName . ' <' . $fromEmail . '>', 'to' => [$to], 'reply_to' => $replyTo, 'subject' => $subject, 'html' => $html, 'text' => $text];
            [$code, $res] = mail_http_post(mail_api_base('https://api.resend.com') . '/emails', array_merge($json, ['Authorization: Bearer ' . $key]), json_encode($payload));
            break;
        case 'mailgun_api':
            $domain = trim((string)setting('mail_mailgun_domain'));
            if ($domain === '') {
                mail_last_error('Enter your Mailgun sending domain under Email, Sending.');
                return false;
            }
            $base = (string)setting('mail_mailgun_region') === 'eu' ? 'https://api.eu.mailgun.net' : 'https://api.mailgun.net';
            $fields = http_build_query(['from' => $fromName . ' <' . $fromEmail . '>', 'to' => $to, 'subject' => $subject, 'text' => $text, 'html' => $html, 'h:Reply-To' => $replyTo]);
            [$code, $res] = mail_http_post(mail_api_base($base) . '/v3/' . rawurlencode($domain) . '/messages', ['Content-Type: application/x-www-form-urlencoded'], $fields, 'api:' . $key);
            break;
        default:
            mail_last_error('Unknown email provider.');
            return false;
    }
    if ($code >= 200 && $code < 300) {
        return true;
    }
    $detail = $code === 0 ? 'The provider could not be reached (' . trim($res) . ').' : 'The provider answered ' . $code . ': ' . mb_substr(trim(preg_replace('/\s+/', ' ', strip_tags($res)) ?? ''), 0, 200);
    if ($code === 401 || $code === 403) {
        $detail .= ' Check the API key and that the From address is verified.';
    }
    mail_last_error($detail);
    app_log('mail', $driver . ' failed: ' . $detail);
    return false;
}

/** Send a test message and say what happened. Returns [ok, message]. */
function mail_send_test(string $to): array
{
    $driver = (string)setting('mail_driver');
    $labels = mail_driver_options();
    $subject = 'Test email from ' . site_name();
    $html = render_email('notification', ['subject' => $subject, 'name' => 'there', 'title' => 'Email is working', 'body' => 'This test was sent with: ' . ($labels[$driver] ?? $driver) . '. You can ignore it.']);
    $ok = send_mail($to, $subject, $html);
    if ($ok) {
        return [true, $driver === 'log' ? 'Saved to the log instead of sending, because the method is set to testing. Look in storage/mail.' : 'Sent to ' . $to . '. Check the inbox and the spam folder.'];
    }
    return [false, mail_last_error() ?: 'The message was not accepted. See Activity log, system log for details.'];
}

/**
 * Tell the site owner something happened (a gift was sent, a payout was requested, a photo needs review).
 * $kind is one of the admin_alert_* settings. Goes to the alert addresses by email; never throws.
 */
function admin_alert(string $kind, string $title, string $body, string $path = '/admin'): bool
{
    try {
        if (!(int)setting('admin_alert_' . $kind)) {
            return false;
        }
        $to = admin_alert_recipients();
        if (!$to) {
            return false;
        }
        // Inside a database transaction (a payment being confirmed) the email waits until the page has been answered,
        // so a slow mail server never holds a lock or delays the member.
        if (db()->inTransaction()) {
            static $queued = false;
            static $queue = [];
            $queue[] = [$kind, $title, $body, $path];
            if (!$queued) {
                $queued = true;
                register_shutdown_function(function () use (&$queue) {
                    foreach ($queue as [$k, $t, $b, $p]) {
                        admin_alert($k, $t, $b, $p);
                    }
                });
            }
            return true;
        }
        $sent = false;
        foreach ($to as $addr) {
            $sent = send_template_mail($addr, '[' . site_name() . '] ' . $title, 'admin_alert', ['title' => $title, 'body' => $body, 'url' => abs_url($path)]) || $sent;
        }
        return $sent;
    } catch (Throwable $e) {
        app_log('mail', 'admin_alert failed: ' . $e->getMessage());
        return false;
    }
}

/** Addresses that receive admin alerts: the alert setting, or the support address when it is empty. */
function admin_alert_recipients(): array
{
    $raw = (string)setting('admin_alert_email');
    $list = array_filter(array_map('trim', preg_split('/[,;\s]+/', $raw) ?: []), 'valid_email');
    if (!$list) {
        $fallback = (string)setting('support_email');
        $list = valid_email($fallback) ? [$fallback] : [];
    }
    return array_values(array_unique($list));
}

function mail_encode_header(string $s): string
{
    return preg_match('/[^\x20-\x7E]/', $s) ? '=?UTF-8?B?' . base64_encode($s) . '?=' : $s;
}

function mail_multipart(string $boundary, string $text, string $html): string
{
    return "--$boundary\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
        . chunk_split(base64_encode($text))
        . "--$boundary\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
        . chunk_split(base64_encode($html))
        . "--$boundary--";
}

function mail_write_log(string $to, string $subject, string $html): bool
{
    $dir = ROOT . '/storage/mail';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    $file = $dir . '/' . date('Ymd-His') . '-' . substr(random_token(3), 0, 6) . '.html';
    @file_put_contents($file, "<!-- To: $to\n     Subject: $subject -->\n" . $html);
    app_log('mail', 'LOGGED to=' . $to . ' subject=' . $subject . ' file=' . basename($file));
    return true;
}

function mail_smtp(string $to, string $subject, string $html, string $text, string $fromEmail, string $fromName): bool
{
    $host = (string)setting('smtp_host');
    $port = (int)setting('smtp_port') ?: 587;
    $secure = (string)setting('smtp_secure');
    $user = (string)setting('smtp_user');
    $pass = (string)setting('smtp_pass');
    $preset = mail_smtp_presets()[(string)setting('smtp_preset')] ?? null;
    if ($preset && (string)setting('smtp_preset') !== 'custom') {
        $host = str_replace('{region}', preg_replace('/[^a-z0-9-]/', '', strtolower((string)setting('mail_ses_region'))) ?: 'us-east-1', $preset[1]);
        $port = (int)$preset[2];
        $secure = (string)$preset[3];
    }
    if ($host === '') {
        mail_last_error('The SMTP server address is empty. Pick a provider or type the server under Email, Sending.');
        app_log('mail', 'SMTP host not set');
        return false;
    }
    $remote = ($secure === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port;
    $ctx = stream_context_create(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true, 'SNI_enabled' => true]]);
    $fp = @stream_socket_client($remote, $errno, $errstr, 12, STREAM_CLIENT_CONNECT, $ctx);
    if (!$fp) {
        mail_last_error("Could not connect to $host on port $port ($errstr). Check the server name, the port and the encryption.");
        app_log('mail', "SMTP connect failed: $errstr ($errno)");
        return false;
    }
    stream_set_timeout($fp, 12);
    $read = function () use ($fp): string {
        $out = '';
        while (($line = fgets($fp, 515)) !== false) {
            $out .= $line;
            if (strlen($line) < 4 || $line[3] === ' ') {
                break;
            }
        }
        return $out;
    };
    $cmd = function (string $c, array $expect) use ($fp, $read): bool {
        fwrite($fp, $c . "\r\n");
        $r = $read();
        foreach ($expect as $e) {
            if (strpos($r, (string)$e) === 0) {
                return true;
            }
        }
        app_log('mail', 'SMTP unexpected reply to "' . (strpos($c, 'AUTH') === 0 || strlen($c) > 60 ? substr($c, 0, 10) . '…' : $c) . '": ' . trim($r));
        mail_last_error('The mail server said: ' . mb_substr(trim($r), 0, 200) . (strlen($c) > 60 || !preg_match('/^(EHLO|STARTTLS|MAIL|RCPT|DATA)/', $c) ? ' (login failed, check the username and password)' : ''));
        return false;
    };
    $domain = preg_replace('/^www\./', '', (string)parse_url(site_origin(), PHP_URL_HOST)) ?: 'localhost';
    $greet = $read();
    if (strpos($greet, '220') !== 0) {
        fclose($fp);
        mail_last_error('The mail server did not greet us properly: ' . mb_substr(trim($greet), 0, 120));
        return false;
    }
    $ok = $cmd('EHLO ' . $domain, ['250']);
    if ($ok && $secure === 'tls') {
        $ok = $cmd('STARTTLS', ['220']) && stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        $ok = $ok && $cmd('EHLO ' . $domain, ['250']);
    }
    if ($ok && $user !== '') {
        $ok = $cmd('AUTH LOGIN', ['334']) && $cmd(base64_encode($user), ['334']) && $cmd(base64_encode($pass), ['235']);
    }
    $ok = $ok && $cmd('MAIL FROM:<' . $fromEmail . '>', ['250']) && $cmd('RCPT TO:<' . $to . '>', ['250', '251']) && $cmd('DATA', ['354']);
    if ($ok) {
        $boundary = 'b' . random_token(12);
        $msg = 'Date: ' . date('r') . "\r\n"
            . 'From: ' . mail_encode_header($fromName) . ' <' . $fromEmail . ">\r\n"
            . 'To: <' . $to . ">\r\n"
            . 'Subject: ' . mail_encode_header($subject) . "\r\n"
            . 'Message-ID: <' . random_token(10) . '@' . $domain . ">\r\n"
            . "MIME-Version: 1.0\r\n"
            . 'Content-Type: multipart/alternative; boundary="' . $boundary . "\"\r\n\r\n"
            . mail_multipart($boundary, $text, $html);
        $msg = preg_replace('/^\./m', '..', str_replace(["\r\n", "\r"], "\n", $msg));
        $msg = str_replace("\n", "\r\n", $msg);
        fwrite($fp, $msg . "\r\n.\r\n");
        $r = $read();
        $ok = strpos($r, '250') === 0;
        if (!$ok) {
            mail_last_error('The mail server rejected the message: ' . mb_substr(trim($r), 0, 200));
            app_log('mail', 'SMTP DATA rejected: ' . trim($r));
        }
    }
    @fwrite($fp, "QUIT\r\n");
    fclose($fp);
    return $ok;
}

/**
 * Render templates/emails/{name}.php inside the shared layout and send it.
 * Template variables are passed in $vars; the template echoes the inner HTML.
 */
function send_template_mail(string $to, string $subject, string $template, array $vars = []): bool
{
    return send_mail($to, $subject, render_email($template, $vars + ['subject' => $subject]));
}

function render_email(string $template, array $vars = []): string
{
    $file = ROOT . '/templates/emails/' . preg_replace('/[^a-z0-9_-]/i', '', $template) . '.php';
    if (!is_file($file)) {
        $inner = '<p>' . e($vars['body'] ?? '') . '</p>';
    } else {
        extract($vars, EXTR_SKIP);
        ob_start();
        include $file;
        $inner = (string)ob_get_clean();
    }
    $content = $inner;
    extract($vars, EXTR_SKIP);
    ob_start();
    include ROOT . '/templates/emails/_layout.php';
    return (string)ob_get_clean();
}
