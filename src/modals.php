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
                    <label>Projet / Couleur *</label>
                    <select name="projet" required>
                        <option value="">-- Sélectionner --</option>
                        <?php foreach($settings['projets'] as $p): 
                            $pName = is_array($p) ? $p['name'] : $p;
                        ?>
                            <option value="<?= htmlspecialchars($pName) ?>"><?= htmlspecialchars($pName) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if(!isset($settings['enable_code_projet']) || $settings['enable_code_projet']): ?>
                <div class="form-group">
                    <label>Code Projet</label>
                    <input type="text" name="code_projet" placeholder="Ex: PRJ-2026">
                </div>
                <div class="form-group">
                    <label>Lien du Code Projet (URL)</label>
                    <input type="url" name="link_code_projet" placeholder="https://...">
                </div>
                <?php endif; ?>
                <?php if(!isset($settings['enable_code_itbm']) || $settings['enable_code_itbm']): ?>
                <div class="form-group">
                    <label>Code ITBM</label>
                    <input type="text" name="code_itbm" placeholder="Ex: TSK0123456">
                </div>
                <div class="form-group">
                    <label>Lien du Code ITBM (URL)</label>
                    <input type="url" name="link_code_itbm" placeholder="https://...">
                </div>
                <?php endif; ?>
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
                <?php if(!isset($settings['enable_charge_jh']) || $settings['enable_charge_jh']): ?>
                <div class="form-group">
                    <label>Charge (JH)</label>
                    <input type="number" name="charge_jh" id="add_charge_jh" step="0.5" min="0" placeholder="Ex: 1.5">
                </div>
                <?php endif; ?>
                <div class="form-group">
                    <label>Tâche Prérequis</label>
                    <select name="prerequis" id="add_prerequis">
                        <option value="">-- Aucun --</option>
                    </select>
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
                    <label>Projet / Couleur *</label>
                    <select name="projet" id="edit_projet" required>
                        <option value="">-- Sélectionner --</option>
                        <?php foreach($settings['projets'] as $p): 
                            $pName = is_array($p) ? $p['name'] : $p;
                        ?>
                            <option value="<?= htmlspecialchars($pName) ?>"><?= htmlspecialchars($pName) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if(!isset($settings['enable_code_projet']) || $settings['enable_code_projet']): ?>
                <div class="form-group">
                    <label>Code Projet</label>
                    <input type="text" name="code_projet" id="edit_code_projet" placeholder="Ex: PRJ-2026">
                </div>
                <div class="form-group">
                    <label>Lien du Code Projet (URL)</label>
                    <input type="url" name="link_code_projet" id="edit_link_code_projet" placeholder="https://...">
                </div>
                <?php endif; ?>
                <?php if(!isset($settings['enable_code_itbm']) || $settings['enable_code_itbm']): ?>
                <div class="form-group">
                    <label>Code ITBM</label>
                    <input type="text" name="code_itbm" id="edit_code_itbm" placeholder="Ex: TSK0123456">
                </div>
                <div class="form-group">
                    <label>Lien du Code ITBM (URL)</label>
                    <input type="url" name="link_code_itbm" id="edit_link_code_itbm" placeholder="https://...">
                </div>
                <?php endif; ?>
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
                <?php if(!isset($settings['enable_charge_jh']) || $settings['enable_charge_jh']): ?>
                <div class="form-group">
                    <label>Charge (JH)</label>
                    <input type="number" name="charge_jh" id="edit_charge_jh" step="0.5" min="0" placeholder="Ex: 1.5">
                </div>
                <?php endif; ?>
                <div class="form-group">
                    <label>Tâche Prérequis</label>
                    <select name="prerequis" id="edit_prerequis">
                        <option value="">-- Aucun --</option>
                    </select>
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
    <div class="context-menu-item" id="menu-archive-task" style="color: #de350b;">🗄️ Archiver la tâche</div>
</div>

<!-- Modale d'historique (Vue Rapide + Affichage des Pièces jointes) -->
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
                <?php if($is_logged_in): ?>
                    <button class="btn-modal-add" onclick="switchToAddNote()">➕ Ajouter un point</button>
                <?php endif; ?>
                <div class="close-panel" onclick="closeModal(event)">×</div>
            </div>
        </div>
        
        <h3 id="modal-title" style="margin-top: 0; color: #091e42; font-size: 20px; margin-bottom: 20px;"></h3>
        
        <div class="task-meta-info">
            <div>Projet : <strong id="modal-project"></strong></div>
            <div id="modal-code-projet-container" style="display:none;">Code Projet : <strong id="modal-code-projet"></strong></div>
            <div id="modal-itbm-container" style="display:none;">ITBM : <strong id="modal-itbm"></strong></div>
            <div>Acteur : <strong id="modal-acteur"></strong></div>
            <div id="modal-charge-container" style="display:none;">Charge : <strong id="modal-charge"></strong></div>
            <div id="modal-prerequis-container" style="display:none;">Prérequis : <strong id="modal-prerequis"></strong></div>
        </div>

        <!-- ZONE DES PIÈCES JOINTES EN MODE LECTURE SEULE -->
        <div id="modal-attachments-container" style="margin-bottom: 20px;"></div>
        
        <table class="notes-table">
            <thead>
                <tr><th style="width: 120px;">Date</th><th style="width: 150px;">Contexte</th><th>Détails du suivi</th></tr>
            </thead>
            <tbody id="modal-table-body"></tbody>
        </table>
    </div>
</div>

<!-- Panneau latéral ajout de note ET GESTION DES LOTS / PIÈCES JOINTES -->
<div id="details-panel">
    <div class="panel-header-container">
        <h2 class="panel-header-title">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
            </svg>
            Détails & Suivi
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
        <div id="panel-charge-container" style="display:none;">Charge : <strong id="panel-charge"></strong></div>
        <div id="panel-prerequis-container" style="display:none;">Prérequis : <strong id="panel-prerequis"></strong></div>
    </div>
    
    <h4 style="margin-bottom: 10px; font-size:15px; color: #172b4d;">Lots / Sous-tâches :</h4>
    <div id="panel-lots-container" style="margin-bottom: 15px;"></div>
    
    <?php if($is_logged_in): ?>
        <div style="background: #fafbfc; border: 1px dashed #dfe1e6; padding: 15px; border-radius: 8px; margin-bottom: 25px;">
            <h5 style="margin: 0 0 10px 0; color: #5e6c84; font-size: 13px;">+ Déclarer un nouveau Lot</h5>
            <div style="display: flex; gap: 10px;">
                <input type="text" id="new-lot-titre" placeholder="Intitulé du lot (ex: Front-end)" style="flex:2; padding: 8px; border: 1px solid #dfe1e6; border-radius: 4px; font-size:13px;">
                <input type="text" id="new-lot-code" placeholder="Code (ex: TSK123)" style="flex:1; padding: 8px; border: 1px solid #dfe1e6; border-radius: 4px; font-size:13px;">
                <button class="btn" style="padding: 8px 15px; font-size: 13px;" onclick="submitLot()">Ajouter</button>
            </div>
        </div>
    <?php endif; ?>

    <h4 style="margin-bottom: 10px; font-size:15px; color: #172b4d; border-top: 2px solid #ebecf0; padding-top:20px;">Pièces Jointes :</h4>
    <div id="panel-attachments-container" style="margin-bottom: 15px;"></div>
    
    <?php if($is_logged_in): ?>
        <div style="background: #fafbfc; border: 1px dashed #dfe1e6; padding: 10px; border-radius: 8px; margin-bottom: 25px; display:flex; gap:10px; align-items:center;">
            <input type="file" id="new-attachment-file" style="flex:1; font-size:13px;">
            <button class="btn" style="padding: 6px 15px; font-size: 13px;" onclick="uploadAttachment()">Ajouter le fichier</button>
        </div>
        
        <h4 id="note-form-title" style="margin-bottom: 10px; border-top: 2px solid #ebecf0; padding-top:20px; font-size:15px; color: #172b4d;">Saisir un point de suivi :</h4>
        <div class="note-meta-inputs" style="flex-wrap: wrap;">
            <select id="new-note-target" style="flex: 1; min-width: 150px; background: #e3f2fd; font-weight: 600;">
                <option value="">🎯 Tâche principale</option>
            </select>
            <input type="date" id="new-note-date" title="Date de la note" style="max-width: 130px;">
            <select id="new-note-reunion" style="flex: 1;">
                <option value="">-- Contexte / Réunion --</option>
                <?php foreach($settings['reunions'] as $r): ?>
                    <option value="<?= htmlspecialchars($r) ?>"><?= htmlspecialchars($r) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <textarea id="new-note-text" style="width:100%; height:120px; margin-bottom:12px; padding: 10px; border: 1px solid #dfe1e6; border-radius: 4px; font-family:inherit; box-sizing: border-box; resize: vertical;"></textarea>
        
        <div style="display:flex; gap:10px;">
            <button id="btn-submit-note" class="btn" style="flex: 1; padding: 12px; font-size: 15px;" onclick="submitNote()">Enregistrer la note</button>
            <button id="btn-cancel-edit" class="btn" style="display: none; background: #ebecf0; color: #42526e; padding: 12px; font-size: 15px;" onclick="cancelEditNote()">Annuler</button>
        </div>
    <?php endif; ?>

    <h4 style="margin-top: 40px; font-size:15px; color: #172b4d; border-bottom: 2px solid #ebecf0; padding-bottom: 10px;">Historique global (Tâche + Lots)</h4>
    <div id="panel-notes-list"></div>
</div>

<!-- Modale À Propos -->
<div id="about-modal" class="modal-overlay" onclick="closeAboutModal(event)">
    <div class="modal-content" onclick="event.stopPropagation()" style="max-width: 500px; text-align: center; padding: 30px;">
        <div class="close-panel" onclick="closeAboutModal(event)" style="position: absolute; top: 15px; right: 15px;">×</div>
        
        <?php
        $modal_logo = (!empty($settings['app_logo']) && file_exists(__DIR__ . '/' . $settings['app_logo'])) ? htmlspecialchars($settings['app_logo']) : 'img/logo.png';
        ?>
        <div style="margin-bottom: 20px;">
            <img src="<?= $modal_logo ?>?t=<?= time() ?>" alt="Logo FlexKlite" style="max-height: 130px; object-fit: contain;">
        </div>
        <p style="color: #5e6c84; font-size: 14px; margin-bottom: 20px;">
            <?= nl2br(htmlspecialchars($about_data['description'] ?? '')) ?>
        </p>
        
        <div style="background: #f4f5f7; padding: 15px; border-radius: 8px; text-align: left; font-size: 13px; color: #172b4d;">
            <div style="margin-bottom: 8px;"><strong>👤 Auteur :</strong> <?= htmlspecialchars($about_data['author'] ?? '') ?></div>
            <div style="margin-bottom: 8px;"><strong>✉️ Contact :</strong> <a href="mailto:<?= htmlspecialchars($about_data['contact'] ?? '') ?>" style="color: var(--primary);"><?= htmlspecialchars($about_data['contact'] ?? '') ?></a></div>
            <?php if (!empty($about_data['github'])): ?>
            <div style="margin-bottom: 8px;"><strong>🔗 GitHub :</strong> <a href="<?= htmlspecialchars($about_data['github']) ?>" target="_blank" style="color: var(--primary);"><?= htmlspecialchars($about_data['github']) ?></a></div>
            <?php endif; ?>
            <div style="margin-top: 15px; border-top: 1px solid #dfe1e6; padding-top: 10px;">
                <strong>🏷️ Version / Build :</strong> <?= $compilation_date ?>
            </div>
        </div>
        
        <button class="btn" style="margin-top: 25px; padding: 10px 30px; background: #0052cc;" onclick="closeAboutModal(event)">Fermer</button>
    </div>
</div>

<!-- Modale Login Requis -->
<div id="login-required-modal" class="modal-overlay" onclick="closeLoginRequiredModal(event)">
    <div class="modal-content" onclick="event.stopPropagation()" style="max-width: 400px; text-align: center; padding: 30px;">
        <div class="close-panel" onclick="closeLoginRequiredModal(event)" style="position: absolute; top: 15px; right: 15px;">×</div>
        
        <div style="font-size: 40px; margin-bottom: 15px;">🔒</div>
        <h2 style="color: #091e42; margin-top: 0; margin-bottom: 15px; font-size: 20px;">
            Mode lecture seule
        </h2>
        <p style="color: #5e6c84; font-size: 14px; margin-bottom: 25px; line-height: 1.5;">
            Vous devez être connecté en tant qu'administrateur pour modifier ou déplacer des tâches.
        </p>
        
        <div style="display: flex; gap: 10px; justify-content: center;">
            <button class="btn" style="background: #ebecf0; color: #42526e; padding: 10px 20px;" onclick="closeLoginRequiredModal(event)">Annuler</button>
            <a href="login.php" class="btn" style="background: #0052cc; padding: 10px 20px; text-decoration: none; color: white;">Se connecter</a>
        </div>
    </div>
</div>

<!-- Modale de Confirmation d'Archivage -->
<div id="archive-confirm-modal" class="modal-overlay" onclick="closeArchiveConfirmModal(event)">
    <div class="modal-content" onclick="event.stopPropagation()" style="max-width: 450px; text-align: center; padding: 30px;">
        <div class="close-panel" onclick="closeArchiveConfirmModal(event)" style="position: absolute; top: 15px; right: 15px;">×</div>
        
        <div style="font-size: 40px; margin-bottom: 15px;">🗄️</div>
        <h2 style="color: var(--text-main); margin-top: 0; margin-bottom: 15px; font-size: 20px;">
            Archiver la tâche
        </h2>
        <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 25px; line-height: 1.5;">
            Voulez-vous vraiment archiver cette tâche ? Elle sera déplacée vers l'onglet Archives.
        </p>
        
        <div style="display: flex; gap: 10px; justify-content: center;">
            <button class="btn" style="background: #ebecf0; color: #42526e; padding: 10px 20px;" onclick="closeArchiveConfirmModal(event)">Annuler</button>
            <button class="btn" style="background: var(--primary); padding: 10px 20px; color: white;" onclick="confirmArchiveTask()">Archiver</button>
        </div>
    </div>
</div>
