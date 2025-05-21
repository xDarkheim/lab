<!-- 
Copyright (c) 2025 Dmytro Hovenko
All rights reserved.
-->

# WebEngine Darkheim

WebEngine Darkheim is a web application focused on providing a resource hub for web development topics, including articles, news, and user account management.

## Features

*   **User Authentication**: Secure user registration ([page/register.php](page/register.php)), login ([page/login.php](page/login.php)), and logout ([modules/logout_process.php](modules/logout_process.php)) functionality.
*   **Account Management**:
    *   User dashboard ([page/account/dashboard.php](page/account/dashboard.php)).
    *   Profile updates ([page/account/edit_profile.php](page/account/edit_profile.php)).
    *   Account settings ([page/account/settings.php](page/account/settings.php)).
*   **Content System**:
    *   Article creation ([page/account/create_article.php](page/account/create_article.php)), editing ([page/account/edit_article.php](page/account/edit_article.php)), and management ([page/account/manage_articles.php](page/account/manage_articles.php)).
    *   News/Article display ([page/news.php](page/news.php)).
    *   Individual article view (e.g., `index.php?page=news&id=X`).
    *   Categorization of articles.
    *   Commenting system on articles ([modules/add_comment_process.php](modules/add_comment_process.php)).
*   **Admin Area**: Basic administration capabilities in the [admin/](admin/) directory (further details to be defined).
*   **Modular Design**:
    *   Core functionalities separated into [includes/](includes/).
    *   Form processing and actions in [modules/](modules/).
    *   Reusable UI elements in [includes/components/](includes/components/).
*   **Theming**: Support for themes, with a default theme located in [themes/default/](themes/default/).
*   **Basic Pages**: Includes standard pages like About ([page/about.php](page/about.php)), Contact ([page/contact.php](page/contact.php)), and a 404 error page ([page/404.php](page/404.php)).

## Project Structure

```
.
├── admin/              # Admin panel for site management
│   ├── auth.php
│   ├── index.php
│   └── manage_projects.php # (Consider if this is still relevant or part of general content management)
├── includes/           # Core files, libraries, and components
│   ├── bootstrap.php   # Main application bootstrap
│   ├── components/     # Reusable UI components (NavigationComponent, QuickLinksComponent, UserPanelComponent)
│   ├── config/         # Configuration files (app_config.php, routes_config.php, etc.)
│   ├── controllers/    # Business logic handlers (ProfileController, etc.)
│   ├── lib/            # Utility libraries (Database, Router, etc.)
│   ├── models/         # Database interaction models (Article, User, Category, Comment)
│   └── view/           # View templates or partials (if any, structure may vary)
├── modules/            # Action processing scripts (login_process.php, add_comment_process.php, etc.)
├── page/               # User-facing pages
│   ├── account/        # User account-specific pages
│   │   ├── create_article.php
│   │   ├── dashboard.php
│   │   ├── delete_article.php
│   │   ├── edit_article.php
│   │   ├── edit_profile.php
│   │   ├── manage_articles.php
│   │   └── settings.php
│   ├── 404.php
│   ├── about.php
│   ├── contact.php
│   ├── home.php
│   ├── login.php
│   ├── news.php
│   ├── project_single.php # (Consider if this is a distinct content type or part of articles)
│   └── register.php
├── public/             # Publicly accessible files
│   ├── index.php       # Main entry point of the application
│   └── assets/         # Frontend assets (CSS, JS, images)
├── themes/             # Site themes
│   └── default/        # Default theme (CSS, potentially templates/images)
├── .htaccess           # Apache server configuration
└── README.md           # This file
```

## Setup

1.  **Clone the repository.**
    ```bash
    git clone https://github.com/xDarkheim/lab
    cd lab 
    ```
2.  **Web Server Configuration**: Configure your web server (e.g., Apache, Nginx) to point the document root to the `public/` directory.
    *   Ensure `mod_rewrite` is enabled for Apache if using the provided `.htaccess`.
3.  **Database Setup**:
    *   Create a database (e.g., `simple` as per `app_config.php`).
    *   Update database connection details in `includes/config/app_config.php`:
        ```php
        // filepath: includes/config/app_config.php
        define('DB_HOST', 'your_db_host');
        define('DB_NAME', 'your_db_name');
        define('DB_USER', 'your_db_user');
        define('DB_PASS', 'your_db_password');
        define('DB_CHARSET', 'utf8mb4');
        ```
    *   Execute the following SQL queries to create the necessary tables. **Note:** These are basic schemas; you might need to adjust them based on all your application's requirements (e.g., constraints, default values, exact data types).

    ```sql
    CREATE TABLE `users` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `username` varchar(255) NOT NULL,
      `email` varchar(255) NOT NULL,
      `password_hash` varchar(255) NOT NULL,
      `role` varchar(50) DEFAULT 'user',
      `location` varchar(255) DEFAULT NULL,
      `user_status` varchar(255) DEFAULT NULL,
      `bio` text DEFAULT NULL,
      `website_url` varchar(255) DEFAULT NULL,
      `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
      `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
      PRIMARY KEY (`id`),
      UNIQUE KEY `username` (`username`),
      UNIQUE KEY `email` (`email`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

    CREATE TABLE `articles` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `user_id` int(11) NOT NULL,
      `title` varchar(255) NOT NULL,
      `short_description` text DEFAULT NULL,
      `full_text` longtext NOT NULL,
      `date` datetime NOT NULL,
      `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
      `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
      PRIMARY KEY (`id`),
      KEY `user_id` (`user_id`),
      CONSTRAINT `articles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

    CREATE TABLE `categories` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `name` varchar(255) NOT NULL,
      `slug` varchar(255) NOT NULL,
      PRIMARY KEY (`id`),
      UNIQUE KEY `slug` (`slug`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

    CREATE TABLE `article_categories` (
      `article_id` int(11) NOT NULL,
      `category_id` int(11) NOT NULL,
      PRIMARY KEY (`article_id`,`category_id`),
      KEY `category_id` (`category_id`),
      CONSTRAINT `article_categories_ibfk_1` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE CASCADE,
      CONSTRAINT `article_categories_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

    CREATE TABLE `comments` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `article_id` int(11) NOT NULL,
      `user_id` int(11) DEFAULT NULL, -- Allow anonymous comments if user_id is NULL
      `content` text NOT NULL,
      `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
      PRIMARY KEY (`id`),
      KEY `article_id` (`article_id`),
      KEY `user_id` (`user_id`),
      CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE CASCADE,
      CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL -- Or CASCADE if users must exist
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ```

4.  **Permissions**: Ensure the web server has appropriate write permissions for any directories that require it (e.g., `public/assets/uploads` if you plan to have file uploads, or cache directories if used).
5.  **Dependencies**: If there are PHP dependencies managed by Composer (currently no `composer.json` is listed), run `composer install`.
6.  **Access**: Open the application in your browser, pointing to your `public/` directory (e.g., `http://localhost/lab/public/` or your configured virtual host).

## Usage

*   **Browse Content**: Navigate to pages like Home ([public/index.php](public/index.php)), News ([public/index.php?page=news](public/index.php?page=news)), About ([public/index.php?page=about](public/index.php?page=about)), and Contact ([public/index.php?page=contact](public/index.php?page=contact)).
*   **User Accounts**:
    *   Register for a new account via [public/index.php?page=register](public/index.php?page=register).
    *   Login to an existing account via [public/index.php?page=login](public/index.php?page=login).
    *   Access your dashboard at [public/index.php?page=account_dashboard](public/index.php?page=account_dashboard) after logging in.
    *   Manage your profile, articles, and settings through the account pages.
*   **Admin Functions**: Access administrative features through the [admin/](admin/) section (requires appropriate authentication and further development for specific features).

## Customization

*   **Theming**:
    *   Modify the existing theme in [themes/default/css/style.css](themes/default/css/style.css).
    *   Create a new theme by duplicating the `default` theme directory and updating `SITE_THEME` in `includes/config/app_config.php`.
*   **Modules**: Add new functionality by creating new PHP scripts in the [modules/](modules/) directory for processing data or actions. Ensure they are appropriately routed or included.
*   **Pages**: Create new content pages within the [page/](page/) directory. Define their routes and access rules in `includes/config/routes_config.php`.
*   **Components**: Develop new reusable UI parts in [includes/components/](includes/components/) and integrate them into your pages.

## License

All rights reserved.