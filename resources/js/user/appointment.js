import $ from 'jquery';
import axios from 'axios';
import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import timeGridPlugin from '@fullcalendar/timegrid';
import listPlugin from '@fullcalendar/list';

let calendar;
let selectedTimeSlot = null;
let bookedTimeSlots = {}; // Will store booked time slots by date

// Initialize Calendar with enhanced features
function initializeCalendar() {
    const calendarEl = document.getElementById('calendar');
    if (!calendarEl) return;

    // Show loading indicator
    $(calendarEl).append('<div class="calendar-loading">Loading calendar...</div>');

    calendar = new Calendar(calendarEl, {
        plugins: [dayGridPlugin, interactionPlugin, timeGridPlugin, listPlugin],
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
        },
        events: {
            url: '/appointment/calendar-events',
            failure: function() {
                // Show a notification if events fail to load
                showNotification('Failed to load calendar events. Using demo mode.', 'warning');
                // Return demo events if API fails
                return [
                    {
                        title: 'Demo Appointment',
                        start: new Date(new Date().setDate(new Date().getDate() + 5)),
                        extendedProps: { status: 'pending' }
                    }
                ];
            }
        },
        eventClick: function(info) {
            showAppointmentDetails(info.event);
        },
        eventDidMount: function(info) {
            const status = info.event.extendedProps.status?.toLowerCase() || 'pending';
            info.el.classList.add(`appointment-status-${status}`);
            
            // Enhanced tooltip
            $(info.el).attr('title', `${info.event.title}\nStatus: ${info.event.extendedProps.status}\nTime: ${info.event.start.toLocaleTimeString()}`);
        },
        selectable: true,
        select: function(info) {
            handleDateSelection(info);
        },
        eventTimeFormat: {
            hour: 'numeric',
            minute: '2-digit',
            meridiem: 'short'
        },
        height: 'auto',
        aspectRatio: 1.8,
        displayEventTime: true,
        displayEventEnd: true,
        eventDisplay: 'block',
        dayMaxEvents: true,
        eventOverlap: false,
        slotMinTime: '08:00:00',
        slotMaxTime: '17:00:00',
        allDaySlot: false,
        nowIndicator: true,
        businessHours: {
            daysOfWeek: [1, 2, 3, 4, 5], // Monday - Friday
            startTime: '08:00',
            endTime: '17:00',
        },
        eventContent: function(arg) {
            return {
                html: `
                    <div class="fc-event-main-content">
                        <div class="fc-event-title">${arg.event.title}</div>
                        <div class="fc-event-time">${arg.timeText}</div>
                        <div class="fc-event-status">${arg.event.extendedProps.status}</div>
                    </div>
                `
            };
        },
        loading: function(isLoading) {
            if (isLoading) {
                $('#calendar').append('<div class="calendar-loading">Loading calendar data...</div>');
            } else {
                $('.calendar-loading').remove();
            }
        },
        datesSet: function(info) {
            updateCalendarLegend();
            // Prefetch booked slots for visible month
            const startMonth = new Date(info.start);
            fetchBookedTimeSlotsForMonth(startMonth);
        },
        dayCellDidMount: function(info) {
            // Highlight today and add hover effects
            if (info.date.toDateString() === new Date().toDateString()) {
                info.el.classList.add('fc-day-today-enhanced');
            }
        },
        dayHeaderDidMount: function(info) {
            // Enhance day headers
            info.el.classList.add('fc-header-enhanced');
        }
    });
    
    calendar.render();
    updateCalendarLegend();
    $('.calendar-loading').remove();
}

// Handle date selection with validation and feedback
function handleDateSelection(info) {
    const selectedDate = info.startStr;
    const today = new Date();
    const minDate = new Date(today.setDate(today.getDate() + 3));
    
    if (new Date(selectedDate) < minDate) {
        showNotification('Please select a date at least 3 days from now.', 'warning');
        return;
    }

    // Weekend validation
    const dayOfWeek = new Date(selectedDate).getDay();
    if (dayOfWeek === 0 || dayOfWeek === 6) { // 0 = Sunday, 6 = Saturday
        showNotification('Weekend appointments are not available. Please select a weekday.', 'warning');
        return;
    }

    // Switch to timeGridDay view with animation
    calendar.unselect(); // Clear selection
    $('#calendar').addClass('view-transition');
    
    setTimeout(() => {
        calendar.changeView('timeGridDay', selectedDate);
        $('#calendar').removeClass('view-transition');
        
        // Show available time slots after a short delay
        setTimeout(() => {
            showAvailableTimeSlots(selectedDate);
        }, 300);
    }, 300);
}

// Show available time slots for the selected date with enhanced UI
function showAvailableTimeSlots(selectedDate) {
    // Fetch booked time slots for this date if not already cached
    if (!bookedTimeSlots[selectedDate]) {
        fetchBookedTimeSlotsForDate(selectedDate, (bookedSlots) => {
            renderTimeSlotModal(selectedDate, bookedSlots);
        });
    } else {
        renderTimeSlotModal(selectedDate, bookedTimeSlots[selectedDate]);
    }
}

// Render the time slot modal with booked slots data
function renderTimeSlotModal(selectedDate, bookedSlots = []) {
    // Format date for display
    const dateOptions = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    const formattedDate = new Date(selectedDate).toLocaleDateString(undefined, dateOptions);
    
    // Create time slot modal
    const modal = $(`
        <div class="calendar-modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Available Time Slots for ${formattedDate}</h3>
                    <button class="close-modal">×</button>
                </div>
                <div class="modal-body">
                    <div class="time-slots-container">
                        <div class="time-slots-section">
                            <h4>Morning Slots (8:00 AM - 12:00 PM)</h4>
                            <div class="time-slots-grid" id="morningSlots"></div>
                        </div>
                        <div class="time-slots-section">
                            <h4>Afternoon Slots (1:00 PM - 5:00 PM)</h4>
                            <div class="time-slots-grid" id="afternoonSlots"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `);

    // Generate time slots
    const morningSlots = generateTimeSlots('08:00', '12:00', selectedDate, bookedSlots);
    const afternoonSlots = generateTimeSlots('13:00', '17:00', selectedDate, bookedSlots);

    // Show the modal first, then populate slots with animation
    $('body').append(modal);
    setTimeout(() => {
        modal.addClass('active');
        modal.find('.modal-content').css('transform', 'translateY(0)');
        
        // Populate time slots with animation
        setTimeout(() => {
            $('#morningSlots').html(morningSlots);
            
            setTimeout(() => {
                $('#afternoonSlots').html(afternoonSlots);
                
                // Add click handlers for time slots
                addTimeSlotHandlers(modal, selectedDate);
            }, 100);
        }, 100);
    }, 10);
    
    // Close modal handlers
    setUpModalCloseHandlers(modal);
}

// Add click handlers for time slots
function addTimeSlotHandlers(modal, selectedDate) {
    $('.time-slot:not(.unavailable)').click(function() {
        const selectedTime = $(this).data('time');
        const selectedPreference = $(this).data('preference');
        
        // Remove selected class from all slots and add to clicked slot
        $('.time-slot').removeClass('selected');
        $(this).addClass('selected');
        
        selectedTimeSlot = {
            date: selectedDate,
            time: selectedTime,
            preference: selectedPreference
        };
        
        // Set the selected time and preference in the booking form
        $('#date').val(selectedDate);
        $('#preference').val(selectedPreference);
        
        // Add a small delay before closing the modal for better UX
        setTimeout(() => {
            modal.removeClass('active');
            modal.find('.modal-content').css('transform', 'translateY(-20px)');
            
            setTimeout(() => {
                modal.remove();
                // Open the booking modal with animation
                $('#addModal').fadeIn().css('display', 'flex');
                $('#addModal .modal').addClass('animated');
            }, 300);
        }, 300);
    });
}

// Set up handlers to close the modal
function setUpModalCloseHandlers(modal) {
    // Close button handler
    modal.find('.close-modal').click(() => {
        modal.removeClass('active');
        modal.find('.modal-content').css('transform', 'translateY(-20px)');
        setTimeout(() => modal.remove(), 300);
    });

    // Close modal when clicking outside
    modal.click(function(e) {
        if (e.target === this) {
            modal.removeClass('active');
            modal.find('.modal-content').css('transform', 'translateY(-20px)');
            setTimeout(() => modal.remove(), 300);
        }
    });

    // Add keyboard support
    $(document).on('keydown.modal', function(e) {
        if (e.key === 'Escape') {
            modal.removeClass('active');
            modal.find('.modal-content').css('transform', 'translateY(-20px)');
            setTimeout(() => modal.remove(), 300);
        }
    });

    // Clean up keyboard listener when modal is closed
    modal.on('remove', function() {
        $(document).off('keydown.modal');
    });
}

// Generate time slots with booked slots taken into account
function generateTimeSlots(startTime, endTime, selectedDate, bookedSlots = []) {
    let slots = '';
    const start = new Date(`2000-01-01 ${startTime}`);
    const end = new Date(`2000-01-01 ${endTime}`);
    
    while (start < end) {
        const timeString = start.toLocaleTimeString('en-US', { 
            hour: '2-digit', 
            minute: '2-digit',
            hour12: true 
        });
        
        const preference = start.getHours() < 12 ? 'Morning' : 'Afternoon';
        
        // Check if the time slot is available
        const timeValue = `${start.getHours().toString().padStart(2, '0')}:${start.getMinutes().toString().padStart(2, '0')}`;
        const isAvailable = !bookedSlots.includes(timeValue);
        
        slots += `
            <div class="time-slot ${!isAvailable ? 'unavailable' : ''}" 
                 data-time="${timeString}" 
                 data-preference="${preference}"
                 ${!isAvailable ? 'title="This time slot is already booked"' : 'title="Available"'}>
                ${timeString}
                ${!isAvailable ? '<span class="slot-status">Booked</span>' : ''}
            </div>
        `;
        
        start.setMinutes(start.getMinutes() + 30); // 30-minute intervals
    }
    
    return slots;
}

// Fetch booked time slots for a specific date
function fetchBookedTimeSlotsForDate(date, callback) {
    showLoading();
    
    axios.get(`/appointment/booked-slots?date=${date}`)
        .then(response => {
            bookedTimeSlots[date] = response.data.booked_slots || [];
            hideLoading();
            if (callback) callback(bookedTimeSlots[date]);
        })
        .catch(error => {
            console.warn('Error fetching booked slots (API might not be ready yet):', error);
            // Continue with empty booked slots if API fails
            bookedTimeSlots[date] = [];
            hideLoading();
            if (callback) callback([]);
        });
}

// Fetch booked time slots for an entire month
function fetchBookedTimeSlotsForMonth(date) {
    const year = date.getFullYear();
    const month = date.getMonth() + 1; // JavaScript months are 0-based
    
    showLoading();
    
    axios.get(`/appointment/booked-slots-month?year=${year}&month=${month}`)
        .then(response => {
            const monthData = response.data.booked_slots || {};
            // Merge the data with our cache
            Object.assign(bookedTimeSlots, monthData);
            hideLoading();
        })
        .catch(error => {
            console.warn('Error fetching month booked slots (API might not be ready yet):', error);
            // Continue with the existing cache
            hideLoading();
        });
}

// Show loading indicator
function showLoading() {
    if ($('.global-loading').length === 0) {
        $('body').append('<div class="global-loading">Loading...</div>');
    }
}

// Hide loading indicator
function hideLoading() {
    $('.global-loading').remove();
}

// Show notification message
function showNotification(message, type = 'info') {
    const notification = $(`
        <div class="notification notification-${type}">
            <div class="notification-content">${message}</div>
        </div>
    `);
    
    $('body').append(notification);
    
    setTimeout(() => {
        notification.addClass('show');
        
        setTimeout(() => {
            notification.removeClass('show');
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }, 10);
}

// Update calendar legend with animation
function updateCalendarLegend() {
    const legendContainer = $('.calendar-legend');
    if (legendContainer.length === 0) {
        $('.calendar-container').append('<div class="calendar-legend"></div>');
    }
    
    const statuses = {
        'pending': 'Pending',
        'accepted': 'Accepted',
        'completed': 'Completed',
        'cancelled': 'Cancelled',
        'ongoing': 'Ongoing'
    };
    
    let legendHtml = '<div class="legend-title">Status Legend:</div>';
    Object.entries(statuses).forEach(([status, label]) => {
        legendHtml += `
            <div class="legend-item">
                <span class="legend-color appointment-status-${status}"></span>
                <span class="legend-label">${label}</span>
            </div>
        `;
    });
    
    $('.calendar-legend').html(legendHtml);
    
    // Add animation
    $('.legend-item').each((index, item) => {
        setTimeout(() => {
            $(item).addClass('legend-item-visible');
        }, index * 100);
    });
}

// Show appointment details in a modal with enhanced UI
function showAppointmentDetails(event) {
    const statusClass = `appointment-status-${event.extendedProps.status?.toLowerCase() || 'pending'}`;
    
    // Format dates nicely
    const formattedDate = event.start.toLocaleDateString(undefined, { 
        weekday: 'long', 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    });
    
    const formattedTime = event.start.toLocaleTimeString(undefined, {
        hour: '2-digit',
        minute: '2-digit',
        hour12: true
    });
    
    const details = `
        <div class="appointment-details">
            <h3>Appointment Details</h3>
            <div class="details-content">
                <div class="detail-item">
                    <span class="detail-label">Date:</span>
                    <span class="detail-value">${formattedDate}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Time:</span>
                    <span class="detail-value">${formattedTime}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Procedure:</span>
                    <span class="detail-value">${event.title}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Status:</span>
                    <span class="detail-value ${statusClass}">${event.extendedProps.status}</span>
                </div>
                ${event.extendedProps.remarks ? `
                    <div class="detail-item">
                        <span class="detail-label">Remarks:</span>
                        <span class="detail-value">${event.extendedProps.remarks}</span>
                    </div>
                ` : ''}
            </div>
        </div>
    `;
    
    const modal = $(`
        <div class="calendar-modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Appointment Details</h3>
                    <button class="close-modal">×</button>
                </div>
                <div class="modal-body">
                    ${details}
                </div>
            </div>
        </div>
    `);
    
    $('body').append(modal);
    setTimeout(() => {
        modal.addClass('active');
        modal.find('.modal-content').css('transform', 'translateY(0)');
    }, 10);
    
    setUpModalCloseHandlers(modal);
}

// Utility Functions
function resetCurrentAppointmentUI() {
    $('.progress-node').removeClass('active');
    $('.progress-line').removeClass('active');
    $('#dateText').text('None');
    $('#timeText').text('None');
    $('#serviceText').text('None');
    $('#bookButton').show();
    $('#cancelButton').hide();
}

function updateCancelButton(status, hoursDifference) {
    const cancelButton = $('#cancelButton');
    const bookButton = $('#bookButton');
    if (status === 'Accepted' || status === 'Pending' || status === 'Ongoing') {
        // Allow cancellation anytime for Pending status
        if (status === 'Pending') {
            cancelButton.prop('disabled', false).css('background-color', '');
        } else if (hoursDifference < 24) {
            // For Accepted and Ongoing, still apply 24-hour restriction
            cancelButton.prop('disabled', true).css('background-color', 'grey');
        } else {
            cancelButton.prop('disabled', false).css('background-color', '');
        }
        cancelButton.show();
        bookButton.hide();
    } else {
        cancelButton.hide();
        bookButton.show();
    }
}

function updateProgressNodes(status) {
    if (status === 'Pending') {
        $('#node1').addClass('active');
        $('#node2, #node3, #node4').removeClass('active');
        $('#line1, #line2, #line3').removeClass('active');
    } else if (status === 'Accepted') {
        $('#node1, #node2').addClass('active');
        $('#line1').addClass('active');
        $('#node3, #node4').removeClass('active');
        $('#line2, #line3').removeClass('active');
    } else if (status === 'Ongoing') {
        $('#node1, #node2, #node3').addClass('active');
        $('#line1').addClass('active');
        setTimeout(() => $('#line2').addClass('active'), 100);
        $('#node4').removeClass('active');
        $('#line3').removeClass('active');
    } else if (status === 'Completed') {
        $('#node1, #node2, #node3, #node4').addClass('active');
        $('#line1').addClass('active');
        setTimeout(() => $('#line2').addClass('active'), 100);
        setTimeout(() => $('#line3').addClass('active'), 100);
        setTimeout(() => $('#line3').addClass('active'), 100);
    }
}

function renderPagination(currentPage, lastPage, paginationWrapper, onPageClick) {
    const startPage = Math.max(1, currentPage - 1);
    const endPage = Math.min(lastPage, currentPage + 1);

    paginationWrapper.empty();

    paginationWrapper.append(currentPage > 1
        ? `<button class="pagination-link" data-page="${currentPage - 1}">Previous</button>`
        : `<button class="pagination-link disabled" disabled>Previous</button>`);

    for (let i = startPage; i <= endPage; i++) {
        const activeClass = i === currentPage ? 'active' : '';
        paginationWrapper.append(`
            <button class="pagination-link ${activeClass}" data-page="${i}">${i}</button>
        `);
    }

    paginationWrapper.append(currentPage < lastPage
        ? `<button class="pagination-link" data-page="${currentPage + 1}">Next</button>`
        : `<button class="pagination-link disabled" disabled>Next</button>`);
}

function renderAppointmentRows(appointments, tableBody) {
    tableBody.empty();

    if (appointments.length === 0) {
        tableBody.html('<tr><td colspan="4">No Appointments Found</td></tr>');
        return;
    }

    appointments.forEach(({ appointment_date, formatted_time, procedures, status }) => {
        const row = `
            <tr>
                <td>${appointment_date}</td>
                <td>${formatted_time}</td>
                <td>${procedures}</td>
                <td>${status}</td>
            </tr>`;
        tableBody.append(row);
    });
}

function fetchAppointmentList(page = 1, search = '') {
    axios.post(`appointment/populate?page=${page}&search=${search}`)
        .then(response => {
            const { data: appointments, current_page, last_page } = response.data;
            const tableBody = $('#appointmentListTableBody');
            const paginationWrapper = $('#appointmentPagination');

            renderAppointmentRows(appointments, tableBody);
            renderPagination(current_page, last_page, paginationWrapper, (page) => {
                fetchAppointmentList(page);
            });
        })
        .catch(error => console.error('Error fetching appointments:', error));
}

function fetchCurrentAppointment() {
    axios.post('appointment/fetch')
        .then(response => {
            const appointments = response.data;

            if (Array.isArray(appointments) && appointments.length > 0) {
                const appointment = appointments[0];
                const { status, appointment_date_time, formatted_time, procedures, appointment_date, hours_difference } = appointment;
                updateProgressNodes(status);
                $('#dateText').text(appointment_date);
                $('#timeText').text(formatted_time);
                $('#serviceText').text(procedures);

                updateCancelButton(status, hours_difference);
            }
        })
        .catch(error => console.error('Error fetching appointments:', error));
}

$(document).ready(() => {
    initializeCalendar();
    resetCurrentAppointmentUI();
    fetchCurrentAppointment();
    fetchAppointmentList();
    
    // Set up refresh intervals
    setInterval(() => fetchCurrentAppointment(), 30000); // 30 seconds
    setInterval(() => calendar?.refetchEvents(), 30000); // 30 seconds

    // Disable confirm button initially
    $('.submit-btn').prop('disabled', true).css('opacity', '0.5');

    // Handle terms checkbox change
    $('#terms').on('change', function() {
        $('.submit-btn').prop('disabled', !$(this).is(':checked')).css('opacity', $(this).is(':checked') ? '1' : '0.5');
    });

    let selectedServices = [];

    // Enhanced service selection
    $("#addServiceButton").click(function() {
        const selectedService = $("#service").val();

        if (!selectedService) {
            showNotification('Please select a service first', 'warning');
            return;
        }

        if (selectedServices.length >= 2) {
            showNotification('You can only select up to 2 services', 'warning');
            return; 
        }

        if (selectedService && !selectedServices.includes(selectedService)) {
            selectedServices.push(selectedService);

            const listItem = $(`
                <li class="service-item">
                    <span>${selectedService}</span>
                    <button type="button" class="remove-btn" data-service="${selectedService}">Remove</button>
                </li>
            `);
            
            // Animate new service addition
            listItem.css('opacity', 0);
            $("#serviceList").append(listItem);
            listItem.animate({ opacity: 1 }, 300);
            
            updateProceduresInput();
            
            // Show feedback
            showNotification(`Added service: ${selectedService}`, 'success');
        } else if (selectedService) {
            showNotification('This service has already been selected', 'warning');
        }
    
        $("#service").val("");
    });

    // Enhanced service removal
    $("#serviceList").on("click", ".remove-btn", function() {
        const serviceToRemove = $(this).data("service");
        const listItem = $(this).parent();

        selectedServices = selectedServices.filter(service => service !== serviceToRemove);

        // Animate removal
        listItem.animate({ opacity: 0, height: 0 }, 300, function() {
            $(this).remove();
            updateProceduresInput();
            showNotification(`Removed service: ${serviceToRemove}`, 'info');
        });
    });

    function updateProceduresInput() {
        $("#procedures").val(selectedServices.join(", "));
    }

    $(document).on('click', '.pagination-link', function () {
        const page = $(this).data('page');
        const search = $('#searchInput').val() || '';
        if (page) {
            fetchAppointmentList(page, search);
        }
    });

    $('#searchInput').on('input', function () {
        const search = $(this).val();
        fetchAppointmentList(1, search); 
    });

    $(document).on('click', '#bookButton', function() {
        $('#add-errors').empty().hide();
        
        // First check if user already has an active appointment
        axios.post('appointment/fetch')
            .then(response => {
                const appointments = response.data;
                
                // If there are active appointments, don't allow booking
                if (Array.isArray(appointments) && appointments.length > 0) {
                    const activeAppointment = appointments.find(apt => 
                        apt.status === 'Pending' || apt.status === 'Accepted' || apt.status === 'Ongoing'
                    );
                    
                    if (activeAppointment) {
                        const errorMessage = 'You can only have ONE active appointment at a time. Please cancel your existing appointment before booking a new one.';
                        showNotification(errorMessage, 'error');
                        return;
                    }
                }
                
                // No active appointments found, proceed with booking
                // Show loading indicator
                showLoading();
                
                axios.get('/user/fetch/id')
                    .then(response => {
                        hideLoading();
                        console.log('Fetch user response:', response.data);
                        
                        if (!response.data || !response.data.id) {
                            console.error('Invalid user data response. Missing ID.');
                            $('#add-errors').html('<li>Unable to retrieve your user ID. Please try refreshing the page.</li>').show();
                            return;
                        }
                        
                        $('#user-appointment-id').val(response.data.id);
                        showModal('#addModal');
                    })
                    .catch(error => {
                        hideLoading();
                        console.error('Error fetching user data:', error);
                        let errorMessage = 'Unable to retrieve your user information.';
                        
                        if (error.response) {
                            console.error('Error response:', error.response.data);
                            errorMessage = error.response.data.error || errorMessage;
                        }
                        
                        $('#add-errors').html(`<li>${errorMessage} Please try refreshing the page.</li>`).show();
                        showNotification(errorMessage, 'error');
                    });
            })
            .catch(error => {
                console.error('Error checking existing appointments:', error);
                // If we can't check, still allow them to try booking
                showLoading();
                
                axios.get('/user/fetch/id')
                    .then(response => {
                        hideLoading();
                        $('#user-appointment-id').val(response.data.id);
                        showModal('#addModal');
                    })
                    .catch(error => {
                        hideLoading();
                        showNotification('Unable to retrieve your user information.', 'error');
                    });
            });
    });

    $(document).on('click', '#cancelButton', function() {
        $('#add-errors').empty().hide();
        
        // Make sure button is clickable (in case CSS prevents clicking)
        $(this).prop('disabled', false).css('cursor', 'pointer');
        
        // Show loading indicator
        showLoading();
        
        // First get the current appointment details
        axios.post('appointment/fetch')
            .then(response => {
                hideLoading();
                const appointments = response.data;
                
                if (Array.isArray(appointments) && appointments.length > 0) {
                    const appointment = appointments[0];
                    
                    if (!appointment || !appointment.id) {
                        console.error('Invalid appointment data for cancellation. Missing ID.');
                        showNotification('Unable to retrieve appointment details', 'error');
                        return;
                    }
                    
                    // Set the appointment ID in the cancel form
                    $('#cancel-appointment-id').val(appointment.id);
                    
                    // Populate the cancel modal info
                    $('#cancel-date').text(appointment.appointment_date || 'N/A');
                    $('#cancel-time').text(appointment.formatted_time || 'None');
                    $('#cancel-service').text(appointment.procedures || 'N/A');
                    
                    // Add a note about cancellation policy based on status
                    let cancelNote = '';
                    if (appointment.status === 'Pending') {
                        cancelNote = 'Pending appointments can be cancelled at any time.';
                    } else if (appointment.hours_difference < 24) {
                        cancelNote = 'Warning: This appointment is less than 24 hours away. Please contact us directly if you need to cancel.';
                    } else {
                        cancelNote = 'Appointments can be cancelled up to 24 hours before the scheduled time.';
                    }
                    
                    // Add the note to the modal
                    $('#cancel-note').text(cancelNote);
                    
                    // Show the cancel modal
                    showModal('#cancelModal');
                } else {
                    showNotification('No active appointment found to cancel', 'warning');
                }
            })
            .catch(error => {
                hideLoading();
                console.error('Error fetching appointment data for cancel:', error);
                
                let errorMessage = 'Unable to retrieve appointment information for cancellation.';
                if (error.response) {
                    console.error('Error response (cancel):', error.response.data);
                    errorMessage = error.response.data.error || errorMessage;
                }
                
                showNotification(errorMessage, 'error');
            });
    });
    
    // Form submissions
    $('#addForm').on('submit', function(e) {
        e.preventDefault();
        $('#add-errors').empty().hide();

        // Check if terms and conditions are accepted
        if (!$('#terms').is(':checked')) {
            $('#add-errors').html('<li>You must accept the Terms and Conditions to proceed with the booking.</li>').show();
            return;
        }

        const formData = new FormData(this);
        
        // Log form data for debugging
        console.log('Form submission data:');
        for (let [key, value] of formData.entries()) {
            console.log(`${key}: ${value}`);
        }
        
        // Show loading state
        const submitBtn = $(this).find('.submit-btn');
        const originalText = submitBtn.text();
        submitBtn.prop('disabled', true).text('Processing...');
        
        // The server will get the user ID from the session if it's not in the form
        axios.post('/book/appointment', formData)
            .then(response => {
                console.log('Booking success:', response.data);
                hideModal('#addModal');
                showNotification('Appointment booked successfully!', 'success');
                fetchCurrentAppointment();
                fetchAppointmentList();
                
                // Reset form
                this.reset();
                $('#serviceList').empty();
                selectedServices = [];
            })
            .catch(error => {
                console.error('Booking error:', error);
                console.error('Error response:', error.response);
                
                let errorMessage = 'An error occurred while booking the appointment.';
                
                if (error.response) {
                    console.error('Error details:', error.response.data);
                    errorMessage = error.response.data.message || errorMessage;
                    
                    // If there are validation errors, display them
                    if (error.response.data.errors) {
                        const validationErrors = error.response.data.errors;
                        console.error('Validation errors:', validationErrors);
                        
                        let errorList = '';
                        for (const field in validationErrors) {
                            validationErrors[field].forEach(message => {
                                errorList += `<li>${message}</li>`;
                            });
                        }
                        
                        $('#add-errors').html(errorList).show();
                        return;
                    }
                    
                    // Check if this is the "only one appointment" error
                    if (errorMessage.includes('ONE active appointment')) {
                        // Show a more prominent error
                        showNotification(errorMessage, 'error');
                        $('#add-errors').html(`<li class="one-appointment-error">${errorMessage}</li>`).show();
                        return;
                    }
                }
                
                $('#add-errors').html(`<li>${errorMessage}</li>`).show();
            })
            .finally(() => {
                submitBtn.prop('disabled', false).text(originalText);
            });
    });

    $('#cancelForm').on('submit', function(e) {
        e.preventDefault();

        // Show loading state
        const submitBtn = $(this).find('.submit-btn');
        const originalText = submitBtn.text();
        submitBtn.prop('disabled', true).text('Cancelling...');
        
        const formData = new FormData(this);
        
        // The server will get the user ID from the session if it's not in the form
        axios.post('/cancel/appointment', formData)
            .then(response => {
                hideModal('#cancelModal');
                showNotification('Appointment cancelled successfully!', 'info');
                fetchCurrentAppointment();
                fetchAppointmentList();
                resetCurrentAppointmentUI();
            })
            .catch(error => {
                console.error('Error cancelling appointment:', error);
                let errorMessage = 'Failed to cancel appointment.';
                
                if (error.response && error.response.data) {
                    errorMessage = error.response.data.message || error.response.data.error || errorMessage;
                }
                
                showNotification(errorMessage, 'error');
            })
            .finally(() => {
                submitBtn.prop('disabled', false).text(originalText);
            });
    });

    // Function to show modal with animation
    function showModal(modalId) {
        const $modal = $(modalId);
        $modal.fadeIn().css('display', 'flex');
        $modal.find('.modal').addClass('animated');
        
        // Add animation class to elements inside the modal
        setTimeout(() => {
            $modal.find('h2').addClass('fade-in');
            $modal.find('.form-group').each((index, el) => {
                setTimeout(() => {
                    $(el).addClass('fade-in');
                }, index * 100);
            });
        }, 300);
    }

    // Update close handlers to use animation for closing
    $('#add-close-modal').click(() => hideModal('#addModal'));
    $('#cancel-close-modal, #cancel-close-btn').click(() => hideModal('#cancelModal'));
    
    // Function to hide modal with animation
    function hideModal(modalId) {
        const $modal = $(modalId);
        $modal.find('.modal').removeClass('animated').addClass('closing');
        
        setTimeout(() => {
            $modal.fadeOut(() => {
                $modal.find('.modal').removeClass('closing');
                $modal.find('.fade-in').removeClass('fade-in');
            });
        }, 200);
    }

    const dateInput = $('#date');
    const today = new Date();
    const minDate = new Date(today.setDate(today.getDate() + 3)).toISOString().split('T')[0];
    dateInput.attr('min', minDate);

    // Add validation functionality:

    // Function to check if a date is a weekend
    function isWeekend(dateStr) {
        const date = new Date(dateStr);
        const day = date.getDay();
        return day === 0 || day === 6; // 0 is Sunday, 6 is Saturday
    }

    // Function to validate form and update submit button state
    function validateBookingForm() {
        const date = $('#date').val();
        const preference = $('#preference').val();
        const procedures = $('#procedures').val();
        const termsChecked = $('#terms').is(':checked');
        const submitBtn = $('#addForm').find('.submit-btn');
        
        // Reset errors
        $('#add-errors').empty().hide();
        let errors = [];
        
        // Check for weekend
        if (date && isWeekend(date)) {
            errors.push('<li class="weekend-error">Appointments cannot be booked on weekends. Please select a weekday.</li>');
        }
        
        // Check for missing fields
        if (!date) {
            errors.push('<li>Please select an appointment date</li>');
        }
        
        if (!preference) {
            errors.push('<li>Please select a time preference</li>');
        }
        
        if (!procedures) {
            errors.push('<li>Please select at least one service</li>');
        }
        
        if (!termsChecked) {
            errors.push('<li>You must accept the Terms and Conditions</li>');
        }
        
        // Show errors if any
        if (errors.length > 0) {
            $('#add-errors').html(errors.join('')).show();
            submitBtn.prop('disabled', true).css('opacity', '0.5');
            return;
        }
        
        // All validations passed
        submitBtn.prop('disabled', false).css('opacity', '1');
    }
    
    // Add event listeners to form fields
    $('#preference').on('change', validateBookingForm);
    $('#terms').on('change', validateBookingForm);
    $('#procedures').on('input', validateBookingForm);
    
    // Special handling for date field to immediately validate weekends
    $('#date').on('change', function() {
        const dateValue = $(this).val();
        if (dateValue && isWeekend(dateValue)) {
            // Show the weekend error immediately
            $('#add-errors').html('<li class="weekend-error">Appointments cannot be booked on weekends. Please select a weekday.</li>').show();
            $('#addForm').find('.submit-btn').prop('disabled', true).css('opacity', '0.5');
            
            // Add visual indicator to the date field
            $(this).addClass('weekend-selected');
            
            // Optional: You could clear the date field
            // $(this).val('');
        } else {
            $(this).removeClass('weekend-selected');
            validateBookingForm();
        }
    });

    // Add listeners for service list changes
    // Replace with the MutationObserver implementation above
    // $("#serviceList").on("DOMNodeInserted DOMNodeRemoved", function() {
    //    validateBookingForm();
    // });
    
    // Initialize validation on modal open
    $('#addForm').on('reset', function() {
        setTimeout(validateBookingForm, 100);
    });
    
    $('#bookButton').on('click', function() {
        setTimeout(validateBookingForm, 500);
    });

    // Use MutationObserver instead of deprecated DOMNodeInserted and DOMNodeRemoved
    // Create a MutationObserver to watch for changes to the serviceList
    const serviceListObserver = new MutationObserver(function(mutations) {
        // Update the total when the service list changes
        updateTotal();
        
        // Also validate the booking form
        validateBookingForm();
    });
    
    // Start observing the serviceList element
    const serviceList = document.getElementById("serviceList");
    if (serviceList) {
        serviceListObserver.observe(serviceList, {
            childList: true, // Watch for changes to child elements (added/removed)
            subtree: true    // Watch for changes in the entire subtree
        });
    }
    
    // Also trigger updateTotal manually after any other relevant actions
    function updateTotal() {
        var total = 0;
        $(".service-item").each(function() {
            total += parseFloat($(this).data("price") || 0);
        });
        
        // Format total with two decimal places
        $("#totalAmount").text(formatCurrency(total));
        
        // Update hidden input field for form submission
        $("#totalField").val(total);
        
        // Toggle visibility of total section based on whether items exist
        if ($(".service-item").length > 0) {
            $("#totalSection").show();
        } else {
            $("#totalSection").hide();
        }
    }
});