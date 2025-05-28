<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: ../login.php");
    exit();
}

include '../includes/db.php'; 
$message = '';
$trips = [];
$cities = []; 


$stmt_cities = $conn->prepare("SELECT DISTINCT start_location FROM LINE UNION SELECT DISTINCT end_location FROM LINE ORDER BY 1");

if ($stmt_cities === false) {
    $message .= "<div class='error'>Şehirleri çekerken SQL hatası: " . $conn->error . "</div>";
} else {
    $stmt_cities->execute();
    $result_cities = $stmt_cities->get_result();

    while ($row = $result_cities->fetch_assoc()) {
        $cities[] = $row[key($row)]; 
    }
    
    $stmt_cities->close();
}




if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['search_trip'])) {
    $start_location = $_POST['start_location'];
    $end_location = $_POST['end_location'];
    $departure_date = $_POST['departure_date'];

    
    if (empty($start_location) || empty($end_location) || empty($departure_date)) {
        $message = "<div class='error'>Lütfen tüm arama kriterlerini doldurunuz.</div>";
    } else {
        
        $sql = "SELECT
                    T.trip_id,
                    L.start_location,
                    L.end_location,
                    T.departure_time,
                    D.name as driver_name,
                    D.surname as driver_surname,
                    V.plate_number,
                    V.capacity
                FROM
                    TRIP T
                JOIN
                    LINE L ON T.line_id = L.line_id
                JOIN
                    DRIVER D ON T.driver_id = D.driver_id
                JOIN
                    VEHICLE V ON T.vehicle_id = V.vehicle_id
                WHERE
                    L.start_location = ? AND L.end_location = ?
                    AND DATE(T.departure_time) = ?
                ORDER BY T.departure_time ASC";

        $stmt = $conn->prepare($sql);

        if ($stmt === false) {
            $message = "<div class='error'>Sefer arama sorgusu hazırlanamadı: " . $conn->error . "</div>";
        } else {
            $stmt->bind_param("sss", $start_location, $end_location, $departure_date);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $trips[] = $row;
                }
            } else {
                $message = "<div class='info'>Belirtilen kriterlere uygun sefer bulunamadı.</div>";
            }
            $stmt->close();
        }
    }
}



$display_user_name = '';
if (isset($_SESSION['name']) && isset($_SESSION['surname'])) {
    $display_user_name = htmlspecialchars($_SESSION['name'] . ' ' . $_SESSION['surname']);
} elseif (isset($_SESSION['user_name'])) {
    $display_user_name = htmlspecialchars($_SESSION['user_name']);
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sefer Ara ve Bilet Al - Otobüs Takip Sistemi</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="navbar">
        <a href="../dashboard.php">Ana Sayfa</a>
        <a href="view_trips.php">Seferler</a>
        <a href="my_tickets.php">Biletlerim</a>
        <a href="../logout.php" class="logout">Çıkış Yap <?php echo $display_user_name; ?></a>
    </div>

    <div class="container">
        <h2>Sefer Ara</h2>
        <?php echo $message; ?>

        <form action="view_trips.php" method="POST"> <div class="form-group">
                <label for="start_location">Kalkış Şehri:</label>
                <select id="start_location" name="start_location" required>
                    <option value="">Seçiniz...</option>
                    <?php foreach ($cities as $city): ?>
                        <option value="<?php echo htmlspecialchars($city); ?>"
                            <?php echo (isset($start_location) && $start_location == $city) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($city); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="end_location">Varış Şehri:</label>
                <select id="end_location" name="end_location" required>
                    <option value="">Seçiniz...</option>
                    <?php foreach ($cities as $city): ?>
                        <option value="<?php echo htmlspecialchars($city); ?>"
                            <?php echo (isset($end_location) && $end_location == $city) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($city); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="departure_date">Tarih:</label>
                <input type="date" id="departure_date" name="departure_date" required min="<?php echo date('Y-m-d'); ?>"
                       value="<?php echo isset($departure_date) ? htmlspecialchars($departure_date) : ''; ?>">
            </div>

            <div class="form-group">
                <label>Seyahat Tipi:</label>
                <label for="one_way">
                    <input type="radio" id="one_way" name="trip_type" value="one_way" checked> Tek Yön
                </label>
            </div>
            <div class="form-group" id="return_date_group" style="display: none;">
                <label for="return_date">Dönüş Tarihi:</label>
                <input type="date" id="return_date" name="return_date" min="<?php echo date('Y-m-d'); ?>">
            </div>

            <button type="submit" name="search_trip">Sefer Bul</button>
        </form>

        <?php if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['search_trip']) && !empty($trips)): ?>
            <h3>Bulunan Seferler</h3>
            <p><strong><?php echo htmlspecialchars($start_location); ?></strong> - <strong><?php echo htmlspecialchars($end_location); ?></strong>, Tarih: <strong><?php echo htmlspecialchars(date('d.m.Y', strtotime($departure_date))); ?></strong></p>
            <table>
                <thead>
                    <tr>
                        <th>Sefer ID</th>
                        <th>Kalkış Saati</th>
                        <th>Sürücü</th>
                        <th>Araç Plakası</th>
                        <th>Kapasite</th>
                        <th>İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($trips as $trip): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($trip['trip_id']); ?></td>
                            <td><?php echo htmlspecialchars(date('H:i', strtotime($trip['departure_time']))); ?></td>
                            <td><?php echo htmlspecialchars($trip['driver_name'] . ' ' . $trip['driver_surname']); ?></td>
                            <td><?php echo htmlspecialchars($trip['plate_number']); ?></td>
                            <td><?php echo htmlspecialchars($trip['capacity']); ?></td>
                            <td>
                                <a href="buy_ticket.php?trip_id=<?php echo $trip['trip_id']; ?>" class="button" style="background-color: #28a745;">Bilet Al</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php elseif ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['search_trip']) && empty($trips) && empty($message)): ?>
            <p>Belirtilen kriterlere uygun sefer bulunamadı.</p>
        <?php endif; ?>

    </div>

    <script>
        
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('departure_date').min = today;
        });
    </script>
</body>
</html>