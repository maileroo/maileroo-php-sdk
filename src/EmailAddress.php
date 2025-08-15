<?php

namespace Maileroo;

class EmailAddress {

    private $address;
    private $display_name;

    public function __construct($address, $display_name = null) {

        if (!is_string($address) || trim($address) === '') {
            throw new \InvalidArgumentException('Email address must be a non-empty string.');
        }

        if (!filter_var($address, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid email address format: ' . $address);
        }

        if ($display_name !== null && (!is_string($display_name) || trim($display_name) === '')) {
            throw new \InvalidArgumentException('Display name must be a non-empty string or null.');
        }

        $this->address = $address;
        $this->display_name = $display_name;

    }

    public function getAddress() {
        return $this->address;
    }

    public function getDisplayName() {
        return $this->display_name;
    }

    public function toArray() {

        $data = ['address' => $this->address];

        if ($this->display_name !== null) {
            $data['display_name'] = $this->display_name;
        }

        return $data;

    }

}
