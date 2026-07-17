<div id="tab-list" class="tab-content">
    
    <div style="display: flex; justify-content: flex-end; margin-bottom: 15px;">
        <button class="btn" style="background: #107c41; box-shadow: 0 4px 10px rgba(16, 124, 65, 0.2);" onclick="exportToExcel()">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
                <line x1="8" y1="13" x2="16" y2="13"></line>
                <line x1="8" y1="17" x2="16" y2="17"></line>
                <polyline points="10 9 9 9 8 9"></polyline>
            </svg>
            Exporter sous Excel
        </button>
    </div>

    <div class="data-table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 12%;">
                        <div class="sortable-header" onclick="sortTable('projet', this)">Projet <span class="sort-icon"></span></div>
                    </th>
                    <th style="width: 20%;">
                        <div class="sortable-header" style="margin-top: 15px;" onclick="sortTable('titre', this)">Tâche <span class="sort-icon"></span></div>
                    </th>
                    <th style="width: 10%;">
                        <div class="sortable-header" onclick="sortTable('statut', this)">Statut <span class="sort-icon"></span></div>
                    </th>
                    <th style="width: 8%;">
                        <div class="sortable-header" onclick="sortTable('prio', this)">Priorité <span class="sort-icon"></span></div>
                    </th>
                    <th style="width: 12%;">
                        <div class="sortable-header" onclick="sortTable('acteur', this)">Acteur <span class="sort-icon"></span></div>
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
