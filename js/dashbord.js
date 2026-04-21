
document.querySelectorAll('.menu-item').forEach(item => {
    item.addEventListener('click', () => {

        document.querySelectorAll('.menu-item').forEach(i => i.classList.remove('active'));
    
        document.querySelectorAll('.content-section').forEach(s => s.classList.remove('active'));
        
        
        item.classList.add('active');
        const target = item.getAttribute('data-target');
        document.getElementById(target).classList.add('active');
    });
});

function selesaiCuci(btn, harga) {
    const row = btn.closest('tr');
    const plat = row.cells[0].innerText;
    
    row.remove();
    
    const recapTable = document.getElementById('recap-table-body');
    const today = new Date().toLocaleDateString('id-ID', { month: 'short', day: 'numeric', year: 'numeric' });
    
    const newRow = `
        <tr>
            <td>${plat}</td>
            <td>${today}</td>
            <td>${harga/1000}k</td>
            <td><span class="status complete">Complete</span></td>
        </tr>
    `;
    recapTable.insertAdjacentHTML('afterbegin', newRow);
    
    updateStats();
    alert(`Kendaraan ${plat} selesai dicuci!`);
}

function validasiBayar(btn) {
    btn.innerText = "Divalidasi ✓";
    btn.disabled = true;
    btn.style.background = "#2ecc71";
    
    const row = btn.closest('tr');
    row.style.opacity = "0.5";
    alert("Transfer telah divalidasi!");
}

function updateStats() {
    let totalHarian = 0;
    const rows = document.querySelectorAll('#recap-table-body tr');
    
    rows.forEach(row => {
        const hargaText = row.cells[2].innerText.replace('k', '000').replace(/[^0-9]/g, '');
        totalHarian += parseInt(hargaText) || 0;
    });

    const formatted = "Rp " + totalHarian.toLocaleString('id-ID');
    document.getElementById('harian').innerText = formatted;
    document.getElementById('stat-pendapatan').innerText = formatted;
    
    const antreanCount = document.querySelectorAll('#live-queue tr').length;
    document.getElementById('count-antrean').innerText = antreanCount;
}

updateStats();