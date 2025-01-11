 



document.getElementById('uploadButton').addEventListener('click', function () {
    var form = document.getElementById('uploadForm');
    var formData = new FormData(form);
    var xhr = new XMLHttpRequest();

    xhr.open('POST', 'upload.php', true);

    xhr.upload.addEventListener('progress', function (e) {
        var progressBarContainer = document.getElementById('progressBarContainer');
        var progressBar = document.getElementById('progressBar');

        if (e.lengthComputable) {
            var percentComplete = (e.loaded / e.total) * 100;
            progressBar.style.width = percentComplete + '%';
            progressBar.setAttribute('data-content', Math.round(percentComplete) + '%');

            if (percentComplete > 0) {
                progressBarContainer.style.display = 'block';
            }
        }
    }, false);

    xhr.addEventListener('load', function () {
        var progressBar = document.getElementById('progressBar');
        var response = JSON.parse(xhr.responseText);

        if (xhr.status === 200) {
            if (response.success) {
                Swal.fire({
                    title: 'Success',
                    text: response.message,
                    icon: 'success',
                    confirmButtonText: 'OK'
                }).then(() => {
                    location.reload(); 
                });
            } else {
                Swal.fire({
                    title: 'Error',
                    text: response.message,
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            }
        } else {
            Swal.fire({
                title: 'Error',
                text: 'Upload Failed',
                icon: 'error',
                confirmButtonText: 'OK'
            });
        }

        setTimeout(() => {
            document.getElementById('progressBarContainer').style.display = 'none';
            progressBar.style.width = '0%';
            progressBar.setAttribute('data-content', '');
        }, 2000);
    }, false);

    xhr.send(formData);
});


function confirmDelete(id) {
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
            
            window.location.href = `delete_data.php?id=${id}`;
        }
    });
}


document.addEventListener('DOMContentLoaded', function() {
    
    const messageElements = document.querySelectorAll('.message-tooltip');
    
    messageElements.forEach(element => {
        element.addEventListener('click', function() {
            
            const fullText = this.getAttribute('data-fulltext');
            
            document.getElementById('modalMessageContent').textContent = fullText;
        });
    });
});

