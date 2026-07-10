// --- ÉTAT GLOBAL DE L'APPLICATION ---
const statusLabels = { todo: 'À Faire', in_progress: 'En Cours', blocked: 'Bloqué / En attente', done: 'Terminé' };
let currentTaskRef = { column: null, index: null, task: null, allNotes: [], editingNoteTimestamp: null };
let currentSort = { column: '', asc: true };

// --- HELPERS DONNÉES ---
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

// --- CONTRÔLEURS UI BASIQUES ---
function toggleHeaderMenu(e) {
    e.stopPropagation();
    document.getElementById('header-dropdown').classList.toggle('show');
}

document.addEventListener('click', () => { 
    const dropdown = document.getElementById('header-dropdown');
    if(dropdown && dropdown.classList.contains('show')) dropdown.classList.remove('show');
    const ctxMenu = document.getElementById('context-menu');
    if(ctxMenu) ctxMenu.style.display = 'none'; 
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

// --- GESTION DU TRI DYNAMIQUE ---
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
        let valA = a.dataset[column] || ''; let valB = b.dataset[column] || '';
        if (column === 'prio') {
            valA = valA === '' ? '999' : valA; valB = valB === '' ? '999' : valB;
            const numA = parseInt(valA); const numB = parseInt(valB);
            if(!isNaN(numA) && !isNaN(numB)) return isAsc ? numA - numB : numB - numA;
        }
        if (valA < valB) return isAsc ? -1 : 1;
        if (valA > valB) return isAsc ? 1 : -1;
        return 0;
    });
    rows.forEach(row => tbody.appendChild(row));
}

// --- FILTRES ---
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

// --- EXPORT EXCEL ---
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
                projet: projet, tache: tache, statut: statut, prio: prio !== '-' ? prio : '',
                acteur: acteur !== '-' ? acteur : '', maj: maj, notes: notesText
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
                if ([1, 3, 4, 5].includes(colNumber)) { cell.alignment = { vertical: 'top', horizontal: 'center' }; }
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
