<div id="tab-list" class="tab-content">
    <div class="data-table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 15%;">
                        <div>Projet</div>
                        <select id="filter-projet" class="table-filter" onchange="applyFilters()">
                            <option value="">Tous</option>
                            <?php foreach($settings['projets'] as $p): ?>
                                <option value="<?= htmlspecialchars($p) ?>"><?= htmlspecialchars($p) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </th>
                    <th style="width: 30%;">
                        <div style="margin-top: 15px;">Tâche</div>
                    </th>
                    <th style="width: 12%;">
                        <div>Statut</div>
                        <select id="filter-statut" class="table-filter" onchange="applyFilters()">
                            <option value="">Tous</option>
                            <option value="todo">À Faire</option>
                            <option value="in_progress">En Cours</option>
                            <option value="blocked">Bloqué</option>
                            <option value="done">Terminé</option>
                        </select>
                    </th>
                    <th style="width: 10%;">
                        <div>Priorité</div>
                        <select id="filter-prio" class="table-filter" onchange="applyFilters()">
                            <option value="">Toutes</option>
                            <?php foreach($settings['priorites'] as $p): ?>
                                <option value="<?= htmlspecialchars($p) ?>"><?= htmlspecialchars($p) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </th>
                    <th style="width: 13%;">
                        <div>Acteur</div>
                        <select id="filter-acteur" class="table-filter" onchange="applyFilters()">
                            <option value="">Tous</option>
                            <?php foreach($settings['acteurs'] as $a): ?>
                                <option value="<?= htmlspecialchars($a) ?>"><?= htmlspecialchars($a) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </th>
                    <th style="width: 10%;">
                        <div style="margin-top: 15px;">Échéance</div>
                    </th>
                    <th style="width: 10%;">
                        <div style="margin-top: 15px;">Dernière MAJ</div>
                    </th>
                </tr>
            </thead>
            <tbody id="list-table-body"></tbody>
        </table>
    </div>
</div>
