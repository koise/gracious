<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gracious Smile Admin -  Patient</title>

    @vite(['resources/scss/admin/admintable.scss', 'resources/scss/sidebar.scss', 'resources/scss/footer.scss', 'resources/js/sidebar.js', 'resources/scss/admin/adminmodal.scss', 'resources/js/admin/qr.js'])

    <style>

    </style>
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
                            <h2>Deactivated Accounts</h2>
                        </div>
                        <div class="table-wrapper">
                            <div class="table-navigation">
                                <div class="search">
                                    <input type="text" id="deactiveSearchInput" placeholder="Search">
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
                                    <tbody id="deactivatedUserTableBody">

                                    </tbody>

                                </table>
                            </div>
                        </div>
                        <div id="deactivatedPagination" class="pagination-controls"></div>
                    </div>
                </div>
            </div>
        </div>

    </main>

    <div id="addModal">
        <div class="modal">
            <div class="form-header">
                <div id="add-close-modal">X</div>
            </div>
            <div class="form-content">
                <h2>Add QR Code</h2>
                <form id="addQRForm" method="POST" enctype="multipart/form-data">
                    @csrf
                    <br>

                    <div class="form-control">
                    <ul id="validation-errors" class="text-danger" style="color:red; list-style:none"></ul>
                    </div>

                    <div class="form-control">
                        <label for="addImage" class="upload-label">Upload QR Image</label>
                        <input name="image" id="addImage" type="file" accept="image/*" required>
                        <img id="imagePreview" src="#" alt="QR Preview" style="display: none;">
                    </div>

                    <div class="form-control">
                        <input name="qr_name" id="addQRName" type="text" placeholder="QR Name" required>
                    </div>

                    <div class="form-control">
                        <input name="gcash_name" id="addGCashName" type="text" placeholder="GCash Name" required>
                    </div>

                    <div class="form-control">
                        <input name="gcash_number" id="addGCashNumber" type="text" placeholder="GCash Number" required>
                    </div>

                    <div class="form-control">
                        <button type="submit" class="submit-btn">Confirm</button>
                    </div>
                </form>
            </div>
        </div>
    </div>



    <div id="updateModal">
        <div class="modal">
            <div class="form-header">
                <div id="update-close-modal">
                    X
                </div>
            </div>
            <div class="form-content">
                <h2>Edit Patient</h2>
                <form id="updateForm" method="POST">
                    @csrf
                    <div class="form-control">
                    <ul id="validation-errors" class="text-danger"></ul>
                </div>

                <div class="form-control">
                    <input name="qr_name" id="addQRName" type="text" placeholder="QR Name" required>
                </div>

                <div class="form-control">
                    <input name="gcash_name" id="addGCashName" type="text" placeholder="GCash Name" required>
                </div>

                <div class="form-control">
                    <input name="gcash_number" id="addGCashNumber" type="text" placeholder="GCash Number" required>
                </div>

                <div class="form-control">
                    <input name="amount" id="addAmount" type="number" step="0.01" placeholder="Amount (₱)" required>
                </div>

                <div class="form-control">
                    <label for="addImage" class="upload-label">Upload QR Image</label>
                    <input name="image" id="addImage" type="file" accept="image/*" required>
                    <img id="imagePreview" src="#" alt="QR Preview" style="display: none;">
                </div>

                <div class="form-control">
                    <button type="submit" class="submit-btn">Confirm</button>
                </div>
                </form>
            </div>
        </div>
    </div>

    <div id="deactivateModal">
        <div class="modal">
            <div class="form-header">
                <div id="deactivate-close-modal">
                    X
                </div>
            </div>
            <div class="form-content">
                <form id="deactivateForm" method="POST">
                    @csrf
                    <input type="hidden" name="id" id="deactivate-gcash-id" value="">
                    <div class="form-control">
                        <p>Are you sure you want to deactivate this QR?</p>
                    </div>
                    <div class="form-control">
                        <p id="deactivate-qr"></p>
                    </div>
                    <div class="form-control">
                        <button type="submit" class="submit-btn">Confirm</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="activateModal">
        <div class="modal">
            <div class="form-header">
                <div id="activate-close-modal">
                    X
                </div>
            </div>
            <div class="form-content">
                <form id="activateForm" method="POST">
                    @csrf
                    <input type="hidden" name="id" id="activate-gcash-id" value="">
                    <div class="form-control">
                        <p>Are you sure you want to activate this QR?</p>
                    </div>
                    <div class="form-control">
                        <p id="activate-qr"></p>
                    </div>
                    <div class="form-control">
                        <button type="submit" class="submit-btn">Confirm</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
<script>
document.addEventListener("DOMContentLoaded", function () {
    // Add Modal
    const addBtn = document.getElementById("add-btn");
    const addModal = document.getElementById("addModal");
    const addClose = document.getElementById("add-close-modal");

    addBtn.addEventListener("click", function () {
        addModal.style.display = "flex";
    });

    addClose.addEventListener("click", function () {
        addModal.style.display = "none";
    });

    // Close modal when clicking outside
    window.addEventListener("click", function (e) {
        if (e.target === addModal) {
            addModal.style.display = "none";
        }
    });

    // Image Preview
    const imageInput = document.getElementById("addImage");
    const imagePreview = document.getElementById("imagePreview");

    imageInput.addEventListener("change", function () {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function (e) {
                imagePreview.src = e.target.result;
                imagePreview.style.display = "block";
            };
            reader.readAsDataURL(file);
        }
    });
});
</script>


</html>
