<?php
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php"); 
    exit();
}

include 'includes/db.php';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Otobüs Takip Sistemi</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="navbar">
        <a href="dashboard.php">Ana Sayfa</a>
        <?php if ($_SESSION['user_type'] == 'customer'): ?>
            <a href="customer/view_trips.php">Seferleri Görüntüle</a>
            <a href="customer/my_tickets.php">Biletlerim</a>
        <?php elseif ($_SESSION['user_type'] == 'admin'): ?>
            <a href="admin/admin_dashboard.php">Yönetim Paneli</a>
            <a href="admin/manage_trips.php">Sefer Yönetimi</a>
            <a href="admin/manage_drivers.php">Sürücü Yönetimi</a>
            <?php endif; ?>
        <a href="logout.php" class="logout">Çıkış Yap (<?php echo htmlspecialchars($_SESSION['user_name']); ?>)</a>
    </div>

    <div class="container">
        <h2>Hoş Geldiniz, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h2>
        <p>Bu, Otobüs Takip Sisteminin ana panosudur. Kullanıcı tipinize göre farklı seçenekler göreceksiniz.</p>

        <?php if ($_SESSION['user_type'] == 'customer'): ?>
            <h3>Müşteri İşlemleri:</h3>
            <ul>
                <li><a href="customer/view_trips.php">Seferleri Görüntüle ve Bilet Al</a></li>
                <li><a href="customer/my_tickets.php">Satın Aldığım Biletleri Gör</a></li>
            </ul>
        <?php elseif ($_SESSION['user_type'] == 'admin'): ?>
            <h3>Yönetici İşlemleri:</h3>
            <ul>
                <li><a href="admin/manage_trips.php">Seferleri Yönet</a></li>
                <li><a href="admin/manage_drivers.php">Sürücüleri Yönet</a></li>
                <li><a href="admin/manage_vehicles.php">Araçları Yönet</a></li>
                <li><a href="admin/manage_lines.php">Hatları Yönet</a></li>
                <li><a href="admin/manage_customers.php">Müşterileri Yönet</a></li>
                <li><a href="admin/manage_employees.php">Çalışanları Yönet</a></li>
                <li><a href="#">Raporlar</a> (Geliştirilecek)</li>
            </ul>
        <?php endif; ?>
    </div>
</body>
</html>