<?php
// partials/head.php — en-tête commun (styles, animations, navigation)
// Variables attendues : $pageTitle, $eyebrow

$current = basename($_SERVER['PHP_SELF']);
$nav = [
    'afficher_stages.php'    => 'Stages',
    'ajouter_stage.php'      => 'Nouveau stage',
    'ajouter_etudiant.php'   => 'Étudiant',
    'ajouter_entreprise.php' => 'Entreprise',
    'ajouter_tuteur.php'     => 'Tuteur',
    'associer_tuteur.php'    => 'Associer',
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle ?? 'Gestion des stages') ?> — Stages</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500&family=Space+Grotesk:wght@400;500;700&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Space Grotesk"', 'system-ui', 'sans-serif'],
                        mono: ['"IBM Plex Mono"', 'ui-monospace', 'monospace'],
                    },
                },
            },
        };
    </script>

    <!-- Fond en grille (Uiverse.io — Gautammsharma) + animations -->
    <style>
        .grid-wrapper {
            min-height: 100vh;
            width: 100%;
            position: relative;
            background-color: #f8fafc;
        }
        .grid-background {
            position: absolute;
            inset: 0;
            z-index: 0;
            pointer-events: none;
            background-image: linear-gradient(to right, #e2e8f0 1px, transparent 1px),
                              linear-gradient(to bottom, #e2e8f0 1px, transparent 1px);
            background-size: 20px 30px;
            -webkit-mask-image: radial-gradient(ellipse 70% 60% at 50% 0%, #000 60%, transparent 100%);
            mask-image: radial-gradient(ellipse 70% 60% at 50% 0%, #000 60%, transparent 100%);
            animation: drift 14s linear infinite;
        }

        @keyframes drift { to { background-position: 20px 30px; } }
        @keyframes rise  { from { opacity: 0; transform: translateY(18px); } to { opacity: 1; transform: none; } }
        @keyframes pop   { 0% { opacity: 0; transform: scale(.96) translateY(-6px); } 100% { opacity: 1; transform: none; } }
        @keyframes grow  { from { transform: scaleX(0); } to { transform: scaleX(1); } }

        .animate-rise { animation: rise .6s cubic-bezier(.2,.8,.2,1) both; }
        .animate-pop  { animation: pop .35s cubic-bezier(.2,.8,.2,1) both; }
        .line-grow    { transform-origin: left; animation: grow .7s .2s cubic-bezier(.2,.8,.2,1) both; }
        .card         { animation: rise .6s .05s cubic-bezier(.2,.8,.2,1) both; }

        /* Apparition en cascade des champs d'un formulaire */
        .stagger > * { animation: rise .5s cubic-bezier(.2,.8,.2,1) both; }
        .stagger > *:nth-child(1) { animation-delay: .10s; }
        .stagger > *:nth-child(2) { animation-delay: .17s; }
        .stagger > *:nth-child(3) { animation-delay: .24s; }
        .stagger > *:nth-child(4) { animation-delay: .31s; }
        .stagger > *:nth-child(5) { animation-delay: .38s; }
        .stagger > *:nth-child(6) { animation-delay: .45s; }
        .stagger > *:nth-child(7) { animation-delay: .52s; }
        .stagger > *:nth-child(8) { animation-delay: .59s; }

        /* Bouton : balayage blanc au survol */
        .btn::before {
            content: '';
            position: absolute;
            inset: 0;
            background: #fff;
            transform: translateX(-101%);
            transition: transform .35s cubic-bezier(.2,.8,.2,1);
            z-index: 0;
        }
        .btn:hover::before, .btn:focus-visible::before { transform: translateX(0); }
        .btn > span { position: relative; z-index: 1; }
        .btn:focus-visible { outline: 3px solid #000; outline-offset: 3px; }


        .field[aria-invalid="true"] {
            background-image: repeating-linear-gradient(135deg, #fff, #fff 6px, #f1f5f9 6px, #f1f5f9 12px);
            border-style: dashed;
        }
        dialog[open] { animation: pop .25s cubic-bezier(.2,.8,.2,1) both; }


        /* ---------- Messages (alertes) ---------- */
        .alert {
            position: relative;
            display: flex;
            align-items: flex-start;
            gap: .875rem;
            margin-bottom: 1.5rem;
            padding: .875rem 1rem;
            border: 2px solid #000;
            max-height: 14rem;
            overflow: hidden;
            animation: pop .4s cubic-bezier(.2,.8,.2,1) both;
            transition: opacity .3s ease, transform .3s ease, max-height .3s ease,
                        margin .3s ease, padding .3s ease, border-width .3s ease;
        }
        .alert.is-leaving {
            opacity: 0; transform: translateY(-8px);
            max-height: 0; margin-bottom: 0; padding-top: 0; padding-bottom: 0; border-width: 0;
        }
        .alert-icon {
            flex: none; display: grid; place-items: center;
            width: 2rem; height: 2rem;
            font-family: "IBM Plex Mono", monospace; font-weight: 500; font-size: .95rem;
        }
        .alert-body  { flex: 1; min-width: 0; }
        .alert-title { font-family: "IBM Plex Mono", monospace; font-size: 11px; text-transform: uppercase; letter-spacing: .2em; opacity: .7; }
        .alert-text  { margin-top: .125rem; font-size: .9rem; line-height: 1.35; overflow-wrap: anywhere; }
        .alert-close {
            flex: none; width: 1.75rem; height: 1.75rem; line-height: 1;
            font-size: 1.25rem; cursor: pointer; background: transparent;
            border: 2px solid transparent; color: inherit;
            transition: border-color .15s, transform .15s;
        }
        .alert-close:hover { border-color: currentColor; transform: rotate(90deg); }
        .alert-close:focus-visible { outline: 2px solid currentColor; outline-offset: 2px; }

        /* Succès : plein noir, icône inversée, barre de compte à rebours */
        .alert-ok { background: #000; color: #fff; box-shadow: 4px 4px 0 0 #94a3b8; }
        .alert-ok .alert-icon { background: #fff; color: #000; }
        .alert-bar {
            position: absolute; left: 0; bottom: 0; height: 3px; width: 100%;
            background: #fff; transform-origin: left;
            animation: countdown 5s linear forwards;
        }

        /* Erreur : fond hachuré, icône noire, petite secousse */
        .alert-err {
            background: repeating-linear-gradient(135deg, #fff, #fff 8px, #f1f5f9 8px, #f1f5f9 16px);
            color: #000; box-shadow: 4px 4px 0 0 #000;
            animation: pop .4s cubic-bezier(.2,.8,.2,1) both, shake .45s .1s ease-in-out;
        }
        .alert-err .alert-icon { background: #000; color: #fff; }

        @keyframes countdown { to { transform: scaleX(0); } }
        @keyframes shake {
            0%, 100% { translate: 0; }
            20% { translate: -6px; } 40% { translate: 5px; }
            60% { translate: -3px; } 80% { translate: 2px; }
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation: none !important; transition: none !important; }
        }
    </style>

    <style type="text/tailwindcss">
        @layer components {
            .card      { @apply bg-white border-2 border-black p-6 sm:p-8 shadow-[8px_8px_0_0_#000]; }
            .label     { @apply block font-mono text-[11px] uppercase tracking-[0.18em] mb-2; }
            .field     { @apply w-full bg-white border-2 border-black px-3 py-2.5 outline-none transition-all duration-200
                                focus:shadow-[4px_4px_0_0_#000] focus:-translate-x-0.5 focus:-translate-y-0.5; }
            .btn       { @apply relative w-full overflow-hidden border-2 border-black bg-black text-white px-4 py-3
                                font-mono text-xs uppercase tracking-[0.2em] transition-[color,transform] duration-300
                                hover:text-black active:scale-[.98]; }
            .btn-ghost { @apply border-2 border-black bg-white text-black px-4 py-2 font-mono text-xs uppercase tracking-[0.16em]
                                transition-colors duration-200 hover:bg-neutral-200 active:scale-[.97]
                                focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2; }
        }
    </style>
</head>
<body class="font-sans text-black antialiased selection:bg-black selection:text-white">

<div class="grid-wrapper">
    <div class="grid-background"></div>

    <header class="sticky top-0 z-20 border-b-2 border-black bg-white/90 backdrop-blur">
        <div class="mx-auto flex max-w-5xl items-center gap-6 px-4 sm:px-6">
            <a href="afficher_stages.php" class="shrink-0 py-4 font-mono text-sm font-medium tracking-tight">
                STAGES<span class="animate-pulse">_</span>
            </a>
            <nav class="-mb-0.5 flex overflow-x-auto" aria-label="Navigation principale">
                <?php foreach ($nav as $file => $label): $active = $file === $current; ?>
                    <a href="<?= $file ?>"
                       class="relative whitespace-nowrap px-3 py-4 font-mono text-[11px] uppercase tracking-[0.16em] transition-colors
                              <?= $active
                                  ? 'bg-black text-white'
                                  : 'hover:bg-black/5 after:absolute after:inset-x-3 after:bottom-2 after:h-0.5 after:origin-left after:scale-x-0 after:bg-black after:transition-transform after:duration-300 hover:after:scale-x-100' ?>"
                       <?= $active ? 'aria-current="page"' : '' ?>>
                        <?= $label ?>
                    </a>
                <?php endforeach; ?>
            </nav>
        </div>
    </header>

    <main class="relative z-10 mx-auto max-w-5xl px-4 py-10 sm:px-6 sm:py-14">
        <div class="mb-8 animate-rise">
            <p class="mb-2 font-mono text-[11px] uppercase tracking-[0.25em] text-black/60"><?= htmlspecialchars($eyebrow ?? '') ?></p>
            <h1 class="text-4xl font-bold tracking-tight sm:text-5xl"><?= htmlspecialchars($pageTitle ?? '') ?></h1>
            <div class="line-grow mt-4 h-1 w-20 bg-black"></div>
        </div>