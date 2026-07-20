<!-- Panneau latéral d'Aide -->
<div id="help-panel" class="side-panel-help">
    <div class="panel-header-container" style="position: sticky; top: -40px; background: var(--card-bg); padding-top: 40px; z-index: 10;">
        <h3 class="panel-header-title">❓ Aide & Documentation</h3>
        <div class="close-panel" onclick="closeHelpPanel()">×</div>
    </div>
    <div id="help-markdown-body" class="markdown-body">
        <p style="color: var(--text-muted);">Chargement de la documentation...</p>
    </div>
</div>

<div id="help-panel-overlay" class="modal-overlay" onclick="closeHelpPanel()" style="display: none; z-index: 4999;"></div>

<style>
.side-panel-help {
    position: fixed;
    right: -100vw;
    top: 0;
    width: 900px;
    max-width: 90vw;
    height: 100%;
    background: var(--card-bg);
    box-shadow: var(--shadow-hover);
    padding: 0 40px 40px 40px;
    transition: right 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    overflow-y: auto;
    z-index: 5000;
    box-sizing: border-box;
    backdrop-filter: var(--glass-filter);
}
.side-panel-help.open {
    right: 0;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script>
window.helpContentLoaded = false;

function openHelpPanel() {
    document.getElementById('help-panel').classList.add('open');
    document.getElementById('help-panel-overlay').style.display = 'block';
    document.body.style.overflow = 'hidden'; 
    
    if (!window.helpContentLoaded) {
        fetch('api.php?action=get_help')
            .then(response => response.text())
            .then(markdown => {
                document.getElementById('help-markdown-body').innerHTML = marked.parse(markdown);
                window.helpContentLoaded = true;
            })
            .catch(error => {
                document.getElementById('help-markdown-body').innerHTML = '<p style="color:red;">Erreur lors du chargement de l\'aide.</p>';
            });
    }
}

function closeHelpPanel() {
    document.getElementById('help-panel').classList.remove('open');
    document.getElementById('help-panel-overlay').style.display = 'none';
    document.body.style.overflow = 'auto';
}
</script>
