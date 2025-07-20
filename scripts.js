// Sidebar toggle functionality
document.getElementById('sidebarToggle').addEventListener('click', function () {
    document.getElementById('sidebar').classList.toggle('collapsed');
});

// Search filter functionality
document.getElementById('searchInput').addEventListener('keyup', function () {
    const filter = this.value.toLowerCase();
    const items = document.querySelectorAll('#sidebar ul li a');

    items.forEach(function (item) {
        const text = item.textContent.toLowerCase();
        if (text.includes(filter)) {
            item.parentElement.style.display = '';
        } else {
            item.parentElement.style.display = 'none';
        }
    });
});
