function validateRegisterForm() {
    const name = document.getElementById('name').value.trim();
    const surname = document.getElementById('surname').value.trim();
    const email = document.getElementById('e_mail').value.trim();
    const password = document.getElementById('password').value;
    const confirm_password = document.getElementById('confirm_password').value;

    if (name === '' || surname === '' || email === '' || password === '' || confirm_password === '') {
        alert('Lütfen tüm zorunlu alanları doldurun.');
        return false;
    }

    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { // Basit e-posta regex kontrolü
        alert('Lütfen geçerli bir e-posta adresi girin.');
        return false;
    }

    if (password.length < 6) {
        alert('Şifre en az 6 karakter olmalıdır.');
        return false;
    }

    if (password !== confirm_password) {
        alert('Şifreler eşleşmiyor.');
        return false;
    }

    return true; // Tüm validasyonlar başarılıysa formu gönder
}

function validateLoginForm() {
    const email = document.getElementById('e_mail').value.trim();
    const password = document.getElementById('password').value;

    if (email === '' || password === '') {
        alert('Lütfen e-posta ve şifrenizi girin.');
        return false;
    }

    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        alert('Lütfen geçerli bir e-posta adresi girin.');
        return false;
    }

    return true;
}