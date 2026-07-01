<?php 
require_once 'auth.php'; 
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Administration - Paramètres</title>
    <link rel="stylesheet" href="style.css?<?= time() ?>">
    <style>
        .admin-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
        .admin-card { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid #ebecf0;}
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
        <h1 style="margin: 0;">Administration des paramètres</h1>
        <div>
            <a href="index.php" class="btn" style="background: #ebecf0; color: #42526e; text-decoration: none; margin-right: 10px;">Retour au Tableau</a>
            <button onclick="saveSettings()" class="btn" style="background: #00875a;">Enregistrer les modifications</button>
        </div>
    </div>

    <div class="admin-grid">

        <div class="admin-card" style="grid-column: 1 / -1;">
            <h3>Paramètres Généraux de l'Application</h3>
            <div style="display: flex; gap: 20px; flex-wrap: wrap;">
                <div class="form-group-admin" style="flex: 1; min-width: 250px;">
                    <label>Titre de l'application (Sera affiché en haut et dans l'onglet)</label>
                    <input type="text" id="input-app-title" placeholder="Ex: Gestion des Chantiers">
                </div>
                <div class="form-group-admin" style="flex: 1; min-width: 250px;">
                    <label>Nom de l'équipe (Badge mis en évidence)</label>
                    <input type="text" id="input-team-name" placeholder="Ex: Équipe IHMT">
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

    <script>
        let settingsData = { app_title: "", team_name: "", projets: [], acteurs: [], priorites: [], reunions: [] };

        fetch('api.php?action=get_settings')
            .then(res => res.json())
            .then(data => {
                settingsData = { ...settingsData, ...data };
                
                // Remplissage des champs textes
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
            // Mise à jour des valeurs textes avant sauvegarde
            settingsData.app_title = document.getElementById('input-app-title').value.trim();
            settingsData.team_name = document.getElementById('input-team-name').value.trim();

            fetch('api.php?action=save_settings', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(settingsData)
            })
            .then(res => res.json())
            .then(resData => {
                if(resData.success) alert('Paramètres mis à jour avec succès !');
            });
        }
    </script>
</body>
</html>
