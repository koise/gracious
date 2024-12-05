<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Dashboard</title>
    @vite([
    // Partials
    'resources/scss/sidebar.scss', 
    'resources/scss/footer.scss', 
    'resources/js/sidebar.js', 

    // Dashboard
    'resources/scss/admin/admindashboard.scss', 
    'resources/js/admin/dashboard.js'])
</head>
<body>
    <main>
        <div class="wrapper">
            <div class="container">
                @include('/partials/sidebar')
                <div class="content">
                    <div class="content-header">
                        <div class="content-header-heading">
                            <h1>Admin | <span>Dashboard</span></h1>
                        </div>
                        <div class="content-header-body">
                            <div class="total">
                                <div class="total-header">
                                    <h2 id="appointmentsToday"></h2>
                                    <p>Appointments Today</p>
                                </div>
                                <div class="total-body">
                                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e8eaed"><path d="M360-320q-17 0-28.5-11.5T320-360v-80q17 0 28.5-11.5T360-480q0-17-11.5-28.5T320-520v-80q0-17 11.5-28.5T360-640h240q17 0 28.5 11.5T640-600v80q-17 0-28.5 11.5T600-480q0 17 11.5 28.5T640-440v80q0 17-11.5 28.5T600-320H360Zm120-60q8 0 14-6t6-14q0-8-6-14t-14-6q-8 0-14 6t-6 14q0 8 6 14t14 6Zm0-80q8 0 14-6t6-14q0-8-6-14t-14-6q-8 0-14 6t-6 14q0 8 6 14t14 6Zm0-80q8 0 14-6t6-14q0-8-6-14t-14-6q-8 0-14 6t-6 14q0 8 6 14t14 6ZM280-40q-33 0-56.5-23.5T200-120v-720q0-33 23.5-56.5T280-920h400q33 0 56.5 23.5T760-840v720q0 33-23.5 56.5T680-40H280Zm0-120v40h400v-40H280Zm0-80h400v-480H280v480Zm0-560h400v-40H280v40Zm0 0v-40 40Zm0 640v40-40Z"/></svg>
                                </div>
                            </div>
                            <div class="total">
                                <div class="total-header">
                                    <h2 id="totalUsers"></h2>
                                    <p>Total Patient</p>
                                </div>
                                <div class="total-body">
                                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e8eaed"><path d="M40-160v-112q0-34 17.5-62.5T104-378q62-31 126-46.5T360-440q66 0 130 15.5T616-378q29 15 46.5 43.5T680-272v112H40Zm720 0v-120q0-44-24.5-84.5T666-434q51 6 96 20.5t84 35.5q36 20 55 44.5t19 53.5v120H760ZM360-480q-66 0-113-47t-47-113q0-66 47-113t113-47q66 0 113 47t47 113q0 66-47 113t-113 47Zm400-160q0 66-47 113t-113 47q-11 0-28-2.5t-28-5.5q27-32 41.5-71t14.5-81q0-42-14.5-81T544-792q14-5 28-6.5t28-1.5q66 0 113 47t47 113ZM120-240h480v-32q0-11-5.5-20T580-306q-54-27-109-40.5T360-360q-56 0-111 13.5T140-306q-9 5-14.5 14t-5.5 20v32Zm240-320q33 0 56.5-23.5T440-640q0-33-23.5-56.5T360-720q-33 0-56.5 23.5T280-640q0 33 23.5 56.5T360-560Zm0 320Zm0-400Z"/></svg>
                                </div>
                            </div>
                            <div class="total">
                                <div class="total-header">
                                    <h2 id="totalDoctors"></h2>
                                    <p>Total Doctor</p>
                                </div>
                                <div class="total-body">
                                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e8eaed"><path d="M480-480q-66 0-113-47t-47-113q0-66 47-113t113-47q66 0 113 47t47 113q0 66-47 113t-113 47ZM160-160v-112q0-34 17.5-62.5T224-378q62-31 126-46.5T480-440q66 0 130 15.5T736-378q29 15 46.5 43.5T800-272v112H160Zm80-80h480v-32q0-11-5.5-20T700-306q-54-27-109-40.5T480-360q-56 0-111 13.5T260-306q-9 5-14.5 14t-5.5 20v32Zm240-320q33 0 56.5-23.5T560-640q0-33-23.5-56.5T480-720q-33 0-56.5 23.5T400-640q0 33 23.5 56.5T480-560Zm0-80Zm0 400Z"/></svg>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="content-body">
                        <div class="graphbox">
                            <div class="box">
                                <h1>Appoinment Status</h1>
                                <canvas id="pieChart">
        
                                </canvas>
                            </div>
                            <div class="box">
                                <canvas id="lineChart">
        
                                </canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.min.js" integrity="sha512-L0Shl7nXXzIlBSUUPpxrokqq4ojqgZFQczTYlGjzONGTDAcLremjwaWv5A+EDLnxhQzY5xUZPWLOLqYRkY0Cbw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
</body>
</html>