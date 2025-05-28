<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php"); // Yönetici değilse giriş sayfasına yönlendir
    exit();
}
include '../includes/db.php'; // Veritabanı bağlantısı
$message = ''; // İşlem mesajları için
?>

<h3>Yeni Kayıt Ekle</h3>
<form action="" method="POST">
    <div class="form-group">
        <label for="field1">Alan 1:</label>
        <input type="text" id="field1" name="field1" required>
    </div>
    <button type="submit" name="add_record">Kaydet</button>
</form>
// Örnek PHP (manage_drivers.php için)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_driver'])) {
    $name = $conn->real_escape_string($_POST['name']);
    $surname = $conn->real_escape_string($_POST['surname']);
    $phone_number = $conn->real_escape_string($_POST['phone_number']);

    if (empty($name) || empty($surname)) {
        $message = "<div class='error'>Ad ve Soyad boş bırakılamaz.</div>";
    } else {
        $stmt = $conn->prepare("INSERT INTO DRIVER (name, surname, phone_number) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $surname, $phone_number);
        if ($stmt->execute()) {
            $message = "<div class='success'>Sürücü başarıyla eklendi.</div>";
        } else {
            $message = "<div class='error'>Sürücü eklenirken hata oluştu: " . $stmt->error . "</div>";
        }
        $stmt->close();
    }
}

<h3>Mevcut Kayıtlar</h3>
<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Alan 1</th>
            <th>İşlemler</th>
        </tr>
    </thead>
    <tbody>
        <?php
        // Veritabanından verileri çek ve listele
        $result = $conn->query("SELECT * FROM YOUR_TABLE_NAME");
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['id_column']) . "</td>";
                echo "<td>" . htmlspecialchars($row['field1']) . "</td>";
                echo "<td>";
                echo "<a href='edit_record.php?id=" . $row['id_column'] . "' class='button' style='background-color: #ffc107;'>Düzenle</a> ";
                echo "<a href='?delete_id=" . $row['id_column'] . "' class='button' style='background-color: #dc3545;' onclick='return confirm(\"Bu kaydı silmek istediğinizden emin misiniz?\");'>Sil</a>";
                echo "</td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='3'>Henüz kayıt bulunmamaktadır.</td></tr>";
        }
        ?>
    </tbody>
</table>

// Örnek PHP (manage_drivers.php için)
if (isset($_GET['delete_id'])) {
    $driver_id = $_GET['delete_id'];
    $stmt = $conn->prepare("DELETE FROM DRIVER WHERE driver_id = ?");
    $stmt->bind_param("i", $driver_id);
    if ($stmt->execute()) {
        $message = "<div class='success'>Sürücü başarıyla silindi.</div>";
    } else {
        $message = "<div class='error'>Sürücü silinirken hata oluştu: " . $stmt->error . "</div>";
    }
    $stmt->close();
}

<?php
// Güvenlik ve DB bağlantısı
if (isset($_GET['id'])) {
    $driver_id = $_GET['id'];
    $stmt = $conn->prepare("SELECT name, surname, phone_number FROM DRIVER WHERE driver_id = ?");
    $stmt->bind_param("i", $driver_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $driver = $result->fetch_assoc();
    } else {
        // Hata: Sürücü bulunamadı
        header("Location: manage_drivers.php");
        exit();
    }
    $stmt->close();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_driver'])) {
    $driver_id = $_POST['driver_id']; // Gizli inputtan gelecek
    $name = $conn->real_escape_string($_POST['name']);
    $surname = $conn->real_escape_string($_POST['surname']);
    $phone_number = $conn->real_escape_string($_POST['phone_number']);

    $stmt = $conn->prepare("UPDATE DRIVER SET name=?, surname=?, phone_number=? WHERE driver_id=?");
    $stmt->bind_param("sssi", $name, $surname, $phone_number, $driver_id);
    if ($stmt->execute()) {
        $message = "<div class='success'>Sürücü başarıyla güncellendi.</div>";
        // İsteğe bağlı: manage_drivers.php'ye geri yönlendir
        // header("Location: manage_drivers.php"); exit();
    } else {
        $message = "<div class='error'>Güncelleme sırasında hata oluştu: " . $stmt->error . "</div>";
    }
    $stmt->close();
}
?>
<form action="edit_driver.php" method="POST">
    <input type="hidden" name="driver_id" value="<?php echo htmlspecialchars($driver_id); ?>">
    <div class="form-group">
        <label for="name">Ad:</label>
        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($driver['name']); ?>" required>
    </div>
    <button type="submit" name="update_driver">Güncelle</button>
</form>

<?php
// SQL Injection saldırılarına karşı güvenli hale getirmek için
function sanitize_input($conn, $data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data); // HTML etiketlerini kaçış karakterlerine çevirir
    $data = $conn->real_escape_string($data); // SQL injection'a karşı ek koruma
    return $data;
}

// Mesajları yazdırmak için
function display_message($message) {
    if (!empty($message)) {
        echo $message;
    }
}

// Sayfa yönlendirme fonksiyonu (daha temiz kod için)
function redirect_to($location) {
    header("Location: " . $location);
    exit();
}
?>