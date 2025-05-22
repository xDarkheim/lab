<?php
require_once dirname(__DIR__) . '/includes/config/app_config.php'; 
require_once dirname(__DIR__) . '/includes/bootstrap.php';


use App\Lib\Database;
use App\Lib\Router;
use App\Lib\FlashMessageService;
use App\Components\NavigationComponent;
use App\Components\UserPanelComponent;
use App\Components\QuickLinksComponent;

$database = new Database();
$db = $database->getConnection(); 

if ($db === null) {
    error_log("Critical Error: Database connection failed. Check Database class and config.");
}

$flashMessageService = new FlashMessageService();

$routes_config = require_once ROOT_PATH . DS . 'includes' . DS . 'config' . DS . 'routes_config.php';

$router = new Router(ROOT_PATH . DS . 'page', $routes_config);

$page_key = isset($_GET['page']) ? trim(strtolower($_GET['page'])) : 'home';

$router->dispatch($page_key);

$page_messages_from_service = $flashMessageService->getMessages();

$page_success_message_sidebar_text = $_SESSION['success_message_sidebar'] ?? null;
if (isset($_SESSION['success_message_sidebar'])) {
    unset($_SESSION['success_message_sidebar']);
}

$final_page_messages = [];
if ($page_success_message_sidebar_text !== null) {
    foreach ($page_messages_from_service as $msg) {
        if (!($msg['text'] === $page_success_message_sidebar_text && $msg['type'] === 'success')) {
            $final_page_messages[] = $msg;
        }
    }
} else {
    $final_page_messages = $page_messages_from_service;
}

$current_user_role = $_SESSION['user_role'] ?? null;

$template_data = [];

$template_data['page_title'] = htmlspecialchars($page_title ?? SITE_NAME);

$template_data['site_name_logo'] = defined('SITE_NAME') ? htmlspecialchars(SITE_NAME) : 'WebEngine Darkheim';

$template_data['database_handler'] = $database;
$template_data['db'] = $db;

$template_data['page_messages'] = $final_page_messages;

$navigationComponent = new NavigationComponent($page_key); 
$template_data['main_navigation_html'] = $navigationComponent->render();

$auth_pages_no_sidebar = ['login']; 

if (in_array($page_key, $auth_pages_no_sidebar)) {
    $template_data['sidebar_user_panel_html'] = '';
    $template_data['recent_news_sidebar_html'] = '';
    $template_data['show_sidebar'] = false;
} else {
    $userPanelComponent = new UserPanelComponent(
        $current_user_role,
        [],
        $page_success_message_sidebar_text 
    );
    $sidebar_user_panel_html = $userPanelComponent->render();
    $template_data['sidebar_user_panel_html'] = $sidebar_user_panel_html;

    $quick_links_config_array = require_once ROOT_PATH . DS . 'includes' . DS . 'config' . DS . 'quick_links_config.php';
    $quickLinksComponent = new QuickLinksComponent($quick_links_config_array, $current_user_role);
    $template_data['recent_news_sidebar_html'] = $quickLinksComponent->render();
    $template_data['show_sidebar'] = true;
}

extract($template_data);

require_once ROOT_PATH . DS . 'themes' . DS . SITE_THEME . DS . 'header.php';

if (!empty($page_messages)) {
    echo '<div class="page-messages-container">';
    foreach ($page_messages as $message) {
        $typeClass = 'info'; 
            switch (strtolower($message['type'])) { 
                case 'success':
                    $typeClass = 'success';
                    break;
                case 'error':
                case 'errors':
                    $typeClass = 'errors';
                    break;
                case 'warning':
                    $typeClass = 'warning';
                    break;
            }
       
        echo '<div class="messages ' . htmlspecialchars($typeClass) . '">';
        echo '<p>' . htmlspecialchars($message['text']) . '</p>';
        echo '</div>';
    }
    echo '</div>';
}

if (!empty($content_file) && file_exists($content_file)) {
    require_once $content_file;
} else {
    error_log("Error: Content file not found or invalid for page key '{$page_key}'. Expected at: {$content_file}");
    require_once ROOT_PATH . DS . 'page' . DS . '404.php';
}

require_once ROOT_PATH . DS . 'themes' . DS . SITE_THEME . DS . 'footer.php';
?>