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
    $default = ["projets" => [], "acteurs" => [], "priorites" => [], "reunions" => []];
    $settings = file_exists($settings_file) ? array_merge($default, json_decode(file_get_contents($settings_file), true)) : $default; 
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

    <div id="context-menu">
        <div class="context-menu-item" id="menu-add-note">
            ➕ Ajouter un point de suivi
        </div>
    </div>

    <div id="notes-modal" class="modal-overlay" onclick="closeModal(event)">
        <div class="modal-content" onclick="event.stopPropagation()">
            <span class="modal-close" onclick="closeModal(event)">×</span>
            <h2 id="modal-title" style="margin-top: 0; color: #091e42;"></h2>
            <p style="font-size: 13px; color:#5e6c84; margin-bottom: 20px;">
                Projet : <strong id="modal-project"></strong> | 
                Acteur : <strong id="modal-acteur"></strong>
            </p>
            
            <table class="notes-table">
                <thead>
                    <tr>
                        <th style="width: 120px;">Date</th>
                        <th style="width: 150px;">Contexte</th>
                        <th>Détails du suivi</th>
                    </tr>
                </thead>
                <tbody id="modal-table-body">
                    </tbody>
            </table>
        </div>
    </div>

    <div id="details-panel">
        <span class="close-panel" onclick="closePanel()">×</span>
        <h2 id="panel-title" style="font-size: 20px; margin-top: 0; color: #091e42; line-height: 1.3;"></h2>
        
        <h4 style="margin-bottom: 10px; margin-top: 30px; font-size:15px; color: #172b4d;">Ajouter un point de suivi :</h4>
        
        <div class="note-meta-inputs">
            <input type="date" id="new-note-date" title="Date de la note" style="max-width: 130px;">
            <select id="new-note-reunion">
                <option value="">-- Contexte / Réunion --</option>
                <?php foreach($settings['reunions'] as $r): ?>
                    <option value="<?= htmlspecialchars($r) ?>"><?= htmlspecialchars($r) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <textarea id="new-note-text" style="width:100%; height:120px; margin-bottom:12px; padding: 10px; border: 1px solid #dfe1e6; border-radius: 4px; font-family:inherit; box-sizing: border-box; resize: vertical;" placeholder="Saisir les détails abordés..."></textarea>
        <button class="btn" style="width: 100%; padding: 12px; font-size: 15px;" onclick="submitNote()">Enregistrer</button>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
    <script>
        let currentTaskRef = { column: null, index: null, task: null };

        function loadBoard() {
            fetch('api.php?action=get')
                .then(res => res.json())
                .then(data => {
                    Object.keys(data).forEach(status => {
                        const container = document.querySelector(`[data-status="${status}"]`);
                        container.innerHTML = '';
                        data[status].forEach((task, index) => {
                            const card = document.createElement('div');
                            
                            const colorClass = task.couleur ? task.couleur : 'color-yellow';
                            card.className = `card ${colorClass}`;
                            card.dataset.index = index;
                            
                            // Événement Clic Gauche : Ouvre le tableau
                            card.addEventListener('click', () => openHistoryModal(task));
                            
                            // Événement Clic Droit : Ouvre le menu
                            card.addEventListener('contextmenu', (e) => {
                                e.preventDefault();
                                showContextMenu(e, status, index, task);
                            });
                            
                            card.innerHTML = `
                                <div class="tags-container">
                                    <span class="tag">📁 ${task.projet}</span>
                                    ${task.prio ? `<span class="tag tag-prio">🔥 Prio ${task.prio}</span>` : ''}
                                </div>
                                <div class="card-title">${task.titre}</div>
                                <div class="card-footer">
                                    <span title="Assigné à">🧑‍💻 ${task.acteur || task.porteur || 'Non assigné'}</span>
                                    <span title="Dernière mise à jour">🕒 ${task.maj}</span>
                                </div>
                            `;
                            container.appendChild(card);
                        });
                    });
                });
        }

        // --- GESTION DU DRAG & DROP ---
        document.querySelectorAll('.list').forEach(listEl => {
            new Sortable(listEl, {
                group: 'kanban-board',
                animation: 200,
                ghostClass: 'sortable-ghost',
                delay: 100, // Evite de déclencher le drag accidentellement au clic
                delayOnTouchOnly: true,
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
                    });
                }
            });
        });

        // --- GESTION DU CLIC GAUCHE (MODALE TABLEAU) ---
        function openHistoryModal(task) {
            document.getElementById('modal-title').innerText = task.titre;
            document.getElementById('modal-project').innerText = task.projet;
            document.getElementById('modal-acteur').innerText = task.acteur || 'Non assigné';
            
            const tbody = document.getElementById('modal-table-body');
            tbody.innerHTML = '';
            
            if (task.notes && task.notes.length > 0) {
                task.notes.forEach(note => {
                    const tr = document.createElement('tr');
                    const badge = note.reunion ? `<span class="badge-reunion">${note.reunion}</span>` : '<span style="color:#aaa;">-</span>';
                    tr.innerHTML = `
                        <td>${note.date}</td>
                        <td>${badge}</td>
                        <td style="white-space: pre-wrap;">${note.texte}</td>
                    `;
                    tbody.appendChild(tr);
                });
            } else {
                tbody.innerHTML = '<tr><td colspan="3" style="text-align:center; color:#888; font-style:italic;">Aucun historique de suivi.</td></tr>';
            }
            
            document.getElementById('notes-modal').style.display = 'flex';
        }

        function closeModal(e) {
            if(e) e.stopPropagation();
            document.getElementById('notes-modal').style.display = 'none';
        }

        // --- GESTION DU CLIC DROIT (MENU CONTEXTUEL) ---
        function showContextMenu(e, column, index, task) {
            const menu = document.getElementById('context-menu');
            
            // Positionne le menu à l'emplacement de la souris
            menu.style.display = 'block';
            menu.style.left = e.pageX + 'px';
            menu.style.top = e.pageY + 'px';
            
            // On stocke la référence de la tâche cliquée
            currentTaskRef = { column, index, task };
        }

        // Cacher le menu contextuel au clic ailleurs
        document.addEventListener('click', () => {
            document.getElementById('context-menu').style.display = 'none';
        });

        // Action quand on clique sur "Ajouter une note" dans le menu contextuel
        document.getElementById('menu-add-note').addEventListener('click', (e) => {
            e.stopPropagation(); // Évite la fermeture immédiate
            document.getElementById('context-menu').style.display = 'none';
            openAddNotePanel();
        });

        // --- GESTION DU PANNEAU D'AJOUT ---
        function openAddNotePanel() {
            const task = currentTaskRef.task;
            document.getElementById('panel-title').innerText = task.titre;
            
            // Réinitialisation du formulaire
            document.getElementById('new-note-text').value = '';
            document.getElementById('new-note-reunion').value = '';
            document.getElementById('new-note-date').valueAsDate = new Date(); // Date du jour
            
            document.getElementById('details-panel').classList.add('open');
        }

        function closePanel() {
            document.getElementById('details-panel').classList.remove('open');
        }

        function submitNote() {
            const text = document.getElementById('new-note-text').value;
            const date = document.getElementById('new-note-date').value;
            const reunion = document.getElementById('new-note-reunion').value;

            if (!text.trim()) return;

            fetch('api.php?action=add_note', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    column: currentTaskRef.column,
                    index: currentTaskRef.index,
                    text: text,
                    date: date,
                    reunion: reunion
                })
            })
            .then(res => res.json())
            .then(resData => {
                if(resData.success) {
                    closePanel();
                    loadBoard();
                    // Optionnel : ouvrir la modale pour voir la note fraîchement ajoutée
                    // openHistoryModal(resData.task);
                }
            });
        }

        window.onload = loadBoard;
    </script>
</body>
</html>
