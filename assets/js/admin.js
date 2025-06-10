document.addEventListener("DOMContentLoaded", function() {
    let lastClickedButton = null;

    document.querySelectorAll("#apply-changes input[type=submit]").forEach(btn => {
        btn.addEventListener("click", function() {
            lastClickedButton = this.name;
        });
    });

    document.getElementById("apply-changes").addEventListener("submit", function(event) {
        event.preventDefault();

        var formData = new FormData(this);
        formData.append("action", "apply_changes"); // Ensure WordPress recognizes the action

        // Add which button was pressed
        if (lastClickedButton) {
            formData.append(lastClickedButton, "1");
        }

        fetch(gsr_ajax.ajax_url, {
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
                    popup.innerHTML = '<p><strong>✅ Alles succesvol vervangen!</strong></p>';

                    // Add pop-up to container
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