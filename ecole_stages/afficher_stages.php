<?php
require_once __DIR__ . '/bootstrap.php';

// ---------- Suppression d'un stage (POST + CSRF + transaction) ----------
if (is_post() && ($_POST['action'] ?? '') === 'delete') {
    if (!csrf_valid()) {
        set_flash('err', 'Session expirée. Veuillez réessayer.');
    } else {
        $sid = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if (!$sid) {
            set_flash('err', 'Stage invalide.');
        } else {
            try {
                $pdo->beginTransaction();
                $pdo->prepare("DELETE FROM suivi_tuteur WHERE id_stage = ?")->execute([$sid]);
                $del = $pdo->prepare("DELETE FROM stage WHERE id = ?");
                $del->execute([$sid]);
                $pdo->commit();
                set_flash($del->rowCount() ? 'ok' : 'err', $del->rowCount() ? 'Stage supprimé.' : 'Stage introuvable.');
            } catch (PDOException $ex) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                error_log($ex);
                set_flash('err', 'Impossible de supprimer ce stage.');
            }
        }
    }
    redirect('afficher_stages.php');   // évite la re-soumission au rafraîchissement
}

// ---------- Requête (GROUP BY explicite : compatible ONLY_FULL_GROUP_BY) ----------
$stages = $pdo->query("
    SELECT
        s.id, s.sujet, s.date_debut, s.date_fin,
        e.id AS etudiant_id, e.nom AS etudiant_nom, e.prenom AS etudiant_prenom,
        en.id AS entreprise_id, en.nom AS entreprise_nom,
        GROUP_CONCAT(DISTINCT CONCAT(t.prenom, ' ', t.nom) ORDER BY t.nom, t.prenom SEPARATOR '||') AS tuteurs
    FROM stage s
    JOIN etudiant e   ON s.id_etudiant = e.id
    JOIN entreprise en ON s.id_entreprise = en.id
    LEFT JOIN suivi_tuteur st ON s.id = st.id_stage
    LEFT JOIN tuteur t ON st.id_tuteur = t.id
    GROUP BY s.id, s.sujet, s.date_debut, s.date_fin, e.id, e.nom, e.prenom, en.id, en.nom
    ORDER BY s.date_debut DESC, s.id DESC
")->fetchAll();

$total       = count($stages);
$etudiants   = count(array_unique(array_column($stages, 'etudiant_id')));
$entreprises = count(array_unique(array_column($stages, 'entreprise_id')));
$sansTuteur  = count(array_filter($stages, fn($s) => empty($s['tuteurs'])));

$fmt = fn($d) => $d ? date('d/m/Y', strtotime($d)) : '—';

/** Statut calculé à partir des dates. */
function stage_status(string $debut, string $fin): array
{
    $today = date('Y-m-d');
    if ($today < $debut) return ['À venir',  'border border-dashed border-current'];
    if ($today > $fin)   return ['Terminé',  'border border-current opacity-60'];
    return ['● En cours', 'border border-current font-medium'];
}

$pageTitle = 'Liste des stages';
$eyebrow   = "01 — Vue d'ensemble";
require __DIR__ . '/partials/head.php';

flash();
?>

<!-- Statistiques -->
<div class="stagger mb-8 grid grid-cols-2 gap-4 lg:grid-cols-4">
    <?php foreach ([['Stages', $total], ['Étudiants', $etudiants], ['Entreprises', $entreprises], ['Sans tuteur', $sansTuteur]] as [$label, $value]): ?>
        <div class="border-2 border-black bg-white p-5 shadow-[4px_4px_0_0_#000] transition-transform duration-300 hover:-translate-y-1">
            <p class="font-mono text-[11px] uppercase tracking-[0.18em] text-black/60"><?= e($label) ?></p>
            <p class="mt-1 text-4xl font-bold tabular-nums" data-count="<?= (int) $value ?>">0</p>
        </div>
    <?php endforeach; ?>
</div>

<!-- Barre d'outils -->
<div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between animate-rise" style="animation-delay:.25s">
    <input id="q" type="search" placeholder="Rechercher (étudiant, entreprise, sujet, statut…)"
           class="field sm:max-w-sm" autocomplete="off" aria-label="Rechercher un stage">
    <a href="ajouter_stage.php" class="btn !w-auto text-center sm:!px-6"><span>+ Nouveau stage</span></a>
</div>

<!-- Tableau -->
<div class="overflow-x-auto border-2 border-black bg-white shadow-[8px_8px_0_0_#000] animate-rise" style="animation-delay:.3s">
    <table class="min-w-full text-left">
        <thead class="bg-black font-mono text-[11px] uppercase tracking-[0.18em] text-white">
            <tr>
                <th class="px-4 py-3 font-medium">Étudiant</th>
                <th class="px-4 py-3 font-medium">Entreprise</th>
                <th class="px-4 py-3 font-medium">Sujet</th>
                <th class="px-4 py-3 font-medium whitespace-nowrap">Période</th>
                <th class="px-4 py-3 font-medium">Statut</th>
                <th class="px-4 py-3 font-medium">Tuteurs</th>
                <th class="px-4 py-3 font-medium text-right">Actions</th>
            </tr>
        </thead>
        <tbody id="rows">
            <?php foreach ($stages as $i => $stage):
                [$statusLabel, $statusClass] = stage_status($stage['date_debut'], $stage['date_fin']);
                $nomComplet = $stage['etudiant_prenom'] . ' ' . $stage['etudiant_nom'];
            ?>
                <tr class="animate-rise border-b border-black/15 transition-colors duration-200 hover:bg-black hover:text-white"
                    style="animation-delay: <?= min(350 + $i * 50, 1000) ?>ms">
                    <td class="px-4 py-3 font-medium whitespace-nowrap"><?= e($nomComplet) ?></td>
                    <td class="px-4 py-3"><?= e($stage['entreprise_nom']) ?></td>
                    <td class="px-4 py-3"><?= e($stage['sujet']) ?></td>
                    <td class="px-4 py-3 font-mono text-xs whitespace-nowrap">
                        <?= e($fmt($stage['date_debut'])) ?> → <?= e($fmt($stage['date_fin'])) ?>
                    </td>
                    <td class="px-4 py-3">
                        <span class="inline-block px-2 py-0.5 font-mono text-[11px] whitespace-nowrap <?= $statusClass ?>"><?= e($statusLabel) ?></span>
                    </td>
                    <td class="px-4 py-3">
                        <?php if (!empty($stage['tuteurs'])): ?>
                            <div class="flex flex-wrap gap-1.5">
                                <?php foreach (explode('||', $stage['tuteurs']) as $tuteur): ?>
                                    <span class="border border-current px-2 py-0.5 font-mono text-[11px]"><?= e($tuteur) ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <span class="font-mono text-xs opacity-50">Aucun</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex justify-end gap-1.5 whitespace-nowrap">
                            <a href="associer_tuteur.php?stage=<?= (int) $stage['id'] ?>" class="btn-ghost !px-2.5 !py-1 !text-[10px]">Tuteurs</a>
                            <a href="ajouter_stage.php?id=<?= (int) $stage['id'] ?>" class="btn-ghost !px-2.5 !py-1 !text-[10px]">Modifier</a>
                            <button type="button" class="btn-ghost !px-2.5 !py-1 !text-[10px] js-delete"
                                    data-id="<?= (int) $stage['id'] ?>"
                                    data-label="<?= e($nomComplet . ' — ' . $stage['sujet']) ?>">Supprimer</button>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <p id="empty" class="<?= $total ? 'hidden ' : '' ?>px-4 py-10 text-center font-mono text-xs uppercase tracking-widest text-black/60">
        <?= $total ? 'Aucun résultat' : 'Aucun stage enregistré' ?>
    </p>
</div>

<!-- Confirmation de suppression -->
<dialog id="confirm" class="m-auto w-[calc(100%-2rem)] max-w-md border-2 border-black p-0 shadow-[8px_8px_0_0_#000] backdrop:bg-black/50 backdrop:backdrop-blur-sm">
    <form method="post" class="space-y-5 p-6">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" id="confirm-id">
        <p class="label !mb-0">Confirmation</p>
        <h2 class="text-2xl font-bold">Supprimer ce stage ?</h2>
        <p id="confirm-text" class="break-words text-sm text-black/70"></p>
        <p class="font-mono text-[11px] uppercase tracking-wider">Les associations de tuteurs seront aussi supprimées.</p>
        <div class="flex gap-3">
            <button type="button" id="cancel" class="btn-ghost flex-1 !py-3">Annuler</button>
            <button type="submit" class="btn flex-1"><span>Supprimer</span></button>
        </div>
    </form>
</dialog>

<script>
    // Compteurs animés
    document.querySelectorAll('[data-count]').forEach(el => {
        const target = +el.dataset.count;
        const step = Math.max(1, Math.ceil(target / 30));
        let n = 0;
        const id = setInterval(() => {
            n = Math.min(target, n + step);
            el.textContent = n;
            if (n >= target) clearInterval(id);
        }, 25);
    });

    // Filtre instantané
    const q = document.getElementById('q');
    const rows = [...document.querySelectorAll('#rows tr')];
    const empty = document.getElementById('empty');
    q.addEventListener('input', () => {
        const v = q.value.trim().toLowerCase();
        let visible = 0;
        rows.forEach(r => {
            const show = r.textContent.toLowerCase().includes(v);
            r.classList.toggle('hidden', !show);
            if (show) visible++;
        });
        empty.textContent = 'Aucun résultat';
        empty.classList.toggle('hidden', visible > 0 || rows.length === 0);
    });

    // Boîte de confirmation
    const dlg = document.getElementById('confirm');
    document.querySelectorAll('.js-delete').forEach(btn => btn.addEventListener('click', () => {
        document.getElementById('confirm-id').value = btn.dataset.id;
        document.getElementById('confirm-text').textContent = btn.dataset.label;
        dlg.showModal();
    }));
    document.getElementById('cancel').addEventListener('click', () => dlg.close());
    dlg.addEventListener('click', e => { if (e.target === dlg) dlg.close(); });
</script>

<?php require __DIR__ . '/partials/foot.php'; ?>