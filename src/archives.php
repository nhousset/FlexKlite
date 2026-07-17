<div id="tab-archives" class="tab-content">
    <div class="data-table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 12%;">
                        <div class="sortable-header" onclick="sortTable('projet', this)">Projet <span class="sort-icon"></span></div>
                    </th>
                    <th style="width: 20%;">
                        <div class="sortable-header" style="margin-top: 15px;" onclick="sortTable('titre', this)">Titre <span class="sort-icon"></span></div>
                    </th>
                    <th style="width: 10%;">
                        <div class="sortable-header" onclick="sortTable('statut', this)">Ancien Statut <span class="sort-icon"></span></div>
                    </th>
                    <th style="width: 8%;">
                        <div class="sortable-header" onclick="sortTable('prio', this)">Priorité <span class="sort-icon"></span></div>
                    </th>
                    <th style="width: 12%;">
                        <div class="sortable-header" onclick="sortTable('acteur', this)">Acteur <span class="sort-icon"></span></div>
                    </th>
                    <th style="width: 8%;">
                        <div class="sortable-header" style="margin-top: 15px;" onclick="sortTable('maj', this)">Date M.A.J <span class="sort-icon"></span></div>
                    </th>
                    <th style="width: 30%;">
                        <div style="margin-top: 15px;">Derniers Échanges</div>
                    </th>
                </tr>
            </thead>
            <tbody id="archive-table-body">
                <!-- Peuplé dynamiquement par JS -->
            </tbody>
        </table>
    </div>
</div>
