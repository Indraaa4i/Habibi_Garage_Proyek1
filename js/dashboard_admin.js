/* ============================================================
   Habibi Garage — Admin Dashboard JS
   File: js/dashboard_admin.js
   ============================================================ */

// ── Navigasi Sidebar ────────────────────────────────────────
document.querySelectorAll('.menu-item').forEach(item => {
    item.addEventListener('click', () => {
        document.querySelectorAll('.menu-item').forEach(i => i.classList.remove('active'));
        document.querySelectorAll('.content-section').forEach(s => s.classList.remove('active'));
        item.classList.add('active');
        document.getElementById(item.getAttribute('data-target')).classList.add('active');
    });
});

// ── Search / Filter Tabel Dashboard ─────────────────────────
function filterTable() {
    const keyword = document.getElementById('searchInput').value.toLowerCase();
    document.querySelectorAll('#mainTable tbody tr').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(keyword) ? '' : 'none';
    });
}
