
import axios from 'axios';

document.addEventListener('DOMContentLoaded', function() {
    const numberInput = document.getElementById('number');
    const otpNumberInput = document.getElementById('otpNumber');
    const generateBtn = document.getElementById('generateBtn');
    let countdown;

    numberInput.addEventListener('input', function() {
        otpNumberInput.value = numberInput.value;
    });

    $('#otpForm').on('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        axios.post('/account/verification/send-otp', formData)
        .then(function(response) {
            if (response.data.status === 'ok') {
                startTimer(60, generateBtn);
                alert('OTP sent successfully!');
            } else {
                alert(response.data.message || 'Failed to generate OTP. Please try again.');
            }
        })
        .catch(function(error) {
            alert(error.response.data.message || 'Failed to generate OTP. Please try again.');
        });
    })

    $('#verificationOTP').on('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('number', otpNumberInput.value);
        axios.post('/account/verification/process', formData)
            .then((response) => {
                alert(response.data.message);
                window.location.href = "/account/login";
            })
            .catch((error) => {
                let errorMessage;
                if (error.response && error.response.data && error.response.data.message) {
                    errorMessage = error.response.data.message;
                }
                alert(errorMessage);
            });
    });
    
    function startTimer(duration, button) {
        let timer = duration, seconds;
        clearInterval(countdown);

        countdown = setInterval(function () {
            seconds = parseInt(timer % 60, 10);
            seconds = seconds < 10 ? "0" + seconds : seconds;

            button.textContent = 'Generate OTP (' + seconds + 's)';
            button.disabled = true;

            if (--timer < 0) {
                clearInterval(countdown);
                button.textContent = 'Generate OTP';
                button.disabled = false;
            }
        }, 1000);
    }
});