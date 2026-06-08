<?php
/** Local Antifraud Analysis Service */

class FraudeService
{
    // ─── Poids des 3 modules dans le score global ─────────────────────────────
    private const WEIGHT_TEXTE        = 0.70; // 70 %
    private const WEIGHT_COMPORTEMENT = 0.15; // 15 %
    private const WEIGHT_CONTRAT      = 0.15; // 15 %

    // ─── Seuils NLP ───────────────────────────────────────────────────────────
    private const SEUIL_VAGUE_FLAG  = 60;   // score vague > 60 → flag_vague = true
    private const MIN_DESC_LEN      = 20;   // moins de 20 chars → suspect immédiat

    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /** Main analysis entry point */
    public function analyser(
        int    $idSinistre,
        ?int   $idContrat,
        int    $idUser,
        string $type,
        string $description,
        ?string $photoPath = null,
        ?float $montant = null
    ): array {
        // ── 1. Analyse textuelle NLP local ────────────────────────────────────
        $analyseTexte = $this->analyserTexte($type, $description);

        // ── 2. Analyse comportementale SQL ───────────────────────────────────
        $analyseComport = $this->analyserComportement($idUser, $idContrat);

        // ── 3. Analyse contrat SQL ────────────────────────────────────────────
        $analyseContrat = $this->analyserContrat($idContrat, $idUser, $montant);

        // ── 4. Analyse Image & GPS ───────────────────────────────────────────
        $analyseImage = $this->analyserImage($photoPath, $description);

        // ── 5. Analyse Doublons ───────────────────────────────────────────────
        $analyseDoublon = $this->detecterDoublons($idSinistre, $description);

        // ── 6. Score global pondéré ───────────────────────────────────────────
        $scoreGlobal = (int)round(
            $analyseTexte['score']   * self::WEIGHT_TEXTE       +
            $analyseComport['score'] * self::WEIGHT_COMPORTEMENT +
            $analyseContrat['score'] * self::WEIGHT_CONTRAT
        );

        // Majoration si doublon (capé à 100)
        if ($analyseDoublon['score'] > 0) {
            $scoreGlobal = min(100, $scoreGlobal + $analyseDoublon['score']);
        }

        // --- RÈGLE CRITIQUE : DATE DANS LE FUTUR = FRAUDE 100% ---
        if (!empty($analyseTexte['flag_futur'])) {
            $scoreGlobal = 100;
        }

        // ── 7. Impact de la Photo (Règles métier) ────────────────────────────
        if (!$photoPath) {
            // Absence de photo : Malus léger (+15)
            $scoreGlobal = min(100, $scoreGlobal + 15);
            
            // Malus cumulatif par signal suspect détecté (+20 par signal)
            if ($analyseTexte['flag_vague']) $scoreGlobal = min(100, $scoreGlobal + 20);
            if ($analyseTexte['flag_tardif']) $scoreGlobal = min(100, $scoreGlobal + 20);
            if ($analyseTexte['flag_no_witnesses']) $scoreGlobal = min(100, $scoreGlobal + 20);
            if ($analyseContrat['flag_montant']) $scoreGlobal = min(100, $scoreGlobal + 20);
        } else {
            // Photo présente : Bonus de confiance (-20) si l'image n'est pas elle-même suspecte
            if ($analyseImage['score'] === 0) {
                $scoreGlobal = max(0, $scoreGlobal - 20);
            } else {
                // Si l'image est suspecte (ex: Photoshop), on ajoute son score de suspicion
                $scoreGlobal = min(100, $scoreGlobal + $analyseImage['score']);
            }
        }

        // ── 8. Niveau de risque ───────────────────────────────────────────────
        $niveauRisque = $this->scoreToNiveau($scoreGlobal);
        $suggestionIa = $this->niveauToSuggestion($niveauRisque);

        // ── 9. Recommandation — moteur de règles expert local ────────────────
        $recommandation = $this->genererRecommandation(
            $type, $description, $scoreGlobal, $niveauRisque,
            $analyseTexte, $analyseComport, $analyseContrat
        );
        if ($analyseDoublon['detail']) $recommandation .= " [ALERTE DOUBLON: " . $analyseDoublon['detail'] . "]";
        if ($analyseImage['detail'])   $recommandation .= " [ALERTE IMAGE: " . $analyseImage['detail'] . "]";
        if (!$photoPath)               $recommandation .= " [ALERTE: Aucune photo fournie - vigilance accrue recommandée]";

        // ── 8b. Règle spéciale : Montant >= 10 000 DT => Manuel (Normal) ────────
        if ($montant !== null && $montant >= 10000) {
            if ($niveauRisque === 'faible') {
                $niveauRisque = 'normal';
                $suggestionIa = 'investiguer';
                $scoreGlobal  = max($scoreGlobal, 20); // Floor at 20 for UI consistency
                $recommandation = "⚠️ VÉRIFICATION MANUELLE OBLIGATOIRE (Montant >= 10 000 DT). " . $recommandation;
            }
        }

        // ── 10. Flags binaires ─────────────────────────────────────────────────
        $flags = [
            'description_vague'   => $analyseTexte['flag_vague']      ? 1 : 0,
            'sinistres_multiples' => $analyseComport['flag_multiples'] ? 1 : 0,
            'contrat_recent'      => $analyseContrat['flag_recent']    ? 1 : 0,
            'montant_eleve'       => $analyseContrat['flag_montant']   ? 1 : 0,
            'image_suspecte'      => $analyseImage['score'] > 40       ? 1 : 0,
            'absence_photo'       => !$photoPath ? 1 : 0,
            'declaration_tardive' => $analyseTexte['flag_tardif']      ? 1 : 0,
        ];

        // ── 10. Persistance en base ───────────────────────────────────────────
        $this->sauvegarder([
            'id_sinistre'              => $idSinistre,
            'id_user'                  => $idUser,
            'score_global'             => $scoreGlobal,
            'niveau_risque'            => $niveauRisque,
            'suggestion_ia'            => $suggestionIa,
            'score_texte'              => $analyseTexte['score'],
            'score_comportement'       => $analyseComport['score'],
            'score_contrat'            => $analyseContrat['score'],
            'score_image'              => $analyseImage['score'],
            'flag_description_vague'   => $flags['description_vague'],
            'flag_sinistres_multiples' => $flags['sinistres_multiples'],
            'flag_contrat_recent'      => $flags['contrat_recent'],
            'flag_montant_eleve'       => $flags['montant_eleve'],
            'flag_image_suspecte'      => $flags['image_suspecte'],
            'analyse_texte'            => $analyseTexte['detail'],
            'analyse_comportement'     => $analyseComport['detail'],
            'analyse_image'            => $analyseImage['detail'],
            'recommandation_ia'        => $recommandation,
        ]);

        return [
            'score_global'   => $scoreGlobal,
            'niveau_risque'  => $niveauRisque,
            'suggestion_ia'  => $suggestionIa,
            'recommandation' => $recommandation,
            'scores_detail'  => [
                'texte'        => $analyseTexte['score'],
                'comportement' => $analyseComport['score'],
                'contrat'      => $analyseContrat['score'],
            ],
            'flags' => $flags,
        ];
    }

    public function previewFraudScore(string $type, string $description, ?string $photoPath = null): array
    {
        $analyseTexte = $this->analyserTexte($type, $description);

        $score = (int) $analyseTexte['score'];

        if (!$photoPath) {
            $score = min(100, $score + 15);
        } elseif (file_exists($photoPath)) {
            $score = max(0, $score - 20);
        }

        return [
            'score' => $score,
            'flags' => array_values(array_filter([
                !empty($analyseTexte['flag_vague']) ? 'description_vague' : null,
                !empty($analyseTexte['flag_tardif']) ? 'declaration_tardive' : null,
                !empty($analyseTexte['flag_no_witnesses']) ? 'absence_temoins' : null,
                !empty($analyseTexte['flag_futur']) ? 'date_future' : null,
                !$photoPath ? 'absence_photo' : null,
            ])),
        ];
    }

    /** NLP analysis of claim description */
    private function analyserTexte(string $type, string $description): array
    {
        $desc    = trim($description);
        $descLen = mb_strlen($desc);
        $descLow = mb_strtolower($desc);
        $typeLow = mb_strtolower(trim($type));
        $flagNoWitness = (stripos($descLow, 'témoin') !== false && (stripos($descLow, 'aucun') !== false || stripos($descLow, 'pas de') !== false));
        $flagTardif = false;

        // ── Cas trivial : trop court ──────────────────────────────────────────
        if ($descLen < 5) {
            return [
                'score'      => 100,
                'flag_vague' => true,
                'flag_tardif' => false,
                'flag_no_witnesses' => false,
                'detail'     => "Description quasi-vide ({$descLen} caractères) — suspect critique.",
            ];
        }

        $details = [];

        // ── A. Score VAGUE (0-100) ────────────────────────────────────────────
        $scoreVague = 0;

        // Longueur brute
        if ($descLen < 15)       { $scoreVague += 60; $details[] = 'Description très succincte.'; }
        elseif ($descLen < 30)   { $scoreVague += 30; $details[] = 'Description courte.'; }
        elseif ($descLen < 60)   { $scoreVague += 15; }
        elseif ($descLen < 120)  { $scoreVague += 5; }

        // Nombre de mots
        $mots = preg_split('/\s+/', $descLow, -1, PREG_SPLIT_NO_EMPTY);
        $nbMots = count($mots);
        if ($nbMots <= 1 && $descLen > 0) {
            $scoreVague += 40;
            $details[] = 'Description composée d\'un seul mot (très suspect).';
        }

        // Absence d'heure / moment
        if (!preg_match('/\b(\d{1,2})[h:h]\d{0,2}|\b(heures?|matin|après-?midi|soir|nuit|midi|minuit|aujourd\'hui|hier)\b/i', $descLow)) {
            $scoreVague += 10;
            $details[] = 'Aucune indication temporelle.';
        }

        // Absence de lieu (rue, ville, etc. - 'véhicule' ne suffit plus)
        if (!preg_match('/\b(rue|avenue|boulevard|autoroute|route|place|quartier|maison|domicile|bureau|magasin|parking|gare|immeuble|km|kilomètre)\b/i', $descLow)) {
            $scoreVague += 15;
            $details[] = 'Aucune indication de lieu précise (rue, ville, parking, etc.).';
        }

        // Densité lexicale : rapport mots uniques / total mots
        $mots       = preg_split('/\s+/', $descLow, -1, PREG_SPLIT_NO_EMPTY);
        $nbMots     = count($mots);
        $nbUniques  = count(array_unique($mots));
        $densiteLex = $nbMots > 0 ? $nbUniques / $nbMots : 0;
        if ($nbMots < 5 || $densiteLex < 0.50) {
            $scoreVague += 15; $details[] = 'Faible densité lexicale (répétitions ou peu de mots).';
        }

        // Absence de verbe d'action
        if (!preg_match('/\b(est|était|s\'est|a|avait|eu|subi|causé|produit|arrivé|survenu|blessé|touché|endommagé|brûlé|volé|perdu|cassé|détruit|heurté|glissé|tombé)\b/i', $descLow)) {
            $scoreVague += 8; $details[] = 'Aucun verbe d\'action détecté.';
        }

        // Gibberish / Non-sense detection
        $vowels = preg_match_all('/[aeiouyàéèêëîïôûù]/i', $description);
        $consonants = preg_match_all('/[bcdfghjklmnpqrstvwxz]/i', $description);
        if ($descLen > 10 && ($vowels / ($vowels + $consonants + 1)) < 0.15) {
            $scoreVague += 80;
            $details[] = 'Texte semblant être du charabia (peu de voyelles).';
        }

        $scoreVague = min(100, $scoreVague);

        // ── B. Score INCOHÉRENCE (0-100) ──────────────────────────────────────
        $scoreIncoherence = $this->evaluerIncoherence($type, $descLow, $details);

        // ── C. Score GENERICITE (0-100) ───────────────────────────────────────
        $scoreGenericite = 0;
        $motsGeneriques = [
            'accident', 'problème', 'sinistre', 'dommage', 'incident', 'chose',
            'truc', 'situation', 'événement', 'cas', 'fait', 'suite à', 'à la suite',
        ];
        $nbGeneriques = 0;
        foreach ($motsGeneriques as $mg) {
            if (str_contains($descLow, $mg)) $nbGeneriques++;
        }
        if ($nbGeneriques >= 4) {
            $scoreGenericite = 70; $details[] = 'Vocabulaire très générique (' . $nbGeneriques . ' termes passe-partout).';
        } elseif ($nbGeneriques >= 2) {
            $scoreGenericite = 35; $details[] = 'Quelques termes génériques détectés.';
        }

        // Détection de formules pré-rédigées
        $formulesTypiques = [
            'je déclare', 'par la présente', 'je soussigné', 'suite à l\'incident',
            'je vous informe', 'prière de bien vouloir', 'dans l\'attente',
        ];
        foreach ($formulesTypiques as $f) {
            if (str_contains($descLow, $f)) {
                $scoreGenericite = min(100, $scoreGenericite + 30);
                $details[] = 'Formule administrative détectée ("' . $f . '").';
                break;
            }
        }
        $scoreGenericite = min(100, $scoreGenericite);

        // ── D. Score URGENCE SUSPECTE (0-100) ─────────────────────────────────
        $scoreUrgence = 0;
        $marqueursPression = [
            'urgent', 'immédiatement', 'd\'urgence', 'le plus vite', 'rapidement',
            'je compte sur vous', 'je vous en supplie', 'ruiné', 'catastrophe',
            'je n\'en peux plus', 'obligation', 'menace', 'avocat', 'justice',
            'plainte', 'tribunal', 'tout de suite', 'sans délai',
        ];
        $nbPression = 0;
        foreach ($marqueursPression as $mp) {
            if (str_contains($descLow, $mp)) $nbPression++;
        }
        if ($nbPression >= 3) {
            $scoreUrgence = 75; $details[] = 'Pression émotionnelle forte (' . $nbPression . ' marqueurs d\'urgence).';
        } elseif ($nbPression >= 1) {
            $scoreUrgence = 30; $details[] = 'Marqueur d\'urgence détecté.';
        }

        // ── E. Score PRÉCISION FACTUELLE (0-100) ──────────────────────────────
        $scorePrecision = 0;
        $bonusClarete = 0;
        
        // Détection d'excuses de flou (classique en fraude)
        $excusesFlou = [
            '/difficile.*préciser/i', '/pas sûr/i', '/ne sais plus/i', '/souviens plus/i',
            '/visibilité/i', '/nuit noire/i', '/sombre/i', '/choqué/i', '/panique/i',
            '/soudain/i', '/tout à coup/i', '/impossible.*dire/i', '/aucune idée/i', '/flou/i', '/manque.*détail/i',
            '/rien entendu/i', '/rien remarqué/i', '/aucun témoin/i', '/rien vu/i', '/pas fait attention/i'
        ];
        $nbExcuses = 0;
        foreach ($excusesFlou as $regex) {
            if (preg_match($regex, $descLow)) $nbExcuses++;
        }
        if ($nbExcuses >= 2) {
            $scorePrecision += 80;
            $scoreVague += 50;
            $details[] = 'Plusieurs excuses de flou détectées (évitement de détails).';
        } elseif ($nbExcuses >= 1) {
            $scorePrecision += 40;
            $scoreVague += 25;
            $details[] = 'Justification de manque de précision détectée.';
        }

        // Densité numérique (les récits réels ont des chiffres : vitesse, heure, distance, plaques)
        preg_match_all('/\d+/', $description, $matchesDigits);
        $nbDigits = count($matchesDigits[0]);
        if ($descLen > 100 && $nbDigits <= 1) {
            $scorePrecision += 30;
            $details[] = 'Récit verbeux mais pauvre en données chiffrées.';
        }

        // ── F. Détection de Déclaration Tardive (> 7 jours) ───────────────────
        // --- Detection Regex 1: DD/MM/YYYY or MM/DD/YYYY ---
        if (preg_match('/\b(\d{1,2})[\/\-\.](\d{1,2})[\/\-\.](\d{2,4})\b/', $description, $matches)) {
            $v1 = (int)$matches[1];
            $v2 = (int)$matches[2];
            $year = (int)$matches[3];
            
            // Tentative d'interprétation intelligente (gestion inversion jour/mois)
            $day = $v1; $month = $v2;
            if ($month > 12 && $day <= 12) { // Probable format US MM/DD/YYYY
                $day = $v2; $month = $v1;
            }
            
            if (checkdate($month, $day, $year)) {
                $flagTardif = $this->validerDateAnalyse($day, $month, $year, $scoreVague, $details);
            } else {
                // Date invalide mais présente (ex: 15/15/2026) -> Très suspect
                $scoreVague += 80;
                $details[] = "Format de date invalide ou suspect détecté ({$matches[0]}).";
            }
        } 
        // --- Detection Regex 2: DD Month YYYY (French) ---
        elseif (preg_match('/\b(\d{1,2})\s+(janvier|février|mars|avril|mai|juin|juillet|août|septembre|octobre|novembre|décembre|janv\.?|févr\.?|avr\.?|juil\.?|sept\.?|oct\.?|nov\.?|déc\.?)\s+(\d{4})\b/i', $descLow, $matches)) {
            $day = (int)$matches[1];
            $monthStr = $matches[2];
            $year = (int)$matches[3];
            
            $monthMap = [
                'janvier'=>1,'janv'=>1,'février'=>2,'févr'=>2,'mars'=>3,'avril'=>4,'avr'=>4,'mai'=>5,'juin'=>6,
                'juillet'=>7,'juil'=>7,'août'=>8,'septembre'=>9,'sept'=>9,'octobre'=>10,'oct'=>10,'novembre'=>11,
                'nov'=>11,'décembre'=>12,'déc'=>12
            ];
            
            $month = $monthMap[mb_strtolower($monthStr)] ?? 0;
            if ($month > 0) {
                $flagTardif = $this->validerDateAnalyse($day, $month, $year, $scoreVague, $details);
            }
        }

        // Présence d'un montant chiffré
        if (preg_match('/\b\d+[\s,.]?\d*\s*(dt|tnd|dinar|€|\$|euros?)\b/i', $descLow)) {
            $bonusClarete += 15;
        }
        // Présence d'une date ou heure précise
        if (preg_match('/\b\d{1,2}[\/\-\.]\d{1,2}[\/\-\.]\d{2,4}\b|\b\d{1,2}[h:]\d{2}\b/', $descLow)) {
            $bonusClarete += 10;
        }
        // Noms propres / entités (indicateur de récit réel)
        if (preg_match('/[A-ZÀÂÉÈÊË][a-zàâéèêëîï]{2,}/', $desc)) {
            $bonusClarete += 8;
        }

        // ── G. Détection d'Hyperboles / Contradictions (Cause vs Effet) ──────
        $smallCause = ['petite', 'légère', 'mini', 'faible', 'simple', 'petit', 'minuscule', 'quelques gouttes'];
        $bigEffect  = ['très importants', 'énormes', 'considérables', 'catastrophique', 'total', 'dévastateur', 'majeurs', 'partout', 'tout l\'appartement'];
        $hasSmall = false; $hasBig = false;
        foreach($smallCause as $s) if(str_contains($descLow, $s)) { $hasSmall = true; break; }
        foreach($bigEffect as $b)  if(str_contains($descLow, $b)) { $hasBig = true; break; }
        if ($hasSmall && $hasBig) {
            $scoreIncoherence = min(100, $scoreIncoherence + 45);
            $details[] = 'Contradiction détectée entre la cause (minime) et les dégâts (majeurs).';
        }

        // ── H. Isolement suspect (Dégât des eaux massif sans voisin touché) ──
        if (str_contains($typeLow, 'eau') && str_contains($descLow, 'appartement') && $hasBig) {
            if (preg_match('/aucun.*voisin/i', $descLow) || preg_match('/pas.*voisin/i', $descLow)) {
                $scoreIncoherence = min(100, $scoreIncoherence + 30);
                $details[] = 'Isolement suspect : dégâts massifs en appartement sans impact sur le voisinage.';
            }
        }

        // ── Score final pondéré ───────────────────────────────────────────────
        // Nouvelle pondération incluant la précision : A*30 + B*25 + C*20 + D*10 + E*15
        $scoreGlobalTexte = (int)round(
            ($scoreVague * 30 + $scoreIncoherence * 25 + $scoreGenericite * 20 + $scoreUrgence * 10 + $scorePrecision * 15) / 100
        );

        // Application du bonus clarté (réduction plafonnée à 20 pts)
        // SAUF si incohérence critique — une fraude bien écrite reste une fraude
        if ($scoreIncoherence < 70) {
            $scoreGlobalTexte = max(0, $scoreGlobalTexte - min(20, $bonusClarete));
        }

        // FORCE CRITIQUE if major type/content mismatch (fire text in water claim, etc.)
        if ($scoreIncoherence >= 70) {
            $scoreGlobalTexte = max($scoreGlobalTexte, 85);
            $details[] = 'ALERTE : contenu incompatible avec le type de sinistre déclaré.';
        }

        // FORCE 100 if critical issue (Future date or Late declaration)
        if ($scoreVague >= 100) {
            $scoreGlobalTexte = 100;
        }

        $scoreGlobalTexte = min(100, $scoreGlobalTexte);

        // Si bcp d'excuses de flou, on force un flag vague
        $flagVague = ($scoreVague > self::SEUIL_VAGUE_FLAG) || ($scorePrecision >= 50);

        // ── Synthèse textuelle ────────────────────────────────────────────────
        $explication = $this->synthetiserAnalyseTexte(
            $scoreGlobalTexte, $scoreVague, $scoreIncoherence, $scoreGenericite, $scoreUrgence, $scorePrecision, $details
        );

        $flagFutur = str_contains($explication, 'futur');
        return [
            'score'      => $scoreGlobalTexte,
            'flag_vague' => $flagVague,
            'flag_tardif' => $flagTardif,
            'flag_futur'  => $flagFutur,
            'flag_no_witnesses' => $flagNoWitness,
            'detail'     => $explication,
            '_debug'     => [
                'vague'        => $scoreVague,
                'incoherence'  => $scoreIncoherence,
                'genericite'   => $scoreGenericite,
                'urgence'      => $scoreUrgence,
                'bonus_clarte' => $bonusClarete,
            ],
        ];
    }

    private function validerDateAnalyse(int $day, int $month, int $year, int &$scoreVague, array &$details): bool
    {
        if ($year < 100) $year += 2000;
        if (checkdate($month, $day, $year)) {
            $dateIncid = new DateTime("$year-$month-$day");
            $today     = new DateTime();
            $diff      = $today->diff($dateIncid)->days;

            if ($diff > 7 && $dateIncid < $today) {
                $scoreVague += 100;
                $details[] = "Déclaration tardive détectée ($diff jours après l'incident).";
                return true; // flag_tardif
            } elseif ($dateIncid > $today) {
                $scoreVague += 200; // Force dépassement pour détection
                $details[] = "CRITIQUE : Date incohérente (dans le futur : " . $dateIncid->format('d/m/Y') . ").";
                return false;
            }
        }
        return false;
    }

    /**
     * Évalue l'incohérence entre le type de sinistre et les mots-clés attendus.
     * Chaque type a une liste de mots attendus (présence réduit le score)
     * et une liste de mots suspects (présence augmente le score).
     */
    private function evaluerIncoherence(string $type, string $descLow, array &$details): int
    {
        $typeLow = mb_strtolower(trim($type));

        // Dictionnaire : type → [mots_attendus[], mots_suspects[]]
        $dictionnaire = [
            'accident auto' => [
                'attendus'  => ['voiture', 'véhicule', 'collision', 'choc', 'conducteur', 'route', 'autoroute', 'carrefour', 'freinage', 'impact', 'auto', 'camion', 'moto', 'roulant'],
                'suspects'  => ['incendie', 'inondation', 'vol', 'hospitalisation', 'chute', 'explosion'],
            ],
            'bris de glace' => [
                'attendus'  => ['vitre', 'pare-brise', 'glace', 'fissure', 'éclat', 'caillou', 'gravillon', 'graviers', 'projecté', 'projection', 'cassé', 'impact', 'pierre', 'lunette', 'latérale'],
                'suspects'  => ['moteur', 'feu', 'volé', 'blessé', 'inondation', 'fumée'],
            ],
            'vol de véhicule' => [
                'attendus'  => ['volé', 'voleur', 'disparu', 'véhicule', 'voiture', 'clé', 'parking', 'retrouvé', 'plainte', 'police', 'gendarmerie'],
                'suspects'  => ['accident', 'brûlé', 'inondé', 'collision'],
            ],
            'vol' => [
                'attendus'  => ['volé', 'voleur', 'cambriolage', 'disparu', 'manquant', 'effraction', 'serrure', 'fenêtre', 'porte', 'objet', 'bijou', 'téléphone', 'ordinateur'],
                'suspects'  => ['brûlé', 'inondé', 'accident', 'chute', 'blessé', 'explosion'],
            ],
            'cambriolage' => [
                'attendus'  => ['volé', 'voleur', 'cambriolage', 'effraction', 'serrure', 'fenêtre', 'porte', 'fouillé', 'désordre', 'bijou', 'télévision'],
                'suspects'  => ['inondation', 'feu', 'collision'],
            ],
            'incendie' => [
                'attendus'  => ['feu', 'flamme', 'brûlé', 'fumée', 'incendie', 'carbonisé', 'brûlure', 'chaleur', 'pompier', 'extincteur', 'foyer', 'court-circuit'],
                'suspects'  => ['vol', 'inondation', 'collision', 'chute', 'blessure'],
            ],
            'dégât des eaux' => [
                'attendus'  => ['eau', 'fuite', 'inondation', 'dégât', 'tuyau', 'robinet', 'humidité', 'moisissure', 'plafond', 'plancher', 'dommage', 'lézard', 'canalisation', 'baignoire', 'évier'],
                'suspects'  => ['vol', 'feu', 'flamme', 'collision', 'accident'],
            ],
            'catastrophe naturelle' => [
                'attendus'  => ['tempête', 'inondation', 'vent', 'grêle', 'foudre', 'pluie', 'orage', 'catastrophe', 'sinistre', 'mairie', 'arrêté'],
                'suspects'  => ['vol', 'cambriolage', 'collision'],
            ],
            'maladie' => [
                'attendus'  => ['maladie', 'médecin', 'hôpital', 'diagnostic', 'traitement', 'symptôme', 'consultation', 'médicament', 'ordonnance', 'grave'],
                'suspects'  => ['vol', 'incendie', 'collision', 'véhicule'],
            ],
            'hospitalisation' => [
                'attendus'  => ['hôpital', 'clinique', 'séjour', 'chirurgie', 'opération', 'soins', 'infirmier', 'admission', 'sortie'],
                'suspects'  => ['vol', 'incendie', 'voiture'],
            ],
            'accident corporel' => [
                'attendus'  => ['blessé', 'chute', 'fracture', 'douleur', 'urgence', 'samu', 'pompiers', 'blessure', 'sang'],
                'suspects'  => ['dommage matériel', 'vol', 'incendie'],
            ],
            'décès' => [
                'attendus'  => ['décès', 'mort', 'décédé', 'enterrement', 'acte de décès', 'hôpital', 'cause'],
                'suspects'  => ['vol', 'incendie', 'véhicule', 'dommage matériel'],
            ],
            'invalidité' => [
                'attendus'  => ['invalidité', 'handicap', 'incapacité', 'expertise', 'médecin', 'taux', 'perte'],
                'suspects'  => ['vol', 'incendie', 'voiture'],
            ],
        ];

        // Trouver le meilleur match de type
        $attendus  = [];
        $suspects  = [];
        foreach ($dictionnaire as $cleType => $data) {
            if (str_contains($typeLow, $cleType) || str_contains($cleType, $typeLow)) {
                $attendus = $data['attendus'];
                $suspects = $data['suspects'];
                break;
            }
        }

        if (empty($attendus)) {
            // Type inconnu du dictionnaire → analyse générique
            return 20;
        }

        $nbAttendusTrouves = 0;
        foreach ($attendus as $mot) {
            if (str_contains($descLow, $mot)) $nbAttendusTrouves++;
        }

        $nbSuspectsTrouves = 0;
        foreach ($suspects as $mot) {
            if (str_contains($descLow, $mot)) $nbSuspectsTrouves++;
        }

        // Score incohérence = pénalité pour absence de mots attendus + présence de mots suspects
        $txCouverture = count($attendus) > 0 ? $nbAttendusTrouves / count($attendus) : 0;
        $score = 0;

        if ($txCouverture < 0.15) {
            $score = 70;
            $details[] = 'Aucun mot-clé attendu pour ce type de sinistre (' . $type . ').';
        } elseif ($txCouverture < 0.35) {
            $score = 40;
            $details[] = 'Peu de mots-clés cohérents avec le type "' . $type . '".';
        } elseif ($txCouverture < 0.55) {
            $score = 20;
        }

        if ($nbSuspectsTrouves > 0) {
            $score = min(100, $score + $nbSuspectsTrouves * 20);
            $details[] = 'Termes appartenant à un autre type de sinistre détectés.';
        }

        return min(100, $score);
    }

    /**
     * Synthétise les scores partiels en une explication humaine.
     */
    private function synthetiserAnalyseTexte(
        int   $scoreGlobal,
        int   $scoreVague,
        int   $scoreIncoherence,
        int   $scoreGenericite,
        int   $scoreUrgence,
        int   $scorePrecision,
        array $details
    ): string {
        // Critère dominant
        $max  = max($scoreVague, $scoreIncoherence, $scoreGenericite, $scoreUrgence, $scorePrecision);
        $dom  = match(true) {
            $max === $scoreIncoherence && $scoreIncoherence > 30 => 'incohérence entre le type déclaré et le contenu',
            $max === $scoreUrgence     && $scoreUrgence > 30     => 'pression émotionnelle suspecte',
            $max === $scoreGenericite  && $scoreGenericite > 30  => 'description générique ou pré-rédigée',
            $max === $scorePrecision   && $scorePrecision > 30   => 'manque de précision ou excuses de flou',
            default                                              => 'manque de détails concrets',
        };

        $niveauTexte = match(true) {
            $scoreGlobal >= 70 => 'Risque textuel élevé',
            $scoreGlobal >= 40 => 'Risque textuel modéré',
            default            => 'Description textuelle acceptable',
        };

        $base = "{$niveauTexte} (score: {$scoreGlobal}/100). Critère dominant : {$dom}.";

        if (!empty($details)) {
            $base .= ' Détails : ' . implode(' ', array_slice($details, 0, 3));
        }

        return $base;
    }

    /** Image and GPS forensics */
    private function analyserImage(?string $path, string $description): array
    {
        if (!$path || !file_exists($path)) {
            return ['score' => 0, 'detail' => null];
        }

        $score = 0;
        $details = [];

        // 1. Extraction EXIF (nécessite l'extension php_exif)
        $exif = @exif_read_data($path);
        if (!$exif) {
            // Pas de metadata = neutre ou légèrement suspect pour un smartphone moderne
            return ['score' => 0, 'detail' => "Aucune métadonnée EXIF trouvée."];
        }

        // 2. Détection de logiciel de retouche
        $software = $exif['Software'] ?? $exif['ImageDescription'] ?? '';
        $suspectSoftware = ['photoshop', 'gimp', 'canva', 'picsart', 'snapseed'];
        foreach ($suspectSoftware as $s) {
            if (stripos($software, $s) !== false) {
                $score += 40;
                $details[] = "Image éditée via $software.";
                break;
            }
        }

        // 3. Analyse GPS
        $gps = $this->extraireGps($exif);
        if ($gps) {
            // Comparaison basique avec la description
            $descLow = mb_strtolower($description);
            // Si l'image vient d'un autre pays (ex: coord. non tunisiennes si on est en Tunisie)
            // Ici on simule une vérification de périmètre (ex: hors Tunisie/France selon le contexte)
            if ($gps['lat'] < 30 || $gps['lat'] > 48) { // Exemple large France/Tunisie
                $score += 30;
                $details[] = "Localisation GPS de l'image inhabituelle ({$gps['lat']}, {$gps['lng']}).";
            }
        }

        return [
            'score'  => min(100, $score),
            'detail' => implode(' ', $details)
        ];
    }

    private function extraireGps(array $exif): ?array
    {
        if (!isset($exif['GPSLatitude'], $exif['GPSLongitude'], $exif['GPSLatitudeRef'], $exif['GPSLongitudeRef'])) {
            return null;
        }

        $getGps = function($exifCoord, $hemi) {
            $degrees = count($exifCoord) > 0 ? $this->gpsToFloat($exifCoord[0]) : 0;
            $minutes = count($exifCoord) > 1 ? $this->gpsToFloat($exifCoord[1]) : 0;
            $seconds = count($exifCoord) > 2 ? $this->gpsToFloat($exifCoord[2]) : 0;
            $flip = ($hemi == 'W' || $hemi == 'S') ? -1 : 1;
            return $flip * ($degrees + ($minutes / 60) + ($seconds / 3600));
        };

        return [
            'lat' => $getGps($exif['GPSLatitude'], $exif['GPSLatitudeRef']),
            'lng' => $getGps($exif['GPSLongitude'], $exif['GPSLongitudeRef'])
        ];
    }

    private function gpsToFloat($coord): float
    {
        $parts = explode('/', $coord);
        if (count($parts) <= 0) return 0;
        if (count($parts) == 1) return (float)$parts[0];
        return (float)$parts[0] / (float)$parts[1];
    }

    /** Duplicate and similarity detection */
    private function detecterDoublons(int $idSinistre, string $description): array
    {
        $desc = trim($description);
        if (mb_strlen($desc) < 30) return ['score' => 0, 'detail' => null];

        // 1. Recherche de doublon EXACT (Hash)
        $stmt = $this->db->prepare("
            SELECT id_sinistre, id_user FROM sinistre 
            WHERE description = :d AND id_sinistre <> :id
            LIMIT 1
        ");
        $stmt->execute([':d' => $desc, ':id' => $idSinistre]);
        $exact = $stmt->fetch();

        if ($exact) {
            return [
                'score'  => 100,
                'detail' => "Description identique au sinistre #{$exact['id_sinistre']}."
            ];
        }

        // 2. Recherche de similarité (Levenshtein / similar_text)
        // Note: Sur une grosse base, il faudrait utiliser un index de recherche plein texte
        // Ici on compare avec les 50 derniers sinistres pour rester performant localement
        $stmt = $this->db->prepare("
            SELECT id_sinistre, description FROM sinistre 
            WHERE id_sinistre <> :id 
            ORDER BY id_sinistre DESC LIMIT 50
        ");
        $stmt->execute([':id' => $idSinistre]);
        
        foreach ($stmt->fetchAll() as $row) {
            similar_text($desc, $row['description'], $percent);
            if ($percent > 85) {
                return [
                    'score'  => 80,
                    'detail' => "Forte similarité (" . round($percent) . "%) avec le sinistre #{$row['id_sinistre']}."
                ];
            }
        }

        return ['score' => 0, 'detail' => null];
    }

    /** Behavioral analysis based on user history */
    private function analyserComportement(int $idUser, ?int $idContrat): array
    {
        $score  = 0;
        $flags  = [];
        $detail = [];

        $stmt = $this->db->prepare("
            SELECT COUNT(*) AS nb
            FROM sinistre
            WHERE id_user = :u
              AND date_declaration >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
        ");
        $stmt->execute([':u' => $idUser]);
        $nb90j = (int)$stmt->fetchColumn();

        if ($nb90j >= 4) {
            $score += 50;
            $flags[] = 'sinistres_multiples';
            $detail[] = $nb90j . ' sinistres déclarés en 90 jours (très élevé).';
        } elseif ($nb90j >= 2) {
            $score += 25;
            $detail[] = $nb90j . ' sinistres déclarés en 90 jours (élevé).';
        } else {
            $detail[] = $nb90j . ' sinistre(s) en 90 jours (normal).';
        }

        $stmt2 = $this->db->prepare("
            SELECT COUNT(*) AS total, SUM(statut = 'refuse') AS refuses
            FROM sinistre WHERE id_user = :u
        ");
        $stmt2->execute([':u' => $idUser]);
        $row     = $stmt2->fetch();
        $total   = (int)($row['total']   ?? 0);
        $refuses = (int)($row['refuses'] ?? 0);

        if ($total >= 3) {
            $txRefus = $refuses / $total;
            if ($txRefus >= 0.5) {
                $score += 30;
                $detail[] = round($txRefus * 100) . '% des sinistres passés ont été refusés.';
            } elseif ($txRefus >= 0.3) {
                $score += 15;
                $detail[] = round($txRefus * 100) . '% des sinistres refusés (modéré).';
            }
        }

        if ($idContrat) {
            $stmt3 = $this->db->prepare("
                SELECT COUNT(*) AS nb FROM sinistre
                WHERE id_contrat = :c AND id_user <> :u
                  AND date_declaration >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            ");
            $stmt3->execute([':c' => $idContrat, ':u' => $idUser]);
            $nbAutres = (int)$stmt3->fetchColumn();
            if ($nbAutres > 0) {
                $score += 20;
                $detail[] = 'Ce contrat a eu ' . $nbAutres . ' autre(s) sinistre(s) récent(s) par d\'autres utilisateurs.';
            }
        }

        $score = min(100, $score);
        return [
            'score'          => $score,
            'flag_multiples' => in_array('sinistres_multiples', $flags),
            'detail'         => implode(' ', $detail) ?: 'Comportement normal.',
        ];
    }

    /** Contract analysis and risk assessment */
    private function analyserContrat(?int $idContrat, int $idUser, ?float $montant = null): array
    {
        $score  = 0;
        $detail = [];
        $flagRecent  = false;
        $flagMontant = false;

        $stmt = $this->db->prepare("
            SELECT date_debut_contrat, 
                   franchise_contrat, 
                   prime_contrat, 
                   statut_contrat
            FROM contrat WHERE id_contrat = :c
        ");
        $stmt->execute([':c' => $idContrat]);
        $contrat = $stmt->fetch();

        if (!$contrat) {
            return ['score' => 20, 'flag_recent' => false, 'flag_montant' => false, 'detail' => 'Contrat introuvable.'];
        }

        if ($contrat['date_debut_contrat']) {
            $debut = new DateTime($contrat['date_debut_contrat']);
            $joursDepuisDebut = (int)$debut->diff(new DateTime())->days;

            if ($joursDepuisDebut < 15) {
                $score += 20; $flagRecent = true;
                $detail[] = 'Contrat tout récent (' . $joursDepuisDebut . ' jours).';
            } elseif ($joursDepuisDebut < 45) {
                $score += 10; $flagRecent = true;
                $detail[] = 'Contrat récent : ' . $joursDepuisDebut . ' jours.';
            } else {
                $detail[] = 'Contrat établi depuis ' . $joursDepuisDebut . ' jours (normal).';
            }
        }

        if ($contrat['statut_contrat'] !== 'actif' && $contrat['statut_contrat'] !== null) {
            $score += 30;
            $detail[] = 'Statut du contrat : ' . $contrat['statut_contrat'] . ' (anormal).';
        }

        $franchise = (float)($contrat['franchise_contrat'] ?? 0);
        $prime     = (float)($contrat['prime_contrat']     ?? 1);
        if ($prime > 0 && $franchise > 0 && ($franchise / $prime) > 10) {
            $score += 15; $flagMontant = true;
            $detail[] = 'Rapport franchise/prime élevé (' . round($franchise / $prime, 1) . 'x).';
        }

        // --- Check Indemnity Amount (> 10 000 DT requires manual check) ---
        if ($montant !== null && $montant >= 10000) {
            $score += 35; // Land in 'normal' range (20-60) if everything else is fine
            $flagMontant = true;
            $detail[] = 'Montant élevé nécessitant une validation manuelle (>= 10 000 DT).';
        } elseif ($montant !== null && $montant > 5000) {
            $score += 15;
            $detail[] = 'Montant significatif (' . number_format($montant, 0, '.', ' ') . ' DT).';
        }

        return [
            'score'        => min(100, $score),
            'flag_recent'  => $flagRecent,
            'flag_montant' => $flagMontant,
            'detail'       => implode(' ', $detail) ?: 'Contrat dans les paramètres normaux.',
        ];
    }

    /** Generate actionable recommendations for agents */
    private function genererRecommandation(
        string $type,
        string $description,
        int    $scoreGlobal,
        string $niveauRisque,
        array  $analyseTexte,
        array  $analyseComport,
        array  $analyseContrat
    ): string {
        // ── Collecte des causes actives ───────────────────────────────────────
        $causes = [];

        if ($analyseTexte['flag_vague']) {
            $causes[] = 'description vague ou incomplète';
        }
        if (isset($analyseTexte['_debug'])) {
            $d = $analyseTexte['_debug'];
            if (($d['incoherence'] ?? 0) >= 40) $causes[] = 'incohérence entre le type et le contenu déclaré';
            if (($d['genericite']  ?? 0) >= 40) $causes[] = 'description générique ou pré-rédigée';
            if (($d['urgence']     ?? 0) >= 40) $causes[] = 'pression émotionnelle suspecte';
        }
        if ($analyseComport['flag_multiples']) $causes[] = 'sinistres multiples déclarés récemment';
        if ($analyseContrat['flag_recent'])    $causes[] = 'contrat très récent au moment de la déclaration';
        if ($analyseContrat['flag_montant'])   $causes[] = 'rapport franchise/prime anormal';

        $nbCauses    = count($causes);
        $causesTexte = $nbCauses > 0 ? implode(', ', $causes) : 'aucune anomalie majeure isolée';

        // ── Actions suggérées selon le niveau ────────────────────────────────
        $actionsSpecifiques = $this->determinerActions($type, $scoreGlobal, $causes, $analyseTexte, $analyseComport, $analyseContrat);

        // ── Modèle de recommandation selon le niveau ──────────────────────────
        return match($niveauRisque) {
            'fraude' => $this->recoFraude($type, $scoreGlobal, $causesTexte, $actionsSpecifiques),
            'normal' => $this->recoNormal($type, $scoreGlobal, $causesTexte, $actionsSpecifiques),
            default  => $this->recoFaible($type, $scoreGlobal, $actionsSpecifiques),
        };
    }

    /**
     * Détermine les actions concrètes à demander selon le type de sinistre et les flags.
     * @return string[] Liste d'actions
     */
    private function determinerActions(
        string $type,
        int    $scoreGlobal,
        array  $causes,
        array  $analyseTexte,
        array  $analyseComport,
        array  $analyseContrat
    ): array {
        $actions = [];
        $typeLow = mb_strtolower($type);

        // Actions universelles selon les flags
        if ($analyseTexte['flag_vague']) {
            $actions[] = 'demander une description plus détaillée (lieu exact, heure, circonstances précises)';
        }
        if ($analyseComport['flag_multiples']) {
            $actions[] = 'consulter l\'historique complet des sinistres du client';
            $actions[] = 'vérifier la cohérence des déclarations précédentes';
        }
        if ($analyseContrat['flag_recent']) {
            $actions[] = 'vérifier la date de souscription du contrat et l\'intention d\'assurance';
        }
        if ($analyseContrat['flag_montant']) {
            $actions[] = 'faire évaluer le sinistre par un expert indépendant';
        }

        // Actions spécifiques au type de sinistre
        if (str_contains($typeLow, 'auto') || str_contains($typeLow, 'véhicule') || str_contains($typeLow, 'accident')) {
            $actions[] = 'demander le rapport de police ou constat amiable signé';
            $actions[] = 'vérifier le permis de conduire et les antécédents d\'accidents';
            if ($scoreGlobal >= 50) $actions[] = 'demander photos du véhicule avec horodatage GPS';
        }
        if (str_contains($typeLow, 'vol')) {
            $actions[] = 'exiger le récépissé de plainte auprès des autorités';
            $actions[] = 'demander la liste des objets volés avec preuves d\'achat';
            if ($scoreGlobal >= 50) $actions[] = 'vérifier les accès et traces d\'effraction';
        }
        if (str_contains($typeLow, 'incendie')) {
            $actions[] = 'demander le rapport des pompiers ou procès-verbal d\'intervention';
            $actions[] = 'faire expertiser les dégâts par un expert sinistre agréé';
        }
        if (str_contains($typeLow, 'eau') || str_contains($typeLow, 'inondation')) {
            $actions[] = 'demander un rapport de plombier ou technicien certifié';
            $actions[] = 'vérifier les relevés météorologiques si applicable';
        }
        if (str_contains($typeLow, 'maladie') || str_contains($typeLow, 'décès')) {
            $actions[] = 'exiger les documents médicaux officiels (certificat médical, acte de décès)';
        }

        // Dédoublonnage
        return array_unique($actions);
    }

    private function recoFraude(string $type, int $score, string $causes, array $actions): string
    {
        $actTxt = !empty($actions)
            ? 'Actions requises avant tout traitement : ' . implode('; ', array_slice($actions, 0, 3)) . '.'
            : 'Soumettre le dossier à l\'équipe antifraud avant toute décision.';

        return "⚠️ FRAUDE DÉTECTÉE (score: {$score}/100) — Ce sinistre présente plusieurs signaux d'alerte sérieux : {$causes}. "
             . "Ne pas traiter ce dossier avant une investigation approfondie. "
             . "{$actTxt} "
             . "Si les justificatifs ne sont pas fournis dans les 5 jours ouvrables, recommander le refus.";
    }

    private function recoNormal(string $type, int $score, string $causes, array $actions): string
    {
        $actTxt = !empty($actions)
            ? 'Points à vérifier : ' . implode('; ', array_slice($actions, 0, 2)) . '.'
            : 'Vérification de routine recommandée.';

        return "🔍 RISQUE NORMAL (score: {$score}/100) — Points d'attention : {$causes}. "
             . "Le dossier peut être avancé mais nécessite une validation complémentaire. "
             . "{$actTxt} "
             . "Traitement possible après confirmation des éléments manquants.";
    }

    private function recoFaible(string $type, int $score, array $actions): string
    {
        $actTxt = !empty($actions)
            ? ' Vérifications habituelles : ' . implode('; ', array_slice($actions, 0, 2)) . '.'
            : '';

        return "✅ RISQUE FAIBLE (score: {$score}/100) — Profil cohérent, aucune anomalie majeure détectée. "
             . "Le dossier peut être traité selon la procédure standard.{$actTxt}";
    }

    /** Save analysis results to database */
    private function sauvegarder(array $data): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO fraud_analysis (
                id_sinistre, id_user, score_global, niveau_risque, suggestion_ia,
                score_texte, score_comportement, score_contrat, score_image,
                flag_description_vague, flag_sinistres_multiples, flag_contrat_recent,
                flag_montant_eleve, flag_image_suspecte,
                analyse_texte, analyse_comportement, analyse_image, recommandation_ia
            ) VALUES (
                :id_sinistre, :id_user, :score_global, :niveau_risque, :suggestion_ia,
                :score_texte, :score_comportement, :score_contrat, :score_image,
                :flag_description_vague, :flag_sinistres_multiples, :flag_contrat_recent,
                :flag_montant_eleve, :flag_image_suspecte,
                :analyse_texte, :analyse_comportement, :analyse_image, :recommandation_ia
            )
            ON DUPLICATE KEY UPDATE
                score_global             = VALUES(score_global),
                niveau_risque            = VALUES(niveau_risque),
                suggestion_ia            = VALUES(suggestion_ia),
                score_texte              = VALUES(score_texte),
                score_comportement       = VALUES(score_comportement),
                score_contrat            = VALUES(score_contrat),
                flag_description_vague   = VALUES(flag_description_vague),
                flag_sinistres_multiples = VALUES(flag_sinistres_multiples),
                flag_contrat_recent      = VALUES(flag_contrat_recent),
                flag_montant_eleve       = VALUES(flag_montant_eleve),
                analyse_texte            = VALUES(analyse_texte),
                analyse_comportement     = VALUES(analyse_comportement),
                recommandation_ia        = VALUES(recommandation_ia),
                date_analyse             = CURRENT_TIMESTAMP
        ");
        $stmt->execute($data);
    }

    public function getAnalyse(int $idSinistre): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM fraud_analysis WHERE id_sinistre = :id");
        $stmt->execute([':id' => $idSinistre]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /** Helper methods for scoring and suggestions */
    private function scoreToNiveau(int $score): string
    {
        return match(true) {
            $score >= 75 => 'fraude',
            $score >= 30 => 'normal',
            default      => 'faible',
        };
    }

    private function niveauToSuggestion(string $niveau): string
    {
        return match($niveau) {
            'fraude' => 'refuser',
            'normal' => 'investiguer',
            default  => 'accepter',
        };
    }
}
