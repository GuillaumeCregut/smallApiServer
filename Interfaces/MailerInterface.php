<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Interfaces;

interface MailerInterface
{
    /**
     * Envoie un email
     *
     * @param string $to Destinataire
     * @param string $subject Sujet de l'email
     * @param string $body Contenu de l'email
     * @return bool Retourne true si l'email a été envoyé avec succès, false sinon
     * @throws Exception Si une erreur survient lors de l'envoi de l'email
     */
    public function sendEmail(string $to, string $subject, string $body): bool;
}