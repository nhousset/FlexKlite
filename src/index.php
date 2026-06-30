<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Suivi de Chantiers - Kanban JSON</title>
    <link rel="stylesheet" href="style.css?<?= time() ?>">
</head>
<body>

    <h1>Mon Kanban Chantiers & Suivi</h1>

    <div class="forms-container">
        <form action="api.php?action=add_task" method="POST">
            <input type="text" name="projet" placeholder="Projet (ex: VIYA 4)" required>
            <input type="text" name="titre" placeholder="Intitulé de la tâche" style="width: 250px;" required>
            <input type="text" name="prio" placeholder="Prio" style="width: 50px;">
            <input type="text" name="porteur" placeholder="Porteur" style="width: 80px;">
            <input type="text" name="acteur" placeholder="Acteur" style="width: 80px;">
            <input type="text" name="note_initiale" placeholder="Première note de suivi (optionnel)" style="width: 250px;">
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
        <span class="close-panel" onclick="closePanel()">X</span>
        <h2 id="panel-title" style="font-size: 16px; margin-top: 0;"></h2>
        <p style="font-size: 12px; color:#6b778c;">Projet : <span id="panel-project"></span> | Acteur : <span id="panel-acteur"></span></p>
        
        <h4 style="margin-bottom: 8px; font-size:13px;">Ajouter un point de suivi :</h4>
        <textarea id="new-note-text" style="width:100%; height:60px; margin-bottom:8px; font-family:inherit;"></textarea>
        <button class="btn" onclick="submitNote()">Enregistrer la note</button>
        
        <h4 style="margin-top: 20px; border-top: 1px solid #ddd; padding-top: 15px; font-size:13px;">Historique des notes :</h4>
        <div id="panel-notes-list"></div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
    <script>
        let currentTaskRef = { column: null, index: null };

        // Charger et afficher les données
        function loadBoard() {
            fetch('api.php?action=get')
                .then(res => res.json())
                .then(data => {
                    Object.keys(data).forEach(status => {
                        const container = document.querySelector(`[data-status="${status}"]`);
                        container.innerHTML = '';
                        data[status].forEach((task, index) => {
                            const card = document.createElement('div');
                            card.className = 'card';
                            card.dataset.index = index;
                            card.onclick = () => openPanel(status, index, task);
                            
                            card.innerHTML = `
                                <div class="card-header">
                                    <span class="tag-project">${task.projet}</span>
                                    ${task.prio ? `<span class="prio">Prio ${task.prio}</span>` : ''}
                                </div>
                                <div class="card-title">${task.titre}</div>
                                <div class="card-footer">
                                    <span>${task.acteur || task.porteur || ''}</span>
                                    <span>MAJ: ${task.maj}</span>
                                </div>
                            `;
                            container.appendChild(card);
                        });
                    });
                });
        }

        // Configuration du Drag & Drop
        document.querySelectorAll('.list').forEach(listEl => {
            new Sortable(listEl, {
                group: 'kanban-board',
                animation: 150,
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

        // Gestion du panneau latéral de suivi
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
                    item.innerHTML = `<div class="note-date">Le ${note.date}</div><div>${note.texte}</div>`;
                    listContainer.appendChild(item);
                });
            } else {
                listContainer.innerHTML = '<p style="font-size:12px; color:#888; italic">Aucun historique de suivi.</p>';
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
