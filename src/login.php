<?php
require_once 'auth.php';

// Si déjà connecté, on redirige vers l'accueil
if ($is_logged_in) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pass = $_POST['password'] ?? '';
    
    // Mot de passe fixé à "admin" (Vous pourrez le changer ici)
    if ($pass === 'admin') {
        $_SESSION['logged_in'] = true;
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
    <title>Connexion au Kanban</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; background: #f4f5f7; margin:0; }
        .login-card { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); width: 100%; max-width: 400px; text-align: center; }
        .login-card input { width: 100%; padding: 12px; margin: 15px 0; border: 1px solid #dfe1e6; border-radius: 6px; box-sizing: border-box; font-size:15px; }
        .login-card button { width: 100%; padding: 12px; background: #0052cc; color: white; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; font-size:15px; transition: 0.2s; }
        .login-card button:hover { background: #0047b3; }
    </style>
</head>
<body>
    <div class="login-card">
        <div style="font-size:40px; margin-bottom:10px;">🔒</div>
        <h2 style="margin-top:0; color:#091e42;">Déverrouillage</h2>
        <p style="color: #5e6c84; font-size: 14px; margin-bottom:20px;">Saisissez le mot de passe (par défaut : <b>admin</b>) pour passer en mode édition.</p>
        
        <?php if($error): ?>
            <div style="color: #de350b; background: #ffebee; padding: 8px; border-radius: 4px; margin-bottom: 10px; font-size:14px; font-weight:bold;"><?= $error ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <input type="password" name="password" placeholder="Mot de passe..." required autofocus>
            <button type="submit">Déverrouiller l'accès</button>
        </form>
        <div style="margin-top: 20px;">
            <a href="index.php" style="color: #0052cc; text-decoration: none; font-size: 14px; font-weight:600;">← Retour au mode invité (Lecture seule)</a>
        </div>
    </div>
</body>
</html>
