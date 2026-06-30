<?php 
require_once 'auth.php'; 
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Administration - Kanban JSON</title>
    <link rel="stylesheet" href="style.css?<?= time() ?>">
    <style>
        .admin-grid { display: flex; gap: 20px; flex-wrap: wrap; }
        .admin-card { background: white; padding: 20px; border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); flex: 1; min-width: 250px; }
        .admin-card h3 { margin-top: 0; color: #5e6c84; font-size: 14px; text-transform: uppercase; border-bottom: 2px solid #eef2f5; padding-bottom: 10px;}
        .item-list { list-style: none; padding: 0; margin: 0 0 15px 0; }
        .item-list li { display: flex; justify-content: space-between; padding: 8px; background: #f4f5f7; margin-bottom: 5px; border-radius: 4px; font-size: 13px; }
        .item-list li button { background: none; border: none; color: #d32f2f; cursor: pointer; font-weight: bold; }
        .add-group { display: flex; gap: 10px; }
        .add-group input { flex: 1; padding: 6px; border: 1px solid #ccc; border-radius: 4px; }
    </style>
</head>
<body>

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h1 style="margin: 0;">Administration des données</h1>
        <div>
            <a href="index.php" class="btn" style="background: #5e6c84; text-decoration: none; margin-right: 10px;">Retour au Kanban</a>
            <button onclick="saveSettings()" class="btn" style="background: #00875a;">Enregistrer les modifications</button>
        </div>
    </div>

    <div class="admin-grid">
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
    </div>

    <script>
        let settingsData = { projets: [], acteurs: [], priorites: [] };

        // Charger les données
        fetch('api.php?action=get_settings')
            .then(res => res.json())
            .then(data => {
                settingsData = data;
                renderLists();
            });

        function renderLists() {
            ['projets', 'acteurs', 'priorites'].forEach(category => {
                const ul = document.getElementById(`list-${category}`);
                ul.innerHTML = '';
                settingsData[category].forEach((item, index) => {
                    const li = document.createElement('li');
                    li.innerHTML = `${item} <button onclick="removeItem('${category}', ${index})" title="Supprimer">X</button>`;
                    ul.appendChild(li);
                });
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
            fetch('api.php?action=save_settings', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(settingsData)
            })
            .then(res => res.json())
            .then(resData => {
                if(resData.success) alert('Paramètres enregistrés avec succès !');
            });
        }
    </script>
</body>
</html>
