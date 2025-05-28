<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('Europe/Istanbul'); // Zaman dilimi ayarı

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: ../login.php");
    exit();
}

include '../includes/db.php'; // Veritabanı bağlantısı

$message = '';
$trip_details = null;
$customer_details = null;

// Oturumdan müşteri ID'sini al
$customer_id = $_SESSION['customer_id'];

// --- DEBUG: Müşteri ID'si kontrolü ---
echo "<div style='background-color: #f0f8ff; padding: 10px; border: 1px solid #d0e8f8; margin-bottom: 10px;'>";
echo "<h3>buy_ticket.php Giriş DEBUG Bilgileri:</h3>";
echo "Oturumdaki Customer ID: <strong>" . htmlspecialchars($customer_id) . "</strong><br>";
echo "</div>";
// --- DEBUG SONU ---

// Müşteri bilgilerini çek
$stmt_customer = $conn->prepare("SELECT name, surname, email, phone_number FROM CUSTOMER WHERE customer_id = ?");
if ($stmt_customer === false) {
    $message .= "<div class='error'>Müşteri bilgileri sorgusu hazırlanamadı: " . $conn->error . "</div>";
} else {
    $stmt_customer->bind_param("i", $customer_id);
    $stmt_customer->execute();
    $result_customer = $stmt_customer->get_result();
    if ($result_customer->num_rows > 0) {
        $customer_details = $result_customer->fetch_assoc();
    } else {
        $message .= "<div class='error'>Müşteri bilgileri bulunamadı. Lütfen tekrar giriş yapınız.</div>";
    }
    $stmt_customer->close();
}

// trip_id'yi URL'den al
if (isset($_GET['trip_id'])) {
    $trip_id = $_GET['trip_id'];

    // Seçilen seferin detaylarını çek
    $sql = "SELECT
                T.trip_id,
                L.start_location,
                L.end_location,
                T.departure_time,
                D.name as driver_name,
                D.surname as driver_surname,
                V.plate_number,
                V.capacity,
                -- Fiyat TRIP tablosunda yoksa varsayılan bir değer kullanıyoruz
                250.00 as price_per_ticket -- Örnek sabit fiyat, siz bunu TRIP tablosuna ekleyebilirsiniz
            FROM
                TRIP T
            JOIN
                LINE L ON T.line_id = L.line_id
            JOIN
                DRIVER D ON T.driver_id = D.driver_id
            JOIN
                VEHICLE V ON T.vehicle_id = V.vehicle_id
            WHERE
                T.trip_id = ?";

    $stmt_trip = $conn->prepare($sql);
    if ($stmt_trip === false) {
        $message .= "<div class='error'>Sefer detayları sorgusu hazırlanamadı: " . $conn->error . "</div>";
    } else {
        $stmt_trip->bind_param("i", $trip_id);
        $stmt_trip->execute();
        $result_trip = $stmt_trip->get_result();

        if ($result_trip->num_rows > 0) {
            $trip_details = $result_trip->fetch_assoc();
        } else {
            $message = "<div class='error'>Sefer bulunamadı.</div>";
        }
        $stmt_trip->close();
    }
} else {
    $message = "<div class='error'>Sefer ID belirtilmedi.</div>";
    header("Location: view_trips.php"); // Eğer trip_id yoksa, arama sayfasına geri yönlendir
    exit();
}

// Bilet satın alma işlemi (Ödeme ve ardından Bilet kaydı)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['confirm_purchase'])) {
    $trip_id = $_POST['trip_id']; // Hiddenden gelen trip_id
    $customer_id = $_SESSION['customer_id'];
    $amount_to_pay = $trip_details['price_per_ticket']; // Yukarıda çekilen fiyattan alıyoruz
    $payment_method = 'Kredi Kartı'; // Örnek ödeme yöntemi

    // 1. PAYMENT tablosuna kayıt ekle
    $conn->begin_transaction(); // İşlemi başlat

    try {
        $stmt_payment = $conn->prepare("INSERT INTO PAYMENT (customer_id, amount, payment_method) VALUES (?, ?, ?)");
        if ($stmt_payment === false) {
            throw new Exception("Ödeme sorgusu hazırlanamadı: " . $conn->error);
        }
        $stmt_payment->bind_param("ids", $customer_id, $amount_to_pay, $payment_method); // i:int, d:double, s:string
        
        if (!$stmt_payment->execute()) {
            throw new Exception("Ödeme kaydedilirken hata oluştu: " . $stmt_payment->error);
        }
        $payment_id = $conn->insert_id; // Yeni eklenen ödemenin ID'sini al
        // --- DEBUG: PAYMENT ID ---
        echo "<div style='background-color: #d1ecf1; padding: 10px; border: 1px solid #bee5eb; margin-bottom: 5px;'>DEBUG: PAYMENT ID: <strong>" . $payment_id . "</strong></div>";
        // --- DEBUG SONU ---
        $stmt_payment->close();

        // 2. TICKET tablosuna kayıt ekle (payment_id ile)
        $stmt_ticket = $conn->prepare("INSERT INTO TICKET (payment_id) VALUES (?)");
        if ($stmt_ticket === false) {
            throw new Exception("Bilet sorgusu hazırlanamadı: " . $conn->error);
        }
        $stmt_ticket->bind_param("i", $payment_id);
        
        if (!$stmt_ticket->execute()) {
            throw new Exception("Bilet kaydedilirken hata oluştu: " . $stmt_ticket->error);
        }
        $ticket_id = $conn->insert_id; // Yeni eklenen biletin ID'sini al
        // --- DEBUG: TICKET ID ---
        echo "<div style='background-color: #d1ecf1; padding: 10px; border: 1px solid #bee5eb; margin-bottom: 5px;'>DEBUG: TICKET ID: <strong>" . $ticket_id . "</strong></div>";
        // --- DEBUG SONU ---
        $stmt_ticket->close();

        // 3. TRIP_TICKET_RELATION tablosuna kayıt ekle
        $stmt_trip_ticket = $conn->prepare("INSERT INTO TRIP_TICKET_RELATION (trip_id, ticket_id) VALUES (?, ?)");
        if ($stmt_trip_ticket === false) {
            throw new Exception("Sefer-Bilet ilişki sorgusu hazırlanamadı: " . $conn->error);
        }
        $stmt_trip_ticket->bind_param("ii", $trip_id, $ticket_id);

        if (!$stmt_trip_ticket->execute()) {
            throw new Exception("Sefer-Bilet ilişkisi kaydedilirken hata oluştu: " . $stmt_trip_ticket->error);
        }
        // --- DEBUG: TRIP_TICKET_RELATION ---
        echo "<div style='background-color: #d1ecf1; padding: 10px; border: 1px solid #bee5eb; margin-bottom: 5px;'>DEBUG: TRIP_TICKET_RELATION Eklendi (Trip ID: <strong>" . $trip_id . "</strong>, Ticket ID: <strong>" . $ticket_id . "</strong>)</div>";
        // --- DEBUG SONU ---
        $stmt_trip_ticket->close();


        $conn->commit(); // Tüm işlemler başarılıysa onayla
        // --- DEBUG: COMMIT BAŞARILI ---
        echo "<div style='background-color: #d4edda; padding: 10px; border: 1px solid #c3e6cb; margin-bottom: 10px;'>DEBUG: Tüm İşlemler Başarılı Şekilde Commit Edildi!</div>";
        // --- DEBUG SONU ---

        $message = "<div class='success'>Biletiniz başarıyla satın alındı! Bilet ID: " . $ticket_id . "</div>";
        header("Location: my_tickets.php?status=success&ticket_id=" . $ticket_id);
        exit();

    } catch (Exception $e) {
        $conn->rollback(); // Bir hata olursa tüm işlemleri geri al
        // --- DEBUG: HATA OLUŞTU ---
        echo "<div style='background-color: #f8d7da; padding: 10px; border: 1px solid #f5c6cb; margin-bottom: 10px;'>DEBUG HATA: Bilet satın alınırken hata oluştu: <strong>" . $e->getMessage() . "</strong></div>";
        // --- DEBUG SONU ---
        $message = "<div class='error'>Bilet satın alınırken hata oluştu: " . $e->getMessage() . "</div>";
    }
}

// Navbar için kullanıcı adı ve soyadını oturumdan al
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
    <title>Bilet Onayı - Otobüs Takip Sistemi</title>
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
        <h2>Bilet Onayı</h2>
        <?php echo $message; ?>

        <?php if ($trip_details && $customer_details): ?>
            <div class="card">
                <h3>Sefer Bilgileri</h3>
                <p><strong>Kalkış:</strong> <?php echo htmlspecialchars($trip_details['start_location']); ?></p>
                <p><strong>Varış:</strong> <?php echo htmlspecialchars($trip_details['end_location']); ?></p>
                <p><strong>Kalkış Zamanı:</strong> <?php echo htmlspecialchars(date('d.m.Y H:i', strtotime($trip_details['departure_time']))); ?></p>
                <p><strong>Sürücü:</strong> <?php echo htmlspecialchars($trip_details['driver_name'] . ' ' . $trip_details['driver_surname']); ?></p>
                <p><strong>Araç:</strong> <?php echo htmlspecialchars($trip_details['plate_number']); ?></p>
                <p><strong>Fiyat:</strong> <?php echo htmlspecialchars(number_format($trip_details['price_per_ticket'], 2)) . ' TL'; ?></p>
                <p><strong>Koltuk Numarası:</strong> Atanacak</p>
            </div>

            <div class="card" style="margin-top: 20px;">
                <h3>Yolcu Bilgileri</h3>
                <p><strong>Ad Soyad:</strong> <?php echo htmlspecialchars($customer_details['name'] . ' ' . $customer_details['surname']); ?></p>
                <p><strong>E-posta:</strong> <?php echo htmlspecialchars($customer_details['email']); ?></p>
                <p><strong>Telefon:</strong> <?php echo htmlspecialchars($customer_details['phone_number']); ?></p>
            </div>

            <form action="buy_ticket.php" method="POST" style="margin-top: 20px;">
                <input type="hidden" name="trip_id" value="<?php echo htmlspecialchars($trip_details['trip_id']); ?>">
                <button type="submit" name="confirm_purchase" class="button" style="background-color: #007bff;">Bileti Onayla</button>
                <a href="view_trips.php" class="button" style="background-color: #6c757d;">Geri Dön</a>
            </form>
        <?php else: ?>
            <p><?php echo $message; ?></p>
        <?php endif; ?>
    </div>
</body>
</html>