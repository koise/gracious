import axios from 'axios';
import $ from 'jquery';

document.addEventListener('DOMContentLoaded', function () {
    const filterDateInput = document.getElementById('filterDate');

        const today = new Date();
        const tomorrow = new Date(today);
        tomorrow.setDate(today.getDate() + 1);

        $('#date').text('Date: ' + tomorrow.toISOString().split('T')[0]);
        filterDateInput.min = tomorrow.toISOString().split('T')[0];
        filterDateInput.value = filterDateInput.min;

    $(document).on('click', '#generate-btn', function() {
        let table = `<table>`;

        const header = `
            <thead>
                <tr>
                    <th colspan="10" id="date">Date: ${filterDateInput.value}</th>
                </tr>
                <tr>
                    <th colspan="5">Morning</th>
                    <th colspan="5">Afternoon</th>
                </tr>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Time</th>
                    <th>Procedure</th>
                    <th>Signature</th>
                    <th>#</th>
                    <th>Name</th>
                    <th>Time</th>
                    <th>Procedure</th>
                    <th>Signature</th>
                </tr>
            </thead>
        `;
        table += header;

        let tableBody = `<tbody>`;
        console.log($('#filterDate').val());
        axios.post('/admin/appointment/schedule/populate', { filterDate: $('#filterDate').val()  })
                .then(response => {
                    const { appointments } = response.data; 
                    console.log(appointments);
                    let morningAppointments = appointments.Morning || [];
                    let afternoonAppointments = appointments.Afternoon || [];
                    
                    while (morningAppointments.length < 30) {
                        morningAppointments.push({});
                    }
                    while (afternoonAppointments.length < 30) {
                        afternoonAppointments.push({});
                    }
            
                    
                    let morningCount = 1; 
                    let afternoonCount = 1;
            
                    for (let i = 0; i < 30; i++) {
                        let row = `<tr>`;
            
                        if (morningAppointments[i] && morningAppointments[i].name) {
                            row += `
                                <td>${morningCount}</td>
                                <td>${morningAppointments[i].name}</td>
                                <td>${morningAppointments[i].appointment_time}</td>
                                <td>${morningAppointments[i].procedures}</td>
                                <td></td>
                            `;
                            morningCount++;
                        } else {
                            row += `
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            `;
                        }
            
                        if (afternoonAppointments[i] && afternoonAppointments[i].name) {
                            row += `
                                <td>${afternoonCount}</td> 
                                
                                <td>${afternoonAppointments[i].name}</td>
                                <td>${afternoonAppointments[i].appointment_time}</td>
                                <td>${afternoonAppointments[i].procedures}</td>
                                <td></td>
                            `;
                            afternoonCount++;
                        } else {
                            row += `
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            `;
                        }

                        row += `</tr>`;
                        tableBody += row;
                    }
                    tableBody += `</tbody>`;
                    table += tableBody;
                    
                    axios.post('/admin/appointment/schedule/generate-pdf', {
                        content: table
                    }, {
                        responseType: 'blob'  
                    })
                    .then(response => {
                        const link = document.createElement('a');
                        const url = window.URL.createObjectURL(new Blob([response.data]));
                        link.href = url;
                        link.download = 'schedule.pdf';  
                        document.body.appendChild(link);
                        link.click(); 
                        document.body.removeChild(link); 
                    })
                    .catch(error => {
                        console.error(error);
                        alert('There was an error generating the PDF');
                    });
                })
                .catch(error => {
                    console.error('Error fetching appointments:', error);
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
                                <td colspan="8" style="font-weight: bold;">No Appointments Found</td>
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
                            timeInput.value = minTime;
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
                    const { appointments } = response.data; 
                    const tableBody = document.getElementById('scheduleAppointmentsTableBody');
                    tableBody.innerHTML = '';
                    let morningAppointments = appointments.Morning || [];
                    let afternoonAppointments = appointments.Afternoon || [];
            
                    while (morningAppointments.length < 30) {
                        morningAppointments.push({});
                    }
                    while (afternoonAppointments.length < 30) {
                        afternoonAppointments.push({});
                    }
            
                    
                    let morningCount = 1; 
                    let afternoonCount = 1;
            
                    for (let i = 0; i < 30; i++) {
                        let row = '<tr>';
            
                        if (morningAppointments[i] && morningAppointments[i].name) {
                            row += `
                                <td>${morningCount}</td>
                                <td>${morningAppointments[i].name}</td>
                                <td>${morningAppointments[i].appointment_time}</td>
                                <td>${morningAppointments[i].procedures}</td>
                                <td>
                                <select name="status" 
                                        data-id="${morningAppointments[i].id}" 
                                        data-prev-value="${morningAppointments[i].status}">
                                    <option value="${morningAppointments[i].status}" selected disabled>${morningAppointments[i].status}</option>
                                    <option value="Missed" ${morningAppointments[i].status === "Missed" ? "selected" : ""}>Missed</option>
                                    <option value="Completed" ${morningAppointments[i].status === "Completed" ? "selected" : ""}>Completed</option>
                                </select>
                                </td>
                            `;
                            morningCount++;
                        } else {
                            row += `
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            `;
                        }
            
                        if (afternoonAppointments[i] && afternoonAppointments[i].name) {
                            row += `
                                <td>${afternoonCount}</td> 
                                
                                <td>${afternoonAppointments[i].name}</td>
                                <td>${afternoonAppointments[i].appointment_time}</td>
                                <td>${afternoonAppointments[i].procedures}</td>
                                <td>
                                <select name="status" 
                                        data-id="${afternoonAppointments[i].id}" 
                                        data-prev-value="${afternoonAppointments[i].status}">
                                    <option value="Accepted" ${afternoonAppointments[i].status === "Accepted" ? "selected" : ""} disabled>Accepted</option>
                                    <option value="Ongoing" ${afternoonAppointments[i].status === "Ongoing" ? "selected" : ""} disabled>Ongoing</option>
                                    <option value="Missed" ${afternoonAppointments[i].status === "Missed" ? "selected" : ""}>Missed</option>
                                    <option value="Completed" ${afternoonAppointments[i].status === "Completed" ? "selected" : ""}>Completed</option>
                                </select>
                                </td>
                            `;
                            afternoonCount++;
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

                        tableBody.innerHTML += row;
                    }
                })
                .catch(error => {
                    console.error('Error fetching appointments:', error);
                });
        }
        
        
        
    fetchPendingAppointments();
    fetchScheduledAppointments();

    $(document).on('change', 'select[name="status"]', function () {
        const $select = $(this);
        const appointmentId = $select.data('id'); 
        const status = $select.val(); 

        axios.post('/admin/appointment/update', {
            id: appointmentId,
            status: status
        })
        .then(response => {
            alert("Status updated successfully");
        })
        .catch(error => {
            console.error("Error updating status:", error);
            $select.val($select.data('prev-value'));
        });

        $select.data('prev-value', status);
    });

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
                $('#time').val(timeRange[0]); 
                $('#time-validation-message').text('');
                $('#timeRange').text(`${min} to ${max}`);
            } else {
                $('#time').removeAttr('min');
                $('#time').removeAttr('max');
                $('#time').val('');
                $('#timeRange').text('');
                $('#time-validation-message').text(''); 
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
        validationMessage.text('');
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
        fetchScheduledAppointments();
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
        fetchScheduledAppointments();
        $('#rejectModal').fadeOut();

        // Display success alert
        alert('Appointment rejected!');
    })
    .catch(() => {
        alert('Error accepting appointment');
    });
});
});