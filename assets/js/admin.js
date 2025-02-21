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
                    document.getElementById("preview-container").remove();

                    // Create a temporary succes pop-up
                    let popup = document.createElement("div");
                    popup.classList.add("pop-up");
                    popup.innerHTML = '<p><strong>✅ Changes Applied Successfully!</strong></p>';

                    // Append the pop-up
                    document.getElementById("search_replace_container").appendChild(popup);

                    // Remove pop-up after 5 seconds
                    setTimeout(() => {
                        popup.remove();
                    }, 5000);
                } else {
                    console.error("Error:", data.data.message);
                    alert("Error: " + data.data.message);
                }
            })
            .catch(error => {
                console.error("Fetch Error:", error);
            });
    });
});