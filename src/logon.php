<?php
// logon.php
session_start();

$db_dir = __DIR__ . '/db';
$db_admin_file = $db_dir . '/admin.json';

// Détecte si c'est la première exécution (fichier absent)
$is_first_run = !file_exists($db_admin_file);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';

    if ($is_first_run) {
        // --- ÉTAPE 1 : CRÉATION DU MOT DE PASSE ---
        if (!empty($password)) {
            if (!is_dir($db_dir)) {
                mkdir($db_dir, 0755, true);
            }
            // Création d'un hash sécurisé (Bcrypt)
            $hash = password_hash($password, PASSWORD_DEFAULT);
            file_put_contents($db_admin_file, json_encode(['hash' => $hash], JSON_PRETTY_PRINT));
            
            $_SESSION['admin_logged'] = true;
            header('Location: index.php');
            exit;
        } else {
            $error = "Le mot de passe ne peut pas être vide.";
        }
    } else {
        // --- ÉTAPE 2 : VÉRIFICATION DU MOT DE PASSE ---
        $admin_data = json_decode(file_get_contents($db_admin_file), true);
        
        // Comparaison du mot de passe saisi avec le hash stocké
        if (password_verify($password, $admin_data['hash'])) {
            $_SESSION['admin_logged'] = true;
            header('Location: index.php');
            exit;
        } else {
            $error = "Mot de passe incorrect.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= $is_first_run ? 'Initialisation' : 'Connexion' ?> - Kanban</title>
    <link rel="stylesheet" href="style.css?<?= time() ?>">
    <style>
        body { display: flex; justify-content: center; align-items: center; height: 100vh; background-color: var(--bg-color); }
        .login-box { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); width: 100%; max-width: 350px; text-align: center; }
        .login-box h2 { margin-top: 0; color: var(--text-color); font-size: 18px; margin-bottom: 20px;}
        .login-box input[type="password"] { width: 100%; padding: 10px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .login-box button { width: 100%; padding: 10px; font-size: 14px; }
        .error { color: #d32f2f; font-size: 13px; margin-bottom: 15px; background: #ffebee; padding: 8px; border-radius: 4px;}
        .info-badge { display: inline-block; background: #e3f2fd; color: #0d47a1; font-size: 12px; padding: 4px 8px; border-radius: 4px; margin-bottom: 15px; font-weight: bold;}
    </style>
</head>
<body>

    <div class="login-box">
        <?php if ($is_first_run): ?>
            <h2>Bienvenue sur ton Kanban</h2>
            <div class="info-badge">Configuration initiale</div>
            <p style="font-size: 13px; color: #666; margin-bottom: 20px;">Crée le mot de passe administrateur pour sécuriser l'accès.</p>
        <?php else: ?>
            <h2>Accès Sécurisé</h2>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="password" name="password" placeholder="Mot de passe" required autofocus>
            <button type="submit" class="btn">
                <?= $is_first_run ? 'Créer et se connecter' : 'Se connecter' ?>
            </button>
        </form>
    </div>

</body>
</html>
