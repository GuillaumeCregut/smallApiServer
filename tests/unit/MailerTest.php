<?php

use PHPUnit\Framework\TestCase;
use App\Services\Mailer\Mailer;
use App\Services\Mailer\MailConfig;

class MailerTest extends TestCase
{
    private function makeConfig(
        string $fromEmail = 'no-reply@example.com',
        string $fromName = 'Example',
        string $smtpHost = 'localhost',
        int $smtpPort = 25,
        string $smtpUser = 'user',
        string $smtpPass = 'pass'
    ): MailConfig {
        $cfg = new MailConfig();
        $cfg->fromEmail = $fromEmail;
        $cfg->fromName = $fromName;
        $cfg->smtpHost = $smtpHost;
        $cfg->smtpPort = $smtpPort;
        $cfg->smtpUser = $smtpUser;
        $cfg->smtpPass = $smtpPass;
        return $cfg;
    }

    public function testWhenAllIsOK(): void
    {
        $config = $this->makeConfig();
        $mailer = new Mailer($config);
        // sanitizeValues() is called before network operations and will throw
        $this->assertFalse($mailer->sendEmail('to@example.com', 'Subject', 'Body'));
    }

    public function testThrowsWhenFromEmailIsInvalid(): void
    {
        $config = $this->makeConfig($fromEmail="bad\nemail");
        $mailer = new Mailer($config);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Email invalide');
        // sanitizeValues() is called before network operations and will throw
        $mailer->sendEmail('to@example.com', 'Subject', 'Body');
    }

    public function testThrowsWhenToEmailIsInvalid(): void
    {
        $config = $this->makeConfig();
        $mailer = new Mailer($config);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Email invalide');

        $mailer->sendEmail("to@@example..com", 'Subject', 'Body');
    }

    public function testThrowsWhenCcContainsInvalidEmail(): void
    {
        $config = $this->makeConfig();
        $mailer = new Mailer($config);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Email invalide');
        $mailer->sendEmail('to@example.com', 'Subject', 'Body', true, [], [], ['ok@example.com', 'not-an-email'], []);
    }

    public function testThrowsWhenBccContainsInvalidEmail(): void
    {
        $config = $this->makeConfig();
        $mailer = new Mailer($config);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Email invalide');

        $mailer->sendEmail('to@example.com', 'Subject', 'Body', true, [], [], [], ['also-ok@example.com', 'invalid@']);
    }

    public function testThrowsWhenReplyToIsInvalid(): void
    {
        $config = $this->makeConfig();
        $mailer = new Mailer($config);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Email invalide');
        $mailer->sendEmail('to@example.com', 'Subject', 'Body', true, [], [], [], [], 'invalid@');
    }

    public function testThrowsWhenAttachmentDoesNotExist(): void
    {
        $config = $this->makeConfig();
        $mailer = new Mailer($config);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Fichier introuvable'); 
        $mailer->sendEmail('to@example.com', 'Subject', 'Body', true, ['Z:/definitely/not/here/xyz.bin']);
    }
    public function testThrowsWhenEmailIsTooLong(): void
    {
        $config = $this->makeConfig();
        $mailer = new Mailer($config);
        $local = str_repeat('a', 255);
        $domain = str_repeat('b', 256);
        $veryLongEmail = $local . '@' . $domain . '.com';
        $this->expectException(\Exception::class);
        
        $this->expectExceptionMessage('Email trop long');
        $mailer->sendEmail($veryLongEmail, 'Subject', 'Body');
    }
}
