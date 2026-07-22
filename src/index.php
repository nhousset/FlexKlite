<?php 
require_once 'auth.php'; 

$settings_file = __DIR__ . '/db/settings.json';
$default = [
    "app_title" => "Gestion des Chantiers", 
    "team_name" => "IHMT",
    "projets" => [], "acteurs" => [], "priorites" => [], "reunions" => [],
    "require_read_password" => false
];
$settings = file_exists($settings_file) ? array_merge($default, json_decode(file_get_contents($settings_file), true)) : $default; 

// Vérification de sécurité : si la lecture seule est protégée et que l'utilisateur n'a pas l'accès
if (!empty($settings['require_read_password']) && !$has_read_access) {
    header('Location: read_login.php');
    exit;
}

$app_title = htmlspecialchars($settings['app_title']);
$team_name = htmlspecialchars($settings['team_name']);

// Chargement des informations "A propos" et de la date de compilation
$about_file = __DIR__ . '/about.json';
$about_data = file_exists($about_file) ? json_decode(file_get_contents($about_file), true) : [
    "title" => "FlexKlite",
    "description" => "Application de gestion de tâches et de chantiers.",
    "company" => "Mon Entreprise",
    "contact" => "support@example.com"
];
$compilation_date = date("d/m/Y H:i", filemtime(__FILE__));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= $app_title ?> - <?= $team_name ?></title>
    <link rel="stylesheet" href="style.css?<?= time() ?>">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/exceljs/4.3.0/exceljs.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
    <!-- Frappe Gantt -->
    <?php if(!isset($settings['enable_gantt']) || $settings['enable_gantt']): ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/frappe-gantt/0.6.1/frappe-gantt.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/frappe-gantt/0.6.1/frappe-gantt.min.js"></script>
    <?php endif; ?>
    
    <!-- Injection du statut de connexion et des couleurs de projets pour le JavaScript -->
    <script>
        window.IS_LOGGED_IN = <?= $is_logged_in ? 'true' : 'false' ?>;
        window.ENABLE_CODE_PROJET = <?= !isset($settings['enable_code_projet']) || $settings['enable_code_projet'] ? 'true' : 'false' ?>;
        window.ENABLE_CODE_ITBM = <?= !isset($settings['enable_code_itbm']) || $settings['enable_code_itbm'] ? 'true' : 'false' ?>;
        window.ENABLE_CHARGE_JH = <?= !isset($settings['enable_charge_jh']) || $settings['enable_charge_jh'] ? 'true' : 'false' ?>;
        window.ENABLE_GANTT = <?= !isset($settings['enable_gantt']) || $settings['enable_gantt'] ? 'true' : 'false' ?>;
        window.PROJECT_COLORS = {};
        <?php foreach($settings['projets'] as $p): 
            if(is_array($p)): ?>
                window.PROJECT_COLORS["<?= addslashes($p['name']) ?>"] = "<?= htmlspecialchars($p['color']) ?>";
        <?php endif; endforeach; ?>
    </script>
    
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
<body data-theme="<?= $settings['app_theme'] ?? 'classic' ?>">

    <!-- OVERLAY DE CHARGEMENT -->
    <div id="loading-overlay">
        <div class="spinner"></div>
        <h2 style="color: var(--text-main); font-size: 18px; margin: 0;">Chargement en cours...</h2>
        <p style="color: var(--text-muted); font-size: 13px;">Initialisation de votre espace de travail</p>
    </div>

    <!-- Inclusion de l'en-tête -->
    <?php include 'header.php'; ?>

    <div class="app-layout">
        <div class="main-content">
            <div class="tabs-header" style="display: flex; justify-content: space-between; align-items: flex-end;">
                <div style="display: flex; gap: 15px;">
                    <button class="tab-btn active" onclick="switchTab('tab-kanban', this)">🗂️ Kanban</button>
                    <button class="tab-btn" onclick="switchTab('tab-list', this)">📋 Liste</button>
                    <?php if(!isset($settings['enable_gantt']) || $settings['enable_gantt']): ?>
                    <button class="tab-btn" onclick="switchTab('tab-gantt', this)">📅 Gantt</button>
                    <?php endif; ?>
                    <button class="tab-btn" onclick="switchTab('tab-kpi', this)">📊 Tableau de Bord</button>
                    <button class="tab-btn" onclick="switchTab('tab-archives', this)">🗄️ Archives</button>
                </div>
                <button class="tab-btn" id="btn-toggle-activity" onclick="toggleActivityPanel()" style="color: #5e6c84; font-size: 13px;">👁️ Masquer l'activité</button>
            </div>

            <!-- Barre de filtres globaux -->
            <div class="global-filters" style="background: white; padding: 10px 15px; border-radius: 6px; border: 1px solid #dfe1e6; margin-bottom: 15px; display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
                <div style="display: flex; align-items: center; gap: 5px;">
                    <span style="font-size: 13px; font-weight: 600; color: #5e6c84;">Projet:</span>
                    <select id="filter-projet" class="table-filter" onchange="handleFiltersChange()">
                        <option value="">Tous</option>
                        <?php foreach($settings['projets'] as $p): $pName = is_array($p) ? $p['name'] : $p; ?>
                            <option value="<?= htmlspecialchars($pName) ?>"><?= htmlspecialchars($pName) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="display: flex; align-items: center; gap: 5px;">
                    <span style="font-size: 13px; font-weight: 600; color: #5e6c84;">Statut:</span>
                    <select id="filter-statut" class="table-filter" onchange="handleFiltersChange()">
                        <option value="">Tous</option>
                        <option value="todo">À Faire</option>
                        <option value="in_progress">En Cours</option>
                        <option value="blocked">Bloqué</option>
                        <option value="done">Terminé</option>
                    </select>
                </div>
                <div style="display: flex; align-items: center; gap: 5px;">
                    <span style="font-size: 13px; font-weight: 600; color: #5e6c84;">Priorité:</span>
                    <select id="filter-prio" class="table-filter" onchange="handleFiltersChange()">
                        <option value="">Toutes</option>
                        <?php foreach($settings['priorites'] as $p): ?>
                            <option value="<?= htmlspecialchars($p) ?>"><?= htmlspecialchars($p) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="display: flex; align-items: center; gap: 5px;">
                    <span style="font-size: 13px; font-weight: 600; color: #5e6c84;">Acteur:</span>
                    <select id="filter-acteur" class="table-filter" onchange="handleFiltersChange()">
                        <option value="">Tous</option>
                        <?php foreach($settings['acteurs'] as $a): ?>
                            <option value="<?= htmlspecialchars($a) ?>"><?= htmlspecialchars($a) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button onclick="resetFilters()" style="background: #f4f5f7; border: 1px solid #dfe1e6; border-radius: 4px; padding: 4px 10px; font-size: 13px; font-weight: 600; color: #5e6c84; cursor: pointer; display: flex; align-items: center; gap: 4px;" title="Réinitialiser tous les filtres">
                    🧹 Réinitialiser
                </button>
                <div style="margin-left: auto; display: flex; align-items: center; gap: 5px; background: #e3f2fd; padding: 5px 10px; border-radius: 4px; border: 1px solid #bbdefb;">
                    <input type="checkbox" id="compact-mode" onchange="handleFiltersChange()" style="cursor: pointer;">
                    <label for="compact-mode" style="font-size: 13px; font-weight: bold; color: #0052cc; cursor: pointer; margin:0;">Mode Compact</label>
                </div>
            </div>

            <!-- Inclusion des différentes vues -->
            <?php 
            include 'kanban.php';
            include 'liste.php';
            if(!isset($settings['enable_gantt']) || $settings['enable_gantt']) include 'gantt.php';
            include 'kpi.php';
            include 'archives.php';
            ?>
        </div>

        <!-- Panneau latéral d'activité -->
        <aside class="activity-sidebar" id="activity-sidebar-panel">
            <h3>⚡ Activité Récente</h3>
            <div id="recent-activity-list"></div>
        </aside>
    </div>

    <!-- Inclusion de toutes les fenêtres modales -->
    <?php include 'modals.php'; ?>
    <?php include 'help_modal.php'; ?>

    <!-- Inclusion de la bibliothèque Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Inclusion de la logique JavaScript -->
    <script src="app.js?v=<?= time() ?>"></script>

</body>
</html>
