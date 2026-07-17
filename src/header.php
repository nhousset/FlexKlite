<?php
// On s'assure que le fichier existe physiquement sur le disque avant de tenter de l'afficher
$logo_path = '';
if (!empty($settings['app_logo']) && file_exists(__DIR__ . '/' . $settings['app_logo'])) {
    $logo_path = htmlspecialchars($settings['app_logo']);
}
?>
<div class="main-header">
    <div class="header-title-wrapper">
        <div class="app-logo-container" style="background: transparent; padding: 0; display: flex; align-items: center; justify-content: center; width: auto;">
            <?php if ($logo_path): ?>
                <img src="<?= $logo_path ?>?t=<?= time() ?>" alt="Logo" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';" style="max-height: 40px; max-width: 150px; object-fit: contain; border-radius: 6px;">
                <svg style="display:none; width: 24px; height: 24px; color: var(--primary);" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="9" y1="3" x2="9" y2="21"></line>
                    <line x1="15" y1="3" x2="15" y2="21"></line>
                </svg>
            <?php else: ?>
                <svg style="width: 24px; height: 24px; color: var(--primary);" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="9" y1="3" x2="9" y2="21"></line>
                    <line x1="15" y1="3" x2="15" y2="21"></line>
                </svg>
            <?php endif; ?>
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
                <button class="btn-header dropdown-btn" onclick="toggleHeaderMenu(event)">
                    ⚙️ Menu <span style="font-size: 10px;">▼</span>
                </button>
                <div class="dropdown-menu" id="header-dropdown">
                    <a href="admin.php" class="dropdown-item">⚙️ Paramètres globaux</a>
                    <div class="dropdown-divider"></div>
                    <a href="logout.php" class="dropdown-item text-danger">🚪 Se déconnecter</a>
                </div>
            </div>
        <?php else: ?>
            <div style="display: flex; align-items: center; gap: 10px;">
                <span style="background: #fff3e0; color: #e65100; padding: 6px 12px; border-radius: 20px; font-size: 13px; font-weight: bold; border: 1px solid #ffcc80;">
                    👁️ Mode Invité (Lecture seule)
                </span>
                <a href="login.php" class="btn-header" style="background: #0052cc; color: white; text-decoration: none;">Se connecter</a>
            </div>
        <?php endif; ?>
    </div>
</div>
