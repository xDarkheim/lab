<nav class="main-navigation">
    <ul>
        <?php foreach ($navItems as $item): ?>
            <li>
                <a href="<?php echo htmlspecialchars($item['url']); ?>"
                   class="<?php echo ($item['is_active'] ? 'active' : ''); ?> <?php echo htmlspecialchars($item['class'] ?? ''); ?>">
                    <?php echo htmlspecialchars($item['text']); ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</nav>