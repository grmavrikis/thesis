<footer class="main-footer">
    <nav>
        <ul class="footer-nav">
            <li><a href="<?php echo $seoUrl->generate('index.php'); ?>"><?php echo TEXT_HOME; ?></a></li>
            <?php if ($scope === 'public'): ?>
                <li><a href="<?php echo $seoUrl->generate('administration/login.php'); ?>"><?php echo TEXT_ADMIN_LOGIN; ?></a></li>
            <?php endif; ?>
        </ul>
    </nav>
    <div class="main-footer-content">
        <p><?php echo TEXT_DIETITIAN_OFFICE; ?></p>
        <p><?php echo TEXT_ADDRESS; ?></p>
        <p><a href="mailto:info@thesis.com">Email: info@thesis.com</a></p>
        <p>&copy; <?php echo date("Y"); ?> <?php echo TEXT_COPYRIGHT; ?></p>
    </div>
</footer>