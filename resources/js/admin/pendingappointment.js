import axios from 'axios';
import $ from 'jquery';

document.addEventListener('DOMContentLoaded', function () {
    // Initialize draggable and droppable functionalities
    const filterDateInput = document.getElementById('filterDate');

        const today = new Date();
        const tomorrow = new Date(today);
        tomorrow.setDate(today.getDate() + 1);

        $('#date').text('Date: ' + tomorrow.toISOString().split('T')[0]);
        filterDateInput.min = tomorrow.toISOString().split('T')[0];
        filterDateInput.value = filterDateInput.min;

        // Event listener for generating the PDF
    $(document).on('click', '#generate-btn', function() {
        // Get the content of the table (or any other HTML you want to convert to PDF)
        const tableContent = $('#scheduleTable').html();
        // Send a POST request to the server to generate the PDF
        axios.post('/admin/appointment/schedule/generate-pdf', {
            content: tableContent
        }, {
            responseType: 'blob'  
        })
        .then(response => {
            // Create a URL for the PDF Blob and trigger the download
            const link = document.createElement('a');
            const url = window.URL.createObjectURL(new Blob([response.data]));
            link.href = url;
            link.download = 'schedule.pdf';  // Set the default download file name
            document.body.appendChild(link);
            link.click();  // Simulate a click on the link to start the download
            document.body.removeChild(link);  // Remove the link after download
        })
        .catch(error => {
            // Handle any errors that occur during the request
            console.error(error);
            alert('There was an error generating the PDF');
        });
    });


        function fetchPendingAppointments(filterDate = filterDateInput.value) {
            axios.post('/admin/appointment/pending/populate', { filterDate })
                .then(response => {
                    const { appointments, appointmentCount, appointmentCap } = response.data;
                    const tableBody = document.getElementById('pendingAppointmentsTableBody');
                    if (appointmentCount >= appointmentCap) {
                        alert(`The selected date (${filterDate}) has reached the appointment cap of ${appointmentCap}.`);
                    }
        
                    tableBody.innerHTML = '';
        
                    if (appointments.length === 0) { 
                        const noAppointmentsRow = `
                            <tr>
                                <td colspan="8">No Appointments Found</td>
                            </tr>
                        `;
                        tableBody.innerHTML = noAppointmentsRow;
                        return;
                    }
        
                    appointments.forEach(appointment => {
                        const appointmentDate = new Date(appointment.appointment_date).toISOString().split('T')[0];
                        const timeRange = appointment.time_range || ['N/A', 'N/A'];
                        const row = `
                            <tr>
                                <td>${appointment.id}</td>
                                <td>${appointment.name}</td>
                                <td>${appointmentDate}</td>
                                <td>${appointment.preference}</td>
                                <td>${appointment.status}</td>
                                <td>${appointment.procedures}</td>
                                <td>
                                    <button id="view-btn" data-id="${appointment.id}" class="view-btn">
                                        View
                                    </button>
                                </td>
                                <td>
                                    <button id="accept-btn" data-id="${appointment.id}" class="accept-btn">
                                        Accept
                                    </button>
                                    <button id="reject-btn" data-id="${appointment.id}" class="reject-btn">
                                        Reject
                                    </button>
                                </td>
                            </tr>
                        `;
                        tableBody.innerHTML += row;
        
                        const timeInput = document.getElementById('time');
                        if (appointment.preference && timeInput) {
                            const [minTime, maxTime] = timeRange;
                            timeInput.min = minTime;
                            timeInput.max = maxTime;
                            timeInput.value = minTime; // Default to start of range
                        }
                    });
        
                })
                .catch(error => {
                    console.error('Error fetching appointments:', error);
                });
        }
        
        function fetchScheduledAppointments(filterDate = filterDateInput.value) {
            axios.post('/admin/appointment/schedule/populate', { filterDate })
                .then(response => {
                    const { appointments } = response.data; // Assuming appointments are grouped by preference (Morning and Afternoon)
                    const tableBody = document.getElementById('scheduleAppointmentsTableBody');
                    // Clear the current table content
                    tableBody.innerHTML = '';
                    // Assuming appointments are now grouped by preference: Morning and Afternoon
                    let morningAppointments = appointments.Morning || [];
                    let afternoonAppointments = appointments.Afternoon || [];
            
                    // Ensure that each array has 15 elements by padding with empty objects if needed
                    while (morningAppointments.length < 30) {
                        morningAppointments.push({});
                    }
                    while (afternoonAppointments.length < 30) {
                        afternoonAppointments.push({});
                    }
            
                    // We iterate through both arrays, assuming each will now have exactly 15 elements
                    let morningCount = 1; // Initialize counter for morning appointments
                    let afternoonCount = 1; // Initialize counter for afternoon appointments
            
                    for (let i = 0; i < 30; i++) {
                        let row = '<tr>';
            
                        // Add morning preference (if it exists for the current index)
                        if (morningAppointments[i] && morningAppointments[i].name) {
                            row += `
                                <td>${morningCount}</td>
                                <td>${morningAppointments[i].name}</td>
                                <td>${morningAppointments[i].appointment_time}</td>
                                <td>${morningAppointments[i].procedures}</td>
                                <td></td>
                            `;
                            morningCount++; // Increment counter for morning
                        } else {
                            row += `
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            `;
                        }
            
                        // Add afternoon preference (if it exists for the current index)
                        if (afternoonAppointments[i] && afternoonAppointments[i].name) {
                            row += `
                                <td>${afternoonCount}</td> 
                                
                                <td>${afternoonAppointments[i].name}</td>
                                <td>${afternoonAppointments[i].appointment_time}</td>
                                <td>${afternoonAppointments[i].procedures}</td>
                                <td></td>
                            `;
                            afternoonCount++; // Increment counter for afternoon
                        } else {
                            row += `
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            `;
                        }
            
                        row += '</tr>';
            
                        // Append the row to the table body
                        tableBody.innerHTML += row;
                    }
                })
                .catch(error => {
                    console.error('Error fetching appointments:', error);
                });
        }
        
        
    fetchPendingAppointments();
    fetchScheduledAppointments();

// Fetch appointments when the date is changed
filterDateInput.addEventListener('change', () => {
    fetchPendingAppointments(filterDateInput.value);
    fetchScheduledAppointments(filterDateInput.value);
    $('#date').text('Date: ' + filterDateInput.value);
});

$(document).on('click', '#accept-btn', function () {
    const appointmentId = $(this).data('id');

    axios.get(`/admin/appointment/fetch/${appointmentId}`)
        .then(response => {
            const { appointment, timeRange } = response.data;

            $('#accept-appointment-id').val(appointment.id);
            $('#preference').text(appointment.preference);


            if (timeRange) {
                const min = convertTo12HourFormat(timeRange[0]);
                const max = convertTo12HourFormat(timeRange[1]);
                $('#time').attr('min', timeRange[0]);
                $('#time').attr('max', timeRange[1]);
                $('#time').val(timeRange[0]); // Default to the start of the range
                $('#time-validation-message').text(''); // Clear previous message
                $('#timeRange').text(`${min} to ${max}`);
            } else {
                $('#time').removeAttr('min');
                $('#time').removeAttr('max');
                $('#time').val('');
                $('#timeRange').text('');
                $('#time-validation-message').text(''); // Clear message for no range
            }

            $('#acceptModal').fadeIn().css('display', 'flex');
        })
        .catch(() => {
            alert('Error fetching appointment data');
        });
});

function convertTo12HourFormat(time) {
    if (!time) {
        return ''; 
    }
    
    const hour = parseInt(time.slice(0, 2)); 
    const minute = time.slice(3, 5); 
    const period = hour >= 12 ? 'PM' : 'AM';
    const convertedHour = hour % 12 === 0 ? 12 : hour % 12;
    return `${convertedHour}:${minute} ${period}`;
}

$('#time').on('input', function () {
    const min = this.min;
    const max = this.max;
    const value = this.value;
    const validationMessage = $('#time-validation-message');
    if (!value) {
        validationMessage.text(''); // Clear message if input is empty
        return;
    }

    if (value < min) {
        this.value = min; // Enforce minimum value
        validationMessage.text(`Invalid time. Input ${convertTo12HourFormat(min)} to ${convertTo12HourFormat(max)}.`).css('color', 'red');
    } else if (value > max) {
        this.value = max; // Enforce maximum value
        validationMessage.text(`Invalid time. Input ${convertTo12HourFormat(min)} to ${convertTo12HourFormat(max)}.`).css('color', 'red');
    } else {
        validationMessage.text(``)
    }
});

$('#accept-close-modal').click(function () {
    $('#acceptModal').fadeOut();
});

$('#acceptForm').submit(function (e) {
    e.preventDefault();

    const formData = new FormData(this);

    axios.post('/admin/appointment/confirm', formData)
    .then(() => {
        fetchPendingAppointments();
        fetchAppointmentList();
        $('#acceptModal').fadeOut();

        alert('Appointment accepted!');
    })
    .catch(() => {
        alert('Error accepting appointment');
    });
});

$(document).on('click', '#reject-btn', function () {
    const appointmentId = $(this).data('id');

        axios.get(`/admin/appointment/fetch/${appointmentId}`)
            .then(response => {
                const { appointment, timeRange } = response.data;
                $('#reject-appointment-id').val(appointment.id)
                console.log(appointment.id);
                // Show the modal
                $('#rejectModal').fadeIn().css("display", "flex");
            })
            .catch(()=> {
                alert('Error fetching appointment data');
            });
});

$('#reject-close-modal').click(function () {
    $('#rejectModal').fadeOut();
});

$('#rejectForm').submit(function (e) {
    e.preventDefault();

    const formData = new FormData(this);

    axios.post('/admin/appointment/reject', formData)
    .then(() => {
        fetchPendingAppointments();
        $('#rejectModal').fadeOut();

        // Display success alert
        alert('Appointment rejected!');
    })
    .catch(() => {
        alert('Error accepting appointment');
    });
});
});