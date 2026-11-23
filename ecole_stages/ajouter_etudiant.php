<style>
  @keyframes fadeUp { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: none; } }
  @keyframes shake  { 0%,100% { transform: translateX(0); } 25% { transform: translateX(-4px); } 75% { transform: translateX(4px); } }
  .fade-up { opacity: 0; animation: fadeUp .55s cubic-bezier(.2,.7,.2,1) forwards; }
  .shake   { animation: shake .35s ease; }
  @media (prefers-reduced-motion: reduce) { .fade-up, .shake { animation: none; opacity: 1; } }
</style>

<?php
$h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');

$fields = [
  ['nom',       'Nom',       'text',  'DUPONT',            'family-name', 0.10],
  ['prenom',    'Prénom',    'text',  'Jean',              'given-name',  0.18],
  ['email',     'Email',     'email', 'jean@exemple.com',  'email',       0.26],
  ['formation', 'Formation', 'text',  'Licence Informatique', 'off',      0.34],
];
?>

<section class="relative mx-auto w-full max-w-xl px-4 py-12 sm:py-16">

  <!-- Header -->
  <header class="fade-up mb-8" style="animation-delay:.02s">
    <p class="mb-3 inline-flex items-center gap-2 rounded-full border border-black/15 bg-white/80 px-3 py-1 text-[11px] font-medium uppercase tracking-[.2em] text-black/60 backdrop-blur">
      <span class="h-1.5 w-1.5 rounded-full bg-black"></span>
      <?= $h($eyebrow) ?>
    </p>
    <h1 class="text-3xl font-semibold tracking-tight text-black sm:text-4xl"><?= $h($pageTitle) ?></h1>
    <p class="mt-2 text-sm text-black/55">Renseignez les informations de l'étudiant. Tous les champs sont obligatoires.</p>
  </header>

  <!-- Alert -->
  <?php if ($message): ?>
    <div role="alert"
         class="fade-up shake mb-6 flex items-start gap-3 rounded-xl border px-4 py-3 text-sm
                <?= $message[0] === 'err' ? 'border-black bg-black text-white' : 'border-black/15 bg-white text-black' ?>">
      <svg class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/>
      </svg>
      <span><?= $h($message[1]) ?></span>
    </div>
  <?php endif; ?>

  <!-- Card -->
  <form method="post" novalidate
        class="fade-up rounded-2xl border border-black/10 bg-white/90 p-6 shadow-[0_10px_40px_-12px_rgba(0,0,0,.18)] backdrop-blur sm:p-8"
        style="animation-delay:.06s">
    <?= function_exists('csrf_field') ? csrf_field() : '' ?>

    <div class="space-y-5">
      <?php foreach ($fields as [$name, $label, $type, $ph, $ac, $delay]):
        $err = $errors[$name] ?? null; ?>
        <div class="fade-up" style="animation-delay:<?= $delay ?>s">
          <label for="<?= $name ?>" class="mb-1.5 block text-xs font-medium uppercase tracking-wider text-black/60">
            <?= $label ?>
          </label>
          <input id="<?= $name ?>" name="<?= $name ?>" type="<?= $type ?>"
                 value="<?= $h($values[$name]) ?>" placeholder="<?= $h($ph) ?>"
                 autocomplete="<?= $ac ?>" required
                 <?= $err ? 'aria-invalid="true"' : '' ?>
                 class="w-full rounded-xl border bg-white px-4 py-3 text-sm text-black placeholder:text-black/30
                        outline-none transition duration-200
                        focus:-translate-y-0.5 focus:border-black focus:shadow-[0_6px_18px_-8px_rgba(0,0,0,.35)]
                        <?= $err ? 'border-black shake' : 'border-black/15 hover:border-black/40' ?>">
          <?php if ($err): ?>
            <p class="mt-1.5 flex items-center gap-1.5 text-xs font-medium text-black">
              <span class="inline-block h-1 w-1 rounded-full bg-black"></span><?= $h($err) ?>
            </p>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Actions -->
    <div class="fade-up mt-8 flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between" style="animation-delay:.42s">
      <a href="afficher_stages.php"
         class="group inline-flex items-center justify-center gap-2 text-sm text-black/60 transition hover:text-black">
        <span class="transition-transform duration-200 group-hover:-translate-x-1">←</span> Retour
      </a>

      <button type="submit"
              class="group inline-flex items-center justify-center gap-2 rounded-xl bg-black px-6 py-3 text-sm font-medium text-white
                     transition duration-200 hover:-translate-y-0.5 hover:shadow-[0_10px_24px_-8px_rgba(0,0,0,.5)] active:translate-y-0 active:scale-[.98]
                     focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-black">
        Ajouter l'étudiant
        <span class="transition-transform duration-200 group-hover:translate-x-1">→</span>
      </button>
    </div>
  </form>
</section>
