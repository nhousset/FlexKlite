<?php 
require_once 'auth.php'; 
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Suivi de Chantiers - Kanban</title>
    <link rel="stylesheet" href="style.css?<?= time() ?>">
</head>
<body>

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <h1 style="margin: 0;">Tableau Kanban - Suivi des Chantiers</h1>
        <div>
            <a href="admin.php" class="btn" style="background: #e3f2fd; color: #0052cc; text-decoration: none; margin-right: 10px;">⚙️ Paramètres</a>
            <a href="logout.php" class="btn" style="background: #ffebee; color: #d32f2f; text-decoration: none;">Se déconnecter</a>
        </div>
    </div>

    <?php 
    $settings_file = __DIR__ . '/db/settings.json';
    $settings = file_exists($settings_file) ? json_decode(file_get_contents($settings_file), true) : ["projets" => [], "acteurs" => [], "priorites" => []]; 
    ?>

    <div class="forms-container">
        <form action="api.php?action=add_task" method="POST">
            
            <select name="projet" required>
                <option value="">-- Projet --</option>
                <?php foreach($settings['projets'] as $p): ?>
                    <option value="<?= htmlspecialchars($p) ?>"><?= htmlspecialchars($p) ?></option>
                <?php endforeach; ?>
            </select>

            <input type="text" name="titre" placeholder="Intitulé de la tâche" style="width: 250px;" required>
            
            <select name="couleur">
                <option value="color-yellow">🟨 Standard</option>
                <option value="color-blue">🟦 Étude/Tech</option>
                <option value="color-orange">🟧 Urgence</option>
                <option value="color-pink">🟥 Bug/Bloquant</option>
                <option value="color-green">🟩 Validé</option>
                <option value="color-grey">⬜ En attente</option>
            </select>

            <select name="prio">
                <option value="">-- Prio --</option>
                <?php foreach($settings['priorites'] as $p): ?>
                    <option value="<?= htmlspecialchars($p) ?>"><?= htmlspecialchars($p) ?></option>
                <?php endforeach; ?>
            </select>

            <select name="acteur">
                <option value="">-- Acteur --</option>
                <?php foreach($settings['acteurs'] as $a): ?>
                    <option value="<?= htmlspecialchars($a) ?>"><?= htmlspecialchars($a) ?></option>
                <?php endforeach; ?>
            </select>

            <input type="text" name="note_initiale" placeholder="Note (optionnel)" style="width: 150px;">
            <button type="submit" class="btn">Ajouter la tâche</button>
        </form>
    </div>

    <div class="board">
        <div class="column" id="todo">
            <h3>À Faire</h3>
            <div class="list" data-status="todo"></div>
        </div>
        <div class="column" id="in_progress">
            <h3>En Cours</h3>
            <div class="list" data-status="in_progress"></div>
        </div>
        <div class="column" id="blocked">
            <h3>En attente / Bloqué</h3>
            <div class="list" data-status="blocked"></div>
        </div>
        <div class="column" id="done">
            <h3>Terminé</h3>
            <div class="list" data-status="done"></div>
        </div>
    </div>

    <div id="details-panel">
        <span class="close-panel" onclick="closePanel()">×</span>
        <h2 id="panel-title" style="font-size: 18px; margin-top: 0; color: #091e42; line-height: 1.3;"></h2>
        <p style="font-size: 12px; color:#5e6c84; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 1px solid #ebecf0;">
            Projet : <strong id="panel-project" style="color: #172b4d;"></strong> | 
            Acteur : <strong id="panel-acteur" style="color: #172b4d;"></strong>
        </p>
        
        <h4 style="margin-bottom: 10px; font-size:14px; color: #172b4d;">Ajouter un point de suivi :</h4>
        <textarea id="new-note-text" style="width:100%; height:80px; margin-bottom:12px; padding: 10px; border: 1px solid #dfe1e6; border-radius: 4px; font-family:inherit; box-sizing: border-box;"></textarea>
        <button class="btn" style="width: 100%;" onclick="submitNote()">Enregistrer la note</button>
        
        <h4 style="margin-top: 30px; font-size:14px; color: #172b4d;">Historique des notes :</h4>
        <div id="panel-notes-list"></div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
    <script>
        let currentTaskRef = { column: null, index: null };

        function loadBoard() {
            fetch('api.php?action=get')
                .then(res => res.json())
                .then(data => {
                    Object.keys(data).forEach(status => {
                        const container = document.querySelector(`[data-status="${status}"]`);
                        container.innerHTML = '';
                        data[status].forEach((task, index) => {
                            const card = document.createElement('div');
                            
                            // Application dynamique de la classe de couleur (Jaune par défaut si absent)
                            const colorClass = task.couleur ? task.couleur : 'color-yellow';
                            card.className = `card ${colorClass}`;
                            
                            card.dataset.index = index;
                            card.onclick = () => openPanel(status, index, task);
                            
                            card.innerHTML = `
                                <div class="card-header">
                                    <span class="tag-project">${task.projet}</span>
                                    ${task.prio ? `<span class="prio">Prio ${task.prio}</span>` : ''}
                                </div>
                                <div class="card-title">${task.titre}</div>
                                <div class="card-footer">
                                    <span>${task.acteur || task.porteur || 'Non assigné'}</span>
                                    <span>MAJ: ${task.maj}</span>
                                </div>
                            `;
                            container.appendChild(card);
                        });
                    });
                });
        }

        document.querySelectorAll('.list').forEach(listEl => {
            new Sortable(listEl, {
                group: 'kanban-board',
                animation: 200,
                ghostClass: 'sortable-ghost',
                onEnd: function (evt) {
                    const fromColumn = evt.from.dataset.status;
                    const toColumn = evt.to.dataset.status;
                    const fromIndex = evt.oldIndex;
                    const toIndex = evt.newIndex;

                    if (fromColumn === toColumn && fromIndex === toIndex) return;

                    fetch('api.php?action=move', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ fromColumn, toColumn, fromIndex, toIndex })
                    }).then(() => {
                        loadBoard(); 
                        if (document.getElementById('details-panel').classList.contains('open')) {
                            closePanel();
                        }
                    });
                }
            });
        });

        function openPanel(column, index, task) {
            currentTaskRef = { column, index };
            document.getElementById('panel-title').innerText = task.titre;
            document.getElementById('panel-project').innerText = task.projet;
            document.getElementById('panel-acteur').innerText = task.acteur || 'Non assigné';
            document.getElementById('new-note-text').value = '';
            
            const listContainer = document.getElementById('panel-notes-list');
            listContainer.innerHTML = '';
            
            if (task.notes && task.notes.length > 0) {
                task.notes.forEach(note => {
                    const item = document.createElement('div');
                    item.className = 'note-item';
                    item.innerHTML = `<div class="note-date">${note.date}</div><div>${note.texte}</div>`;
                    listContainer.appendChild(item);
                });
            } else {
                listContainer.innerHTML = '<p style="font-size:13px; color:#888; font-style: italic;">Aucun historique de suivi pour cette tâche.</p>';
            }
            
            document.getElementById('details-panel').classList.add('open');
        }

        function closePanel() {
            document.getElementById('details-panel').classList.remove('open');
        }

        function submitNote() {
            const text = document.getElementById('new-note-text').value;
            if (!text.trim()) return;

            fetch('api.php?action=add_note', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    column: currentTaskRef.column,
                    index: currentTaskRef.index,
                    text: text
                })
            })
            .then(res => res.json())
            .then(resData => {
                if(resData.success) {
                    openPanel(currentTaskRef.column, currentTaskRef.index, resData.task);
                    loadBoard();
                }
            });
        }

        window.onload = loadBoard;
    </script>
</body>
</html>
