<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


session_start(); 

include 'includes/db.php'; 

$message = ''; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $name = $conn->real_escape_string($_POST['name']);
    $surname = $conn->real_escape_string($_POST['surname']);
    $email = $conn->real_escape_string($_POST['email']);
    $phone_number = $conn->real_escape_string($_POST['phone_number']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    
    if (empty($name) || empty($surname) || empty($email) || empty($password) || empty($confirm_password)) {
        $message = "<div class='error'>Lütfen tüm alanları doldurunuz.</div>";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "<div class='error'>Geçerli bir e-posta adresi giriniz.</div>";
    } elseif ($password !== $confirm_password) {
        $message = "<div class='error'>Şifreler eşleşmiyor.</div>";
    } elseif (strlen($password) < 6) { 
        $message = "<div class='error'>Şifre en az 6 karakter olmalıdır.</div>";
    } else {
        
        $stmt = $conn->prepare("SELECT customer_id FROM CUSTOMER WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $message = "<div class='error'>Bu e-posta adresi zaten kayıtlı.</div>";
        } else {
            
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            
            $stmt = $conn->prepare("INSERT INTO CUSTOMER (name, surname, phone_number, email, password_hash) VALUES (?, ?, ?, ?, ?)");
            
            $stmt->bind_param("sssss", $name, $surname, $phone_number, $email, $hashed_password);

            if ($stmt->execute()) {
                $message = "<div class='success'>Kayıt başarıyla tamamlandı. Şimdi giriş yapabilirsiniz.</div>";
                
                header("Refresh: 3; url=login.php");
               
                exit();
            } else {
                $message = "<div class='error'>Kayıt sırasında bir hata oluştu: " . $stmt->error . "</div>";
            }
        }
        $stmt->close();
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kayıt Ol - Otobüs Takip Sistemi</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h2>Müşteri Kayıt</h2>
        <?php echo $message; ?>
        <form action="register.php" method="POST" onsubmit="return validateRegisterForm()">
            <div class="form-group">
                <label for="name">Adınız:</label>
                <input type="text" id="name" name="name" required>
            </div>
            <div class="form-group">
                <label for="surname">Soyadınız:</label>
                <input type="text" id="surname" name="surname" required>
            </div>
            <div class="form-group">
                <label for="email">E-posta Adresiniz:</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="phone_number">Telefon Numaranız (Opsiyonel):</label>
                <input type="text" id="phone_number" name="phone_number">
            </div>
            <div class="form-group">
                <label for="password">Şifreniz:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">Şifrenizi Tekrar Girin:</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            <button type="submit">Kayıt Ol</button>
        </form>
        <p>Zaten hesabınız var mı? <a href="login.php">Giriş Yapın</a></p>
    </div>
    <script src="js/validation.js"></script>
</body>
</html>