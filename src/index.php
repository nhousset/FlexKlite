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
            
            <button onclick="openAddTaskModal()" class="btn-header btn-new-task">➕ Nouvelle Tâche</button>
            
            <!-- NOUVEAU : Le Menu Déroulant -->
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

        </div>
    </div>

    <div class="app-layout">
        <div class="main-content">
            <div class="tabs-header">
                <button class="tab-btn active" onclick="switchTab('tab-kanban', this)">🗂️ Vue Kanban</button>
                <button class="tab-btn" onclick="switchTab('tab-list', this)">📋 Vue Liste (Excel)</button>
                <button class="tab-btn" onclick="switchTab('tab-kpi', this)">📊 Tableau de Bord</button>
            </div>

            <?php 
            include 'kanban.php';
            include 'liste.php';
            include 'kpi.php';
            ?>
        </div>

        <aside class="activity-sidebar">
            <h3>⚡ Activité Récente</h3>
            <div id="recent-activity-list"></div>
        </aside>
    </div>

    <!-- ================= MODALES ET MENUS ================= -->

    <!-- Modale de Création de Tâche -->
    <div id="add-task-modal" class="modal-overlay" onclick="closeAddTaskModal(event)">
        <div class="modal-content" onclick="event.stopPropagation()" style="max-width: 700px;">
            <div class="panel-header-container">
                <h2 class="panel-header-title">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    Créer une nouvelle tâche
                </h2>
                <div class="close-panel" onclick="closeAddTaskModal(event)">×</div>
            </div>
            
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
                        <label>Code Projet</label>
                        <input type="text" name="code_projet" placeholder="Ex: PRJ-2026">
                    </div>
                    <div class="form-group">
                        <label>Code ITBM</label>
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
                        <label>Échéance</label>
                        <input type="date" name="date_fin">
                    </div>
                    <div class="form-group full-width">
                        <label>Note de suivi initiale (Optionnelle)</label>
                        <textarea name="note_initiale" rows="3" placeholder="Contexte initial..."></textarea>
                    </div>
                </div>
                <div style="text-align: right; border-top: 1px solid #dfe1e6; padding-top: 20px;">
                    <button type="button" class="btn" style="background: #ebecf0; color: #42526e; margin-right: 10px;" onclick="closeAddTaskModal(event)">Annuler</button>
                    <button type="submit" class="btn" style="background: #00875a; padding: 10px 20px;">Créer la tâche</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modale d'Édition de Tâche -->
    <div id="edit-task-modal" class="modal-overlay" onclick="closeEditTaskModal(event)">
        <div class="modal-content" onclick="event.stopPropagation()" style="max-width: 700px;">
            <div class="panel-header-container">
                <h2 class="panel-header-title">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                    </svg>
                    Modifier la tâche
                </h2>
                <div class="close-panel" onclick="closeEditTaskModal(event)">×</div>
            </div>
            
            <form action="api.php?action=edit_task" method="POST">
                <input type="hidden" name="column" id="edit_column">
                <input type="hidden" name="index" id="edit_index">

                <div class="form-grid">
                    <div class="form-group full-width">
                        <label>Intitulé de la tâche *</label>
                        <input type="text" name="titre" id="edit_titre" required>
                    </div>
                    <div class="form-group">
                        <label>Type / Couleur</label>
                        <select name="couleur" id="edit_couleur">
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
                        <select name="projet" id="edit_projet" required>
                            <option value="">-- Sélectionner --</option>
                            <?php foreach($settings['projets'] as $p): ?>
                                <option value="<?= htmlspecialchars($p) ?>"><?= htmlspecialchars($p) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Code Projet</label>
                        <input type="text" name="code_projet" id="edit_code_projet" placeholder="Ex: PRJ-2026">
                    </div>
                    <div class="form-group">
                        <label>Code ITBM</label>
                        <input type="text" name="code_itbm" id="edit_code_itbm" placeholder="Ex: TSK0123456">
                    </div>
                    <div class="form-group">
                        <label>Acteur / Porteur</label>
                        <select name="acteur" id="edit_acteur">
                            <option value="">-- Non assigné --</option>
                            <?php foreach($settings['acteurs'] as $a): ?>
                                <option value="<?= htmlspecialchars($a) ?>"><?= htmlspecialchars($a) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Priorité</label>
                        <select name="prio" id="edit_prio">
                            <option value="">-- Non définie --</option>
                            <?php foreach($settings['priorites'] as $p): ?>
                                <option value="<?= htmlspecialchars($p) ?>"><?= htmlspecialchars($p) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Date de début</label>
                        <input type="date" name="date_debut" id="edit_date_debut">
                    </div>
                    <div class="form-group">
                        <label>Échéance</label>
                        <input type="date" name="date_fin" id="edit_date_fin">
                    </div>
                </div>
                <div style="text-align: right; border-top: 1px solid #dfe1e6; padding-top: 20px;">
                    <button type="button" class="btn" style="background: #ebecf0; color: #42526e; margin-right: 10px;" onclick="closeEditTaskModal(event)">Annuler</button>
                    <button type="submit" class="btn" style="background: #0052cc; padding: 10px 20px;">Enregistrer les modifications</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Menu Contextuel -->
    <div id="context-menu">
        <div class="context-menu-item" id="menu-add-note">➕ Ajouter un point de suivi</div>
        <div class="context-menu-item" id="menu-edit-task">✏️ Modifier les paramètres</div>
    </div>

    <!-- Modale d'historique -->
    <div id="notes-modal" class="modal-overlay" onclick="closeModal(event)">
        <div class="modal-content" onclick="event.stopPropagation()">
            <div class="panel-header-container">
                <h2 class="panel-header-title">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                    Historique de la tâche
                </h2>
                <div style="display:flex; gap: 15px; align-items:center;">
                    <button class="btn-modal-add" onclick="switchToAddNote()">➕ Ajouter un point</button>
                    <div class="close-panel" onclick="closeModal(event)">×</div>
                </div>
            </div>
            
            <h3 id="modal-title" style="margin-top: 0; color: #091e42; font-size: 20px; margin-bottom: 20px;"></h3>
            
            <div class="task-meta-info">
                <div>Projet : <strong id="modal-project"></strong></div>
                <div id="modal-code-projet-container" style="display:none;">Code Projet : <strong id="modal-code-projet"></strong></div>
                <div id="modal-itbm-container" style="display:none;">ITBM : <strong id="modal-itbm"></strong></div>
                <div>Acteur : <strong id="modal-acteur"></strong></div>
            </div>
            
            <table class="notes-table">
                <thead>
                    <tr><th style="width: 120px;">Date</th><th style="width: 150px;">Contexte</th><th>Détails du suivi</th></tr>
                </thead>
                <tbody id="modal-table-body"></tbody>
            </table>
        </div>
    </div>

    <!-- Panneau latéral ajout de note -->
    <div id="details-panel">
        <div class="panel-header-container">
            <h2 class="panel-header-title">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
                </svg>
                Ajout d'une note
            </h2>
            <div class="close-panel" onclick="closePanel()">×</div>
        </div>

        <h3 id="panel-title" style="font-size: 20px; margin-top: 0; color: #091e42; line-height: 1.3; margin-bottom: 20px;"></h3>
        
        <div class="task-meta-info">
            <div>Projet : <strong id="panel-project"></strong></div>
            <div id="panel-code-projet-container" style="display:none;">Code Projet : <strong id="panel-code-projet"></strong></div>
            <div id="panel-itbm-container" style="display:none;">ITBM : <strong id="panel-itbm"></strong></div>
            <div>Acteur : <strong id="panel-acteur"></strong></div>
            <div id="panel-dates-container" style="display:none;">Dates : <strong id="panel-dates"></strong></div>
        </div>
        
        <h4 style="margin-bottom: 10px; margin-top: 20px; font-size:15px; color: #172b4d;">Saisir votre point de suivi :</h4>
        
        <div class="note-meta-inputs">
            <input type="date" id="new-note-date" title="Date de la note" style="max-width: 130px;">
            <select id="new-note-reunion">
                <option value="">-- Contexte / Réunion --</option>
                <?php foreach($settings['reunions'] as $r): ?>
                    <option value="<?= htmlspecialchars($r) ?>"><?= htmlspecialchars($r) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <textarea id="new-note-text" style="width:100%; height:120px; margin-bottom:12px; padding: 10px; border: 1px solid #dfe1e6; border-radius: 4px; font-family:inherit; box-sizing: border-box; resize: vertical;"></textarea>
        <button class="btn" style="width: 100%; padding: 12px; font-size: 15px;" onclick="submitNote()">Enregistrer la note</button>

        <h4 style="margin-top: 40px; font-size:15px; color: #172b4d; border-bottom: 2px solid #ebecf0; padding-bottom: 10px;">Historique des notes</h4>
        <div id="panel-notes-list"></div>
    </div>

    <!-- ================= LOGIQUE JS ================= -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
    <script>
        let currentTaskRef = { column: null, index: null, task: null };
        const statusLabels = { todo: 'À Faire', in_progress: 'En Cours', blocked: 'Bloqué / En attente', done: 'Terminé' };

        // --- GESTION DU MENU DÉROULANT DU HEADER ---
        function toggleHeaderMenu(e) {
            e.stopPropagation();
            document.getElementById('header-dropdown').classList.toggle('show');
        }

        // Ferme tous les menus contextuels ou dropdowns si on clique ailleurs
        document.addEventListener('click', () => { 
            const dropdown = document.getElementById('header-dropdown');
            if(dropdown && dropdown.classList.contains('show')) {
                dropdown.classList.remove('show');
            }
            document.getElementById('context-menu').style.display = 'none'; 
        });

        function switchTab(tabId, btn) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
            document.getElementById(tabId).classList.add('active');
            btn.classList.add('active');
        }

        function loadBoard() {
            fetch('api.php?action=get')
                .then(res => res.json())
                .then(data => {
                    const listTableBody = document.getElementById('list-table-body');
                    if(listTableBody) listTableBody.innerHTML = '';
                    
                    let kpi = { total: 0, status: { todo: 0, in_progress: 0, blocked: 0, done: 0 }, acteur: {}, prio: {} };
                    let allNotesForActivity = [];

                    Object.keys(data).forEach(status => {
                        const container = document.querySelector(`[data-status="${status}"]`);
                        if(container) container.innerHTML = ''; 
                        
                        data[status].forEach((task, index) => {
                            
                            const searchableText = `${task.titre} ${task.projet} ${task.code_projet||''} ${task.code_itbm||''}`.toLowerCase();
                            const pAttr = task.projet || '';
                            const aAttr = task.acteur || '';
                            const prAttr = task.prio || '';

                            // 1. VUE KANBAN
                            const card = document.createElement('div');
                            card.className = `card filter-item ${task.couleur ? task.couleur : 'color-yellow'}`;
                            card.dataset.index = index;
                            card.dataset.search = searchableText;
                            card.dataset.projet = pAttr;
                            card.dataset.acteur = aAttr;
                            card.dataset.statut = status;
                            card.dataset.prio = prAttr;
                            
                            card.addEventListener('click', () => openHistoryModal(task, status, index));
                            card.addEventListener('contextmenu', (e) => { e.preventDefault(); showContextMenu(e, status, index, task); });
                            
                            let extraTags = '';
                            if(task.code_itbm) extraTags += `<span class="tag tag-itbm">🎫 ${task.code_itbm}</span>`;
                            if(task.prio) extraTags += `<span class="tag tag-prio">🔥 Prio ${task.prio}</span>`;

                            card.innerHTML = `
                                <div class="tags-container"><span class="tag">📁 ${task.projet}</span>${extraTags}</div>
                                <div class="card-title">${task.titre}</div>
                                <div class="card-footer">
                                    <span title="Assigné à">🧑‍💻 ${task.acteur || 'Non assigné'}</span>
                                    <span title="Dernière mise à jour">🕒 ${task.maj}</span>
                                </div>
                            `;
                            if(container) container.appendChild(card);

                            // Construction du bloc des notes (pour la vue liste)
                            let notesHtml = '';
                            if (task.notes && task.notes.length > 0) {
                                const sortedNotes = [...task.notes].sort((a, b) => (b.timestamp || 0) - (a.timestamp || 0));
                                const top5 = sortedNotes.slice(0, 5);
                                notesHtml = top5.map(n => {
                                    const ctx = n.reunion ? ` - <strong>${n.reunion}</strong>` : '';
                                    return `<div style="font-size: 13px; margin-bottom: 8px; padding-bottom: 8px; border-bottom: 1px dashed #ebecf0; line-height: 1.4;">
                                                <span style="color:#5e6c84; font-weight: 600;">${n.date}${ctx} :</span> ${n.texte}
                                            </div>`;
                                }).join('');
                            } else {
                                notesHtml = `<span style="color:#aaa; font-style:italic; font-size:13px;">Aucune note</span>`;
                            }

                            // 2. VUE LISTE
                            if(listTableBody) {
                                const tr = document.createElement('tr');
                                tr.className = 'filter-item';
                                tr.dataset.search = searchableText;
                                tr.dataset.projet = pAttr;
                                tr.dataset.acteur = aAttr;
                                tr.dataset.statut = status;
                                tr.dataset.prio = prAttr;
                                
                                tr.dataset.titre = task.titre.toLowerCase();
                                const majParts = task.maj ? task.maj.split('/') : [];
                                tr.dataset.maj = majParts.length === 2 ? `${majParts[1]}${majParts[0]}` : (task.maj || '');

                                tr.addEventListener('click', () => openHistoryModal(task, status, index));
                                tr.addEventListener('contextmenu', (e) => { e.preventDefault(); showContextMenu(e, status, index, task); });
                                
                                const actLabel = task.acteur || '-';
                                const prioLabel = task.prio || '-';
                                
                                tr.innerHTML = `
                                    <td><span class="tag tag-itbm" style="background:none; border:1px solid #dfe1e6;">📁 ${task.projet}</span></td>
                                    <td style="font-weight: 500;">${task.titre}</td>
                                    <td><span class="status-badge status-${status}">${statusLabels[status]}</span></td>
                                    <td>${prioLabel !== '-' ? `🔥 ${prioLabel}` : '-'}</td>
                                    <td>🧑‍💻 ${actLabel}</td>
                                    <td style="color:#5e6c84; white-space:nowrap; font-weight: 600;">🕒 ${task.maj}</td>
                                    <td>${notesHtml}</td>
                                `;
                                listTableBody.appendChild(tr);
                            }

                            // 3. KPI & ACTIVITÉ
                            kpi.total++; kpi.status[status]++;
                            const acteur = task.acteur || 'Non assigné'; kpi.acteur[acteur] = (kpi.acteur[acteur] || 0) + 1;
                            const prio = task.prio || 'Aucune'; kpi.prio[prio] = (kpi.prio[prio] || 0) + 1;

                            if (task.notes && task.notes.length > 0) {
                                task.notes.forEach(note => {
                                    allNotesForActivity.push({
                                        taskTitle: task.titre, projet: task.projet,
                                        texte: note.texte, date: note.date, reunion: note.reunion,
                                        timestamp: note.timestamp || 0 
                                    });
                                });
                            }
                        });
                    });

                    renderKPIs(kpi);
                    renderRecentActivity(allNotesForActivity);
                    applyFilters();
                    
                    if(currentSort.column) {
                        applySort(currentSort.column, currentSort.asc);
                    }
                });
        }

        // --- GESTION DU TRI DYNAMIQUE ---
        let currentSort = { column: '', asc: true };

        function sortTable(column, headerEl) {
            if (currentSort.column === column) {
                currentSort.asc = !currentSort.asc;
            } else {
                currentSort.column = column;
                currentSort.asc = true;
            }

            document.querySelectorAll('.sort-icon').forEach(icon => icon.classList.remove('asc', 'desc'));
            if (headerEl) {
                const icon = headerEl.querySelector('.sort-icon');
                if (icon) icon.classList.add(currentSort.asc ? 'asc' : 'desc');
            }

            applySort(currentSort.column, currentSort.asc);
        }

        function applySort(column, isAsc) {
            const tbody = document.getElementById('list-table-body');
            if(!tbody) return;
            const rows = Array.from(tbody.querySelectorAll('tr'));

            rows.sort((a, b) => {
                let valA = a.dataset[column] || '';
                let valB = b.dataset[column] || '';

                if (column === 'prio') {
                    valA = valA === '' ? '999' : valA;
                    valB = valB === '' ? '999' : valB;
                    const numA = parseInt(valA);
                    const numB = parseInt(valB);
                    if(!isNaN(numA) && !isNaN(numB)) {
                        return isAsc ? numA - numB : numB - numA;
                    }
                }

                if (valA < valB) return isAsc ? -1 : 1;
                if (valA > valB) return isAsc ? 1 : -1;
                return 0;
            });

            rows.forEach(row => tbody.appendChild(row));
        }

        function applyFilters() {
            const searchEl = document.getElementById('filter-search');
            const projetEl = document.getElementById('filter-projet');
            const statutEl = document.getElementById('filter-statut');
            const prioEl = document.getElementById('filter-prio');
            const acteurEl = document.getElementById('filter-acteur');

            const search = searchEl ? searchEl.value.toLowerCase() : '';
            const projet = projetEl ? projetEl.value : '';
            const statut = statutEl ? statutEl.value : '';
            const prio = prioEl ? prioEl.value : '';
            const acteur = acteurEl ? acteurEl.value : '';

            document.querySelectorAll('.filter-item').forEach(item => {
                const text = item.dataset.search;
                const p = item.dataset.projet;
                const s = item.dataset.statut;
                const pr = item.dataset.prio;
                const a = item.dataset.acteur;
                
                const matchSearch = search === '' || text.includes(search);
                const matchProjet = projet === '' || p === projet;
                const matchStatut = statut === '' || s === statut;
                const matchPrio = prio === '' || pr === prio;
                const matchActeur = acteur === '' || a === acteur;

                if (matchSearch && matchProjet && matchStatut && matchPrio && matchActeur) {
                    item.style.display = item.tagName === 'TR' ? 'table-row' : 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        }

        function renderKPIs(kpi) {
            const kpiContainer = document.getElementById('kpi-container');
            if(!kpiContainer) return;

            const sortedActors = Object.entries(kpi.acteur).sort((a, b) => b[1] - a[1]);
            const sortedPrios = Object.entries(kpi.prio).sort((a, b) => b[1] - a[1]);

            kpiContainer.innerHTML = `
                <div class="kpi-card" style="display:flex; flex-direction:column; justify-content:center; align-items:center;">
                    <h3>Total des tâches</h3><div class="kpi-value-main">${kpi.total}</div><div class="kpi-value-label">Chantiers actifs et terminés</div>
                </div>
                <div class="kpi-card">
                    <h3>Par Statut</h3>
                    <ul class="kpi-list">
                        <li><span>À Faire</span> <span class="kpi-count">${kpi.status.todo}</span></li>
                        <li><span>En Cours</span> <span class="kpi-count">${kpi.status.in_progress}</span></li>
                        <li><span>En attente / Bloqué</span> <span class="kpi-count">${kpi.status.blocked}</span></li>
                        <li><span>Terminé</span> <span class="kpi-count">${kpi.status.done}</span></li>
                    </ul>
                </div>
                <div class="kpi-card">
                    <h3>Charge par Acteur</h3>
                    <ul class="kpi-list">${sortedActors.map(([actor, count]) => `<li><span>${actor}</span> <span class="kpi-count">${count}</span></li>`).join('')}</ul>
                </div>
                <div class="kpi-card">
                    <h3>Par Priorité</h3>
                    <ul class="kpi-list">${sortedPrios.map(([prio, count]) => `<li><span>${prio === 'Aucune' ? 'Non définie' : prio}</span> <span class="kpi-count">${count}</span></li>`).join('')}</ul>
                </div>
            `;
        }

        function renderRecentActivity(notes) {
            const container = document.getElementById('recent-activity-list');
            if(!container) return;

            container.innerHTML = '';
            
            notes.sort((a, b) => b.timestamp - a.timestamp);
            const top5 = notes.slice(0, 5);

            if(top5.length === 0) {
                container.innerHTML = '<p style="color:#888; font-size:13px; font-style:italic;">Aucune activité récente.</p>';
                return;
            }

            top5.forEach(note => {
                const item = document.createElement('div');
                item.className = 'activity-item';
                const badge = note.reunion ? `<span class="badge-reunion" style="font-size:10px;">${note.reunion}</span>` : '';
                item.innerHTML = `
                    <div class="activity-header">
                        <span class="tag" style="font-size:10px; padding:2px 6px;">📁 ${note.projet}</span>
                        <span style="font-size:11px; color:#5e6c84;">${note.date}</span>
                    </div>
                    <div class="activity-task">${note.taskTitle}</div>
                    <div class="activity-note">${badge} ${note.texte}</div>
                `;
                container.appendChild(item);
            });
        }

        document.querySelectorAll('.list').forEach(listEl => {
            new Sortable(listEl, {
                group: 'kanban-board', animation: 200, ghostClass: 'sortable-ghost', delay: 100, delayOnTouchOnly: true,
                onEnd: function (evt) {
                    const fromColumn = evt.from.dataset.status; const toColumn = evt.to.dataset.status;
                    const fromIndex = evt.oldIndex; const toIndex = evt.newIndex;
                    if (fromColumn === toColumn && fromIndex === toIndex) return;
                    fetch('api.php?action=move', {
                        method: 'POST', headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ fromColumn, toColumn, fromIndex, toIndex })
                    }).then(() => loadBoard());
                }
            });
        });

        // ================= GESTION DES MODALES D'AJOUT ET D'ÉDITION =================
        function openAddTaskModal() { document.getElementById('add-task-modal').style.display = 'flex'; }
        function closeAddTaskModal(e) { if(e) e.stopPropagation(); document.getElementById('add-task-modal').style.display = 'none'; }

        function openEditTaskModal() {
            const task = currentTaskRef.task;
            
            document.getElementById('edit_column').value = currentTaskRef.column;
            document.getElementById('edit_index').value = currentTaskRef.index;

            document.getElementById('edit_titre').value = task.titre || '';
            document.getElementById('edit_couleur').value = task.couleur || 'color-yellow';
            document.getElementById('edit_projet').value = task.projet || '';
            document.getElementById('edit_code_projet').value = task.code_projet || '';
            document.getElementById('edit_code_itbm').value = task.code_itbm || '';
            document.getElementById('edit_acteur').value = task.acteur || '';
            document.getElementById('edit_prio').value = task.prio || '';
            document.getElementById('edit_date_debut').value = task.date_debut || '';
            document.getElementById('edit_date_fin').value = task.date_fin || '';

            document.getElementById('edit-task-modal').style.display = 'flex';
        }
        function closeEditTaskModal(e) { if(e) e.stopPropagation(); document.getElementById('edit-task-modal').style.display = 'none'; }

        // ================= HISTORIQUE ET PANNEAU LATÉRAL =================
        function openHistoryModal(task, column, index) {
            currentTaskRef = { column, index, task };
            
            document.getElementById('modal-title').innerText = task.titre;
            document.getElementById('modal-project').innerText = task.projet;
            document.getElementById('modal-acteur').innerText = task.acteur || 'Non assigné';
            document.getElementById('modal-code-projet').innerText = task.code_projet || '';
            document.getElementById('modal-code-projet-container').style.display = task.code_projet ? 'block' : 'none';
            document.getElementById('modal-itbm').innerText = task.code_itbm || '';
            document.getElementById('modal-itbm-container').style.display = task.code_itbm ? 'block' : 'none';
            
            const tbody = document.getElementById('modal-table-body');
            tbody.innerHTML = '';
            if (task.notes && task.notes.length > 0) {
                task.notes.forEach(note => {
                    const tr = document.createElement('tr');
                    const badge = note.reunion ? `<span class="badge-reunion">${note.reunion}</span>` : '<span style="color:#aaa;">-</span>';
                    tr.innerHTML = `<td>${note.date}</td><td>${badge}</td><td style="white-space: pre-wrap;">${note.texte}</td>`;
                    tbody.appendChild(tr);
                });
            } else {
                tbody.innerHTML = '<tr><td colspan="3" style="text-align:center; color:#888; font-style:italic;">Aucun historique.</td></tr>';
            }
            document.getElementById('notes-modal').style.display = 'flex';
        }
        function closeModal(e) { if(e) e.stopPropagation(); document.getElementById('notes-modal').style.display = 'none'; }

        function switchToAddNote() { closeModal(); openAddNotePanel(); }

        function showContextMenu(e, column, index, task) {
            const menu = document.getElementById('context-menu');
            menu.style.display = 'block'; menu.style.left = e.pageX + 'px'; menu.style.top = e.pageY + 'px';
            currentTaskRef = { column, index, task };
        }
        
        document.getElementById('menu-add-note').addEventListener('click', (e) => {
            e.stopPropagation(); document.getElementById('context-menu').style.display = 'none'; openAddNotePanel();
        });
        document.getElementById('menu-edit-task').addEventListener('click', (e) => {
            e.stopPropagation(); document.getElementById('context-menu').style.display = 'none'; openEditTaskModal();
        });

        function openAddNotePanel() {
            const task = currentTaskRef.task;
            document.getElementById('panel-title').innerText = task.titre;
            document.getElementById('panel-project').innerText = task.projet;
            document.getElementById('panel-acteur').innerText = task.acteur || 'Non assigné';
            document.getElementById('panel-code-projet').innerText = task.code_projet || '';
            document.getElementById('panel-code-projet-container').style.display = task.code_projet ? 'block' : 'none';
            document.getElementById('panel-itbm').innerText = task.code_itbm || '';
            document.getElementById('panel-itbm-container').style.display = task.code_itbm ? 'block' : 'none';

            if(task.date_debut || task.date_fin) {
                const debut = task.date_debut ? task.date_debut.split('-').reverse().join('/') : '?';
                const fin = task.date_fin ? task.date_fin.split('-').reverse().join('/') : '?';
                document.getElementById('panel-dates').innerText = `${debut} ➔ ${fin}`;
                document.getElementById('panel-dates-container').style.display = 'block';
            } else {
                document.getElementById('panel-dates-container').style.display = 'none';
            }

            document.getElementById('new-note-text').value = '';
            document.getElementById('new-note-reunion').value = '';
            document.getElementById('new-note-date').valueAsDate = new Date(); 
            
            const listContainer = document.getElementById('panel-notes-list');
            listContainer.innerHTML = '';
            
            if (task.notes && task.notes.length > 0) {
                task.notes.forEach(note => {
                    const item = document.createElement('div');
                    item.className = 'note-item';
                    const badge = note.reunion ? `<span class="badge-reunion">${note.reunion}</span>` : '';
                    item.innerHTML = `<div class="note-date">🗓️ ${note.date} ${badge}</div><div style="white-space: pre-wrap;">${note.texte}</div>`;
                    listContainer.appendChild(item);
                });
            } else {
                listContainer.innerHTML = '<p style="font-size:14px; color:#888; font-style: italic;">Aucun historique de suivi.</p>';
            }
            document.getElementById('details-panel').classList.add('open');
        }

        function closePanel() { document.getElementById('details-panel').classList.remove('open'); }

        function submitNote() {
            const text = document.getElementById('new-note-text').value;
            const date = document.getElementById('new-note-date').value;
            const reunion = document.getElementById('new-note-reunion').value;
            if (!text.trim()) return;

            fetch('api.php?action=add_note', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ column: currentTaskRef.column, index: currentTaskRef.index, text: text, date: date, reunion: reunion })
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
