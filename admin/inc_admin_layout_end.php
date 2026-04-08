        </main><!-- /admin-content -->
    </div><!-- /admin-main -->
</div><!-- /admin-shell -->

<!-- Sidebar overlay for mobile -->
<div class="admin-sidebar-overlay" id="adminSidebarOverlay"></div>

<script>
(function () {
    var toggle  = document.getElementById('adminSidebarToggle');
    var sidebar = document.getElementById('adminSidebar');
    var overlay = document.getElementById('adminSidebarOverlay');
    if (!toggle || !sidebar) return;

    function openSidebar() {
        sidebar.classList.add('is-open');
        overlay.classList.add('active');
        toggle.setAttribute('aria-expanded', 'true');
        document.body.style.overflow = 'hidden';
    }
    function closeSidebar() {
        sidebar.classList.remove('is-open');
        overlay.classList.remove('active');
        toggle.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = '';
    }

    toggle.addEventListener('click', function () {
        sidebar.classList.contains('is-open') ? closeSidebar() : openSidebar();
    });
    overlay.addEventListener('click', closeSidebar);
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeSidebar();
    });
})();

// Confirm-delete: any form with class admin-confirm-delete-form,
// or any button with class admin-confirm-delete-btn
(function () {
    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!form.classList.contains('admin-confirm-delete-form')) return;
        if (!confirm('Delete permanently? This cannot be undone.')) {
            e.preventDefault();
        }
    });

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.admin-confirm-delete-btn');
        if (!btn) return;
        if (!confirm('Delete permanently? This cannot be undone.')) {
            e.preventDefault();
        }
    });
})();
</script>
</body>
</html>
