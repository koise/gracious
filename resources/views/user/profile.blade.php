<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile</title>
    @vite([ 
        'resources/scss/user/userappointment.scss', 
        'resources/scss/usersidebar.scss', 
        'resources/scss/modal.scss', 
        'resources/scss/footer.scss', 
        'resources/scss/paymentmodal.scss', 
        'resources/scss/user/profile.scss', 
        'resources/js/user/appointment.js', 
        'resources/js/user/profile.js' 
    ])
</head>
<body>
    @include('partials.topbar')
    <div class="profile-container" style="justify-content:center; margin-top: 20%;">
        <div class="profile-card">
            <div class="profile-header">
                <h2 class="profile-name"></h2>
                <p class="profile-username"></p>
            </div>
            <div class="profile-body">
                <div class="profile-info">
                    <div class="info-item">
                        <strong>Phone Number:</strong> <span id="phoneNum"></span>
                    </div>
                    <div class="info-item">
                        <strong>Age:</strong> <span id="age"></span>
                    </div>
                    <div class="info-item">
                        <strong>Address:</strong> <span id="address"></span>
                    </div>
                    <div class="info-item">
                        <strong>City:</strong> <span id="city"></span>
                    </div>
                    <div class="info-item">
                        <strong>Province:</strong> <span id="province"></span>
                    </div>
                    <div class="info-item">
                        <strong>Status:</strong> <span id="status"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="section">
    <h1>ID:</h1>
    <br>

<!-- Dropzone Container -->
<div class="dropzone" id="dropzone">
    <!-- Placeholder shown when no image is uploaded -->
    <div class="placeholder-text" style="display: block;">Drag & Drop your ID image here or click to upload</div>

    <!-- Preview image shown after upload -->
    <img class="uploaded-image" style="display: none; max-width: 100%; height: auto;" />

    <!-- Hidden file input (used for actual upload) -->
    <input type="file" name="id_image" id="id_image" style="display: none;" accept="image/*" />
</div>

<!-- Upload Button (optional trigger if not using drag/drop only) -->
<button id="uploadBtn" style="display: block;">Upload ID</button>

</body>
</html>
