<?php
require_once __DIR__ . '/bootstrap.php';

$errors  = [];
$message = null;
$values  = ['nom' => '', 'prenom' => '', 'email' => '', 'formation' => ''];

/** Trim, collapse inner whitespace. */
function clean(string $s): string {
    return trim(preg_replace('/\s+/u', ' ', $s));
}

if (is_post()) {
    foreach ($values as $k => $_) $values[$k] = clean(input($k));

    // Normalisation
    $values['email']  = mb_strtolower($values['email']);
    $values['nom']    = mb_strtoupper($values['nom']);
    $values['prenom'] = mb_convert_case($values['prenom'], MB_CASE_TITLE, 'UTF-8');

    if (!csrf_valid()) {
        $message = ['err', 'Session expirée. Rechargez la page et réessayez.'];
    } else {
        require_text($errors, 'nom', $values['nom'], 'Le nom');
        require_text($errors, 'prenom', $values['prenom'], 'Le prénom');
        require_text($errors, 'formation', $values['formation'], 'La formation');

        if ($values['email'] === '' || mb_strlen($values['email']) > 150
            || !filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Adresse email invalide.';
        }

        // Friendly duplicate check (the DB unique index is the real guard)
        if (!isset($errors['email'])) {
            $q = $pdo->prepare("SELECT 1 FROM etudiant WHERE email = ? LIMIT 1");
            $q->execute([$values['email']]);
            if ($q->fetchColumn()) $errors['email'] = 'Un étudiant avec cet email existe déjà.';
        }

        if (!$errors) {
            try {
                $pdo->prepare("INSERT INTO etudiant (nom, prenom, email, formation) VALUES (?, ?, ?, ?)")
                    ->execute([$values['nom'], $values['prenom'], $values['email'], $values['formation']]);
                set_flash('ok', 'Étudiant ajouté avec succès.');
                redirect('ajouter_etudiant.php');
            } catch (PDOException $ex) {
                // 23000 = integrity violation (duplicate email inserted at the same moment)
                if ($ex->getCode() === '23000') {
                    $errors['email'] = 'Un étudiant avec cet email existe déjà.';
                    $message = ['err', 'Veuillez corriger les champs signalés.'];
                } else {
                    error_log($ex);
                    $message = ['err', "Erreur lors de l'ajout de l'étudiant."];
                }
            }
        } else {
            $message = ['err', 'Veuillez corriger les champs signalés.'];
        }
    }
}

$pageTitle = 'Ajouter un étudiant';
$eyebrow   = '03 — Étudiants';
require __DIR__ . '/partials/head.php';
?>
<!-- HTML form stays exactly the same -->
