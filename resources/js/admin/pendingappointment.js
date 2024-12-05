import axios from 'axios';

document.addEventListener('DOMContentLoaded', function () {
    const filterDateInput = document.getElementById('filterDate');

        const today = new Date();
        const tomorrow = new Date(today);
        tomorrow.setDate(today.getDate() + 1);

        filterDateInput.min = tomorrow.toISOString().split('T')[0];
        filterDateInput.value = filterDateInput.min;

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
                            <td>${appointment.service}</td>
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

    
    function fetchAppointmentList() {
        axios.post('/admin/appointment/populate')
            .then(response => {
                const appointments = response.data;
                const tableBody = document.getElementById('appointmentListTableBody');
                tableBody.innerHTML = '';

                if (appointments.length === 0) {
                    const noAppointmentsRow = `
                        <tr>
                            <td colspan="7">No Appointments Found</td>
                        </tr>
                    `;
                    tableBody.innerHTML = noAppointmentsRow;
                    return;
                }
                appointments.forEach(appointment => {
                    const appointmentDate = new Date(appointment.appointment_date).toISOString().split('T')[0];
                    const appointmentTime = convertTo12HourFormat(appointment.appointment_time);
                    const createdAt = new Date(appointment.created_at).toISOString().split('T')[0];
                    const row = `
                        <tr>
                            <td>${appointment.id}</td>
                            <td>${appointment.name}</td>
                            <td>${appointmentDate}</td>
                            <td>${appointmentTime}</td>
                            <td>${appointment.service}</td>
                            <td>${appointment.status}</td>
                            <td>${createdAt}</td>
                        </tr>
                    `;
                    tableBody.innerHTML += row;
                });
            })
            .catch(error => {
                console.error('There was an error fetching the appointments!', error);
            });
    }
    
    fetchPendingAppointments();
    fetchAppointmentList();

// Fetch appointments when the date is changed
filterDateInput.addEventListener('change', () => {
    fetchPendingAppointments(filterDateInput.value);
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

        // Display success alert
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