<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    // Initialize Select2 for Patient Name
    $('.patient_name').select2({
        placeholder: 'Search for a patient...',
        allowClear: true,
        ajax: {
            url: 'get_patients.php', // PHP script to fetch patient names
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    q: params.term // Search term
                };
            },
            processResults: function(data) {
                return {
results: data.map(item => ({ id: item.patient_id, text: item.patient_name })) // Ensure correct format for Select2
};
            },
            cache: true
        },
        minimumInputLength: 1 // Minimum characters to type before searching
    });

    // Initialize Select2 for Medicine
    $('.medicine').select2({
        placeholder: 'Search for a medicine...',
        allowClear: true,
        ajax: {
            url: 'get_medicines.php', // PHP script to fetch medicine names
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    q: params.term // Search term
                };
            },
            processResults: function(data) {
                return {
results: data.map(item => ({ id: item.medicine_name, text: item.medicine_name })) // Ensure correct format for Select2
};
            },
            cache: true
        },
        minimumInputLength: 1 // Minimum characters to type before searching
    });
});

document.getElementById('rxForm').addEventListener('submit', function(e) {
    e.preventDefault(); // Prevent default form submit

    Swal.fire({
        title: 'Are you sure?',
        text: "Do you want to save this patient's information?",
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, save it!',
        cancelButtonText: 'Cancel',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            // Here you would normally submit to save_rx.php
            // You can get selected values like:
            // const patientName = $('#patient_name').val();
            // const medicine = $('#medicine').val();
            Swal.fire('Saved!', 'Prescription has been saved.', 'success');
        }
    });
});

function printRX() {
    // Get form values
    const patientName = $('#patient_name').select2('data')[0] ? $('#patient_name').select2('data')[0].text : ''; // Get text from Select2
    const date = document.getElementById('date').value;
    const age = document.getElementById('age').value;
    const gender = document.getElementById('gender').value;
    const address = document.getElementById('address').value;
    const diagnosis = document.getElementById('diagnosis').value;
    const prescription = document.getElementById('prescription').value;

    // Check if required fields are filled
    if (!patientName || !date || !age || !gender || !address || !diagnosis || !prescription) {
        Swal.fire('Error!', 'Please fill in all required fields before printing.', 'error');
        return;
    }

    // Format date
    const formattedDate = new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });

    // Fill print template with form data
    document.getElementById('print-name').textContent = patientName;
    document.getElementById('print-date').textContent = formattedDate;
    document.getElementById('print-address').textContent = address;
    document.getElementById('print-age-gender').textContent = `${age}/${gender}`;
    document.getElementById('print-diagnosis').textContent = diagnosis;
    document.getElementById('print-prescription-content').innerHTML = prescription.replace(/\n/g, '<br>');

    // Create a new window for printing
    const printWindow = window.open('', '_blank', 'width=600,height=800');
    const printContent = document.querySelector('.print-receipt').innerHTML;

    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Prescription</title>
            <style>
                @page {
                   size: 8.3in 5.8in; /* Half of A4 landscape */
margin: 0.5in;
                }

                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }

                body {
                    width: 100%;
                    height: 100%;
                    font-family: Arial, sans-serif;
                    font-size: 12px;
                    line-height: 1.3;
                    padding: 5px;
                }

                .prescription-header {
                    text-align: center;
                    border-bottom: 2px solid #000;
                    padding-bottom: 10px;
                    margin-bottom: 15px;
                }

                .doctor-name {
                    font-size: 16px;
                    font-weight: bold;
                    margin: 8px 0;
                }

                .specialty {
                    font-size: 12px;
                    font-weight: normal;
                    margin-bottom: 10px;
                }

                .clinic-info {
                    display: flex;
                    justify-content: space-between;
                    align-items: flex-start;
                    margin-bottom: 12px;
                }

                .clinic-details {
                    text-align: left;
                    font-size: 10px;
                    line-height: 1.2;
                }

                .clinic-hours {
                    text-align: right;
                    font-size: 10px;
                    line-height: 1.2;
                }

                .patient-info {
                    margin: 18px 0;
                    font-size: 12px;
                }

                .patient-line {
                    margin: 8px 0;
                    display: flex;
                    align-items: center;
                }

                .patient-line label {
                    font-weight: bold;
                    margin-right: 8px;
                    min-width: 60px;
                }

                .patient-line .value {
                    border-bottom: 1px solid #000;
                    flex: 1;
                    padding: 3px 0;
                    min-height: 16px;
                }

                .rx-symbol {
                    font-size: 40px;
                    font-weight: bold;
                    margin: 20px 0 15px 0;
                }

                .prescription-content {
                    min-height: 120px;
                    border: 1px solid #000;
                    padding: 12px;
                    margin: 15px 0;
                    font-size: 12px;
                }

                .doctor-signature {
                    text-align: center;
                    margin-top: 25px;
                    font-size: 11px;
                    line-height: 1.2;
                }

                .signature-line {
                    border-bottom: 1px solid #000;
                    margin: 12px 0 6px 0;
                    height: 1px;
                }

                .license-info {
                    display: flex;
                    justify-content: space-between;
                    font-size: 10px;
                    margin-top: 8px;
                }
            </style>
        </head>
        <body>
            ${printContent}
        </body>
        </html>
    `);

    printWindow.document.close();
    printWindow.focus();

    // Wait for content to load then print
    setTimeout(() => {
        printWindow.print();
        printWindow.close();
    }, 500);
}


$('#patient_name').on('change', function () {
    var selectedPatient = $(this).val();

    if (selectedPatient) {
        $.ajax({
            url: 'fetch_patient_info.php',
            type: 'POST',
            data: { patient_name: selectedPatient },
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    $('#gender').val(response.gender);
                    $('#address').val(response.address);
                } else {
                    Swal.fire('Error', 'Patient not found.', 'error');
                }
            },
            error: function () {
                Swal.fire('Error', 'Unable to fetch patient info.', 'error');
            }
        });
    }
});
</script>