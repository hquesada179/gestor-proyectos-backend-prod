<?php

namespace App\Notifications;

use App\Services\BrevoMailService;
use Illuminate\Notifications\Notification;

class ResetPasswordBrevoNotification extends Notification
{
    public function __construct(private readonly string $token) {}

    public function via(object $notifiable): array
    {
        return [];
    }

    public function send(object $notifiable): void
    {
        $resetUrl = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        $userName    = $notifiable->name ?? 'Usuario';
        $fromName    = config('mail.from.name', 'Scrumter');
        $appUrl      = config('app.url', 'https://scrumter.io');
        $htmlContent = $this->buildHtml($userName, $resetUrl, $appUrl);

        app(BrevoMailService::class)->send(
            $notifiable->getEmailForPasswordReset(),
            'Recupera tu contraseña en Scrumter',
            $htmlContent,
        );
    }

    private function buildHtml(string $userName, string $resetUrl, string $appUrl): string
    {
        $escapedUrl  = htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8');
        $escapedName = htmlspecialchars($userName, ENT_QUOTES, 'UTF-8');

        return <<<HTML
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Recuperar contraseña — Scrumter</title>
            <style>
                body { margin:0; padding:0; background-color:#f4f6f9; font-family:'Segoe UI',Arial,sans-serif; color:#333333; }
                .wrapper { max-width:600px; margin:40px auto; background:#ffffff; border-radius:10px; overflow:hidden; box-shadow:0 2px 12px rgba(0,0,0,0.08); }
                .header { background:#1a1a2e; padding:32px 40px; text-align:center; }
                .header h1 { margin:0; font-size:28px; font-weight:700; color:#ffffff; letter-spacing:1px; }
                .header span { color:#4f8ef7; }
                .body { padding:40px; }
                .body p { font-size:15px; line-height:1.7; margin:0 0 16px; }
                .btn-wrapper { text-align:center; margin:32px 0; }
                .btn { display:inline-block; background:#4f8ef7; color:#ffffff !important; text-decoration:none; font-size:16px; font-weight:600; padding:14px 36px; border-radius:8px; letter-spacing:0.5px; }
                .btn:hover { background:#3a7ae0; }
                .alt-link { font-size:13px; color:#666666; word-break:break-all; margin-top:24px; }
                .alt-link a { color:#4f8ef7; }
                .divider { border:none; border-top:1px solid #e8ecf0; margin:32px 0; }
                .footer { background:#f8f9fb; padding:24px 40px; text-align:center; font-size:12px; color:#999999; }
                .footer a { color:#4f8ef7; text-decoration:none; }
                .expiry { background:#fff8e1; border-left:4px solid #ffc107; padding:12px 16px; border-radius:4px; font-size:13px; color:#7a6200; margin-top:24px; }
            </style>
        </head>
        <body>
            <div class="wrapper">
                <div class="header">
                    <h1>Scrum<span>ter</span></h1>
                </div>
                <div class="body">
                    <p>Hola, <strong>{$escapedName}</strong></p>
                    <p>Recibimos una solicitud para restablecer la contraseña de tu cuenta en Scrumter. Si fuiste tú, haz clic en el botón a continuación.</p>
                    <div class="btn-wrapper">
                        <a href="{$escapedUrl}" class="btn">Restablecer contraseña</a>
                    </div>
                    <div class="expiry">
                        Este enlace expira en <strong>60 minutos</strong>. Si no solicitaste esto, puedes ignorar este correo con seguridad.
                    </div>
                    <hr class="divider">
                    <p class="alt-link">
                        Si el botón no funciona, copia y pega este enlace en tu navegador:<br>
                        <a href="{$escapedUrl}">{$escapedUrl}</a>
                    </p>
                </div>
                <div class="footer">
                    &copy; 2025 <a href="{$appUrl}">Scrumter</a> &mdash; Gestión ágil de proyectos<br>
                    Si tienes dudas, contáctanos en <a href="mailto:soporte@scrumter.io">soporte@scrumter.io</a>
                </div>
            </div>
        </body>
        </html>
        HTML;
    }
}
