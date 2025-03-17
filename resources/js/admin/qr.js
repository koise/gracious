    import $ from "jquery";
    import axios from "axios";

    $(document).ready(function () {
        let selectedQRId = null;

        // 🟢 Open Update Modal and Populate Fields
        $(document).on("click", ".update-btn", function () {
            selectedQRId = $(this).data("id");

            // Fetch existing QR data
            axios.get(`qrs/${selectedQRId}`)
                .then((response) => {
                    let qr = response.data.qr;
                    $("#addQRName").val(qr.name);
                    $("#addGCashName").val(qr.gcash_name);
                    $("#addGCashNumber").val(qr.number);
                    $("#addAmount").val(qr.amount);
                    
                    if (qr.image_path) {
                        $("#imagePreview").attr("src", `/${qr.image_path}`).show();
                    } else {
                        $("#imagePreview").hide();
                    }

                    $("#updateModal").fadeIn();
                })
                .catch((error) => {
                    console.error("Error fetching QR data:", error);
                    alert("Failed to fetch QR details.");
                });
        });

        // 🔴 Close Modal
        $("#update-close-modal").click(function () {
            $("#updateModal").fadeOut();
        });

        // 🟡 Update QR Code
        $("#updateForm").submit(function (event) {
            event.preventDefault();

            let formData = new FormData(this);
            formData.append("_method", "PUT");

            axios.post(`/qrs/${selectedQRId}`, formData, {
                headers: { "Content-Type": "multipart/form-data" }
            })
            .then((response) => {
                alert(response.data.message);
                $("#updateModal").fadeOut();
                location.reload(); // Refresh table
            })
            .catch((error) => {
                console.error("Update error:", error);
                $("#validation-errors").html(""); // Clear errors

                if (error.response && error.response.data.errors) {
                    let errors = error.response.data.errors;
                    Object.keys(errors).forEach((key) => {
                        $("#validation-errors").append(`<li>${errors[key][0]}</li>`);
                    });
                } else {
                    alert("An error occurred while updating.");
                }
            });
        });
    });


    $(document).ready(function () {
        // Fetch QR data on page load
        fetchQRData();

        // ✅ Image preview before upload
        $("#addImage").on("change", function () {
            let reader = new FileReader();
            reader.onload = function (e) {
                $("#imagePreview").attr("src", e.target.result).show();
            };
            reader.readAsDataURL(this.files[0]);
        });

        // ✅ Handle form submission
        $("#addQRForm").on("submit", function (e) {
            e.preventDefault();

            let formData = new FormData(this);

            axios.post("/admin/qr/add", formData, {
                headers: {
                    "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
                    "Content-Type": "multipart/form-data",
                },
            })
            .then((response) => {
                alert("QR Code added successfully!");
                fetchQRData();
                $("#addQRForm")[0].reset();
                $("#imagePreview").hide();
                $("#addModal").fadeOut();
            })
            .catch((error) => {
                if (error.response && error.response.status === 422) {
                    displayValidationErrors(error.response.data.errors);
                } else {
                    console.error("Error:", error);
                    alert("Something went wrong. Please try again.");
                }
            });
        });

        // ✅ Fetch QR data
        function fetchQRData() {
            axios.get("/admin/qr/fetch")
                .then((response) => {
                    console.log("Fetched QR Data:", response.data);
                    populateTable(response.data.activeQRs ?? [], "userTableBody");
                    populateTable(response.data.deactivatedQRs ?? [], "deactivatedUserTableBody");
                })
                .catch((error) => {
                    console.error("Error fetching QR data:", error);
                    alert("Failed to fetch QR codes.");
                });
        }

    // ✅ Populate table with QR data
    function populateTable(qrData, tableBodyId) {
        const tableBody = $("#" + tableBodyId);
        tableBody.empty();

        if (!qrData.length) {
            tableBody.append(`<tr><td colspan="5" class="text-center">No QR codes available.</td></tr>`);
            return;
        }

        qrData.forEach((qr) => {
            const row = `
                <tr>
                    <td>${qr.id}</td>
                    <td>${qr.name}</td>
                    <td>${qr.gcash_name}</td>
                    <td>${new Date(qr.created_at).toLocaleDateString()}</td>
                    <td>
                        <button 
                            data-id="${qr.id}" 
                            data-name="${qr.name}"  
                            data-number="${qr.number}" 
                            class="send-btn">
                            <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e8eaed">
                                <path d="M120-160v-640l760 320-760 320Zm80-120 474-200-474-200v140l240 60-240 60v140Zm0 0v-400 400Z"/>
                            </svg>
                        </button>
                    </td>
                </tr>
            `;
            tableBody.append(row);
        });

        $(".delete-btn").off("click").on("click", function () {
            deleteQR($(this).data("id"));
        });
    }


        // ✅ Display validation errors
        function displayValidationErrors(errors) {
            let errorList = $("#validation-errors");
            errorList.html("");

            $.each(errors, function (key, value) {
                errorList.append(`<li>${value[0]}</li>`);
            });
        }
    });
