<?php
namespace App\Components;

use App\Lib\View;

class NavigationComponent {
    private string $currentPageKey;
    private array $navConfig;

    public function __construct(string $currentPageKey) {
        $this->currentPageKey = $currentPageKey;
        
        $configFile = ROOT_PATH . DS . 'includes' . DS . 'config' . DS . 'navigation_config.php';
        if (file_exists($configFile)) {
            $this->navConfig = require $configFile;
        } else {
            error_log("Navigation configuration file not found: " . $configFile);
            $this->navConfig = ['main' => [], 'user_specific' => ['guest' => [], 'auth' => []]];
        }
    }

    public function render(): string {
        $navItems = [];
        $isLoggedIn = isset($_SESSION['user_id']);

        if (!empty($this->navConfig['main'])) {
            foreach ($this->navConfig['main'] as $item) {

                $navItems[] = [
                    'url' => $item['url'],
                    'text' => $item['text'],
                    'is_active' => $this->currentPageKey === $item['key'],
                    'class' => $item['class'] ?? '' 
                ];
            }
        }

        $userSpecificItem = null;
        if ($isLoggedIn && !empty($this->navConfig['user_specific']['auth'])) {
            $userSpecificItem = $this->navConfig['user_specific']['auth'];
        } elseif (!$isLoggedIn && !empty($this->navConfig['user_specific']['guest'])) {
            $userSpecificItem = $this->navConfig['user_specific']['guest'];
        }

        if ($userSpecificItem) {
            $navItems[] = [
                'url' => $userSpecificItem['url'],
                'text' => $userSpecificItem['text'],
                'is_active' => $this->currentPageKey === $userSpecificItem['key'],
                'class' => $userSpecificItem['class'] ?? ''
            ];
        }
        
        return View::make(
            ROOT_PATH . DS . 'includes' . DS . 'view' .  DS . '_main_navigation.php',
            ['navItems' => $navItems] 
        );
    }
}