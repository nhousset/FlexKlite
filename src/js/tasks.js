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

document.addEventListener('DOMContentLoaded', () => {
    const btnNote = document.getElementById('menu-add-note');
    const btnEdit = document.getElementById('menu-edit-task');
    if(btnNote) btnNote.addEventListener('click', (e) => { e.stopPropagation(); document.getElementById('context-menu').style.display = 'none'; openAddNotePanel(); });
    if(btnEdit) btnEdit.addEventListener('click', (e) => { e.stopPropagation(); document.getElementById('context-menu').style.display = 'none'; openEditTaskModal(); });
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
    lotsContainer.innerHTML = '';
    
    const targetSelect = document.getElementById('new-note-target');
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
            
            // Le bouton d'édition n'est inséré que si on est admin
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
    
    const payload = { column: currentTaskRef.column, index: currentTaskRef.index, text: text, date: date, reunion: reunion, lot_id: lotId };
    if (isEdit) { payload.timestamp = currentTaskRef.editingNoteTimestamp; }

    fetch('api.php?action=' + actionUrl, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) })
    .then(res => res.json())
    .then(resData => {
        if(resData.success) { currentTaskRef.task = resData.task; openAddNotePanel(); loadBoard(); }
    });
}

function submitLot() {
    const titre = document.getElementById('new-lot-titre').value;
    const code = document.getElementById('new-lot-code').value;
    if (!titre.trim()) return;

    fetch('api.php?action=add_lot', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ column: currentTaskRef.column, index: currentTaskRef.index, titre: titre, code: code }) })
    .then(res => res.json())
    .then(resData => {
        if(resData.success) { currentTaskRef.task = resData.task; openAddNotePanel(); loadBoard(); }
    });
}

function renderAttachmentsList(task) {
    const container = document.getElementById('panel-attachments-container');
    container.innerHTML = '';
    if (task.attachments && task.attachments.length > 0) {
        task.attachments.forEach(att => {
            const sizeKB = Math.round(att.size / 1024);
            
            // Le bouton de suppression n'est inséré que si on est admin
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
        } else { alert(resData.error || "Erreur lors de l'envoi."); }
    });
}

function deleteAttachment(attId) {
    if (!confirm('Voulez-vous vraiment supprimer ce fichier définitivement ?')) return;
    fetch('api.php?action=delete_attachment', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ column: currentTaskRef.column, index: currentTaskRef.index, attachment_id: attId })
    })
    .then(res => res.json())
    .then(resData => {
        if (resData.success) {
            currentTaskRef.task = resData.task;
            openAddNotePanel(); loadBoard();
        } else { alert(resData.error || "Erreur lors de la suppression."); }
    });
}
