// =============================================
//  admin.js — Toutes les actions admin
// =============================================

// ===== CHARGEMENT DES AGENCES DANS LE SELECT =====
function loadAgences() {
    const sel = document.getElementById('fAgence');
    if (!sel) return;
    fetch('get_agences.php')
        .then(res => res.json())
        .then(data => {
            if (!data.success || !Array.isArray(data.data)) return;
            const current = sel.value;
            sel.innerHTML = '<option value="">— Choisir une agence —</option>';
            data.data.forEach(ag => {
                const opt = document.createElement('option');
                opt.value = ag.id_agence;
                opt.textContent = ag.nom_agence + (ag.statut === 'inactive' ? ' (inactive)' : '');
                if (ag.statut === 'inactive') opt.disabled = true;
                sel.appendChild(opt);
            });
            if (current) sel.value = current;
        })
        .catch(() => {});
}


function saveUserAdd() {
    const data = new FormData();
    const role = document.getElementById('fRole').value;

    data.append('nom',       document.getElementById('fNom').value.trim());
    data.append('prenom',    document.getElementById('fPrenom').value.trim());
    data.append('email',     document.getElementById('fEmail').value.trim());
    data.append('telephone', document.getElementById('fTel').value.trim());
    data.append('cin',       document.getElementById('fCin').value.trim());
    data.append('role',      role);
    data.append('statut',    document.getElementById('fStatut').value);
    data.append('password',  document.getElementById('fPassword').value);
    data.append('csrf_token', typeof csrfToken !== 'undefined' ? csrfToken : '');

    if (role === 'admin')  data.append('niveau_acces',  document.getElementById('fNiveau').value || 1);
    if (role === 'admin' || role === 'agent' || role === 'client')  data.append('id_agence', document.getElementById('fAgence').value);
    if (role === 'agent')  data.append('salaire', document.getElementById('fSalaire').value);
    // CLIENT : numero_client est généré automatiquement côté serveur, ne pas l'envoyer

    fetch('admin_add_user.php', { method: 'POST', body: data })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                showToast('Utilisateur ajouté avec succès', 'success');
                closeModal('modalAdd');
                loadUsers();
                loadStats();
            } else {
                alert('Erreur : ' + res.message);
            }
        })
        .catch(err => console.error(err));
}

function saveUser() {
    if (!validate()) return;
    const btn = document.getElementById('btnSaveUser');
    const orig = btn.innerHTML;
    btn.innerHTML = '<i class="bi bi-arrow-repeat spin"></i> Enregistrement...';
    btn.disabled = true;

    if (editingId) {
        const role = document.getElementById('fRole').value;
        const data = new FormData();
        data.append('id_user',   editingId);
        data.append('nom',       document.getElementById('fNom').value.trim());
        data.append('prenom',    document.getElementById('fPrenom').value.trim());
        data.append('email',     document.getElementById('fEmail').value.trim());
        data.append('telephone', document.getElementById('fTel').value.trim());
        data.append('cin',       document.getElementById('fCin').value.trim());
        data.append('role',      role);
        data.append('statut',    document.getElementById('fStatut').value);
        data.append('csrf_token', typeof csrfToken !== 'undefined' ? csrfToken : '');

        if (role === 'admin')  data.append('niveau_acces',  document.getElementById('fNiveau').value || 1);
        if (role === 'admin' || role === 'agent' || role === 'client')  data.append('id_agence', document.getElementById('fAgence').value);
        if (role === 'agent')  data.append('salaire', document.getElementById('fSalaire').value);
        // CLIENT : numero_client ne peut pas être modifié

        fetch('admin_update_user.php', { method: 'POST', body: data })
            .then(res => res.json())
            .then(res => {
                btn.innerHTML = orig; btn.disabled = false;
                if (res.success) {
                    showToast('Utilisateur modifié avec succès', 'success');
                    closeModal('modalAdd');
                    loadUsers();
                    loadStats();
                } else {
                    alert('Erreur : ' + res.message);
                }
            })
            .catch(err => { btn.innerHTML = orig; btn.disabled = false; console.error(err); });
    } else {
        saveUserAdd();
        return;
    }
}

// ===== BLOQUER / DÉBLOQUER =====
function toggleStatut(id) {
    const data = new FormData();
    data.append('id_user', id);
    data.append('csrf_token', typeof csrfToken !== 'undefined' ? csrfToken : '');

    const toggleInput = document.getElementById(`toggle-${id}`);
    const originalState = !toggleInput.checked; // L'état avant le changement manuel

    fetch('admin_toggle_statut.php', { method: 'POST', body: data })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                const u = users.find(u => String(u.id) === String(id));
                if (u) {
                    u.statut = res.statut;
                    // Mettre à jour le badge sans tout recharger pour éviter le scroll/focus reset
                    const row = toggleInput.closest('tr');
                    if (row) {
                        const badgeCell = row.querySelector('td:nth-child(6)');
                        if (badgeCell) {
                            badgeCell.innerHTML = `<span class="badge badge-${u.statut}">
                                <i class="bi bi-${u.statut === 'actif' ? 'check-circle' : 'slash-circle'}"></i>
                                ${u.statut.charAt(0).toUpperCase() + u.statut.slice(1)}
                            </span>`;
                        }
                    }
                }
                showToast(res.statut === 'actif' ? 'Compte débloqué' : 'Compte bloqué',
                          res.statut === 'actif' ? 'success' : 'warning');
                loadStats();
            } else {
                showToast('Erreur : ' + res.message, 'danger');
                // Remettre l'ancien état si erreur
                toggleInput.checked = originalState;
            }
        })
        .catch(err => {
            console.error(err);
            showToast('Erreur de connexion', 'danger');
            toggleInput.checked = originalState;
        });
}

// ===== SUPPRIMER =====
function deleteUser(id) {
    const u = users.find(u => String(u.id) === String(id));
    if (!u) return;
    deletingId = id;
    document.getElementById('deleteMsg').innerHTML =
        `Vous êtes sur le point de supprimer <span class="delete-name">${u.prenom} ${u.nom}</span>.<br>Cette action est irréversible.`;
    openModal('modalDelete');
    loadStats();
}

function confirmDelete() {
    const btn = document.getElementById('btnConfirmDelete');
    const orig = btn.innerHTML;
    btn.innerHTML = '<i class="bi bi-arrow-repeat spin"></i> Suppression...';
    btn.disabled = true;

    const data = new FormData();
    data.append('id_user', deletingId);
    data.append('csrf_token', typeof csrfToken !== 'undefined' ? csrfToken : '');

    fetch('admin_delete_user.php', { method: 'POST', body: data })
        .then(res => res.json())
        .then(res => {
            btn.innerHTML = orig; btn.disabled = false;
            if (res.success) {
                users = users.filter(u => u.id !== deletingId);
                closeModal('modalDelete');
                showToast('Utilisateur supprimé', 'danger');
                render();
            } else {
                alert('Erreur : ' + res.message);
            }
        })
        .catch(err => { btn.innerHTML = orig; btn.disabled = false; console.error(err); });
        
}
function loadStats() {
    // Construire l'URL avec le filtre de période si actif
    const params = new URLSearchParams();
    if (typeof currentPeriodDays !== 'undefined' && currentPeriodDays !== null) {
        params.set('days', currentPeriodDays);
    }
    const url = 'get_advanced_stats.php' + (params.toString() ? '?' + params.toString() : '');

    fetch(url)
        .then(res => res.json())
        .then(data => {
            if (!data.success) return;
            const s = data.data;

            function animateValue(id, end, duration = 800) {
                const el = document.getElementById(id);
                if (!el) return;
                const start = parseInt(el.textContent) || 0;
                const startTime = performance.now();
                function update(currentTime) {
                    const elapsed = currentTime - startTime;
                    const progress = Math.min(elapsed / duration, 1);
                    const easeOut = 1 - Math.pow(1 - progress, 3);
                    const current = Math.floor(start + (end - start) * easeOut);
                    el.textContent = current;
                    if (progress < 1) requestAnimationFrame(update);
                }
                requestAnimationFrame(update);
            }

            animateValue("totalUsers", s.total);
            animateValue("actifs",     s.actifs);
            animateValue("bloques",    s.bloques);
            animateValue("agents",     s.agents);

            // Taux actifs
            const tauxEl = document.getElementById("tauxActifs");
            if (tauxEl && s.total > 0) {
                tauxEl.textContent = Math.round((s.actifs / s.total) * 100);
            }

            // Nouveaux ce mois
            const newMonthEl = document.getElementById("newThisMonth");
            if (newMonthEl && s.new_this_month !== undefined) {
                newMonthEl.textContent = '+' + s.new_this_month;
            }

            // Évolution par rapport à la période précédente
            const trendEl = document.getElementById("trendNewMonth");
            if (trendEl && s.evolution !== undefined && s.evolution !== null) {
                const sign = s.evolution >= 0 ? '+' : '';
                const color = s.evolution >= 0 ? 'var(--success)' : 'var(--danger)';
                const icon = s.evolution >= 0 ? 'arrow-up' : 'arrow-down';
                trendEl.innerHTML = `<i class="bi bi-${icon}" style="color:${color}"></i> <span style="color:${color}">${sign}${s.evolution}%</span> vs période préc.`;
            }

            const admins = document.getElementById("admins");
            const clients = document.getElementById("clients");
            if (admins)  admins.textContent  = s.admins;
            if (clients) clients.textContent = s.clients;
        })
        .catch(err => console.error(err));
}