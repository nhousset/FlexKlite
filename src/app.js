let currentTaskRef = { column: null, index: null, task: null, allNotes: [], editingNoteTimestamp: null };
const statusLabels = { todo: 'À Faire', in_progress: 'En Cours', blocked: 'Bloqué / En attente', done: 'Terminé' };

// Utilitaire pour convertir une couleur Hex (#RRGGBB) en RGBA pâle
function hexToPale(hex) {
    if (!hex || !hex.startsWith('#')) return '#ffffff';
    let r = 0, g = 0, b = 0;
    if (hex.length === 4) {
        r = parseInt(hex[1] + hex[1], 16);
        g = parseInt(hex[2] + hex[2], 16);
        b = parseInt(hex[3] + hex[3], 16);
    } else if (hex.length === 7) {
        r = parseInt(hex.slice(1, 3), 16);
        g = parseInt(hex.slice(3, 5), 16);
        b = parseInt(hex.slice(5, 7), 16);
    }
    // Opacité fixée à 8% (0.08) pour un fond pastel lisible
    return `rgba(${r}, ${g}, ${b}, 0.08)`;
}

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

function toggleHeaderMenu(e, menuId = 'header-dropdown') {
    e.stopPropagation();
    document.querySelectorAll('.dropdown-menu').forEach(el => {
        if(el.id !== menuId) el.classList.remove('show');
    });
    const menu = document.getElementById(menuId);
    if (menu) menu.classList.toggle('show');
}

document.addEventListener('click', () => { 
    document.querySelectorAll('.dropdown-menu').forEach(el => el.classList.remove('show'));
    const ctxMenu = document.getElementById('context-menu');
    if (ctxMenu) ctxMenu.style.display = 'none'; 
});

function switchTab(tabId, btn) {
    document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
    document.getElementById(tabId).classList.add('active');
    btn.classList.add('active');
    
    if (tabId === 'tab-gantt') {
        if (typeof renderGantt === 'function') renderGantt();
    }
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

let boardData = null;

function initFilters() {
    const saved = localStorage.getItem('flexklite_filters');
    if (saved) {
        try {
            const f = JSON.parse(saved);
            if (document.getElementById('filter-search')) document.getElementById('filter-search').value = f.search || '';
            if (document.getElementById('filter-projet')) document.getElementById('filter-projet').value = f.projet || '';
            if (document.getElementById('filter-statut')) document.getElementById('filter-statut').value = f.statut || '';
            if (document.getElementById('filter-prio')) document.getElementById('filter-prio').value = f.prio || '';
            if (document.getElementById('filter-acteur')) document.getElementById('filter-acteur').value = f.acteur || '';
            if (document.getElementById('compact-mode')) document.getElementById('compact-mode').checked = !!f.compact;
        } catch(e) {}
    }
}

function saveFilters() {
    const f = {
        search: document.getElementById('filter-search') ? document.getElementById('filter-search').value : '',
        projet: document.getElementById('filter-projet') ? document.getElementById('filter-projet').value : '',
        statut: document.getElementById('filter-statut') ? document.getElementById('filter-statut').value : '',
        prio: document.getElementById('filter-prio') ? document.getElementById('filter-prio').value : '',
        acteur: document.getElementById('filter-acteur') ? document.getElementById('filter-acteur').value : '',
        compact: document.getElementById('compact-mode') ? document.getElementById('compact-mode').checked : false
    };
    localStorage.setItem('flexklite_filters', JSON.stringify(f));
}

let lastCompactState = false;

function handleFiltersChange() {
    saveFilters();
    const isCompact = document.getElementById('compact-mode') ? document.getElementById('compact-mode').checked : false;
    if (isCompact !== lastCompactState) {
        lastCompactState = isCompact;
        renderBoard(); // Re-render entirely to regroup by project
    } else {
        applyFilters(); // Just hide/show items
    }
}

function applyFilters() {
    saveFilters();
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
        const text = item.dataset.search || '';
        const p = item.dataset.projet || '';
        const s = item.dataset.statut || '';
        const pr = item.dataset.prio || '';
        const a = item.dataset.acteur || '';

        let matchSearch = search === '' || text.includes(search);
        let matchProjet = projet === '' || p === projet;
        let matchStatut = statut === '' || s === statut;
        let matchPrio = prio === '' || pr === prio;
        let matchActeur = acteur === '' || a === acteur;

        if (matchSearch && matchProjet && matchStatut && matchPrio && matchActeur) {
            item.style.display = '';
        } else {
            item.style.display = 'none';
        }
    });

    // Hide empty compact project headers
    document.querySelectorAll('.compact-project-header').forEach(header => {
        const nextCards = [];
        let el = header.nextElementSibling;
        while (el && el.classList.contains('card')) {
            nextCards.push(el);
            el = el.nextElementSibling;
        }
        const hasVisibleCard = nextCards.some(card => card.style.display !== 'none');
        header.style.display = hasVisibleCard ? '' : 'none';
    });
}

function loadBoard() {
    fetch('api.php?action=get&_t=' + Date.now())
        .then(async res => {
            const text = await res.text();
            try {
                return JSON.parse(text);
            } catch (e) {
                alert("Erreur de format de données. Voici ce que le serveur a renvoyé :\n\n" + text.substring(0, 500));
                throw e;
            }
        })
        .then(data => {
            boardData = data;
            renderBoard();
            if (typeof renderGantt === 'function') renderGantt();
            
            // MASQUER LE LOADER UNE FOIS LE RENDU TERMINÉ
            const loader = document.getElementById('loading-overlay');
            if (loader) {
                loader.style.opacity = '0';
                setTimeout(() => loader.style.display = 'none', 400); // 400ms pour laisser la transition CSS s'exécuter
            }
        })
        .catch(err => {
            console.error("Erreur lors du chargement des données :", err);
            const loader = document.getElementById('loading-overlay');
            if (loader) {
                loader.style.display = 'none';
            }
        });
}

function renderBoard() {
    if (!boardData) return;
    const data = boardData;
    const isCompact = document.getElementById('compact-mode') ? document.getElementById('compact-mode').checked : false;
    lastCompactState = isCompact;

    if (window.sortableInstances) {
        window.sortableInstances.forEach(inst => inst.option('disabled', isCompact));
    }

    const listTableBody = document.getElementById('list-table-body');
    if(listTableBody) listTableBody.innerHTML = '';
    
    let kpi = { total: 0, status: { todo: 0, in_progress: 0, blocked: 0, done: 0 }, acteur: {}, prio: {} };
    let allNotesForActivity = [];

    Object.keys(data).forEach(status => {
        if (status === 'archives') return;
        
        const container = document.querySelector(`[data-status="${status}"]`);
        if(container) container.innerHTML = ''; 
        
        let currentProjectHeader = null;
        
        let tasksToRender = data[status].map((t, i) => ({ task: t, originalIndex: i }));
        if (isCompact) {
            tasksToRender.sort((a, b) => (a.task.projet || '').localeCompare(b.task.projet || ''));
        }

        tasksToRender.forEach(({task, originalIndex: index}) => {
            const searchableText = `${task.titre} ${task.projet} ${task.code_projet||''} ${task.code_itbm||''}`.toLowerCase();
            const pAttr = task.projet || '';
            const aAttr = task.acteur || '';
            const prAttr = task.prio || '';
            
            const projColor = window.PROJECT_COLORS && window.PROJECT_COLORS[pAttr] ? window.PROJECT_COLORS[pAttr] : '#dfe1e6';
            const paleColor = hexToPale(projColor);

            // 1. VUE KANBAN
            if (isCompact && pAttr !== currentProjectHeader && container) {
                currentProjectHeader = pAttr;
                const header = document.createElement('div');
                header.className = 'compact-project-header';
                header.style.cssText = `margin-top: 10px; padding: 4px 8px; font-weight: bold; font-size: 12px; color: ${projColor}; border-bottom: 2px solid ${projColor};`;
                header.innerText = `📁 ${pAttr || 'Sans Projet'}`;
                container.appendChild(header);
            }

            const card = document.createElement('div');
            card.className = `card filter-item`;
            card.style.borderTop = `4px solid ${projColor}`;
            card.style.backgroundColor = paleColor; 
            if (isCompact) {
                card.style.padding = '8px 12px';
                card.style.marginBottom = '4px';
            }
            
            card.dataset.index = index;
            card.dataset.search = searchableText;
            card.dataset.projet = pAttr;
            card.dataset.acteur = aAttr;
            card.dataset.statut = status;
            card.dataset.prio = prAttr;
            
            card.addEventListener('click', () => openHistoryModal(task, status, index));
            card.addEventListener('contextmenu', (e) => { 
                e.preventDefault(); 
                if (window.IS_LOGGED_IN) {
                    showContextMenu(e, status, index, task); 
                } else {
                    showLoginRequiredModal(e);
                }
            });
            
            let extraTags = '';
            if(window.ENABLE_CHARGE_JH !== false && task.charge_jh) extraTags += `<span class="tag" style="background:#e3f2fd; color:#0d47a1; border-color:#90caf9;">⏱️ ${task.charge_jh} JH</span>`;
            if(task.prerequis) extraTags += `<span class="tag" style="background:#ffebee; color:#b71c1c; border-color:#ef9a9a;" title="Prérequis">🔗 ${task.prerequis}</span>`;
            if(task.code_itbm) extraTags += `<span class="tag tag-itbm">🎫 ${task.code_itbm}</span>`;
            if(task.prio) extraTags += `<span class="tag tag-prio" title="Priorité">${task.prio}</span>`;
            if(task.lots && task.lots.length > 0) extraTags += `<span class="tag" style="background:#e8f5e9; color:#006644; border-color:#b7eb8f;">📦 ${task.lots.length} Lot(s)</span>`;
            if(task.attachments && task.attachments.length > 0) extraTags += `<span class="tag" style="background:#fff3e0; color:#e65100; border-color:#ffcc80;">📎 ${task.attachments.length} Fichier(s)</span>`;

            if (isCompact) {
                card.innerHTML = `<div class="card-title" style="font-size: 13px; margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="${task.titre}">${task.titre}</div>`;
            } else {
                card.innerHTML = `
                    <div class="tags-container">
                        <span class="tag" style="border-left: 3px solid ${projColor}; background: rgba(255,255,255,0.7);">📁 ${task.projet}</span>${extraTags}
                    </div>
                    <div class="card-title">${task.titre}</div>
                    <div class="card-footer">
                        <span title="Assigné à">🧑‍💻 ${task.acteur || 'Non assigné'}</span>
                        <span title="Dernière mise à jour">🕒 ${task.maj}</span>
                    </div>
                `;
            }
            if(container) container.appendChild(card);

            // Construction du bloc des notes pour la liste (Excel) - Sans emoji ni fioriture
            let listNotesHtml = '';
            const allTaskNotes = getAllNotesAggregated(task);
            if (allTaskNotes.length > 0) {
                const top5 = allTaskNotes.slice(0, 5);
                listNotesHtml = top5.map(n => {
                    const ctx = n.reunion ? ` - ${n.reunion}` : '';
                    const srcBadge = n.sourceName ? `[${n.sourceName}] ` : '';
                    return `<div style="margin-bottom: 4px; padding-bottom: 4px; border-bottom: 1px solid #e0e0e0;">
                                <strong>${n.date}${ctx} :</strong> ${srcBadge}${n.texte}
                            </div>`;
                }).join('');
            } else {
                listNotesHtml = `<span style="color:#aaa; font-style:italic;">Aucune note</span>`;
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
                    e.preventDefault(); 
                    if (window.IS_LOGGED_IN) {
                        showContextMenu(e, status, index, task); 
                    } else {
                        showLoginRequiredModal(e);
                    }
                });
                
                const actLabel = task.acteur || '';
                const prioLabel = task.prio || '';
                
                tr.innerHTML = `
                    <td style="font-weight: bold; color: ${projColor}; background-color: ${paleColor};">${task.projet}</td>
                    <td>${task.titre}</td>
                    <td>${statusLabels[status]}</td>
                    <td style="color:#c62828;">${prioLabel}</td>
                    <td>${actLabel}</td>
                    <td style="white-space:nowrap;">${task.maj}</td>
                    <td>${listNotesHtml}</td>
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
                        taskTitle: task.titre, projet: task.projet, projColor: projColor, paleColor: paleColor,
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
    
    // 3. VUE ARCHIVES
    const archiveTableBody = document.getElementById('archive-table-body');
    if (archiveTableBody && data.archives) {
        archiveTableBody.innerHTML = '';
        data.archives.forEach((task, index) => {
            const searchableText = `${task.titre} ${task.projet} ${task.code_projet||''} ${task.code_itbm||''}`.toLowerCase();
            const pAttr = task.projet || '';
            const aAttr = task.acteur || '';
            const prAttr = task.prio || '';
            const projColor = window.PROJECT_COLORS && window.PROJECT_COLORS[pAttr] ? window.PROJECT_COLORS[pAttr] : '#dfe1e6';
            const paleColor = hexToPale(projColor);

            let listNotesHtml = '';
            const allTaskNotes = getAllNotesAggregated(task);
            if (allTaskNotes.length > 0) {
                const top5 = allTaskNotes.slice(0, 5);
                listNotesHtml = top5.map(n => {
                    const ctx = n.reunion ? ` - ${n.reunion}` : '';
                    const srcBadge = n.sourceName ? `[${n.sourceName}] ` : '';
                    return `<div style="margin-bottom: 4px; padding-bottom: 4px; border-bottom: 1px solid #e0e0e0;">
                                <strong>${n.date}${ctx} :</strong> ${srcBadge}${n.texte}
                            </div>`;
                }).join('');
            } else {
                listNotesHtml = `<span style="color:#aaa; font-style:italic;">Aucune note</span>`;
            }

            const tr = document.createElement('tr');
            tr.className = 'filter-item';
            tr.dataset.search = searchableText;
            tr.dataset.projet = pAttr;
            tr.dataset.acteur = aAttr;
            tr.dataset.statut = ''; 
            tr.dataset.prio = prAttr;
            tr.dataset.titre = task.titre.toLowerCase();
            const majParts = task.maj ? task.maj.split('/') : [];
            tr.dataset.maj = majParts.length === 2 ? `${majParts[1]}${majParts[0]}` : (task.maj || '');

            tr.addEventListener('click', () => openHistoryModal(task, 'archives', index));
            
            const actLabel = task.acteur || '';
            const prioLabel = task.prio || '';
            
            tr.innerHTML = `
                <td style="font-weight: bold; color: ${projColor}; background-color: ${paleColor};">${task.projet}</td>
                <td>${task.titre}</td>
                <td style="color:#888;">Archivée</td>
                <td style="color:#c62828;">${prioLabel}</td>
                <td>${actLabel}</td>
                <td style="white-space:nowrap;">${task.maj}</td>
                <td>${listNotesHtml}</td>
            `;
            archiveTableBody.appendChild(tr);
        });
    }

    applyFilters();
    
    if(currentSort.column) {
        applySort(currentSort.column, currentSort.asc);
    }
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
    const compactMode = document.getElementById('compact-mode')?.checked || false;

    const search = searchEl ? searchEl.value.toLowerCase() : '';
    const projet = projetEl ? projetEl.value : '';
    const statut = statutEl ? statutEl.value : '';
    const prio = prioEl ? prioEl.value : '';
    const acteur = acteurEl ? acteurEl.value : '';

    localStorage.setItem('filters', JSON.stringify({search, projet, statut, prio, acteur, compactMode}));

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
            item.style.display = item.tagName === 'TR' ? (compactMode ? 'table-row' : 'table-row') : 'block';
            if (compactMode) item.classList.add('compact'); else item.classList.remove('compact');
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
                <span class="tag" style="font-size:10px; padding:2px 6px; border-left:3px solid ${note.projColor}; background:${note.paleColor};">📁 ${note.projet}</span>
                <span style="font-size:11px; color:#5e6c84;">${note.date}</span>
            </div>
            <div class="activity-task">${note.taskTitle}</div>
            <div class="activity-note">${badge} ${note.texte}</div>
        `;
        container.appendChild(item);
    });
}

function populatePrerequisSelect(selectId, excludeTitle = '') {
    const select = document.getElementById(selectId);
    if (!select) return;
    
    select.innerHTML = '<option value="">-- Aucun --</option>';
    if (!boardData) return;
    
    let allTasks = [];
    Object.keys(boardData).forEach(status => {
        if (status === 'archives') return;
        boardData[status].forEach(t => {
            if (t.titre && t.titre !== excludeTitle) {
                allTasks.push(t.titre);
            }
        });
    });
    
    allTasks.sort((a, b) => a.localeCompare(b));
    allTasks.forEach(titre => {
        const opt = document.createElement('option');
        opt.value = titre;
        opt.textContent = titre;
        select.appendChild(opt);
    });
}

function openAddTaskModal() { 
    populatePrerequisSelect('add_prerequis');
    document.getElementById('add-task-modal').style.display = 'flex'; 
}
function closeAddTaskModal(e) { if(e) e.stopPropagation(); document.getElementById('add-task-modal').style.display = 'none'; }

function openEditTaskModal() {
    const task = currentTaskRef.task;
    
    document.getElementById('edit_column').value = currentTaskRef.column;
    document.getElementById('edit_index').value = currentTaskRef.index;

    document.getElementById('edit_titre').value = task.titre || '';
    document.getElementById('edit_projet').value = task.projet || '';
    if(window.ENABLE_CODE_PROJET && document.getElementById('edit_code_projet')) {
        document.getElementById('edit_code_projet').value = task.code_projet || '';
        document.getElementById('edit_link_code_projet').value = task.link_code_projet || '';
    }
    if(window.ENABLE_CODE_ITBM && document.getElementById('edit_code_itbm')) {
        document.getElementById('edit_code_itbm').value = task.code_itbm || '';
        document.getElementById('edit_link_code_itbm').value = task.link_code_itbm || '';
    }
    document.getElementById('edit_acteur').value = task.acteur || '';
    document.getElementById('edit_prio').value = task.prio || '';
    document.getElementById('edit_date_debut').value = task.date_debut || '';
    document.getElementById('edit_date_fin').value = task.date_fin || '';
    
    const chargeInput = document.getElementById('edit_charge_jh');
    if (chargeInput) chargeInput.value = task.charge_jh || '';
    
    populatePrerequisSelect('edit_prerequis', task.titre);
    const prerequisSelect = document.getElementById('edit_prerequis');
    if (prerequisSelect) prerequisSelect.value = task.prerequis || '';

    document.getElementById('edit-task-modal').style.display = 'flex';
}
function closeEditTaskModal(e) { if(e) e.stopPropagation(); document.getElementById('edit-task-modal').style.display = 'none'; }

function openHistoryModal(task, column, index) {
    currentTaskRef = { column, index, task };
    
    document.getElementById('modal-title').innerText = task.titre;
    document.getElementById('modal-project').innerText = task.projet;
    document.getElementById('modal-acteur').innerText = task.acteur || 'Non assigné';
    if(window.ENABLE_CODE_PROJET && task.code_projet) {
        let text = task.code_projet;
        if(task.link_code_projet) text = `<a href="${task.link_code_projet}" target="_blank" style="color:var(--primary); text-decoration:underline;">${text}</a>`;
        document.getElementById('modal-code-projet').innerHTML = text;
        document.getElementById('modal-code-projet-container').style.display = 'block';
    } else {
        document.getElementById('modal-code-projet-container').style.display = 'none';
    }
    
    if(window.ENABLE_CODE_ITBM && task.code_itbm) {
        let text = task.code_itbm;
        if(task.link_code_itbm) text = `<a href="${task.link_code_itbm}" target="_blank" style="color:var(--primary); text-decoration:underline;">${text}</a>`;
        document.getElementById('modal-itbm').innerHTML = text;
        document.getElementById('modal-itbm-container').style.display = 'block';
    } else {
        document.getElementById('modal-itbm-container').style.display = 'none';
    }
    
    if (window.ENABLE_CHARGE_JH !== false && task.charge_jh) {
        document.getElementById('modal-charge').innerText = `${task.charge_jh} JH`;
        document.getElementById('modal-charge-container').style.display = 'block';
    } else {
        document.getElementById('modal-charge-container').style.display = 'none';
    }
    
    if (task.prerequis) {
        document.getElementById('modal-prerequis').innerText = task.prerequis;
        document.getElementById('modal-prerequis-container').style.display = 'block';
    } else {
        document.getElementById('modal-prerequis-container').style.display = 'none';
    }
    
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
function closeAboutModal(e) { if(e) e.stopPropagation(); document.getElementById('about-modal').style.display = 'none'; }

function openAboutModal(e) {
    if(e) e.preventDefault();
    document.getElementById('about-modal').style.display = 'flex';
}

function showLoginRequiredModal(e) {
    if(e) e.preventDefault();
    document.getElementById('login-required-modal').style.display = 'flex';
}

function closeLoginRequiredModal(e) {
    if(e) e.stopPropagation();
    document.getElementById('login-required-modal').style.display = 'none';
}

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
const archiveBtn = document.getElementById('menu-archive-task');
if (archiveBtn) {
    archiveBtn.addEventListener('click', (e) => {
        e.stopPropagation(); document.getElementById('context-menu').style.display = 'none'; archiveTask();
    });
}

function closeArchiveConfirmModal(e) {
    if(e) e.stopPropagation();
    document.getElementById('archive-confirm-modal').style.display = 'none';
}

function archiveTask() {
    document.getElementById('archive-confirm-modal').style.display = 'flex';
}

function confirmArchiveTask() {
    closeArchiveConfirmModal();
    fetch('api.php?action=archive_task', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ column: currentTaskRef.column, index: currentTaskRef.index })
    })
    .then(res => res.json())
    .then(resData => {
        if(resData.success) {
            loadBoard();
        } else {
            alert(resData.error || "Erreur lors de l'archivage.");
        }
    });
}

function openAddNotePanel() {
    const task = currentTaskRef.task;
    
    currentTaskRef.allNotes = getAllNotesAggregated(task);
    
    document.getElementById('panel-title').innerText = task.titre;
    document.getElementById('panel-project').innerText = task.projet;
    document.getElementById('panel-acteur').innerText = task.acteur || 'Non assigné';
    if(window.ENABLE_CODE_PROJET && task.code_projet) {
        let text = task.code_projet;
        if(task.link_code_projet) text = `<a href="${task.link_code_projet}" target="_blank" style="color:var(--primary); text-decoration:underline;">${text}</a>`;
        document.getElementById('panel-code-projet').innerHTML = text;
        document.getElementById('panel-code-projet-container').style.display = 'block';
    } else {
        document.getElementById('panel-code-projet-container').style.display = 'none';
    }
    
    if(window.ENABLE_CODE_ITBM && task.code_itbm) {
        let text = task.code_itbm;
        if(task.link_code_itbm) text = `<a href="${task.link_code_itbm}" target="_blank" style="color:var(--primary); text-decoration:underline;">${text}</a>`;
        document.getElementById('panel-itbm').innerHTML = text;
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
    
    if (task.charge_jh) {
        document.getElementById('panel-charge').innerText = `${task.charge_jh} JH`;
        document.getElementById('panel-charge-container').style.display = 'block';
    } else {
        document.getElementById('panel-charge-container').style.display = 'none';
    }
    
    if (task.prerequis) {
        document.getElementById('panel-prerequis').innerText = task.prerequis;
        document.getElementById('panel-prerequis-container').style.display = 'block';
    } else {
        document.getElementById('panel-prerequis-container').style.display = 'none';
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

// Initialisation sécurisée de l'application
function initApp() {
    initFilters(); // Restore filters from localStorage before loading the board
    window.sortableInstances = [];
    document.querySelectorAll('.list').forEach(listEl => {
        // Sécurité si le CDN de SortableJS met du temps à répondre ou est bloqué
        if (typeof Sortable !== 'undefined') {
            const inst = new Sortable(listEl, {
                group: 'kanban-board', 
                animation: 200, 
                ghostClass: 'sortable-ghost', 
                delay: 100, 
                delayOnTouchOnly: true,
                disabled: document.getElementById('compact-mode') && document.getElementById('compact-mode').checked,
                onEnd: function (evt) {
                    if (!window.IS_LOGGED_IN) {
                        showLoginRequiredModal();
                        renderBoard(); // Rétablit l'état initial du DOM
                        return;
                    }
                    const fromColumn = evt.from.dataset.status; 
                    const toColumn = evt.to.dataset.status;
                    const fromIndex = evt.oldIndex; 
                    const toIndex = evt.newIndex;
                    if (fromColumn === toColumn && fromIndex === toIndex) return;
                    fetch('api.php?action=move', {
                        method: 'POST', 
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ fromColumn, toColumn, fromIndex, toIndex })
                    }).then(() => loadBoard());
                }
            });
            window.sortableInstances.push(inst);
        } else {
            console.warn("SortableJS n'a pas pu être chargé depuis le CDN.");
        }
    });
    loadBoard();
}

// Lancement garanti au chargement du DOM
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initApp);
} else {
    initApp();
}

// --- GANTT VIEW LOGIC ---
let ganttInstance = null;

function renderGantt() {
    if (window.ENABLE_GANTT === false) return;
    if (!boardData) return;
    
    // Convert boardData to Frappe Gantt tasks
    const ganttTasks = [];
    
    const statusMap = {
        'todo': 0,
        'in_progress': 50,
        'blocked': 20,
        'done': 100
    };

    // Injection dynamique des couleurs de projets pour le Gantt
    let ganttStyle = document.getElementById('dynamic-gantt-styles');
    if (!ganttStyle) {
        ganttStyle = document.createElement('style');
        ganttStyle.id = 'dynamic-gantt-styles';
        document.head.appendChild(ganttStyle);
    }
    let cssRules = '';
    if (window.PROJECT_COLORS) {
        Object.keys(window.PROJECT_COLORS).forEach(proj => {
            const safeName = proj.replace(/[^a-zA-Z0-9]/g, '');
            const color = window.PROJECT_COLORS[proj];
            if (color) {
                const paleColor = typeof hexToPale === 'function' ? hexToPale(color) : '#b3d4ff';
                cssRules += `.gantt .bar-wrapper.gantt-proj-${safeName} .bar { fill: ${paleColor} !important; stroke: ${color} !important; }\n`;
                cssRules += `.gantt .bar-wrapper.gantt-proj-${safeName} .bar-progress { fill: ${color} !important; }\n`;
            }
        });
    }
    ganttStyle.innerHTML = cssRules;

    Object.keys(boardData).forEach(status => {
        if (status === 'archives') return;
        
        boardData[status].forEach((task, idx) => {
            // Gantt requires dates
            let start = task.date_debut;
            let end = task.date_fin;
            
            if (!start && !end) {
                // If both are missing, use today
                const today = new Date().toISOString().split('T')[0];
                start = today;
                end = today;
            } else if (!start) {
                start = end; // Fallback
            } else if (!end) {
                end = start; // Fallback
            }
            
            const dependencies = task.prerequis ? task.prerequis : '';
            const progress = statusMap[status] !== undefined ? statusMap[status] : 0;
            
            ganttTasks.push({
                id: task.titre, // Using title as ID since it's unique enough for dependencies here
                name: task.titre,
                start: start,
                end: end,
                progress: progress,
                dependencies: dependencies,
                custom_class: task.projet ? 'gantt-proj-' + task.projet.replace(/[^a-zA-Z0-9]/g, '') : '',
                // Save meta info for updating
                meta: { column: status, index: idx, charge_jh: task.charge_jh }
            });
        });
    });
    
    // Don't render if no tasks
    if (ganttTasks.length === 0) {
        document.getElementById('gantt').innerHTML = '<text x="10" y="20" fill="#172b4d">Aucune tâche disponible pour le Gantt.</text>';
        return;
    }

    document.getElementById('gantt').innerHTML = ''; // Clear SVG
    
    ganttInstance = new Gantt("#gantt", ganttTasks, {
        on_date_change: function(task, start, end) {
            // Convert back to YYYY-MM-DD
            const startStr = start.toISOString().split('T')[0];
            const endStr = end.toISOString().split('T')[0];
            
            if (!window.IS_LOGGED_IN) {
                showLoginRequiredModal();
                loadBoard(); // Revert visual change
                return;
            }
            
            fetch('api.php?action=update_task_dates', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    column: task.meta.column,
                    index: task.meta.index,
                    start: startStr,
                    end: endStr
                })
            }).then(res => res.json()).then(resData => {
                if (!resData.success) {
                    alert(resData.error || 'Erreur lors de la mise à jour des dates.');
                    loadBoard();
                } else {
                    loadBoard(); // Reload to sync Kanban and Gantt
                }
            });
        },
        on_click: function (task) {
            if (!window.IS_LOGGED_IN) {
                showLoginRequiredModal();
                return;
            }
            const realTask = boardData[task.meta.column][task.meta.index];
            currentTaskRef = { column: task.meta.column, index: task.meta.index, task: realTask };
            openEditTaskModal();
        },
        custom_popup_html: function(task) {
            return `
                <div class="details-container" style="padding: 12px; font-family: 'Calibri', sans-serif; min-width: 160px;">
                    <h5 style="margin: 0 0 8px 0; font-size: 14px; color: #172b4d;">${task.name}</h5>
                    <div style="margin: 0 0 4px 0; font-size: 12px; color: #0d47a1; background: #e3f2fd; padding: 2px 6px; border-radius: 4px; display: inline-block;">
                        ⏱️ ${task.meta.charge_jh || 0} JH
                    </div>
                    <p style="margin: 8px 0 0 0; font-size: 11px; color: #888; font-style: italic;">💡 Cliquez pour éditer</p>
                </div>
            `;
        },
        view_mode: 'Week',
        language: 'fr'
    });
    
    // Force active button
    document.querySelectorAll('.gantt-view-modes button').forEach(btn => {
        btn.style.background = '#ebecf0';
        btn.style.color = '#42526e';
    });
    const weekBtn = document.getElementById('btn-gantt-week');
    if (weekBtn) {
        weekBtn.style.background = 'var(--primary)';
        weekBtn.style.color = 'white';
    }
}

function changeGanttView(mode) {
    if (ganttInstance) {
        ganttInstance.change_view_mode(mode);
        
        document.querySelectorAll('.gantt-view-modes button').forEach(btn => {
            btn.style.background = '#ebecf0';
            btn.style.color = '#42526e';
        });
        
        const btnId = 'btn-gantt-' + mode.toLowerCase();
        const activeBtn = document.getElementById(btnId);
        if (activeBtn) {
            activeBtn.style.background = 'var(--primary)';
            activeBtn.style.color = 'white';
        }
    }
}
