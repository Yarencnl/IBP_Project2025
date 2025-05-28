function filterTrips() {
    const lineId = document.getElementById('filter_line').value;
    const filterDate = document.getElementById('filter_date').value;
    const tripsListDiv = document.getElementById('trips_list');

    
    fetch(`../includes/get_filtered_trips.php?line_id=${lineId}&filter_date=${filterDate}`)
        .then(response => response.text()) 
        .then(data => {
            tripsListDiv.innerHTML = data; 
        })
        .catch(error => {
            console.error('Seferler filtrelenirken hata oluştu:', error);
            tripsListDiv.innerHTML = '<p class="error">Seferler yüklenirken bir hata oluştu.</p>';
        });
}


document.addEventListener('DOMContentLoaded', filterTrips);