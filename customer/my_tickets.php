<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('Europe/Istanbul'); 


echo "<div style='background-color: #fff3cd; padding: 10px; border: 1px solid #ffeeba; margin-bottom: 10px;'>";
echo "<h3>my_tickets.php DEBUG Bilgileri:</h3>";
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    echo "Kullanıcı Giriş Yapmış.<br>";
    if (isset($_SESSION['customer_id'])) {
        $debug_customer_id = $_SESSION['customer_id'];
        echo "Oturumdaki Customer ID: <strong>" . htmlspecialchars($debug_customer_id) . "</strong><br>";
    } else {
        echo "HATA: Oturumda 'customer_id' bulunamadı!<br>";
        $debug_customer_id = 'BULUNAMADI'; 
    }
} else {
    echo "HATA: Kullanıcı oturum açmamış veya oturum süresi dolmuş. Lütfen tekrar giriş yapın.<br>";
    $debug_customer_id = 'GİRİŞ_YAPMAMIŞ'; 
}
echo "</div>";


if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: ../login.php");
    exit();
}

include '../includes/db.php';

$message = '';
$tickets = [];

$customer_id = $_SESSION['customer_id']; 


$sql = "SELECT
            TI.ticket_id,
            P.payment_date, 
            P.amount as ticket_price, 
            T.departure_time,
            L.start_location,
            L.end_location,
            V.plate_number,
            D.name as driver_name,
            D.surname as driver_surname
        FROM
            TICKET TI
        JOIN
            PAYMENT P ON TI.payment_id = P.payment_id
        JOIN
            CUSTOMER C ON P.customer_id = C.customer_id 
        JOIN
            TRIP_TICKET_RELATION TTR ON TI.ticket_id = TTR.ticket_id 
        JOIN
            TRIP T ON TTR.trip_id = T.trip_id
        JOIN
            LINE L ON T.line_id = L.line_id
        JOIN
            VEHICLE V ON T.vehicle_id = V.vehicle_id
        JOIN
            DRIVER D ON T.driver_id = D.driver_id
        WHERE
            C.customer_id = ? 
        ORDER BY
            T.departure_time DESC"; 


$debug_sql_with_value = str_replace("?", $conn->real_escape_string($customer_id), $sql);
echo "<div style='background-color: #f8d7da; padding: 10px; border: 1px solid #f5c6cb; margin-bottom: 10px;'>";
echo "<h3>my_tickets.php SQL Debug:</h3>";
echo "Veritabanına gönderilecek SQL Sorgusu: <pre>" . htmlspecialchars($debug_sql_with_value) . "</pre>";
echo "</div>";


$stmt = $conn->prepare($sql);
if ($stmt === false) {
    $message = "<div class='error'>Biletleri çekerken sorgu hazırlanamadı: " . $conn->error . "</div>";
} else {
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();

    
    echo "<div style='background-color: #d4edda; padding: 10px; border: 1px solid #c3e6cb; margin-bottom: 10px;'>";
    echo "<h3>my_tickets.php Sonuç Debug:</h3>";
    echo "Sorgudan dönen sonuç sayısı: <strong>" . $result->num_rows . "</strong><br>";
    echo "</div>";
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $tickets[] = $row; 
        }
    } else {
        $message = "<div class='info'>Henüz satın alınmış bir biletiniz bulunmamaktadır.</div>";
    }
    $stmt->close();
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
    <title>Biletlerim - Otobüs Takip Sistemi</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="navbar">
        <a href="../dashboard.php">Ana Sayfa</a>
        <a href="view_trips.php">Sefer Ara</a>
        <a href="my_tickets.php">Biletlerim</a>
        <a href="../logout.php" class="logout">Çıkış Yap (<?php echo $display_user_name; ?>)</a>
    </div>

    <div class="container">
        <h2>Biletlerim</h2>
        <?php echo $message; ?>

        <?php if (!empty($tickets)): ?>
            <table>
                <thead>
                    <tr>
                        <th>Bilet ID</th>
                        <th>Kalkış</th>
                        <th>Varış</th>
                        <th>Kalkış Zamanı</th>
                        <th>Fiyat</th>
                        <th>Plaka</th>
                        <th>Sürücü</th>
                        <th>Satın Alma Tarihi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tickets as $ticket): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($ticket['ticket_id']); ?></td>
                            <td><?php echo htmlspecialchars($ticket['start_location']); ?></td>
                            <td><?php htmlspecialchars($ticket['end_location']); ?></td>
                            <td><?php echo htmlspecialchars(date('d.m.Y H:i', strtotime($ticket['departure_time']))); ?></td>
                            <td><?php echo htmlspecialchars(number_format($ticket['ticket_price'], 2)) . ' TL'; ?></td>
                            <td><?php htmlspecialchars($ticket['plate_number']); ?></td>
                            <td><?php echo htmlspecialchars($ticket['driver_name'] . ' ' . $ticket['driver_surname']); ?></td>
                            <td><?php echo htmlspecialchars(date('d.m.Y H:i', strtotime($ticket['payment_date']))); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>