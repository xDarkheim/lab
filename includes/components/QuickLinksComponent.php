<?php
namespace App\Components;

class QuickLinksComponent
{
    private array $config;
    private ?string $currentUserRole;

    public function __construct(array $config, ?string $currentUserRole)
    {
        $this->config = $config;
        $this->currentUserRole = $currentUserRole;
    }

    public function render(): string
    {
        $output = '';
        $linksToDisplay = [];

        if (!isset($this->config['links']) || !is_array($this->config['links'])) {
            return '';
        }

        foreach ($this->config['links'] as $link) {
            if (!is_array($link)) {
                continue;
            }

            $showLink = false;
            if (empty($link['roles'])) {
                $showLink = true;
            } elseif ($this->currentUserRole && isset($link['roles']) && is_array($link['roles']) && in_array($this->currentUserRole, $link['roles'], true)) {
                $showLink = true;
            }

            if ($showLink) {
                $linksToDisplay[] = $link;
            }
        }

        if (!empty($linksToDisplay)) {
            $output .= '<div class="quick-links-widget">';
            
            if (!empty($this->config['title'])) {
                $output .= '<h3 class="widget-title">' . htmlspecialchars($this->config['title']) . '</h3>';
            }
            
            $output .= '<ul class="quick-links-list">';
            foreach ($linksToDisplay as $linkItem) {
                $url = $linkItem['url'] ?? '#';
                $text = $linkItem['text'] ?? 'Unnamed Link';

                $output .= '<li><a href="' . htmlspecialchars($url) . '">' . htmlspecialchars($text) . '</a></li>';
            }
            $output .= '</ul>';
            $output .= '</div>';
        }
        return $output;
    }
}