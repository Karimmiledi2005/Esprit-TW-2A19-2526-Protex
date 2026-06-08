<?php
require_once __DIR__ . '/../../bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
RoleHelper::requireRole(['superadmin', 'admin', 'agent']);

try {
    $reclamation_id = (int)($_POST['reclamation_id'] ?? 0);
    if (!$reclamation_id) {
        throw new Exception('ID réclamation manquant.');
    }

    $ctrl = new ReponseController();
    $stmt = $ctrl->getDb()->prepare("SELECT objet, description, type FROM reclamation WHERE id = ?");
    $stmt->execute([$reclamation_id]);
    $rec = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$rec) {
        throw new Exception('Réclamation introuvable.');
    }

    // Utilisation de GROQ car présent dans le .env, ou Anthropic si configuré.
    $apiKey = $_ENV['ANTHROPIC_API_KEY'] ?? getenv('ANTHROPIC_API_KEY') ?? '';
    $isGroq = false;

    if (!$apiKey) {
        $apiKey = $_ENV['GROQ_API_KEY'] ?? getenv('GROQ_API_KEY') ?? '';
        $isGroq = !!$apiKey;
    }

    if (!$apiKey) {
        throw new Exception('Clé API (Anthropic ou Groq) non configurée.');
    }

    $url = $isGroq ? 'https://api.groq.com/openai/v1/chat/completions' : 'https://api.anthropic.com/v1/messages';
    
    $prompt = 'Tu es un agent d\'assurance expert. Génère 3 suggestions de réponse courtes, professionnelles et empathiques pour cette réclamation client. 
    Réponds UNIQUEMENT avec un tableau JSON de chaînes de caractères, sans texte avant ni après. 
    Exemple: ["suggestion1", "suggestion2", "suggestion3"]
    
    Objet: ' . ($rec['objet'] ?? '—') . '
    Type: ' . ($rec['type'] ?? '—') . '
    Description: ' . ($rec['description'] ?? '—');

    if ($isGroq) {
        $payload = json_encode([
            'model' => 'llama-3.1-8b-instant',
            'messages' => [['role' => 'user', 'content' => $prompt]],
            'temperature' => 0.7
        ]);
    } else {
        $payload = json_encode([
            'model' => 'claude-3-sonnet-20240229',
            'max_tokens' => 1024,
            'messages' => [['role' => 'user', 'content' => $prompt]],
            'anthropic_version' => '2023-06-01'
        ]);
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            $isGroq ? "Authorization: Bearer $apiKey" : "x-api-key: $apiKey",
            !$isGroq ? 'anthropic-version: 2023-06-01' : ''
        ]
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        throw new Exception('Erreur API (' . $httpCode . ') : ' . $response);
    }

    $data = json_decode($response, true);
    
    if ($isGroq) {
        $content = $data['choices'][0]['message']['content'] ?? '[]';
    } else {
        $content = $data['content'][0]['text'] ?? '[]';
    }

    // Nettoyage si l'IA a ajouté des balises markdown
    $content = preg_replace('/```json\s*|\s*```/', '', $content);
    $suggestions = json_decode(trim($content), true);

    if (!is_array($suggestions)) {
        $suggestions = [];
    }

    echo json_encode(['success' => true, 'suggestions' => $suggestions]);

} catch (Exception $e) {
    error_log('suggest_response error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}
