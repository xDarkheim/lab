<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

define('ROOT_PATH', dirname(__DIR__));
define('DS', DIRECTORY_SEPARATOR);

require_once ROOT_PATH . DS . 'includes' . DS . 'config' . DS . 'app_config.php';

spl_autoload_register(function ($className) {
    $baseNamespace = 'App\\';
    $baseDir = ROOT_PATH . DS . 'includes' . DS;

    if (strpos($className, $baseNamespace) === 0) {
        $relativeClassName = substr($className, strlen($baseNamespace));
        $classPath = str_replace('\\', DS, $relativeClassName);
        $pathParts = explode(DS, $classPath);
        $fileName = array_pop($pathParts);
        $lowercaseDirectoryPath = implode(DS, array_map('strtolower', $pathParts));

        $filePath = $baseDir
                    . ($lowercaseDirectoryPath ? $lowercaseDirectoryPath . DS : '')
                    . $fileName . '.php';

        if (file_exists($filePath)) {
            require_once $filePath;
            return;
        }
    }
});
?>