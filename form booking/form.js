document.addEventListener('DOMContentLoaded', function() {
    
    const dateInput = document.getElementById('bookingDate');
    if (dateInput) {
        const today = new Date().toISOString().split('T')[0];
        dateInput.setAttribute('min', today);
    }

    const bookingForm = document.getElementById('bookingForm');
    const btnSubmit = document.getElementById('btnSubmit');

    bookingForm.addEventListener('submit', function(e) {
        e.preventDefault(); // Mencegah reload halaman
       
        btnSubmit.innerHTML = 'Mengirim Pesanan...';
        btnSubmit.disabled = true;

        
        setTimeout(() => {
            alert('Terima kasih! Pesanan cuci mobil Anda telah diterima. Kami akan segera menghubungi Anda.');
            
            
            bookingForm.reset();
            
            
            btnSubmit.innerHTML = 'Konfirmasi Booking Sekarang';
            btnSubmit.disabled = false;
        }, 1500);
    });
});