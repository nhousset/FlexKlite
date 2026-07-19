<div id="tab-gantt" class="tab-content">
    <div style="background: white; border: 1px solid #dfe1e6; border-radius: 6px; padding: 15px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0; color: #172b4d;">Planification Temporelle</h3>
            <div class="gantt-view-modes" style="display: flex; gap: 10px;">
                <button class="btn" onclick="changeGanttView('Day')" id="btn-gantt-day" style="background: #ebecf0; color: #42526e; padding: 6px 12px;">Jour</button>
                <button class="btn" onclick="changeGanttView('Week')" id="btn-gantt-week" style="background: var(--primary); padding: 6px 12px;">Semaine</button>
                <button class="btn" onclick="changeGanttView('Month')" id="btn-gantt-month" style="background: #ebecf0; color: #42526e; padding: 6px 12px;">Mois</button>
            </div>
        </div>
        
        <div id="gantt-container" style="overflow-x: auto;">
            <svg id="gantt"></svg>
        </div>
    </div>
</div>

<style>
    /* Surcharge de quelques styles de base de Frappe Gantt pour coller au design de l'app */
    .gantt .bar-wrapper { cursor: pointer; }
    .gantt .bar-progress { fill: var(--primary); }
    .gantt .bar { fill: #b3d4ff; stroke: var(--primary); stroke-width: 1; }
    .gantt .bar-label { font-family: "Calibri", "Segoe UI", sans-serif; font-size: 12px; font-weight: bold; fill: #172b4d; }
    .gantt .grid-header { fill: #f4f5f7; }
    .gantt .grid-row { fill: #ffffff; }
    .gantt .grid-row:nth-child(even) { fill: #fafbfc; }
</style>
