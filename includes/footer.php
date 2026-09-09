<?php
/**
 * AulaCode - Pie HTML común
 */
?>
<?php if (is_logged_in()): ?>
    </main>
</div>
<?php else: ?>
</main>
<?php endif; ?>
<script src="<?= e(url('assets/js/app.js')) ?>"></script>
</body>
</html>
