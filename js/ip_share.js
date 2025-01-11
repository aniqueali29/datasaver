

document.addEventListener("DOMContentLoaded", function () {
    
    document.addEventListener("keydown", function (e) {
        if (e.code === "Space") {
            e.preventDefault();
            const button = document.getElementById("myButton");
            if (button) button.click();
        }
    });

    
    document.addEventListener("contextmenu", function (e) {
        e.preventDefault();
    });

    
    const dropZone = document.getElementById("dropZone");
    const fileInput = document.getElementById("file");
    const dropText = document.getElementById("dropText");
    const uploadButton = document.getElementById("uploadButton");
    const progressBar = document.getElementById("progressBar");
    const progressBarContainer = document.getElementById("progressBarContainer");

    function uploadFile() {
        const form = document.getElementById("uploadForm");
        const formData = new FormData(form);
        const file = fileInput.files[0];

        if (!file) {
            Swal.fire({
                title: "No File Selected",
                text: "Please select a file to upload.",
                icon: "warning",
                confirmButtonText: "OK",
            });
            return;
        }

        const xhr = new XMLHttpRequest();
        xhr.open("POST", "ip_upload.php", true);

        xhr.upload.addEventListener("progress", function (e) {
            if (e.lengthComputable) {
                const percentComplete = Math.round((e.loaded / e.total) * 100);
                progressBarContainer.style.display = "block";
                progressBar.style.width = percentComplete + "%";
                progressBar.setAttribute("data-content", percentComplete + "%");

                if (percentComplete === 100) {
                    Swal.fire({
                        title: "Almost there!",
                        html: `<div class="spinner-border" role="status" style="width: 3rem; height: 3rem;"></div>
                            <p style="margin-top: 10px;">Preparing your file for Network Sharing<span id="dots">.</span></p>`,
                        icon: "info",
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        didOpen: () => {
                            let dots = document.getElementById("dots");
                            setInterval(() => {
                                dots.innerHTML = dots.innerHTML.length > 3 ? "" : dots.innerHTML + ".";
                            }, 500);
                        },
                    });
                }
            }
        });

        xhr.addEventListener("load", function () {
            let response;
            try {
                response = JSON.parse(xhr.responseText);
            } catch (error) {
                response = { success: false, message: "Invalid response from server." };
            }

            if (xhr.status === 200 && response.success) {
                Swal.fire({
                    title: "Success",
                    text: response.message,
                    icon: "success",
                    timer: 1000,
                    showConfirmButton: false,
                    willClose: () => location.reload(),
                });
            } else {
                Swal.fire({
                    title: "Error",
                    text: response.message || "Upload Failed",
                    icon: "error",
                    confirmButtonText: "OK",
                });
            }

            setTimeout(() => {
                progressBarContainer.style.display = "none";
                progressBar.style.width = "0%";
                progressBar.setAttribute("data-content", "");
            }, 2000);
        });

        xhr.send(formData);
    }

    uploadButton?.addEventListener("click", uploadFile);

    document.addEventListener("keydown", function (e) {
        if (e.key === "Enter") {
            e.preventDefault();
            uploadFile();
        }
    });

    dropZone?.addEventListener("dragover", function (e) {
        e.preventDefault();
        dropZone.classList.add("dragover");
    });

    dropZone?.addEventListener("dragleave", function () {
        dropZone.classList.remove("dragover");
    });

    dropZone?.addEventListener("drop", function (e) {
        e.preventDefault();
        dropZone.classList.remove("dragover");

        const files = e.dataTransfer.files;
        if (files.length > 0) {
            fileInput.files = files;
            dropText.textContent = files[0].name;
        }
    });

    dropZone?.addEventListener("click", function () {
        fileInput.click();
    });

    fileInput?.addEventListener("change", function () {
        if (fileInput.files.length > 0) {
            dropText.textContent = fileInput.files[0].name;
        }
    });

    
    window.confirmDelete = function (id) {
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `delete_file.php?id=${id}`;
            }
        });
    };

    
    const messageElements = document.querySelectorAll('.message-tooltip');
    messageElements.forEach(element => {
        element.addEventListener('click', function () {
            const fullText = this.getAttribute('data-fulltext');
            document.getElementById('modalMessageContent').textContent = fullText;
        });
    });

    
    const timezoneOffset = new Date().getTimezoneOffset();
    const timezoneOffsetInMinutes = -timezoneOffset;
    const timezoneInput = document.getElementById('timezone-offset');
    if (timezoneInput) timezoneInput.value = timezoneOffsetInMinutes;

    
    window.deleteFile = function (fileId, fileName) {
    Swal.fire({
        title: 'Are you sure?',
        text: "This action cannot be undone!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            const xhr = new XMLHttpRequest();
            // Updated path for delete_file.php
            xhr.open('POST', '../php/delete_file.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

            xhr.onload = function () {
                if (xhr.status === 200) {
                    Swal.fire('Deleted!', xhr.responseText, 'success').then(() => location.reload());
                } else {
                    Swal.fire('Error!', 'There was an error deleting the file.', 'error');
                }
            };

            xhr.send('id=' + fileId + '&file=' + encodeURIComponent(fileName));
        }
    });
};

});
