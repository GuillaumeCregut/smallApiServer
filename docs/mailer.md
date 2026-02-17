# Mailer Documentation

## Overview

The `Mailer` class is a modern, secure email service that implements the SMTP protocol for sending emails. It provides comprehensive features including HTML support, attachments, CC/BCC recipients, custom headers, and robust input validation to prevent email injection attacks.

## Purpose

The Mailer serves to:
- **Send emails via SMTP** with full protocol compliance
- **Support multiple content types** (plain text and HTML)
- **Attach files** with size validation
- **Add recipients** through To, CC, and BCC fields
- **Sanitize inputs** to prevent email injection attacks
- **Handle TLS encryption** for secure SMTP connections
- **Encode headers** properly for international characters

## Class Structure

### Location

```
App\Services\Mailer\Mailer
```

### Namespace

```php
namespace App\Services\Mailer;
```

### Interfaces

- `App\Interfaces\MailerInterface` - Interface contract for mailer implementations

### Dependencies

- `MailConfig` - Configuration object for SMTP settings
- `Logger` - For error logging
- `GetEnvDatas` - For environment variable management

## Configuration

### MailConfig Class

The `MailConfig` class holds SMTP server configuration:

```php
<?php

namespace App\Services\Mailer;

class MailConfig
{
    public string $smtpHost;      // SMTP server hostname
    public int $smtpPort;         // SMTP port (typically 587 for TLS, 25 for unencrypted)
    public string $smtpUser;      // SMTP authentication username
    public string $smtpPass;      // SMTP authentication password
    public string $fromEmail;     // Sender email address
    public string $fromName = ''; // Sender display name (optional)
}
```

### Loading Configuration

#### From Environment Variables

```php
<?php

use App\Services\Mailer\MailConfig;

// Load configuration from environment variables
$config = MailConfig::getInstance();
// Looks for: smtp_host, smtp_port, smtp_user, smtp_pass, from_email, from_name
```

#### Manual Configuration

```php
<?php

use App\Services\Mailer\MailConfig;
use App\Services\Mailer\Mailer;

$config = new MailConfig();
$config->smtpHost = 'smtp.gmail.com';
$config->smtpPort = 587;
$config->smtpUser = 'your-email@gmail.com';
$config->smtpPass = 'your-app-password';
$config->fromEmail = 'noreply@example.com';
$config->fromName = 'My Application';

$mailer = new Mailer($config);
```

## Constructor

```php
public function __construct(
    MailConfig $config,
    ?bool $useTLS = true
)
```

### Parameters

- `config` (MailConfig): SMTP configuration object
- `useTLS` (bool, optional): Enable TLS encryption (default: true)

### Example

```php
<?php

use App\Services\Mailer\MailConfig;
use App\Services\Mailer\Mailer;

$config = new MailConfig();
$config->smtpHost = 'smtp.example.com';
$config->smtpPort = 587;
$config->smtpUser = 'user@example.com';
$config->smtpPass = 'password123';
$config->fromEmail = 'noreply@example.com';
$config->fromName = 'My App';

// With TLS (secure, default)
$mailer = new Mailer($config);

// Without TLS (unencrypted)
$mailer = new Mailer($config, false);
```

## Method Reference

### sendEmail()

Sends an email with optional attachments, CC, BCC, and custom headers.

```php
public function sendEmail(
    string|array $to,
    string $subject,
    string $body,
    bool $isHtml = true,
    array $attachments = [],
    array $headers = [],
    string|array $cc = [],
    string|array $bcc = [],
    string $replyTo = ''
): bool
```

### Parameters

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `$to` | string\|array | Required | Recipient email address(es) |
| `$subject` | string | Required | Email subject |
| `$body` | string | Required | Email body content |
| `$isHtml` | bool | true | Whether body is HTML (true) or plain text (false) |
| `$attachments` | array | [] | Array of file paths to attach |
| `$headers` | array | [] | Custom email headers |
| `$cc` | string\|array | [] | CC recipient(s) |
| `$bcc` | string\|array | [] | BCC recipient(s) |
| `$replyTo` | string | '' | Reply-To email address |

### Return Value

- **bool**: true if email sent successfully, false if connection/network error
- **throws Exception**: For validation errors (invalid email, missing file, etc.)

### Behavior

1. **Sanitizes all inputs** - Validates emails and removes control characters
2. **Connects to SMTP server** - Establishes TLS/TCP connection
3. **Authenticates** - Logs in with provided credentials
4. **Sends message** - Transmits email via SMTP protocol
5. **Disconnects** - Closes the connection
6. **Returns result** - true on success, false on network errors

## Basic Usage

### Simple Email

```php
<?php

use App\Services\Mailer\Mailer;
use App\Services\Mailer\MailConfig;

$config = MailConfig::getInstance();
$mailer = new Mailer($config);

$success = $mailer->sendEmail(
    'user@example.com',
    'Welcome!',
    '<h1>Hello World</h1><p>Welcome to our platform!</p>'
);

if ($success) {
    echo "Email sent successfully";
} else {
    echo "Failed to send email";
}
```

### Multiple Recipients

```php
<?php

$mailer->sendEmail(
    ['user1@example.com', 'user2@example.com'],  // Multiple To recipients
    'Newsletter',
    'This is our latest newsletter...',
    true,
    [],                                           // No attachments
    [],                                           // No custom headers
    ['manager@example.com'],                      // CC
    ['admin@example.com']                         // BCC
);
```

### With Attachments

```php
<?php

$success = $mailer->sendEmail(
    'user@example.com',
    'Your invoice',
    'Please find your invoice attached.',
    true,
    [
        '/var/www/invoices/invoice-001.pdf',
        '/var/www/receipts/receipt-001.pdf'
    ]
);
```

### Plain Text Email

```php
<?php

$success = $mailer->sendEmail(
    'user@example.com',
    'Important Notice',
    'This is a plain text email without any HTML formatting.',
    false  // isHtml = false for plain text
);
```

### With Custom Headers

```php
<?php

$headers = [
    'X-Priority' => '1',
    'X-MSMail-Priority' => 'High',
    'X-Mailer' => 'My Custom Mailer/1.0',
    'Keywords' => 'invoice, payment'
];

$success = $mailer->sendEmail(
    'user@example.com',
    'Urgent Payment',
    'Your payment is overdue...',
    true,
    [],
    $headers
);
```

### With Reply-To Address

```php
<?php

$success = $mailer->sendEmail(
    'customer@example.com',
    'Customer Support Response',
    'Thank you for contacting us...',
    true,
    [],
    [],
    [],
    [],
    'support@example.com'  // Reply-To
);
```

## Email Content

### HTML Emails

When `isHtml = true`, the mailer:
- Generates both HTML and plain text versions (multipart/alternative)
- Uses MIME encoding for proper display in all clients
- Supports UTF-8 encoding for international characters

```php
<?php

$htmlBody = <<<HTML
<html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; }
            .welcome { color: #333; }
        </style>
    </head>
    <body>
        <div class="welcome">
            <h1>Welcome to Our Service!</h1>
            <p>Your account has been created successfully.</p>
            <a href="https://example.com/verify">Click here to verify your email</a>
        </div>
    </body>
</html>
HTML;

$mailer->sendEmail(
    'newuser@example.com',
    'Welcome!',
    $htmlBody,
    true
);
```

### Plain Text Emails

When `isHtml = false`, the mailer sends plain text with quoted-printable encoding:

```php
<?php

$textBody = <<<TEXT
Welcome to Our Service!

Your account has been created successfully.

To verify your email, visit:
https://example.com/verify

Questions? Contact us at support@example.com
TEXT;

$mailer->sendEmail(
    'newuser@example.com',
    'Welcome!',
    $textBody,
    false
);
```

## Attachments

### Attaching Files

Files are validated before attachment:
- Must exist and be readable
- Must not exceed maximum size limit
- Total size must not exceed `MAX_ATTACHMENT` limit

```php
<?php

$files = [
    '/var/www/documents/report.pdf',
    '/var/www/images/chart.png',
    '/var/www/data/export.xlsx'
];

$success = $mailer->sendEmail(
    'recipient@example.com',
    'Monthly Report',
    'Please review the attached monthly report.',
    true,
    $files
);
```

### Size Validation

The maximum attachment size is controlled by the `MAX_ATTACHMENT` environment variable (in MB):

```bash
# .env file
MAX_ATTACHMENT=10  # Maximum 10 MB per message
```

The total size of all attachments must not exceed this limit, or an exception is thrown.

### File Type Support

Any file type can be attached:
- PDF documents
- Images (PNG, JPG, GIF, etc.)
- Office documents (DOCX, XLSX, etc.)
- Archives (ZIP, TAR, etc.)
- Any other binary or text files

## Input Validation and Security

The Mailer implements comprehensive input sanitization to prevent email injection attacks:

### Email Validation

All email addresses are validated using:
- PHP's `FILTER_VALIDATE_EMAIL` filter
- Length check (max 254 characters per RFC 5321)
- Removal of control characters (CRLF, tabs, null bytes)

```php
<?php

try {
    // Valid email
    $mailer->sendEmail('valid@example.com', 'Subject', 'Body');
    
    // Email too long - throws Exception
    $mailer->sendEmail(str_repeat('a', 300) . '@example.com', 'Subject', 'Body');
    
    // Invalid format - throws Exception
    $mailer->sendEmail('not-an-email', 'Subject', 'Body');
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

### Header Injection Prevention

Header values are sanitized to prevent injection:
- Control characters (CR, LF, null) are removed
- Certain dangerous headers are blocked:
  - `Bcc` (used for header injection)
  - `X-Confirm-Reading-To`
  - `Disposition-Notification-To`

```php
<?php

// Dangerous headers are removed
$headers = [
    'Bcc' => 'attacker@example.com',  // Will be removed
    'X-Custom-Safe' => 'value'         // Will be kept
];

$mailer->sendEmail('user@example.com', 'Subject', 'Body', true, [], $headers);
```

### Attachment Security

Attachments are validated:
- File must exist and be readable
- File must not be a directory
- File path is sanitized (special characters removed)
- Total size is validated

```php
<?php

try {
    // Non-existent file - throws Exception
    $mailer->sendEmail('user@example.com', 'Subject', 'Body', true, 
        ['/path/that/does/not/exist.pdf']);
    
    // Directory instead of file - throws Exception
    $mailer->sendEmail('user@example.com', 'Subject', 'Body', true, 
        ['/tmp']);
        
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

## Error Handling

### Exception Types

The Mailer throws exceptions for validation errors:

```php
<?php

try {
    $mailer->sendEmail('invalid@', 'Subject', 'Body');
} catch (Exception $e) {
    // Exception message: "Email invalide: invalid@"
}
```

### Common Errors

| Error | Cause | Solution |
|-------|-------|----------|
| *Email invalide* | Invalid email format | Validate email before sending |
| *Email trop long* | Email longer than 254 chars | Use shorter email address |
| *Fichier introuvable* | Attachment file doesn't exist | Verify file path |
| *Fichier invalide ou non lisible* | File is directory or not readable | Check file permissions |
| *Total files size exceed maximum mail limit* | Attachments too large | Reduce file sizes or increase limit |
| *Impossible de se connecter au serveur SMTP* | SMTP connection failed | Check server, port, credentials |
| *Erreur SMTP: ...* | Protocol error from server | Check credentials and server status |

### Network Errors

Network and connection errors return `false` instead of throwing:

```php
<?php

// Returns false if SMTP server unreachable
$success = $mailer->sendEmail('user@example.com', 'Subject', 'Body');

if (!$success) {
    // Check logs for error details
    // Logs are written via Logger::error()
}
```

## SMTP Protocol Details

### Connection Process

1. **Socket Creation** - Creates TLS/TCP socket connection
2. **SSL Context** - Configures SSL with certificate verification
3. **EHLO Handshake** - Identifies client and discovers server capabilities
4. **STARTTLS** - Upgrades to encrypted connection (if TLS enabled)
5. **Re-EHLO** - Re-identifies after STARTTLS (RFC requirement)
6. **AUTH LOGIN** - Authenticates with username and password
7. **MAIL FROM** - Specifies sender
8. **RCPT TO** - Specifies recipients (To, CC, BCC)
9. **DATA** - Transmits message
10. **QUIT** - Closes connection gracefully

### TLS/SSL Support

The Mailer supports both encrypted and unencrypted connections:

```php
<?php

// With TLS (secure, recommended)
$mailer = new Mailer($config, true);  // default

// Without TLS (legacy servers only)
$mailer = new Mailer($config, false);
```

### SSL Context Configuration

TLS connections use secure SSL context:
- Verifies peer certificates
- Verifies peer name
- Rejects self-signed certificates
- Timeout: 30 seconds (configurable)

## Message Encoding

### MIME Structure

HTML emails use MIME multipart format:

```
multipart/mixed (if attachments)
├── multipart/alternative
│   ├── text/plain (UTF-8, quoted-printable)
│   └── text/html (UTF-8, quoted-printable)
└── attachments (base64)

OR

multipart/alternative (no attachments)
├── text/plain (UTF-8, quoted-printable)
└── text/html (UTF-8, quoted-printable)

OR

text/plain (plain text only)
```

### Header Encoding

Headers with special characters are encoded using RFC 2047 Base64:

```
Subject: =?UTF-8?B?Sm9pbiDDqSBub3VzIQ==?=
```

This enables proper display of international characters in:
- Subject lines
- Sender name
- Custom headers

## Testing

The Mailer is tested through `MailerTest` with comprehensive test cases:

### Test Coverage

| Test | Purpose |
|------|---------|
| `testWhenAllIsOK` | Basic functionality with network error |
| `testThrowsWhenFromEmailIsInvalid` | From address validation |
| `testThrowsWhenToEmailIsInvalid` | To address validation |
| `testThrowsWhenCcContainsInvalidEmail` | CC address validation |
| `testThrowsWhenBccContainsInvalidEmail` | BCC address validation |
| `testThrowsWhenReplyToIsInvalid` | Reply-To validation |
| `testThrowsWhenAttachmentDoesNotExist` | File existence check |
| `testThrowsWhenEmailIsTooLong` | Email length validation |
| `testHeaderSanitizationRemovesForbiddenHeaders` | Header injection prevention |
| `testCcAndBccAreNormalizedWhenProvidedAsStrings` | CC/BCC normalization |
| `testReplyToSanitizationWithControlChars` | Reply-To control char removal |
| `testAttachmentPathIsDirectoryThrows` | Directory validation |
| `testPlainTextEmailReturnsFalse` | Plain text handling |

### Example Test

```php
<?php

use PHPUnit\Framework\TestCase;
use App\Services\Mailer\Mailer;
use App\Services\Mailer\MailConfig;

class SendEmailTest extends TestCase
{
    public function testSendValidEmail(): void
    {
        $config = new MailConfig();
        $config->smtpHost = 'smtp.example.com';
        $config->smtpPort = 587;
        $config->smtpUser = 'user@example.com';
        $config->smtpPass = 'password';
        $config->fromEmail = 'noreply@example.com';
        $config->fromName = 'App';

        $mailer = new Mailer($config);
        
        // Will fail to connect (expected in test environment)
        $result = $mailer->sendEmail(
            'test@example.com',
            'Test Subject',
            'Test Body'
        );
        
        $this->assertFalse($result);  // False due to connection failure
    }

    public function testInvalidEmailThrows(): void
    {
        $config = new MailConfig();
        $config->fromEmail = 'invalid@';
        
        $mailer = new Mailer($config);
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Email invalide');
        
        $mailer->sendEmail('test@example.com', 'Subject', 'Body');
    }
}
```

## Advanced Examples

### Example 1: Sending Welcome Email

```php
<?php

namespace App\Services;

use App\Services\Mailer\Mailer;
use App\Services\Mailer\MailConfig;
use App\Models\User;

class UserWelcomeService
{
    private Mailer $mailer;

    public function __construct()
    {
        $config = MailConfig::getInstance();
        $this->mailer = new Mailer($config);
    }

    public function sendWelcomeEmail(User $user): bool
    {
        $htmlBody = $this->getWelcomeTemplate($user);

        return $this->mailer->sendEmail(
            $user->getEmail(),
            'Welcome to Our Platform!',
            $htmlBody,
            true,
            [],
            [],
            [],
            [],
            'support@example.com'
        );
    }

    private function getWelcomeTemplate(User $user): string
    {
        return <<<HTML
<html>
    <body style="font-family: Arial, sans-serif;">
        <h1>Welcome, {$user->getFirstName()}!</h1>
        <p>Your account has been created successfully.</p>
        <p><a href="https://example.com/verify/{$user->getVerificationToken()}">
            Verify Your Email
        </a></p>
        <p>Thanks,<br>The Example Team</p>
    </body>
</html>
HTML;
    }
}
```

### Example 2: Sending Report with Attachments

```php
<?php

namespace App\Services;

use App\Services\Mailer\Mailer;
use App\Services\Mailer\MailConfig;

class MonthlyReportService
{
    private Mailer $mailer;

    public function __construct()
    {
        $config = MailConfig::getInstance();
        $this->mailer = new Mailer($config);
    }

    public function sendMonthlyReport(string $email, array $reportFiles): bool
    {
        return $this->mailer->sendEmail(
            $email,
            'Monthly Report - ' . date('F Y'),
            $this->getReportBody(),
            true,
            $reportFiles,
            ['X-Priority' => '3'],
            ['manager@example.com']  // CC manager
        );
    }

    private function getReportBody(): string
    {
        return <<<HTML
<html>
    <body>
        <h2>Monthly Report</h2>
        <p>Please find attached the monthly report for {date('F Y')}.</p>
        <p>Key metrics are included in the attached files.</p>
    </body>
</html>
HTML;
    }
}
```

### Example 3: Error Handling and Retry Logic

```php
<?php

namespace App\Services;

use App\Services\Mailer\Mailer;
use App\Services\Mailer\MailConfig;

class ReliableMailerService
{
    private Mailer $mailer;
    private int $maxRetries = 3;
    private int $retryDelay = 2;  // seconds

    public function __construct()
    {
        $config = MailConfig::getInstance();
        $this->mailer = new Mailer($config);
    }

    public function sendEmailWithRetry(
        string $to,
        string $subject,
        string $body,
        bool $isHtml = true
    ): bool {
        for ($attempt = 1; $attempt <= $this->maxRetries; $attempt++) {
            try {
                $success = $this->mailer->sendEmail($to, $subject, $body, $isHtml);
                
                if ($success) {
                    return true;
                }
                
                // Network error, wait before retry
                if ($attempt < $this->maxRetries) {
                    sleep($this->retryDelay);
                }
                
            } catch (\Exception $e) {
                // Validation error, don't retry
                \App\Kernel\Logger::error(
                    $this,
                    "Email validation error: " . $e->getMessage()
                );
                return false;
            }
        }
        
        return false;
    }
}
```

### Example 4: Bulk Email Sending

```php
<?php

namespace App\Services;

use App\Services\Mailer\Mailer;
use App\Services\Mailer\MailConfig;

class BulkMailerService
{
    private Mailer $mailer;

    public function __construct()
    {
        $config = MailConfig::getInstance();
        $this->mailer = new Mailer($config);
    }

    public function sendNewsletterToList(array $subscribers, string $subject, string $body): array
    {
        $results = [
            'successful' => 0,
            'failed' => 0,
            'errors' => []
        ];

        foreach ($subscribers as $subscriber) {
            try {
                $success = $this->mailer->sendEmail(
                    $subscriber['email'],
                    $subject,
                    $body,
                    true,
                    [],
                    [],
                    [],
                    [],
                    'newsletter@example.com'
                );

                if ($success) {
                    $results['successful']++;
                } else {
                    $results['failed']++;
                    $results['errors'][] = [
                        'email' => $subscriber['email'],
                        'reason' => 'Connection error'
                    ];
                }
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'email' => $subscriber['email'],
                    'reason' => $e->getMessage()
                ];
            }
        }

        return $results;
    }
}
```

## Environment Configuration

### Required Environment Variables

```bash
# .env file
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=your-email@gmail.com
SMTP_PASS=your-app-password
FROM_EMAIL=noreply@example.com
FROM_NAME=My Application
MAX_ATTACHMENT=10
```

### Loading from Environment

```php
<?php

use App\Services\Mailer\MailConfig;
use App\Services\Mailer\Mailer;

// Automatically loads from environment variables
$config = MailConfig::getInstance();
$mailer = new Mailer($config);
```

## Best Practices

1. **Always validate before sending** - Check email addresses are correct
2. **Use TLS encryption** - Always enable TLS for secure transmission
3. **Set Reply-To address** - Make it easy for recipients to respond
4. **Use proper MIME types** - Send HTML with plain text alternative
5. **Keep attachments small** - Large files may be rejected
6. **Handle exceptions** - Catch validation errors and log them
7. **Retry on network errors** - Implement retry logic for reliability
8. **Use templates** - Build email content from templates
9. **Test before deploying** - Verify SMTP configuration works
10. **Monitor delivery** - Log all email sends for debugging

## Troubleshooting

### Issue: SMTP Connection Failed

**Error**: "Impossible de se connecter au serveur SMTP"

**Solutions**:
- Verify SMTP host and port are correct
- Check if firewall allows outgoing SMTP connections
- Ensure TLS is enabled if required by server
- Verify credentials are correct

### Issue: Authentication Failed

**Error**: "Erreur SMTP: ... (attendu: 235)"

**Solutions**:
- Check SMTP username and password
- Verify account isn't locked
- Check if two-factor authentication needs app-specific password
- Ensure user has SMTP permissions

### Issue: Email Marked as Spam

**Solutions**:
- Add SPF record: `v=spf1 include:smtp.provider.com ~all`
- Add DKIM signature support
- Add DMARC record: `v=DMARC1; p=none`
- Use proper From and Reply-To addresses
- Avoid spam keywords in subject and body

### Issue: Attachment Not Received

**Solutions**:
- Check file exists and is readable
- Verify total attachment size is under limit
- Ensure MIME encoding is correct
- Check mail client doesn't have filters

## Performance Considerations

- **Connection overhead** - Each email creates a new connection
- **Sequential sending** - Emails are sent one at a time
- **For bulk mail** - Consider batching or using services
- **Timeout** - Default 30 seconds per SMTP operation
- **Large attachments** - May take time to encode and transmit

## Summary

The Mailer class provides a robust, secure way to send emails from your PHP application. With comprehensive input validation, MIME support, and proper error handling, it follows modern email standards and security best practices.

Key features:
- Full SMTP protocol implementation
- TLS/SSL encryption support
- HTML and plain text support
- File attachments with size validation
- CC, BCC, and Reply-To support
- Email injection prevention
- International character support
- Comprehensive error handling
- Easy configuration via environment variables
