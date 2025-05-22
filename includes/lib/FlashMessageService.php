<?php
namespace App\Lib;

class FlashMessageService{
    private const SESSION_KEY = '_flash_messages';

    public function __construct()
    {
        if(session_status() === PHP_SESSION_NONE){
            // session_start();
        }
        if (!isset($_SESSION[self::SESSION_KEY]) || !is_array($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = [];
        }
    }

    public function addMessages(string $text, string $type = 'info') : void
    {
        $_SESSION[self::SESSION_KEY][] = ['text' => $text, 'type' => strtolower($type)];
    }

    public function addSuccess(string $text): void
    {
        $this->addMessages($text, 'success');
    }

    public function addError(string $text): void
    {
        $this->addMessages($text, 'error');
    }

    public function addInfo(string $text): void
    {
        $this->addMessages($text, 'info');
    }

    public function addWarning(string $text): void
    {
        $this->addMessages($text, 'warning');
    }

    public function getMessages(): array
    {
        $messages = $_SESSION[self::SESSION_KEY] ?? [];
        $this->clearMessages();
        return $messages;
    }

    public function hasMessages(): bool
    {
        return !empty($_SESSION[self::SESSION_KEY]);
    }

    public function clearMessages(): void
    {
        $_SESSION[self::SESSION_KEY] = [];
    }
}