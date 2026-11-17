<?php
require_once __DIR__ . '/bootstrap.php';

$errors  = [];
$message = null;
$values  = ['nom' => '', 'secteur' => '', 'adresse' => ''];

if (is_post()) {
    foreach ($values as $k => $_) $values[$k] = input($k);

    if (!csrf_valid()) {
        $message = ['err', 'Session expirée. Rechargez la page et réessayez.'];
    } else {
        require_text($errors, 'nom', $values['nom'], "Le nom de l'entreprise");
        require_text($errors, 'secteur', $values['secteur'], 'Le secteur');
        require_text($errors, 'adresse', $values['adresse'], "L'adresse", 255);

        // Doublon (même nom + même adresse)
        if (!$errors) {
            $q = $pdo->prepare("SELECT COUNT(*) FROM entreprise WHERE nom = ? AND adresse = ?");
            $q->execute([$values['nom'], $values['adresse']]);
            if ($q->fetchColumn() > 0) $errors['nom'] = 'Cette entreprise existe déjà à cette adresse.';
        }

        if (!$errors) {
            try {
                $pdo->prepare("INSERT INTO entreprise (nom, secteur, adresse) VALUES (?, ?, ?)")
                    ->execute([$values['nom'], $values['secteur'], $values['adresse']]);
                set_flash('ok', 'Entreprise ajoutée avec succès.');
                redirect('ajouter_entreprise.php');
            } catch (PDOException $ex) {
                error_log($ex);
                $message = ['err', "Erreur lors de l'ajout de l'entreprise."];
            }
        } else {
            $message = ['err', 'Veuillez corriger les champs signalés.'];
        }
    }
}

$pageTitle = 'Ajouter une entreprise';
$eyebrow   = '04 — Entreprises';
require __DIR__ . '/partials/head.php';
?>

<div class="card max-w-2xl">
    <?php flash($message); ?>

    <form method="post" class="stagger space-y-5" novalidate>
        <?= csrf_field() ?>
        <div>
            <label class="label" for="nom">Nom de l'entreprise</label>
            <input id="nom" type="text" name="nom" required maxlength="100" class="field"
                   value="<?= e($values['nom']) ?>" <?= invalid($errors, 'nom') ?>>
            <?= field_error($errors, 'nom') ?>
        </div>
        <div>
            <label class="label" for="secteur">Secteur</label>
            <input id="secteur" type="text" name="secteur" required maxlength="100" class="field"
                   value="<?= e($values['secteur']) ?>" <?= invalid($errors, 'secteur') ?>>
            <?= field_error($errors, 'secteur') ?>
        </div>
        <div>
            <label class="label" for="adresse">Adresse</label>
            <input id="adresse" type="text" name="adresse" required maxlength="255" class="field"
                   value="<?= e($values['adresse']) ?>" <?= invalid($errors, 'adresse') ?>>
            <?= field_error($errors, 'adresse') ?>
        </div>
        <button type="submit" class="btn"><span>Ajouter l'entreprise</span></button>
    </form>
</div>

<?php require __DIR__ . '/partials/foot.php'; ?>