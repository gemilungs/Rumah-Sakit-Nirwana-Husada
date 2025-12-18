document.addEventListener('DOMContentLoaded', function() {
    // Toggle Current Password
    const toggleCurrent = document.getElementById('toggleCurrent');
    const currentPwd = document.getElementById('currentPwd');
    
    if (toggleCurrent) {
        toggleCurrent.addEventListener('click', function(e) {
            e.preventDefault();
            if (currentPwd.type === 'password') {
                currentPwd.type = 'text';
                toggleCurrent.textContent = 'Sembunyikan';
            } else {
                currentPwd.type = 'password';
                toggleCurrent.textContent = 'Tampilkan';
            }
        });
    }

    // Toggle New Password
    const toggleNew = document.getElementById('toggleNew');
    const newPwd = document.getElementById('newPwd');
    
    if (toggleNew) {
        toggleNew.addEventListener('click', function(e) {
            e.preventDefault();
            if (newPwd.type === 'password') {
                newPwd.type = 'text';
                toggleNew.textContent = 'Sembunyikan';
            } else {
                newPwd.type = 'password';
                toggleNew.textContent = 'Tampilkan';
            }
        });
    }

    // Toggle Confirm Password
    const toggleConfirm = document.getElementById('toggleConfirm');
    const confirmPwd = document.getElementById('confirmPwd');
    
    if (toggleConfirm) {
        toggleConfirm.addEventListener('click', function(e) {
            e.preventDefault();
            if (confirmPwd.type === 'password') {
                confirmPwd.type = 'text';
                toggleConfirm.textContent = 'Sembunyikan';
            } else {
                confirmPwd.type = 'password';
                toggleConfirm.textContent = 'Tampilkan';
            }
        });
    }

    // Form Submit
    const changeForm = document.getElementById('changeForm');
    if (changeForm) {
        changeForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            const currentPassword = currentPwd.value;
            const newPassword = newPwd.value;
            const confirmPassword = confirmPwd.value;

            // Validasi
            if (!currentPassword) {
                alert('Kata sandi saat ini wajib diisi');
                return;
            }

            if (!newPassword || newPassword.length < 8) {
                alert('Kata sandi baru minimal 8 karakter');
                return;
            }

            if (newPassword !== confirmPassword) {
                alert('Kata sandi baru dan konfirmasi tidak cocok');
                return;
            }

            if (currentPassword === newPassword) {
                alert('Kata sandi baru harus berbeda dengan kata sandi lama');
                return;
            }

            // Kirim ke API
            try {
                const response = await fetch('api/change-password.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        password_lama: currentPassword,
                        password_baru: newPassword
                    })
                });

                const data = await response.json();

                if (data.success) {
                    alert('Password berhasil diubah!');
                    changeForm.reset();
                    // Optional: redirect ke profile
                    // window.location.href = 'profile.php';
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Terjadi kesalahan. Cek console untuk detail.');
            }
        });
    }

    // Back to Profile Button
    const backToProfile = document.getElementById('backToProfile');
    if (backToProfile) {
        backToProfile.addEventListener('click', function() {
            window.location.href = 'profile.php';
        });
    }

    // Cancel Button
    const cancelBtn = document.getElementById('cancelBtn');
    if (cancelBtn) {
        cancelBtn.addEventListener('click', function() {
            if (confirm('Batalkan perubahan password?')) {
                changeForm.reset();
            }
        });
    }
});
