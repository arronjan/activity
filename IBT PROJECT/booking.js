let selectedCourt = null;
let courts = [];

//initialize

document.addEventListener('DOMContentLoaded', function() {
    console.log('Booking page loaded');
    checkAuth();
    loadCourts();
    setupFormListeners();
});

//load courts gikan server

async function loadCourts() {
    console.log('Loading courts...');
    try {
        showLoading();
        const response = await fetch('get_courts.php');
        courts = await response.json();
        console.log('Courts loaded:', courts);
        displayCourts(courts);
        hideLoading();
    } catch (error) {
        console.error('Error loading courts:', error);
        showNotification('Error loading courts', 'error');
        hideLoading();
    }
}

function displayCourts(courts) {
    const courtSelection = document.getElementById('courtSelection');
    if (!courtSelection) {
        console.error('Court selection container not found!');
        return;
    }
    
    console.log('Displaying courts:', courts);
    
    courtSelection.innerHTML = courts.map(court => {
        const isAvailable = court.availability_status === 'Available';
        const isSelected = selectedCourt?.court_id === court.court_id;
        
        console.log(`Court ${court.court_id}: ${court.availability_status}`);
        
        return `
            <div class="court-card ${isAvailable ? 'available' : 'occupied'} ${isSelected ? 'selected' : ''}" 
                 data-court-id="${court.court_id}"
                 data-status="${court.availability_status}"
                 onclick="selectCourt(${court.court_id})">
                <div class="court-icon">🏸</div>
                <p class="court-name">${court.court_name}</p>
                <p class="court-status">${court.availability_status}</p>
            </div>
        `;
    }).join('');
}


window.selectCourt = function(courtId) {
    console.log('=== SELECT COURT CALLED ===');
    console.log('Court ID:', courtId);
    console.log('All courts:', courts);
    
    // Find the court
    const court = courts.find(c => c.court_id == courtId); // Use == instead of ===
    console.log('Found court:', court);
    
    if (!court) {
        console.error('Court not found!');
        showNotification('Court not found', 'error');
        return;
    }
    
    // Log the status check
    console.log('Court status:', court.availability_status);
    console.log('Is Available?', court.availability_status === 'Available');
    
    // REMOVED THE AVAILABILITY CHECK - Allow selection of any court
    // The server will check availability when booking
    
    selectedCourt = court;
    console.log('Selected court set to:', selectedCourt);
    
    // Update UI
    document.querySelectorAll('.court-card').forEach(card => {
        card.classList.remove('selected');
    });
    
    const selectedCard = document.querySelector(`[data-court-id="${courtId}"]`);
    console.log('Selected card element:', selectedCard);
    
    if (selectedCard) {
        selectedCard.classList.add('selected');
        console.log('Added selected class to card');
    }
    
    updateBookingSummary();
    
    // Show success notification
    showNotification(`${court.court_name} selected!`, 'success');
}


function setupFormListeners() {
    const dateInput = document.getElementById('bookingDate');
    const startTimeInput = document.getElementById('startTime');
    const endTimeInput = document.getElementById('endTime');
    
    if (dateInput) {
        dateInput.addEventListener('change', updateBookingSummary);
    }
    
    if (startTimeInput) {
        startTimeInput.addEventListener('change', updateBookingSummary);
    }
    
    if (endTimeInput) {
        endTimeInput.addEventListener('change', updateBookingSummary);
    }
}



function updateBookingSummary() {
    console.log('Updating booking summary...');
    console.log('Selected court:', selectedCourt);
    
    const summaryContainer = document.getElementById('bookingSummary');
    if (!summaryContainer) return;
    
    const bookingDate = document.getElementById('bookingDate')?.value;
    const startTime = document.getElementById('startTime')?.value;
    const endTime = document.getElementById('endTime')?.value;
    
    console.log('Form values:', { bookingDate, startTime, endTime });
    
    // Check if all required fields are filled
    if (!selectedCourt || !bookingDate || !startTime || !endTime) {
        summaryContainer.innerHTML = `
            <div class="empty-state">
                <div class="empty-icon">📅</div>
                <p>Select a court and fill in the booking details</p>
            </div>
        `;
        return;
    }
    
    // Calculate duration and price
    const duration = calculateDuration(startTime, endTime);
    const price = calculatePrice(startTime, endTime);
    
    console.log('Calculated:', { duration, price });
    
    // Display summary
    summaryContainer.innerHTML = `
        <div class="summary-card">
            <p class="summary-label">Selected Court</p>
            <p class="summary-value">${selectedCourt.court_name}</p>
        </div>
        
        <div class="summary-details">
            <div class="summary-row">
                <span>Date</span>
                <span class="summary-value">${formatDate(bookingDate)}</span>
            </div>
            <div class="summary-row">
                <span>Time</span>
                <span class="summary-value">${formatTime(startTime)} - ${formatTime(endTime)}</span>
            </div>
            <div class="summary-row">
                <span>Duration</span>
                <span class="summary-value">${duration}</span>
            </div>
            <div class="summary-row">
                <span>Court Fee (₱250/hr)</span>
                <span class="summary-value">₱${price}</span>
            </div>
            <div class="summary-row total">
                <span>Total Amount</span>
                <span class="summary-value">₱${price}</span>
            </div>
        </div>
        
        <div class="booking-actions">
            <button class="btn btn-primary" onclick="submitBooking()">
                Confirm Booking
            </button>
            <p class="alert-note">
                ⚠️ Cancellation allowed 3 days before booking
            </p>
        </div>
    `;
}


window.submitBooking = async function() {
    console.log('=== SUBMIT BOOKING CALLED ===');
    
    const user = JSON.parse(sessionStorage.getItem('user'));
    if (!user) {
        showNotification('Please login to continue', 'error');
        return;
    }
    
    console.log('User:', user);
    console.log('Selected court:', selectedCourt);
    
    // Check if court is selected
    if (!selectedCourt) {
        showNotification('Please select a court', 'error');
        return;
    }
    
    const bookingData = {
        user_id: user.user_id,
        court_id: selectedCourt.court_id,
        booking_date: document.getElementById('bookingDate').value,
        start_time: document.getElementById('startTime').value,
        end_time: document.getElementById('endTime').value
    };
    
    console.log('Booking data:', bookingData);
    
    // Validate all fields are filled
    if (!bookingData.booking_date || !bookingData.start_time || !bookingData.end_time) {
        showNotification('Please fill in all booking details', 'error');
        return;
    }
    
    // Validate form
    const validation = validateBookingForm(bookingData);
    if (!validation.valid) {
        showNotification(validation.errors[0], 'error');
        return;
    }
    
    // Check for past dates
    const bookingDate = new Date(bookingData.booking_date);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    
    if (bookingDate < today) {
        showNotification('Cannot book for past dates', 'error');
        return;
    }
    
    // Submit booking
    console.log('Sending booking request...');
    try {
        showLoading();
        
        const response = await fetch('create_booking.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(bookingData)
        });
        
        console.log('Response status:', response.status);
        
        const result = await response.json();
        console.log('Response data:', result);
        
        hideLoading();
        
        if (result.success) {
            showNotification('Booking created successfully!', 'success');
            
            // Reset form
            setTimeout(() => {
                window.location.href = 'dashboard.html';
            }, 1500);
        } else {
            console.error('Booking failed:', result.message);
            showNotification(result.message || 'Booking failed', 'error');
        }
    } catch (error) {
        hideLoading();
        console.error('Error submitting booking:', error);
        showNotification('Connection error. Please try again.', 'error');
    }
}

// ============================================
// 8. DEBUG INFO
// ============================================

// Log to console when page loads
console.log('Booking.js loaded successfully');

// Make variables accessible in console for debugging
window.debugBooking = function() {
    console.log('=== BOOKING DEBUG INFO ===');
    console.log('Selected Court:', selectedCourt);
    console.log('All Courts:', courts);
    console.log('Form Values:', {
        date: document.getElementById('bookingDate')?.value,
        startTime: document.getElementById('startTime')?.value,
        endTime: document.getElementById('endTime')?.value
    });
};

console.log('Type debugBooking() in console to see current state');