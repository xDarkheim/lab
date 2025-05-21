<?php
namespace App\Components;

class QuickLinksComponent
{
    private array $linksConfig;
    private ?string $currentUserRole;

    public function __construct(array $linksConfig, ?string $currentUserRole)
    {
        $this->linksConfig = $linksConfig;
        $this->currentUserRole = $currentUserRole;
    }

    public function render(): string
    {
        if (empty($this->currentUserRole)) {
            return ''; 
        }

        $html = '<ul class="quick-links-widget__list">';
        foreach ($this->linksConfig as $link) {
            $requiresAdmin = isset($link['requires_admin']) && $link['requires_admin'];

            if ($requiresAdmin && $this->currentUserRole !== 'admin') {
                continue;
            }

            $url = htmlspecialchars($link['url']);
            $text = htmlspecialchars($link['text']);

            $html .= '<li><a href="' . $url . '">' . $text . '</a></li>';
        }
        $html .= '</ul>';
        return $html;
    }
}