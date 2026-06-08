#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Génération intelligente locale de proposition de contrat Protex.
Entrée  : JSON sur stdin depuis PHP.
Sortie  : JSON sur stdout.
Sans API externe, sans bibliothèque payante.
"""
import json
import sys
import unicodedata
from typing import Any, Dict, List, Tuple


def norm(value: Any) -> str:
    text = str(value or "").strip().lower()
    text = "".join(
        ch for ch in unicodedata.normalize("NFD", text)
        if unicodedata.category(ch) != "Mn"
    )
    return text


def yes(value: Any) -> bool:
    return norm(value) in {"oui", "yes", "true", "1", "neuf", "maison", "proprietaire"}


def number(value: Any, default: float = 0) -> float:
    try:
        return float(str(value).replace(",", "."))
    except Exception:
        return default


def get_formules(data: Dict[str, Any]) -> List[Dict[str, Any]]:
    formules = data.get("formules")
    if isinstance(formules, list) and formules:
        return formules
    return [
        {"nom_formule": "Classique", "nom_categorie": "Auto", "prix_formule": 80, "franchise_formule": 220},
        {"nom_formule": "Tierce collision", "nom_categorie": "Auto", "prix_formule": 150, "franchise_formule": 160},
        {"nom_formule": "Tous risques", "nom_categorie": "Auto", "prix_formule": 260, "franchise_formule": 90},
        {"nom_formule": "Économique", "nom_categorie": "Habitation", "prix_formule": 55, "franchise_formule": 180},
        {"nom_formule": "Privilège", "nom_categorie": "Habitation", "prix_formule": 140, "franchise_formule": 90},
        {"nom_formule": "Économique", "nom_categorie": "Santé", "prix_formule": 40, "franchise_formule": 200},
        {"nom_formule": "Confort", "nom_categorie": "Santé", "prix_formule": 110, "franchise_formule": 120},
        {"nom_formule": "Premium", "nom_categorie": "Santé", "prix_formule": 220, "franchise_formule": 60},
        {"nom_formule": "Sécurité", "nom_categorie": "Protection", "prix_formule": 35, "franchise_formule": 220},
        {"nom_formule": "Max Protection", "nom_categorie": "Protection", "prix_formule": 70, "franchise_formule": 150},
        {"nom_formule": "Premium plus", "nom_categorie": "Protection", "prix_formule": 120, "franchise_formule": 80},
    ]


def formule_name(f: Dict[str, Any]) -> str:
    return str(f.get("nom_formule") or f.get("nom") or "Formule personnalisée")


def formule_cat(f: Dict[str, Any]) -> str:
    return str(f.get("nom_categorie") or f.get("categorie") or "")


def formule_price(f: Dict[str, Any]) -> float:
    return number(f.get("prix_formule") or f.get("prix") or 0)


def formule_franchise(f: Dict[str, Any]) -> float:
    return number(f.get("franchise_formule") or f.get("franchise") or 0)


def category_page(categorie: str) -> str:
    c = norm(categorie)
    if "auto" in c:
        return "contrat_auto.php"
    if "habitation" in c:
        return "contrat_habitation.php"
    if "sante" in c:
        return "contrat_sante.php"
    if "protection" in c:
        return "contrat_protection.php"
    return "contrat.php"


def garantie_suggestions(categorie: str, data: Dict[str, Any]) -> List[str]:
    c = norm(categorie)
    besoin = norm(data.get("besoin"))
    objectif = norm(data.get("objectif"))
    garanties: List[str] = []

    if "auto" in c:
        garanties += ["Responsabilité civile", "Assistance dépannage"]
        if yes(data.get("conduite_quotidienne")) or "quotid" in besoin:
            garanties.append("Bris de glace")
        if not yes(data.get("stationnement")) or "vol" in besoin:
            garanties.append("Vol et incendie")
        if norm(data.get("vehicule_age")) == "neuf" or objectif == "couverture_max":
            garanties.append("Dommages tous accidents")
    elif "habitation" in c:
        garanties += ["Incendie", "Dégâts des eaux"]
        if norm(data.get("type_logement")) == "maison":
            garanties.append("Jardin et dépendances")
        if yes(data.get("zone_risque")) or objectif == "couverture_max":
            garanties.append("Catastrophes naturelles")
        garanties.append("Responsabilité civile habitation")
    elif "sante" in c:
        garanties += ["Consultations médicales", "Médicaments"]
        if yes(data.get("hospitalisation")):
            garanties.append("Hospitalisation complète")
        if yes(data.get("consultations_frequentes")):
            garanties.append("Consultations spécialistes")
        if yes(data.get("couverture_familiale")) or "famille" in besoin:
            garanties.append("Couverture familiale")
        if "optique" in besoin:
            garanties.append("Optique")
        if "dentaire" in besoin:
            garanties.append("Dentaire")
    elif "protection" in c:
        garanties += ["Assistance juridique", "Protection des données personnelles"]
        if yes(data.get("assistance_juridique")):
            garanties.append("Défense en cas de litige")
        if yes(data.get("securite_voyage")) or "voyage" in besoin:
            garanties.append("Assistance voyage")
        if yes(data.get("protection_personnelle")) or "identite" in besoin or "fraude" in besoin:
            garanties.append("Protection identité numérique")
    else:
        garanties += ["Assistance", "Couverture de base", "Suivi personnalisé"]

    seen = set()
    clean = []
    for g in garanties:
        if g not in seen:
            clean.append(g)
            seen.add(g)
    return clean


def score_formula(f: Dict[str, Any], data: Dict[str, Any]) -> Tuple[float, List[str]]:
    budget = number(data.get("budget"), 0)
    objectif = norm(data.get("objectif"))
    risque = norm(data.get("risque"))
    franchise_pref = norm(data.get("franchise_pref"))
    prix = formule_price(f)
    franchise = formule_franchise(f)

    score = 50.0
    reasons: List[str] = []

    if budget > 0:
        if prix <= budget:
            score += 30
            reasons.append("La prime proposée respecte le budget mensuel indiqué.")
        else:
            ecart = prix - budget
            score -= min(35, ecart / 4)
            reasons.append("La formule dépasse légèrement le budget, mais reste pertinente selon le niveau de protection demandé.")

    if objectif == "prix_bas":
        score += max(0, 25 - prix / 8)
        reasons.append("La priorité donnée au prix a favorisé les formules économiques.")
    elif objectif == "franchise_faible":
        score += max(0, 30 - franchise / 7)
        reasons.append("La franchise souhaitée a été prise en compte dans le calcul.")
    elif objectif == "couverture_max":
        score += 30 if prix >= max(1, budget) * 0.50 else 12
        reasons.append("Le besoin de couverture maximale oriente vers une formule plus complète.")
    elif objectif == "equilibre":
        score += 25 if prix <= max(1, budget) and franchise <= 180 else 10
        reasons.append("La formule présente un bon équilibre entre prix, franchise et garanties.")

    if risque == "eleve":
        score += 18 if prix >= max(1, budget) * 0.45 else 6
        reasons.append("Le niveau de risque élevé nécessite une couverture plus solide.")
    elif risque == "moyen":
        score += 12
        reasons.append("Le niveau de risque moyen correspond à une formule équilibrée.")
    elif risque == "faible":
        score += 14 if prix <= max(1, budget) * 0.85 else 5
        reasons.append("Le niveau de risque faible permet d’éviter une couverture trop coûteuse.")

    if franchise_pref == "basse":
        score += 18 if franchise <= 120 else -8
    elif franchise_pref == "moyenne":
        score += 12 if 80 <= franchise <= 220 else 4
    elif franchise_pref == "peu_importe":
        score += 5

    # Bonus métier selon catégories
    cat = norm(formule_cat(f))
    if "auto" in cat:
        if yes(data.get("conduite_quotidienne")):
            score += 8
        if not yes(data.get("stationnement")):
            score += 8
        if norm(data.get("vehicule_age")) == "neuf":
            score += 8
    elif "habitation" in cat:
        if norm(data.get("type_logement")) == "maison":
            score += 8
        if yes(data.get("zone_risque")):
            score += 10
    elif "sante" in cat:
        if yes(data.get("hospitalisation")):
            score += 10
        if yes(data.get("consultations_frequentes")):
            score += 8
        if yes(data.get("couverture_familiale")):
            score += 8
    elif "protection" in cat:
        if yes(data.get("assistance_juridique")):
            score += 8
        if yes(data.get("protection_personnelle")):
            score += 8
        if yes(data.get("securite_voyage")):
            score += 6

    return max(1, min(100, round(score))), reasons[:4]


def choose_best(data: Dict[str, Any]) -> Dict[str, Any]:
    categorie = str(data.get("categorie") or "").strip()
    c_norm = norm(categorie)
    formules = get_formules(data)
    candidates = [f for f in formules if c_norm and (c_norm in norm(formule_cat(f)) or norm(formule_cat(f)) in c_norm)]
    if not candidates:
        candidates = formules

    best = None
    best_score = -999
    best_reasons: List[str] = []
    for f in candidates:
        sc, rs = score_formula(f, data)
        if sc > best_score:
            best = f
            best_score = sc
            best_reasons = rs

    if not best:
        best = {"nom_formule": "Formule personnalisée", "nom_categorie": categorie, "prix_formule": 0, "franchise_formule": 0}
        best_score = 50
        best_reasons = ["Une proposition personnalisée a été générée à partir des besoins fournis."]

    cat = formule_cat(best) or categorie
    name = formule_name(best)
    garanties = garantie_suggestions(cat, data)
    besoin = str(data.get("besoin") or "").strip()

    resume = (
        f"Le système propose le contrat {cat} « {name} » car il correspond au budget, au niveau de risque "
        f"et aux priorités indiquées par le client. "
    )
    if besoin:
        resume += f"Le besoin exprimé (« {besoin} ») a été utilisé pour renforcer les garanties conseillées. "
    resume += "Cette proposition reste modifiable avant la création finale du contrat."

    return {
        "success": True,
        "categorie": cat,
        "formule": name,
        "score": int(best_score),
        "prime": formule_price(best),
        "franchise": formule_franchise(best),
        "garanties": garanties,
        "raisons": best_reasons or ["La formule présente le meilleur score selon les réponses du client."],
        "resume": resume,
        "page": category_page(cat),
        "engine": "Python local - règles métier Protex",
    }


def main() -> None:
    try:
        raw = sys.stdin.read()
        data = json.loads(raw or "{}")
        result = choose_best(data)
        print(json.dumps(result, ensure_ascii=False))
    except Exception as exc:
        print(json.dumps({"success": False, "message": str(exc)}, ensure_ascii=False))
        sys.exit(1)


if __name__ == "__main__":
    main()
