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
        <div style="display: flex; gap: 10px;">
            <button onclick="openAddTaskModal()" class="btn" style="background: #00875a;">➕ Nouvelle Tâche</button>
            <a href="admin.php" class="btn" style="background: #e3f2fd; color: #0052cc; text-decoration: none;">⚙️ Paramètres</a>
            <a href="logout.php" class="btn" style="background: #ffebee; color: #d32f2f; text-decoration: none;">Se déconnecter</a>
        </div>
    </div>

    <?php 
    $settings_file = __DIR__ . '/db/settings.json';
    $default = ["projets" => [], "acteurs" => [], "priorites" => [], "reunions" => []];
    $settings = file_exists($settings_file) ? array_merge($default, json_decode(file_get_contents($settings_file), true)) : $default; 
    ?>

    <div id="add-task-modal" class="modal-overlay" onclick="closeAddTaskModal(event)">
        <div class="modal-content" onclick="event.stopPropagation()" style="max-width: 700px;">
            <span class="modal-close" onclick="closeAddTaskModal(event)">×</span>
            <h2 style="margin-top: 0; color: #091e42; margin-bottom: 25px;">Créer une nouvelle tâche</h2>
            
            <form action="api.php?action=add_task" method="POST">
                <div class="form-grid">
                    
                    <div class="form-group full-width">
                        <label>Intitulé de la tâche *</label>
                        <input type="text" name="titre" required>
                    </div>

                    <div class="form-group">
                        <label>Type / Couleur</label>
                        <select name="couleur">
                            <option value="color-yellow">🟨 Standard</option>
                            <option value="color-blue">🟦 Étude/Tech</option>
                            <option value="color-orange">🟧 Urgence</option>
                            <option value="color-pink">🟥 Bug/Bloquant</option>
                            <option value="color-green">🟩 Validé</option>
                            <option value="color-grey">⬜ En attente</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Projet *</label>
                        <select name="projet" required>
                            <option value="">-- Sélectionner --</option>
                            <?php foreach($settings['projets'] as $p): ?>
                                <option value="<?= htmlspecialchars($p) ?>"><?= htmlspecialchars($p) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Code Projet (Optionnel)</label>
                        <input type="text" name="code_projet" placeholder="Ex: PRJ-2026">
                    </div>

                    <div class="form-group">
                        <label>Code ITBM (Optionnel)</label>
                        <input type="text" name="code_itbm" placeholder="Ex: TSK0123456">
                    </div>

                    <div class="form-group">
                        <label>Acteur / Porteur</label>
                        <select name="acteur">
                            <option value="">-- Non assigné --</option>
                            <?php foreach($settings['acteurs'] as $a): ?>
                                <option value="<?= htmlspecialchars($a) ?>"><?= htmlspecialchars($a) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Priorité</label>
                        <select name="prio">
                            <option value="">-- Non définie --</option>
                            <?php foreach($settings['priorites'] as $p): ?>
                                <option value="<?= htmlspecialchars($p) ?>"><?= htmlspecialchars($p) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Date de début</label>
                        <input type="date" name="date_debut">
                    </div>

                    <div class="form-group">
                        <label>Date de fin / Échéance</label>
                        <input type="date" name="date_fin">
                    </div>

                    <div class="form-group full-width">
                        <label>Note de suivi initiale (Optionnelle)</label>
                        <textarea name="note_initiale" rows="3" placeholder="Saisir le contexte initial de création..."></textarea>
                    </div>

                </div>
                
                <div style="text-align: right; border-top: 1px solid #dfe1e6; padding-top: 20px;">
                    <button type="button" class="btn" style="background: #ebecf0; color: #42526e; margin-right: 10px;" onclick="closeAddTaskModal(event)">Annuler</button>
                    <button type="submit" class="btn" style="background: #00875a; padding: 10px 20px;">Créer la tâche</button>
                </div>
            </form>
        </div>
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
            
            <div class="task-meta-info">
                <div>Projet : <strong id="modal-project"></strong></div>
                <div id="modal-code-projet-container" style="display:none;">Code Projet : <strong id="modal-code-projet"></strong></div>
                <div id="modal-itbm-container" style="display:none;">ITBM : <strong id="modal-itbm"></strong></div>
                <div>Acteur : <strong id="modal-acteur"></strong></div>
            </div>
            
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
        <h2 id="panel-title" style="font-size: 22px; margin-top: 0; color: #091e42; line-height: 1.3; margin-bottom: 15px;"></h2>
        
        <div class="task-meta-info">
            <div>Projet : <strong id="panel-project"></strong></div>
            <div id="panel-code-projet-container" style="display:none;">Code Projet : <strong id="panel-code-projet"></strong></div>
            <div id="panel-itbm-container" style="display:none;">ITBM : <strong id="panel-itbm"></strong></div>
            <div>Acteur : <strong id="panel-acteur"></strong></div>
            <div id="panel-dates-container" style="display:none;">Dates : <strong id="panel-dates"></strong></div>
        </div>
        
        <h4 style="margin-bottom: 10px; margin-top: 20px; font-size:15px; color: #172b4d;">Ajouter un point de suivi :</h4>
        
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

        <h4 style="margin-top: 40px; font-size:15px; color: #172b4d; border-bottom: 2px solid #ebecf0; padding-bottom: 10px;">Historique des notes</h4>
        <div id="panel-notes-list"></div>
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
                            
                            card.addEventListener('click', () => openHistoryModal(task));
                            card.addEventListener('contextmenu', (e) => {
                                e.preventDefault();
                                showContextMenu(e, status, index, task);
                            });
                            
                            // Affichage des tags conditionnels
                            let extraTags = '';
                            if(task.code_itbm) extraTags += `<span class="tag tag-itbm">🎫 ${task.code_itbm}</span>`;
                            if(task.prio) extraTags += `<span class="tag tag-prio">🔥 Prio ${task.prio}</span>`;

                            card.innerHTML = `
                                <div class="tags-container">
                                    <span class="tag">📁 ${task.projet}</span>
                                    ${extraTags}
                                </div>
                                <div class="card-title">${task.titre}</div>
                                <div class="card-footer">
                                    <span title="Assigné à">🧑‍💻 ${task.acteur || 'Non assigné'}</span>
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
                delay: 100,
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

        // --- GESTION DES MODALES ---
        function openAddTaskModal() {
            document.getElementById('add-task-modal').style.display = 'flex';
        }

        function closeAddTaskModal(e) {
            if(e) e.stopPropagation();
            document.getElementById('add-task-modal').style.display = 'none';
        }

        function openHistoryModal(task) {
            document.getElementById('modal-title').innerText = task.titre;
            document.getElementById('modal-project').innerText = task.projet;
            document.getElementById('modal-acteur').innerText = task.acteur || 'Non assigné';
            
            if(task.code_projet) {
                document.getElementById('modal-code-projet').innerText = task.code_projet;
                document.getElementById('modal-code-projet-container').style.display = 'block';
            } else {
                document.getElementById('modal-code-projet-container').style.display = 'none';
            }

            if(task.code_itbm) {
                document.getElementById('modal-itbm').innerText = task.code_itbm;
                document.getElementById('modal-itbm-container').style.display = 'block';
            } else {
                document.getElementById('modal-itbm-container').style.display = 'none';
            }
            
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

        // --- GESTION DU CLIC DROIT ---
        function showContextMenu(e, column, index, task) {
            const menu = document.getElementById('context-menu');
            menu.style.display = 'block';
            menu.style.left = e.pageX + 'px';
            menu.style.top = e.pageY + 'px';
            currentTaskRef = { column, index, task };
        }

        document.addEventListener('click', () => {
            document.getElementById('context-menu').style.display = 'none';
        });

        document.getElementById('menu-add-note').addEventListener('click', (e) => {
            e.stopPropagation();
            document.getElementById('context-menu').style.display = 'none';
            openAddNotePanel();
        });

        // --- GESTION DU PANNEAU LATÉRAL ---
        function openAddNotePanel() {
            const task = currentTaskRef.task;
            document.getElementById('panel-title').innerText = task.titre;
            
            // Remplissage des métadonnées
            document.getElementById('panel-project').innerText = task.projet;
            document.getElementById('panel-acteur').innerText = task.acteur || 'Non assigné';
            
            if(task.code_projet) {
                document.getElementById('panel-code-projet').innerText = task.code_projet;
                document.getElementById('panel-code-projet-container').style.display = 'block';
            } else {
                document.getElementById('panel-code-projet-container').style.display = 'none';
            }

            if(task.code_itbm) {
                document.getElementById('panel-itbm').innerText = task.code_itbm;
                document.getElementById('panel-itbm-container').style.display = 'block';
            } else {
                document.getElementById('panel-itbm-container').style.display = 'none';
            }

            if(task.date_debut || task.date_fin) {
                const debut = task.date_debut ? task.date_debut.split('-').reverse().join('/') : '?';
                const fin = task.date_fin ? task.date_fin.split('-').reverse().join('/') : '?';
                document.getElementById('panel-dates').innerText = `${debut} ➔ ${fin}`;
                document.getElementById('panel-dates-container').style.display = 'block';
            } else {
                document.getElementById('panel-dates-container').style.display = 'none';
            }

            // Init formulaires
            document.getElementById('new-note-text').value = '';
            document.getElementById('new-note-reunion').value = '';
            document.getElementById('new-note-date').valueAsDate = new Date(); 
            
            // Remplissage de l'historique
            const listContainer = document.getElementById('panel-notes-list');
            listContainer.innerHTML = '';
            
            if (task.notes && task.notes.length > 0) {
                task.notes.forEach(note => {
                    const item = document.createElement('div');
                    item.className = 'note-item';
                    const badge = note.reunion ? `<span class="badge-reunion">${note.reunion}</span>` : '';
                    item.innerHTML = `
                        <div class="note-date">🗓️ ${note.date} ${badge}</div>
                        <div style="white-space: pre-wrap;">${note.texte}</div>
                    `;
                    listContainer.appendChild(item);
                });
            } else {
                listContainer.innerHTML = '<p style="font-size:14px; color:#888; font-style: italic;">Aucun historique de suivi pour cette tâche.</p>';
            }

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
                    currentTaskRef.task = resData.task;
                    openAddNotePanel(); 
                    loadBoard();
                }
            });
        }

        window.onload = loadBoard;
    </script>
</body>
</html>
