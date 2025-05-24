<section class="hero-section">
    <div class="hero-content">
        <h1 class="hero-title">Welcome to DarkheimHub</h1>
        <p class="hero-subtitle">Join discussions, share experiences, and learn new things in the world of web technologies.</p>
        <div class="hero-actions">
            <a href="/index.php?page=news" class="button button-primary">Latest Articles</a>
            <a href="/index.php?page=register" class="button button-secondary">Join Us</a>
        </div>
    </div>
</section>

<section class="home-section latest-posts">
    <h2 class="section-title">Recent Articles</h2>
    <div class="posts-grid">
        <?php
        $recent_articles = []; 

        if (!$db) {
            echo '<div class="message message--warning text-center full-width-message"><p>Database connection unavailable. Please try again later.</p></div>';
        } else {
            if ($db) {
                try {
                    $table_exists_stmt = $db->query("SHOW TABLES LIKE 'articles'");
                    if ($table_exists_stmt && $table_exists_stmt->rowCount() > 0) {
                        $query = "SELECT id, title, short_description, created_at FROM articles ORDER BY created_at DESC LIMIT :limit";
                        $stmt = $db->prepare($query);
                        $stmt->bindValue(':limit', 4, PDO::PARAM_INT);
                        $stmt->execute();
                        $fetched_articles = $stmt->fetchAll(PDO::FETCH_ASSOC);

                        foreach ($fetched_articles as $article) {
                            $recent_articles[] = [
                                'id' => $article['id'],
                                'title' => $article['title'],
                                'excerpt' => $article['short_description'],
                                'image' => null,
                            ];
                        }

                    } else {
                        error_log("Warning: 'articles' table not found. Using static placeholders for home page recent articles.");
                    }
                } catch (PDOException $e) {
                    error_log("Error fetching recent articles: " . $e->getMessage());
                }
            } else {
                error_log("Error: Database connection object (\$db) not available in home.php.");
            }

            if (empty($recent_articles)) {
                echo '<div class="message message--info text-center full-width-message"><p>No recent articles found.</p></div>';
            } else {
                foreach ($recent_articles as $post):
        ?>
        <article class="post-card">
            <?php if (!empty($post['image'])): ?>
            <a href="/index.php?page=news&id=<?php echo $post['id']; ?>" class="post-card-image-link">
                <img src="<?php echo htmlspecialchars($post['image']); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>" class="post-card-image">
            </a>
            <?php endif; ?>
            <div class="post-card-content">
                <h3 class="post-card-title">
                    <a href="/index.php?page=news&id=<?php echo $post['id']; ?>"><?php echo htmlspecialchars($post['title']); ?></a>
                </h3>
                <p class="post-card-excerpt"><?php echo htmlspecialchars($post['excerpt']); ?></p>
                <a href="/index.php?page=news&id=<?php echo $post['id']; ?>" class="button button-outline button-small">Read More</a>
            </div>
        </article>
        <?php
                endforeach; 
            } 
        }
        ?>
    </div>
    <?php if (!empty($recent_articles) || $db): ?>
    <div class="view-all-articles-container text-center">
         <br><a href="/index.php?page=news" class="button button-secondary">View All Articles</a>
    </div>
    <?php endif; ?>
</section>

<?php if (!isset($_SESSION['user_id'])): ?>
<section class="home-section call-to-action">
    <h2 class="section-title">Stay Updated!</h2>
    <p>Subscribe to our newsletter or register to join the community and never miss a post.</p>
    <div class="cta-actions">
        <a href="/index.php?page=register" class="button button-primary button-large">Register Now</a>
        <a href="/index.php?page=contact" class="button button-secondary button-large">Contact Us</a>
    </div>
</section>
<?php endif; ?>