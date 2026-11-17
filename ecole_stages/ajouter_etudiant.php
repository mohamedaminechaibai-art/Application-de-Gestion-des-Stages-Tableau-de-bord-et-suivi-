<?php
require_once __DIR__ . '/bootstrap.php';

$errors  = [];
$message = null;
$values  = ['nom' => '', 'prenom' => '', 'email' => '', 'formation' => ''];

if (is_post()) {
    foreach ($values as $k => $_) $values[$k] = input($k);

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

        // Doublon d'email
        if (!isset($errors['email'])) {
            $q = $pdo->prepare("SELECT COUNT(*) FROM etudiant WHERE email = ?");
            $q->execute([$values['email']]);
            if ($q->fetchColumn() > 0) $errors['email'] = 'Un étudiant avec cet email existe déjà.';
        }

        if (!$errors) {
            try {
                $pdo->prepare("INSERT INTO etudiant (nom, prenom, email, formation) VALUES (?, ?, ?, ?)")
                    ->execute([$values['nom'], $values['prenom'], $values['email'], $values['formation']]);
                set_flash('ok', 'Étudiant ajouté avec succès.');
                redirect('ajouter_etudiant.php');
            } catch (PDOException $ex) {
                error_log($ex);
                $message = ['err', "Erreur lors de l'ajout de l'étudiant."];
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

<div class="card max-w-2xl">
    <?php flash($message); ?>

    <form method="post" class="stagger space-y-5" novalidate>
        <?= csrf_field() ?>
        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label class="label" for="nom">Nom</label>
                <input id="nom" type="text" name="nom" required maxlength="100" class="field"
                       value="<?= e($values['nom']) ?>" <?= invalid($errors, 'nom') ?>>
                <?= field_error($errors, 'nom') ?>
            </div>
            <div>
                <label class="label" for="prenom">Prénom</label>
                <input id="prenom" type="text" name="prenom" required maxlength="100" class="field"
                       value="<?= e($values['prenom']) ?>" <?= invalid($errors, 'prenom') ?>>
                <?= field_error($errors, 'prenom') ?>
            </div>
        </div>
        <div>
            <label class="label" for="email">Email</label>
            <input id="email" type="email" name="email" required maxlength="150" class="field"
                   value="<?= e($values['email']) ?>" <?= invalid($errors, 'email') ?>>
            <?= field_error($errors, 'email') ?>
        </div>
        <div>
            <label class="label" for="formation">Formation</label>
            <input id="formation" type="text" name="formation" required maxlength="100" class="field"
                   value="<?= e($values['formation']) ?>" <?= invalid($errors, 'formation') ?>>
            <?= field_error($errors, 'formation') ?>
        </div>
        <button type="submit" class="btn"><span>Ajouter l'étudiant</span></button>
    </form>
</div>

<?php require __DIR__ . '/partials/foot.php'; ?>