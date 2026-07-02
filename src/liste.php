<div id="tab-list" class="tab-content">
    <div class="data-table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 12%;">
                        <div class="sortable-header" onclick="sortTable('projet', this)">Projet <span class="sort-icon"></span></div>
                        <select id="filter-projet" class="table-filter" onchange="applyFilters()">
                            <option value="">Tous</option>
                            <?php foreach($settings['projets'] as $p): ?>
                                <option value="<?= htmlspecialchars($p) ?>"><?= htmlspecialchars($p) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </th>
                    <th style="width: 20%;">
                        <div class="sortable-header" style="margin-top: 15px;" onclick="sortTable('titre', this)">Tâche <span class="sort-icon"></span></div>
                    </th>
                    <th style="width: 10%;">
                        <div class="sortable-header" onclick="sortTable('statut', this)">Statut <span class="sort-icon"></span></div>
                        <select id="filter-statut" class="table-filter" onchange="applyFilters()">
                            <option value="">Tous</option>
                            <option value="todo">À Faire</option>
                            <option value="in_progress">En Cours</option>
                            <option value="blocked">Bloqué</option>
                            <option value="done">Terminé</option>
                        </select>
                    </th>
                    <th style="width: 8%;">
                        <div class="sortable-header" onclick="sortTable('prio', this)">Priorité <span class="sort-icon"></span></div>
                        <select id="filter-prio" class="table-filter" onchange="applyFilters()">
                            <option value="">Toutes</option>
                            <?php foreach($settings['priorites'] as $p): ?>
                                <option value="<?= htmlspecialchars($p) ?>"><?= htmlspecialchars($p) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </th>
                    <th style="width: 12%;">
                        <div class="sortable-header" onclick="sortTable('acteur', this)">Acteur <span class="sort-icon"></span></div>
                        <select id="filter-acteur" class="table-filter" onchange="applyFilters()">
                            <option value="">Tous</option>
                            <?php foreach($settings['acteurs'] as $a): ?>
                                <option value="<?= htmlspecialchars($a) ?>"><?= htmlspecialchars($a) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </th>
                    <th style="width: 8%;">
                        <div class="sortable-header" style="margin-top: 15px;" onclick="sortTable('maj', this)">MAJ <span class="sort-icon"></span></div>
                    </th>
                    <th style="width: 30%;">
                        <div style="margin-top: 15px;">Dernières notes (Historique)</div>
                    </th>
                </tr>
            </thead>
            <tbody id="list-table-body"></tbody>
        </table>
    </div>
</div>
