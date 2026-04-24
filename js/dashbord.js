// Navigasi Menu Sidebar
document.querySelectorAll('.menu-item').forEach(item => {
    item.addEventListener('click', () => {
        document.querySelectorAll('.menu-item').forEach(i => i.classList.remove('active'));
        document.querySelectorAll('.content-section').forEach(s => s.classList.remove('active'));
        
        item.classList.add('active');
        const target = item.getAttribute('data-target');
        document.getElementById(target).classList.add('active');
    });
});

// Modal Pop-up Toggle
function toggleModal() {
    const modal = document.getElementById('modalPelanggan');
    if (modal.style.display === 'flex') {
        modal.style.display = 'none';
    } else {
        modal.style.display = 'flex';
    }
}

// Tambah Pelanggan Baru ke Dashboard
function simpanPelanggan() {
    const nama = document.getElementById('input-nama').value;
    const plat = document.getElementById('input-plat').value;
    const paketSelect = document.getElementById('input-paket');
    const paket = paketSelect.value;
    const harga = paketSelect.options[paketSelect.selectedIndex].getAttribute('data-price');
    const jam = document.getElementById('input-jam').value;

    if (nama === "" || plat === "") {
        alert("Harap isi nama dan plat nomor!");
        return;
    }

    const table = document.getElementById('live-queue');
    const row = `
        <tr>
            <td>${plat.toUpperCase()} <br><small>(${nama})</small></td>
            <td>${paket}</td>
            <td>${jam}</td>
            <td><button class="btn-selesai" onclick="selesaiCuci(this, ${harga})">Selesai</button></td>
        </tr>
    `;

    table.insertAdjacentHTML('beforeend', row);
    
    // Reset dan Tutup
    document.getElementById('input-nama').value = "";
    document.getElementById('input-plat').value = "";
    toggleModal();
    updateStats();
}

// Pindahkan data ke Recap
function tambahKeRecap(plat, harga) {
    const tableBody = document.getElementById('recap-table-body');
    const date = new Date().toLocaleDateString('id-ID');
    const hargaK = (parseInt(harga) / 1000) + "k";

    const row = `
        <tr>
            <td>${plat}</td>
            <td>${date}</td>
            <td>${hargaK}</td>
            <td><span style="color: #2ecc71; font-weight: bold;">Lunas</span></td>
        </tr>
    `;
    tableBody.insertAdjacentHTML('afterbegin', row);
    updateStats();
}

// Aksi Tombol Selesai di Dashboard
function selesaiCuci(btn, harga) {
    const row = btn.closest('tr');
    const platRaw = row.cells[0].innerText.split('\n')[0]; // Ambil platnya saja
    row.remove();
    tambahKeRecap(platRaw, harga);
}

// Validasi Pembayaran Transfer
function validasiBayar(btn) {
    const row = btn.closest('tr');
    const info = row.cells[0].innerText;
    const hargaRaw = row.cells[1].innerText.replace(/[^0-9]/g, '');

    // Ekstrak plat dari string "Nama (Plat)"
    const plat = info.includes('(') ? info.split('(')[1].replace(')', '') : info;

    btn.innerText = "Berhasil";
    btn.style.background = "#bdc3c7";
    btn.disabled = true;

    setTimeout(() => {
        row.remove();
        tambahKeRecap(plat, hargaRaw);
    }, 800);
}

// Update Angka Statistik
function updateStats() {
    let total = 0;
    const recapRows = document.querySelectorAll('#recap-table-body tr');
    
    recapRows.forEach(r => {
        const h = r.cells[2].innerText.replace('k', '000').replace(/[^0-9]/g, '');
        total += parseInt(h) || 0;
    });

    const formatted = "Rp " + total.toLocaleString('id-ID');
    
    // Update semua label harga
    document.getElementById('harian').innerText = formatted;
    document.getElementById('bulanan').innerText = formatted;
    document.getElementById('stat-pendapatan').innerText = formatted;
    
    // Update jumlah antrean
    const antreanCount = document.querySelectorAll('#live-queue tr').length;
    document.getElementById('count-antrean').innerText = antreanCount;
}

// Jalankan update pertama kali
updateStats();