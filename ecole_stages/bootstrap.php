<?php
/**
 * bootstrap.php — à inclure en tout premier dans chaque page.
 * Session, connexion PDO, CSRF, messages flash, validation, gestion d'erreurs.
 */

// ---------- En-têtes de sécurité ----------
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');

// ---------- Session ----------
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']),
    ]);
    session_start();
}

// ---------- Gestion des erreurs : on journalise, on n'affiche jamais le détail ----------
set_exception_handler(function (Throwable $t) {
    error_log($t);
    http_response_code(500);
    echo '<!DOCTYPE html><html lang="fr"><meta charset="UTF-8"><title>Erreur</title>'
       . '<body style="font-family:ui-monospace,monospace;display:grid;place-items:center;min-height:100vh;margin:0">'
       . '<div style="border:2px solid #000;padding:2rem;box-shadow:8px 8px 0 #000;max-width:28rem">'
       . '<h1 style="margin:0 0 .5rem">Erreur serveur</h1>'
       . '<p>Une erreur inattendue est survenue. Veuillez réessayer dans un instant.</p>'
       . '<a href="afficher_stages.php" style="color:#000">← Retour à la liste</a></div></body></html>';
    exit;
});

// ---------- Base de données ----------
require_once __DIR__ . '/config/db.php';   // doit définir $pdo
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

// ---------- Helpers ----------
function e(?string $v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function is_post(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

/** Lit un champ POST, nettoyé (trim) ; toujours une chaîne. */
function input(string $key): string
{
    $v = $_POST[$key] ?? '';
    return is_string($v) ? trim($v) : '';
}

function redirect(string $to): void
{
    header('Location: ' . $to);
    exit;
}

// ---------- CSRF ----------
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_valid(): bool
{
    return isset($_POST['csrf'], $_SESSION['csrf'])
        && is_string($_POST['csrf'])
        && hash_equals($_SESSION['csrf'], $_POST['csrf']);
}

// ---------- Messages flash (survivent à la redirection) ----------
function set_flash(string $type, string $text): void
{
    $_SESSION['flash'] = [$type, $text];
}

function take_flash(): ?array
{
    $m = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $m;
}

/** Affiche un message ; sans argument, affiche (et consomme) le flash en session. */
function flash(?array $m = null): void
{
    $m ??= take_flash();
    if (!$m) return;
    [$type, $text] = $m;
    $ok    = $type === 'ok';
    $cls   = $ok ? 'alert alert-ok' : 'alert alert-err';
    $icon  = $ok ? '✓' : '✕';
    $title = $ok ? 'Succès' : 'Erreur';
    $role  = $ok ? 'status' : 'alert';
    $auto  = $ok ? ' data-autoclose="5000"' : '';

    echo '<div class="' . $cls . '" role="' . $role . '" data-alert' . $auto . '>'
       . '<span class="alert-icon" aria-hidden="true">' . $icon . '</span>'
       . '<div class="alert-body"><p class="alert-title">' . $title . '</p><p class="alert-text">' . e($text) . '</p></div>'
       . '<button type="button" class="alert-close" data-dismiss aria-label="Fermer le message">×</button>'
       . ($ok ? '<span class="alert-bar" aria-hidden="true"></span>' : '')
       . '</div>';
}

// ---------- Validation ----------
function valid_date(string $d): bool
{
    $x = DateTime::createFromFormat('Y-m-d', $d);
    return $x && $x->format('Y-m-d') === $d;
}

function field_error(array $errors, string $key): string
{
    return isset($errors[$key])
        ? '<p class="mt-1.5 font-mono text-[11px]" id="err-' . e($key) . '">↳ ' . e($errors[$key]) . '</p>'
        : '';
}

function invalid(array $errors, string $key): string
{
    return isset($errors[$key]) ? 'aria-invalid="true" aria-describedby="err-' . e($key) . '"' : '';
}

/** Vérifie la longueur d'un champ texte obligatoire. */
function require_text(array &$errors, string $key, string $value, string $label, int $max = 100): void
{
    if ($value === '') {
        $errors[$key] = "$label est obligatoire.";
    } elseif (mb_strlen($value) > $max) {
        $errors[$key] = "$label : $max caractères maximum.";
    }
}