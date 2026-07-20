<?php
require_once 'auth.php';
$settings_file = __DIR__ . '/db/settings.json';
$current_settings = file_exists($settings_file) ? json_decode(file_get_contents($settings_file), true) : [];
$app_title = $current_settings['app_title'] ?? 'Kanban Agile';
$app_theme = $current_settings['app_theme'] ?? 'classic';

// Get the documentation content
$doc_path = __DIR__ . '/DOCUMENTATION.md';
if (!file_exists($doc_path)) {
    $doc_path = __DIR__ . '/../DOCUMENTATION.md';
}
$markdown_content = file_exists($doc_path) ? file_get_contents($doc_path) : "# Erreur\nFichier DOCUMENTATION.md introuvable.";

// Adjust image paths since help.php is inside src/
$markdown_content = str_replace('src/img/', 'img/', $markdown_content);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aide - <?= htmlspecialchars($app_title) ?></title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <style>
        .help-container {
            max-width: 900px;
            margin: 40px auto;
            background: var(--card-bg);
            padding: 40px 50px;
            border-radius: 12px;
            box-shadow: var(--shadow-main);
        }
    </style>
</head>
<body data-theme="<?= htmlspecialchars($app_theme) ?>">

    <div class="top-nav">
        <div class="top-nav-left">
            <h1 style="margin: 0; font-size: 20px; color: var(--text-main);">Aide & Documentation</h1>
        </div>
        <div class="top-nav-right">
            <a href="index.php" class="btn" style="text-decoration:none; background:var(--column-bg); color:var(--text-main); padding:6px 12px; margin-right:10px;">🏠 Retour au Tableau</a>
        </div>
    </div>

    <div class="help-container">
        <!-- Markdown rendering container -->
        <div id="markdown-body" class="markdown-body"></div>
    </div>

    <!-- Hidden element to store raw markdown -->
    <textarea id="markdown-source" style="display:none;"><?= htmlspecialchars($markdown_content) ?></textarea>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const markdownSource = document.getElementById('markdown-source').value;
            // Parse Markdown to HTML
            const htmlContent = marked.parse(markdownSource);
            // Inject into the DOM
            document.getElementById('markdown-body').innerHTML = htmlContent;
        });
    </script>
</body>
</html>
