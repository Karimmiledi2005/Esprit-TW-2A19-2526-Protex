<?php
/**
 * MODULE 8 — RC5 — Page de satisfaction après clôture de réclamation
 */

session_start();
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../helpers/SessionGuard.php';
require_once __DIR__ . '/../../helpers/RoleHelper.php';

$idReclamation = (int)($_GET['id'] ?? 0);
$token = $_GET['token'] ?? '';
$db = config::getConnexion();

if ($idReclamation <= 0) {
    http_response_code(400);
    exit('Réclamation invalide');
}

$stmt = $db->prepare('SELECT r.id_reclamation, r.id_client, r.objet, r.statut, u.email, u.nom, u.prenom FROM reclamation r JOIN `user` u ON r.id_client = u.id_user WHERE r.id_reclamation = ?');
$stmt->execute([$idReclamation]);
$reclamation = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$reclamation) {
    http_response_code(404);
    exit('Réclamation introuvable');
}

$allowed = false;
if (isset($_SESSION['user_id']) && $_SESSION['user_id'] === (int)$reclamation['id_client']) {
    $allowed = true;
}

$secretKey = defined('MAIL_SMTP_PASS') && MAIL_SMTP_PASS !== '' ? MAIL_SMTP_PASS : 'protex_assurance_secret';
$expectedToken = hash_hmac('sha256', $idReclamation . '|' . $reclamation['id_client'], $secretKey);
if (!$allowed && hash_equals($expectedToken, $token)) {
    $allowed = true;
}

if (!$allowed) {
    http_response_code(403);
    exit('Accès non autorisé');
}

$stmt = $db->prepare('SELECT note, commentaire FROM reclamation_satisfaction WHERE id_reclamation = ?');
$stmt->execute([$idReclamation]);
$existing = $stmt->fetch(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Votre avis — Réclamation Protex</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background: #f8fafc; }
        .card { border-radius: 20px; border: none; box-shadow: 0 20px 60px rgba(15, 23, 42, 0.08); }
        .star-btn { background: transparent; border: none; cursor: pointer; color: #cbd5e1; font-size: 40px; transition: color 0.2s; }
        .star-btn.active, .star-btn:hover { color: #f59e0b; }
        .form-control, .form-select, .btn { border-radius: 14px; }
        .review-header { font-size: 18px; font-weight: 700; }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card p-4">
                    <div class="mb-4">
                        <h1 class="h3">Comment avez-vous trouvé la prise en charge de votre réclamation ?</h1>
                        <p class="text-muted">Réclamation : <strong><?php echo htmlspecialchars($reclamation['objet']); ?></strong></p>
                    </div>

                    <div class="mb-4">
                        <div class="d-flex align-items-center gap-2">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <button type="button" class="star-btn" data-value="<?php echo $i; ?>" aria-label="<?php echo $i; ?> étoiles">★</button>
                            <?php endfor; ?>
                        </div>
                        <input type="hidden" id="rating" value="<?php echo (int)$existing['note']; ?>">
                    </div>

                    <div class="mb-4">
                        <label for="commentaire" class="form-label">Votre commentaire (optionnel)</label>
                        <textarea id="commentaire" class="form-control" rows="5"><?php echo htmlspecialchars($existing['commentaire'] ?? ''); ?></textarea>
                    </div>

                    <button id="submitBtn" class="btn btn-primary btn-lg w-100">Envoyer mon avis</button>
                    <div id="statusMsg" class="mt-3"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const stars = document.querySelectorAll('.star-btn');
        const ratingInput = document.getElementById('rating');
        const commentaire = document.getElementById('commentaire');
        const submitBtn = document.getElementById('submitBtn');
        const statusMsg = document.getElementById('statusMsg');

        function paintStars(value) {
            stars.forEach(star => {
                star.classList.toggle('active', Number(star.dataset.value) <= value);
            });
        }

        stars.forEach(star => {
            star.addEventListener('click', () => {
                const value = Number(star.dataset.value);
                ratingInput.value = value;
                paintStars(value);
            });
        });

        paintStars(ratingInput.value);

        submitBtn.addEventListener('click', async () => {
            const note = Number(ratingInput.value);
            if (note < 1 || note > 5) {
                statusMsg.innerHTML = '<div class="alert alert-warning">Veuillez sélectionner une note.</div>';
                return;
            }
            submitBtn.disabled = true;
            submitBtn.textContent = 'Enregistrement...';

            const formData = new URLSearchParams();
            formData.append('id_reclamation', '<?php echo $idReclamation; ?>');
            formData.append('note', note);
            formData.append('commentaire', commentaire.value.trim());

            const response = await fetch('../../api.php?action=save_satisfaction', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                body: formData.toString()
            });
            const data = await response.json();
            if (data.success) {
                statusMsg.innerHTML = '<div class="alert alert-success">Merci ! Votre avis a bien été enregistré.</div>';
                submitBtn.textContent = 'Enregistré';
            } else {
                statusMsg.innerHTML = '<div class="alert alert-danger">' + (data.message || 'Erreur lors de l\'enregistrement.') + '</div>';
                submitBtn.disabled = false;
                submitBtn.textContent = 'Envoyer mon avis';
            }
        });
    </script>
</body>
</html>
