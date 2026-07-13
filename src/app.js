let currentTaskRef = { column: null, index: null, task: null, allNotes: [], editingNoteTimestamp: null };
const statusLabels = { todo: 'À Faire', in_progress: 'En Cours', blocked: 'Bloqué / En attente', done: 'Terminé' };

// Helper pour regrouper toutes les notes d'une tâche (principale + lots)
function getAllNotesAggregated(task) {
    let allNotes = [];
    if (task.notes && task.notes.length > 0) {
        allNotes = task.notes.map(n => ({ ...n, sourceName: '', lotId: '' }));
    }
    if (task.lots && task.lots.length > 0) {
        task.lots.forEach(lot => {
            if (lot.notes && lot.notes.length > 0) {
                lot.notes.forEach(n => {
                    allNotes.push({ ...n, sourceName: lot.titre, lotCode: lot.code_itbm, lotId: lot.id });
                });
            }
        });
    }
    return allNotes.sort((a, b) => (b.timestamp || 0) - (a.timestamp || 0));
}

function toggleHeaderMenu(e) {
    e.stopPropagation();
    document.getElementById('header-dropdown').classList.toggle('show');
}

document.addEventListener('click', () => { 
    const dropdown = document.getElementById('header-dropdown');
    if(dropdown && dropdown.classList.contains('show')) dropdown.classList.remove('show');
    const ctxMenu = document.getElementById('context-menu');
    if (ctxMenu) ctxMenu.style.display = 'none'; 
});

function switchTab(tabId, btn) {
    document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
    document.getElementById(tabId).classList.add('active');
    btn.classList.add('active');
}

function toggleActivityPanel() {
    const sidebar = document.getElementById('activity-sidebar-panel');
    const btn = document.getElementById('btn-toggle-activity');
    if (sidebar.style.display === 'none') {
        sidebar.style.display = 'block';
        btn.innerHTML = '👁️ Masquer l\'activité';
    } else {
        sidebar.style.display = 'none';
        btn.innerHTML = '👁️ Afficher l\'activité';
    }
}

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

                    // Construction du bloc des notes
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

                    // 3. KPI & ACTIVITÉ
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
            
            if(currentSort.column) {
                applySort(currentSort.column, currentSort.asc);
            }
        });
}

async function exportToExcel() {
    const workbook = new ExcelJS.Workbook();
    const worksheet = workbook.addWorksheet('Suivi des Chantiers');

    worksheet.columns = [
        { header: 'Projet', key: 'projet', width: 15 },
        { header: 'Tâche', key: 'tache', width: 45 },
        { header: 'Statut', key: 'statut', width: 15 },
        { header: 'Prio.', key: 'prio', width: 10 },
        { header: 'Acteur', key: 'acteur', width: 18 },
        { header: 'MAJ', key: 'maj', width: 12 },
        { header: 'Dernières notes (Historique)', key: 'notes', width: 75 }
    ];

    worksheet.getRow(1).eachCell((cell) => {
        cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF4472C4' } };
        cell.font = { color: { argb: 'FFFFFFFF' }, bold: true, name: 'Calibri', size: 11 };
        cell.alignment = { vertical: 'middle', horizontal: 'center' };
        cell.border = { top: {style:'thin'}, left: {style:'thin'}, bottom: {style:'thin'}, right: {style:'thin'} };
    });

    const rows = document.querySelectorAll('#list-table-body tr');
    rows.forEach(row => {
        if (row.style.display !== 'none') {
            const cells = row.querySelectorAll('td');
            const projet = cells[0].innerText.replace('📁 ', '').trim();
            const tache = cells[1].innerText.trim();
            const statut = cells[2].innerText.trim();
            const prio = cells[3].innerText.trim(); 
            const acteur = cells[4].innerText.replace('🧑‍💻 ', '').trim();
            const maj = cells[5].innerText.replace('🕒 ', '').trim();

            let notesText = '';
            const noteDivs = cells[6].querySelectorAll('.note-entry');
            if (noteDivs.length > 0) {
                const noteLines = Array.from(noteDivs).map(div => div.innerText.trim());
                notesText = noteLines.join('\n');
            } else {
                notesText = cells[6].innerText.trim();
                if (notesText === 'Aucune note') notesText = '';
            }

            const excelRow = worksheet.addRow({
                projet: projet,
                tache: tache,
                statut: statut,
                prio: prio !== '-' ? prio : '',
                acteur: acteur !== '-' ? acteur : '',
                maj: maj,
                notes: notesText
            });

            excelRow.eachCell((cell, colNumber) => {
                cell.border = { top: {style:'thin'}, left: {style:'thin'}, bottom: {style:'thin'}, right: {style:'thin'} };
                cell.alignment = { vertical: 'top', wrapText: true };
                cell.font = { name: 'Calibri', size: 11 };
                
                if (colNumber === 6) {
                    cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF00B0F0' } };
                    cell.font = { color: { argb: 'FFFFFFFF' }, bold: true, name: 'Calibri', size: 11 };
                    cell.alignment = { vertical: 'top', horizontal: 'center' };
                }
                
                if ([1, 3, 4, 5].includes(colNumber)) {
                    cell.alignment = { vertical: 'top', horizontal: 'center' };
                }
            });
        }
    });

    worksheet.autoFilter = { from: 'A1', to: { row: 1, column: 7 } };

    const buffer = await workbook.xlsx.writeBuffer();
    const blob = new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `Suivi_Chantiers_${new Date().toISOString().split('T')[0]}.xlsx`;
    a.click();
    window.URL.revokeObjectURL(url);
}

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

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.list').forEach(listEl => {
        new Sortable(listEl, {
            group: 'kanban-board', animation: 200, ghostClass: 'sortable-ghost', delay: 100, delayOnTouchOnly: true,
            disabled: !window.IS_LOGGED_IN,
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

function openHistoryModal(task, column, index) {
    currentTaskRef = { column, index, task };
    
    document.getElementById('modal-title').innerText = task.titre;
    document.getElementById('modal-project').innerText = task.projet;
    document.getElementById('modal-acteur').innerText = task.acteur || 'Non assigné';
    document.getElementById('modal-code-projet').innerText = task.code_projet || '';
    document.getElementById('modal-code-projet-container').style.display = task.code_projet ? 'block' : 'none';
    document.getElementById('modal-itbm').innerText = task.code_itbm || '';
    document.getElementById('modal-itbm-container').style.display = task.code_itbm ? 'block' : 'none';
    
    const attContainer = document.getElementById('modal-attachments-container');
    attContainer.innerHTML = '';
    
    if (task.attachments && task.attachments.length > 0) {
        let attHtml = '<h4 style="font-size:14px; margin-bottom:10px; margin-top:20px; color:#172b4d;">Pièces jointes</h4>';
        attHtml += '<div style="display:flex; flex-wrap:wrap; gap:10px;">';
        
        task.attachments.forEach(att => {
            const ext = att.filename.split('.').pop().toLowerCase();
            const isImage = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'].includes(ext);
            const sizeKB = Math.round(att.size / 1024);
            
            if (isImage) {
                attHtml += `
                    <a href="${att.path}" target="_blank" style="display:block; text-decoration:none; border:1px solid #dfe1e6; border-radius:6px; padding:4px; background:#fff; width: 80px; text-align:center; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                        <img src="${att.path}" style="width:100%; height:50px; object-fit:cover; border-radius:4px; margin-bottom:4px;" alt="${att.original_name}">
                        <div style="font-size:9px; color:#5e6c84; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="${att.original_name}">${att.original_name}</div>
                    </a>
                `;
            } else {
                attHtml += `
                    <a href="${att.path}" target="_blank" style="display:flex; align-items:center; gap:8px; text-decoration:none; border:1px solid #dfe1e6; border-radius:6px; padding:8px 12px; background:#fff; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                        <span style="font-size:20px;">📄</span>
                        <div style="display:flex; flex-direction:column;">
                            <span style="color:var(--primary); font-weight:600; font-size:12px; max-width:120px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="${att.original_name}">${att.original_name}</span>
                            <span style="font-size:10px; color:#5e6c84;">${sizeKB} Ko</span>
                        </div>
                    </a>
                `;
            }
        });
        attHtml += '</div>';
        attContainer.innerHTML = attHtml;
        attContainer.style.display = 'block';
    } else {
        attContainer.style.display = 'none';
    }

    const tbody = document.getElementById('modal-table-body');
    tbody.innerHTML = '';
    
    const allNotes = getAllNotesAggregated(task);
    
    if (allNotes.length > 0) {
        allNotes.forEach(note => {
            const tr = document.createElement('tr');
            const badge = note.reunion ? `<span class="badge-reunion">${note.reunion}</span>` : '<span style="color:#aaa;">-</span>';
            const srcBadge = note.sourceName ? `<span class="note-target-badge">${note.sourceName}</span><br>` : '';
            tr.innerHTML = `<td>${note.date}</td><td>${badge}</td><td style="white-space: pre-wrap;">${srcBadge}${note.texte}</td>`;
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
    
    currentTaskRef.allNotes = getAllNotesAggregated(task);
    
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

    if (window.IS_LOGGED_IN) {
        cancelEditNote();
        document.getElementById('new-lot-titre').value = '';
        document.getElementById('new-lot-code').value = '';
    }
    
    const lotsContainer = document.getElementById('panel-lots-container');
    const targetSelect = document.getElementById('new-note-target');
    lotsContainer.innerHTML = '';
    if(targetSelect) targetSelect.innerHTML = '<option value="">🎯 Tâche principale</option>';

    if (task.lots && task.lots.length > 0) {
        task.lots.forEach(lot => {
            if(targetSelect) {
                const opt = document.createElement('option');
                opt.value = lot.id; opt.innerText = `📦 ${lot.titre}`;
                targetSelect.appendChild(opt);
            }

            const codeBadge = lot.code_itbm ? `<span class="lot-code">🎫 ${lot.code_itbm}</span>` : '';
            lotsContainer.innerHTML += `<div class="lot-card"><div class="lot-header"><span class="lot-title">${lot.titre}</span>${codeBadge}</div></div>`;
        });
    } else {
        lotsContainer.innerHTML = '<span style="font-size:13px; color:#888; font-style:italic;">Aucun lot créé pour le moment.</span>';
    }

    renderAttachmentsList(task);

    const listContainer = document.getElementById('panel-notes-list');
    listContainer.innerHTML = '';
    
    if (currentTaskRef.allNotes.length > 0) {
        currentTaskRef.allNotes.forEach(note => {
            const item = document.createElement('div');
            item.className = 'note-item';
            const badge = note.reunion ? `<span class="badge-reunion">${note.reunion}</span>` : '';
            const srcBadge = note.sourceName ? `<span class="note-target-badge">${note.sourceName}</span> ` : '';
            
            const editBtn = window.IS_LOGGED_IN ? `<button class="btn-edit-note" onclick="startEditNote(${note.timestamp})" title="Modifier cette note">✏️</button>` : '';

            item.innerHTML = `
                <div class="note-date" style="display:flex; justify-content:space-between; align-items:center;">
                    <div>🗓️ ${note.date} ${badge}</div>
                    ${editBtn}
                </div>
                <div style="white-space: pre-wrap;">${srcBadge}${note.texte}</div>
            `;
            listContainer.appendChild(item);
        });
    } else {
        listContainer.innerHTML = '<p style="font-size:14px; color:#888; font-style: italic;">Aucun historique de suivi.</p>';
    }
    document.getElementById('details-panel').classList.add('open');
}

function closePanel() { document.getElementById('details-panel').classList.remove('open'); }

function startEditNote(timestamp) {
    if (!window.IS_LOGGED_IN) return;
    const note = currentTaskRef.allNotes.find(n => n.timestamp === timestamp);
    if(!note) return;

    currentTaskRef.editingNoteTimestamp = timestamp;

    const parts = note.date.split('/');
    if(parts.length === 3) {
        document.getElementById('new-note-date').value = `${parts[2]}-${parts[1]}-${parts[0]}`;
    } else {
        document.getElementById('new-note-date').valueAsDate = new Date();
    }

    document.getElementById('new-note-reunion').value = note.reunion || '';
    document.getElementById('new-note-target').value = note.lotId || '';
    document.getElementById('new-note-text').value = note.texte || '';

    document.getElementById('note-form-title').innerText = "✏️ Modifier le point de suivi :";
    document.getElementById('btn-submit-note').innerText = "Mettre à jour la note";
    document.getElementById('btn-cancel-edit').style.display = "block";

    document.getElementById('note-form-title').scrollIntoView({ behavior: 'smooth', block: 'center' });
    document.getElementById('new-note-text').focus();
}

function cancelEditNote() {
    currentTaskRef.editingNoteTimestamp = null;
    document.getElementById('new-note-date').valueAsDate = new Date();
    document.getElementById('new-note-reunion').value = '';
    document.getElementById('new-note-target').value = '';
    document.getElementById('new-note-text').value = '';
    
    document.getElementById('note-form-title').innerText = "Saisir un point de suivi :";
    document.getElementById('btn-submit-note').innerText = "Enregistrer la note";
    document.getElementById('btn-cancel-edit').style.display = "none";
}

function submitNote() {
    const text = document.getElementById('new-note-text').value;
    const date = document.getElementById('new-note-date').value;
    const reunion = document.getElementById('new-note-reunion').value;
    const lotId = document.getElementById('new-note-target').value;
    if (!text.trim()) return;

    const isEdit = !!currentTaskRef.editingNoteTimestamp;
    const actionUrl = isEdit ? 'edit_note' : 'add_note';
    
    const payload = { 
        column: currentTaskRef.column, 
        index: currentTaskRef.index, 
        text: text, 
        date: date, 
        reunion: reunion, 
        lot_id: lotId 
    };

    if (isEdit) {
        payload.timestamp = currentTaskRef.editingNoteTimestamp;
    }

    fetch('api.php?action=' + actionUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
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

function submitLot() {
    const titre = document.getElementById('new-lot-titre').value;
    const code = document.getElementById('new-lot-code').value;
    if (!titre.trim()) return;

    fetch('api.php?action=add_lot', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ column: currentTaskRef.column, index: currentTaskRef.index, titre: titre, code: code })
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

function renderAttachmentsList(task) {
    const container = document.getElementById('panel-attachments-container');
    container.innerHTML = '';
    if (task.attachments && task.attachments.length > 0) {
        task.attachments.forEach(att => {
            const sizeKB = Math.round(att.size / 1024);
            const deleteBtn = window.IS_LOGGED_IN ? `<button onclick="deleteAttachment('${att.id}')" class="btn-edit-note" style="color:#de350b;" title="Supprimer">✖</button>` : '';

            container.innerHTML += `
                <div class="attachment-item">
                    <div style="display:flex; align-items:center; gap:10px; overflow:hidden;">
                        <span>📎</span>
                        <a href="${att.path}" target="_blank" title="${att.original_name}" style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">${att.original_name}</a>
                        <span style="color:#5e6c84; font-size:11px;">(${sizeKB} Ko)</span>
                    </div>
                    ${deleteBtn}
                </div>
            `;
        });
    } else {
        container.innerHTML = '<span style="font-size:13px; color:#888; font-style:italic;">Aucune pièce jointe pour le moment.</span>';
    }
}

function uploadAttachment() {
    const input = document.getElementById('new-attachment-file');
    if (!input.files || input.files.length === 0) return;
    
    const formData = new FormData();
    formData.append('file', input.files[0]);
    formData.append('column', currentTaskRef.column);
    formData.append('index', currentTaskRef.index);
    
    fetch('api.php?action=upload_attachment', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(resData => {
        if (resData.success) {
            currentTaskRef.task = resData.task;
            input.value = ''; 
            openAddNotePanel(); 
            loadBoard();
        } else {
            alert(resData.error || "Erreur lors de l'envoi.");
        }
    });
}

function deleteAttachment(attId) {
    if (!confirm('Voulez-vous vraiment supprimer ce fichier définitivement ?')) return;
    fetch('api.php?action=delete_attachment', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ column: currentTaskRef.column, index: currentTaskRef.index, attachment_id: attId })
    })
    .then(res => res.json())
    .then(resData => {
        if (resData.success) {
            currentTaskRef.task = resData.task;
            openAddNotePanel();
            loadBoard();
        } else {
            alert(resData.error || "Erreur lors de la suppression.");
        }
    });
}

document.addEventListener('DOMContentLoaded', loadBoard);
</script>
