<?php

namespace Maileroo;

class Attachment {

    private $file_name;
    private $content_type;
    private $content;
    private $inline;

    private function __construct($file_name, $base64_content, $content_type = null, $inline = false) {

        if (!is_string($file_name) || $file_name === '') {
            throw new \InvalidArgumentException('file_name is required.');
        }

        if (!is_string($base64_content) || $base64_content === '') {
            throw new \InvalidArgumentException('content must be a non-empty base64 string.');
        }

        $this->file_name = $file_name;
        $this->content = $base64_content;
        $this->content_type = $content_type ?: 'application/octet-stream';
        $this->inline = (bool)$inline;

    }

    public static function fromContent($file_name, $content, $content_type = null, $inline = false, $is_base64 = false) {

        if (!is_string($content)) {
            throw new \InvalidArgumentException('content must be a string.');
        }

        $binary = $is_base64 ? base64_decode($content, true) : $content;

        if ($binary === false) {
            throw new \InvalidArgumentException('Invalid base64 content provided.');
        }

        $detected_type = $content_type ?: self::detectMimeFromBuffer($binary) ?: 'application/octet-stream';
        $b64 = base64_encode($binary);

        return new self($file_name, $b64, $detected_type, $inline);

    }

    public static function fromStream($file_name, $stream, $content_type = null, $inline = false) {

        if (!is_resource($stream)) {
            throw new \InvalidArgumentException('stream must be a valid resource.');
        }

        @rewind($stream);

        $binary = stream_get_contents($stream);

        if ($binary === false) {
            throw new \RuntimeException('Failed to read from stream.');
        }

        $detected_type = $content_type ?: self::detectMimeFromBuffer($binary) ?: 'application/octet-stream';
        $b64 = base64_encode($binary);

        return new self($file_name, $b64, $detected_type, $inline);

    }

    public static function fromFile($path, $content_type = null, $inline = false) {

        if (!is_string($path) || $path === '' || !is_file($path) || !is_readable($path)) {
            throw new \InvalidArgumentException('path must be a readable file.');
        }

        $file_name = basename($path);
        $binary = file_get_contents($path);

        if ($binary === false) {
            throw new \RuntimeException('Failed to read file: ' . $path);
        }

        $detected_type = $content_type ?: self::detectMimeFromPath($path) ?: self::detectMimeFromBuffer($binary) ?: 'application/octet-stream';
        $b64 = base64_encode($binary);

        return new self($file_name, $b64, $detected_type, $inline);

    }

    public function getFileName() {
        return $this->file_name;
    }

    public function getContentType() {
        return $this->content_type;
    }

    public function getContent() {
        return $this->content;
    }

    public function isInline() {
        return $this->inline;
    }

    public function toArray() {

        return [
            'file_name' => $this->file_name,
            'content_type' => $this->content_type ?: 'application/octet-stream',
            'content' => $this->content,
            'inline' => $this->inline,
        ];

    }

    private static function detectMimeFromPath($path) {

        if (function_exists('finfo_open')) {

            $finfo = @finfo_open(FILEINFO_MIME_TYPE);

            if ($finfo) {

                $type = @finfo_file($finfo, $path);

                @finfo_close($finfo);

                if (is_string($type) && $type !== '') {
                    return $type;
                }

            }

        }

        return self::detectMimeFromExtension($path) ?: 'application/octet-stream';

    }

    private static function detectMimeFromExtension($path) {

        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        $map = [
            'png'   => 'image/png',
            'jpg'   => 'image/jpeg',
            'jpeg'  => 'image/jpeg',
            'gif'   => 'image/gif',
            'bmp'   => 'image/bmp',
            'webp'  => 'image/webp',
            'svg'   => 'image/svg+xml',
            'tiff'  => 'image/tiff',
            'ico'   => 'image/x-icon',
            'pdf'   => 'application/pdf',
            'doc'   => 'application/msword',
            'docx'  => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls'   => 'application/vnd.ms-excel',
            'xlsx'  => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'ppt'   => 'application/vnd.ms-powerpoint',
            'pptx'  => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'odt'   => 'application/vnd.oasis.opendocument.text',
            'ods'   => 'application/vnd.oasis.opendocument.spreadsheet',
            'odp'   => 'application/vnd.oasis.opendocument.presentation',
            'rtf'   => 'application/rtf',
            'txt'   => 'text/plain',
            'csv'   => 'text/csv',
            'tsv'   => 'text/tab-separated-values',
            'json'  => 'application/json',
            'xml'   => 'application/xml',
            'html'  => 'text/html',
            'htm'   => 'text/html',
            'md'    => 'text/markdown',
            'zip'   => 'application/zip',
            'tar'   => 'application/x-tar',
            'gz'    => 'application/gzip',
            'tgz'   => 'application/gzip',
            'rar'   => 'application/vnd.rar',
            '7z'    => 'application/x-7z-compressed',
            'mp3'   => 'audio/mpeg',
            'wav'   => 'audio/wav',
            'ogg'   => 'audio/ogg',
            'm4a'   => 'audio/mp4',
            'flac'  => 'audio/flac',
            'aac'   => 'audio/aac',
            'mp4'   => 'video/mp4',
            'webm'  => 'video/webm',
            'mov'   => 'video/quicktime',
            'avi'   => 'video/x-msvideo',
            'mkv'   => 'video/x-matroska',
            'flv'   => 'video/x-flv',
            'wmv'   => 'video/x-ms-wmv',
            'm4v'   => 'video/x-m4v',
            'woff'  => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf'   => 'font/ttf',
            'otf'   => 'font/otf',
            'eot'   => 'application/vnd.ms-fontobject',

        ];

        return $map[$ext] ?? null;

    }

    private static function detectMimeFromBuffer($buffer) {

        if (function_exists('finfo_open')) {

            $finfo = @finfo_open(FILEINFO_MIME_TYPE);

            if ($finfo) {

                $type = @finfo_buffer($finfo, $buffer);

                @finfo_close($finfo);

                if (is_string($type) && $type !== '') {
                    return $type;
                }

            }

        }

        return null;

    }

}
