<?php
require_once 'auth.php';

if ($is_logged_in) {
    header('Location: index.php');
    exit;
}

$db_dir = __DIR__ . '/db';
if (!is_dir($db_dir)) {
    mkdir($db_dir, 0755, true);
}
$admin_file = $db_dir . '/admin.json';

$has_password = false;
$admin_hash = '';

if (file_exists($admin_file)) {
    $data = json_decode(file_get_contents($admin_file), true);
    if (!empty($data['password'])) {
        $has_password = true;
        $admin_hash = $data['password'];
    }
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (!$has_password) {
        $pass1 = $_POST['new_password'] ?? '';
        $pass2 = $_POST['confirm_password'] ?? '';
        
        if (empty($pass1)) {
            $error = "Le mot de passe ne peut pas être vide.";
        } elseif ($pass1 !== $pass2) {
            $error = "Les mots de passe ne correspondent pas.";
        } else {
            $hash = password_hash($pass1, PASSWORD_DEFAULT);
            file_put_contents($admin_file, json_encode(['password' => $hash], JSON_PRETTY_PRINT));
            
            $_SESSION['logged_in'] = true;
            header('Location: index.php');
            exit;
        }
    } else {
        $pass = $_POST['password'] ?? '';
        
        if (password_verify($pass, $admin_hash)) {
            $_SESSION['logged_in'] = true;
            header('Location: index.php');
            exit;
        } else {
            $error = 'Mot de passe incorrect.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Configuration & Connexion</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; background: #f4f5f7; margin:0; }
        .login-card { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); width: 100%; max-width: 400px; text-align: center; border: 1px solid #ebecf0; }
        .login-card input { width: 100%; padding: 12px; margin: 10px 0 20px 0; border: 1px solid #dfe1e6; border-radius: 6px; box-sizing: border-box; font-size:15px; background: #fafbfc; }
        .login-card input:focus { outline: none; border-color: #0052cc; background: white; }
        .login-card button { width: 100%; padding: 12px; background: #00875a; color: white; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; font-size:15px; transition: 0.2s; }
        .login-card button:hover { background: #006644; }
        .login-card label { display: block; text-align: left; font-size: 13px; font-weight: 600; color: #172b4d; margin-bottom: -5px; }
        .alert-error { color: #de350b; background: #ffebee; padding: 10px; border-radius: 4px; margin-bottom: 20px; font-size:14px; font-weight:bold; }
    </style>
</head>
<body>
    <div class="login-card">
        
        <?php if (!$has_password): ?>
            <div style="font-size:40px; margin-bottom:10px;">⚙️</div>
            <h2 style="margin-top:0; color:#091e42;">Première configuration</h2>
            <p style="color: #5e6c84; font-size: 14px; margin-bottom:25px;">Créez un mot de passe administrateur pour protéger le mode édition de votre Kanban.</p>
            
            <?php if($error): ?><div class="alert-error"><?= $error ?></div><?php endif; ?>
            
            <form method="POST">
                <label>Nouveau mot de passe</label>
                <input type="password" name="new_password" required autofocus>
                
                <label>Confirmer le mot de passe</label>
                <input type="password" name="confirm_password" required>
                
                <button type="submit">Enregistrer et déverrouiller</button>
            </form>
            
        <?php else: ?>
            <div style="font-size:40px; margin-bottom:10px;">🔒</div>
            <h2 style="margin-top:0; color:#091e42;">Déverrouillage</h2>
            <p style="color: #5e6c84; font-size: 14px; margin-bottom:25px;">Saisissez votre mot de passe pour passer en mode édition.</p>
            
            <?php if($error): ?><div class="alert-error"><?= $error ?></div><?php endif; ?>
            
            <form method="POST">
                <input type="password" name="password" placeholder="Mot de passe admin..." required autofocus>
                <button type="submit">Déverrouiller l'accès</button>
            </form>
        <?php endif; ?>
        
        <div style="margin-top: 25px;">
            <a href="index.php" style="color: #0052cc; text-decoration: none; font-size: 14px; font-weight:600;">← Retour à la consultation</a>
        </div>
    </div>
</body>
</html>
