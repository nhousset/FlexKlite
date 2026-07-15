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
$app_logo = '';
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
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; background: #f4f5f7; margin:0; }
        .login-card { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); width: 100%; max-width: 400px; text-align: center; border: 1px solid #ebecf0; }
        .login-card input { width: 100%; padding: 12px; margin: 10px 0 20px 0; border: 1px solid #dfe1e6; border-radius: 6px; box-sizing: border-box; font-size:15px; background: #fafbfc; }
        .login-card input:focus { outline: none; border-color: #0052cc; background: white; }
        .login-card button { width: 100%; padding: 12px; background: #0052cc; color: white; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; font-size:15px; transition: 0.2s; }
        .login-card button:hover { background: #0047b3; }
        .alert-error { color: #de350b; background: #ffebee; padding: 10px; border-radius: 4px; margin-bottom: 20px; font-size:14px; font-weight:bold; }
    </style>
</head>
<body>
    <div class="login-card">
        
        <?php if (!empty($app_logo)): ?>
            <div style="margin-bottom:20px; text-align: center;">
                <img src="<?= $app_logo ?>?t=<?= time() ?>" alt="Logo de l'application" style="max-height: 70px; max-width: 100%; object-fit: contain; border-radius: 6px;">
            </div>
        <?php else: ?>
            <div style="font-size:40px; margin-bottom:10px;">👁️</div>
        <?php endif; ?>

        <h2 style="margin-top:0; color:#091e42;">Espace Consultatif</h2>
        <p style="color: #5e6c84; font-size: 14px; margin-bottom:25px;">Saisissez le mot de passe pour consulter l'avancement des chantiers.</p>
        
        <?php if($error): ?><div class="alert-error"><?= $error ?></div><?php endif; ?>
        
        <form method="POST">
            <input type="password" name="password" placeholder="Mot de passe visiteur..." required autofocus>
            <button type="submit">Accéder au Kanban</button>
        </form>
        
        <div style="margin-top: 25px; border-top: 1px solid #ebecf0; padding-top: 20px;">
            <a href="login.php" style="color: #5e6c84; text-decoration: none; font-size: 13px; font-weight: 600;">Accès Édition Administrateur</a>
        </div>
    </div>
</body>
</html>
