<?php 
require_once 'auth.php'; 
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Administration - Configuration</title>
    <link rel="stylesheet" href="style.css?<?= time() ?>">
    <style>
        .admin-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
        .admin-card { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 20px rgba(9, 30, 66, 0.05); border: 1px solid #ebecf0;}
        .admin-card h3 { margin-top: 0; color: #091e42; font-size: 16px; font-weight: 600; border-bottom: 2px solid #f4f5f7; padding-bottom: 12px; margin-bottom: 20px;}
        .item-list { list-style: none; padding: 0; margin: 0 0 20px 0; }
        .item-list li { display: flex; justify-content: space-between; align-items: center; padding: 10px 12px; background: #f4f5f7; margin-bottom: 8px; border-radius: 4px; font-size: 14px; border: 1px solid #ebecf0;}
        .item-list li button { background: #ffebee; border: none; color: #d32f2f; cursor: pointer; font-weight: bold; border-radius: 4px; padding: 4px 8px; transition: background 0.2s;}
        .item-list li button:hover { background: #ffcdd2; }
        .add-group { display: flex; gap: 10px; }
        .add-group input { flex: 1; padding: 10px; border: 1px solid #dfe1e6; border-radius: 4px; font-size: 14px;}
        .add-group input:focus { outline: none; border-color: var(--primary); }
        
        .form-group-admin label { font-size: 13px; font-weight: 600; color: #172b4d; display: block; margin-bottom: 6px; }
        .form-group-admin input { width: 100%; padding: 10px; border: 1px solid #dfe1e6; border-radius: 4px; font-size: 14px; box-sizing: border-box; background: #fafbfc;}
        .form-group-admin input:focus { border-color: var(--primary); outline: none; background: white;}
    </style>
</head>
<body>

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <h1 style="margin: 0;">Console d'Administration</h1>
        <div>
            <a href="index.php" class="btn" style="background: #ebecf0; color: #42526e; text-decoration: none; margin-right: 10px;">📊 Retour au Tableau</a>
        </div>
    </div>

    <?php if(isset($_GET['status'])): ?>
        <?php if($_GET['status'] === 'import_ok'): ?>
            <div class="alert-banner alert-success">✅ Restauration réussie ! Les fichiers JSON ont été importés et validés avec succès.</div>
        <?php elseif($_GET['status'] === 'import_error'): ?>
            <div class="alert-banner alert-danger">❌ Erreur lors de la restauration. Assurez-vous que l'archive ZIP contient des fichiers JSON valides.</div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="admin-tabs-header">
        <button class="admin-tab-btn active" onclick="switchAdminTab('panel-lists', this)">⚙️ Configuration des Éléments</button>
        <button class="admin-tab-btn" onclick="switchAdminTab('panel-json', this)">📝 Éditeur Brut JSON</button>
        <button class="admin-tab-btn" onclick="switchAdminTab('panel-backup', this)">💾 Sauvegarde & Restauration (ZIP)</button>
        <button class="admin-tab-btn" onclick="switchAdminTab('panel-history', this)">📜 Journal des Actions</button>
    </div>

    <div id="panel-lists" class="admin-tab-content active">
        <div style="display: flex; justify-content: flex-end; margin-bottom: 15px;">
            <button onclick="saveSettings()" class="btn" style="background: #00875a;">Enregistrer la configuration</button>
        </div>
        
        <div class="admin-grid">
            <div class="admin-card" style="grid-column: 1 / -1;">
                <h3>Paramètres Généraux de l'Application</h3>
                <div style="display: flex; gap: 20px; flex-wrap: wrap;">
                    <div class="form-group-admin" style="flex: 1; min-width: 250px;">
                        <label>Titre de l'application</label>
                        <input type="text" id="input-app-title">
                    </div>
                    <div class="form-group-admin" style="flex: 1; min-width: 250px;">
                        <label>Nom de l'équipe</label>
                        <input type="text" id="input-team-name">
                    </div>
                </div>
            </div>

            <div class="admin-card">
                <h3>Projets</h3>
                <ul class="item-list" id="list-projets"></ul>
                <div class="add-group">
                    <input type="text" id="input-projets" placeholder="Nouveau projet...">
                    <button class="btn" onclick="addItem('projets')">Ajouter</button>
                </div>
            </div>

            <div class="admin-card">
                <h3>Acteurs / Porteurs</h3>
                <ul class="item-list" id="list-acteurs"></ul>
                <div class="add-group">
                    <input type="text" id="input-acteurs" placeholder="Nouvel acteur...">
                    <button class="btn" onclick="addItem('acteurs')">Ajouter</button>
                </div>
            </div>

            <div class="admin-card">
                <h3>Priorités</h3>
                <ul class="item-list" id="list-priorites"></ul>
                <div class="add-group">
                    <input type="text" id="input-priorites" placeholder="Nouvelle priorité...">
                    <button class="btn" onclick="addItem('priorites')">Ajouter</button>
                </div>
            </div>

            <div class="admin-card">
                <h3>Types de Réunions</h3>
                <ul class="item-list" id="list-reunions"></ul>
                <div class="add-group">
                    <input type="text" id="input-reunions" placeholder="Point équipe, Coproj...">
                    <button class="btn" onclick="addItem('reunions')">Ajouter</button>
                </div>
            </div>
        </div>
    </div>

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
                <p style="color:var(--text-muted); font-size:14px; margin:0 0 10px 0; max-width:280px;">Générez et téléchargez instantanément une archive ZIP contenant vos tâches, vos notes et vos paramètres.</p>
                <a href="api.php?action=export_backup_zip" class="btn" style="text-decoration:none; background:#0052cc; padding:12px 24px;">Créer un Backup (.ZIP)</a>
            </div>
            <div class="backup-card">
                <div style="background: #e8f5e9; color: #00875a; width:60px; height:60px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:24px;">📥</div>
                <h3 style="margin:0; color:#091e42; font-size:18px;">Restaurer une sauvegarde</h3>
                <p style="color:var(--text-muted); font-size:14px; margin:0 0 10px 0; max-width:280px;">Importez une archive de sauvegarde précédemment exportée pour restaurer l'état complet.</p>
                <form action="api.php?action=import_backup_zip" method="POST" enctype="multipart/form-data" style="width:100%;" id="backup-form">
                    <input type="file" name="zip_file" accept=".zip" required style="margin-bottom: 15px; display: block; width: 100%;">
                    <button type="submit" class="btn" style="background: #00875a; width:100%; padding:12px;">Démarrer la Restauration</button>
                </form>
            </div>
        </div>
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
                            <th style="width:100px; text-align:center;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="table-body-history">
                        </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        let settingsData = { app_title: "", team_name: "", projets: [], acteurs: [], priorites: [], reunions: [] };

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

        fetch('api.php?action=get_settings')
            .then(res => res.json())
            .then(data => {
                settingsData = { ...settingsData, ...data };
                document.getElementById('input-app-title').value = settingsData.app_title || '';
                document.getElementById('input-team-name').value = settingsData.team_name || '';
                renderLists();
            });

        function renderLists() {
            ['projets', 'acteurs', 'priorites', 'reunions'].forEach(category => {
                const ul = document.getElementById(`list-${category}`);
                ul.innerHTML = '';
                if(settingsData[category]) {
                    settingsData[category].forEach((item, index) => {
                        const li = document.createElement('li');
                        li.innerHTML = `${item} <button onclick="removeItem('${category}', ${index})" title="Supprimer">X</button>`;
                        ul.appendChild(li);
                    });
                }
            });
        }

        function addItem(category) {
            const input = document.getElementById(`input-${category}`);
            const val = input.value.trim();
            if (val && !settingsData[category].includes(val)) {
                settingsData[category].push(val);
                input.value = '';
                renderLists();
            }
        }

        function removeItem(category, index) {
            settingsData[category].splice(index, 1);
            renderLists();
        }

        function saveSettings() {
            settingsData.app_title = document.getElementById('input-app-title').value.trim();
            settingsData.team_name = document.getElementById('input-team-name').value.trim();

            fetch('api.php?action=save_settings', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(settingsData)
            })
            .then(res => res.json())
            .then(resData => {
                if(resData.success) alert('Configuration enregistrée avec succès !');
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
                if (data.success) { alert(`Fichier ${fileName}.json enregistré.`); } 
                else { alert(`Erreur : ${data.error}`); }
            });
        }

        /* ================= NEW JS FOR SYSTEM LOG HISTORY ================= */
        function loadHistoryLog() {
            fetch('api.php?action=get_history')
                .then(res => res.json())
                .then(data => {
                    const tbody = document.getElementById('table-body-history');
                    tbody.innerHTML = '';
                    
                    if (!data || data.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="4" style="text-align:center; color:#888; font-style:italic; padding: 20px;">Aucun événement consigné dans l\'historique pour le moment.</td></tr>';
                        return;
                    }
                    
                    data.forEach(item => {
                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                            <td style="color:#5e6c84; font-weight:600;">${item.date}</td>
                            <td><span class="badge-reunion" style="background:#e3f2fd; color:#0052cc; font-size:11px;">${item.action}</span></td>
                            <td style="font-weight:500;">${item.details}</td>
                            <td style="text-align:center;">
                                <button class="btn" style="background:#de350b; padding:5px 10px; font-size:12px;" onclick="deleteLogLine('${item.id}')">Supprimer</button>
                            </td>
                        `;
                        tbody.appendChild(tr);
                    });
                });
        }

        function deleteLogLine(lineId) {
            if (confirm('Êtes-vous sûr de vouloir purger cette ligne du journal d\'historique ?')) {
                fetch('api.php?action=delete_history_line', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: lineId })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        loadHistoryLog();
                    }
                });
            }
        }
    </script>
</body>
</html>
