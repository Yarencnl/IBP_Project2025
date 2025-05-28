<?php
session_start();
ini_set('display_errors', 1); // Hataları göster
ini_set('display_startup_errors', 1); // Başlangıç hatalarını göster
error_reporting(E_ALL); // Tüm hataları raporla

// Veritabanı bağlantı dosyanızın yolu, login.php ile aynı dizinde olduğu varsayılıyor.
include 'includes/db.php'; 

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // POST ile gelen verileri güvenli hale getir
    // trim() ile baştaki ve sondaki boşlukları kaldırıyoruz
    $email = trim($conn->real_escape_string($_POST['email']));
    $password = $_POST['password']; // Şifre doğrulaması password_verify ile yapıldığından direkt alıyoruz

    if (empty($email) || empty($password)) {
        $message = "<div class='error'>E-posta ve şifre boş bırakılamaz.</div>";
    } else {
        // CUSTOMER tablosundan kullanıcıyı ve gerekli tüm bilgileri bul
        // password_hash sütun adınız doğruysa sorun yok.
        $stmt = $conn->prepare("SELECT customer_id, name, surname, password_hash FROM CUSTOMER WHERE email = ?");
        
        if ($stmt === false) {
            $message = "<div class='error'>Sorgu hazırlanamadı: " . $conn->error . "</div>";
        } else {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result(); // Sonuçları belleğe depolar
            $stmt->bind_result($customer_id, $customer_name, $customer_surname, $hashed_password); // Sütunları değişkenlere bağla
            $stmt->fetch(); // Tek bir satır getir

            if ($stmt->num_rows > 0 && password_verify($password, $hashed_password)) {
                // Giriş başarılı! Oturum değişkenlerini ayarla
                $_SESSION['loggedin'] = true;
                $_SESSION['customer_id'] = $customer_id;
                $_SESSION['name'] = $customer_name;
                $_SESSION['surname'] = $customer_surname;
                $_SESSION['user_type'] = 'customer'; // Kullanıcı tipi belirle

                // Müşteri paneline yönlendirme
                header("Location: customer/view_trips.php");
                exit();
            } else {
                // ADMIN tablosunu kontrol et (eğer ayrı bir admin girişiniz varsa)
                // Admin tablonuzun adı 'ADMINS' ve admin_id, username, password_hash sütunları olduğunu varsayıyorum.
                $stmt_admin = $conn->prepare("SELECT admin_id, username, password_hash FROM ADMINS WHERE username = ?");
                if ($stmt_admin === false) {
                    // Admin sorgusu hatası, ancak kullanıcıya bunu göstermek istemeyebiliriz.
                    error_log("Admin sorgusu hazırlanamadı: " . $conn->error);
                    $message = "<div class='error'>Geçersiz e-posta veya şifre.</div>";
                } else {
                    $stmt_admin->bind_param("s", $email); // Adminler için e-posta yerine kullanıcı adı kullanıyoruz
                    $stmt_admin->execute();
                    $stmt_admin->store_result();
                    $stmt_admin->bind_result($admin_id, $admin_username, $admin_hashed_password);
                    $stmt_admin->fetch();

                    if ($stmt_admin->num_rows > 0 && password_verify($password, $admin_hashed_password)) {
                        // Admin girişi başarılı! Oturum değişkenlerini ayarla
                        $_SESSION['loggedin'] = true;
                        $_SESSION['admin_id'] = $admin_id;
                        $_SESSION['user_name'] = $admin_username; // Admin için kullanıcı adı
                        $_SESSION['user_type'] = 'admin'; // Kullanıcı tipi admin

                        // Admin paneline yönlendirme
                        header("Location: admin/dashboard.php"); // Admin paneli yolunuzu kontrol edin
                        exit();
                    } else {
                        // Hem müşteri hem de admin tablolarında bulunamadı veya şifre yanlış
                        $message = "<div class='error'>Geçersiz e-posta/kullanıcı adı veya şifre.</div>";
                    }
                    $stmt_admin->close();
                }
            }
            $stmt->close(); // Müşteri sorgusu kapatılıyor
        }
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş Yap - Otobüs Takip Sistemi</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="login-body">
    <div class="container login-container">
        <h2>Giriş Yap</h2> <?php echo $message; ?>
        <form action="login.php" method="POST">
            <div class="form-group">
                <label for="email">E-posta Adresiniz / Kullanıcı Adınız:</label>
                <input type="text" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Şifreniz:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit">Giriş Yap</button>
        </form>
        <p>Hesabınız yok mu? <a href="register.php">Kayıt Olun</a></p>
    </div>
</body>
</html>