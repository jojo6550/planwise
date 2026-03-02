    </main><!-- /.admin-content -->
</div><!-- /.admin-main -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
/* === Admin Sidebar Toggle === */
function toggleSidebar() {
    const sidebar = document.getElementById('adminSidebar');
    const overlay = document.getElementById('sidebarOverlay');
    sidebar.classList.toggle('sidebar-open');
    overlay.classList.toggle('sidebar-open');
    document.body.style.overflow = sidebar.classList.contains('sidebar-open') ? 'hidden' : '';
}

function closeSidebar() {
    const sidebar = document.getElementById('adminSidebar');
    const overlay = document.getElementById('sidebarOverlay');
    sidebar.classList.remove('sidebar-open');
    overlay.classList.remove('sidebar-open');
    document.body.style.overflow = '';
}

/* === Auto-dismiss alerts === */
document.querySelectorAll('.alert.auto-dismiss').forEach(function(alert) {
    setTimeout(function() {
        const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
        if (bsAlert) bsAlert.close();
    }, 5000);
});

/* === Confirm delete helper === */
function confirmDelete(message) {
    return confirm(message || 'Are you sure you want to delete this? This action cannot be undone.');
}
</script>
<?php if (isset($extraScripts)) echo $extraScripts; ?>
</body>
</html>
