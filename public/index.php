<?php
require_once dirname(__DIR__) . '/includes/config/app_config.php'; // Site-wide settings
require_once dirname(__DIR__) . '/includes/bootstrap.php';         // Sets up important stuff like autoloader and session

// Import necessary classes using 'use'. Makes code cleaner.
use App\Lib\Database;
use App\Lib\Router;
use App\Components\NavigationComponent;
use App\Components\UserPanelComponent;
use App\Components\QuickLinksComponent;

// Initialize Database connection.
// We need this to talk to our database (like MySQL).
$database = new Database(); // Create a new Database object
$db = $database->getConnection(); // Get the actual PDO connection object from our Database class

// Check if the database connection worked. This is important!
if ($db === null) {
    // If $db is null, something went wrong. Log it and maybe show an error.
    error_log("Critical Error: Database connection failed. Check Database class and config.");
    // For a real site, we might want to show a user-friendly error page here.
}

// Load the routes configuration. This tells the site which file to load for which page.
$routes_config = require_once ROOT_PATH . DS . 'includes' . DS . 'config' . DS . 'routes_config.php';

// Create a Router object. This will figure out what content to show.
$router = new Router(ROOT_PATH . DS . 'page', $routes_config);

// Get the page key from the URL (e.g., 'home', 'about', 'news').
// If no page is specified, default to 'home'.
$page_key = isset($_GET['page']) ? trim(strtolower($_GET['page'])) : 'home';

// Initialize some variables that the router or page files might set.
$page_title = SITE_NAME; // Default page title
$content_file = '';      // Path to the content file for the page

// Tell the router to figure out the page title and content file based on $page_key.
$router->dispatch($page_key);

// Initialize $page_messages array to hold all messages for the current request
$page_messages = [];

// Centralized Flash Message Handling:
// Retrieve flash messages from session and merge into $page_messages
if (isset($_SESSION['flash_messages']) && is_array($_SESSION['flash_messages'])) {
    $page_messages = array_merge($page_messages, $_SESSION['flash_messages']);
    unset($_SESSION['flash_messages']); // Clear them so they don't show again
}

// Handle login errors from session.
// These might be set by the login_process.php script.
$page_login_errors_raw = $_SESSION['login_errors'] ?? []; // Get errors, or an empty array if none
if (isset($_SESSION['login_errors'])) {
    unset($_SESSION['login_errors']); // Clear them so they don't show again on refresh
}

// Convert login errors to the $page_messages format
if (!empty($page_login_errors_raw)) {
    foreach ($page_login_errors_raw as $error_text) {
        $page_messages[] = ['type' => 'error', 'text' => $error_text]; // 'error' type will be handled by the switch
    }
}

// Handle success messages for the sidebar (e.g., after successful login/registration).
$page_success_message_sidebar = $_SESSION['success_message_sidebar'] ?? null;
if (isset($_SESSION['success_message_sidebar'])) {
    unset($_SESSION['success_message_sidebar']); // Clear it after use
}

// For general page-level messages (not specific to sidebar/login widget).
// These could be success, error, or info messages for the whole page.
$page_messages = array_merge($page_messages, $_SESSION['page_messages'] ?? []);
if (isset($_SESSION['page_messages'])) {
    unset($_SESSION['page_messages']); // Clear after fetching
}

// Get the current user's role from the session, if they are logged in.
$current_user_role = $_SESSION['user_role'] ?? null;

// Prepare data to be passed to the main template file (template.php or header/footer).
$template_data = [];

// Set the page title for the HTML <title> tag.
$template_data['page_title'] = htmlspecialchars($page_title);

// Set the site name for the logo or header.
$template_data['site_name_logo'] = defined('SITE_NAME') ? htmlspecialchars(SITE_NAME) : 'WebEngine Darkheim';

// Add database handler and connection to template_data.
// This makes $database (our Database class instance) and $db (PDO object) available in templates if needed.
// Though, it's usually better to pass data through controllers/page files.
$template_data['database_handler'] = $database;
$template_data['db'] = $db;

// Create and render the main navigation menu.
$navigationComponent = new NavigationComponent($page_key); // Pass current page key for active state
$template_data['main_navigation_html'] = $navigationComponent->render();

// Define pages where the sidebar should not be displayed
$auth_pages_no_sidebar = ['login']; // Only hide sidebar for the login page

if (in_array($page_key, $auth_pages_no_sidebar)) {
    $template_data['sidebar_user_panel_html'] = '';
    $template_data['recent_news_sidebar_html'] = '';
    $template_data['show_sidebar'] = false;
} else {
    // Create and render the user panel for the sidebar (shows login form or user info).
    $userPanelComponent = new UserPanelComponent(
        $current_user_role,
        [], // $page_login_errors are no longer passed here for display in the widget
        $page_success_message_sidebar 
    );
    $sidebar_user_panel_html = $userPanelComponent->render(); // This variable is then used in the theme template
    $template_data['sidebar_user_panel_html'] = $sidebar_user_panel_html;

    // Load configuration for quick links and render the component.
    $quick_links_config_array = require_once ROOT_PATH . DS . 'includes' . DS . 'config' . DS . 'quick_links_config.php';
    $quickLinksComponent = new QuickLinksComponent($quick_links_config_array, $current_user_role);
    $template_data['recent_news_sidebar_html'] = $quickLinksComponent->render(); // Renamed for clarity, was 'sidebar_quick_links_html'
    $template_data['show_sidebar'] = true;
}

// Extract variables from $template_data array into the current scope.
extract($template_data);

// Now, include the header part of the theme.
require_once ROOT_PATH . DS . 'themes' . DS . SITE_THEME . DS . 'header.php';

// Display general page-level messages (like success/error banners).
if (!empty($page_messages)) {
    echo '<div class="page-messages-container">'; // Added a wrapper for better control if needed
    foreach ($page_messages as $message) {
        $typeClass = 'info'; // Default to 'info' style
        if (isset($message['type'])) {
            // Set CSS class based on message type for different styling
            switch (strtolower($message['type'])) { // Use strtolower for case-insensitivity
                case 'success':
                    $typeClass = 'success';
                    break;
                case 'error': // Ensure this matches the type we set for login errors
                case 'errors': // Keep 'errors' for compatibility if used elsewhere
                    $typeClass = 'errors'; // 'errors' class for error messages
                    break;
                case 'warning':
                    $typeClass = 'warning'; // 'warning' class
                    break;
                // Add more types if needed, e.g., 'info' is already default
            }
        }
        // Output the message HTML
        echo '<div class="messages ' . htmlspecialchars($typeClass) . '">';
        echo '<p>' . htmlspecialchars($message['text']) . '</p>';
        echo '</div>';
    }
    echo '</div>'; // Close the wrapper
}

// Include the main content file for the requested page.
// $content_file was set by the Router.
if (!empty($content_file) && file_exists($content_file)) {
    require_once $content_file; // This loads home.php, about.php, etc.
} else {
    // If the content file doesn't exist, something went wrong (e.g., bad page key).
    error_log("Error: Content file not found or invalid for page key '{$page_key}'. Expected at: {$content_file}");
    require_once ROOT_PATH . DS . 'page' . DS . '404.php'; // Show a 404 error page
}

// Finally, include the footer part of the theme.
require_once ROOT_PATH . DS . 'themes' . DS . SITE_THEME . DS . 'footer.php';
?>