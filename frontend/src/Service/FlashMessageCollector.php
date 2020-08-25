<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBag;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;

class FlashMessageCollector {

    /**
     * @var array
     */
    private $messages;

    /**
     * @var Session
     */
    private $session;

    public function __construct() {

        $this->messages = [];
        $this->session = null;
    }

    public function getSession() {

        $this->messages = [];
        $this->session = new Session(new NativeSessionStorage(), new AttributeBag());
    }

    /**
     * @return array
     */
    public function getAllMessages() {

        $messages = [
            'success' => [],
            'warning' => [],
            'error' => []
        ];

        foreach ($this->getSuccessMessages() as $message) {
            array_push($messages['success'], $message);
        }

        foreach ($this->getWarningMessages() as $message) {
            array_push($messages['warning'], $message);
        }

        foreach ($this->getErrorMessages() as $message) {
            array_push($messages['error'], $message);
        }

        return $messages;
    }

    /**
     * @return array
     */
    public function getSuccessMessages() {

        $this->getSession();

        foreach ($this->session->getFlashBag()->get('success', []) as $message) {
            array_push($this->messages, $message);
        }

        return $this->messages;
    }

    public function getErrorMessages() {

        $this->getSession();

        foreach ($this->session->getFlashBag()->get('error', []) as $message) {
            array_push($this->messages, $message);
        }

        return $this->messages;
    }

    public function getWarningMessages() {

        $this->getSession();

        foreach ($this->session->getFlashBag()->get('warning', []) as $message) {
            array_push($this->messages, $message);
        }

        return $this->messages;
    }
}
