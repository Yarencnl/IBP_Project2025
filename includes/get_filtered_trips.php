<?php
include 'db.php';

$line_id = isset($_GET['line_id']) ? intval($_GET['line_id']) : 0;
$filter_date = isset($_GET['filter_date']) ? $_GET['filter_date'] : '';

$query = "SELECT T.trip_id, L.route, D.name as driver_name, D.surname as driver_surname, V.plate_number, V.capacity, T.departure_time
          FROM TRIP T
          JOIN LINE L ON T.line_id = L.line_id
          JOIN DRIVER D ON T.driver_id = D.driver_id
          JOIN VEHICLE V ON T.vehicle_id = V.vehicle_id
          WHERE T.departure_time > NOW()"; 
$params = [];
$types = '';

if ($line_id > 0) {
    $query .= " AND T.line_id = ?";
    $params[] = $line_id;
    $types .= 'i';
}

if (!empty($filter_date)) {
    
    $query .= " AND DATE(T.departure_time) = ?";
    $params[] = $filter_date;
    $types .= 's';
}

$query .= " ORDER BY T.departure_time ASC";

$stmt = $conn->prepare($query);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$trips_result = $stmt->get_result();

if ($trips_result->num_rows > 0): ?>
    <table>
        <thead>
            <tr>
                <th>Sefer ID</th>
                <th>Hat</th>
                <th>Sürücü</th>
                <th>Araç Plakası</th>
                <th>Kalkış Zamanı</th>
                <th>Kapasite</th>
                <th>İşlem</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($trip = $trips_result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($trip['trip_id']); ?></td>
                    <td><?php echo htmlspecialchars($trip['route']); ?></td>
                    <td><?php echo htmlspecialchars($trip['driver_name'] . ' ' . $trip['driver_surname']); ?></td>
                    <td><?php echo htmlspecialchars($trip['plate_number']); ?></td>
                    <td><?php echo htmlspecialchars($trip['departure_time']); ?></td>
                    <td><?php echo htmlspecialchars($trip['capacity']); ?></td>
                    <td>
                        <a href="buy_ticket.php?trip_id=<?php echo $trip['trip_id']; ?>" class="button" style="background-color: #28a745;">Bilet Al</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>Filtreleme kriterlerine uygun aktif sefer bulunmamaktadır.</p>
<?php endif;

$stmt->close();
$conn->close();
?>