<?php
/**
 * model/Message.php
 * Système de messagerie interne BackOffice — Protex 2026
 * Utilise la table existante `user`
 */

class Message
{
    // ═══ USERS ═══

    public static function getAllUsers(PDO $db): array
    {
        $stmt = $db->query("
            SELECT id_user, nom, prenom, email, role, telephone,
                   statut, last_login, avatar
            FROM user
            WHERE statut = 'actif' AND role IN ('admin', 'agent')
            ORDER BY
                CASE role WHEN 'admin' THEN 1 WHEN 'agent' THEN 2 ELSE 3 END,
                nom ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getUserById(PDO $db, int $id): ?array
    {
        $stmt = $db->prepare("SELECT * FROM user WHERE id_user = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }

    public static function getUserByEmail(PDO $db, string $email): ?array
    {
        $stmt = $db->prepare("SELECT * FROM user WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }

    public static function findUserByName(PDO $db, string $search): array
    {
        $search = '%' . $search . '%';
        $stmt = $db->prepare("
            SELECT id_user, nom, prenom, role
            FROM user
            WHERE statut = 'actif' AND role IN ('admin', 'agent')
              AND (prenom LIKE ? OR nom LIKE ?)
            LIMIT 5
        ");
        $stmt->execute([$search, $search]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ═══ CONVERSATIONS ═══

    public static function createPrivateConversation(PDO $db, int $user1, int $user2): int
    {
        $stmt = $db->prepare("
            SELECT c.id_conversation
            FROM conversations c
            JOIN conversation_participants cp1 ON cp1.id_conversation = c.id_conversation AND cp1.id_user = ?
            JOIN conversation_participants cp2 ON cp2.id_conversation = c.id_conversation AND cp2.id_user = ?
            WHERE c.type = 'prive'
            LIMIT 1
        ");
        $stmt->execute([$user1, $user2]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            return (int)$existing['id_conversation'];
        }

        $db->beginTransaction();
        try {
            $stmt = $db->prepare("INSERT INTO conversations (nom, type, cree_par) VALUES (NULL, 'prive', ?)");
            $stmt->execute([$user1]);
            $convId = (int)$db->lastInsertId();

            $stmt = $db->prepare("INSERT INTO conversation_participants (id_conversation, id_user) VALUES (?, ?), (?, ?)");
            $stmt->execute([$convId, $user1, $convId, $user2]);

            $db->commit();
            return $convId;
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public static function createGroupConversation(PDO $db, string $nom, int $creePar, array $participantIds): int
    {
        $db->beginTransaction();
        try {
            $stmt = $db->prepare("INSERT INTO conversations (nom, type, cree_par) VALUES (?, 'groupe', ?)");
            $stmt->execute([$nom, $creePar]);
            $convId = (int)$db->lastInsertId();

            $ids = array_unique(array_merge([$creePar], $participantIds));
            $values = [];
            $params = [];
            foreach ($ids as $uid) {
                $values[] = '(?, ?)';
                $params[] = $convId;
                $params[] = $uid;
            }
            $sql = "INSERT INTO conversation_participants (id_conversation, id_user) VALUES " . implode(', ', $values);
            $stmt = $db->prepare($sql);
            $stmt->execute($params);

            $stmt = $db->prepare("INSERT INTO messages_admin (id_conversation, id_expediteur, contenu, type_message) VALUES (?, ?, ?, 'systeme')");
            $stmt->execute([$convId, $creePar, "Conversation de groupe créée"]);

            $db->commit();
            return $convId;
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public static function getUserConversations(PDO $db, int $userId): array
    {
        $stmt = $db->prepare("
            SELECT c.*,
                   (SELECT COUNT(*) FROM messages_admin m
                    WHERE m.id_conversation = c.id_conversation
                    AND m.id_expediteur != ?
                    AND (m.date_envoi > cp.dernier_message_lu OR cp.dernier_message_lu IS NULL)
                   ) AS non_lus,
                   (SELECT COUNT(*) FROM messages_admin m WHERE m.id_conversation = c.id_conversation) AS total_messages,
                   (SELECT CONCAT(u.prenom, ' ', u.nom) FROM conversation_participants cp2
                    JOIN user u ON u.id_user = cp2.id_user
                    WHERE cp2.id_conversation = c.id_conversation AND cp2.id_user != ?
                    AND c.type = 'prive'
                    LIMIT 1
                   ) AS autre_nom,
                   (SELECT u.role FROM conversation_participants cp2
                    JOIN user u ON u.id_user = cp2.id_user
                    WHERE cp2.id_conversation = c.id_conversation AND cp2.id_user != ?
                    AND c.type = 'prive'
                    LIMIT 1
                   ) AS autre_role,
                   (SELECT m.contenu FROM messages_admin m
                    WHERE m.id_conversation = c.id_conversation
                    ORDER BY m.date_envoi DESC LIMIT 1
                   ) AS dernier_message,
                   (SELECT m.date_envoi FROM messages_admin m
                    WHERE m.id_conversation = c.id_conversation
                    ORDER BY m.date_envoi DESC LIMIT 1
                   ) AS dernier_message_date
            FROM conversations c
            JOIN conversation_participants cp ON cp.id_conversation = c.id_conversation AND cp.id_user = ?
            ORDER BY c.derniere_activite DESC
        ");
        $stmt->execute([$userId, $userId, $userId, $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ═══ MESSAGES ═══

    public static function sendMessage(PDO $db, int $convId, int $expediteur, string $contenu, string $type = 'texte', ?string $fichierUrl = null, ?int $dureeAudio = null): int
    {
        $mentions = [];
        if (preg_match_all('/@(\w+)/', $contenu, $matches)) {
            foreach ($matches[1] as $prenom) {
                $stmt = $db->prepare("SELECT id_user FROM user WHERE LOWER(prenom) LIKE ? AND statut = 'actif' AND role IN ('admin','agent') LIMIT 1");
                $stmt->execute([strtolower($prenom) . '%']);
                $u = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($u) $mentions[] = (int)$u['id_user'];
            }
        }

        $db->beginTransaction();
        try {
            $stmt = $db->prepare("INSERT INTO messages_admin (id_conversation, id_expediteur, contenu, type_message, fichier_url, duree_audio) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$convId, $expediteur, $contenu, $type, $fichierUrl, $dureeAudio]);
            $msgId = (int)$db->lastInsertId();

            $stmt = $db->prepare("UPDATE conversation_participants SET dernier_message_lu = NOW() WHERE id_conversation = ? AND id_user = ?");
            $stmt->execute([$convId, $expediteur]);

            $stmt = $db->prepare("UPDATE conversations SET derniere_activite = NOW() WHERE id_conversation = ?");
            $stmt->execute([$convId]);

            if (!empty($mentions)) {
                $stmtM = $db->prepare("INSERT INTO message_mentions (id_message, id_user_mentionne) VALUES (?, ?)");
                foreach ($mentions as $uid) {
                    if ($uid != $expediteur) {
                        $stmtM->execute([$msgId, $uid]);
                    }
                }
            }

            $db->commit();
            return $msgId;
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public static function getMessages(PDO $db, int $convId, int $limit = 100): array
    {
        $stmt = $db->prepare("
            SELECT m.*,
                   u.nom, u.prenom, u.role, u.avatar
            FROM messages_admin m
            JOIN user u ON u.id_user = m.id_expediteur
            WHERE m.id_conversation = ?
            ORDER BY m.date_envoi ASC
            LIMIT ?
        ");
        $stmt->execute([$convId, $limit]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$row) {
            $stmtM = $db->prepare("
                SELECT u2.id_user FROM message_mentions mm
                JOIN user u2 ON u2.id_user = mm.id_user_mentionne
                WHERE mm.id_message = ?
            ");
            $stmtM->execute([$row['id_message']]);
            $ids = $stmtM->fetchAll(PDO::FETCH_COLUMN);
            $row['mentionnes'] = !empty($ids) ? json_encode($ids) : null;
        }

        return $rows;
    }

    public static function markAsRead(PDO $db, int $convId, int $userId): bool
    {
        $stmt = $db->prepare("UPDATE conversation_participants SET dernier_message_lu = NOW() WHERE id_conversation = ? AND id_user = ?");
        return $stmt->execute([$convId, $userId]);
    }

    public static function getUnreadCount(PDO $db, int $userId): int
    {
        $stmt = $db->prepare("
            SELECT COALESCE(SUM(
                (SELECT COUNT(*) FROM messages_admin m
                 WHERE m.id_conversation = cp.id_conversation
                 AND m.id_expediteur != ?
                 AND (m.date_envoi > cp.dernier_message_lu OR cp.dernier_message_lu IS NULL))
            ), 0) AS total
            FROM conversation_participants cp
            WHERE cp.id_user = ?
        ");
        $stmt->execute([$userId, $userId]);
        return (int)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
    }

    // ═══ MENTIONS / APPELS ═══

    public static function getActiveMentions(PDO $db, int $userId): array
    {
        $stmt = $db->prepare("
            SELECT mm.*, m.contenu, m.date_envoi,
                   u.nom AS expediteur_nom, u.prenom AS expediteur_prenom, u.role AS expediteur_role,
                   c.id_conversation
            FROM message_mentions mm
            JOIN messages_admin m ON m.id_message = mm.id_message
            JOIN user u ON u.id_user = m.id_expediteur
            JOIN conversations c ON c.id_conversation = m.id_conversation
            WHERE mm.id_user_mentionne = ? AND mm.est_resolu = 0
            ORDER BY mm.date_mention DESC
            LIMIT 20
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function resolveMention(PDO $db, int $mentionId): bool
    {
        $stmt = $db->prepare("UPDATE message_mentions SET est_resolu = 1 WHERE id_mention = ?");
        return $stmt->execute([$mentionId]);
    }

    public static function getActiveMentionsCount(PDO $db, int $userId): int
    {
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM message_mentions WHERE id_user_mentionne = ? AND est_resolu = 0");
        $stmt->execute([$userId]);
        return (int)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
    }
}
