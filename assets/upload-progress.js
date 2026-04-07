(function () {
    function initAdminImageUploadProgress() {
        var form = document.getElementById('adminImageUploadForm');
        var fileInput = document.getElementById('image_file');
        var wrap = document.getElementById('adminImageUploadProgress');
        var bar = document.getElementById('adminImageUploadProgressBar');
        var text = document.getElementById('adminImageUploadProgressText');

        if (!form || !fileInput || !wrap || !bar || !text) {
            return;
        }

        function resetProgress() {
            wrap.hidden = true;
            bar.style.width = '0%';
            text.textContent = 'Uploading...';
        }

        function showProgress() {
            wrap.hidden = false;
            bar.style.width = '0%';
            text.textContent = 'Uploading...';
        }

        fileInput.addEventListener('change', function () {
            if (!fileInput.files || !fileInput.files.length) {
                return;
            }

            showProgress();
            var xhr = new XMLHttpRequest();
            xhr.open('POST', form.action, true);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

            xhr.upload.onprogress = function (event) {
                if (!event.lengthComputable) {
                    return;
                }
                var pct = Math.round((event.loaded / event.total) * 100);
                bar.style.width = pct + '%';
                text.textContent = 'Uploading... ' + pct + '%';
            };

            xhr.onerror = function () {
                text.textContent = 'Upload failed. Please try again.';
            };

            xhr.onload = function () {
                if (xhr.status < 200 || xhr.status >= 300) {
                    text.textContent = 'Upload failed. Please try again.';
                    return;
                }

                var payload;
                try {
                    payload = JSON.parse(xhr.responseText);
                } catch (e) {
                    text.textContent = 'Unexpected server response.';
                    return;
                }

                if (payload && payload.ok && payload.redirect) {
                    text.textContent = 'Upload complete. Refreshing...';
                    window.location.href = payload.redirect;
                    return;
                }

                text.textContent = (payload && payload.message) ? payload.message : 'Upload failed. Please try again.';
                setTimeout(resetProgress, 1600);
            };

            var formData = new FormData(form);
            xhr.send(formData);
        });
    }

    function initReviewUploadHint() {
        var reviewInput = document.getElementById('review_media_file');
        if (!reviewInput) {
            return;
        }
        reviewInput.addEventListener('change', function () {
            if (!reviewInput.files || !reviewInput.files.length) {
                return;
            }
            var file = reviewInput.files[0];
            var hint = document.getElementById('reviewUploadStatusText');
            if (!hint) {
                return;
            }
            var mb = (file.size / (1024 * 1024)).toFixed(2);
            hint.textContent = 'Selected: ' + file.name + ' (' + mb + ' MB)';
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        initAdminImageUploadProgress();
        initReviewUploadHint();
    });
})();
