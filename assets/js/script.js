document.addEventListener('DOMContentLoaded', function () {
    const donateForm = document.querySelector('form[data-donate-form]');
    if (donateForm) {
        donateForm.addEventListener('submit', function (e) {
            const amount = parseInt(this.amount.value || '0', 10);
            const fileInput = this.proof_file;
            const allowed = ['image/jpeg', 'image/png', 'application/pdf'];

            if (amount < 10000) {
                alert('Nominal minimal donasi adalah Rp10.000.');
                e.preventDefault();
                return;
            }

            if (!fileInput.files.length) {
                alert('Bukti transfer wajib diupload.');
                e.preventDefault();
                return;
            }

            const file = fileInput.files[0];
            if (!allowed.includes(file.type)) {
                alert('File harus JPG, PNG, atau PDF.');
                e.preventDefault();
                return;
            }

            if (file.size > 2 * 1024 * 1024) {
                alert('Ukuran file maksimal 2MB.');
                e.preventDefault();
                return;
            }
        });
    }
});