// General form validation
document.addEventListener('DOMContentLoaded', function() {
    // Enable tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });
    
    // Confirm before deleting
    const deleteButtons = document.querySelectorAll('.delete-btn');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to delete this record?')) {
                e.preventDefault();
            }
        });
    });
    
    // Dynamic form fields for test orders
    const addTestBtn = document.getElementById('add-test-btn');
    if (addTestBtn) {
        addTestBtn.addEventListener('click', function() {
            const testContainer = document.getElementById('test-container');
            const newTestRow = document.createElement('div');
            newTestRow.className = 'row mb-3 test-row';
            newTestRow.innerHTML = `
                <div class="col-md-6">
                    <select name="test_ids[]" class="form-control" required>
                        <option value="">Select Lab Test</option>
                        <?php
                        $tests = $conn->query("SELECT * FROM lab_tests");
                        while ($test = $tests->fetch_assoc()): ?>
                        <option value="<?php echo $test['test_id']; ?>"><?php echo $test['test_name']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <textarea name="test_notes[]" class="form-control" placeholder="Notes"></textarea>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-danger remove-test-btn">Remove</button>
                </div>
            `;
            testContainer.appendChild(newTestRow);
        });
    }
    
    // Remove test row
    document.addEventListener('click', function(e) {
        if (e.target && e.target.classList.contains('remove-test-btn')) {
            e.target.closest('.test-row').remove();
        }
    });
    
    // Similar for radiology and medications
    const addRadiologyBtn = document.getElementById('add-radiology-btn');
    if (addRadiologyBtn) {
        addRadiologyBtn.addEventListener('click', function() {
            const radiologyContainer = document.getElementById('radiology-container');
            const newRadiologyRow = document.createElement('div');
            newRadiologyRow.className = 'row mb-3 radiology-row';
            newRadiologyRow.innerHTML = `
                <div class="col-md-6">
                    <select name="radiology_ids[]" class="form-control" required>
                        <option value="">Select Radiology Test</option>
                        <?php
                        $tests = $conn->query("SELECT * FROM radiology_tests");
                        while ($test = $tests->fetch_assoc()): ?>
                        <option value="<?php echo $test['radiology_id']; ?>"><?php echo $test['test_name']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <textarea name="radiology_notes[]" class="form-control" placeholder="Notes"></textarea>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-danger remove-radiology-btn">Remove</button>
                </div>
            `;
            radiologyContainer.appendChild(newRadiologyRow);
        });
    }
    
    document.addEventListener('click', function(e) {
        if (e.target && e.target.classList.contains('remove-radiology-btn')) {
            e.target.closest('.radiology-row').remove();
        }
    });
    
    const addMedicationBtn = document.getElementById('add-medication-btn');
    if (addMedicationBtn) {
        addMedicationBtn.addEventListener('click', function() {
            const medicationContainer = document.getElementById('medication-container');
            const newMedicationRow = document.createElement('div');
            newMedicationRow.className = 'row mb-3 medication-row';
            newMedicationRow.innerHTML = `
                <div class="col-md-4">
                    <select name="medication_ids[]" class="form-control" required>
                        <option value="">Select Medication</option>
                        <?php
                        $meds = $conn->query("SELECT * FROM medications");
                        while ($med = $meds->fetch_assoc()): ?>
                        <option value="<?php echo $med['medication_id']; ?>"><?php echo $med['name']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="text" name="dosages[]" class="form-control" placeholder="Dosage" required>
                </div>
                <div class="col-md-2">
                    <input type="text" name="frequencies[]" class="form-control" placeholder="Frequency" required>
                </div>
                <div class="col-md-2">
                    <input type="text" name="durations[]" class="form-control" placeholder="Duration" required>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-danger remove-medication-btn">Remove</button>
                </div>
            `;
            medicationContainer.appendChild(newMedicationRow);
        });
    }
    
    document.addEventListener('click', function(e) {
        if (e.target && e.target.classList.contains('remove-medication-btn')) {
            e.target.closest('.medication-row').remove();
        }
    });
});