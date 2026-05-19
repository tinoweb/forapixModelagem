<?php

namespace App\Mail;

/**
 * Responsável apenas por construir os corpos (HTML e texto) do e-mail
 * de recuperação de senha. Mantém o controller limpo e segue SRP.
 */
class PasswordResetMailBuilder
{
    public function buildHtml(string $userName, string $resetUrl, int $expirationMinutes): string
    {
        $name = e($userName);
        $url  = e($resetUrl);
        $exp  = (int) $expirationMinutes;

        return <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Recuperação de senha</title>
</head>
<body style="margin:0;padding:0;background:#0a0b1a;font-family:Arial,Helvetica,sans-serif;color:#e5e7eb;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#0a0b1a;padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="560" cellspacing="0" cellpadding="0"
                       style="background:#151a35;border-radius:16px;overflow:hidden;max-width:560px;width:100%;">
                    <tr>
                        <td style="background:linear-gradient(135deg,#7c3aed,#8b5cf6);padding:24px 32px;text-align:center;">
                            <h1 style="margin:0;color:#fff;font-size:22px;letter-spacing:2px;">APOSTACASADA</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;">
                            <h2 style="color:#fff;font-size:20px;margin:0 0 16px;">Olá, {$name}!</h2>
                            <p style="color:#cbd5e1;line-height:1.6;font-size:15px;margin:0 0 16px;">
                                Recebemos uma solicitação para redefinir a senha da sua conta na <strong>ApostaCasada</strong>.
                            </p>
                            <p style="color:#cbd5e1;line-height:1.6;font-size:15px;margin:0 0 24px;">
                                Clique no botão abaixo para criar uma nova senha. Este link é válido por <strong>{$exp} minutos</strong>.
                            </p>
                            <table role="presentation" cellspacing="0" cellpadding="0" style="margin:0 auto;">
                                <tr>
                                    <td align="center" style="border-radius:12px;background:#7c3aed;">
                                        <a href="{$url}"
                                           style="display:inline-block;padding:14px 28px;font-size:15px;color:#fff;text-decoration:none;font-weight:bold;border-radius:12px;">
                                            Redefinir minha senha
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            <p style="color:#94a3b8;font-size:13px;margin:32px 0 8px;">
                                Se o botão não funcionar, copie e cole o link abaixo no navegador:
                            </p>
                            <p style="word-break:break-all;font-size:12px;margin:0 0 24px;">
                                <a href="{$url}" style="color:#a78bfa;">{$url}</a>
                            </p>
                            <hr style="border:0;border-top:1px solid #1f2447;margin:24px 0;">
                            <p style="color:#94a3b8;font-size:12px;line-height:1.6;margin:0;">
                                Se você não solicitou esta recuperação, pode ignorar este e-mail com segurança —
                                sua senha permanecerá inalterada.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="background:#0f1226;padding:16px 32px;text-align:center;">
                            <p style="color:#64748b;font-size:11px;margin:0;">
                                © ApostaCasada — Apostas online. Não responda a este e-mail.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }

    public function buildText(string $userName, string $resetUrl, int $expirationMinutes): string
    {
        return "Olá, {$userName}!\n\n"
            . "Recebemos uma solicitação para redefinir a senha da sua conta na ApostaCasada.\n"
            . "Acesse o link abaixo para criar uma nova senha (válido por {$expirationMinutes} minutos):\n\n"
            . "{$resetUrl}\n\n"
            . "Se você não solicitou esta recuperação, ignore este e-mail.\n\n"
            . "— Equipe ApostaCasada";
    }
}
