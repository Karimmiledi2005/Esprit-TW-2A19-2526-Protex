<?php

class PosteModel {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function getAllPostes(): array {
        $sql = "SELECT 
                    p.id_poste,
                    p.contenu,
                    p.date_publication,
                    p.note,
                    p.auteur,
                    p.nb_likes,
                    p.nb_commentaires,
                    p.id_agence,
                    a.nom_agence AS agence,
                    a.nom_agence
                FROM poste p
                LEFT JOIN agence a ON p.id_agence = a.id_agence
                ORDER BY p.id_poste DESC";

        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllAgences(): array {
        $sql = "SELECT id_agence, nom_agence, pays, tel, email, statut, adresse
                FROM agence
                ORDER BY nom_agence ASC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Returns only id + name for dropdown selects (lightweight).
     */
    public function getAgencesForSelect(): array {
        $sql = "SELECT id_agence, nom_agence FROM agence WHERE statut = 'active' ORDER BY nom_agence ASC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPosteById(int $id): ?array {
        $stmt = $this->pdo->prepare("SELECT * FROM poste WHERE id_poste = ?");
        $stmt->execute([$id]);
        $poste = $stmt->fetch(PDO::FETCH_ASSOC);
        return $poste ?: null;
    }

    public function createPoste(array $data): bool {
        $sql = "INSERT INTO poste (
                    contenu,
                    date_publication,
                    note,
                    auteur,
                    nb_likes,
                    nb_commentaires,
                    id_agence
                ) VALUES (
                    :contenu,
                    :date_publication,
                    :note,
                    :auteur,
                    :nb_likes,
                    :nb_commentaires,
                    :id_agence
                )";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':contenu' => trim($data['contenu'] ?? ''),
            ':date_publication' => $data['date_publication'] ?? date('Y-m-d'),
            ':note' => (isset($data['note']) && $data['note'] !== '' && $data['note'] >= 1 && $data['note'] <= 5) ? (int)$data['note'] : null,
            ':auteur' => trim($data['auteur'] ?? ''),
            ':nb_likes' => isset($data['nb_likes']) && $data['nb_likes'] !== '' ? (int)$data['nb_likes'] : 0,
            ':nb_commentaires' => isset($data['nb_commentaires']) && $data['nb_commentaires'] !== '' ? (int)$data['nb_commentaires'] : 0,
            ':id_agence' => (int)($data['id_agence'] ?? 0)
        ]);
    }

    public function updatePoste(array $data): bool {
        $sql = "UPDATE poste SET
                    contenu = :contenu,
                    date_publication = :date_publication,
                    note = :note,
                    auteur = :auteur,
                    nb_likes = :nb_likes,
                    nb_commentaires = :nb_commentaires,
                    id_agence = :id_agence
                WHERE id_poste = :id_poste";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':id_poste' => (int)$data['id_poste'],
            ':contenu' => trim($data['contenu'] ?? ''),
            ':date_publication' => $data['date_publication'] ?? date('Y-m-d'),
            ':note' => (isset($data['note']) && $data['note'] !== '' && $data['note'] >= 1 && $data['note'] <= 5) ? (int)$data['note'] : null,
            ':auteur' => trim($data['auteur'] ?? ''),
            ':nb_likes' => isset($data['nb_likes']) && $data['nb_likes'] !== '' ? (int)$data['nb_likes'] : 0,
            ':nb_commentaires' => isset($data['nb_commentaires']) && $data['nb_commentaires'] !== '' ? (int)$data['nb_commentaires'] : 0,
            ':id_agence' => (int)($data['id_agence'] ?? 0)
        ]);
    }

    public function deletePoste(int $id): bool {
        $stmt = $this->pdo->prepare("DELETE FROM poste WHERE id_poste = ?");
        return $stmt->execute([$id]);
    }

    public function getAgenceById(int $id): ?array {
        $stmt = $this->pdo->prepare("SELECT * FROM agence WHERE id_agence = ?");
        $stmt->execute([$id]);
        $agence = $stmt->fetch(PDO::FETCH_ASSOC);
        return $agence ?: null;
    }

    public function createAgence(array $data): bool {
        $sql = "INSERT INTO agence (nom_agence, pays, tel, email, statut, adresse) 
                VALUES (:nom_agence, :pays, :tel, :email, :statut, :adresse)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':nom_agence' => trim($data['nom_agence']),
            ':pays' => trim($data['pays']),
            ':tel' => trim($data['tel']),
            ':email' => trim($data['email']),
            ':statut' => $data['statut'] ?? 'active',
            ':adresse' => trim($data['adresse'] ?? '')
        ]);
    }

    public function updateAgence(array $data): bool {
        $sql = "UPDATE agence SET 
                    nom_agence = :nom_agence, 
                    pays = :pays, 
                    tel = :tel, 
                    email = :email, 
                    statut = :statut, 
                    adresse = :adresse 
                WHERE id_agence = :id_agence";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':id_agence' => (int)$data['id_agence'],
            ':nom_agence' => trim($data['nom_agence']),
            ':pays' => trim($data['pays']),
            ':tel' => trim($data['tel']),
            ':email' => trim($data['email']),
            ':statut' => $data['statut'] ?? 'active',
            ':adresse' => trim($data['adresse'] ?? '')
        ]);
    }

    public function deleteAgence(int $id): bool {
        $stmt = $this->pdo->prepare("DELETE FROM agence WHERE id_agence = ?");
        return $stmt->execute([$id]);
    }
}
?>