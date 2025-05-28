<?php
// Veritabanı bağlantı bilgileri
$servername = "localhost";   // XAMPP'ın varsayılan sunucu adı
$username = "root";          // XAMPP'ın varsayılan MySQL kullanıcı adı
$password = "";              // XAMPP'ın varsayılan MySQL şifresi (boş)
$dbname = "internet_based_db"; // phpMyAdmin'de oluşturduğunuz veritabanı adı

// Veritabanı bağlantısı oluşturma (MySQLi kullanarak)
$conn = new mysqli($servername, $username, $password, $dbname);

// Bağlantıyı kontrol etme
if ($conn->connect_error) {
    die("Veritabanı bağlantısı başarısız: " . $conn->connect_error);
}

// İsteğe bağlı ama önerilir: Karakter setini UTF-8 olarak ayarla
// Bu, Türkçe karakterlerin (ş, ç, ğ, ü, ö, ı) düzgün görüntülenmesini sağlar.
$conn->set_charset("utf8mb4");

// NOT: Bu dosyayı dahil eden PHP sayfaları artık $conn değişkenini kullanarak veritabanı işlemlerini yapabilir.
?>