<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

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
        $config->smtpHost = $env->get('SMTP_HOST');
        $config->smtpPort = (int)$env->get('SMTP_PORT', 587);
        $config->smtpUser = $env->get('SMTP_USER');
        $config->smtpPass = $env->get('SMTP_PASS');
        $config->fromEmail = $env->get('FROM_EMAIL');
        $config->fromName = $env->get('FROM_NAME');   
        return $config;
    }
}