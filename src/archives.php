<div id="tab-archives" class="tab-content">
    <div class="data-table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th class="sortable-header" onclick="handleSort('projet')">Projet <span class="sort-icon" id="sort-icon-projet"></span></th>
                    <th class="sortable-header" onclick="handleSort('titre')">Titre <span class="sort-icon" id="sort-icon-titre"></span></th>
                    <th class="sortable-header" onclick="handleSort('statut')">Ancien Statut <span class="sort-icon" id="sort-icon-statut"></span></th>
                    <th class="sortable-header" onclick="handleSort('prio')">Priorité <span class="sort-icon" id="sort-icon-prio"></span></th>
                    <th class="sortable-header" onclick="handleSort('acteur')">Acteur <span class="sort-icon" id="sort-icon-acteur"></span></th>
                    <th class="sortable-header" onclick="handleSort('maj')">Date M.A.J <span class="sort-icon" id="sort-icon-maj"></span></th>
                    <th>Derniers Échanges</th>
                </tr>
            </thead>
            <tbody id="archive-table-body">
                <!-- Peuplé dynamiquement par JS -->
            </tbody>
        </table>
    </div>
</div>
