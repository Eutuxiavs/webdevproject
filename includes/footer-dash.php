<?php
/**
 * includes/footer-dash.php
 * Closes the <main> opened by header-dash.php, includes the floating
 * chat widget (unless $showChatWidget is explicitly set to false), and
 * loads main.js. Include this right before the closing of the page.
 */
$showChatWidget = $showChatWidget ?? true;
?>
</main>

<?php if ($showChatWidget): ?>
<?php require __DIR__ . '/chat-widget.php'; ?>
<?php endif; ?>
<script src="<?= asset_url('js/main.js') ?>"></script>
</body>
</html>
