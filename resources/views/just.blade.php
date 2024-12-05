<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
        rel="stylesheet">

    <title>Home</title>
    
    @vite([ 
    'resources/scss/just.scss',
    'resources/scss/footer.scss',
    'resources/js/index.js'
    ])
</head>
<body>
    <main>
        <div class="container">
            <div class="area" id="trigger-point">
                <nav id="nav">
                    <div class="navigation">
                        <div class="logo-container">
                            <img src="{{ asset('images/index/logo.png') }}" alt="logo" width="100">
                        </div>
                        <div class="navigation-content">
                            <ul>
                                <li><a href="#sectionOne">Home</a></li>
                                <li><a href="#sectionTwo">Feature</a></li>
                                <li><a href="#sectionThree">About us</a></li>
                                <li><a href="#sectionFour">FAQs</a></li>
                                <li><span><a href=" {{ route('user.login')}}">Login</a></span></li>
                                <li><span><a href=" {{ route('user.register')}}">Register</a></span></li>
                            </ul>
                        </div>
                    </div>
                </nav>
                <div class="content">
                    <div class="background"></div>
                    <h2><span class="typing"></span></h2>
                    <p>We offer a wide range of services for
                        your every dental need.</p>
                    <a>Book Now!</a>
                </div>
            </div>
            <div class="area" id="sectionTwo">
                <div class="background"></div>
                <div class="content">
                    <div class="feature-container">
                        <div class="feature-content">
                            <h2>Gracious Clinic Features</h2>
                            <p>Designed with ease of use in mind, you will be up and running with Gracious Clinic in a few minutes! We have included the features that you need most:</p>
                        </div>
                        <div class="cards">
                            <div class="card" id="feature-1">
                                <h2>Appointment</h2>
                                <p>Schedule and manage appointments seamlessly with our easy-to-use booking system. Stay organized with automated reminders.</p>
                            </div>
                            <div class="card" id="feature-2">
                                <h2>View Records</h2>
                                <p>Access patient records anytime, anywhere. From medical history to treatment details.</p>
                            </div>
                            <div class="card" id="feature-3">
                                <h2>Notification</h2>
                                <p>Stay informed with real-time notifications. Receive updates on appointment confirmations, reminders, and important clinic announcements.</p>
                            </div>
                            <div class="card" id="feature-4">
                                <h2>Payment</h2>
                                <p>Manage overdue payments effortlessly. Keep track of outstanding balances and streamline payment processes with integrated payment solutions.</p>
                            </div>
                            <div class="card" id="feature-5">
                                <h2>Transaction</h2>
                                <p>Track financial transactions with ease. Monitor payments, invoices, and receipts in one centralized system.</p>
                            </div>
                            <button id="prev">←</button>
                            <button id="next">→</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="area" id="sectionThree">
                <div class="background"></div>
                <div class="content">
                    <div class="left-content">
                        <h2>ABOUT US</h2>
                        <p>At Gracious Clinic, we are dedicated to providing exceptional dental care with a focus on patient comfort and satisfaction. Our mission is to help you achieve and maintain a healthy, beautiful smile in a welcoming and relaxed environment.</p>
                        <p>With a team of skilled and compassionate dental professionals, we offer a wide range of services, from routine check-ups and cleanings to advanced dental treatments. We believe in personalized care, which means we take the time to understand your unique needs and tailor our services to meet them. Whether you're here for preventive care, restorative treatments, or cosmetic enhancements, we are committed to delivering the highest standards of dentistry.</p>
                    </div>
                    <div class="right-content">
                        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3860.5632822854127!2d121.11292837590445!3d14.623936576503281!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3397b8518ca56f4d%3A0x8abca99187cc673e!2sVermont%20Park%20Executive%20Village!5e0!3m2!1sen!2sph!4v1728939033375!5m2!1sen!2sph" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                        
                    </div>
                </div>
            </div>
            <div class="area" id="sectionFour">
                <div class="background"></div>
                <div class="content">
                    <h2>Frequently Asked Questions</h2>
                    <div class="faq-container">
                        
                        <div class="faq-item">
                          <div class="faq-question">
                            How to use?
                            <span class="arrow">▶</span>
                          </div>
                          <div class="faq-answer">
                            Sample
                          </div>
                        </div>
                        <div class="faq-item">
                          <div class="faq-question">
                            Terms and Conditions
                            <span class="arrow">▶</span>
                          </div>
                          <div class="faq-answer">
                            Sample
                        </div>
                        <div class="faq-item">
                          <div class="faq-question">
                            Is Gracious Clinic App free to use?
                            <span class="arrow">▶</span>
                          </div>
                          <div class="faq-answer">
                            Sample
                          </div>
                        </div>
                      </div>
                </div>
            </div>
            <div class="area">
                @include('partials.footer')
            </div>
        </div>
    </main>

    <a href="#" class="to-top">
        <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e8eaed"><path d="m296-224-56-56 240-240 240 240-56 56-184-183-184 183Zm0-240-56-56 240-240 240 240-56 56-184-183-184 183Z"/></svg>
    </a>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/typed.js/2.1.0/typed.umd.js"
        integrity="sha512-+2pW8xXU/rNr7VS+H62aqapfRpqFwnSQh9ap6THjsm41AxgA0MhFRtfrABS+Lx2KHJn82UOrnBKhjZOXpom2LQ=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>
</body>
</html>