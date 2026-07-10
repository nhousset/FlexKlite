function loadBoard() {
    fetch('api.php?action=get&_t=' + Date.now())
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
                    
                    // Bloquer le clic-droit si mode invité
                    card.addEventListener('contextmenu', (e) => { 
                        if (window.IS_LOGGED_IN) {
                            e.preventDefault(); 
                            showContextMenu(e, status, index, task); 
                        }
                    });
                    
                    let extraTags = '';
                    if(task.code_itbm) extraTags += `<span class="tag tag-itbm">🎫 ${task.code_itbm}</span>`;
                    if(task.prio) extraTags += `<span class="tag tag-prio" title="Priorité">${task.prio}</span>`;
                    if(task.lots && task.lots.length > 0) extraTags += `<span class="tag" style="background:#e8f5e9; color:#006644; border-color:#b7eb8f;">📦 ${task.lots.length} Lot(s)</span>`;
                    if(task.attachments && task.attachments.length > 0) extraTags += `<span class="tag" style="background:#fff3e0; color:#e65100; border-color:#ffcc80;">📎 ${task.attachments.length} Fichier(s)</span>`;

                    card.innerHTML = `
                        <div class="tags-container"><span class="tag">📁 ${task.projet}</span>${extraTags}</div>
                        <div class="card-title">${task.titre}</div>
                        <div class="card-footer">
                            <span title="Assigné à">🧑‍💻 ${task.acteur || 'Non assigné'}</span>
                            <span title="Dernière mise à jour">🕒 ${task.maj}</span>
                        </div>
                    `;
                    if(container) container.appendChild(card);

                    // Construction du bloc des notes (Agrégation)
                    let notesHtml = '';
                    const allTaskNotes = getAllNotesAggregated(task);
                    if (allTaskNotes.length > 0) {
                        const top5 = allTaskNotes.slice(0, 5);
                        notesHtml = top5.map(n => {
                            const ctx = n.reunion ? ` - <strong>${n.reunion}</strong>` : '';
                            const srcBadge = n.sourceName ? `<span class="note-target-badge">${n.sourceName}</span><br/>` : '';
                            return `<div style="font-size: 13px; margin-bottom: 8px; padding-bottom: 8px; border-bottom: 1px dashed #ebecf0; line-height: 1.4;" class="note-entry">
                                        <span style="color:#5e6c84; font-weight: 600;">${n.date}${ctx} :</span> <br/>${srcBadge}${n.texte}
                                    </div>`;
                        }).join('');
                    } else {
                        notesHtml = `<span style="color:#aaa; font-style:italic; font-size:13px;" class="note-entry">Aucune note</span>`;
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
                        tr.addEventListener('contextmenu', (e) => { 
                            if (window.IS_LOGGED_IN) {
                                e.preventDefault(); 
                                showContextMenu(e, status, index, task); 
                            }
                        });
                        
                        const actLabel = task.acteur || '-';
                        const prioLabel = task.prio || '-';
                        
                        tr.innerHTML = `
                            <td><span class="tag tag-itbm" style="background:none; border:1px solid #dfe1e6;">📁 ${task.projet}</span></td>
                            <td style="font-weight: 500;">${task.titre}</td>
                            <td><span class="status-badge status-${status}">${statusLabels[status]}</span></td>
                            <td style="font-weight:bold; color:#c62828;">${prioLabel !== '-' ? prioLabel : '-'}</td>
                            <td>🧑‍💻 ${actLabel}</td>
                            <td style="color:#5e6c84; white-space:nowrap; font-weight: 600;">🕒 ${task.maj}</td>
                            <td>${notesHtml}</td>
                        `;
                        listTableBody.appendChild(tr);
                    }

                    kpi.total++; kpi.status[status]++;
                    const acteur = task.acteur || 'Non assigné'; kpi.acteur[acteur] = (kpi.acteur[acteur] || 0) + 1;
                    const prio = task.prio || 'Aucune'; kpi.prio[prio] = (kpi.prio[prio] || 0) + 1;

                    if (allTaskNotes.length > 0) {
                        allTaskNotes.forEach(note => {
                            allNotesForActivity.push({
                                taskTitle: task.titre, projet: task.projet,
                                texte: (note.sourceName ? `[${note.sourceName}] ` : '') + note.texte, 
                                date: note.date, reunion: note.reunion,
                                timestamp: note.timestamp || 0 
                            });
                        });
                    }
                });
            });

            renderKPIs(kpi);
            renderRecentActivity(allNotesForActivity);
            applyFilters();
            if(currentSort.column) { applySort(currentSort.column, currentSort.asc); }
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

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.list').forEach(listEl => {
        new Sortable(listEl, {
            group: 'kanban-board', animation: 200, ghostClass: 'sortable-ghost', delay: 100, delayOnTouchOnly: true,
            disabled: !window.IS_LOGGED_IN, // Désactivation du Drag & Drop si invité
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

    loadBoard();
});
