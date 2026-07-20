<?php
require_once 'auth.php';

// Si l'utilisateur a déjà accès, on le renvoie au tableau
if ($has_read_access) {
    header('Location: index.php');
    exit;
}

$db_dir = __DIR__ . '/db';
$admin_file = $db_dir . '/admin.json';
$settings_file = $db_dir . '/settings.json';

// Vérifie si la fonctionnalité est bien activée, sinon on le laisse passer
$settings = file_exists($settings_file) ? json_decode(file_get_contents($settings_file), true) : [];
if (empty($settings['require_read_password'])) {
    header('Location: index.php');
    exit;
}

// Récupération du logo (Uniquement si le fichier est physiquement accessible)
$app_logo = 'img/logo.png';
if (!empty($settings['app_logo']) && file_exists(__DIR__ . '/' . $settings['app_logo'])) {
    $app_logo = htmlspecialchars($settings['app_logo']);
}

// Récupération du hash du mot de passe de lecture
$readonly_hash = '';
if (file_exists($admin_file)) {
    $data = json_decode(file_get_contents($admin_file), true);
    $readonly_hash = $data['readonly_password'] ?? '';
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pass = $_POST['password'] ?? '';
    
    if (password_verify($pass, $readonly_hash)) {
        $_SESSION['readonly_logged_in'] = true;
        header('Location: index.php');
        exit;
    } else {
        $error = 'Mot de passe incorrect.';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Accès Protégé</title>
    <link rel="stylesheet" href="style.css?<?= time() ?>">
    <style>
        body { font-family: var(--font-main); display: flex; justify-content: center; align-items: center; height: 100vh; background: var(--bg-color); margin:0; }
        .login-card { background: var(--card-bg); padding: 40px; border-radius: var(--card-radius); box-shadow: var(--shadow-main); width: 100%; max-width: 400px; text-align: center; border: 1px solid var(--border-color); color: var(--text-main); }
        .login-card input { width: 100%; padding: 12px; margin: 10px 0 20px 0; border: 1px solid var(--border-color); border-radius: var(--border-radius); box-sizing: border-box; font-size:15px; background: var(--column-bg); color: var(--text-main); }
        .login-card input:focus { outline: none; border-color: var(--primary); background: var(--card-bg); }
        .login-card button { width: 100%; padding: 12px; background: var(--primary); color: white; border: none; border-radius: var(--border-radius); font-weight: bold; cursor: pointer; font-size:15px; transition: 0.2s; }
        .login-card button:hover { opacity: 0.9; }
        .alert-error { color: #de350b; background: #ffebee; padding: 10px; border-radius: 4px; margin-bottom: 20px; font-size:14px; font-weight:bold; }
    </style>
</head>
<body data-theme="<?= htmlspecialchars($settings['app_theme'] ?? 'classic') ?>">
    <div class="login-card">
        
        <?php if (!empty($app_logo)): ?>
            <div style="margin-bottom:20px; text-align: center;">
                <img src="<?= $app_logo ?>?t=<?= time() ?>" alt="Logo de l'application" style="max-height: 70px; max-width: 100%; object-fit: contain; border-radius: 6px;">
            </div>
        <?php else: ?>
            <div style="font-size:40px; margin-bottom:10px;">👁️</div>
        <?php endif; ?>

        <p style="color: var(--text-muted); font-size: 14px; margin-bottom:25px;">Saisissez le mot de passe pour consulter l'avancement des chantiers.</p>
        
        <?php if($error): ?><div class="alert-error"><?= $error ?></div><?php endif; ?>
        
        <form method="POST">
            <input type="password" name="password" placeholder="Mot de passe visiteur..." required autofocus>
            <button type="submit">Accéder au Kanban</button>
        </form>
        
        <div style="margin-top: 25px; border-top: 1px solid var(--border-color); padding-top: 20px;">
            <a href="login.php" style="color: var(--text-muted); text-decoration: none; font-size: 13px; font-weight: 600;">Accès Édition Administrateur</a>
        </div>
    </div>
</body>
</html>
