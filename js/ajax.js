function filterTrips() {
    const lineId = document.getElementById('filter_line').value;
    const filterDate = document.getElementById('filter_date').value;
    const tripsListDiv = document.getElementById('trips_list');

    // AJAX isteği başlat
    fetch(`../includes/get_filtered_trips.php?line_id=${lineId}&filter_date=${filterDate}`)
        .then(response => response.text()) // Yanıtı metin olarak al
        .then(data => {
            tripsListDiv.innerHTML = data; // Gelen HTML'i trips_list div'ine yerleştir
        })
        .catch(error => {
            console.error('Seferler filtrelenirken hata oluştu:', error);
            tripsListDiv.innerHTML = '<p class="error">Seferler yüklenirken bir hata oluştu.</p>';
        });
}

// Sayfa yüklendiğinde varsayılan olarak seferleri getir
document.addEventListener('DOMContentLoaded', filterTrips);