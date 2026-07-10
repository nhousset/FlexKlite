<?php 
require_once 'auth.php'; 

$settings_file = __DIR__ . '/db/settings.json';
$default = [
    "app_title" => "Gestion des Chantiers", 
    "team_name" => "IHMT",
    "projets" => [], "acteurs" => [], "priorites" => [], "reunions" => []
];
$settings = file_exists($settings_file) ? array_merge($default, json_decode(file_get_contents($settings_file), true)) : $default; 

$app_title = htmlspecialchars($settings['app_title']);
$team_name = htmlspecialchars($settings['team_name']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= $app_title ?> - <?= $team_name ?></title>
    <link rel="stylesheet" href="style.css?<?= time() ?>">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/exceljs/4.3.0/exceljs.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
    
    <!-- Injection du statut de connexion pour le JavaScript -->
    <script>window.IS_LOGGED_IN = <?= $is_logged_in ? 'true' : 'false' ?>;</script>
    
    <style>
        .lot-card { background: #fff; border: 1px solid #dfe1e6; border-radius: 8px; padding: 12px 16px; margin-bottom: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
        .lot-header { display: flex; justify-content: space-between; align-items: center; }
        .lot-title { font-weight: 700; color: #091e42; font-size: 14px; }
        .lot-code { background: #e3f2fd; color: #0052cc; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; border: 1px solid #bbdefb; }
        .note-target-badge { background: #e8f5e9; color: #006644; padding: 2px 6px; border-radius: 4px; font-size: 10px; font-weight: bold; border: 1px solid #b7eb8f; margin-right: 6px; }
        .btn-edit-note { background:none; border:none; cursor:pointer; font-size:14px; transition: transform 0.1s; opacity: 0.6; }
        .btn-edit-note:hover { transform: scale(1.2); opacity: 1; }
        .attachment-item { display:flex; justify-content:space-between; align-items:center; background:#fafbfc; padding:8px 12px; border:1px solid #dfe1e6; border-radius:6px; margin-bottom:8px; }
        .attachment-item a { color:var(--primary); text-decoration:none; font-weight:600; font-size:13px; }
        .attachment-item a:hover { text-decoration:underline; }
    </style>
</head>
<body>

    <div class="main-header">
        <div class="header-title-wrapper">
            <div class="app-logo-container">
                <img src="img/kanban.png" alt="Logo" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                <svg style="display:none; width: 24px; height: 24px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="9" y1="3" x2="9" y2="21"></line>
                    <line x1="15" y1="3" x2="15" y2="21"></line>
                </svg>
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
                <input type="text" id="filter-search" placeholder="Recherche rapide..." onkeyup="applyFilters()">
            </div>
            
            <!-- CONDITION D'AFFICHAGE DU MODE EDITION vs INVITE -->
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

    <div class="app-layout">
        <div class="main-content">
            <div class="tabs-header" style="display: flex; justify-content: space-between; align-items: flex-end;">
                <div style="display: flex; gap: 15px;">
                    <button class="tab-btn active" onclick="switchTab('tab-kanban', this)">🗂️ Vue Kanban</button>
                    <button class="tab-btn" onclick="switchTab('tab-list', this)">📋 Vue Liste (Excel)</button>
                    <button class="tab-btn" onclick="switchTab('tab-kpi', this)">📊 Tableau de Bord</button>
                </div>
                <button class="tab-btn" id="btn-toggle-activity" onclick="toggleActivityPanel()" style="color: #5e6c84; font-size: 13px;">👁️ Masquer l'activité</button>
            </div>

            <?php 
            include 'kanban.php';
            include 'liste.php';
            include 'kpi.php';
            ?>
        </div>

        <aside class="activity-sidebar" id="activity-sidebar-panel">
            <h3>⚡ Activité Récente</h3>
            <div id="recent-activity-list"></div>
        </aside>
    </div>

    <?php include 'modals.php'; ?>

    <script src="js/utils.js?<?= time() ?>"></script>
    <script src="js/tasks.js?<?= time() ?>"></script>
    <script src="js/board.js?<?= time() ?>"></script>

</body>
</html>
