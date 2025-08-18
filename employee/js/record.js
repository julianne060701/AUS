

     // Calculate total automatically and show sale info
     function calculateTotal() {
         const quantity = parseFloat(document.getElementById('quantity_sacks').value) || 0;
         const price = parseFloat(document.getElementById('price_per_kg').value) || 0;
         const unit = document.getElementById('unit').value;
         const selectedOption = document.getElementById('rice_type').options[document.getElementById('rice_type').selectedIndex];
         const sackWeight = parseFloat(selectedOption.getAttribute('data-sack-weight')) || 0;
         
         let totalKg = 0;
         let saleInfoText = '';
         
         if (unit === 'sack') {
             totalKg = quantity * sackWeight;
             saleInfoText = `Selling ${quantity} sack(s) = ${totalKg.toFixed(1)} kg`;
         } else {
             totalKg = quantity;
             saleInfoText = `Selling ${quantity} kg`;
         }
         
         const total = totalKg * price;
         document.getElementById('totalDisplay').textContent = `Total: ₱${total.toFixed(2)}`;
         
         // Show sale info
         if (quantity > 0 && sackWeight > 0) {
             document.getElementById('saleDetails').textContent = saleInfoText;
             document.getElementById('saleInfo').style.display = 'block';
         } else {
             document.getElementById('saleInfo').style.display = 'none';
         }
     }

     // Add event listeners for calculation
     document.getElementById('quantity_sacks').addEventListener('input', calculateTotal);
     document.getElementById('price_per_kg').addEventListener('input', calculateTotal);
     document.getElementById('unit').addEventListener('change', function() {
         updateStockInfo();
         calculateTotal();
     });

     // Function to update stock info based on selected unit
     function updateStockInfo() {
         const selectedOption = document.getElementById('rice_type').options[document.getElementById('rice_type').selectedIndex];
         const unit = document.getElementById('unit').value;
         const stockSacks = parseInt(selectedOption.getAttribute('data-stock-sacks')) || 0;
         const stockKg = parseFloat(selectedOption.getAttribute('data-stock-kg')) || 0;
         const totalKg = parseFloat(selectedOption.getAttribute('data-total-kg')) || 0;
         
         const stockInfo = document.getElementById('stockInfo');
         const unitInfo = document.getElementById('unitInfo');
         const quantityInput = document.getElementById('quantity_sacks');
         
         if (unit === 'sack') {
             stockInfo.innerHTML = `Available: <span class="text-success">${stockSacks} sacks</span>`;
             unitInfo.innerHTML = 'Selling by full sacks';
             quantityInput.setAttribute('max', stockSacks);
             quantityInput.setAttribute('step', '1');
             quantityInput.setAttribute('min', '1');
         } else {
             stockInfo.innerHTML = `Available: <span class="text-success">${totalKg.toFixed(1)} kg total</span>`;
             unitInfo.innerHTML = 'Selling by weight (kg)';
             quantityInput.setAttribute('max', totalKg);
             quantityInput.setAttribute('step', '0.1');
             quantityInput.setAttribute('min', '0.1');
         }
     }

    // Set preset prices when product is selected
    document.getElementById('rice_type').addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        
        if (selectedOption.value) {
            // Get data from the selected option
            const price = parseFloat(selectedOption.getAttribute('data-price')) || 0;
            const stockSacks = parseInt(selectedOption.getAttribute('data-stock-sacks')) || 0;
            const stockKg = parseFloat(selectedOption.getAttribute('data-stock-kg')) || 0;
            const totalKg = parseFloat(selectedOption.getAttribute('data-total-kg')) || 0;
            
            // Update price field
            document.getElementById('price_per_kg').value = price.toFixed(2);
            
            // Update stock info based on current unit selection
            updateStockInfo();
            
            // Calculate total
            calculateTotal();
        } else {
            // Reset fields if no product selected
            document.getElementById('price_per_kg').value = '';
            document.getElementById('stockInfo').innerHTML = 'Select rice type first';
            document.getElementById('unitInfo').innerHTML = 'Choose selling unit';
            document.getElementById('quantity_sacks').removeAttribute('max');
            document.getElementById('saleInfo').style.display = 'none';
        }
    });

     // Form submission with loading state and stock validation
     document.getElementById('saleForm').addEventListener('submit', function(e) {
         const selectedOption = document.getElementById('rice_type').options[document.getElementById('rice_type').selectedIndex];
         const requestedQuantity = parseFloat(document.getElementById('quantity_sacks').value) || 0;
         const unit = document.getElementById('unit').value;
         const stockSacks = parseInt(selectedOption.getAttribute('data-stock-sacks')) || 0;
         const totalKg = parseFloat(selectedOption.getAttribute('data-total-kg')) || 0;
         
         // Validate based on unit type
         let validationError = '';
         if (unit === 'sack') {
             if (requestedQuantity > stockSacks) {
                 validationError = `Error: Requested ${requestedQuantity} sacks exceeds available stock (${stockSacks} sacks).`;
             }
         } else {
             if (requestedQuantity > totalKg) {
                 validationError = `Error: Requested ${requestedQuantity} kg exceeds available stock (${totalKg.toFixed(1)} kg).`;
             }
         }
         
         if (validationError) {
             e.preventDefault();
             alert(validationError);
             return false;
         }
         
         const submitBtn = document.getElementById('submitSale');
         submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Recording...';
         submitBtn.disabled = true;
     });

     // Search functionality
     document.getElementById('salesSearch').addEventListener('keyup', function() {
         const searchValue = this.value.toLowerCase();
         const rows = document.querySelectorAll('#salesTable tbody tr');
         
         rows.forEach(row => {
             const text = row.textContent.toLowerCase();
             row.style.display = text.includes(searchValue) ? '' : 'none';
         });
     });

     // Action functions
     function viewSale(id) {
         alert(`Viewing sale #${id}`);
         // Add your view logic here
     }

     function printReceipt(id) {
         alert(`Printing receipt for sale #${id}`);
         // Add your print logic here
     }

     // Show success message (call this after successful form submission)
     function showSuccessMessage() {
         const alert = document.getElementById('successAlert');
         alert.style.display = 'block';
         alert.classList.add('show');
         
         // Hide after 5 seconds
         setTimeout(() => {
             alert.classList.remove('show');
             setTimeout(() => {
                 alert.style.display = 'none';
             }, 150);
         }, 5000);
     }


    $(document).ready(function () {
        $('#salesTable').DataTable({
            responsive: true,
            pageLength: 10,
            lengthChange: false,
            order: [[0, 'desc']] // Sort by ID descending
        });
    });
