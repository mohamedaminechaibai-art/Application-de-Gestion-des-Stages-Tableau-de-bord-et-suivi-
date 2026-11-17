<?php
require_once __DIR__ . '/bootstrap.php';

$errors  = [];
$message = null;
$values  = ['nom' => '', 'prenom' => '', 'fonction' => ''];

if (is_post()) {
    foreach ($values as $k => $_) $values[$k] = input($k);

    if (!csrf_valid()) {
        $message = ['err', 'Session expirée. Rechargez la page et réessayez.'];
    } else {
        require_text($errors, 'nom', $values['nom'], 'Le nom');
        require_text($errors, 'prenom', $values['prenom'], 'Le prénom');
        require_text($errors, 'fonction', $values['fonction'], 'La fonction');

        // Doublon exact (nom + prénom + fonction)
        if (!$errors) {
            $q = $pdo->prepare("SELECT COUNT(*) FROM tuteur WHERE nom = ? AND prenom = ? AND fonction = ?");
            $q->execute([$values['nom'], $values['prenom'], $values['fonction']]);
            if ($q->fetchColumn() > 0) $errors['nom'] = 'Ce tuteur existe déjà.';
        }

        if (!$errors) {
            try {
                $pdo->prepare("INSERT INTO tuteur (nom, prenom, fonction) VALUES (?, ?, ?)")
                    ->execute([$values['nom'], $values['prenom'], $values['fonction']]);
                set_flash('ok', 'Tuteur ajouté avec succès.');
                redirect('ajouter_tuteur.php');
            } catch (PDOException $ex) {
                error_log($ex);
                $message = ['err', "Erreur lors de l'ajout du tuteur."];
            }
        } else {
            $message = ['err', 'Veuillez corriger les champs signalés.'];
        }
    }
}

$pageTitle = 'Ajouter un tuteur';
$eyebrow   = '05 — Tuteurs';
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
            <label class="label" for="fonction">Fonction</label>
            <input id="fonction" type="text" name="fonction" required maxlength="100" class="field"
                   value="<?= e($values['fonction']) ?>" <?= invalid($errors, 'fonction') ?>>
            <?= field_error($errors, 'fonction') ?>
        </div>
        <button type="submit" class="btn"><span>Ajouter le tuteur</span></button>
    </form>
</div>

<?php require __DIR__ . '/partials/foot.php'; ?>