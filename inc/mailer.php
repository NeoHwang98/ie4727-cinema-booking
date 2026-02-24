<?php
// Local mail sender for XAMPP Mercury

function mail_from_address(): string {
    // Optional override via environment; default to f31 account
    $envFrom = getenv('MAIL_FROM');
    if ($envFrom && filter_var($envFrom, FILTER_VALIDATE_EMAIL)) return $envFrom;
    return 'f31ee@localhost';
}

function mail_to_address(string $fallback): string {
    // Optional override via environment; default to f32 account for local testing
    $envTo = getenv('MAIL_TO');
    if ($envTo && filter_var($envTo, FILTER_VALIDATE_EMAIL)) return $envTo;
    return 'f32ee@localhost';
}

function send_mail_smtp(string $to, string $subject, string $body): bool {
    // Kept function name for compatibility; routes to local mail.
    $from = mail_from_address();
    $to = mail_to_address($to);
    // Ensure Windows SMTP settings are present for mail()
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        @ini_set('SMTP', 'localhost');
        @ini_set('smtp_port', '25');
        @ini_set('sendmail_from', $from);
    }
    $headers = "From: $from\r\n".
               "MIME-Version: 1.0\r\n".
               "Content-Type: text/plain; charset=UTF-8\r\n";
    return @mail($to, $subject, $body, $headers);
}

// Send HTML email with inline images (cid)
// $images: array of [ 'cid' => ['data' => binary, 'type' => 'image/png', 'name' => 'qr.png'] ]
function send_mail_html(string $to, string $subject, string $html, array $images = []): bool {
    $from = mail_from_address();
    $to = mail_to_address($to);
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        @ini_set('SMTP', 'localhost');
        @ini_set('smtp_port', '25');
        @ini_set('sendmail_from', $from);
    }

    $boundary = '=_mime_'.md5(uniqid((string)mt_rand(), true));
    $headers = [
        'From: '.$from,
        'MIME-Version: 1.0',
        'Content-Type: multipart/related; boundary="'.$boundary.'"'
    ];

    $body = '';
    // HTML part
    $body .= "--$boundary\r\n";
    $body .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
    $body .= $html."\r\n";

    // Inline images
    foreach ($images as $cid => $img) {
        $type = $img['type'] ?? 'image/png';
        $name = $img['name'] ?? ($cid.'.png');
        $data = $img['data'] ?? '';
        if ($data === '') continue;
        $body .= "--$boundary\r\n";
        $body .= "Content-Type: $type; name=\"$name\"\r\n";
        $body .= "Content-Transfer-Encoding: base64\r\n";
        $body .= "Content-ID: <{$cid}>\r\n";
        $body .= "Content-Disposition: inline; filename=\"$name\"\r\n\r\n";
        $body .= chunk_split(base64_encode($data));
    }

    $body .= "--$boundary--\r\n";

    return @mail($to, $subject, $body, implode("\r\n", $headers));
}

?>
