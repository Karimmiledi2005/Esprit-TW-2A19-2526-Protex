

function showError(input, message) {
    clearError(input);
    if (!input) return;
    input.classList.add('input-error');
    const err = document.createElement('span');
    err.className = 'field-error';
    err.textContent = message;
    if (input.nextSibling) {
        input.parentNode.insertBefore(err, input.nextSibling);
    } else {
        input.parentNode.appendChild(err);
    }
}

function clearError(input) {
    if (!input) return;
    input.classList.remove('input-error', 'input-valid');
    const existing = input.parentNode.querySelector('.field-error');
    if (existing) existing.remove();
}

function markValid(input) {
    if (!input) return;
    clearError(input);
    input.classList.add('input-valid');
}

const rules = {
nom: {
        validate(v) {
            if (!v) return 'Le nom est obligatoire';
            if (v.length < 2) return 'Le nom doit contenir au moins 2 lettres';
            if (v.length > 50) return 'Le nom ne doit pas dépasser 50 caractères';
            if (/[0-9]/.test(v)) return 'Le nom ne doit pas contenir de chiffres';
            if (!/^[a-zA-ZÀ-ÿ\u0600-\u06FF\s'\-]+$/.test(v)) return 'Le nom ne doit contenir que des lettres';
            return null;
        }
    },
    prenom: {
        validate(v) {
            if (!v) return 'Le prénom est obligatoire';
            if (v.length < 2) return 'Le prénom doit contenir au moins 2 lettres';
            if (v.length > 50) return 'Le prénom ne doit pas dépasser 50 caractères';
            if (/[0-9]/.test(v)) return 'Le prénom ne doit pas contenir de chiffres';
            if (!/^[a-zA-ZÀ-ÿ\u0600-\u06FF\s'\-]+$/.test(v)) return 'Le prénom ne doit contenir que des lettres';
            return null;
        }
    },
    email: {
        validate(v) {
            if (!v) return "L'email est obligatoire";
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(v)) return 'Email invalide';
            return null;
        }
    },
    telephone: {
        validate(v) {
            if (!v) return null;
            const clean = v.replace(/\s/g, '');
            if (!/^(\+216)?[2-9]\d{7}$/.test(clean)) return 'Numéro tunisien invalide';
            return null;
        }
    },
    adresse: {
        validate(v) {
            if (v && v.length > 200) return "L'adresse ne doit pas dépasser 200 caractères";
            return null;
        }
    },
    date_naissance: {
        validate(v) {
            if (!v) return null;
            const d = new Date(v);
            if (isNaN(d)) return 'Date invalide';
            const age = Math.floor((Date.now() - d) / (365.25 * 24 * 3600 * 1000));
            if (age < 18) return 'Vous devez avoir au moins 18 ans';
            if (age > 120) return 'Âge invalide';
            return null;
        }
    },
    password: {
        validate(v) {
            if (!v) return 'Le mot de passe est obligatoire';
            if (v.length < 8) return 'Minimum 8 caractères';
            if (!/[A-Z]/.test(v)) return 'Au moins 1 majuscule';
            if (!/[a-z]/.test(v)) return 'Au moins 1 minuscule';
            if (!/[0-9]/.test(v)) return 'Au moins 1 chiffre';
            if (!/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(v)) return 'Au moins 1 caractère spécial';
            return null;
        }
    },
    cin: {
        validate(v) {
            if (!v) return null;
            if (!/^\d{8}$/.test(v)) return 'Le CIN doit contenir 8 chiffres';
            return null;
        }
    },
    password_confirm: {
        validate(v, extra) {
            if (!v) return 'Confirmation obligatoire';
            if (extra && v !== extra) return 'Les mots de passe ne correspondent pas';
            return null;
        }
    }
};

function validateField(input, ruleName, extra) {
    const ruleKey = ruleName || input.name || input.dataset.rule;
    const rule = rules[ruleKey];
    if (!rule) return true;
    const err = rule.validate(input.value.trim(), extra);
    if (err) { showError(input, err); return false; }
    markValid(input);
    return true;
}

function showHint(input, hintText) {
    if (!input) return;
    clearHint(input);
    const hint = document.createElement('span');
    hint.className = 'field-hint';
    hint.textContent = hintText;
    if (input.nextSibling) {
        input.parentNode.insertBefore(hint, input.nextSibling);
    } else {
        input.parentNode.appendChild(hint);
    }
}

function clearHint(input) {
    if (!input) return;
    const existing = input.parentNode.querySelector('.field-hint');
    if (existing) existing.remove();
}

function updatePasswordStrength(value, barEl, labelEl) {
    if (!barEl) return;
    let score = 0;
    if (value.length >= 8) score++;
    if (/[A-Z]/.test(value)) score++;
    if (/[a-z]/.test(value)) score++;
    if (/[0-9]/.test(value)) score++;
    if (/[^A-Za-z0-9]/.test(value)) score++;

    const levels = [
        { pct: 0,   color: 'transparent', label: 'Saisissez un mot de passe' },
        { pct: 20,  color: '#ef4444',      label: 'Très faible' },
        { pct: 40,  color: '#f97316',      label: 'Faible' },
        { pct: 60,  color: '#eab308',      label: 'Moyen' },
        { pct: 80,  color: '#22c55e',      label: 'Fort' },
        { pct: 100, color: '#10b981',      label: 'Très fort' },
    ];
    const lvl = levels[score];
    barEl.style.width = lvl.pct + '%';
    barEl.style.background = lvl.color;
    if (labelEl) labelEl.textContent = lvl.label;
}

function attachLiveValidation(formEl) {
    formEl.querySelectorAll('[data-rule], [name]').forEach(input => {
        const ruleKey = input.dataset.rule || input.name;
        if (!rules[ruleKey]) return;

        input.addEventListener('blur', () => validateField(input, ruleKey));
        input.addEventListener('input', () => {
            if (input.classList.contains('input-error') || input.classList.contains('input-valid')) {
                validateField(input, ruleKey);
            }
        });
    });
}

function validateForm(formEl) {
    let valid = true;
    formEl.querySelectorAll('[data-rule], [name]').forEach(input => {
        const ruleKey = input.dataset.rule || input.name;
        if (!rules[ruleKey]) return;
        if (!validateField(input, ruleKey)) valid = false;
    });
    return valid;
}

(function initRegisterForm() {
    const form = document.querySelector('form[action="registre.php"]');
    if (!form) return;

    attachLiveValidation(form);

    const pwdInput = form.querySelector('#password, [name="password"]');
    const fillEl = document.getElementById('strength-fill');
    const textEl = document.getElementById('strength-text');

    const dateInput = form.querySelector('[name="date_naissance"]');
    if (dateInput) {
        dateInput.addEventListener('blur', () => validateField(dateInput, 'date_naissance'));
        dateInput.addEventListener('input', () => {
            if (dateInput.classList.contains('input-error') || dateInput.classList.contains('input-valid')) {
                validateField(dateInput, 'date_naissance');
            }
        });
    }

    if (pwdInput) {
        pwdInput.addEventListener('input', () => {
            updatePasswordStrength(pwdInput.value, fillEl, textEl);
            validateField(pwdInput, 'password');
        });
        pwdInput.addEventListener('blur', () => validateField(pwdInput, 'password'));
    }

    const confirmPwd = form.querySelector('#confirm-password, [name="confirm_password"]');
    if (confirmPwd && pwdInput) {
        confirmPwd.addEventListener('input', () => {
            validateField(confirmPwd, 'password_confirm', pwdInput.value);
        });
        confirmPwd.addEventListener('blur', () => {
            validateField(confirmPwd, 'password_confirm', pwdInput.value);
        });
    }

    // Disabled form submit handler in validation.js to avoid conflict with login.html AJAX
    /*
    form.addEventListener('submit', function (e) {
        ...
    });
    */
})();

(function initLoginForm() {
    const form = document.getElementById('loginform');
    if (!form) return;

    const emailInput = form.querySelector('#email, [name="email"]');
    const pwdInput = form.querySelector('#password, [name="password"]');

    if (emailInput) {
        emailInput.addEventListener('blur', () => validateField(emailInput, 'email'));
        emailInput.addEventListener('input', () => {
            if (emailInput.classList.contains('input-error') || emailInput.classList.contains('input-valid')) {
                validateField(emailInput, 'email');
            }
        });
    }

    if (pwdInput) {
        pwdInput.addEventListener('blur', () => {
            if (!pwdInput.value.trim()) showError(pwdInput, 'Le mot de passe est obligatoire');
            else markValid(pwdInput);
        });
        pwdInput.addEventListener('input', () => {
            if (pwdInput.classList.contains('input-error')) {
                if (!pwdInput.value.trim()) showError(pwdInput, 'Le mot de passe est obligatoire');
                else markValid(pwdInput);
            }
        });
    }

    // Disabled form submit handler in validation.js to avoid conflict with login.html AJAX
    /*
    form.addEventListener('submit', function (e) {
        ...
    });
    */
})();

(function initProfileForm() {
    const saveBtn = document.getElementById('saveProfile');
    if (!saveBtn) return;

    const fields = [
        { id: 'nom', rule: 'nom' },
        { id: 'prenom', rule: 'prenom' },
        { id: 'email', rule: 'email' },
        { id: 'phone', rule: 'telephone' },
        { id: 'cin', rule: 'cin' }
    ];

    fields.forEach(({ id, rule }) => {
        const el = document.getElementById(id);
        if (!el) return;
        el.addEventListener('blur', () => validateField(el, rule));
        el.addEventListener('input', () => {
            if (el.classList.contains('input-error') || el.classList.contains('input-valid')) {
                validateField(el, rule);
            }
        });
    });

    const newPwd = document.getElementById('new-password');
    const pwdBar = document.querySelector('.pwd-strength-fill');
    const pwdLabel = document.querySelector('.pwd-strength-label');

    if (newPwd) {
        newPwd.addEventListener('input', () => {
            updatePasswordStrength(newPwd.value, pwdBar, pwdLabel);
            if (newPwd.classList.contains('input-error') || newPwd.classList.contains('input-valid')) {
                validateField(newPwd, 'password');
            }
        });
        newPwd.addEventListener('blur', () => {
            if (newPwd.value) validateField(newPwd, 'password');
        });
    }

    const confirmPwd = document.getElementById('confirm-new-password');
    if (confirmPwd && newPwd) {
        confirmPwd.addEventListener('input', () => {
            validateField(confirmPwd, 'password_confirm', newPwd.value);
        });
        confirmPwd.addEventListener('blur', () => {
            validateField(confirmPwd, 'password_confirm', newPwd.value);
        });
    }
})();
