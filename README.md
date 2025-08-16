# Maileroo PHP SDK

[Maileroo](https://maileroo.com) is a robust email delivery platform designed for effortless sending of transactional and marketing emails. This PHP SDK offers a straightforward interface for working with the Maileroo API, supporting basic email formats, templates, bulk sending, and scheduling capabilities.

## Features

- Send basic HTML or plain text emails with ease
- Use pre-defined templates with dynamic data
- Send up to 500 personalized emails in bulk
- Schedule emails for future delivery
- Manage scheduled emails (list & delete)
- Add tags, custom headers, and reference IDs
- Attach files to your emails
- Support for multiple recipients, CC, BCC, and Reply-To
- Enable or disable open and click tracking
- Built-in input validation and error handling

## Requirements

- PHP 7.0 or higher
- cURL extension (`ext-curl`)
- JSON extension (`ext-json`)

## Installation

Install via Composer:

```bash
composer require maileroo/maileroo-php-sdk
```

## Quick Start

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Maileroo\MailerooClient;
use Maileroo\EmailAddress;
use Maileroo\Attachment;

// Initialize the client

$client = new MailerooClient('your-api-key');

// Send a basic email

$reference_id = $client->sendBasicEmail([
    'from' => new EmailAddress('sender@example.com', 'Sender Name'),
    'to' => [new EmailAddress('recipient@example.com', 'Recipient Name')],
    'subject' => 'Hello from Maileroo!',
    'html' => '<h1>Hello World!</h1><p>This is a test email.</p>',
    'plain' => 'Hello World! This is a test email.'
]);

echo "Email sent with reference ID: " . $reference_id;
```

## Usage Examples

### 1. Basic Email with Attachments

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Maileroo\MailerooClient;
use Maileroo\EmailAddress;
use Maileroo\Attachment;

$client = new MailerooClient('your-api-key');

$reference_id = $client->sendBasicEmail([
    'from' => new EmailAddress('sender@example.com', 'Your Company'),
    'to' => [
        new EmailAddress('john@example.com', 'John Doe'),
        new EmailAddress('jane@example.com')
    ],
    'cc' => [new EmailAddress('manager@example.com', 'Manager')],
    'bcc' => [new EmailAddress('archive@example.com')],
    'reply_to' => new EmailAddress('support@example.com', 'Support Team'),
    'subject' => 'Monthly Report',
    'html' => '<h1>Monthly Report</h1><p>Please find the report attached.</p>',
    'plain' => 'Monthly Report - Please find the report attached.',
    'attachments' => [
        Attachment::fromFile('/path/to/report.pdf', 'application/pdf'),
        Attachment::fromContent('data.csv', $csv_data, 'text/csv')
    ],
    'tracking' => true,
    'tags' => ['campaign' => 'monthly-report', 'type' => 'business'],
    'headers' => [
        'X-Custom-Header' => 'Custom Value',
        'X-Another-Header' => 'Another Value'
    ],
    'reference_id' => $client->getReferenceId()
]);
```

### 2. Template Email

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Maileroo\MailerooClient;
use Maileroo\EmailAddress;

$client = new MailerooClient('your-api-key');

$reference_id = $client->sendTemplatedEmail([
    'from' => new EmailAddress('noreply@example.com', 'Your App'),
    'to' => new EmailAddress('user@example.com', 'John Doe'),
    'subject' => 'Welcome to Our Service!',
    'template_id' => 123,
    'template_data' => [
        'user_name' => 'John Doe',
        'activation_link' => 'https://example.com/activate/abc123',
        'company_name' => 'Your Company'
    ],
    // If "tracking" field is not present, it respects your account settings
]);
```

### 3. Bulk Email Sending (With Plain and HTML)

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Maileroo\MailerooClient;
use Maileroo\EmailAddress;

$client = new MailerooClient('your-api-key');

$result = $client->sendBulkEmails([
    'subject' => 'Newsletter - March 2024',
    'html' => '<h1>Hello {{name}}!</h1><p>Here\'s your personalized newsletter content.</p>',
    'plain' => 'Hello {{name}}! Here\'s your personalized newsletter content.',
    'tracking' => false,
    'tags' => ['campaign' => 'newsletter', 'month' => 'march'],
    'messages' => [
        [
            'from' => new EmailAddress('newsletter@example.com', 'Newsletter Team'),
            'to' => new EmailAddress('john@example.com', 'John Doe'),
            'cc' => [new EmailAddress('manager@example.com', 'Manager')], // Optional
            'bcc' => [new EmailAddress('archive@example.com')], // Optional
            'reply_to' => new EmailAddress('support@example.com', 'Support'), // Optional
            'template_data' => ['name' => 'John'],
            'reference_id' => 'custom-ref-001' // Optional, auto-generated if not provided
        ],
        [
            'from' => new EmailAddress('newsletter@example.com', 'Newsletter Team'),
            'to' => new EmailAddress('jane@example.com', 'Jane Smith'),
            'template_data' => ['name' => 'Jane'],
        ]
        // ... up to 500 messages
    ]
]);

foreach ($result as $reference_id) {
    echo "Email sent with reference ID: " . $reference_id . "\n";
}
```

### 4. Bulk Email Sending (With Template ID)

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Maileroo\MailerooClient;
use Maileroo\EmailAddress;

$client = new MailerooClient('your-api-key');

$result = $client->sendBulkEmails([
    'subject' => 'Welcome to Our Service!',
    'template_id' => 123, // Use template instead of html/plain
    'tracking' => true,
    'tags' => ['campaign' => 'welcome', 'type' => 'onboarding'],
    'messages' => [
        [
            'from' => new EmailAddress('welcome@example.com', 'Welcome Team'),
            'to' => new EmailAddress('newuser1@example.com', 'New User 1'),
            'template_data' => [
                'user_name' => 'New User 1',
                'activation_link' => 'https://example.com/activate/token1',
                'company_name' => 'Your Company'
            ]
        ],
        [
            'from' => new EmailAddress('welcome@example.com', 'Welcome Team'),
            'to' => new EmailAddress('newuser2@example.com', 'New User 2'),
            'template_data' => [
                'user_name' => 'New User 2',
                'activation_link' => 'https://example.com/activate/token2',
                'company_name' => 'Your Company'
            ]
        ]
        // ... up to 500 messages
    ],
    'attachments' => [
        Attachment::fromFile('/path/to/welcome-guide.pdf', 'application/pdf')
    ]
]);

foreach ($result as $reference_id) {
    echo "Email sent with reference ID: " . $reference_id . "\n";
}
```

### 5. Working with Attachments

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Maileroo\Attachment;

// From file path:

// Attachment::fromFile(string $path, ?string $content_type = null, bool $inline = false)
$attachment1 = Attachment::fromFile('/path/to/document.pdf', 'application/pdf', false);

// From string content:

// Attachment::fromContent(string $file_name, string $content, ?string $content_type = null, bool $inline = false, bool $is_base64 = false)
$csv_content = "Name,Email\nJohn,john@example.com";
$attachment2 = Attachment::fromContent('contacts.csv', $csv_content, 'text/csv', false, false);

// From base64 content:

// Attachment::fromContent(string $file_name, string $content, ?string $content_type = null, bool $inline = false, bool $is_base64 = true)
$attachment3 = Attachment::fromContent('image.png', $base64_data, 'image/png', false, true);

// From stream resource:

// Attachment::fromStream(string $file_name, resource $stream, ?string $content_type = null, bool $inline = false)
$stream = fopen('/path/to/file.txt', 'r');
$attachment4 = Attachment::fromStream('file.txt', $stream, 'text/plain', false);

// Inline attachment (for embedding in HTML):

// Attachment::fromFile(string $path, ?string $content_type = null, bool $inline = true)
$inline_image = Attachment::fromFile('/path/to/logo.png', 'image/png', true);
```

### 6. Scheduling Emails

You can schedule emails for future delivery by adding a `scheduled_at` field with an RFC 3339 formatted datetime string.

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Maileroo\MailerooClient;
use Maileroo\EmailAddress;

$client = new MailerooClient('your-api-key');

// Schedule email for delivery tomorrow at 9:00 AM UTC

$scheduled_time = date('c', strtotime('+1 day 9:00')); // RFC 3339 format

$reference_id = $client->sendBasicEmail([
    'from' => new EmailAddress('scheduler@example.com', 'Scheduler'),
    'to' => new EmailAddress('recipient@example.com', 'Recipient'),
    'subject' => 'Scheduled Email - Daily Report',
    'html' => '<h1>Daily Report</h1><p>This email was scheduled for delivery.</p>',
    'plain' => 'Daily Report - This email was scheduled for delivery.',
    'scheduled_at' => $scheduled_time // Schedule for future delivery
]);

echo "Email scheduled with reference ID: " . $reference_id;
```

### 7. Managing Scheduled Emails

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Maileroo\MailerooClient;

$client = new MailerooClient('your-api-key');

$response = $client->getScheduledEmails($page = 1, $per_page = 20);

// Access pagination info

echo "Page: " . $response['page'] . "/" . $response['total_pages'] . "\n";
echo "Total emails: " . $response['total_count'] . "\n";

// Loop through the results

foreach ($response['results'] as $email) {

    echo "Email ID: " . $email['reference_id'] . "\n";
    echo "From: " . $email['from'] . "\n";
    echo "Subject: " . $email['subject'] . "\n";
    echo "Scheduled for: " . $email['scheduled_at'] . "\n";
    echo "Recipients: " . implode(', ', $email['recipients']) . "\n";
    
    // Show tags if present
    
    if (!empty($email['tags'])) {
        echo "Tags: " . json_encode($email['tags']) . "\n";
    }
    
    // Show custom headers if present
    
    if (!empty($email['headers'])) {
        echo "Headers: " . json_encode($email['headers']) . "\n";
    }
    
    // Cancel a scheduled email if needed
    
    if ($email['reference_id'] === 'some-reference-id') {
        $client->deleteScheduledEmail($email['reference_id']);
        echo "Email cancelled\n";
    }
    
    echo "---\n";
    
}
```

### 8. Deleting Scheduled Email

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Maileroo\MailerooClient;

$client = new MailerooClient('your-api-key');

try {
    $client->deleteScheduledEmail('your-reference-id');
    echo "Scheduled email cancelled successfully.";
} catch (\Exception $e) {
    echo "Error cancelling scheduled email: " . $e->getMessage();
}

```

## API Reference

### MailerooClient

#### Constructor
```php
new MailerooClient(string $api_key, int $timeout = 30)
```

#### Methods

- `sendBasicEmail(array $data): string` - Send a basic email, returns reference ID
- `sendTemplatedEmail(array $data): string` - Send a template email, returns reference ID  
- `sendBulkEmails(array $data): array` - Send bulk emails, returns result data
- `deleteScheduledEmail(string $reference_id): void` - Cancel a scheduled email
- `getScheduledEmails(int $page = 1, int $per_page = 10): array` - Get scheduled emails
- `getReferenceId(): string` - Generate a unique reference ID

### EmailAddress

```php
new EmailAddress(string $address, ?string $display_name = null)
```

- `getAddress(): string`
- `getDisplayName(): ?string` 
- `toArray(): array`

### Attachment

Static factory methods:

- `Attachment::fromFile(string $path, ?string $content_type = null, bool $inline = false)`
- `Attachment::fromContent(string $file_name, string $content, ?string $content_type = null, bool $inline = false, bool $is_base64 = false)`
- `Attachment::fromStream(string $file_name, resource $stream, ?string $content_type = null, bool $inline = false)`

## Error Handling

The SDK throws exceptions for various error conditions:

```php
<?php

use Maileroo\MailerooClient;

try {

    $client = new MailerooClient('your-api-key');
    $reference_id = $client->sendBasicEmail($email_data);
    
    echo "Email sent: " . $reference_id;
    
} catch (\Exception $e) {

    echo "Unexpected error: " . $e->getMessage();
    
}
```
## Documentation

For detailed API documentation, including all available endpoints, parameters, and response formats, please refer to the [Maileroo API Documentation](https://maileroo.com/docs).

## License

This SDK is released under the MIT License.

## Support

Please visit our [support page](https://maileroo.com/contact-form) for any issues or questions regarding Maileroo. If you find any bugs or have feature requests, feel free to open an issue on our GitHub repository.