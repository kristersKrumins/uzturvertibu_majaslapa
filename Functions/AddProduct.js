function addToFood(productId, buttonElement) {
    // Prepare data for AJAX request
    const formData = new FormData();
    formData.append('productId', productId);
    
    // Send AJAX request to add_to_food.php
    fetch('Functions/add_to_food.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text()) // Get the response as text
    .then(text => {
        console.log('Server response:', text); // Log the response
        return JSON.parse(text); // Parse the response as JSON
    })
    .then(data => {
        if (data.status === 'error') {
            // Show notification
            alert(data.message);
        } else {
            // Change the button class to active and disable it
            buttonElement.classList.add('active');
            buttonElement.disabled = true;
            buttonElement.textContent = 'Pievienots';
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}