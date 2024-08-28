# Maileroo PHP SDK

Welcome to the official PHP SDK for Maileroo, a powerful and flexible email sending API. This SDK allows you to easily integrate Maileroo's email sending capabilities into your PHP applications.

## Why?

This library can be used to...

- Send basic emails with a single API call
- Send templated emails with dynamic content and personalization
- Send emails to multiple recipients, attaching files, and more
- Manage your contacts, including adding, updating, listing, and deleting contacts

## Requirements

- PHP 7.4 or higher
- cURL extension for PHP

## Installation

Install the SDK via Composer by running the following command:

```bash
composer require maileroo/maileroo-php-sdk
```

## Usage

### 1. Sending a Basic Email

```
<?php

use Maileroo\MailerooClient;

try {

    $client = new MailerooClient("YOUR_API_KEY");

    $basic_email_response = $client->setFrom('Maileroo', 'no.reply@mail.maileroo.com')
        ->setTo('John Doe', 'john.doe@maileroo.com')
        ->setCc('Jane Doe', 'jane.doe@maileroo.com') // Optional
        ->setBcc('Jim Doe', 'jim.doe@maileroo.com') // Optional
        ->setReplyTo('Administrator', 'admin@maileroo.com') // Optional
        ->setReferenceId($client->generateReferenceId()) // Optional
        ->setTags(['tag1' => 'value1', 'tag2' => 'value2']) // Optional
        ->setTracking(true) // Optional
        ->setSubject('Hello World')
        ->setHtml('<p>Hello World</p>')
        ->setPlain('Hello World') // Optional
        ->addAttachment('path/to/file', 'file_name', 'file_type') // Optional
        ->addInlineAttachment('path/to/file', 'file_name', 'file_type') // Optional
        ->sendBasicEmail();

    echo "Basic Email Sent Successfully.\n";

} catch (Exception $e) {
    echo "An error occurred while sending the basic email: " . $e->getMessage() . "\n";
}
```

### 2. Sending a Templated Email

```
<?php

use Maileroo\MailerooClient;

try {

    $client = new MailerooClient(""); // Use Domain Sender Key

    $client->setFrom('Maileroo', 'no.reply@mail.maileroo.com')
        ->setTo('John Doe', 'john.doe@maileroo.com')
        ->setCc('Jane Doe', 'jane.doe@maileroo.com') // Optional
        ->setBcc('Jim Doe', 'jim.doe@maileroo.com') // Optional
        ->setReplyTo('Administrator', 'admin@maileroo.com') // Optional
        ->setReferenceId($client->generateReferenceId()) // Optional
        ->setTags(['tag1' => 'value1', 'tag2' => 'value2']) // Optional
        ->setTracking(true) // Optional
        ->setSubject('Hello World')
        ->setTemplateId(1)
        ->setTemplateData(['name' => 'John Doe'])
        ->addAttachment('path/to/file', 'file_name', 'file_type') // Optional
        ->addInlineAttachment('path/to/file', 'file_name', 'file_type') // Optional
        ->sendTemplateEmail();

    echo "Template Email Sent Successfully.\n";

} catch (Exception $e) {
    echo "An error occurred while sending the template email: " . $e->getMessage() . "\n";
}
```

### 3. Create Contact

```
<?php

use Maileroo\MailerooClient;

try {

    $client = new MailerooClient(""); // Use Organization API Key

    $list_id = '123';

    $contact = [
        'subscriber_name' => 'John Doe',
        'subscriber_email' => 'john.doe@example.com',
        'subscriber_timezone' => 'America/New_York',
        'subscriber_tags' => 'tag1,tag2',
        'subscriber_status' => 'UNCONFIRMED'
    ];

    $client->createContact($list_id, $contact);

    echo "Contact Created Successfully.\n";

} catch (Exception $e) {
    echo "An error occurred while creating the contact: " . $e->getMessage() . "\n";
}
```

### 4. Get Contact

```
<?php

use Maileroo\MailerooClient;

try {

    $client = new MailerooClient(""); // Use Organization API Key

    $list_id = '123';

    $retrieved_contact = $client->getContact($list_id, 'new.contact@example.com');

    print_r($retrieved_contact);

} catch (Exception $e) {
    echo "An error occurred while retrieving the contact: " . $e->getMessage() . "\n";
}
```

### 5. Update Contact

```
<?php

use Maileroo\MailerooClient;

try {

    $client = new MailerooClient(""); // Use Organization API Key

    $list_id = '123';

    $updated_contact = [
        'subscriber_name' => 'John Doe',
        'subscriber_timezone' => 'Australia/Sydney',
    ];

    $client->updateContact($list_id, 'john.doe@example.com', $updated_contact);

    echo "Contact Updated Successfully.\n";

} catch (Exception $e) {
    echo "An error occurred while updating the contact: " . $e->getMessage() . "\n";
}
```

### 6. Delete Contact

```
<?php

use Maileroo\MailerooClient;

try {

    $client = new MailerooClient(""); // Use Organization API Key

    $list_id = '123';

    $client->deleteContact($list_id, 'john.doe@example.com');

    echo "Contact Deleted Successfully.\n";

} catch (Exception $e) {
    echo "An error occurred while deleting the contact: " . $e->getMessage() . "\n";
}
```

### 7. List Contacts

```
<?php

use Maileroo\MailerooClient;

try {

    $client = new MailerooClient(""); // Use Organization API Key

    $list_id = '123';
    $query = '';
    $page = 1;

    $contacts = $client->listContacts($list_id, $query, $page);

    print_r($contacts);

} catch (Exception $e) {
    echo "An error occurred while listing the contacts: " . $e->getMessage() . "\n";
}
```

## License

This software is released under the MIT License. See the [LICENSE](LICENSE) file for more information.