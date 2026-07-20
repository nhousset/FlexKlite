<?php 
require_once 'auth.php'; 
if (!$is_logged_in) {
    header('Location: login.php');
    exit;
}

$settings_file = __DIR__ . '/db/settings.json';
$settings = file_exists($settings_file) ? json_decode(file_get_contents($settings_file), true) : [];
$app_theme = $settings['app_theme'] ?? 'classic';
$app_logo = (!empty($settings['app_logo']) && file_exists(__DIR__ . '/' . $settings['app_logo'])) ? htmlspecialchars($settings['app_logo']) : 'img/logo.png';

$about_file = __DIR__ . '/db/about.json';
$about_data = file_exists($about_file) ? json_decode(file_get_contents($about_file), true) : [];
$compilation_date = $about_data['build_date'] ?? '20/07/2026 08:20';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Administration Pro - Suivi de Chantiers</title>
    <link rel="stylesheet" href="style.css?<?= time() ?>">
</head>
<body data-theme="<?= htmlspecialchars($app_theme) ?>">

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <h1 style="margin: 0;">Console d'Administration</h1>
        <div>
            <button onclick="returnToBoard()" class="btn" style="background: #ebecf0; color: #42526e; text-decoration: none; margin-right: 10px; border:none; cursor:pointer; font-size:14px; font-weight:bold; font-family:inherit;">📊 Retour au Tableau</button>
        </div>
    </div>

    <?php if(isset($_GET['status'])): ?>
        <?php if($_GET['status'] === 'import_ok'): ?>
            <div class="alert-banner alert-success">✅ Restauration réussie ! Les fichiers JSON ont été importés et validés avec succès.</div>
        <?php elseif($_GET['status'] === 'import_error'): ?>
            <div class="alert-banner alert-danger">❌ Erreur lors de la restauration. Assurez-vous que l'archive ZIP contient des fichiers JSON valides.</div>
        <?php elseif($_GET['status'] === 'backup_ok'): ?>
            <div class="alert-banner alert-success">📦 Sauvegarde générée avec succès ! Elle a été ajoutée à la liste ci-dessous.</div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="admin-tabs-header" style="align-items: center;">
        <button class="admin-tab-btn active" onclick="switchAdminTab('panel-lists', this)">⚙️ Configuration des Éléments</button>
        <button class="admin-tab-btn" onclick="switchAdminTab('panel-json', this)">📝 Éditeur Brut JSON</button>
        <button class="admin-tab-btn" onclick="switchAdminTab('panel-backup', this)">💾 Sauvegarde & Restauration (ZIP)</button>
        <button class="admin-tab-btn" onclick="switchAdminTab('panel-history', this)">📜 Journal des Actions</button>
        
        <div style="margin-left: auto; padding-right: 15px;">
            <img src="<?= $app_logo ?>?t=<?= time() ?>" alt="Logo FlexKlite" style="height: 60px; object-fit: contain; vertical-align: middle;">
        </div>
    </div>

    <!-- Modale de notification -->
    <div id="admin-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(9,30,66,0.54); z-index:9999; align-items:center; justify-content:center;">
        <div style="background:white; padding:30px; border-radius:8px; max-width:400px; width:90%; text-align:center; box-shadow:0 8px 16px rgba(0,0,0,0.2);">
            <div style="font-size:40px; margin-bottom:15px;" id="admin-modal-icon">✅</div>
            <h3 style="margin-top:0; color:#091e42; font-size:20px;" id="admin-modal-title">Succès</h3>
            <p id="admin-modal-msg" style="color:#5e6c84; font-size:14px; margin-bottom:25px; line-height: 1.5;"></p>
            <button onclick="document.getElementById('admin-modal').style.display='none'" class="btn" style="background:#0052cc; color:white; padding:10px 20px;">OK</button>
        </div>
    </div>

    <!-- Modale de confirmation -->
    <div id="confirm-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(9,30,66,0.54); z-index:9999; align-items:center; justify-content:center;">
        <div style="background:white; padding:30px; border-radius:8px; max-width:400px; width:90%; text-align:center; box-shadow:0 8px 16px rgba(0,0,0,0.2);">
            <div style="font-size:40px; margin-bottom:15px;">⚠️</div>
            <h3 style="margin-top:0; color:#091e42; font-size:20px;">Confirmation</h3>
            <p id="confirm-modal-msg" style="color:#5e6c84; font-size:14px; margin-bottom:25px; line-height: 1.5;"></p>
            <div style="display:flex; justify-content:center; gap: 15px;">
                <button onclick="closeConfirmModal()" class="btn" style="background:#ebecf0; color:#42526e; padding:10px 20px;">Annuler</button>
                <button id="confirm-modal-btn" class="btn" style="background:#de350b; color:white; padding:10px 20px;">Confirmer</button>
            </div>
        </div>
    </div>

    <div id="panel-lists" class="admin-tab-content active">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px; flex-wrap: wrap; gap: 15px;">
            <div class="admin-sub-tabs-header" style="margin-bottom: 0; border-bottom: none; padding-bottom: 0;">
                <button class="admin-sub-tab-btn active" onclick="switchAdminSubTab('sub-general', this)">Général</button>
                <button class="admin-sub-tab-btn" onclick="switchAdminSubTab('sub-security', this)">Sécurité</button>
                <button class="admin-sub-tab-btn" onclick="switchAdminSubTab('sub-fields', this)">Champs</button>
                <button class="admin-sub-tab-btn" onclick="switchAdminSubTab('sub-projects', this)">Projets</button>
                <button class="admin-sub-tab-btn" onclick="switchAdminSubTab('sub-actors', this)">Acteurs</button>
                <button class="admin-sub-tab-btn" onclick="switchAdminSubTab('sub-priorities', this)">Priorités</button>
                <button class="admin-sub-tab-btn" onclick="switchAdminSubTab('sub-meetings', this)">Réunions</button>
            </div>
            <button onclick="saveSettings()" class="btn" style="background: #00875a; white-space: nowrap;">Enregistrer la configuration</button>
        </div>
        
        <div class="admin-grid">

            <div id="sub-security" class="admin-sub-content">
                <div class="admin-card" style="border-left: 4px solid #ff8b00;">
                    <h3>Sécurité & Accès au tableau</h3>
                    <div style="display: flex; gap: 20px; flex-wrap: wrap; align-items: flex-end;">
                        <div class="form-group-admin" style="flex: 1; min-width: 250px;">
                            <label>Protéger la lecture seule par mot de passe ?</label>
                            <select id="input-require-read">
                                <option value="0">Non, accès visiteur libre</option>
                                <option value="1">Oui, exiger un mot de passe</option>
                            </select>
                        </div>
                        <div class="form-group-admin" style="flex: 1; min-width: 250px;" id="read-password-container">
                            <label>Nouveau mot de passe visiteur (laisser vide pour ne pas changer)</label>
                            <input type="password" id="input-read-password" placeholder="Saisir un mot de passe...">
                        </div>
                        <div style="flex: 1; min-width: 150px;">
                            <button onclick="saveSecurity()" class="btn" style="background: #ff8b00; width: 100%;">Mettre à jour la sécurité</button>
                        </div>
                    </div>
                </div>
            </div>

            <div id="sub-general" class="admin-sub-content active">
                <div class="admin-card">
                    <h3>Paramètres Généraux de l'Application</h3>
                    <div style="display: flex; gap: 20px; flex-wrap: wrap; align-items: flex-start;">
                        
                        <div style="display: flex; flex-direction: column; gap: 20px; flex: 2; min-width: 250px;">
                            <div class="form-group-admin">
                                <label>Titre de l'application</label>
                                <input type="text" id="input-app-title">
                            </div>
                            <div class="form-group-admin">
                                <label>Nom de l'équipe</label>
                                <input type="text" id="input-team-name">
                            </div>
                            <div class="form-group-admin" style="grid-column: 1 / -1;">
                                <label>Thème global de l'application</label>
                                <input type="hidden" id="input-app-theme" value="classic">
                                <div style="display: flex; gap: 15px; flex-wrap: wrap; margin-top: 10px;">
                                    <div class="theme-card" data-value="classic" onclick="selectTheme(this)" style="cursor: pointer; border: 2px solid transparent; border-radius: 6px; overflow: hidden; width: 140px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                                        <img src="img/theme_classic.png" alt="Classique" style="width: 100%; height: 90px; object-fit: cover; display: block;">
                                        <div style="padding: 6px; text-align: center; font-size: 13px; font-weight: 500; background: #fff; color: #172b4d;">Classique</div>
                                    </div>
                                    <div class="theme-card" data-value="dark" onclick="selectTheme(this)" style="cursor: pointer; border: 2px solid transparent; border-radius: 6px; overflow: hidden; width: 140px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                                        <img src="img/theme_sombre.png" alt="Sombre" style="width: 100%; height: 90px; object-fit: cover; display: block;">
                                        <div style="padding: 6px; text-align: center; font-size: 13px; font-weight: 500; background: #fff; color: #172b4d;">Sombre</div>
                                    </div>
                                    <div class="theme-card" data-value="modern" onclick="selectTheme(this)" style="cursor: pointer; border: 2px solid transparent; border-radius: 6px; overflow: hidden; width: 140px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                                        <img src="img/theme_modern.png" alt="Moderne" style="width: 100%; height: 90px; object-fit: cover; display: block;">
                                        <div style="padding: 6px; text-align: center; font-size: 13px; font-weight: 500; background: #fff; color: #172b4d;">Moderne</div>
                                    </div>
                                    <div class="theme-card" data-value="architect" onclick="selectTheme(this)" style="cursor: pointer; border: 2px solid transparent; border-radius: 6px; overflow: hidden; width: 140px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                                        <img src="img/theme_architec.png" alt="Architect" style="width: 100%; height: 90px; object-fit: cover; display: block;">
                                        <div style="padding: 6px; text-align: center; font-size: 13px; font-weight: 500; background: #fff; color: #172b4d;">Architect</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group-admin" style="flex: 1; min-width: 250px; background: #fafbfc; padding: 15px; border-radius: 8px; border: 1px solid #dfe1e6;">
                            <label style="margin-bottom: 10px;">Logo de l'application</label>
                            <div style="text-align: center; margin-bottom: 15px; min-height: 40px;">
                                <img id="current-logo-preview" src="" alt="Logo" style="max-height: 50px; max-width: 100%; border-radius: 4px; display: none;">
                            </div>
                            <div class="file-upload-wrapper" style="padding: 15px 10px; margin-bottom: 0;">
                                <span id="logo-upload-label" style="font-weight:600; font-size:12px; color:#5e6c84;">🖼️ Modifier le logo (PNG, JPG)</span>
                                <input type="file" id="input-app-logo" accept="image/png, image/jpeg, image/svg+xml, image/webp" onchange="uploadLogo(this)">
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <div id="sub-fields" class="admin-sub-content">
                <div class="admin-card">
                    <h3>Configuration des Champs</h3>
                    <div style="display: flex; gap: 20px; flex-wrap: wrap; align-items: flex-start;">
                        <div class="form-group-admin" style="flex: 1; min-width: 250px;">
                            <label>Afficher le bloc "Code Projet" ?</label>
                            <select id="input-enable-code-projet">
                                <option value="1">Oui</option>
                                <option value="0">Non</option>
                            </select>
                        </div>
                        <div class="form-group-admin" style="flex: 1; min-width: 250px;">
                            <label>Afficher le bloc "Code ITBM" ?</label>
                            <select id="input-enable-code-itbm">
                                <option value="1">Oui</option>
                                <option value="0">Non</option>
                            </select>
                        </div>
                        <div class="form-group-admin" style="flex: 1; min-width: 250px;">
                            <label>Activer le module Charge (JH) ?</label>
                            <select id="input-enable-charge-jh">
                                <option value="1">Oui</option>
                                <option value="0">Non</option>
                            </select>
                        </div>
                        <div class="form-group-admin" style="flex: 1; min-width: 250px;">
                            <label>Activer la vue Gantt ?</label>
                            <select id="input-enable-gantt">
                                <option value="1">Oui</option>
                                <option value="0">Non</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div id="sub-projects" class="admin-sub-content">
                <div class="admin-card">
                    <h3>Projets</h3>
                    <ul class="item-list" id="list-projets"></ul>
                    <div class="add-group" id="project-form-container" style="background: #fafbfc; padding: 15px; border-radius: 8px; border: 1px dashed #dfe1e6;">
                        <label style="font-size: 13px; font-weight: 600; color: #172b4d;">1. Choisir une couleur</label>
                        <div id="palette-container" style="display:flex; flex-wrap:wrap; gap:6px; margin-bottom: 10px;"></div>
                        <input type="hidden" id="selected-project-color" value="#0052cc">
                        <input type="hidden" id="edit-project-index" value="-1">
                        
                        <label style="font-size: 13px; font-weight: 600; color: #172b4d;">2. Nom du projet</label>
                        <input type="text" id="input-projets" placeholder="Nouveau projet..." style="margin-bottom: 10px; width: 100%;">
                        
                        <label style="font-size: 13px; font-weight: 600; color: #172b4d;">3. Description (Optionnelle)</label>
                        <textarea id="input-projet-desc" placeholder="Description courte du projet..." rows="2" style="width:100%; padding:8px; border:1px solid #dfe1e6; border-radius:4px; margin-bottom:10px; font-family: inherit; font-size: 14px;"></textarea>
                        
                        <div style="display:flex; gap:10px;">
                            <button class="btn" id="btn-save-project" onclick="saveProject()">Ajouter le projet</button>
                            <button class="btn" id="btn-cancel-project" style="display:none; background:#ebecf0; color:#42526e;" onclick="cancelEditProject()">Annuler</button>
                        </div>
                    </div>
                </div>
            </div>

            <div id="sub-actors" class="admin-sub-content">
                <div class="admin-card">
                    <h3>Acteurs / Porteurs</h3>
                    <ul class="item-list" id="list-acteurs"></ul>
                    <div class="add-group" style="flex-direction:row;">
                        <input type="text" id="input-acteurs" placeholder="Nouvel acteur...">
                        <button class="btn" onclick="addItem('acteurs')">Ajouter</button>
                    </div>
                </div>
            </div>

            <div id="sub-priorities" class="admin-sub-content">
                <div class="admin-card">
                    <h3>Priorités</h3>
                    <ul class="item-list" id="list-priorites"></ul>
                    <div class="add-group" style="flex-direction:row;">
                        <input type="text" id="input-priorites" placeholder="Nouvelle priorité...">
                        <button class="btn" onclick="addItem('priorites')">Ajouter</button>
                    </div>
                </div>
            </div>

            <div id="sub-meetings" class="admin-sub-content">
                <div class="admin-card">
                    <h3>Types de Réunions</h3>
                    <ul class="item-list" id="list-reunions"></ul>
                    <div class="add-group" style="flex-direction:row;">
                        <input type="text" id="input-reunions" placeholder="Point équipe, Coproj...">
                        <button class="btn" onclick="addItem('reunions')">Ajouter</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Rest of JSON, Backup, and History panels -->
    <div id="panel-json" class="admin-tab-content">
        <div class="json-editor-container">
            <div class="json-card">
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <h3 style="margin:0; font-size:15px; color:#091e42;">Base de données Kanban (kanban.json)</h3>
                    <button class="btn" style="padding: 6px 12px; font-size:13px;" onclick="saveRawJson('kanban')">Sauvegarder les Tâches</button>
                </div>
                <textarea id="textarea-kanban" class="json-textarea" placeholder="Chargement..."></textarea>
            </div>
            <div class="json-card">
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <h3 style="margin:0; font-size:15px; color:#091e42;">Configuration Générale (settings.json)</h3>
                    <button class="btn" style="padding: 6px 12px; font-size:13px;" onclick="saveRawJson('settings')">Sauvegarder l'App</button>
                </div>
                <textarea id="textarea-settings" class="json-textarea" placeholder="Chargement..."></textarea>
            </div>
        </div>
    </div>

    <div id="panel-backup" class="admin-tab-content">
        <div class="backup-zone">
            <div class="backup-card">
                <div style="background: #e3f2fd; color: #0052cc; width:60px; height:60px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:24px;">📦</div>
                <h3 style="margin:0; color:#091e42; font-size:18px;">Exporter la base de données</h3>
                <p style="color:var(--text-muted); font-size:14px; margin:0 0 10px 0; max-width:280px;">Générez une archive ZIP contenant vos tâches, vos notes et vos paramètres, et ajoutez-la à la liste de vos sauvegardes.</p>
                <a href="api.php?action=export_backup_zip" class="btn" style="text-decoration:none; background:#0052cc; padding:12px 24px;">Générer une sauvegarde</a>
            </div>
            
            <div class="backup-card">
                <div style="background: #e8f5e9; color: #00875a; width:60px; height:60px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:24px;">📥</div>
                <h3 style="margin:0; color:#091e42; font-size:18px;">Restaurer une sauvegarde</h3>
                <p style="color:var(--text-muted); font-size:14px; margin:0 0 10px 0; max-width:280px;">Importez une archive de sauvegarde précédemment exportée pour restaurer l'état complet.</p>
                
                <form action="api.php?action=import_backup_zip" method="POST" enctype="multipart/form-data" style="width:100%;" onsubmit="showConfirmRestoreModal(event, this);">
                    <div class="file-upload-wrapper">
                        <span id="file-upload-label" style="font-weight:600; font-size:14px; color:#5e6c84;">📁 Cliquez ou glissez votre archive ZIP ici</span>
                        <input type="file" name="backup_zip" accept=".zip" required onchange="updateUploadLabel(this)">
                    </div>
                    <button type="submit" class="btn" style="background: #00875a; width:100%; padding:12px;">Démarrer la Restauration</button>
                </form>
            </div>
        </div>

        <?php
        $backup_dir_path = __DIR__ . '/../uploads/backup___';
        $backups = [];
        if (is_dir($backup_dir_path)) {
            $files = scandir($backup_dir_path);
            foreach ($files as $f) {
                if (substr($f, -4) === '.zip') {
                    $backups[] = [
                        'name' => $f,
                        'date' => filemtime($backup_dir_path . '/' . $f),
                        'size' => filesize($backup_dir_path . '/' . $f)
                    ];
                }
            }
            usort($backups, function($a, $b) { return $b['date'] - $a['date']; });
        }
        ?>
        
        <?php if (!empty($backups)): ?>
        <div class="json-card" style="margin-top: 25px;">
            <h3 style="margin:0; font-size:16px; color:#091e42; border-bottom: 1px solid #ebecf0; padding-bottom: 10px; margin-bottom: 15px;">Sauvegardes disponibles sur le serveur</h3>
            <div style="overflow-x:auto;">
                <table class="notes-table" style="width:100%; border-collapse:collapse;">
                    <thead>
                        <tr>
                            <th style="text-align:left; padding:8px; border-bottom:1px solid #dfe1e6; color:#5e6c84; font-size:13px;">Nom du fichier</th>
                            <th style="text-align:left; padding:8px; border-bottom:1px solid #dfe1e6; color:#5e6c84; font-size:13px;">Date</th>
                            <th style="text-align:left; padding:8px; border-bottom:1px solid #dfe1e6; color:#5e6c84; font-size:13px;">Taille</th>
                            <th style="text-align:right; padding:8px; border-bottom:1px solid #dfe1e6; color:#5e6c84; font-size:13px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($backups as $b): ?>
                        <tr>
                            <td style="padding:10px 8px; border-bottom:1px solid #f4f5f7; font-size:14px;"><code><?= htmlspecialchars($b['name']) ?></code></td>
                            <td style="padding:10px 8px; border-bottom:1px solid #f4f5f7; font-size:14px;"><?= date('d/m/Y H:i', $b['date']) ?></td>
                            <td style="padding:10px 8px; border-bottom:1px solid #f4f5f7; font-size:14px;"><?= round($b['size'] / 1024, 1) ?> Ko</td>
                            <td style="padding:10px 8px; border-bottom:1px solid #f4f5f7; text-align:right;">
                                <a href="api.php?action=download_server_backup&filename=<?= urlencode($b['name']) ?>" class="btn" style="background:#0052cc; padding:6px 12px; font-size:13px; text-decoration:none; display:inline-block; margin-right:5px;">Télécharger</a>
                                <a href="api.php?action=restore_server_backup&filename=<?= urlencode($b['name']) ?>" class="btn" style="background:#00875a; padding:6px 12px; font-size:13px; text-decoration:none; display:inline-block;" onclick="showConfirmRestoreModal(event, this);">Restaurer</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div id="panel-history" class="admin-tab-content">
        <div class="json-card">
            <h3 style="margin:0; font-size:16px; color:#091e42; border-bottom: 1px solid #ebecf0; padding-bottom: 10px; margin-bottom: 15px;">Journal d'historique des actions sur l'application</h3>
            <div style="overflow-x:auto;">
                <table class="notes-table" style="width:100%; border-collapse:collapse; min-width:700px;">
                    <thead>
                        <tr>
                            <th style="width:180px;">Horodatage</th>
                            <th style="width:130px;">Type d'action</th>
                            <th>Détails de l'opération</th>
                        </tr>
                    </thead>
                    <tbody id="table-body-history">
                        </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        window.hasUnsavedChanges = false;
        
        window.addEventListener('beforeunload', function (e) {
            if (window.hasUnsavedChanges) {
                e.preventDefault();
                e.returnValue = '';
            }
        });

        function markDirty() {
            window.hasUnsavedChanges = true;
        }

        // Ajout d'écouteurs sur les champs généraux
        document.addEventListener('DOMContentLoaded', () => {
            const inputsToWatch = [
                'input-app-title', 'input-team-name', 'input-require-read', 'input-read-password',
                'input-enable-code-projet', 'input-enable-code-itbm', 'input-enable-charge-jh', 'input-enable-gantt'
            ];
            inputsToWatch.forEach(id => {
                const el = document.getElementById(id);
                if(el) {
                    el.addEventListener('input', markDirty);
                    el.addEventListener('change', markDirty);
                }
            });
        });

        function returnToBoard() {
            if (window.hasUnsavedChanges) {
                showConfirmModal("Quitter la page d'administration ?\nVos modifications n'ont pas été enregistrées et seront perdues.", () => {
                    window.hasUnsavedChanges = false;
                    window.location.href = 'index.php';
                });
            } else {
                window.location.href = 'index.php';
            }
        }

        function showAdminModal(msg, isError = false) {
            document.getElementById('admin-modal-icon').innerText = isError ? '❌' : '✅';
            document.getElementById('admin-modal-title').innerText = isError ? 'Erreur' : 'Succès';
            document.getElementById('admin-modal-msg').innerText = msg;
            document.getElementById('admin-modal').style.display = 'flex';
        }

        let settingsData = { app_title: "", team_name: "", app_theme: "classic", app_logo: "", require_read_password: false, enable_code_projet: true, enable_code_itbm: true, projets: [], acteurs: [], priorites: [], reunions: [] };

        const palette32 = [
            '#ff9f1a', '#ffb8d2', '#ff5630', '#ff7452', '#00875a', '#36b37e', '#00a3bf', '#00c7e6',
            '#0052cc', '#2684ff', '#5243aa', '#8777d9', '#172b4d', '#42526e', '#006644', '#b3bac5',
            '#c9372c', '#d97a80', '#e34935', '#f29c97', '#f5cd47', '#f8e6a0', '#4bce97', '#9dd9c2',
            '#57d9a3', '#7ee2b8', '#8fdfeb', '#b3f5ff', '#0c66e4', '#5ce1e6', '#6554c0', '#e774bb'
        ];

        function initPalette() {
            const container = document.getElementById('palette-container');
            palette32.forEach((color, index) => {
                const div = document.createElement('div');
                div.className = 'color-swatch' + (index === 8 ? ' selected' : '');
                div.style.backgroundColor = color;
                div.onclick = () => selectColor(div, color);
                container.appendChild(div);
            });
        }

        function selectColor(element, color) {
            document.querySelectorAll('.color-swatch').forEach(el => el.classList.remove('selected'));
            element.classList.add('selected');
            document.getElementById('selected-project-color').value = color;
        }

        function switchAdminTab(panelId, btn) {
            document.querySelectorAll('.admin-tab-content').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.admin-tab-btn').forEach(el => el.classList.remove('active'));
            
            document.getElementById(panelId).classList.add('active');
            btn.classList.add('active');

            if (panelId === 'panel-json') {
                loadRawJsonFiles();
            } else if (panelId === 'panel-history') {
                loadHistoryLog();
            }
        }

        function switchAdminSubTab(panelId, btn) {
            document.querySelectorAll('.admin-sub-content').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.admin-sub-tab-btn').forEach(el => el.classList.remove('active'));
            
            document.getElementById(panelId).classList.add('active');
            btn.classList.add('active');
        }

        fetch('api.php?action=get_settings')
            .then(res => res.json())
            .then(data => {
                settingsData = { ...settingsData, ...data };
                document.getElementById('input-app-title').value = settingsData.app_title || '';
                document.getElementById('input-team-name').value = settingsData.team_name || '';
                document.getElementById('input-app-theme').value = settingsData.app_theme || 'classic';
                
                document.querySelectorAll('.theme-card').forEach(c => c.style.borderColor = 'transparent');
                let activeCard = document.querySelector(`.theme-card[data-value="${settingsData.app_theme || 'classic'}"]`);
                if (activeCard) activeCard.style.borderColor = '#0052cc';
                
                document.getElementById('input-require-read').value = settingsData.require_read_password ? "1" : "0";
                toggleReadPasswordInput();

                document.getElementById('input-enable-code-projet').value = settingsData.enable_code_projet === false ? "0" : "1";
                document.getElementById('input-enable-code-itbm').value = settingsData.enable_code_itbm === false ? "0" : "1";
                document.getElementById('input-enable-charge-jh').value = settingsData.enable_charge_jh === false ? "0" : "1";
                document.getElementById('input-enable-gantt').value = settingsData.enable_gantt === false ? "0" : "1";

                if (settingsData.app_logo) {
                    const img = document.getElementById('current-logo-preview');
                    img.src = settingsData.app_logo + '?t=' + Date.now();
                    img.style.display = 'inline-block';
                    img.onerror = function() {
                        this.style.display = 'none';
                        const label = document.getElementById('logo-upload-label');
                        label.innerText = '⚠️ Image introuvable. Veuillez renvoyer le logo.';
                        label.style.color = '#de350b';
                    };
                }

                initPalette();
                renderLists();
            });

        document.getElementById('input-require-read').addEventListener('change', toggleReadPasswordInput);

        function toggleReadPasswordInput() {
            const val = document.getElementById('input-require-read').value;
            document.getElementById('read-password-container').style.display = val === "1" ? 'block' : 'none';
        }

        function saveSecurity() {
            const req = document.getElementById('input-require-read').value === "1";
            const pass = document.getElementById('input-read-password').value;

            if (req && !pass && !settingsData.require_read_password) {
                showAdminModal("Vous devez définir un mot de passe pour activer la protection d'accès.", true);
                return;
            }

            fetch('api.php?action=save_security', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ require_read_password: req, readonly_password: pass })
            })
            .then(res => res.json())
            .then(resData => {
                if(resData.success) {
                    window.hasUnsavedChanges = false;
                    showAdminModal('Sécurité enregistrée avec succès !');
                    settingsData.require_read_password = req;
                    document.getElementById('input-read-password').value = '';
                }
            });
        }

        function renderLists() {
            ['projets', 'acteurs', 'priorites', 'reunions'].forEach(category => {
                const ul = document.getElementById(`list-${category}`);
                ul.innerHTML = '';
                if(settingsData[category]) {
                    settingsData[category].forEach((item, index) => {
                        const li = document.createElement('li');
                        if (category === 'projets' && typeof item === 'object') {
                            li.style.display = 'flex';
                            li.style.justifyContent = 'space-between';
                            li.style.alignItems = 'flex-start';
                            const descHtml = item.description ? `<div style="font-size:11px; color:#5e6c84; margin-left: 20px; margin-top: 4px;">${item.description}</div>` : '';
                            li.innerHTML = `
                                <div style="flex-grow: 1;">
                                    <div><span class="project-badge" style="background-color:${item.color};"></span> <span style="font-weight:bold;">${item.name}</span></div>
                                    ${descHtml}
                                </div> 
                                <div style="display:flex; gap: 5px;">
                                    <button onclick="editProject(${index})" title="Modifier" style="background:#e3f2fd; color:#0052cc;">✏️</button>
                                    <button onclick="removeItem('${category}', ${index})" title="Supprimer">X</button>
                                </div>
                            `;
                        } else {
                            li.innerHTML = `${item} <button onclick="removeItem('${category}', ${index})" title="Supprimer">X</button>`;
                        }
                        ul.appendChild(li);
                    });
                }
            });
        }

        function addItem(category) {
            const input = document.getElementById(`input-${category}`);
            const val = input.value.trim();
            
            if (!val) return;

            if (!settingsData[category].includes(val)) {
                settingsData[category].push(val);
                input.value = '';
                markDirty();
                renderLists();
            }
        }

        // Helper for rgb to hex since element.style.backgroundColor returns rgb()
        function rgb2hex(rgb) {
            if (/^#[0-9A-F]{6}$/i.test(rgb)) return rgb;
            const match = rgb.match(/^rgb\((\d+),\s*(\d+),\s*(\d+)\)$/);
            if (!match) return null;
            function hex(x) { return ("0" + parseInt(x).toString(16)).slice(-2); }
            return "#" + hex(match[1]) + hex(match[2]) + hex(match[3]);
        }

        function editProject(index) {
            const p = settingsData.projets[index];
            document.getElementById('edit-project-index').value = index;
            document.getElementById('input-projets').value = p.name;
            document.getElementById('input-projet-desc').value = p.description || '';
            
            // Set color
            document.getElementById('selected-project-color').value = p.color;
            document.querySelectorAll('.color-swatch').forEach(el => {
                const elColor = rgb2hex(el.style.backgroundColor);
                const pColor = p.color.toLowerCase();
                if (elColor === pColor || el.style.backgroundColor === pColor) {
                    el.classList.add('selected');
                } else {
                    el.classList.remove('selected');
                }
            });

            document.getElementById('btn-save-project').innerText = "Enregistrer";
            document.getElementById('btn-cancel-project').style.display = "block";
            document.getElementById('project-form-container').style.background = "#e3f2fd";
        }

        function cancelEditProject() {
            document.getElementById('edit-project-index').value = "-1";
            document.getElementById('input-projets').value = "";
            document.getElementById('input-projet-desc').value = "";
            document.getElementById('btn-save-project').innerText = "Ajouter le projet";
            document.getElementById('btn-cancel-project').style.display = "none";
            document.getElementById('project-form-container').style.background = "#fafbfc";
        }

        function saveProject() {
            const name = document.getElementById('input-projets').value.trim();
            const desc = document.getElementById('input-projet-desc').value.trim();
            const color = document.getElementById('selected-project-color').value;
            const editIndex = parseInt(document.getElementById('edit-project-index').value);

            if (!name) return;

            if (editIndex >= 0) {
                // Check if renaming to an existing name (different index)
                if (settingsData.projets.some((p, i) => p.name === name && i !== editIndex)) {
                    showAdminModal("Un projet avec ce nom existe déjà.", true);
                    return;
                }
                settingsData.projets[editIndex] = { name: name, color: color, description: desc };
            } else {
                if (settingsData.projets.some(p => p.name === name)) {
                    showAdminModal("Un projet avec ce nom existe déjà.", true);
                    return;
                }
                settingsData.projets.push({ name: name, color: color, description: desc });
            }

            markDirty();
            cancelEditProject();
            renderLists();
        }

        let confirmCallback = null;
        function showConfirmModal(msg, callback) {
            document.getElementById('confirm-modal-msg').innerText = msg;
            confirmCallback = callback;
            document.getElementById('confirm-modal').style.display = 'flex';
        }
        function closeConfirmModal() {
            document.getElementById('confirm-modal').style.display = 'none';
            confirmCallback = null;
        }
        document.getElementById('confirm-modal-btn').addEventListener('click', function() {
            if (confirmCallback) confirmCallback();
            closeConfirmModal();
        });

        function removeItem(category, index) {
            showConfirmModal("Êtes-vous sûr de vouloir supprimer cet élément ? Cette action nécessitera d'enregistrer la configuration pour être définitive.", () => {
                settingsData[category].splice(index, 1);
                markDirty();
                renderLists();
            });
        }

        function selectTheme(el) {
            document.querySelectorAll('.theme-card').forEach(c => c.style.borderColor = 'transparent');
            el.style.borderColor = '#0052cc';
            const val = el.getAttribute('data-value');
            document.getElementById('input-app-theme').value = val;
            document.body.setAttribute('data-theme', val);
            markDirty();
        }

        function saveSettings() {
            settingsData.app_title = document.getElementById('input-app-title').value.trim();
            settingsData.team_name = document.getElementById('input-team-name').value.trim();
            settingsData.app_theme = document.getElementById('input-app-theme').value;
            settingsData.enable_code_projet = document.getElementById('input-enable-code-projet').value === "1";
            settingsData.enable_code_itbm = document.getElementById('input-enable-code-itbm').value === "1";
            settingsData.enable_charge_jh = document.getElementById('input-enable-charge-jh').value === "1";
            settingsData.enable_gantt = document.getElementById('input-enable-gantt').value === "1";

            fetch('api.php?action=save_settings', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(settingsData)
            })
            .then(res => res.json())
            .then(resData => {
                if(resData.success) {
                    window.hasUnsavedChanges = false;
                    showAdminModal('Configuration enregistrée avec succès !');
                }
            });
        }

        function uploadLogo(input) {
            const label = document.getElementById('logo-upload-label');
            if (!input.files || input.files.length === 0) return;
            
            const formData = new FormData();
            formData.append('logo', input.files[0]);
            
            label.innerText = '⏳ Upload en cours...';
            label.style.color = '#ff8b00';
            
            fetch('api.php?action=upload_logo', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const img = document.getElementById('current-logo-preview');
                    img.src = data.logo_path + '?t=' + Date.now();
                    img.style.display = 'inline-block';
                    img.onerror = null; // Retire l'erreur si la nouvelle image marche
                    label.innerText = '✅ Logo mis à jour';
                    label.style.color = '#00875a';
                    settingsData.app_logo = data.logo_path;
                } else {
                    showAdminModal(data.error || "Erreur lors de l'envoi.", true);
                    label.innerText = '🖼️ Modifier le logo';
                    label.style.color = '#5e6c84';
                }
            });
        }

        function loadRawJsonFiles() {
            fetch('api.php?action=get_raw_json&file=kanban')
                .then(res => res.text())
                .then(text => document.getElementById('textarea-kanban').value = text);

            fetch('api.php?action=get_raw_json&file=settings')
                .then(res => res.text())
                .then(text => document.getElementById('textarea-settings').value = text);
        }

        function saveRawJson(fileName) {
            const rawContent = document.getElementById(`textarea-${fileName}`).value;
            fetch('api.php?action=save_raw_json', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ file: fileName, content: rawContent })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) { showAdminModal(`Fichier ${fileName}.json enregistré.`); } 
                else { showAdminModal(`Erreur : ${data.error}`, true); }
            });
        }

        function updateUploadLabel(input) {
            const label = document.getElementById('file-upload-label');
            if (input.files && input.files.length > 0) {
                label.innerText = `📦 Fichier prêt : ${input.files[0].name}`;
                label.style.color = '#00875a';
            } else {
                label.innerText = '📁 Cliquez ou glissez votre archive ZIP ici';
                label.style.color = '#5e6c84';
            }
        }

        function loadHistoryLog() {
            fetch('api.php?action=get_history')
                .then(res => res.json())
                .then(data => {
                    const tbody = document.getElementById('table-body-history');
                    tbody.innerHTML = '';
                    
                    if (!data || data.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="3" style="text-align:center; color:#888; font-style:italic; padding: 20px;">Aucun événement consigné dans l\'historique pour le moment.</td></tr>';
                        return;
                    }
                    
                    data.forEach(item => {
                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                            <td style="color:#5e6c84; font-weight:600;">${item.date}</td>
                            <td><span class="badge-reunion" style="background:#e3f2fd; color:#0052cc; font-size:11px;">${item.action}</span></td>
                            <td style="font-weight:500;">${item.details}</td>
                        `;
                        tbody.appendChild(tr);
                    });
                });
        }
        let currentRestoreForm = null;
        let currentRestoreUrl = null;

        function showConfirmRestoreModal(event, element) {
            event.preventDefault();
            document.getElementById('confirm-restore-modal').style.display = 'flex';
            
            if (element.tagName && element.tagName.toLowerCase() === 'form') {
                currentRestoreForm = element;
                currentRestoreUrl = null;
            } else if (element.tagName && element.tagName.toLowerCase() === 'a') {
                currentRestoreUrl = element.href;
                currentRestoreForm = null;
            }
        }

        function closeConfirmModal() {
            document.getElementById('confirm-restore-modal').style.display = 'none';
            currentRestoreForm = null;
            currentRestoreUrl = null;
        }

        document.addEventListener('DOMContentLoaded', function() {
            const confirmBtn = document.getElementById('btn-confirm-restore');
            if (confirmBtn) {
                confirmBtn.addEventListener('click', function() {
                    if (currentRestoreForm) {
                        currentRestoreForm.submit();
                    } else if (currentRestoreUrl) {
                        window.location.href = currentRestoreUrl;
                    }
                });
            }
        });
    </script>
    
    <div class="modal-overlay" id="confirm-restore-modal" style="display:none; z-index:9999;">
        <div class="modal-content" style="max-width: 400px; text-align: center;">
            <h2 style="margin-top: 0; color: #091e42;">Confirmer la restauration</h2>
            <p style="color: #5e6c84; font-size: 14px; margin-bottom: 25px;">Êtes-vous sûr de vouloir restaurer cette sauvegarde ? Cela écrasera <b>toutes</b> les données actuelles.</p>
            <div style="display: flex; gap: 10px; justify-content: center;">
                <button onclick="closeConfirmModal()" class="btn" style="background:#ebecf0; color:#42526e; border:none; padding:10px 20px;">Annuler</button>
                <button id="btn-confirm-restore" class="btn" style="background:#00875a; border:none; padding:10px 20px;">Oui, restaurer</button>
            </div>
        </div>
    </div>
    
    <div style="text-align: center; margin-top: 40px; padding-bottom: 20px; font-size: 13px; color: #888;">
        &copy; <?= htmlspecialchars($about_data['author'] ?? 'Nicolas Housset') ?> | 
        <a href="<?= htmlspecialchars($about_data['github'] ?? '') ?>" target="_blank" style="color: #0052cc; text-decoration: none;">GitHub</a> | 
        Version : <?= $compilation_date ?>
    </div>
</body>
</html>
