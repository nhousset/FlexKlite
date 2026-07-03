<?php
require_once 'auth.php';
header('Content-Type: application/json');

$db_dir = __DIR__ . '/db';
$db_file = $db_dir . '/kanban.json';
$settings_file = $db_dir . '/settings.json';
$history_file = $db_dir . '/history.json'; // Nouveau fichier d'historique global

if (!is_dir($db_dir)) { mkdir($db_dir, 0755, true); }
if (!file_exists($db_file)) {
    file_put_contents($db_file, json_encode(["todo" => [], "in_progress" => [], "blocked" => [], "done" => []], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

if (!file_exists($settings_file)) {
    $default_settings = [
        "app_title" => "Gestion des Chantiers & Suivi",
        "team_name" => "IHMT",
        "projets" => ["VIYA 4", "Plateforme", "MCO"],
        "acteurs" => ["Nicolas H.", "Kevin L.", "David M."],
        "priorites" => ["1", "2", "3", "En attente"],
        "reunions" => ["Point OPS", "Comité BI", "Coproj", "Point équipe"]
    ];
    file_put_contents($settings_file, json_encode($default_settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

if (!file_exists($history_file)) {
    file_put_contents($history_file, json_encode([], JSON_PRETTY_PRINT));
}

$current_settings = json_decode(file_get_contents($settings_file), true);
$needs_update = false;
if (!isset($current_settings['reunions'])) { $current_settings['reunions'] = ["Point OPS", "Comité BI", "Coproj", "Point équipe"]; $needs_update = true; }
if (!isset($current_settings['app_title'])) { $current_settings['app_title'] = "Gestion des Chantiers & Suivi"; $needs_update = true; }
if (!isset($current_settings['team_name'])) { $current_settings['team_name'] = "IHMT"; $needs_update = true; }

if ($needs_update) {
    file_put_contents($settings_file, json_encode($current_settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function read_db($file) { return json_decode(file_get_contents($file), true); }
function write_db($file, $data) { file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); }

// Fonction d'aide pour ajouter une entrée au journal général
function log_action($action_type, $details) {
    global $history_file;
    $history = file_exists($history_file) ? json_decode(file_get_contents($history_file), true) : [];
    if (!is_array($history)) { $history = []; }
    
    array_unshift($history, [
        'id'        => uniqid(),
        'date'      => date('d/m/Y H:i:s'),
        'action'    => $action_type,
        'details'   => $details,
        'timestamp' => time()
    ]);
    
    file_put_contents($history_file, json_encode($history, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

$action = $_GET['action'] ?? '';
$kanban = read_db($db_file);

switch ($action) {
    case 'get_settings':
        echo file_get_contents($settings_file);
        break;

    case 'save_settings':
        $data = json_decode(file_get_contents('php://input'), true);
        write_db($settings_file, $data);
        echo json_encode(['success' => true]);
        break;

    case 'get':
        echo json_encode($kanban);
        break;

    case 'move':
        $data = json_decode(file_get_contents('php://input'), true);
        $from_col = $data['fromColumn'];
        $to_col   = $data['toColumn'];
        $from_idx = (int)$data['fromIndex'];
        $to_idx   = (int)$data['toIndex'];

        $task = array_splice($kanban[$from_col], $from_idx, 1)[0];
        array_splice($kanban[$to_col], $to_idx, 0, [$task]);
        
        write_db($db_file, $kanban);
        
        $labels = ['todo' => 'À Faire', 'in_progress' => 'En Cours', 'blocked' => 'Bloqué', 'done' => 'Terminé'];
        log_action('Mouvement', "La tâche '" . $task['titre'] . "' a été déplacée de [" . $labels[$from_col] . "] vers [" . $labels[$to_col] . "].");
        
        echo json_encode(['success' => true]);
        break;

    case 'add_task':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $new_task = [
                'projet'      => $_POST['projet'] ?? '',
                'code_projet' => $_POST['code_projet'] ?? '',
                'titre'       => $_POST['titre'] ?? '',
                'code_itbm'   => $_POST['code_itbm'] ?? '',
                'prio'        => $_POST['prio'] ?? '',
                'acteur'      => $_POST['acteur'] ?? '',
                'couleur'     => $_POST['couleur'] ?? 'color-yellow',
                'date_debut'  => $_POST['date_debut'] ?? '',
                'date_fin'    => $_POST['date_fin'] ?? '',
                'maj'         => date('d/m'),
                'notes'       => []
            ];
            
            if (!empty($_POST['note_initiale'])) {
                $new_task['notes'][] = [
                    'date'      => date('d/m/Y'),
                    'reunion'   => '',
                    'texte'     => $_POST['note_initiale'],
                    'timestamp' => time()
                ];
            }
            array_unshift($kanban['todo'], $new_task);
            write_db($db_file, $kanban);
            
            log_action('Création', "Nouvelle tâche '" . $new_task['titre'] . "' ajoutée au projet [" . $new_task['projet'] . "].");
        }
        header('Location: index.php');
        exit;

    case 'edit_task':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $col = $_POST['column'] ?? '';
            $idx = (int)($_POST['index'] ?? -1);

            if ($col !== '' && $idx !== -1 && isset($kanban[$col][$idx])) {
                $kanban[$col][$idx]['projet']      = $_POST['projet'] ?? '';
                $kanban[$col][$idx]['code_projet'] = $_POST['code_projet'] ?? '';
                $kanban[$col][$idx]['titre']       = $_POST['titre'] ?? '';
                $kanban[$col][$idx]['code_itbm']   = $_POST['code_itbm'] ?? '';
                $kanban[$col][$idx]['prio']        = $_POST['prio'] ?? '';
                $kanban[$col][$idx]['acteur']      = $_POST['acteur'] ?? '';
                $kanban[$col][$idx]['couleur']     = $_POST['couleur'] ?? 'color-yellow';
                $kanban[$col][$idx]['date_debut']  = $_POST['date_debut'] ?? '';
                $kanban[$col][$idx]['date_fin']    = $_POST['date_fin'] ?? '';
                $kanban[$col][$idx]['maj']         = date('d/m');
                
                write_db($db_file, $kanban);
                
                log_action('Modification', "Les paramètres de la tâche '" . $kanban[$col][$idx]['titre'] . "' ont été mis à jour.");
            }
        }
        header('Location: index.php');
        exit;

    case 'add_note':
        $data = json_decode(file_get_contents('php://input'), true);
        $col = $data['column'];
        $idx = (int)$data['index'];
        $texte = $data['text'];
        $reunion = $data['reunion'] ?? '';
        
        $date_saisie = !empty($data['date']) ? date('d/m/Y', strtotime($data['date'])) : date('d/m/Y');

        if (!empty($texte) && isset($kanban[$col][$idx])) {
            array_unshift($kanban[$col][$idx]['notes'], [
                'date'      => $date_saisie,
                'reunion'   => $reunion,
                'texte'     => $texte,
                'timestamp' => time()
            ]);
            $kanban[$col][$idx]['maj'] = !empty($data['date']) ? date('d/m', strtotime($data['date'])) : date('d/m');
            
            write_db($db_file, $kanban);
            
            $ctxLog = !empty($reunion) ? " lors de : " . $reunion : "";
            log_action('Suivi', "Note de suivi ajoutée à la tâche '" . $kanban[$col][$idx]['titre'] . "'${ctxLog}.");
            
            echo json_encode(['success' => true, 'task' => $kanban[$col][$idx]]);
        } else {
            echo json_encode(['success' => false]);
        }
        break;

    case 'get_raw_json':
        $file = $_GET['file'] ?? '';
        if ($file === 'kanban') { echo file_get_contents($db_file); }
        elseif ($file === 'settings') { echo file_get_contents($settings_file); }
        exit;

    case 'save_raw_json':
        $input = json_decode(file_get_contents('php://input'), true);
        $file = $input['file'] ?? '';
        $raw_content = $input['content'] ?? '';
        
        $parsed = json_decode($raw_content, true);
        if ($parsed === null && json_last_error() !== JSON_ERROR_NONE) {
            echo json_encode(['success' => false, 'error' => 'Format JSON invalide : ' . json_last_error_msg()]);
            exit;
        }

        if ($file === 'kanban') {
            file_put_contents($db_file, json_encode($parsed, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            log_action('Admin', "Modification manuelle directe du fichier kanban.json via l'éditeur brut.");
            echo json_encode(['success' => true]);
        } elseif ($file === 'settings') {
            file_put_contents($settings_file, json_encode($parsed, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            log_action('Admin', "Modification manuelle directe du fichier settings.json via l'éditeur brut.");
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Fichier inconnu.']);
        }
        exit;

    case 'export_backup_zip':
        $zip = new ZipArchive();
        $tmp_file = tempnam(sys_get_temp_dir(), 'zip');
        
        if ($zip->open($tmp_file, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            if (file_exists($db_file)) $zip->addFile($db_file, 'kanban.json');
            if (file_exists($settings_file)) $zip->addFile($settings_file, 'settings.json');
            if (file_exists($history_file)) $zip->addFile($history_file, 'history.json'); // Ajout au package zip
            $zip->close();
            
            if (ob_get_level()) { ob_end_clean(); }
            
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="Backup_Chantiers_'.date('Ymd_His').'.zip"');
            header('Content-Length: ' . filesize($tmp_file));
            readfile($tmp_file);
            unlink($tmp_file);
            exit;
        }
        echo "Erreur critique de compression.";
        exit;

    case 'import_backup_zip':
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['backup_zip']) && $_FILES['backup_zip']['error'] === UPLOAD_ERR_OK) {
            $zip = new ZipArchive();
            if ($zip->open($_FILES['backup_zip']['tmp_name']) === TRUE) {
                $tmp_extract = sys_get_temp_dir() . '/kanban_restore_' . uniqid();
                mkdir($tmp_extract, 0755, true);
                $zip->extractTo($tmp_extract);
                $zip->close();
                
                $success_kanban = false;
                $success_settings = false;

                if (file_exists($tmp_extract . '/kanban.json')) {
                    $json = json_decode(file_get_contents($tmp_extract . '/kanban.json'), true);
                    if ($json !== null) { copy($tmp_extract . '/kanban.json', $db_file); $success_kanban = true; }
                }
                if (file_exists($tmp_extract . '/settings.json')) {
                    $json = json_decode(file_get_contents($tmp_extract . '/settings.json'), true);
                    if ($json !== null) { copy($tmp_extract . '/settings.json', $settings_file); $success_settings = true; }
                }
                if (file_exists($tmp_extract . '/history.json')) {
                    copy($tmp_extract . '/history.json', $history_file);
                }

                @unlink($tmp_extract . '/kanban.json');
                @unlink($tmp_extract . '/settings.json');
                @unlink($tmp_extract . '/history.json');
                @rmdir($tmp_extract);

                if ($success_kanban || $success_settings) {
                    log_action('Backup', "Restauration complète du système opérée via l'importation d'une archive ZIP.");
                    header('Location: admin.php?status=import_ok');
                    exit;
                }
            }
        }
        header('Location: admin.php?status=import_error');
        exit;

    /* ================= NEW ACTIONS FOR LOG HISTORY ================= */
    case 'get_history':
        echo file_get_contents($history_file);
        break;

    case 'delete_history_line':
        $data = json_decode(file_get_contents('php://input'), true);
        $line_id = $data['id'] ?? '';
        
        $history = json_decode(file_get_contents($history_file), true);
        if (is_array($history)) {
            $history = array_values(array_filter($history, function($item) use ($line_id) {
                return $item['id'] !== $line_id;
            }));
            file_put_contents($history_file, json_encode($history, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false]);
        }
        break;
}
?>
