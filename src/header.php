<?php
// On s'assure que le fichier existe physiquement sur le disque avant de tenter de l'afficher
$logo_path = 'img/logo.png';
if (!empty($settings['app_logo']) && file_exists(__DIR__ . '/' . $settings['app_logo'])) {
    $logo_path = htmlspecialchars($settings['app_logo']);
}
?>
<div class="main-header">
    <div class="header-title-wrapper">
        <div class="app-logo-container" style="background: transparent; padding: 0; display: flex; align-items: center; justify-content: center; width: auto;">
            <img src="<?= $logo_path ?>?t=<?= time() ?>" alt="Logo" style="max-height: 40px; max-width: 150px; object-fit: contain; border-radius: 6px;">
        </div>
        
        <h1>
            <?= $app_title ?>
            <?php if(!empty($team_name)): ?>
                <span style="font-size: 13px; background: rgba(255,255,255,0.2); color: white; padding: 4px 12px; border-radius: 20px; font-weight: 800; letter-spacing: 0.5px; border: 1px solid rgba(255,255,255,0.4);">
                    <?= $team_name ?>
                </span>
            <?php endif; ?>
        </h1>
    </div>

    <div class="header-actions">
        <div class="search-box">
            <span>🔍</span>
            <input type="text" id="filter-search" placeholder="Recherche rapide..." onkeyup="handleFiltersChange()">
        </div>
        
        <?php if($is_logged_in): ?>
            <button onclick="openAddTaskModal()" class="btn-header btn-new-task">➕ Nouvelle Tâche</button>
            <div class="dropdown">
                <button class="btn-header dropdown-btn" onclick="toggleHeaderMenu(event, 'header-dropdown')">
                    ⚙️ Menu <span style="font-size: 10px;">▼</span>
                </button>
                <div class="dropdown-menu" id="header-dropdown">
                    <a href="admin.php" class="dropdown-item">⚙️ Administration</a>
                    <a href="help.php" class="dropdown-item" target="_blank">❓ Aide</a>
                    <a href="#" class="dropdown-item" onclick="openAboutModal(event)">ℹ️ À propos</a>
                    <div class="dropdown-divider"></div>
                    <a href="logout.php" class="dropdown-item text-danger">🚪 Se déconnecter</a>
                </div>
            </div>
        <?php else: ?>
            <div class="dropdown">
                <button class="btn-header dropdown-btn" onclick="toggleHeaderMenu(event, 'guest-dropdown')" style="background: #0052cc; color: white;">
                    Menu <span style="font-size: 10px;">▼</span>
                </button>
                <div class="dropdown-menu" id="guest-dropdown">
                    <a href="login.php" class="dropdown-item">🔑 Se connecter</a>
                    <div class="dropdown-divider"></div>
                    <a href="help.php" class="dropdown-item" target="_blank">❓ Aide</a>
                    <a href="#" class="dropdown-item" onclick="openAboutModal(event)">ℹ️ À propos</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
