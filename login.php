<?php
session_start();
ini_set('display_errors', 1); 
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); 

include 'includes/db.php'; 

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    

    $email = trim($conn->real_escape_string($_POST['email']));
    $password = $_POST['password']; 

    if (empty($email) || empty($password)) {
        $message = "<div class='error'>E-posta ve şifre boş bırakılamaz.</div>";
    } else {
        

        $stmt = $conn->prepare("SELECT customer_id, name, surname, password_hash FROM CUSTOMER WHERE email = ?");
        
        if ($stmt === false) {
            $message = "<div class='error'>Sorgu hazırlanamadı: " . $conn->error . "</div>";
        } else {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result(); 
            $stmt->bind_result($customer_id, $customer_name, $customer_surname, $hashed_password); 
            $stmt->fetch(); 

            if ($stmt->num_rows > 0 && password_verify($password, $hashed_password)) {
                
                $_SESSION['loggedin'] = true;
                $_SESSION['customer_id'] = $customer_id;
                $_SESSION['name'] = $customer_name;
                $_SESSION['surname'] = $customer_surname;
                $_SESSION['user_type'] = 'customer';

               
                header("Location: customer/view_trips.php");
                exit();
            } else {
                
                $stmt_admin = $conn->prepare("SELECT admin_id, username, password_hash FROM ADMINS WHERE username = ?");
                if ($stmt_admin === false) {
                    
                    error_log("Admin sorgusu hazırlanamadı: " . $conn->error);
                    $message = "<div class='error'>Geçersiz e-posta veya şifre.</div>";
                } else {
                    $stmt_admin->bind_param("s", $email); 
                    $stmt_admin->execute();
                    $stmt_admin->store_result();
                    $stmt_admin->bind_result($admin_id, $admin_username, $admin_hashed_password);
                    $stmt_admin->fetch();

                    if ($stmt_admin->num_rows > 0 && password_verify($password, $admin_hashed_password)) {
                        
                        $_SESSION['loggedin'] = true;
                        $_SESSION['admin_id'] = $admin_id;
                        $_SESSION['user_name'] = $admin_username; 
                        $_SESSION['user_type'] = 'admin'; 
                        
                        header("Location: admin/dashboard.php"); 
                        exit();
                    } else {
                        
                        $message = "<div class='error'>Geçersiz e-posta/kullanıcı adı veya şifre.</div>";
                    }
                    $stmt_admin->close();
                }
            }
            $stmt->close(); 
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