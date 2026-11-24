<?php
if (!defined('CDM')) { http_response_code(404); exit; }

/** Envoie un e-mail texte simple. Retourne true si l'hébergeur l'a accepté. */
function send_mail(string $to, string $subject, string $body): bool
{
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    $clean = static fn(string $s): string => trim(str_replace(["\r", "\n"], ' ', $s)); // aucune ligne d'en-tête injectée
    $from = (string) config('mail_from');
    $fromName = $clean((string) config('mail_from_name', 'Coeur de Meute'));
    $subject = $clean($subject);
    $body .= "\n\n--\nCoeur de Meute — pension et éducation canine\n" . config('base_url') . "\n"
        . "Cet e-mail a été envoyé automatiquement, merci de ne pas y répondre directement.";

    if (config('mail_mode') === 'log') { // utilisé seulement pour les tests
        $line = json_encode(['to' => $to, 'subject' => $subject, 'body' => $body], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
        file_put_contents((string) config('mail_log'), $line, FILE_APPEND | LOCK_EX);
        return true;
    }

    $headers = [
        'From' => mb_encode_mimeheader($fromName, 'UTF-8') . ' <' . $from . '>',
        'Reply-To' => (string) config('notify_email', $from),
        'MIME-Version' => '1.0',
        'Content-Type' => 'text/plain; charset=UTF-8',
        'Content-Transfer-Encoding' => '8bit',
    ];
    $ok = mail($to, mb_encode_mimeheader($subject, 'UTF-8'), $body, $headers, '-f' . $from);
    if (!$ok) {
        error_log('[coeurdemeute] échec envoi e-mail à ' . $to);
    }
    return $ok;
}

function url(string $path): string
{
    return rtrim((string) config('base_url'), '/') . $path;
}

function greet(array $user): string
{
    return 'Bonjour ' . $user['first_name'] . ",\n\n";
}
