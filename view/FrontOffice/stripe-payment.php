<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../model/Paiement.php';
require_once __DIR__ . '/../../model/Offre.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$offreId = (int)($_GET['offre'] ?? 0);
if ($offreId <= 0) { header('Location: index.php'); exit; }

$db = config::getConnexion();
$stmt = $db->prepare("SELECT * FROM offre WHERE id_offre = ? AND statut = 'active'");
$stmt->execute([$offreId]);
$offre = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$offre) { header('Location: index.php?erreur=offre_invalide'); exit; }

$montant   = (float)($_GET['montant'] ?? $_POST['montant'] ?? $offre['prix_mensuel']);
$periode   = $_GET['periode'] ?? $_POST['periode'] ?? 'mensuel';
$devisId   = (int)($_GET['devis'] ?? $_POST['devis'] ?? 0);
$codePromo = trim($_GET['promo'] ?? $_POST['code_promo'] ?? '');

$montantOriginal = ($periode === 'annuel') ? (float)$offre['prix_annuel'] : (float)$offre['prix_mensuel'];
$hasDiscount = ($montant < $montantOriginal);

$stripeConfig = require __DIR__ . '/../../controller/stripe_config.php';
$publishableKey = $stripeConfig['publishable_key'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paiement — <?= htmlspecialchars($offre['nom_offre']) ?> — Protex</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DM Sans', 'Segoe UI', sans-serif; background: #0e1c33; color: #fff; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .payment-container { max-width: 480px; width: 100%; }
        .payment-card { background: rgba(255,255,255,.04); border: 1px solid rgba(255,255,255,.08); border-radius: 24px; padding: 32px; backdrop-filter: blur(20px); }
        .payment-header { text-align: center; margin-bottom: 28px; }
        .payment-header h1 { font-size: 22px; font-weight: 800; margin-bottom: 8px; }
        .payment-header .montant { font-size: 36px; font-weight: 800; color: #FF6B1A; }
        .payment-header .montant span { font-size: 16px; color: var(--text-secondary); font-weight: 500; }
        .payment-header .offre-name { font-size: 14px; color: #8899aa; margin-top: 6px; }
        .payment-header .discount { font-size: 13px; color: #86efac; margin-top: 4px; }
        .payment-header .discount .original { text-decoration: line-through; color: #556677; margin-right: 6px; }

        .promo-badge { display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; border-radius: 999px; background: rgba(16,185,129,.1); border: 1px solid rgba(16,185,129,.2); color: #86efac; font-size: 12px; font-weight: 700; margin-top: 10px; font-family: monospace; letter-spacing: 1px; }

        .back-link { display: inline-flex; align-items: center; gap: 6px; color: #8899aa; text-decoration: none; font-size: 13px; margin-bottom: 16px; transition: .2s; }
        .back-link:hover { color: #FF6B1A; }

        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; font-size: 12px; font-weight: 700; color: #8899aa; margin-bottom: 8px; text-transform: uppercase; letter-spacing: .5px; }
        .form-group input { width: 100%; padding: 12px 16px; border-radius: 12px; border: 1px solid rgba(255,255,255,.1); background: rgba(255,255,255,.05); color: #fff; font-size: 14px; outline: none; transition: .2s; }
        .form-group input:focus { border-color: #FF6B1A; box-shadow: 0 0 0 3px rgba(255,107,26,.15); }

        .stripe-element { padding: 12px 16px; border-radius: 12px; border: 1px solid rgba(255,255,255,.1); background: rgba(255,255,255,.05); min-height: 44px; display: flex; align-items: center; }
        .stripe-element.StripeElement--focus { border-color: #FF6B1A; box-shadow: 0 0 0 3px rgba(255,107,26,.15); }

        .btn-pay { width: 100%; padding: 14px; border-radius: 14px; border: none; background: linear-gradient(135deg, #FF6B1A, #ff8c42); color: #fff; font-size: 16px; font-weight: 700; cursor: pointer; margin-top: 8px; transition: .2s; display: flex; align-items: center; justify-content: center; gap: 10px; }
        .btn-pay:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(255,107,26,.3); }
        .btn-pay:disabled { opacity: .5; cursor: not-allowed; transform: none; }

        .spinner { width: 18px; height: 18px; border: 2px solid rgba(255,255,255,.3); border-top-color: #fff; border-radius: 50%; animation: spin .6s linear infinite; display: none; }
        @keyframes spin { to { transform: rotate(360deg); } }

        .result-message { display: none; padding: 14px; border-radius: 12px; margin-top: 16px; text-align: center; font-size: 14px; font-weight: 600; }
        .result-message.success { background: rgba(16,185,129,.12); color: #86efac; border: 1px solid rgba(16,185,129,.2); }
        .result-message.error { background: rgba(239,68,68,.12); color: #fca5a5; border: 1px solid rgba(239,68,68,.2); }

        .secure-badge { text-align: center; margin-top: 20px; font-size: 12px; color: #556677; display: flex; align-items: center; justify-content: center; gap: 6px; }
        .secure-badge i { color: #10b981; }

        #payment-form { display: block; }
        #payment-result { display: none; text-align: center; }
        #payment-result .icon { font-size: 64px; margin-bottom: 16px; }
        #payment-result h2 { font-size: 22px; font-weight: 800; margin-bottom: 10px; }
        #payment-result p { color: #8899aa; margin-bottom: 6px; }
        #payment-result .ref { font-family: monospace; background: rgba(255,255,255,.05); padding: 6px 14px; border-radius: 8px; display: inline-block; margin: 10px 0; }
        #payment-result .btn-back { display: inline-flex; align-items: center; gap: 8px; padding: 12px 24px; border-radius: 12px; background: rgba(255,255,255,.08); color: #fff; text-decoration: none; font-weight: 600; margin-top: 20px; border: 1px solid rgba(255,255,255,.1); transition: .2s; }
        #payment-result .btn-back:hover { background: rgba(255,255,255,.12); }
    </style>
</head>
<body>
    <div class="payment-container">
        <!-- FORMULAIRE DE PAIEMENT -->
        <div class="payment-card" id="payment-form">
            <a href="javascript:history.back()" class="back-link"><i class="bi bi-arrow-left"></i> Retour</a>
            <div class="payment-header">
                <h1><i class="bi bi-stripe" style="color:#635BFF;"></i> Paiement sécurisé</h1>
                <div class="montant">
                    <?php if ($hasDiscount): ?>
                        <span class="original"><?= number_format($montantOriginal, 2) ?> €</span>
                    <?php endif; ?>
                    <?= number_format($montant, 2) ?> <span>EUR</span>
                </div>
                <div class="offre-name"><?= htmlspecialchars($offre['nom_offre']) ?> — <?= ucfirst($periode) ?></div>
                <?php if ($hasDiscount): ?>
                    <div class="discount">
                        <?php $pct = round((1 - $montant / $montantOriginal) * 100); ?>
                        Vous économisez <?= $pct ?>% sur cette offre
                    </div>
                <?php endif; ?>
                <?php if ($codePromo !== ''): ?>
                    <div class="promo-badge"><i class="bi bi-ticket-perforated-fill"></i> <?= htmlspecialchars($codePromo) ?></div>
                <?php endif; ?>
            </div>

            <form id="checkout-form">
                <div class="form-group">
                    <label>Nom complet</label>
                    <input type="text" id="client-name" placeholder="Nom Prénom" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" id="client-email" placeholder="votre@email.com" required>
                </div>
                <div class="form-group">
                    <label>Carte bancaire</label>
                    <div id="card-element" class="stripe-element"></div>
                    <div id="card-errors" style="color: #fca5a5; font-size: 12px; margin-top: 8px; display: none;"></div>
                </div>

                <button type="submit" class="btn-pay" id="pay-btn">
                    <span class="spinner" id="pay-spinner"></span>
                    <span id="pay-text">Payer <?= number_format($montant, 2) ?> EUR</span>
                </button>
            </form>

            <div class="secure-badge">
                <i class="bi bi-shield-lock-fill"></i>
                Paiement sécurisé par Stripe — Vos données sont chiffrées
            </div>
        </div>

        <!-- RÉSULTAT -->
        <div class="payment-card" id="payment-result" style="display:none;">
            <div class="icon" id="result-icon"></div>
            <h2 id="result-title"></h2>
            <p id="result-subtitle"></p>
            <div class="ref" id="result-ref"></div>
            <p id="result-details"></p>
            <a href="<?= BASE_URL ?>/view/FrontOffice/paiement.php?id_offre=<?= $offreId ?>&success=1&reference=<?= isset($reference) ? $reference : '' ?>" class="btn-back">
                <i class="bi bi-arrow-left"></i> Retour au détail
            </a>
        </div>
    </div>

    <script src="https://js.stripe.com/v3/"></script>
    <script>
        const stripe = Stripe('<?= $publishableKey ?>');
        const elements = stripe.elements();
        const BASE = <?= json_encode(defined('BASE_URL') ? BASE_URL : '') ?>;

        const card = elements.create('card', {
            style: {
                base: {
                    color: '#fff',
                    fontFamily: '"DM Sans", sans-serif',
                    fontSize: '15px',
                    '::placeholder': { color: '#556677' }
                }
            },
            hidePostalCode: true
        });
        card.mount('#card-element');

        card.on('change', (e) => {
            const errDiv = document.getElementById('card-errors');
            if (e.error) {
                errDiv.textContent = e.error.message;
                errDiv.style.display = 'block';
            } else {
                errDiv.style.display = 'none';
            }
        });

        document.getElementById('checkout-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('pay-btn');
            const spinner = document.getElementById('pay-spinner');
            const text = document.getElementById('pay-text');

            btn.disabled = true;
            spinner.style.display = 'block';
            text.textContent = 'Traitement en cours...';

            try {
                // 1. Créer le PaymentIntent côté serveur
                const res = await fetch(BASE + '/controller/StripePaymentController.php?action=creer_session', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        offre_id: <?= $offreId ?>,
                        montant: <?= $montant ?>,
                        nom: document.getElementById('client-name').value,
                        email: document.getElementById('client-email').value,
                        periode: '<?= addslashes($periode) ?>',
                        devis_id: <?= $devisId ?: 'null' ?>,
                        code_promo: '<?= addslashes($codePromo) ?>'
                    })
                });

                const data = await res.json();

                if (data.error) {
                    showError(data.error);
                    return;
                }

                // 2. Confirmer le paiement avec Stripe Elements
                const { error, paymentIntent } = await stripe.confirmCardPayment(data.clientSecret, {
                    payment_method: { card: card }
                });

                if (error) {
                    showError(error.message);
                    return;
                }

                    if (paymentIntent.status === 'succeeded') {
                        // 3. Confirmer côté serveur
                        await fetch(BASE + '/controller/StripePaymentController.php?action=confirmer', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ paiement_id: data.paiement_id, payment_intent_id: paymentIntent.id })
                        });

                        showSuccess(data.reference, <?= $montant ?>, <?= $offreId ?>);
                    }
            } catch (err) {
                showError('Erreur réseau. Vérifiez votre connexion.');
            } finally {
                btn.disabled = false;
                spinner.style.display = 'none';
                text.textContent = 'Payer <?= number_format($montant, 2) ?> EUR';
            }
        });

        function showSuccess(ref, montant, offreId) {
            setTimeout(() => {
                window.location.href = BASE + '/view/FrontOffice/paiement.php?id_offre=' + offreId + '&success=1&reference=' + ref;
            }, 1500);
            document.getElementById('payment-form').style.display = 'none';
            const result = document.getElementById('payment-result');
            result.style.display = 'block';
            document.getElementById('result-icon').innerHTML = '<i class="bi bi-check-circle-fill" style="color:#86efac;"></i>';
            document.getElementById('result-title').textContent = 'Paiement réussi!';
            document.getElementById('result-subtitle').textContent = 'Redirection vers votre tableau de bord...';
            document.getElementById('result-ref').textContent = ref;
            document.getElementById('result-details').textContent = `Montant: ${montant.toFixed(2)} EUR`;
        }

        function showError(msg) {
            document.getElementById('payment-form').style.display = 'none';
            const result = document.getElementById('payment-result');
            result.style.display = 'block';
            document.getElementById('result-icon').innerHTML = '<i class="bi bi-x-circle-fill" style="color:#fca5a5;"></i>';
            document.getElementById('result-title').textContent = 'Paiement échoué';
            document.getElementById('result-subtitle').textContent = msg;
            document.getElementById('result-ref').textContent = '';
            document.getElementById('result-details').textContent = 'Veuillez réessayer ou utiliser une autre carte.';
        }
    </script>
</body>
</html>



