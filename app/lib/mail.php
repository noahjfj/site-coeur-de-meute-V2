<?php
if (!defined('CDM')) { http_response_code(404); exit; }

/**
 * Envoie un e-mail aux couleurs de Coeur de Meute : une version HTML (jolie) et une version texte
 * (lue par les messageries qui n'affichent pas le HTML). Retourne true si l'hébergeur l'a accepté.
 */
function send_mail(string $to, string $subject, string $body): bool
{
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    $clean = static fn(string $s): string => trim(str_replace(["\r", "\n"], ' ', $s)); // aucune ligne d'en-tête injectée
    $from = (string) config('mail_from');
    $fromName = $clean((string) config('mail_from_name', 'Coeur de Meute'));
    $subject = $clean($subject);
    $html = mail_html($subject, $body);
    $text = $body . "\n\n--\nCoeur de Meute — pension et éducation canine\n" . config('base_url') . "\n"
        . "Cet e-mail a été envoyé automatiquement, merci de ne pas y répondre directement.";

    if (config('mail_mode') === 'log') { // utilisé seulement pour les tests
        $line = json_encode(['to' => $to, 'subject' => $subject, 'body' => $text], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
        file_put_contents((string) config('mail_log'), $line, FILE_APPEND | LOCK_EX);
        if (config('mail_html_dir')) {
            file_put_contents(rtrim((string) config('mail_html_dir'), '/') . '/' . date('His') . '-' . bin2hex(random_bytes(3)) . '.html', $html);
        }
        return true;
    }

    $boundary = 'cdm-' . bin2hex(random_bytes(12));
    $headers = [
        'From' => mb_encode_mimeheader($fromName, 'UTF-8') . ' <' . $from . '>',
        'Reply-To' => (string) config('notify_email', $from),
        'MIME-Version' => '1.0',
        'Content-Type' => 'multipart/alternative; boundary="' . $boundary . '"',
    ];
    $message = "--$boundary\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
        . chunk_split(base64_encode($text))
        . "--$boundary\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
        . chunk_split(base64_encode($html))
        . "--$boundary--\r\n";
    $ok = mail($to, mb_encode_mimeheader($subject, 'UTF-8'), $message, $headers, '-f' . $from);
    if (!$ok) {
        error_log('[coeurdemeute] échec envoi e-mail à ' . $to);
    }
    return $ok;
}

/**
 * Met en page le texte d'un e-mail. Règles simples :
 *  - « Texte : https://… » seul sur sa ligne devient un bouton ;
 *  - les lignes qui commencent par « - » deviennent une liste ;
 *  - le reste est du texte (tout est échappé : rien de ce qu'a tapé un client ne devient du code).
 */
function mail_html(string $title, string $body): string
{
    $rust = '#a65a43';
    $ink = '#2f3a4a';
    $parts = [];
    $list = [];
    $flushList = static function () use (&$list, &$parts, $ink): void {
        if ($list) {
            $parts[] = '<ul style="margin:0 0 18px;padding-left:20px;color:' . $ink . ';">' . implode('', $list) . '</ul>';
            $list = [];
        }
    };
    foreach (preg_split('/\n{2,}/', trim($body)) as $block) {
        foreach (explode("\n", $block) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            if (str_starts_with($line, '- ')) {
                $list[] = '<li style="margin:0 0 6px;">' . h(substr($line, 2)) . '</li>';
                continue;
            }
            $flushList();
            if (preg_match('~^(.{2,80}?)\s*:\s*(https?://\S+)$~u', $line, $m)) {
                $parts[] = '<p style="margin:8px 0 18px;"><a href="' . h($m[2]) . '" style="display:inline-block;background:' . $rust
                    . ';color:#ffffff;text-decoration:none;font-weight:bold;padding:13px 26px;border-radius:999px;font-size:15px;">' . h($m[1]) . '</a></p>';
            } elseif (preg_match('~^https?://\S+$~', $line)) {
                $label = str_contains($line, 'confirmer.php') ? 'Confirmer mon adresse e-mail'
                    : (str_contains($line, 'reinitialiser.php') ? 'Choisir un nouveau mot de passe' : 'Ouvrir le lien');
                $parts[] = '<p style="margin:8px 0 6px;"><a href="' . h($line) . '" style="display:inline-block;background:' . $rust
                    . ';color:#ffffff;text-decoration:none;font-weight:bold;padding:13px 26px;border-radius:999px;font-size:15px;">' . $label . '</a></p>'
                    . '<p style="margin:0 0 18px;font-size:12px;color:#5b6676;word-break:break-all;">Si le bouton ne marche pas, copiez ce lien : ' . h($line) . '</p>';
            } else {
                $parts[] = '<p style="margin:0 0 14px;line-height:1.6;color:' . $ink . ';">' . h($line) . '</p>';
            }
        }
        $flushList();
    }
    $site = h((string) config('base_url'));
    return '<!doctype html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>' . h($title) . '</title></head>'
        . '<body style="margin:0;padding:0;background:#f7f3ee;font-family:\'Open Sans\',Helvetica,Arial,sans-serif;font-size:15px;">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f7f3ee;"><tr><td align="center" style="padding:28px 12px;">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border-radius:20px;overflow:hidden;">'
        . '<tr><td style="background:' . $ink . ';padding:26px 32px;">'
        . '<div style="font-family:Georgia,\'Libre Baskerville\',serif;font-size:26px;color:#ffffff;">Coeur <span style="font-style:italic;color:#d9a27f;">de</span> Meute</div>'
        . '<div style="font-size:11px;letter-spacing:3px;text-transform:uppercase;color:#c9a98a;margin-top:6px;">Pension &amp; éducation canine</div></td></tr>'
        . '<tr><td style="height:5px;line-height:5px;font-size:0;background:linear-gradient(90deg,#a65a43 0 40%,#c9a98a 40% 70%,#8ea2b5 70%);background-color:#a65a43;">&nbsp;</td></tr>'
        . '<tr><td style="padding:30px 32px 14px;">'
        . '<h1 style="font-family:Georgia,\'Libre Baskerville\',serif;font-weight:normal;font-size:22px;line-height:1.3;color:' . $ink . ';margin:0 0 20px;">' . h($title) . '</h1>'
        . implode('', $parts)
        . '</td></tr>'
        . '<tr><td style="padding:18px 32px 26px;border-top:1px solid #ece6de;font-size:12px;line-height:1.6;color:#5b6676;">'
        . 'Sérénité · Sécurité · Santé<br><a href="' . $site . '" style="color:' . $rust . ';">' . $site . '</a><br>'
        . 'Cet e-mail a été envoyé automatiquement, merci de ne pas y répondre directement.</td></tr>'
        . '</table></td></tr></table></body></html>';
}

function url(string $path): string
{
    return rtrim((string) config('base_url'), '/') . $path;
}

function greet(array $user): string
{
    return 'Bonjour ' . $user['first_name'] . ",\n\n";
}
