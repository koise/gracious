<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Gracious Smile - Login</title>
    @vite(['resources/scss/header.scss', 'resources/scss/footer.scss', 'resources/scss/user/userregister.scss', 'resources/js/user/register.js'])
</head>

<body>
    @include('partials.header')
    <main>
        <div class="container">
            <div class="form">
                <div class="form-heading">
                    <h1>CREATE ACCOUNT</h1>
                </div>
                <div class="form-body">
                    <form id="registerForm" method="POST">
                        @csrf
                        <div class="form-control">
                            <h2>User Information</h2>
                        </div>
                        <div class="form-control">
                            <input type="text" id="first_name" name="first_name" value="{{ old('first_name') }}"
                                placeholder="First Name" required>
                        </div>
                        <div class="form-control">
                            <input type="text" id="last_name" name="last_name" value="{{ old('last_name') }}"
                                placeholder="Last Name" required>
                        </div>
                        <div class="form-control">
                            <div class="age">
                                <input type="number" id="age" name="age" value="{{ old('age') }}"
                                    placeholder="Age" required>
                            </div>
                            <div class="phone-number">
                                <span class="input-group-addon">+63</span>
                                <input type="text" id="number" name="number" value="{{ old('number') }}"
                                    placeholder="Number" required>
                            </div>
                        </div>
                        <div class="form-control">
                            <input type="text" id="street_address" name="street_address"
                                value="{{ old('street_address') }}" placeholder="Street Address" required>
                        </div>
                        <div class="form-control">
                            <select name="province" id="province" required>
                                <option value="">Select Province</option>
                                @foreach (App\Models\Province::orderBy('name')->get() as $province)
                                    <option value="{{ $province->id }}"
                                        {{ old('province') == $province->id ? 'selected' : '' }}>{{ $province->name }}
                                    </option>
                                @endforeach
                            </select>
                            <select name="city" id="city" required>
                                <option value="">Select City</option>
                            </select>
                            <select name="country" id="country" required>
                                <option value="Philippines" default>Philippines</option>
                            </select>
                        </div>
                        <div class="form-control">
                            <h2>Login Details</h2>
                        </div>
                        <div class="form-control">
                            <input type="text" id="username" name="username" value="{{ old('username') }}"
                                placeholder="Username" autocomplete="username" required>
                        </div>
                        <div class="form-control">
                            <div class="password">
                                <div class="password-toggle">
                                    <input type="password" id="password" name="password" placeholder="Password"
                                        autocomplete="new-password" required>
                                    <span toggle="#password" class="eye-toggle">
                                        <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960"
                                            width="24px" fill="#e8eaed">
                                            <path
                                                d="M480-320q75 0 127.5-52.5T660-500q0-75-52.5-127.5T480-680q-75 0-127.5 52.5T300-500q0 75 52.5 127.5T480-320Zm0-72q-45 0-76.5-31.5T372-500q0-45 31.5-76.5T480-608q45 0 76.5 31.5T588-500q0 45-31.5 76.5T480-392Zm0 192q-146 0-266-81.5T40-500q54-137 174-218.5T480-800q146 0 266 81.5T920-500q-54 137-174 218.5T480-200Zm0-300Zm0 220q113 0 207.5-59.5T832-500q-50-101-144.5-160.5T480-720q-113 0-207.5 59.5T128-500q50 101 144.5 160.5T480-280Z" />
                                        </svg>
                                        <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960"
                                            width="24px" fill="#e8eaed" style="display: none;">
                                            <path
                                                d="m644-428-58-58q9-47-27-88t-93-32l-58-58q17-8 34.5-12t37.5-4q75 0 127.5 52.5T660-500q0 20-4 37.5T644-428Zm128 126-58-56q38-29 67.5-63.5T832-500q-50-101-143.5-160.5T480-720q-29 0-57 4t-55 12l-62-62q41-17 84-25.5t90-8.5q151 0 269 83.5T920-500q-23 59-60.5 109.5T772-302Zm20 246L624-222q-35 11-70.5 16.5T480-200q-151 0-269-83.5T40-500q21-53 53-98.5t73-81.5L56-792l56-56 736 736-56 56ZM222-624q-29 26-53 57t-41 67q50 101 143.5 160.5T480-280q20 0 39-2.5t39-5.5l-36-38q-11 3-21 4.5t-21 1.5q-75 0-127.5-52.5T300-500q0-11 1.5-21t4.5-21l-84-82Zm319 93Zm-151 75Z" />
                                        </svg>
                                    </span>
                                </div>
                                <div id="password-validation-message"></div>
                            </div>

                        </div>
                        <div class="form-control">
                            <div class="password">
                                <div class="password-toggle">
                                    <input type="password" id="confirm_password" name="confirm_password"
                                        placeholder="Confirm Password" autocomplete="new-password" required>
                                    <span toggle="#confirm_password" class="eye-toggle">
                                        <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960"
                                            width="24px" fill="#e8eaed">
                                            <path
                                                d="M480-320q75 0 127.5-52.5T660-500q0-75-52.5-127.5T480-680q-75 0-127.5 52.5T300-500q0 75 52.5 127.5T480-320Zm0-72q-45 0-76.5-31.5T372-500q0-45 31.5-76.5T480-608q45 0 76.5 31.5T588-500q0 45-31.5 76.5T480-392Zm0 192q-146 0-266-81.5T40-500q54-137 174-218.5T480-800q146 0 266 81.5T920-500q-54 137-174 218.5T480-200Zm0-300Zm0 220q113 0 207.5-59.5T832-500q-50-101-144.5-160.5T480-720q-113 0-207.5 59.5T128-500q50 101 144.5 160.5T480-280Z" />
                                        </svg>
                                        <svg xmlns="http://www.w3.org/2000/svg" height="24px"
                                            viewBox="0 -960 960 960" width="24px" fill="#e8eaed"
                                            style="display: none;">
                                            <path
                                                d="m644-428-58-58q9-47-27-88t-93-32l-58-58q17-8 34.5-12t37.5-4q75 0 127.5 52.5T660-500q0 20-4 37.5T644-428Zm128 126-58-56q38-29 67.5-63.5T832-500q-50-101-143.5-160.5T480-720q-29 0-57 4t-55 12l-62-62q41-17 84-25.5t90-8.5q151 0 269 83.5T920-500q-23 59-60.5 109.5T772-302Zm20 246L624-222q-35 11-70.5 16.5T480-200q-151 0-269-83.5T40-500q21-53 53-98.5t73-81.5L56-792l56-56 736 736-56 56ZM222-624q-29 26-53 57t-41 67q50 101 143.5 160.5T480-280q20 0 39-2.5t39-5.5l-36-38q-11 3-21 4.5t-21 1.5q-75 0-127.5-52.5T300-500q0-11 1.5-21t4.5-21l-84-82Zm319 93Zm-151 75Z" />
                                        </svg>
                                    </span>
                                </div>
                                <div id="confirm-password-validation-message"></div>
                            </div>
                        </div>
                        <div class="form-control">
                            <div class="terms-condition">
                                <input type="checkbox" id="terms" name="terms" required>
                                <label for="termsLabel" id="termsLabel">Terms and Conditions</label>
                            </div>
                            
                        </div>
                        <div class="form-control">
                            <button type="submit" id="submitbtn">Submit</button>
                        </div>
                        <div class="form-control">
                            <span>Already have an account?</span><a href="{{ route('user.login') }}"> Sign In</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
    <div id="termsModal" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <h2>Terms and Conditions</h2>
            <pre>
Welcome to the Gracious Smile Dental Clinic online platform. By accessing or using our web-based and mobile application system, you agree to be bound by these Terms and Conditions. Please read them carefully before using the system.
1. Use of the System
1.1 Eligibility: This system is intended for use by patients and staff of Gracious Smile Dental Clinic. You must provide accurate and up-to-date information when booking appointments or accessing your patient records.
1.2 Access: The system requires an active internet connection and a valid phone number to receive SMS notifications. Users are responsible for maintaining the confidentiality of their login credentials.
1.3 Purpose: The system is designed for appointment scheduling, patient record management, and payment tracking. Unauthorized use for fraudulent activities, such as creating fake appointments, is strictly prohibited.

2. Appointment Scheduling
2.1 Booking and Cancellations: Patients can book, modify, or cancel appointments online. All changes must comply with the clinic’s cancellation policies.
2.2 Notifications: Automated SMS reminders will be sent for upcoming appointments, cancellations, or emergencies. Ensure that your contact information is accurate to receive timely notifications.
2.3 Double Bookings: The system prevents double bookings. However, users must confirm their scheduled time slots to avoid conflicts.

3. Patient Records Management
3.1 Data Accuracy: Patients must ensure the accuracy of their personal and medical information. Inaccurate data may affect the quality of care provided.
3.2 Data Access: Patient records, including medical history and billing information, are securely stored. Access is limited to authorized personnel and the account owner.
3.3 Confidentiality: All records are managed following applicable data privacy laws. Unauthorized access, sharing, or tampering with records is prohibited.

4. Payments and Billing
4.1 Transaction Records: The system tracks payment history, including completed, pending, and overdue balances. Patients must settle unpaid balances promptly.
4.2 Disputes: Any disputes regarding payments must be communicated to the clinic for resolution.

5. System Limitations
5.1 Internet Dependency: The system requires an active internet connection. The clinic is not responsible for issues arising from internet outages or technical failures on the user's end.
5.2 SMS Notification Accuracy: Notifications depend on the accuracy of the phone number provided and the recipient's device capability to receive messages.
5.3 Fraudulent Use: The clinic reserves the right to suspend accounts involved in fraudulent activities, including false appointments.

6. User Responsibilities
6.1 Compliance: Users must comply with all local laws and regulations while using the system.
6.2 Technical Issues: Users must report any technical issues promptly for resolution.
6.3 Respect for Policies: Misuse of the system or violation of these terms may result in account suspension or termination.

7. Disclaimer and Liability
7.1 System Reliability: While we strive to maintain the system’s reliability, occasional technical issues may occur. Gracious Smile Dental Clinic is not liable for any losses resulting from such issues.
7.2 Third-Party Services: The clinic is not responsible for the performance of third-party services, such as SMS delivery networks.

8. Modifications to Terms
The clinic reserves the right to update these Terms and Conditions as necessary. Users will be notified of significant changes through the system or via email. Continued use of the system after updates indicates acceptance of the revised terms.

9. Contact Information
For questions or concerns about these Terms and Conditions, please contact Gracious Smile Dental Clinic at 0998 565 3823.
By using the Gracious Smile Dental Clinic’s web and mobile application, you acknowledge that you have read, understood, and agreed to these Terms and Conditions.</pre>
            <button id="confirmButton" class="confirm-button">Confirm</button>
        </div>
    </div>

    @include('partials.footer')
</body>

</html>
