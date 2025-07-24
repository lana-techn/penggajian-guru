<?php if (isset($_SESSION['user_id'])): ?>
    </div>
    </main>
    </div>
<?php else: ?>
    </main>
<?php endif; ?>
</div>

<!-- Toast Notifications -->
<div id="toast-container" class="fixed top-4 right-4 z-50 space-y-2"></div>

<script>
    // Toast notification system
    function showToast(message, type = 'info', duration = 5000) {
        const container = document.getElementById('toast-container');
        const toast = document.createElement('div');

        const colors = {
            success: 'bg-green-500 text-white',
            error: 'bg-red-500 text-white',
            warning: 'bg-yellow-500 text-black',
            info: 'bg-blue-500 text-white'
        };

        const icons = {
            success: 'fa-check-circle',
            error: 'fa-times-circle',
            warning: 'fa-exclamation-triangle',
            info: 'fa-info-circle'
        };

        toast.className = `flex items-center p-4 rounded-lg shadow-lg transform transition-all duration-300 translate-x-full opacity-0 ${colors[type] || colors.info}`;
        toast.innerHTML = `
                <i class="fas ${icons[type] || icons.info} mr-3"></i>
                <span class="flex-1">${message}</span>
                <button onclick="removeToast(this.parentElement)" class="ml-3 text-white hover:text-gray-200">
                    <i class="fas fa-times"></i>
                </button>
            `;

        container.appendChild(toast);

        // Animate in
        setTimeout(() => {
            toast.classList.remove('translate-x-full', 'opacity-0');
        }, 10);

        // Auto remove
        setTimeout(() => {
            removeToast(toast);
        }, duration);
    }

    function removeToast(toast) {
        toast.classList.add('translate-x-full', 'opacity-0');
        setTimeout(() => {
            if (toast.parentElement) {
                toast.parentElement.removeChild(toast);
            }
        }, 300);
    }

    // Global error handler for AJAX requests
    window.addEventListener('error', function(e) {
        console.error('Global error:', e);
    });

    // Handle connection errors
    window.addEventListener('online', function() {
        showToast('Koneksi internet tersambung kembali', 'success');
    });

    window.addEventListener('offline', function() {
        showToast('Koneksi internet terputus', 'warning');
    });
</script>
</body>

</html>