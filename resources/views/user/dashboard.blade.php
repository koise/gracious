<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Appointment</title>
    
    @vite([
    'resources/scss/user/userappointment.scss',
    'resources/scss/usersidebar.scss',
    'resources/scss/modal.scss',
    'resources/scss/footer.scss'
    ])
    <style>
  :root {
    --progress-bar-width: 200px;
    --progress-bar-height: 200px;
  }

  .appointment {
    display: flex;
    flex-direction: column;
    align-items: center;
  }
  .circular-progress {
    width: var(--progress-bar-width);
    height: var(--progress-bar-height);
    border-radius: 50%;
    position: relative;
    }
  .inner-circle {
    position: absolute;
    top: 15px;
    left: 15px;
    width: calc(var(--progress-bar-width) - 30px);
    height: calc(var(--progress-bar-height) - 30px);
    border-radius: 50%;

  }

  .circular-progress p {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    text-align: center;
    font-size: 1.5rem;
    color: black;
    font-weight: bold;
  }
</style>
</head>
<body>
    <main>
        <div class="wrapper">
            <div class="container">
                @include('partials.topbar')
                <div class="content">
                    <div class="section">
                        <div class="section-header">
                            <span>Note: Appointment that are less than 24 hrs cannot be cancelled</span>
                            <h2>Book Appointment</h2>
                        </div>
                        <div class="section-content">
                            <div class="counter">
                                <div class="counter-circle">
                                    <div class="circular-progress" data-inner-circle-color="white" data-progress-color="crimson" data-bg-color="lightgray">
                                        <div class="inner-circle"></div>
                                        <p id="statusText"></p>
                                    </div>
                                </div>
                                <div class="book-button">
                                    <button id="bookButton" style="display: none;">+ Book</button>
                                    <button id="cancelButton" style="display: none;">Cancel</button>
                                </div>
                            </div>
                            <div class="appointment-information">
                                <h2>Appointment Information</h2>
                                <div class="information-row">Appointment Date: <span id="dateText"></span></div>
                                <div class="information-row">Appointment Time: <span id="timeText"></span></div>
                                <div class="information-row">Service: <span id="serviceText"></span></div>
                            </div>
                        </div>
                    </div>
                    <div class="section">
                        <div class="section-header">
                            <h2>Appointment History</h2>
                        </div>
                        <div class="table-wrapper">     
                            <div class="table-navigation">
                             <div class="search">
                                 <input type="text" placeholder="Search">
                             </div>
                         </div>
                         <div class="scrollable-table">
                             <table class="table table-sortable">
                                 <thead>
                                     <tr>
                                         <th>Date</th>
                                         <th>Time</th>
                                         <th>Service</th>
                                         <th>Status</th>
                                     </tr>
                                 </thead>
                                 <tbody id="appointmentListTableBody">
                                     
                                 </tbody>
                         
                             </table>
                         </div> 
                         </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <div id="addModal">
        <div class="modal">
            <div class="form-header">
                <div id="add-close-modal">
                    X
                </div>
            </div>
            <div class="form-content">
                <h2>Book Appointment</h2>
                <form id="addForm" method="POST">
                    @csrf
                    <div class="form-control">
                        <ul id="add-errors" class="text-danger">

                        </ul>
                    </div>
                    <input type="hidden" name="id" id="user-appointment-id" value="">
                    <div class="form-control">
                        <input name="date" id="date" type="date" min="" required>
                    </div>
                    <div class="form-control">
                        <select name="preference" id="preference" required>
                        <option value="">Select Schedule Preference</option>
                        <option value="Morning">Morning</option>
                        <option value="Afternoon">Afternoon</option>
                    </select>
                    </div>
                    <div class="form-control">
                        <select name="service" id="service" required>
                        <option value="">Select Service</option>
                        <option value="Consultation">Consultation</option>
                        <option value="Tooth Extraction">Tooth Extraction</option>
                        <option value="Orthodontic Treatment">Orthodontic Treatment</option>
                    </select>
                    </div>
                    <div class="form-control">
                        <textarea name="remarks" id="remarks" rows="10" placeholder="Remarks"></textarea>
                    </div>
                        <div class="form-control">
                            <button type="submit" class="submit-btn">Confirm</button>
                        </div>
                    </form>
            </div>
        </div>
    </div>

    <div id="cancelModal">
        <div class="modal">
            <div class="form-header">
                <div id="cancel-close-modal">
                    X
                </div>
            </div>
            <div class="form-content">
                <form id="cancelForm" method="POST">
                    @csrf
                    <input type="hidden" name="id" id="cancel-appointment-id" value="">
                    <div class="form-control">
                       <p>Are you sure you want to cancel your appointment?</p>
                    </div>
                        <div class="form-control">
                            <button type="submit" class="submit-btn">Confirm</button>
                        </div>
                    </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/axios/1.7.7/axios.min.js" integrity="sha512-DdX/YwF5e41Ok+AI81HI8f5/5UsoxCVT9GKYZRIzpLxb8Twz4ZwPPX+jQMwMhNQ9b5+zDEefc+dcvQoPWGNZ3g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script>
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

      function fetchCurrentAppointment() {
    axios.post('appointment/fetch')
        .then(response => {
            const appointments = response.data;
            const progressElement = document.querySelector('.circular-progress');
            const innerCircle = progressElement.querySelector('.inner-circle');

            // Remove existing status classes
            progressElement.classList.remove('Accepted', 'Pending', 'None');

            if (Array.isArray(appointments) && appointments.length > 0) {
                const now = new Date();
                let hoursDifference; // Declare hoursDifference here

                const latestAppointment = appointments.find(appointment => {
                    // Combine appointment_date and appointment_time
                    const appointmentDateTime = new Date(appointment.appointment_date + 'T' + appointment.appointment_time);
                    
                    // Convert appointmentDateTime to local timezone (assuming 'Asia/Manila')
                    const options = { timeZone: 'Asia/Manila' };
                    const appointmentLocalTime = new Date(appointmentDateTime.toLocaleString('en-US'));

                    // Calculate time difference in milliseconds
                    const timeDifference = appointmentLocalTime - now;
                    hoursDifference = timeDifference / (1000 * 60 * 60); // Assign here

                    return appointmentLocalTime >= now && ['Pending', 'Accepted'].includes(appointment.status);
                });

                if (latestAppointment) {
                    const appointmentDateTime = new Date(latestAppointment.appointment_date + 'T' + latestAppointment.appointment_time);
                    const appointmentLocalTime = new Date(appointmentDateTime.toLocaleString('en-US', { timeZone: 'Asia/Manila' }));

                    let progressColor;
                    if (latestAppointment.status === 'Accepted') {
                        progressElement.classList.add('Accepted');
                        progressColor = 'lightgreen';
                        document.getElementById('statusText').textContent = 'Confirmed';
                        if (hoursDifference < 24) {
                            document.getElementById('cancelButton').disabled = true;
                            document.getElementById('cancelButton').style.backgroundColor = 'grey';
                        }
                    } else if (latestAppointment.status === 'Pending') {
                        progressElement.classList.add('Pending');
                        progressColor = '#FFD700';
                        document.getElementById('statusText').textContent = 'Pending';
                    }

                    progressElement.setAttribute('data-progress-color', progressColor);
                    innerCircle.style.backgroundColor = progressElement.getAttribute('data-inner-circle-color');
                    updateProgress(progressElement, progressColor);

                    // Fill appointment information
                    document.getElementById('dateText').textContent = appointmentLocalTime.toLocaleDateString('en-US', { timeZone: 'Asia/Manila' }); 
                    if (latestAppointment.appointment_time === '00:00:00') {
                        document.getElementById('timeText').textContent = 'None';
                    } else {
                        document.getElementById('timeText').textContent = appointmentLocalTime.toLocaleTimeString('en-US', { timeZone: 'Asia/Manila', hour: 'numeric', minute: 'numeric' });
                    }
                    document.getElementById('serviceText').textContent = latestAppointment.service;

                    document.getElementById('bookButton').style.display = 'none';
                    document.getElementById('cancelButton').style.display = 'block';
                } else {
                    progressElement.classList.add('None');
                    progressElement.setAttribute('data-progress-color', 'lightgrey');
                    innerCircle.style.backgroundColor = progressElement.getAttribute('data-inner-circle-color');
                    updateProgress(progressElement, 'lightgrey');
                    document.getElementById('dateText').textContent = 'None'; 
                    document.getElementById('timeText').textContent = 'None'; 
                    document.getElementById('serviceText').textContent = 'None';
                    document.getElementById('statusText').textContent = 'None';
                    document.getElementById('bookButton').style.display = 'block';
                    document.getElementById('cancelButton').style.display = 'none';
                }
            } else {
                console.warn('No appointments fetched or invalid data structure:', appointments);
                progressElement.classList.add('None'); 
                progressElement.setAttribute('data-progress-color', 'lightgrey');
                innerCircle.style.backgroundColor = progressElement.getAttribute('data-inner-circle-color');
                updateProgress(progressElement, 'lightgrey');
                document.getElementById('dateText').textContent = 'None'; 
                document.getElementById('timeText').textContent = 'None'; 
                document.getElementById('serviceText').textContent = 'None';
                document.getElementById('statusText').textContent = 'None';
                document.getElementById('bookButton').style.display = 'block';
                document.getElementById('cancelButton').style.display = 'none';
            }
        })
        .catch(error => {
            console.error('Error fetching appointments:', error);
        });
}

function fetchAppointmentList() {
        axios.post('appointment/populate')
            .then(response => {
                const appointments = response.data;
                const tableBody = document.getElementById('appointmentListTableBody');
                tableBody.innerHTML = '';

                if (appointments.length === 0) {
                    const noAppointmentsRow = `
                        <tr>
                            <td colspan="4">No Appointments Found</td>
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
                            <td>${appointmentDate}</td>
                            <td>${appointmentTime}</td>
                            <td>${appointment.service}</td>
                            <td>${appointment.status}</td>
                        </tr>
                    `;
                    tableBody.innerHTML += row;
                });
            })
            .catch(error => {
                console.error('There was an error fetching the appointments!', error);
            });
    }


        // Function to dynamically update progress
        function updateProgress(progressElement, progressColor) {
            let startValue = 0;
            const endValue = 100;
            const speed = 1; // Speed of progress animation
    
            const intervalId = setInterval(() => {
                startValue++;
                progressElement.style.background = `conic-gradient(${progressColor} ${
                    startValue * 3.6
                }deg, ${progressElement.getAttribute('data-bg-color')} 0deg)`;
    
                if (startValue === endValue) {
                    clearInterval(intervalId);
                }
            }, speed);
        }
    
        $(document).ready(() => {
            fetchCurrentAppointment();
            fetchAppointmentList();

            $(document).on('click', '#bookButton', function () {
                $('#add-errors').empty().hide();

                $.ajax({
                    url: '/user/fetch/id',
                    type: 'GET',
                    dataType: 'json',
                    success: function (response) {
                        $('#user-appointment-id').val(response.id); 
                        $('#addModal').fadeIn().css('display', 'flex');
                    },
                    error: function (error) {
                        console.error('Error fetching user data:', error);
                    }
                });
            });

            $(document).on('click', '#cancelButton', function () {
                
                $('#add-errors').empty().hide();

                $.ajax({
                    url: '/user/fetch/id',
                    type: 'GET',
                    dataType: 'json',
                    success: function (response) {
                        $('#cancel-appointment-id').val(response.id); 
                        $('#cancelModal').fadeIn().css('display', 'flex');
                    },
                    error: function (error) {
                        console.error('Error fetching user data:', error);
                    }
                });
            });

            $('#addForm').on('submit', function (e) {
            e.preventDefault(); 

            $('#add-errors').empty().hide();

            const formData = $(this).serialize();
            $.ajax({
                url: '{{ route("book.appointment") }}',
                type: 'POST',
                data: $(this).serialize(),
                success: function (response) {
                    $('#addModal').fadeOut();
                    alert('Appointment Booked successfully!');
                    fetchCurrentAppointment();
                    fetchAppointmentList();
                },
                error: function (xhr) {
                    console.error('Error Response:', xhr);
                    $('#add-errors').html('<li>' + (xhr.responseJSON?.message || 'An error occurred.') + '</li>').show();
                }
            });

    });

    $('#cancelForm').on('submit', function (e) {
        e.preventDefault(); 
        const formData = $(this).serialize();
        $.ajax({
            url: '{{ route("cancel.appointment") }}',
            type: 'POST',
            data: $(this).serialize(),
            success: function (response) {
                $('#cancelModal').fadeOut();
                alert('Appointment Cancelled successfully!');
                fetchCurrentAppointment();
                fetchAppointmentList();
            },
            error: function (xhr) {
                console.error('Error Response:', xhr);
            }
        });
    });

    $('#add-close-modal').click(() => $('#addModal').fadeOut());
    $('#cancel-close-modal').click(() => $('#cancelModal').fadeOut());
    const dateInput = document.getElementById('date');
        const today = new Date();
        const tomorrow = new Date(today);
        tomorrow.setDate(today.getDate() + 1); // Add 1 day
        const formattedTomorrow = tomorrow.toISOString().split('T')[0]; // Format YYYY-MM-DD
        dateInput.setAttribute('min', formattedTomorrow);
        });
    </script>
</body>
</html>