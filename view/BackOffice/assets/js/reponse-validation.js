// reponse-validation.js — validation des formulaires de réponse réclamation
(function() {
    const forms = document.querySelectorAll('form[data-validate="reponse"]');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const textarea = form.querySelector('textarea[name="contenu"], textarea[name="message"]');
            if (textarea && textarea.value.trim().length < 5) {
                e.preventDefault();
                alert('La réponse doit contenir au moins 5 caractères.');
                textarea.focus();
            }
        });
    });
})();
