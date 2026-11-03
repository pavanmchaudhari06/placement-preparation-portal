<?php
/**
 * Footer Template: footer.php
 * Includes Bootstrap 5 JS Bundle and main application script.
 */
$asset_path = file_exists('js/main.js') ? '' : '../';
?>
    <!-- Bootstrap 5 Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Custom Main JS -->
    <script src="<?php echo $asset_path; ?>js/main.js"></script>
</body>
</html>
