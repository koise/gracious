<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile | Gracious Dental Clinic</title>
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
    
    <div class="profile-page">
        <!-- Profile Header with Cover Photo -->
        <div class="profile-header">
            <div class="cover-photo">
                <button class="edit-cover-btn" id="editCoverBtn">
                    <i class="edit-icon"></i>
                </button>
            </div>
            <div class="profile-title">
                <h1 class="profile-name"></h1>
                <p class="profile-username"></p>
                <span class="profile-status" id="status"></span>
            </div>
        </div>
        
        <!-- Main Content Container -->
        <div class="profile-content">
            <!-- Left Column -->
            <div class="profile-column">
                <!-- Personal Information Section -->
                <div class="profile-section">
                    <div class="section-header">
                        <h2>Personal Information</h2>
                        <button class="edit-section-btn" data-section="personal">
                            <i class="edit-icon"></i>
                        </button>
                    </div>
                    <div class="section-content">
                        <div class="info-grid">
                            <div class="info-item">
                                <div class="info-label">Phone Number</div>
                                <div class="info-value" id="phoneNum"></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Age</div>
                                <div class="info-value" id="age"></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Address</div>
                                <div class="info-value" id="address"></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">City</div>
                                <div class="info-value" id="city"></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Province</div>
                                <div class="info-value" id="province"></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Account Stats Section -->
                <div class="profile-section">
                    <div class="section-header">
                        <h2>Account Statistics</h2>
                    </div>
                    <div class="section-content">
                        <div class="stats-grid">
                            <div class="stat-card">
                                <div class="stat-icon appointments-icon"></div>
                                <div class="stat-details">
                                    <span class="stat-count" id="appointmentCount">0</span>
                                    <span class="stat-label">Total</span>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon completed-icon"></div>
                                <div class="stat-details">
                                    <span class="stat-count" id="completedCount">0</span>
                                    <span class="stat-label">Completed</span>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon upcoming-icon"></div>
                                <div class="stat-details">
                                    <span class="stat-count" id="upcomingCount">0</span>
                                    <span class="stat-label">Upcoming</span>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon rejected-icon"></div>
                                <div class="stat-details">
                                    <span class="stat-count" id="rejectedCount">0</span>
                                    <span class="stat-label">Rejected</span>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon cancelled-icon"></div>
                                <div class="stat-details">
                                    <span class="stat-count" id="cancelledCount">0</span>
                                    <span class="stat-label">Cancelled</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Right Column -->
            <div class="profile-column">
                <!-- ID Verification Section -->
                <div class="profile-section">
                    <div class="section-header">
                        <h2>ID Verification</h2>
                    </div>
                    <div class="section-content">
                        <div class="id-container">
                            <!-- Dropzone for ID upload -->
                            <div class="dropzone-container">
                                <div class="dropzone" id="dropzone">
                                    <div class="placeholder-text">
                                        <i class="upload-icon"></i>
                                        <span>Drag & Drop your ID image here or click to upload</span>
                                    </div>
                                    <img class="uploaded-image" />
                                    <input type="file" name="id_image" id="id_image" accept="image/*" />
                                </div>
                                <button id="uploadBtn" class="upload-button">
                                    <i class="upload-icon"></i> Upload ID
                                </button>
                            </div>
                            
                            <!-- ID Guidelines -->
                            <div class="id-guidelines">
                                <h3>Guidelines for ID Upload</h3>
                                <ul>
                                    <li>Make sure your ID is valid and not expired</li>
                                    <li>Ensure the photo is clear and all text is readable</li>
                                    <li>Acceptable file formats: JPG, PNG (max 5MB)</li>
                                    <li>Both front and back of the ID must be visible</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Account Settings/Actions -->
                <div class="profile-section">
                    <div class="section-header">
                        <h2>Account Settings</h2>
                    </div>
                    <div class="section-content">
                        <div class="action-buttons">
                            <button class="action-button edit-profile">
                                <i class="edit-icon"></i> Edit Profile
                            </button>
                            <button class="action-button change-password">
                                <i class="password-icon"></i> Change Password
                            </button>
                            <button class="action-button notification-settings">
                                <i class="notification-icon"></i> Notification Settings
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    @include('partials.footer')
</body>
</html>
