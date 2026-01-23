<?php

namespace App\Services\Mailer;

use App\Kernel\GetEnvDatas;

class MailConfig
{
    // Configuration du serveur SMTP
    public string $smtpHost;
    public int $smtpPort;
    public string $smtpUser;
    public string $smtpPass;
    public string $fromEmail;
    public string $fromName = '';

    public static function getInstance(): MailConfig
    {
        $config = new MailConfig();
        // Vous pouvez ajuster ces valeurs selon vos besoins
        $env = GetEnvDatas::getEnvInstance();
        $config->smtpHost = $env->get('smtp_host');
        $config->smtpPort = (int)$env->get('smtp_port', 587);
        $config->smtpUser = $env->get('smtp_user');
        $config->smtpPass = $env->get('smtp_pass');
        $config->fromEmail = $env->get('from_email');
        $config->fromName = $env->get('from_name');   
        return $config;
    }
}