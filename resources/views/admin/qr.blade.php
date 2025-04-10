<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gracious Smile Admin -  Patient</title>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    @vite(['resources/scss/admin/admintable.scss', 'resources/scss/sidebar.scss', 'resources/scss/footer.scss', 'resources/js/sidebar.js', 'resources/scss/admin/adminmodal.scss', 'resources/js/admin/qr.js'])
</head>

<body>
    <main>
        <div class="wrapper">
            <div class="container">
                @include('partials.sidebar')
                <div class="content">
                    <div class="section">
                        <div class="section-header">
                            <div class="appointment-header">
                                <h2>Admin | <span>Gcash QR</span></h2>
                            </div>
                            <div class="profile">

                            </div>
                        </div>
                    </div>
                    <div class="section">
                        <div class="section-content-header">
                            <h2>Activated QR</h2>
                        </div>
                        <div class="table-wrapper">
                            <div class="table-navigation">
                                <div class="search">
                                    <input type="text" id="activeSearchInput" placeholder="Search">
                                </div>
                                <div class="button">
                                    <button id="add-btn">Add QR Code</button>
                                </div>
                            </div>
                            <div class="scrollable-table">
                                <table class="table table-sortable">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Gcash Name</th>
                                            <th>Date Created</th>
                                            <th>Image</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="userTableBody">

                                    </tbody>

                                </table>
                            </div>
                        </div>
                        <div id="activePagination" class="pagination-controls"></div>
                    </div>
                    <div class="section">
                        <div class="section-content-header">
                            <h2>Inactive QR Codes</h2>
                        </div>
                        <div class="table-wrapper">
                            <div class="table-navigation">
                                <div class="search">
                                    <input type="text" id="inactiveSearchInput" placeholder="Search">
                                </div>
                            </div>
                            <div class="scrollable-table">
                                <table class="table table-sortable">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Gcash Name</th>
                                            <th>Date Created</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="inactiveQRTableBody">

                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div id="inactivePagination" class="pagination-controls"></div>
                    </div>
    </main>

    <div id="addModal"style="display:none; justify-content:center;">
    <div class="modal">
        <div class="form-header">
            <div id="add-close-modal">X</div>
        </div>
        <div class="form-content">
            <h2>Add QR Code</h2>
            <form id="addQRForm" method="POST" enctype="multipart/form-data">
            <input type="hidden" id="updateQRId" name="qrId" value="">
                @csrf
                <br>
                <div class="form-control">
                    <ul id="validation-errors" class="text-danger" style="color:red; list-style:none"></ul>
                </div>

                <div class="form-control">
                    <label for="addImage" class="upload-label">Upload QR Image</label>
                    <input name="file" id="addImage" type="file" accept="image/*" required>
                    <img id="imagePreview" src="#" alt="QR Preview" style="display: none; max-width: 200px; margin-top: 10px; text-align:center;">
                </div>

                <div class="form-control">
                    <input name="name" id="addQRName" type="text" placeholder="QR Name" required>
                </div>

                <div class="form-control">
                    <input name="gcash_name" id="addGCashName" type="text" placeholder="GCash Name" required>
                </div>

                <div class="form-control">
                    <input name="number" id="addGCashNumber" type="text" placeholder="GCash Number" required>
                </div>

                <div class="form-control">
                    <button type="submit" class="submit-btn">Confirm</button>
                </div>
            </form>
        </div>
    </div>
</div>


 <center>
<!-- Modal structure for updating QR Code -->
<div id="updateModal" style="display:none;">
    <div class="modal">
        <div class="form-header">
            <div id="update-close-modal">
                X
            </div>
        </div>
        <div class="form-content">
            <h2>Edit QR Code</h2>
            <form id="updateForm" method="POST">
                @csrf
                <div class="form-control">
                    <ul id="validation-errors" class="text-danger"></ul>
                </div>
                <div class="form-control">
                    <label for="updateImage" class="upload-label">Upload QR Image</label>
                    <input name="image" id="updateImage" type="file" accept="image/*">
                    <!-- Image preview area -->
                    <div id="updateImagePreviewContainer" style="display:none;">
                        <img id="updateImagePreview" src="#" alt="Image Preview" style="max-width: 100%;"/>
                    </div>
                </div>
                <div class="form-control">
                    <input name="qr_name" id="updateQRName" type="text" placeholder="QR Name" required>
                </div>

                <div class="form-control">
                    <input name="gcash_name" id="updateGCashName" type="text" placeholder="GCash Name" required>
                </div>

                <div class="form-control">
                    <input name="gcash_number" id="updateGCashNumber" type="text" placeholder="GCash Number" required>
                </div>

                <div class="form-control">
                    <button type="submit" class="submit-btn">Confirm</button>
                </div>
            </form>
        </div>
    </div>
</div>

</center>

    <div id="deactivateModal" style="display:none;">
        <div class="modal">
            <div class="form-header">
                <div id="deactivate-close-modal">
                    X
                </div>
            </div>
            <div class="form-content">
                    <input type="hidden" name="id" id="deactivate-gcash-id" value="">
                    <div class="form-control">
                        <p>Are you sure you want to deactivate this QR?</p>
                    </div>
                    <div class="form-control">
                        <p id="deactivate-qr"></p>
                    </div>
                    <div class="form-control">
                    <button id = "deactivate-qr-button">Deactivate QR</button>
                    </div>
            </div>
        </div>
    </div>

    <div id="activateModal" style="display:none; justify-content:center;">
        <div class="modal">
            <div class="form-header">
                <div id="activate-close-modal">
                    X
                </div>
            </div>
            <div class="form-content">
                    <input type="hidden" name="id" id="activate-gcash-id" value="">
                    <div class="form-control">
                        <p>Are you sure you want to activate this QR?</p>
                    </div>
                    <div class="form-control">
                        <p id="activate-qr"></p>
                    </div>
                    <div class="form-control">
                      <button id = "activate-qr-button">Activate QR</button>
                    </div>
            </div>
        </div>
    </div>
</body>
</html>
<script>
document.addEventListener("DOMContentLoaded", function () {
    console.log("Blade script loaded");

    const modals = [
        { buttonId: "add-btn", modalId: "addModal", closeId: "add-close-modal" },
        { buttonId: "update-btn", modalId: "updateModal", closeId: "update-close-modal" },
        { buttonId: "activate-btn", modalId: "activateModal", closeId: "activate-close-modal" },
        { buttonId: "deactivate-btn", modalId: "deactivateModal", closeId: "deactivate-close-modal" }
    ];

    modals.forEach(({ buttonId, modalId, closeId }) => {
        let modal = document.getElementById(modalId);
        let closeModal = document.getElementById(closeId);
        let openButton = buttonId ? document.getElementById(buttonId) : null;

        if (openButton) {
            openButton.addEventListener("click", function () {
                console.log(`🔹 Open ${modalId} Clicked!`);
                modal.style.display = "flex";
            });
        }

        if (closeModal) {
            closeModal.addEventListener("click", function () {
                console.log(`❌ Close ${modalId} Clicked!`);
                modal.style.display = "none";
            });
        }

        // Close modal when clicking outside
        window.addEventListener("click", function (event) {
            if (event.target === modal) {
                console.log(`❌ Clicked outside ${modalId}, closing...`);
                modal.style.display = "none";
            }
        });
    });
});

document.addEventListener("DOMContentLoaded", function () {
    const fileInput = document.getElementById("addImage");
    const imagePreview = document.getElementById("imagePreview");

    fileInput.addEventListener("change", function () {
        const file = fileInput.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function (e) {
                imagePreview.src = e.target.result;
                imagePreview.style.display = "block"; 
            };
            reader.readAsDataURL(file); 
        } else {
            imagePreview.style.display = "none";
        }
    });
});


</script>
