$(document).ready(function() {
    // Ensure completed filter is hidden on initial load
    $('#completed-filter-card').hide();

    // View Toggle Functionality
    $('.view-btn').click(function() {
        $('.view-btn').removeClass('active');
        $(this).addClass('active');
        
        $('#calendar-view, #kanban-view, #list-view').hide();
        
        const view = $(this).data('view');
        if (view === 'calendar') {
            $('#calendar-view').show();
            $('.stats-grid').show();
            $('.filter-card').show();
            $('#completed-filter-card').hide();
        } else if (view === 'kanban') {
            $('#kanban-view').css('display', 'flex').show();
            $('.stats-grid').show();
            $('.filter-card').show();
            $('#completed-filter-card').hide();
        } else if (view === 'list') {
            $('#list-view').show();
            $('.stats-grid').hide();
            $('.filter-card').hide();
            $('#completed-filter-card').show();
        }
    });

    // Calendar day click handler
    $('.calendar-day').click(function() {
        const selectedDate = $(this).data('date');
        $('#selected_date').val(selectedDate);
        $('#assignmentModal').modal('show');
        
        // Initialize labels based on current service type selection
        const currentServiceType = $('#service_type').val();
        if (currentServiceType) {
            updateProductLabel(currentServiceType, false);
        }
    });

    // Dynamic label and field type change for service type
    function updateProductLabel(serviceType, isEdit = false) {
        const prefix = isEdit ? 'edit_' : '';
        const dropdownGroup = $('#' + prefix + 'products_dropdown_group');
        const textGroup = $('#' + prefix + 'products_text_group');
        const dropdownLabel = dropdownGroup.find('label');
        const textLabel = textGroup.find('label');
        const quantityLabelElement = $('label[for="' + prefix + 'quantity_to_install"]');
        
        let productLabel = 'Products to Install';
        let quantityLabel = 'Quantity';
        let useDropdown = false;
        
        switch(serviceType) {
            case 'Repair':
                productLabel = 'Products to Repair';
                quantityLabel = 'Quantity';
                useDropdown = false;
                break;
            case 'Maintenance':
                productLabel = 'Products to Maintain';
                quantityLabel = 'Quantity';
                useDropdown = false;
                break;
            case 'Inspection':
                productLabel = 'Products to Inspect';
                quantityLabel = 'Quantity';
                useDropdown = false;
                break;
            case 'Installation':
            default:
                productLabel = 'Products to Install';
                quantityLabel = 'Quantity';
                useDropdown = true;
                break;
        }
        
        // Update labels
        dropdownLabel.text(productLabel + ' *');
        textLabel.text(productLabel + ' *');
        quantityLabelElement.text(quantityLabel + ' *');
        
        // Show/hide appropriate field
        if (useDropdown) {
            dropdownGroup.show();
            textGroup.hide();
            // Clear text input when switching to dropdown
            textGroup.find('input').val('');
        } else {
            dropdownGroup.hide();
            textGroup.show();
            // Clear dropdown when switching to text input
            dropdownGroup.find('select').val('');
        }
    }

    // Handle service type change in assignment modal
    $('#service_type').change(function() {
        updateProductLabel($(this).val(), false);
    });

    // Handle service type change in edit modal
    $('#edit_service_type').change(function() {
        updateProductLabel($(this).val(), true);
    });

    // Handle quantity change to show inventory warning
    $('#quantity_to_install').on('input', function() {
        const serviceType = $('#service_type').val();
        if (serviceType === 'Installation') {
            const quantityToInstall = parseInt($(this).val()) || 0;
            const selectedOption = $('#products_to_install_dropdown option:selected');
            
            if (selectedOption.length > 0 && quantityToInstall > 0) {
                const optionText = selectedOption.text();
                const qtyLeftMatch = optionText.match(/Qty Sold Left: (\d+)/);
                
                if (qtyLeftMatch) {
                    const qtySoldLeft = parseInt(qtyLeftMatch[1]);
                    
                    if (quantityToInstall > qtySoldLeft) {
                        $(this).addClass('is-invalid');
                        $(this).next('.invalid-feedback').remove();
                        $(this).after('<div class="invalid-feedback">Insufficient quantity sold left! Available: ' + qtySoldLeft + '</div>');
                    } else {
                        $(this).removeClass('is-invalid');
                        $(this).next('.invalid-feedback').remove();
                    }
                }
            } else {
                $(this).removeClass('is-invalid');
                $(this).next('.invalid-feedback').remove();
            }
        }
    });

    // Handle quantity change for edit modal
    $('#edit_quantity_to_install').on('input', function() {
        const serviceType = $('#edit_service_type').val();
        if (serviceType === 'Installation') {
            const quantityToInstall = parseInt($(this).val()) || 0;
            const selectedOption = $('#edit_products_to_install_dropdown option:selected');
            
            if (selectedOption.length > 0 && quantityToInstall > 0) {
                const optionText = selectedOption.text();
                const qtyLeftMatch = optionText.match(/Qty Sold Left: (\d+)/);
                
                if (qtyLeftMatch) {
                    const qtySoldLeft = parseInt(qtyLeftMatch[1]);
                    
                    if (quantityToInstall > qtySoldLeft) {
                        $(this).addClass('is-invalid');
                        $(this).next('.invalid-feedback').remove();
                        $(this).after('<div class="invalid-feedback">Insufficient quantity sold left! Available: ' + qtySoldLeft + '</div>');
                    } else {
                        $(this).removeClass('is-invalid');
                        $(this).next('.invalid-feedback').remove();
                    }
                }
            } else {
                $(this).removeClass('is-invalid');
                $(this).next('.invalid-feedback').remove();
            }
        }
    });

    // Handle assignment form submission
    $('#assignmentForm').submit(function(e) {
        e.preventDefault();
        
        // Get the product value from the appropriate field
        const serviceType = $('#service_type').val();
        let productsValue = '';
        
        if (serviceType === 'Installation') {
            productsValue = $('#products_to_install_dropdown').val();
        } else {
            productsValue = $('#products_to_install_text').val();
        }
        
        // Validate that a product is selected/entered
        if (!productsValue) {
            showNotification('Please select or enter a product.', 'error');
            return;
        }
        
        // Validate inventory for Installation service type
        if (serviceType === 'Installation') {
            const quantityToInstall = parseInt($('#quantity_to_install').val()) || 0;
            const selectedOption = $('#products_to_install_dropdown option:selected');
            
            if (selectedOption.length > 0) {
                const optionText = selectedOption.text();
                const qtyLeftMatch = optionText.match(/Qty Sold Left: (\d+)/);
                
                if (qtyLeftMatch) {
                    const qtySoldLeft = parseInt(qtyLeftMatch[1]);
                    
                    if (quantityToInstall > qtySoldLeft) {
                        showNotification(`Insufficient quantity sold left! Available: ${qtySoldLeft}, Requested: ${quantityToInstall}`, 'error');
                        return;
                    }
                }
                
                if (quantityToInstall <= 0) {
                    showNotification('Please enter a valid quantity for installation.', 'error');
                    return;
                }
            }
        }
        
        // Create form data with the correct product value
        let formData = $(this).serializeArray();
        // Remove the old product fields and add the correct one
        formData = formData.filter(item => !item.name.includes('products_to_install'));
        formData.push({name: 'products_to_install', value: productsValue});
        
        $.ajax({
            url: 'process_assignment.php',
            type: 'POST',
            data: $.param(formData),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#assignmentModal').modal('hide');
                    showNotification('Schedule assigned successfully!', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showNotification('Error: ' + response.message, 'error');
                }
            },
            error: function() {
                showNotification('An error occurred while processing the assignment.', 'error');
            }
        });
    });

    // Handle edit schedule button clicks
    $(document).on('click', '.edit-schedule-btn', function(e) {
        e.stopPropagation();
        const scheduleId = $(this).data('schedule-id');
        
        $.ajax({
            url: 'get_schedule_data.php',
            type: 'GET',
            data: { id: scheduleId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#edit_schedule_id').val(response.data.id);
                    $('#edit_installer_name').val(response.data.installer_name);
                    $('#edit_customer_name').val(response.data.customer_name);
                    $('#edit_contact_number').val(response.data.contact_number);
                    $('#edit_address').val(response.data.address);
                    $('#edit_schedule_date').val(response.data.schedule_date);
                    $('#edit_schedule_time').val(response.data.schedule_time);
                    $('#edit_service_type').val(response.data.service_type);
                    $('#edit_quantity_to_install').val(response.data.quantity_to_install || 1);
                    
                    // Debug: Check if quantity field exists and has value
                    const quantityField = $('#edit_quantity_to_install');
                    if (quantityField.length === 0) {
                        console.error('Quantity field not found!');
                    } else {
                        console.log('Quantity field found, value:', quantityField.val());
                    }
                    $('#edit_notes').val(response.data.notes);
                    $('#edit_status').val(response.data.status);
                    
                    // Update labels and field types based on service type
                    updateProductLabel(response.data.service_type, true);
                    
                    // Set the product value in the appropriate field
                    const serviceType = response.data.service_type;
                    if (serviceType === 'Installation') {
                        $('#edit_products_to_install_dropdown').val(response.data.products_to_install);
                        $('#edit_products_to_install_text').val('');
                    } else {
                        $('#edit_products_to_install_text').val(response.data.products_to_install);
                        $('#edit_products_to_install_dropdown').val('');
                    }
                    
                    // Handle completion image and date
                    if (response.data.status === 'Completed' && response.data.completion_image) {
                        $('#edit_completion_image').attr('src', '../' + response.data.completion_image);
                        $('#completion_image_section').show();
                    } else {
                        $('#completion_image_section').hide();
                    }
                    
                    // Handle employee list
                    if (response.data.status === 'Completed' && response.data.employee_list) {
                        const employees = response.data.employee_list.split(/[,\n\r]+/);
                        let employeeBadges = '';
                        employees.forEach(function(employee) {
                            employee = employee.trim();
                            if (employee) {
                                employeeBadges += '<span class="badge badge-secondary mr-1 mb-1">' + employee + '</span>';
                            }
                        });
                        $('#edit_employee_list').html(employeeBadges);
                        $('#employee_list_section').show();
                    } else {
                        $('#employee_list_section').hide();
                    }
                    
                    if (response.data.status === 'Completed' && response.data.completed_at) {
                        $('#edit_completed_at').text(new Date(response.data.completed_at).toLocaleString());
                        $('#completion_date_section').show();
                        
                        // Make modal read-only for completed schedules
                        $('#editScheduleModalLabel').text('View Schedule Details');
                        $('#editScheduleForm input, #editScheduleForm select, #editScheduleForm textarea').prop('readonly', true).prop('disabled', false);
                        $('#editScheduleForm button[type="submit"]').hide();
                        $('#editScheduleModal .modal-footer .btn-primary').hide();
                    } else {
                        $('#completion_date_section').hide();
                        
                        // Make modal editable for non-completed schedules
                        $('#editScheduleModalLabel').text('Edit Schedule');
                        $('#editScheduleForm input, #editScheduleForm select, #editScheduleForm textarea').prop('readonly', false).prop('disabled', false);
                        $('#editScheduleForm button[type="submit"]').show();
                        $('#editScheduleModal .modal-footer .btn-primary').show();
                    }
                    
                    $('#editScheduleModal').modal('show');
                } else {
                    showNotification('Error loading schedule data: ' + response.message, 'error');
                }
            },
            error: function() {
                showNotification('An error occurred while loading schedule data.', 'error');
            }
        });
    });

    // Function to view completion image
    function viewCompletionImage(imagePath) {
        $('#completionImageDisplay').attr('src', '../' + imagePath);
        $('#completionImageModal').modal('show');
    }

    // Function to view completion image from edit modal
    function viewCompletionImageFromModal() {
        const imageSrc = $('#edit_completion_image').attr('src');
        $('#completionImageDisplay').attr('src', imageSrc);
        $('#completionImageModal').modal('show');
    }

    // Handle edit form submission
    $('#editScheduleForm').submit(function(e) {
        e.preventDefault();
        
        // Get the product value from the appropriate field
        const serviceType = $('#edit_service_type').val();
        let productsValue = '';
        
        if (serviceType === 'Installation') {
            productsValue = $('#edit_products_to_install_dropdown').val();
        } else {
            productsValue = $('#edit_products_to_install_text').val();
        }
        
        // Validate that a product is selected/entered
        if (!productsValue) {
            showNotification('Please select or enter a product.', 'error');
            return;
        }
        
        // Create form data with the correct product value
        let formData = $(this).serializeArray();
        // Remove the old product fields and add the correct one
        formData = formData.filter(item => !item.name.includes('products_to_install'));
        formData.push({name: 'products_to_install', value: productsValue});
        
        $.ajax({
            url: 'process_edit_schedule.php',
            type: 'POST',
            data: $.param(formData),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#editScheduleModal').modal('hide');
                    showNotification('Schedule updated successfully!', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showNotification('Error: ' + response.message, 'error');
                }
            },
            error: function() {
                showNotification('An error occurred while updating the schedule.', 'error');
            }
        });
    });

    // Drag and Drop for Kanban Cards
    $('.kanban-card').draggable({
        helper: 'clone',
        cursor: 'move',
        revert: 'invalid',
        opacity: 0.7,
        start: function(event, ui) {
            $(this).addClass('dragging');
        },
        stop: function(event, ui) {
            $(this).removeClass('dragging');
        }
    });

    $('.kanban-cards').droppable({
        accept: '.kanban-card',
        hoverClass: 'bg-light',
        drop: function(event, ui) {
            const scheduleId = ui.draggable.data('schedule-id');
            const columnTitle = $(this).closest('.kanban-column').find('.column-title').text().trim();
            
            let statusValue = 'Scheduled';
            if (columnTitle.includes('In Progress')) {
                statusValue = 'In Progress';
            } else if (columnTitle.includes('Completed')) {
                statusValue = 'Completed';
            } else if (columnTitle.includes('Unassigned')) {
                statusValue = 'Scheduled';
            }
            
            // Update status via AJAX
            $.ajax({
                url: 'update_schedule_status.php',
                type: 'POST',
                data: {
                    schedule_id: scheduleId,
                    status: statusValue
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showNotification('Status updated successfully!', 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showNotification('Error: ' + response.message, 'error');
                    }
                },
                error: function() {
                    showNotification('Error updating status.', 'error');
                }
            });
        }
    });

    // Notification function
    function showNotification(message, type) {
        const bgColor = type === 'success' ? 
            'linear-gradient(135deg, #48bb78 0%, #38a169 100%)' : 
            'linear-gradient(135deg, #f56565 0%, #e53e3e 100%)';
        
        const icon = type === 'success' ? 
            '<i class="fas fa-check-circle" style="margin-right: 8px;"></i>' : 
            '<i class="fas fa-exclamation-circle" style="margin-right: 8px;"></i>';
        
        const notification = $('<div>')
            .css({
                position: 'fixed',
                top: '20px',
                right: '-400px',
                background: bgColor,
                color: 'white',
                padding: '16px 24px',
                borderRadius: '12px',
                boxShadow: '0 8px 24px rgba(0,0,0,0.2)',
                zIndex: 9999,
                display: 'flex',
                alignItems: 'center',
                fontSize: '14px',
                fontWeight: '500'
            })
            .html(icon + message)
            .appendTo('body');
        
        notification.animate({ right: '20px' }, 300);
        
        setTimeout(() => {
            notification.animate({ right: '-400px' }, 300, function() {
                $(this).remove();
            });
        }, 3000);
    }
});
