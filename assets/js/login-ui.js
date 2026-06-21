document.addEventListener("DOMContentLoaded", function() {
    const form = document.querySelector('form');
    
    if (form) {
        form.addEventListener('submit', function(event) {
            let isValid = true;
            
            const requiredInputs = form.querySelectorAll('input[required], select[required]');
            
            // Remove previous error messages
            form.querySelectorAll('.custom-popup').forEach(e => e.remove());
            
            requiredInputs.forEach(input => {
                if (!input.value.trim()) {
                    isValid = false;
                    
                    input.style.borderColor = '#ff4d4f'; 
                    
                    const errorMsg = document.createElement('div');
                    errorMsg.className = 'custom-popup';
                    errorMsg.innerText = "Please fill out this field.";
                    
                    input.parentNode.insertBefore(errorMsg, input.nextSibling);
                } else {
                    input.style.borderColor = 'rgba(255, 255, 255, 0.1)'; 
                }
            });
            
            if (!isValid) {
                event.preventDefault(); 
            }
        });

        // Reset input styles on input
        form.querySelectorAll('input').forEach(input => {
            input.addEventListener('input', () => {
                if (input.value.trim().length > 0) {
                    input.style.borderColor = 'rgba(255, 255, 255, 0.1)';
                    const sibling = input.nextSibling;
                    if (sibling && sibling.className === 'custom-popup') {
                        sibling.remove();
                    }
                }
            });
        });
    }
});
