<?php
require_once __DIR__ . '/bootstrap.php';

$stages = $pdo->query("
    SELECT s.id, s.sujet, e.nom, e.prenom
    FROM stage s
    JOIN etudiant e ON s.id_etudiant = e.id
    ORDER BY s.date_debut DESC, s.id DESC
")->fetchAll();

$tuteurs = $pdo->query("SELECT id, nom, prenom, fonction FROM tuteur ORDER BY nom, prenom")->fetchAll();

$errors  = [];
$message = null;

$stageId = is_post()
    ? (filter_input(INPUT_POST, 'id_stage', FILTER_VALIDATE_INT) ?: null)
    : (filter_input(INPUT_GET, 'stage', FILTER_VALIDATE_INT) ?: null);

$validStageIds  = array_map('intval', array_column($stages, 'id'));
$validTuteurIds = array_map('intval', array_column($tuteurs, 'id'));
$selected = [];

if (is_post()) {
    // Sélection soumise : uniquement des ids entiers, uniques et existants
    $raw = $_POST['tuteurs'] ?? [];
    $raw = is_array($raw) ? $raw : [];
    $selected = array_values(array_intersect(
        array_unique(array_filter(array_map('intval', $raw), fn($v) => $v > 0)),
        $validTuteurIds
    ));

    if (!csrf_valid()) {
        $message = ['err', 'Session expirée. Rechargez la page et réessayez.'];
    } elseif (!$stageId || !in_array($stageId, $validStageIds, true)) {
        $errors['id_stage'] = 'Veuillez choisir un stage valide.';
        $message = ['err', 'Veuillez corriger les champs signalés.'];
    } else {
        try {
            // Tout ou rien : si une insertion échoue, les anciennes associations sont conservées
            $pdo->beginTransaction();
            $pdo->prepare("DELETE FROM suivi_tuteur WHERE id_stage = ?")->execute([$stageId]);
            $ins = $pdo->prepare("INSERT INTO suivi_tuteur (id_stage, id_tuteur) VALUES (?, ?)");
            foreach ($selected as $idTuteur) {
                $ins->execute([$stageId, $idTuteur]);
            }
            $pdo->commit();

            $n = count($selected);
            set_flash('ok', $n === 0
                ? 'Aucun tuteur associé à ce stage.'
                : "$n tuteur" . ($n > 1 ? 's' : '') . ' associé' . ($n > 1 ? 's' : '') . ' avec succès.');
            redirect('associer_tuteur.php?stage=' . $stageId);
        } catch (PDOException $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log($ex);
            $message = ['err', "Erreur lors de l'association des tuteurs."];
        }
    }
} elseif ($stageId && in_array($stageId, $validStageIds, true)) {
    // Pré-cocher les tuteurs déjà associés
    $q = $pdo->prepare("SELECT id_tuteur FROM suivi_tuteur WHERE id_stage = ?");
    $q->execute([$stageId]);
    $selected = array_map('intval', $q->fetchAll(PDO::FETCH_COLUMN));
}

$pageTitle = 'Associer des tuteurs';
$eyebrow   = '06 — Suivi';
require __DIR__ . '/partials/head.php';
?>

<div class="card max-w-2xl">
    <?php flash($message); ?>

    <form method="post" class="stagger space-y-5" novalidate>
        <?= csrf_field() ?>
        <div>
            <label class="label" for="id_stage">Stage</label>
            <select id="id_stage" name="id_stage" required class="field" <?= invalid($errors, 'id_stage') ?>>
                <option value="">-- Choisir un stage --</option>
                <?php foreach ($stages as $s): ?>
                    <option value="<?= (int) $s['id'] ?>" <?= $stageId === (int) $s['id'] ? 'selected' : '' ?>>
                        <?= e($s['sujet']) ?> (<?= e($s['prenom'] . ' ' . $s['nom']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <?= field_error($errors, 'id_stage') ?>
        </div>

        <fieldset>
            <legend class="label">
                Tuteurs à associer
                <span class="ml-2 border border-black px-1.5 py-0.5 normal-case tracking-normal" id="count">0</span>
            </legend>
            <?php if ($tuteurs): ?>
                <div class="flex flex-wrap gap-2">
                    <?php foreach ($tuteurs as $t): ?>
                        <label class="cursor-pointer select-none">
                            <input type="checkbox" name="tuteurs[]" value="<?= (int) $t['id'] ?>" class="peer sr-only"
                                   <?= in_array((int) $t['id'], $selected, true) ? 'checked' : '' ?>>
                            <span class="block border-2 border-black px-3 py-2 transition-all duration-200
                                         hover:-translate-y-0.5 hover:shadow-[3px_3px_0_0_#000]
                                         peer-checked:bg-black peer-checked:text-white
                                         peer-focus-visible:outline peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2">
                                <span class="block text-sm"><?= e($t['prenom'] . ' ' . $t['nom']) ?></span>
                                <span class="block font-mono text-[10px] uppercase tracking-wider opacity-60"><?= e($t['fonction']) ?></span>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="font-mono text-xs text-black/60">
                    Aucun tuteur disponible.
                    <a href="ajouter_tuteur.php" class="underline underline-offset-4">Ajouter un tuteur</a>
                </p>
            <?php endif; ?>
        </fieldset>

        <button type="submit" class="btn"><span>Enregistrer l'association</span></button>
    </form>
</div>

<script>
    // Charger les tuteurs déjà associés quand on change de stage
    document.getElementById('id_stage').addEventListener('change', e => {
        location.href = 'associer_tuteur.php' + (e.target.value ? '?stage=' + e.target.value : '');
    });
    // Compteur de sélection
    const boxes = [...document.querySelectorAll('input[name="tuteurs[]"]')];
    const count = document.getElementById('count');
    const update = () => { count.textContent = boxes.filter(b => b.checked).length; };
    boxes.forEach(b => b.addEventListener('change', update)); update();
</script>

<?php require __DIR__ . '/partials/foot.php'; ?>