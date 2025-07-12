document.addEventListener('DOMContentLoaded', () => {
    const isFormPage = document.getElementById('prescriptionForm') !== null;
    const isPreviewPage = document.getElementById('prescriptionPreview') !== null;

    // Common function to collect all form data
    const collectFormData = () => {
        const data = {};

        // Get basic text inputs and textareas
        const textInputIds = [
            'doctorName', 'qualifications', 'specialist', 'regNo', 'designation',
            'patientName', 'ageSex', 'date', 'bp', 'pr', 'temp', 'wt', 'spo2',
            'co', 'ho', 'oe', 'additionalNotes', 'medicalName', 'addressDetails', 'phoneDetails' // 'medicalName' is here
        ];
        textInputIds.forEach(id => {
            const element = document.getElementById(id);
            if (element) {
                data[id] = element.value;
            }
        });

        // Get medicine data
        data.medicines = [];
        document.querySelectorAll('.medicine-row').forEach(row => {
            data.medicines.push({
                name: row.querySelector('.medicine-name').value,
                m: row.querySelector('.medicine-m').checked,
                a: row.querySelector('.medicine-a').checked,
                e: row.querySelector('.medicine-e').checked,
                n: row.querySelector('.medicine-n').checked,
                days: row.querySelector('.medicine-days').value
            });
        });

        // Get font styles (if available on the form page)
        // Retrieve directly from localStorage as they are updated there live
        data.doctorNameFontSize = parseFloat(localStorage.getItem('doctorNameFontSize')) || 1.6;
        data.doctorNameColor = localStorage.getItem('doctorNameColor') || '#333333';
        data.patientNameFontSize = parseFloat(localStorage.getItem('patientNameFontSize')) || 1.0;
        data.patientNameColor = localStorage.getItem('patientNameColor') || '#000000';

        return data;
    };


    if (isFormPage) {
        const form = document.getElementById('prescriptionForm');
        const medicineInputsContainer = document.getElementById('medicineInputs');
        const addMedicineRowBtn = document.getElementById('addMedicineRow');

        // Initial setup for date
        document.getElementById('date').value = new Date().toISOString().slice(0, 10);

        // --- Medicine Management for Form Page ---
        let medicineCount = 0;

        const addMedicineRow = (medicine = { name: '', m: false, a: false, e: false, n: false, days: '' }) => {
            medicineCount++;
            const inputRow = document.createElement('div');
            inputRow.classList.add('medicine-row');
            inputRow.setAttribute('data-id', medicineCount);

            inputRow.innerHTML = `
                <input type="text" class="medicine-name" value="${medicine.name}" placeholder="Medicine Name">
                <div class="medicine-checkboxes">
                    <div class="medicine-checkbox-group">
                        <label>M</label>
                        <input type="checkbox" class="medicine-m" ${medicine.m ? 'checked' : ''}>
                    </div>
                    <div class="medicine-checkbox-group">
                        <label>A</label>
                        <input type="checkbox" class="medicine-a" ${medicine.a ? 'checked' : ''}>
                    </div>
                    <div class="medicine-checkbox-group">
                        <label>E</label>
                        <input type="checkbox" class="medicine-e" ${medicine.e ? 'checked' : ''}>
                    </div>
                    <div class="medicine-checkbox-group">
                        <label>N</label>
                        <input type="checkbox" class="medicine-n" ${medicine.n ? 'checked' : ''}>
                    </div>
                </div>
                <input type="text" class="medicine-days" value="${medicine.days}" placeholder="Days">
                <button type="button" class="remove-medicine-btn">X</button>
            `;
            medicineInputsContainer.appendChild(inputRow);

            const removeButton = inputRow.querySelector('.remove-medicine-btn');
            removeButton.addEventListener('click', () => {
                inputRow.remove();
                saveFormDataToLocalStorage(); // Save after removing
            });
        };

        addMedicineRowBtn.addEventListener('click', () => {
            addMedicineRow();
            saveFormDataToLocalStorage(); // Save after adding
        });

        // --- Font Size and Color Controls ---
        const applyFontStylesToInput = () => {
            const doctorNameInput = document.getElementById('doctorName');
            const patientNameInput = document.getElementById('patientName');

            const doctorNameFontSizeStored = localStorage.getItem('doctorNameFontSize');
            const doctorNameColorStored = localStorage.getItem('doctorNameColor');
            const patientNameFontSizeStored = localStorage.getItem('patientNameFontSize');
            const patientNameColorStored = localStorage.getItem('patientNameColor');

            if (doctorNameFontSizeStored) doctorNameInput.style.fontSize = `${doctorNameFontSizeStored}em`;
            if (doctorNameColorStored) doctorNameInput.style.color = doctorNameColorStored;
            if (patientNameFontSizeStored) patientNameInput.style.fontSize = `${patientNameFontSizeStored}em`;
            if (patientNameColorStored) patientNameInput.style.color = patientNameColorStored;

            // Set color picker values
            document.getElementById('doctorNameColor').value = doctorNameColorStored || '#333333';
            document.getElementById('patientNameColor').value = patientNameColorStored || '#000000';
        };

        // Event listeners for font controls (update localStorage and apply styles)
        const setupFontControls = (inputId, decreaseBtnId, increaseBtnId, colorInputId) => {
            const inputElement = document.getElementById(inputId);
            const decreaseBtn = document.getElementById(decreaseBtnId);
            const increaseBtn = document.getElementById(increaseBtnId);
            const colorInput = document.getElementById(colorInputId);

            decreaseBtn.addEventListener('click', () => {
                let currentSize = parseFloat(localStorage.getItem(`${inputId}FontSize`)) || (inputId === 'doctorName' ? 1.6 : 1.0);
                const newSize = Math.max(0.8, currentSize - 0.1);
                inputElement.style.fontSize = `${newSize}em`;
                localStorage.setItem(`${inputId}FontSize`, newSize);
            });

            increaseBtn.addEventListener('click', () => {
                let currentSize = parseFloat(localStorage.getItem(`${inputId}FontSize`)) || (inputId === 'doctorName' ? 1.6 : 1.0);
                const newSize = currentSize + 0.1;
                inputElement.style.fontSize = `${newSize}em`;
                localStorage.setItem(`${inputId}FontSize`, newSize);
            });

            colorInput.addEventListener('input', () => {
                inputElement.style.color = colorInput.value;
                localStorage.setItem(`${inputId}Color`, colorInput.value);
            });
        };

        setupFontControls('doctorName', 'doctorNameFontDecrease', 'doctorNameFontIncrease', 'doctorNameColor');
        setupFontControls('patientName', 'patientNameFontDecrease', 'patientNameFontIncrease', 'patientNameColor');

        // --- Save and Load Form Data (to/from Local Storage) ---
        // This is for persisting input form state, not for the final "save" to PHP
        const saveFormDataToLocalStorage = () => {
            const data = collectFormData();
            localStorage.setItem('tempPrescriptionFormData', JSON.stringify(data));
        };

        const loadFormDataFromLocalStorage = () => {
            const savedData = localStorage.getItem('tempPrescriptionFormData');
            if (savedData) {
                const data = JSON.parse(savedData);
                // Populate basic fields
                for (const key in data) {
                    const element = document.getElementById(key);
                    if (element && key !== 'medicines' && !key.includes('FontSize') && !key.includes('Color')) {
                        element.value = data[key];
                    }
                }

                // Populate medicines
                medicineInputsContainer.innerHTML = ''; // Clear existing
                if (data.medicines && data.medicines.length > 0) {
                    data.medicines.forEach(med => addMedicineRow(med));
                } else {
                    // This is the block that previously added default medicines.
                    // We will no longer add any default medicines here.
                    // You might want to add an empty row by default, or nothing.
                    // For now, doing nothing, so the section will be empty if no saved data.
                    addMedicineRow(); // Add one empty row
                }
            } else {
                // If there's no saved data at all, start with one empty medicine row.
                addMedicineRow();
            }
            applyFontStylesToInput(); // Apply stored font styles after loading data
        };

        // Save data on any input change (debounce for better performance on rapid input)
        let debounceTimer;
        const allFormElements = form.querySelectorAll('input, textarea, select, .medicine-checkboxes input[type="checkbox"]');
        allFormElements.forEach(element => {
            element.addEventListener('input', () => {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(saveFormDataToLocalStorage, 300); // Save after 300ms of no input
            });
        });
        medicineInputsContainer.addEventListener('change', () => { // For checkboxes
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(saveFormDataToLocalStorage, 300);
        });


        // --- Handle Form Submission (AJAX to PHP) ---
        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            const prescriptionData = collectFormData();

            try {
                const response = await fetch('save_prescription.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(prescriptionData)
                });

                if (response.ok) {
                    const result = await response.json();
                    if (result.success) {
                        alert('Prescription data successfully saved! File: ' + result.filename); // Success alert
                        // Store filename in localStorage to retrieve on preview page
                        localStorage.setItem('lastPrescriptionFilename', result.filename);
                        window.location.href = 'prescription.html'; // Redirect to preview
                    } else {
                        alert('Error saving prescription: ' + result.message); // PHP reported error
                        console.error('PHP Save Error:', result.message);
                    }
                } else {
                    // Server responded with an error status (e.g., 404, 500)
                    const errorText = await response.text();
                    alert('Server error during save: ' + response.status + ' ' + response.statusText + '\nDetails: ' + errorText.substring(0, 200) + '...'); // Truncate for alert
                    console.error('Server error response:', response.status, response.statusText, errorText);
                }
            } catch (error) {
                // Network error, JSON parsing error, etc.
                console.error('Network or parsing error:', error);
                alert('An error occurred while trying to save the prescription. Please check your network connection or server status. Error: ' + error.message);
            }
        });

        // Load data when the form page loads
        loadFormDataFromLocalStorage();


    } else if (isPreviewPage) {
        // Select all preview elements
        const previewElements = {
            previewDoctorName: document.getElementById('previewDoctorName'),
            previewQualifications: document.getElementById('previewQualifications'),
            previewSpecialist: document.getElementById('previewSpecialist'),
            previewRegNo: document.getElementById('previewRegNo'),
            previewDesignation: document.getElementById('previewDesignation'),
            previewPatientName: document.getElementById('previewPatientName'),
            previewAgeSex: document.getElementById('previewAgeSex'),
            previewDate: document.getElementById('previewDate'),
            previewBp: document.getElementById('previewBp'),
            previewPr: document.getElementById('previewPr'),
            previewTemp: document.getElementById('previewTemp'),
            previewWt: document.getElementById('previewWt'),
            previewSpo2: document.getElementById('previewSpo2'),
            previewCo: document.getElementById('previewCo'),
            previewHo: document.getElementById('previewHo'),
            previewOe: document.getElementById('previewOe'),
            previewAdditionalNotes: document.getElementById('previewAdditionalNotes'),
            previewMedicalNameBox: document.getElementById('previewMedicalNameBox'), // This is the target element
            previewAddressDetails: document.getElementById('previewAddressDetails'),
            previewPhoneDetails: document.getElementById('previewPhoneDetails')
        };

        const medicineTableBody = document.getElementById('medicineTableBody');
        const downloadPdfBtn = document.getElementById('downloadPdfBtn');
        const printPrescriptionBtn = document.getElementById('printPrescriptionBtn');
        const backToFormBtn = document.getElementById('backToFormBtn');


        // Function to populate preview from fetched data
        const populatePreview = (data) => {
            // Populate general fields
            for (const key in previewElements) {
                const dataKey = key.replace('preview', '').charAt(0).toLowerCase() + key.replace('preview', '').slice(1);
                if (data[dataKey] !== undefined) {
                    previewElements[key].textContent = data[dataKey]; // This line sets the text for previewMedicalNameBox
                }
            }

            // Apply font styles
            if (data.doctorNameFontSize) {
                previewElements.previewDoctorName.style.fontSize = `${data.doctorNameFontSize}em`;
            }
            if (data.doctorNameColor) {
                previewElements.previewDoctorName.style.color = data.doctorNameColor;
            }
            if (data.patientNameFontSize) {
                previewElements.previewPatientName.style.fontSize = `${data.patientNameFontSize}em`;
            }
            if (data.patientNameColor) {
                previewElements.previewPatientName.style.color = data.patientNameColor;
            }

            // Populate medicine table
            medicineTableBody.innerHTML = ''; // Clear current table
            if (data.medicines && data.medicines.length > 0) {
                data.medicines.forEach(med => {
                    const newRow = document.createElement('tr');
                    newRow.innerHTML = `
                        <td>${med.name}</td>
                        <td>${med.m ? '✔️' : ''}</td>
                        <td>${med.a ? '✔️' : ''}</td>
                        <td>${med.e ? '✔️' : ''}</td>
                        <td>${med.n ? '✔️' : ''}</td>
                        <td>${med.days}</td>
                    `;
                    medicineTableBody.appendChild(newRow);
                });
            }
        };

        // --- Load Data for Preview Page ---
        const loadPreviewData = async () => {
            const filename = localStorage.getItem('lastPrescriptionFilename');
            if (filename) {
                try {
                    const response = await fetch(`get_prescription.php?filename=${filename}`);
                    if (response.ok) {
                        const prescriptionData = await response.json();
                        if (prescriptionData.success) {
                               populatePreview(prescriptionData.data);
                        } else {
                            alert('Error loading prescription: ' + prescriptionData.message);
                            console.error('Error fetching prescription:', prescriptionData.message);
                            window.location.href = 'view.html'; // Go back if data not found
                        }
                    } else {
                        const errorText = await response.text();
                        alert('Server error loading prescription: ' + response.status + ' ' + response.statusText + '\nDetails: ' + errorText.substring(0, 200) + '...'); // Truncate for alert
                        console.error('Server error response:', response.status, response.statusText, errorText);
                        window.location.href = 'view.html';
                    }
                } catch (error) {
                    console.error('Network or parsing error loading prescription:', error);
                    alert('An error occurred while trying to load the prescription. Please check your network connection. Error: ' + error.message);
                    window.location.href = 'view.html';
                }
            } else {
                alert('No recent prescription to display. Please generate one first.');
                window.location.href = 'view.html'; // Redirect if no filename
            }
        };

        // --- Print Functionality ---
        printPrescriptionBtn.addEventListener('click', () => {
            window.print();
        });

            downloadPdfBtn.addEventListener('click', () => {
        const element = document.getElementById('prescriptionPreview');

        const opt = {
            margin: [0, 0, 0, 0], // Absolute zero margins
            filename: 'prescription.pdf',
            image: { type: 'jpeg', quality: 1 },
            html2canvas: {
                scale: 2,
                useCORS: true,
                scrollX: 0,
                scrollY: 0
            },
            jsPDF: {
                unit: 'mm',
                format: [210, 297],
                orientation: 'portrait'
            },
            pagebreak: { mode: [] } // Disable pagebreak handling
        };

        html2pdf().from(element).set(opt).save();
    });


        // Back to Form Button
        backToFormBtn.addEventListener('click', () => {
            window.location.href = 'view.html';
        });

        loadPreviewData(); // Load data when the preview page loads
    }
});