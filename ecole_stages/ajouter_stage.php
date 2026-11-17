<?php
require_once __DIR__ . '/bootstrap.php';

// Mode édition si ?id=… est présent
$id    = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: null;
$stage = null;
if ($id) {
    $q = $pdo->prepare("SELECT * FROM stage WHERE id = ?");
    $q->execute([$id]);
    $stage = $q->fetch();
    if (!$stage) {
        set_flash('err', 'Stage introuvable.');
        redirect('afficher_stages.php');
    }
}
$editing = $stage !== null;

$etudiants   = $pdo->query("SELECT id, nom, prenom FROM etudiant ORDER BY nom, prenom")->fetchAll();
$entreprises = $pdo->query("SELECT id, nom FROM entreprise ORDER BY nom")->fetchAll();

$errors  = [];
$message = null;
$values  = [
    'sujet'         => $stage['sujet']         ?? '',
    'date_debut'    => $stage['date_debut']    ?? '',
    'date_fin'      => $stage['date_fin']      ?? '',
    'id_etudiant'   => $stage['id_etudiant']   ?? '',
    'id_entreprise' => $stage['id_entreprise'] ?? '',
];

if (is_post()) {
    foreach ($values as $k => $_) $values[$k] = input($k);

    if (!csrf_valid()) {
        $message = ['err', 'Session expirée. Rechargez la page et réessayez.'];
    } else {
        require_text($errors, 'sujet', $values['sujet'], 'Le sujet', 255);

        // Dates : format valide + fin >= début
        foreach (['date_debut' => 'La date de début', 'date_fin' => 'La date de fin'] as $k => $label) {
            if (!valid_date($values[$k])) $errors[$k] = "$label est invalide.";
        }
        if (!$errors && $values['date_fin'] < $values['date_debut']) {
            $errors['date_fin'] = 'La date de fin doit être postérieure ou égale au début.';
        }

        // Étudiant / entreprise : doivent exister
        $etuIds = array_map('intval', array_column($etudiants, 'id'));
        $entIds = array_map('intval', array_column($entreprises, 'id'));
        if (!ctype_digit($values['id_etudiant']) || !in_array((int) $values['id_etudiant'], $etuIds, true)) {
            $errors['id_etudiant'] = 'Veuillez choisir un étudiant.';
        }
        if (!ctype_digit($values['id_entreprise']) || !in_array((int) $values['id_entreprise'], $entIds, true)) {
            $errors['id_entreprise'] = 'Veuillez choisir une entreprise.';
        }

        // Un étudiant ne peut pas avoir deux stages qui se chevauchent
        if (!$errors) {
            $q = $pdo->prepare(
                "SELECT COUNT(*) FROM stage
                 WHERE id_etudiant = ? AND date_debut <= ? AND date_fin >= ? AND id <> ?"
            );
            $q->execute([$values['id_etudiant'], $values['date_fin'], $values['date_debut'], $id ?? 0]);
            if ($q->fetchColumn() > 0) {
                $errors['date_debut'] = 'Cet étudiant a déjà un stage sur cette période.';
            }
        }

        if (!$errors) {
            try {
                $params = [$values['sujet'], $values['date_debut'], $values['date_fin'],
                           (int) $values['id_etudiant'], (int) $values['id_entreprise']];
                if ($editing) {
                    $pdo->prepare("UPDATE stage SET sujet = ?, date_debut = ?, date_fin = ?, id_etudiant = ?, id_entreprise = ? WHERE id = ?")
                        ->execute([...$params, $id]);
                    set_flash('ok', 'Stage modifié avec succès.');
                    redirect('afficher_stages.php');
                }
                $pdo->prepare("INSERT INTO stage (sujet, date_debut, date_fin, id_etudiant, id_entreprise) VALUES (?, ?, ?, ?, ?)")
                    ->execute($params);
                $newId = (int) $pdo->lastInsertId();
                set_flash('ok', 'Stage enregistré. Associez maintenant un ou plusieurs tuteurs.');
                redirect('associer_tuteur.php?stage=' . $newId);
            } catch (PDOException $ex) {
                error_log($ex);
                $message = ['err', "Erreur lors de l'enregistrement du stage."];
            }
        } else {
            $message = ['err', 'Veuillez corriger les champs signalés.'];
        }
    }
}

$pageTitle = $editing ? 'Modifier le stage' : 'Ajouter un stage';
$eyebrow   = $editing ? '02 — Édition' : '02 — Stages';
require __DIR__ . '/partials/head.php';
?>

<div class="card max-w-2xl">
    <?php flash($message); ?>

    <form method="post" class="stagger space-y-5" novalidate>
        <?= csrf_field() ?>
        <div>
            <label class="label" for="sujet">Sujet du stage</label>
            <input id="sujet" type="text" name="sujet" required maxlength="255" class="field"
                   value="<?= e($values['sujet']) ?>" <?= invalid($errors, 'sujet') ?>>
            <?= field_error($errors, 'sujet') ?>
        </div>
        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label class="label" for="date_debut">Date de début</label>
                <input id="date_debut" type="date" name="date_debut" required class="field"
                       value="<?= e($values['date_debut']) ?>" <?= invalid($errors, 'date_debut') ?>>
                <?= field_error($errors, 'date_debut') ?>
            </div>
            <div>
                <label class="label" for="date_fin">Date de fin</label>
                <input id="date_fin" type="date" name="date_fin" required class="field"
                       value="<?= e($values['date_fin']) ?>" <?= invalid($errors, 'date_fin') ?>>
                <?= field_error($errors, 'date_fin') ?>
            </div>
        </div>
        <div>
            <label class="label" for="id_etudiant">Étudiant</label>
            <select id="id_etudiant" name="id_etudiant" required class="field" <?= invalid($errors, 'id_etudiant') ?>>
                <option value="">-- Choisir un étudiant --</option>
                <?php foreach ($etudiants as $et): ?>
                    <option value="<?= (int) $et['id'] ?>" <?= (string) $values['id_etudiant'] === (string) $et['id'] ? 'selected' : '' ?>>
                        <?= e($et['prenom'] . ' ' . $et['nom']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?= field_error($errors, 'id_etudiant') ?>
        </div>
        <div>
            <label class="label" for="id_entreprise">Entreprise</label>
            <select id="id_entreprise" name="id_entreprise" required class="field" <?= invalid($errors, 'id_entreprise') ?>>
                <option value="">-- Choisir une entreprise --</option>
                <?php foreach ($entreprises as $en): ?>
                    <option value="<?= (int) $en['id'] ?>" <?= (string) $values['id_entreprise'] === (string) $en['id'] ? 'selected' : '' ?>>
                        <?= e($en['nom']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?= field_error($errors, 'id_entreprise') ?>
        </div>
        <div class="flex flex-col gap-3 sm:flex-row">
            <button type="submit" class="btn"><span><?= $editing ? 'Enregistrer les modifications' : 'Enregistrer le stage' ?></span></button>
            <?php if ($editing): ?>
                <a href="afficher_stages.php" class="btn-ghost text-center sm:w-40 !py-3">Annuler</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<script>
    // La date de fin ne peut pas précéder la date de début
    const d = document.getElementById('date_debut'), f = document.getElementById('date_fin');
    const sync = () => { f.min = d.value || ''; };
    d.addEventListener('change', sync); sync();
</script>

<?php require __DIR__ . '/partials/foot.php'; ?>