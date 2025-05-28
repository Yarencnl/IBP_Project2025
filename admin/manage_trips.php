<?php
session_start();
// Hata raporlamayı açın (sadece geliştirme aşamasında)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Kullanıcı giriş yapmış mı ve yönetici mi kontrol et
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php"); // Yönetici değilse login sayfasına yönlendir
    exit();
}

include '../includes/db.php'; // Bir üst klasördeki db.php'yi dahil et

$message = '';

// Yeni sefer ekleme işlemi
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_trip'])) {
    $line_id = $_POST['line_id'];
    $driver_id = $_POST['driver_id'];
    $vehicle_id = $_POST['vehicle_id'];
    $departure_time = $_POST['departure_time']; // YYYY-MM-DDTHH:MM formatında gelecek

    // Validasyonlar
    if (empty($line_id) || empty($driver_id) || empty($vehicle_id) || empty($departure_time)) {
        $message = "<div class='error'>Tüm alanları doldurunuz.</div>";
    } else {
        $stmt = $conn->prepare("INSERT INTO TRIP (line_id, driver_id, vehicle_id, departure_time) VALUES (?, ?, ?, ?)");
        if ($stmt === false) {
             $message = "<div class='error'>Sefer ekleme sorgusu hazırlanamadı: " . $conn->error . "</div>";
        } else {
            $stmt->bind_param("iiis", $line_id, $driver_id, $vehicle_id, $departure_time);
            if ($stmt->execute()) {
                $message = "<div class='success'>Sefer başarıyla eklendi.</div>";
            } else {
                $message = "<div class='error'>Sefer eklenirken hata oluştu: " . $stmt->error . "</div>";
            }
            $stmt->close();
        }
    }
}

// Sefer silme işlemi
if (isset($_GET['delete_trip_id'])) {
    $trip_id = $_GET['delete_trip_id'];
    $stmt = $conn->prepare("DELETE FROM TRIP WHERE trip_id = ?");
    if ($stmt === false) {
        $message = "<div class='error'>Sefer silme sorgusu hazırlanamadı: " . $conn->error . "</div>";
    } else {
        $stmt->bind_param("i", $trip_id);
        if ($stmt->execute()) {
            $message = "<div class='success'>Sefer başarıyla silindi.</div>";
        } else {
            $message = "<div class='error'>Sefer silinirken hata oluştu: " . $stmt->error . "</div>";
        }
        $stmt->close();
    }
}

// Mevcut hatları çek (dropdownlar için) - ARTIK start_location ve end_location kullanıyoruz
$lines = $conn->query("SELECT line_id, start_location, end_location FROM LINE");
if ($lines === false) {
    $message .= "<div class='error'>Hatlar çekilirken hata oluştu: " . $conn->error . "</div>";
}

// Mevcut sürücüleri çek (dropdownlar için)
$drivers = $conn->query("SELECT driver_id, name, surname FROM DRIVER");
if ($drivers === false) {
    $message .= "<div class='error'>Sürücüler çekilirken hata oluştu: " . $conn->error . "</div>";
}

// Mevcut araçları çek (dropdownlar için)
// Araç modelini de çekelim, daha bilgilendirici olur
$vehicles = $conn->query("SELECT vehicle_id, plate_number, model FROM VEHICLE");
if ($vehicles === false) {
    $message .= "<div class='error'>Araçlar çekilirken hata oluştu: " . $conn->error . "</div>";
}

// Tüm seferleri çek (listeleme için) - L.route yerine L.start_location ve L.end_location kullanıyoruz
$trips_query = "SELECT
                    T.trip_id,
                    L.start_location, -- Düzeltildi
                    L.end_location,   -- Düzeltildi
                    D.name as driver_name,
                    D.surname as driver_surname,
                    V.plate_number,
                    T.departure_time
                FROM TRIP T
                JOIN LINE L ON T.line_id = L.line_id
                JOIN DRIVER D ON T.driver_id = D.driver_id
                JOIN VEHICLE V ON T.vehicle_id = V.vehicle_id
                ORDER BY T.departure_time DESC";
$trips_result = $conn->query($trips_query);
if ($trips_result === false) {
    $message .= "<div class='error'>Seferler çekilirken hata oluştu: " . $conn->error . "</div>";
}

// _SESSION['user_name'] hatasını düzeltelim. login.php'de name ve surname kullanıyorduk.
$display_user_name = '';
if (isset($_SESSION['name']) && isset($_SESSION['surname'])) {
    $display_user_name = htmlspecialchars($_SESSION['name'] . ' ' . $_SESSION['surname']);
} elseif (isset($_SESSION['user_name'])) {
    $display_user_name = htmlspecialchars($_SESSION['user_name']); // Yedek olarak kalsın, emin değiliz
}
// Veya sadece type gösterelim:
// $display_user_name = htmlspecialchars($_SESSION['user_type']);

$conn->close();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sefer Yönetimi - Otobüs Takip Sistemi</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="navbar">
        <a href="../dashboard.php">Ana Sayfa</a>
        <a href="admin_dashboard.php">Yönetim Paneli</a>
        <a href="manage_trips.php">Sefer Yönetimi</a>
        <a href="manage_drivers.php">Sürücü Yönetimi</a>
        <a href="manage_vehicles.php">Araç Yönetimi</a>
        <a href="manage_lines.php">Hat Yönetimi</a>
        <a href="../logout.php" class="logout">Çıkış Yap (<?php echo $display_user_name; ?>)</a>
    </div>

    <div class="container">
        <h2>Sefer Yönetimi</h2>
        <?php echo $message; ?>

        <h3>Yeni Sefer Ekle</h3>
        <form action="manage_trips.php" method="POST">
            <div class="form-group">
                <label for="line_id">Hat:</label>
                <select id="line_id" name="line_id" required>
                    <option value="">Hat Seçin</option>
                    <?php while ($line = $lines->fetch_assoc()): ?>
                        <option value="<?php echo $line['line_id']; ?>">
                            <?php echo htmlspecialchars($line['start_location'] . ' - ' . $line['end_location']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="driver_id">Sürücü:</label>
                <select id="driver_id" name="driver_id" required>
                    <option value="">Sürücü Seçin</option>
                    <?php while ($driver = $drivers->fetch_assoc()): ?>
                        <option value="<?php echo $driver['driver_id']; ?>"><?php echo htmlspecialchars($driver['name'] . ' ' . $driver['surname']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="vehicle_id">Araç:</label>
                <select id="vehicle_id" name="vehicle_id" required>
                    <option value="">Araç Seçin</option>
                    <?php while ($vehicle = $vehicles->fetch_assoc()): ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="departure_time">Kalkış Zamanı:</label>
                <input type="datetime-local" id="departure_time" name="departure_time" required>
            </div>
            <button type="submit" name="add_trip">Sefer Ekle</button>
        </form>

        <h3>Mevcut Seferler</h3>
        <?php if ($trips_result && $trips_result->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Sefer ID</th>
                        <th>Hat</th> <th>Sürücü</th>
                        <th>Araç Plakası</th>
                        <th>Kalkış Zamanı</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($trip = $trips_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($trip['trip_id']); ?></td>
                            <td><?php echo htmlspecialchars($trip['start_location'] . ' - ' . $trip['end_location']); ?></td>
                            <td><?php echo htmlspecialchars($trip['driver_name'] . ' ' . $trip['driver_surname']); ?></td>
                            <td><?php echo htmlspecialchars($trip['plate_number']); ?></td>
                            <td><?php echo htmlspecialchars($trip['departure_time']); ?></td>
                            <td>
                                <a href="edit_trip.php?id=<?php echo $trip['trip_id']; ?>" class="button" style="background-color: #ffc107;">Düzenle</a>
                                <a href="manage_trips.php?delete_trip_id=<?php echo $trip['trip_id']; ?>" class="button" style="background-color: #dc3545;" onclick="return confirm('Bu seferi silmek istediğinizden emin misiniz?');">Sil</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Henüz kayıtlı sefer bulunmamaktadır.</p>
        <?php endif; ?>
    </div>
</body>
</html>