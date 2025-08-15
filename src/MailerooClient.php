<?php

namespace Maileroo;

class MailerooClient {

    const API_BASE_URL = 'https://smtp.maileroo.com/api/v2/';

    const MAX_ASSOCIATIVE_MAP_KEY_LENGTH = 128;
    const MAX_ASSOCIATIVE_MAP_VALUE_LENGTH = 768;

    const MAX_SUBJECT_LENGTH = 255;
    const REFERENCE_ID_LENGTH = 24;

    private $api_key;
    private $timeout;

    public function __construct($api_key, $timeout = 30) {

        if (!is_string($api_key) || trim($api_key) === '') {
            throw new \InvalidArgumentException('API key must be a non-empty string.');
        }

        if (!is_int($timeout) || $timeout <= 0) {
            throw new \InvalidArgumentException('Timeout must be a positive integer.');
        }

        $this->api_key = $api_key;
        $this->timeout = $timeout;

    }

    public function getReferenceId() {

        $length = self::REFERENCE_ID_LENGTH / 2;

        try {

            $bytes = random_bytes($length);

        } catch (\Exception $e) {

            if (function_exists('openssl_random_pseudo_bytes')) {

                $bytes = openssl_random_pseudo_bytes($length);

                if ($bytes === false) {
                    $bytes = $this->fallbackRandomBytes($length);
                }

            } else {

                $bytes = $this->fallbackRandomBytes($length);

            }

        }

        return bin2hex($bytes);

    }

    private function fallbackRandomBytes($length) {

        $bytes = '';

        for ($i = 0; $i < $length; $i++) {
            $bytes .= chr(mt_rand(0, 255));
        }

        return $bytes;

    }

    private function validateReferenceId($reference_id) {

        if (!is_string($reference_id)) {
            throw new \InvalidArgumentException('reference_id must be a string.');
        }

        if ($reference_id !== trim($reference_id)) {
            throw new \InvalidArgumentException('reference_id must not contain whitespace.');
        }

        if (!preg_match('/^[0-9a-f]{' . self::REFERENCE_ID_LENGTH . '}$/i', $reference_id)) {
            throw new \InvalidArgumentException('reference_id must be a ' . self::REFERENCE_ID_LENGTH . '-character hexadecimal string.');
        }

        return $reference_id;

    }

    private function buildBasePayload(array $data): array {

        $payload = $this->getParsedEmailItems($data);

        if (!isset($data['subject']) || !is_string($data['subject']) || trim($data['subject']) === '' || strlen($data['subject']) > self::MAX_SUBJECT_LENGTH) {
            throw new \InvalidArgumentException('Subject must be a non-empty string with a maximum length of ' . self::MAX_SUBJECT_LENGTH . ' characters.');
        }

        $payload['subject'] = $data['subject'];

        if (isset($data['tracking'])) {

            if (!is_bool($data['tracking'])) {
                throw new \InvalidArgumentException('Tracking must be a boolean value.');
            }

            $payload['tracking'] = $data['tracking'];

        }

        if (isset($data['tags']) && is_array($data['tags'])) {

            $this->validateAssociativeMap($data['tags'], 'tags');

            $payload['tags'] = $data['tags'];

        }

        if (isset($data['headers']) && is_array($data['headers'])) {

            $this->validateAssociativeMap($data['headers'], 'headers');

            $payload['headers'] = $data['headers'];

        }

        if (isset($data['attachments']) && is_array($data['attachments'])) {

            $payload['attachments'] = [];

            foreach ($data['attachments'] as $attachment) {

                if (!$attachment instanceof Attachment) {
                    throw new \InvalidArgumentException('Each attachment must be an instance of Attachment.');
                }

                $payload['attachments'][] = $attachment->toArray();

            }

        }

        if (isset($data['scheduled_at']) && is_string($data['scheduled_at'])) {
            $payload['scheduled_at'] = $data['scheduled_at'];
        }

        if (isset($data['reference_id'])) {
            $payload['reference_id'] = $this->validateReferenceId($data['reference_id']);
        } else {
            $payload['reference_id'] = $this->getReferenceId();
        }

        return $payload;

    }

    public function sendBasicEmail($data) {

        $payload = $this->buildBasePayload($data);

        if (!isset($data['html']) && !isset($data['plain'])) {
            throw new \InvalidArgumentException('Either html or plain body is required.');
        }

        $payload['html'] = $data['html'] ?? null;
        $payload['plain'] = $data['plain'] ?? null;

        $response = $this->sendRequest('POST', 'emails', $payload);

        if ($response['success']) {
            return $response['data']['reference_id'];
        }

        throw new \RuntimeException('The API returned an error: ' . $response['message']);

    }

    private function validateTemplateData($template_data) {

        if ($template_data === null || $template_data === '' || $template_data === []) {
            return [];
        }

        if (!is_array($template_data)) {
            throw new \InvalidArgumentException('template_data must be an array if provided.');
        }

        foreach ($template_data as $key => $value) {

            if (!is_string($key)) {
                throw new \InvalidArgumentException('template_data keys must be strings.');
            }

        }

        return $template_data;

    }

    public function sendTemplatedEmail($data) {

        $payload = $this->buildBasePayload($data);

        if (!isset($data['template_id']) || !is_int($data['template_id']) && !is_string($data['template_id'])) {
            throw new \InvalidArgumentException('template_id must be an integer or a string.');
        }

        $payload['template_id'] = intval($data['template_id']);

        if (isset($data['template_data'])) {
            $payload['template_data'] = $this->validateTemplateData($data['template_data']);
        }

        $response = $this->sendRequest('POST', 'emails/template', $payload);

        if ($response['success']) {
            return $response['data']['reference_id'];
        }

        throw new \RuntimeException('The API returned an error: ' . $response['message']);

    }

    public function sendBulkEmails($data) {

        if (!isset($data['subject']) || !is_string($data['subject']) || trim($data['subject']) === '' || strlen($data['subject']) > self::MAX_SUBJECT_LENGTH) {
            throw new \InvalidArgumentException('Subject must be a non-empty string with a maximum length of ' . self::MAX_SUBJECT_LENGTH . ' characters.');
        }

        $has_html = isset($data['html']) && is_string($data['html']);
        $has_plain = isset($data['plain']) && is_string($data['plain']);
        $has_template = isset($data['template_id']) && (is_int($data['template_id']) || is_string($data['template_id']));

        if ((!$has_html && !$has_plain) && !$has_template) {
            throw new \InvalidArgumentException('You must provide either html, plain, or template_id.');
        }

        if ($has_template && ($has_html || $has_plain)) {
            throw new \InvalidArgumentException('template_id cannot be combined with html or plain.');
        }

        if (!is_array($data['messages']) || empty($data['messages'])) {
            throw new \InvalidArgumentException('messages must be a non-empty array.');
        }

        if (count($data['messages']) > 500) {
            throw new \InvalidArgumentException('messages cannot contain more than 500 items.');
        }

        $payload = [
            'subject' => $data['subject'],
        ];

        if ($has_html) {
            $payload['html'] = $data['html'];
        }

        if ($has_plain) {
            $payload['plain'] = $data['plain'];
        }

        if ($has_template) {
            $payload['template_id'] = (int)$data['template_id'];
        }

        if (isset($data['tracking'])) {

            if (!is_bool($data['tracking'])) {
                throw new \InvalidArgumentException('Tracking must be a boolean value.');
            }

            $payload['tracking'] = $data['tracking'];

        }

        if (isset($data['tags']) && is_array($data['tags'])) {
            $this->validateAssociativeMap($data['tags'], 'tags');
            $payload['tags'] = $data['tags'];
        }

        if (isset($data['headers']) && is_array($data['headers'])) {
            $this->validateAssociativeMap($data['headers'], 'headers');
            $payload['headers'] = $data['headers'];
        }

        if (isset($data['attachments']) && is_array($data['attachments'])) {

            $payload['attachments'] = [];

            foreach ($data['attachments'] as $attachment) {

                if (!$attachment instanceof Attachment) {
                    throw new \InvalidArgumentException('Each attachment must be an instance of Attachment.');
                }

                $payload['attachments'][] = $attachment->toArray();

            }

        }

        $payload['messages'] = $this->normalizeBulkMessages($data['messages']);

        $response = $this->sendRequest('POST', 'emails/bulk', $payload);

        if (is_array($response) && isset($response['success']) && $response['success'] && isset($response['data'])) {
            return $response['data']['reference_ids'];
        }

        throw new \RuntimeException('The API returned an error: ' . $response['message']);

    }

    private function normalizeBulkMessages(array $messages) {

        $normalized = [];

        foreach ($messages as $idx => $msg) {

            if (!is_array($msg)) {
                throw new \InvalidArgumentException("Each message must be an array (message index {$idx}).");
            }

            if (!isset($msg['from']) || !isset($msg['to'])) {
                throw new \InvalidArgumentException("Each message must include 'from' and 'to' (message index {$idx}).");
            }

            $from = $this->normalizeEmailField($msg['from']);
            $to = $this->normalizeEmailFieldOrArray($msg['to']);

            $cc = isset($msg['cc']) ? $this->normalizeEmailFieldOrArray($msg['cc']) : null;
            $bcc = isset($msg['bcc']) ? $this->normalizeEmailFieldOrArray($msg['bcc']) : null;
            $reply_to = isset($msg['reply_to']) ? $this->normalizeEmailFieldOrArray($msg['reply_to']) : null;

            $item = [
                'from' => $this->getEmailArrays($from),
                'to' => $this->getEmailArrays($to),
                'cc' => $this->getEmailArrays($cc),
                'bcc' => $this->getEmailArrays($bcc),
                'reply_to' => $this->getEmailArrays($reply_to),
            ];

            if (isset($msg['reference_id'])) {
                $item['reference_id'] = $this->validateReferenceId($msg['reference_id']);
            } else {
                $item['reference_id'] = $this->getReferenceId();
            }

            if (array_key_exists('template_data', $msg)) {
                $item['template_data'] = $this->validateTemplateData($msg['template_data']);
            }

            $normalized[] = $item;

        }

        return $normalized;

    }

    private function validateAssociativeMap($map, $label) {

        foreach ($map as $key => $value) {

            if (!is_string($key) || !$this->isAcceptableTagOrHeaderValue($value)) {
                throw new \InvalidArgumentException("{$label} must be an associative array with string keys and values.");
            }

            if (strlen($key) > self::MAX_ASSOCIATIVE_MAP_KEY_LENGTH || strlen((string)$value) > self::MAX_ASSOCIATIVE_MAP_VALUE_LENGTH) {
                throw new \InvalidArgumentException("{$label} key must not exceed " . self::MAX_ASSOCIATIVE_MAP_KEY_LENGTH . " characters and value must not exceed " . self::MAX_ASSOCIATIVE_MAP_VALUE_LENGTH . " characters.");
            }

        }

    }

    private function isAcceptableTagOrHeaderValue($value) {
        return is_string($value) || is_int($value) || is_float($value) || is_bool($value);
    }

    private function getParsedEmailItems($data) {

        $response = [];

        $required_fields = ['from', 'to'];
        $optional_fields = ['cc', 'bcc', 'reply_to'];

        foreach ($required_fields as $field) {

            if (!isset($data[$field])) {
                throw new \InvalidArgumentException('Field ' . $field . ' is required.');
            }

        }

        $data['from'] = $this->normalizeEmailField($data['from']);
        $data['to'] = $this->normalizeEmailFieldOrArray($data['to']);

        foreach ($optional_fields as $field) {

            if (isset($data[$field])) {
                $data[$field] = $this->normalizeEmailFieldOrArray($data[$field]);
            }

        }

        $response['from'] = $this->getEmailArrays($data['from'] ?? null);
        $response['to'] = $this->getEmailArrays($data['to'] ?? null);
        $response['cc'] = $this->getEmailArrays($data['cc'] ?? null);
        $response['bcc'] = $this->getEmailArrays($data['bcc'] ?? null);
        $response['reply_to'] = $this->getEmailArrays($data['reply_to'] ?? null);

        return $response;

    }

    private function getEmailArrays($email_list) {

        if (!$email_list) {
            return null;
        }

        if (is_array($email_list)) {

            $response = [];

            foreach ($email_list as $email) {
                $response[] = $email->toArray();
            }

            return $response;

        } else {

            return $email_list->toArray();

        }

    }

    private function normalizeEmailField($field) {

        if ($field instanceof EmailAddress) {
            return $field;
        } else {
            throw new \InvalidArgumentException('Email field must be an instance of EmailAddress.');
        }

    }

    private function normalizeEmailFieldOrArray($email_list) {

        if (is_array($email_list)) {
            return array_map([$this, 'normalizeEmailField'], $email_list);
        } else {
            return $this->normalizeEmailField($email_list);
        }

    }

    public function deleteScheduledEmail($reference_id) {

        $reference_id = $this->validateReferenceId($reference_id);

        $response = $this->sendRequest('DELETE', 'scheduled/' . $reference_id);

        if (is_array($response) && isset($response['success']) && $response['success']) {
            return true;
        }

        throw new \RuntimeException('The API returned an error: ' . $response['message']);

    }

    public function getScheduledEmails($page = 1, $per_page = 10) {

        if (!is_int($page) || $page < 1) {
            throw new \InvalidArgumentException('page must be a positive integer (>= 1).');
        }

        if (!is_int($per_page) || $per_page < 1) {
            throw new \InvalidArgumentException('per_page must be a positive integer (>= 1).');
        }

        if ($per_page > 100) {
            throw new \InvalidArgumentException('per_page cannot be greater than 100.');
        }

        $response = $this->sendRequest('GET', 'scheduled', [
            'page' => $page,
            'per_page' => $per_page,
        ]);

        if (is_array($response) && isset($response['success']) && $response['success'] && isset($response['data'])) {
            return $response['data'];
        }

        throw new \RuntimeException('The API returned an error: ' . $response['message']);

    }

    private function sendRequest($method, $endpoint, $data = null) {

        $method = strtoupper($method);
        $url = self::API_BASE_URL . ltrim($endpoint, '/');

        $ch = curl_init();

        if ($method === 'GET' && is_array($data) && !empty($data)) {
            $qs = http_build_query($data);
            $url .= (strpos($url, '?') === false ? '?' : '&') . $qs;
            $data = null;
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->api_key,
            'User-Agent: MailerooClient/1.0'
        ]);

        if ($method === 'GET') {
            curl_setopt($ch, CURLOPT_HTTPGET, true);
        } else {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($ch, CURLOPT_POSTFIELDS, is_null($data) ? '' : json_encode($data));
        }

        $raw = curl_exec($ch);

        if ($raw === false) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new \RuntimeException('HTTP request failed: ' . $err);
        }

        curl_close($ch);

        $decoded = json_decode($raw, true);

        if (!is_array($decoded) && json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('The API response is not valid JSON: ' . json_last_error_msg());
        }

        if (!isset($decoded['success']) || !is_bool($decoded['success'])) {
            throw new \RuntimeException('The API response is missing the "success" field.');
        }

        if (!$decoded['message']) {
            $decoded['message'] = 'Unknown';
        }

        return $decoded;

    }

}