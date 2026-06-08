// ===== VALIDATION UTILITIES =====
// Front Office Validation Functions

/**
 * Show error for input field
 * @param {string} inputId - Input element ID
 * @param {string} errId - Error element ID
 * @param {string} msg - Error message (optional)
 */
function showErr(inputId, errId, msg) {
    const el = document.getElementById(inputId);
    const er = document.getElementById(errId);
    if (el) el.classList.add('error');
    if (er) {
        if (msg) er.textContent = msg;
        er.classList.add('show');
    }
}

/**
 * Clear error for input field
 * @param {string} inputId - Input element ID
 * @param {string} errId - Error element ID
 */
function clearErr(inputId, errId) {
    const el = document.getElementById(inputId);
    const er = document.getElementById(errId);
    if (el) el.classList.remove('error');
    if (er) er.classList.remove('show');
}

/**
 * Validate sinistre declaration form
 * @returns {boolean} - True if valid, false otherwise
 */
function validateSinistreForm() {
    let ok = true;

    // Contrat validation
    const contratEl = document.getElementById('fContrat');
    const contratVal = contratEl ? contratEl.value.trim() : '';
    if (!contratVal) {
        showErr('fContrat', 'errContrat');
        ok = false;
    } else {
        clearErr('fContrat', 'errContrat');
    }

    // Type validation
    const type = document.getElementById('typeHidden').value;
    if (!type) {
        showErr('typeHidden', 'errType');
        ok = false;
    } else {
        clearErr('typeHidden', 'errType');
    }

    // Description validation (minimum 20 characters)
    const desc = document.getElementById('fDescription').value.trim();
    if (desc.length < 20) {
        showErr('fDescription', 'errDescription');
        ok = false;
    } else {
        clearErr('fDescription', 'errDescription');
    }

    return ok;
}



