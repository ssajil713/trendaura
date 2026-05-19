// Admin Panel JavaScript
(function() {
    'use strict';

    // Sidebar toggle
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarClose = document.getElementById('sidebarClose');
    const sidebar = document.getElementById('adminSidebar');
    const overlay = document.getElementById('sidebarOverlay');

    function openSidebar() {
        if (sidebar) sidebar.classList.add('open');
        if (overlay) overlay.classList.add('show');
    }
    function closeSidebar() {
        if (sidebar) sidebar.classList.remove('open');
        if (overlay) overlay.classList.remove('show');
    }

    if (sidebarToggle) sidebarToggle.addEventListener('click', openSidebar);
    if (sidebarClose) sidebarClose.addEventListener('click', closeSidebar);
    if (overlay) overlay.addEventListener('click', closeSidebar);

    // Revenue chart
    const revenueCanvas = document.getElementById('revenueChart');
    if (revenueCanvas) {
        const ctx = revenueCanvas.getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: revenueMonths || [],
                datasets: [{
                    label: 'Revenue',
                    data: revenueData || [],
                    borderColor: '#6C3BF7',
                    backgroundColor: 'rgba(108,59,247,0.08)',
                    borderWidth: 2,
                    pointBackgroundColor: '#6C3BF7',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { callback: function(v) { return '₹' + v.toLocaleString('en-IN'); } },
                        grid: { color: 'rgba(0,0,0,0.05)' }
                    },
                    x: {
                        grid: { display: false }
                    }
                }
            }
        });
    }

    // Image preview
    document.querySelectorAll('.image-input').forEach(function(input) {
        input.addEventListener('change', function() {
            const preview = this.closest('.image-upload-group').querySelector('.image-preview img, .image-preview');
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    if (preview.tagName === 'IMG') {
                        preview.src = e.target.result;
                    } else {
                        preview.innerHTML = '<img src="' + e.target.result + '" style="width:100%;height:100%;object-fit:cover;">';
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    });

    // Confirm delete
    window.confirmDelete = function(msg) {
        return confirm(msg || 'Are you sure you want to delete this? This action cannot be undone.');
    };

    // Auto-hide alerts
    setTimeout(function() {
        document.querySelectorAll('.alert-dismissible').forEach(function(el) {
            var bsAlert = new bootstrap.Alert(el);
            bsAlert.close();
        });
    }, 5000);

})();
