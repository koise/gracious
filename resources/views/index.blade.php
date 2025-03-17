<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lato:ital,wght@0,100;0,300;0,400;0,700;0,900;1,100;1,300;1,400;1,700;1,900&display=swap" rel="stylesheet">
    <script src="https://kit.fontawesome.com/17dbd0ab3f.js" crossorigin="anonymous"></script>
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    @vite(['resources/scss/index.scss',])
    <title>Gracious Clinic</title>
    @laravelPWA
</head>
    <!-- GO UP ICON -->
     <a href="" class = "goUpIcon"><img src="images/GoUpIcon.png" alt=""></a>
    <!-- HEADER -->
    <header>
        <nav>
            <ul>
                <img src="{{ asset('images/index/logo.png') }}" alt="logo" width="100">
                <h3>Gracious Smile</h3>
            </ul>
            <ul>
                <li><a href="#">Home</a></li>
                <li><a href="#features">Feature</a></li>
                <li><a href="#aboutus">About us</a></li>
                <li><a href="#faqs">FAQs</a></li>
                <li><span class = "nav1"><a href=" {{ route('user.login')}}">Login <i class="fa-solid fa-user"></i></a></span></li>
            </ul>
        </nav>
    </header>
    <!-- HOME PAGE -->
    <div class="homeContainer">
        <div class="homeLeft-side">
            <h2>WELCOME TO</h2>
            <h2>GRACIOUS CLINIC.</h2>
            <p>We want to help you manage your patient information. Instead of using index card,
            we have created an app that yo can use on different device.</p>
            <div class="homeBtnLeft-side">
                <a href=""><img src="{{ asset('images/index/AppStoreIcon.svg') }}" alt=""></a>
                <a href=""><img src="{{ asset('images/index/PlayStoreIcon.svg') }}" alt=""></a>
            </div>
        </div>
        <div class="homeRight-side">
            <img src="{{ asset('images/index/homebg.svg') }}" alt="">
        </div>
    </div>
    <!-- FEATURES PAGE -->
    <div class="featureContainer" id="features">
        <div class="featureHeader">
            <h3>Gracious Smile Clinic App Features</h3>
            <p>Designed with ease of use in mind, you will be up and running with My Dental Clinic in a few minutes!
               We have included the features that you need most:
            </p>
        </div>
        <div class="featureContent">
            <img src="{{ asset('images/index/Container.svg') }}" alt="">
            <div class="featureLists">
                <ul class="featureList">
                    <li>
                        <img src="{{ asset('images/index/give-money.svg fill.png') }}" alt="">
                        <div class="featureText">
                            <h3>PAYMENT TRANSACTION</h3>
                            <p>Keep track of your patient's payments</p>
                        </div>
                    </li>
                    <li>
                        <img src="{{ asset('images/index/calendar-with-a-clock-time-tools.svg fill.png') }}" alt="">
                        <div class="featureText">
                            <h3>APPOINTMENTS</h3>
                            <p>Keep track of your appointments.</p>
                        </div>
                    </li>
                    <li>
                        <img src="images/pie-chart.svg.png" alt="">
                        <div class="featureText">
                            <h3>CHARTS</h3>
                            <p>Dental chart that is very easy to use.</p>
                        </div>
                    </li>
                </ul>
                <ul class="featureList">
                    <li>
                        <img src="images/clipboard-with-pencil.svg fill.png" alt="">
                        <div class="featureText">
                            <h3>PATIENT INFORMATION</h3>
                            <p>Keep track of your patient information</p>
                        </div>
                    </li>
                    <li>
                        <img src="images/picture.svg fill.png" alt="">
                        <div class="featureText">
                            <h3>ATTACH IMAGE</h3>
                            <p>Attach images to your patient records.</p>
                        </div>
                    </li>
                    <li>
                        <img src="images/speech-bubble.svg.png" alt="">
                        <div class="featureText">
                            <h3>EMAIL & SEND SMS</h3>
                            <p>Emailing and Sending SMS to your patience.</p>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    <!-- ABOUT US -->
    <div class="aboutusContainer" id = "aboutus">
        <div class="aboutusLeft">
                <h3>ABOUT US</h3>
                <p>
                    “At Gracious Smile Dental Clinic, we are dedicated to providing exceptional dental care with a focus on patient comfort and satisfaction. 
                    Our mission is to help you achieve and maintain a healthy, beautiful smile in a welcoming and relaxed environment.”</p>
                <p><br><br>
                    “With a team of skilled and compassionate dental professionals, we offer a wide range of services, from routine check-ups and cleanings to advanced dental treatments. We believe in personalized care, which means we take the time to understand your unique needs and tailor our services to meet them. Whether you're here for preventive care, restorative treatments, or cosmetic enhancements, we are committed to delivering the highest standards of dentistry.”
                </p> 
        </div>
        <div class="aboutusRight">
                <h3>LOCATION</h3>
                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3860.5632822854127!2d121.11292837590445!3d14.623936576503281!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3397b8518ca56f4d%3A0x8abca99187cc673e!2sVermont%20Park%20Executive%20Village!5e0!3m2!1sen!2sph!4v1728939033375!5m2!1sen!2sph" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                <h2><i class="fa-solid fa-location-dot"></i> : 202 Park Avenue, Vermont Park Executive Village, Antipolo, 1800 Rizal</h2>
        </div>
    </div>
    <!-- FAQS -->
    <div class="faqsContainer" id="faqs">
        <h2>Frequently Asked Questions</h2>
        <div class="faqsColumns">
            <ul>
                <li>
                    <a href="javascript:void(0);" class="faq-link">Payment Methods <i class="fa-solid fa-plus"></i><i class="fa-solid fa-minus"></i></a>
                    <div class="faq-content">
                        <p>GCASH PAYMAYA CREDITCARD</p>
                    </div>
                </li>
                <li>
                    <a href="javascript:void(0);" class="faq-link">How to use <i class="fa-solid fa-plus"></i><i class="fa-solid fa-minus"></i></a>
                    <div class="faq-content">
                        <p>TRY NIYO GAMITIN</p>
                    </div>
                </li>
                <li>
                    <a href="javascript:void(0);" class="faq-link">Terms and Condition <i class="fa-solid fa-plus"></i><i class="fa-solid fa-minus"></i></a>
                    <div class="faq-content">
                        <p>MY BODY MY RULES</p>
                    </div>
                </li>
                <li>
                    <a href="javascript:void(0);" class="faq-link">General Info <i class="fa-solid fa-plus"></i><i class="fa-solid fa-minus"></i></a>
                    <div class="faq-content">
                        <p>1+1 = 2</p>
                    </div>
                </li>
            </ul>
        </div>
    </div>
    <!--FOOTER-->
    <footer>
        <p>Copyright © 2024 Gracious Smile by <b>Alpha Copia</b></p>
    </footer>
    <!-- JAVA SCRIPT -->
     <script>
            document.querySelectorAll('.faq-link').forEach(link => {
            link.addEventListener('click', function() {
                const content = this.nextElementSibling;
                const isOpen = content.classList.contains('open');
                const faqActive = document.querySelector('faq-link');

                document.querySelectorAll('.faq-content').forEach(item => {
                    item.classList.remove('open');
                    item.style.maxHeight = null;
                }); 
                if (!isOpen) {
                    content.classList.add('open');
                    content.style.maxHeight = content.scrollHeight + "px";
                }
                document.querySelectorAll('.faq-link').forEach(link => link.classList.remove('active'));
                if (!isOpen) {
                    this.classList.add('active');
                }
            });
          });
          const goUpIcon = document.querySelector('.goUpIcon');
            window.onscroll = function() {
                if (document.body.scrollTop > 100 || document.documentElement.scrollTop > 100) {
                    goUpIcon.style.display = "block";
                } else {
                    goUpIcon.style.display = "none";
                }
            };
            goUpIcon.addEventListener('click', function(event) {
                event.preventDefault();
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
     </script>
    </body>
    </html>