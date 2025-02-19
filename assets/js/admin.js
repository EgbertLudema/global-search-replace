document.addEventListener("DOMContentLoaded", function() {
    document.getElementById("apply-changes").addEventListener("submit", function(event) {
        event.preventDefault(); // Prevent full page reload

        var formData = new FormData(this);
        formData.append("action", "apply_changes");

        fetch("<?php echo admin_url('admin-ajax.php'); ?>", {
            method: "POST",
            body: formData
        })
            .then(response => response.text())
            .then(data => {
                console.log("Response:", data);
                document.getElementById("apply-changes").insertAdjacentHTML('beforeend', "<p><strong>Changes Applied Successfully!</strong></p>");
            })
            .catch(error => console.error("Error:", error));
    });
});