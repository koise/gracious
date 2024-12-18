import axios from 'axios';

$(document).ready(function () {
    function clearErrors() {
        $('.text-danger').remove();
    }

    const $numberInput = $('#number');
    const $otpNumberInput = $('#otpNumber');
    const $generateBtn = $('#generateBtn');
    let countdown;

    // Sync the number input value with the hidden OTP number input
    $numberInput.on('input', function () {
        $otpNumberInput.val($numberInput.val());
    });

    // Restore timer state on page load
    const timerEnd = localStorage.getItem('otpTimerEnd');
    if (timerEnd) {
        const remainingTime = Math.max(0, Math.floor((timerEnd - Date.now()) / 1000));
        if (remainingTime > 0) {
            startTimer(remainingTime, $generateBtn);
        } else {
            localStorage.removeItem('otpTimerEnd');
        }
    }

    // Handle OTP form submission
    $('#otpForm').on('submit', function (e) {
        e.preventDefault();
        clearErrors();

        const formData = new FormData(this);
        axios.post('/account/verification/send-otp', formData)
            .then(function (response) {
                const duration = 60; // Timer duration in seconds
                const timerEnd = Date.now() + duration * 1000;
                localStorage.setItem('otpTimerEnd', timerEnd);
                startTimer(duration, $generateBtn);
                alert('OTP sent successfully!');
            })
            .catch(function (error) {
                if (error.response && error.response.data.errors) {
                    const errors = error.response.data.errors;
                    let errorMessages = '<ul class="text-danger">';
                    for (const key in errors) {
                        errorMessages += `<li>${errors[key][0]}</li>`;
                    }
                    errorMessages += '</ul>';
                    $('#otpForm').prepend(errorMessages);
                }
            });
    });

    // Handle OTP verification form submission
    $('#verificationOTP').on('submit', function (e) {
        e.preventDefault();
        clearErrors();

        const formData = new FormData(this);
        formData.append('number', $otpNumberInput.val());

        axios.post('/account/verification/process', formData)
            .then(function (response) {
                alert('Verification Successful');
                localStorage.removeItem('otpTimerEnd'); // Clear timer when OTP is successfully verified
                window.location.href = '/account/login';
            })
            .catch(function (error) {
                if (error.response && error.response.data.errors) {
                    const errors = error.response.data.errors;
                    let errorMessages = '<ul class="text-danger">';
                    for (const key in errors) {
                        errorMessages += `<li>${errors[key][0]}</li>`;
                    }
                    errorMessages += '</ul>';
                    $('#otpForm').prepend(errorMessages);
                }
            });
    });

    // Timer function for the generate button
    function startTimer(duration, $button) {
        let timer = duration, seconds;
        clearInterval(countdown);

        countdown = setInterval(function () {
            seconds = parseInt(timer % 60, 10);
            seconds = seconds < 10 ? '0' + seconds : seconds;

            $button.text(`Generate OTP (${seconds}s)`).prop('disabled', true);

            if (--timer < 0) {
                clearInterval(countdown);
                localStorage.removeItem('otpTimerEnd'); // Clear timer state when it ends
                $button.text('Generate OTP').prop('disabled', false);
            }
        }, 1000);
    }
});
