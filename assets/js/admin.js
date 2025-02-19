document.addEventListener("DOMContentLoaded", function() {
    document.getElementById("apply-changes").addEventListener("submit", function(event) {
        event.preventDefault(); // Prevent form reload

        var formData = new FormData(this);
        formData.append("action", "apply_changes"); // Ensure WordPress recognizes the action

        fetch(gsr_ajax.ajax_url, { // Correct AJAX URL from PHP
            method: "POST",
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log("✅ Changes Applied Successfully");
                    document.getElementById("apply-changes").insertAdjacentHTML('beforeend', "<p><strong>✅ Changes Applied Successfully!</strong></p>");
                } else {
                    console.error("❌ Error:", data.data.message);
                    alert("Error: " + data.data.message);
                }
            })
            .catch(error => {
                console.error("❌ Fetch Error:", error);
            });
    });
});