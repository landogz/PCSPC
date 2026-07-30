{{-- Apply saved theme before paint to avoid flash --}}
<script>
    (function () {
        try {
            const saved = localStorage.getItem('hr-theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            const isDark = saved ? saved === 'dark' : prefersDark;
            document.documentElement.classList.toggle('dark', isDark);
        } catch (e) {
            // Ignore storage access errors
        }
    })();
</script>
