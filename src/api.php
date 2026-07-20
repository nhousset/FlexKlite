<?php
require_once 'auth.php';
header('Content-Type: application/json');

$db_dir = __DIR__ . '/db';
$db_file = $db_dir . '/kanban.json';
$settings_file = $db_dir . '/settings.json';
$history_file = $db_dir . '/history.json'; 
$admin_file = $db_dir . '/admin.json'; 
$uploads_dir = __DIR__ . '/uploads';

if (!is_dir($db_dir)) { mkdir($db_dir, 0755, true); }
if (!is_dir($uploads_dir)) { mkdir($uploads_dir, 0755, true); }

if (!file_exists($db_file)) {
    file_put_contents($db_file, json_encode(["todo" => [], "in_progress" => [], "blocked" => [], "done" => [], "archives" => []], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

if (!file_exists($settings_file)) {
    $default_settings = [
        "app_title" => "Kanban Agile",
        "team_name" => "Mon Équipe",
        "app_logo" => "",
        "require_read_password" => false,
        "enable_code_projet" => true,
        "enable_code_itbm" => true,
        "projets" => [],
        "acteurs" => [],
        "priorites" => ["Basse", "Moyenne", "Haute", "Urgente"],
        "reunions" => []
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
if (!isset($current_settings['app_logo'])) { $current_settings['app_logo'] = ""; $needs_update = true; }
if (!isset($current_settings['require_read_password'])) { $current_settings['require_read_password'] = false; $needs_update = true; }
if (!isset($current_settings['enable_code_projet'])) { $current_settings['enable_code_projet'] = true; $needs_update = true; }
if (!isset($current_settings['enable_code_itbm'])) { $current_settings['enable_code_itbm'] = true; $needs_update = true; }

if (isset($current_settings['projets']) && count($current_settings['projets']) > 0 && is_string($current_settings['projets'][0])) {
    $new_projets = [];
    $default_palette = ['#0052cc', '#00875a', '#ff9f1a', '#de350b', '#5243aa'];
    foreach($current_settings['projets'] as $idx => $p) {
        $new_projets[] = ['name' => $p, 'color' => $default_palette[$idx % 5]];
    }
    $current_settings['projets'] = $new_projets;
    $needs_update = true;
}

if ($needs_update) {
    @file_put_contents($settings_file, json_encode($current_settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function read_db($file) { return json_decode(file_get_contents($file), true); }
function write_db($file, $data) { file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); }

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
$write_actions = ['archive_task', 'save_settings', 'save_security', 'move', 'add_task', 'edit_task', 'add_lot', 'add_note', 'edit_note', 'upload_attachment', 'delete_attachment', 'upload_logo', 'save_raw_json', 'import_backup_zip'];

if (in_array($action, $write_actions) && !$is_logged_in) {
    echo json_encode(['success' => false, 'error' => 'Action non autorisée. Vous êtes en mode invité.']);
    exit;
}

$kanban = read_db($db_file);

switch ($action) {
    case 'archive_task':
        $data = json_decode(file_get_contents('php://input'), true);
        $col = $data['column'] ?? '';
        $idx = $data['index'] ?? -1;

        if (isset($kanban[$col][$idx])) {
            $task = $kanban[$col][$idx];
            array_splice($kanban[$col], $idx, 1);
            if (!isset($kanban['archives'])) {
                $kanban['archives'] = [];
            }
            array_unshift($kanban['archives'], $task);
            @file_put_contents($db_file, json_encode($kanban, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            log_action('Archivage', "Tâche '{$task['titre']}' archivée.");
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Tâche introuvable.']);
        }
        break;

    case 'get_settings':
        echo file_get_contents($settings_file);
        break;

    case 'save_settings':
        $data = json_decode(file_get_contents('php://input'), true);
        write_db($settings_file, $data);
        echo json_encode(['success' => true]);
        break;

    case 'save_security':
        $data = json_decode(file_get_contents('php://input'), true);
        $req_pass = $data['require_read_password'] ?? false;
        $new_pass = $data['readonly_password'] ?? '';

        $settings = read_db($settings_file);
        $settings['require_read_password'] = (bool)$req_pass;
        write_db($settings_file, $settings);

        if (!empty($new_pass)) {
            $admin_data = file_exists($admin_file) ? json_decode(file_get_contents($admin_file), true) : [];
            $admin_data['readonly_password'] = password_hash($new_pass, PASSWORD_DEFAULT);
            file_put_contents($admin_file, json_encode($admin_data, JSON_PRETTY_PRINT));
        }
        
        log_action('Sécurité', "Les paramètres d'accès en lecture seule ont été modifiés.");
        echo json_encode(['success' => true]);
        break;

    case 'upload_logo':
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['logo'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'];
            
            if (in_array($ext, $allowed)) {
                $filename = 'app_logo_' . time() . '.' . $ext;
                $path = $uploads_dir . '/' . $filename;
                
                if (move_uploaded_file($file['tmp_name'], $path)) {
                    // S'assurer que le fichier est bien lisible par le serveur web
                    chmod($path, 0644);
                    
                    $settings = json_decode(file_get_contents($settings_file), true);
                    $settings['app_logo'] = 'uploads/' . $filename;
                    write_db($settings_file, $settings);
                    
                    log_action('Admin', "Le logo de l'application a été modifié.");
                    echo json_encode(['success' => true, 'logo_path' => 'uploads/' . $filename]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Erreur de copie du fichier sur le serveur.']);
                }
            } else {
                echo json_encode(['success' => false, 'error' => 'Format de fichier non autorisé.']);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Aucun fichier reçu ou erreur d\'envoi.']);
        }
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

    case 'update_task_dates':
        $data = json_decode(file_get_contents('php://input'), true);
        $col = $data['column'] ?? '';
        $idx = (int)($data['index'] ?? -1);
        $start = $data['start'] ?? '';
        $end = $data['end'] ?? '';
        
        if ($col !== '' && $idx !== -1 && isset($kanban[$col][$idx])) {
            $kanban[$col][$idx]['date_debut'] = $start;
            $kanban[$col][$idx]['date_fin'] = $end;
            $kanban[$col][$idx]['maj'] = date('d/m');
            write_db($db_file, $kanban);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Tâche introuvable.']);
        }
        break;

    case 'add_task':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $new_task = [
                'projet'      => $_POST['projet'] ?? '',
                'code_projet' => $_POST['code_projet'] ?? '',
                'link_code_projet' => $_POST['link_code_projet'] ?? '',
                'titre'       => $_POST['titre'] ?? '',
                'code_itbm'   => $_POST['code_itbm'] ?? '',
                'link_code_itbm' => $_POST['link_code_itbm'] ?? '',
                'prio'        => $_POST['prio'] ?? '',
                'acteur'      => $_POST['acteur'] ?? '',
                'date_debut'  => $_POST['date_debut'] ?? '',
                'date_fin'    => $_POST['date_fin'] ?? '',
                'charge_jh'   => isset($_POST['charge_jh']) && $_POST['charge_jh'] !== '' ? (float)$_POST['charge_jh'] : null,
                'prerequis'   => $_POST['prerequis'] ?? '',
                'maj'         => date('d/m'),
                'notes'       => [],
                'lots'        => [], 
                'attachments' => [] 
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
                $kanban[$col][$idx]['link_code_projet'] = $_POST['link_code_projet'] ?? '';
                $kanban[$col][$idx]['titre']       = $_POST['titre'] ?? '';
                $kanban[$col][$idx]['code_itbm']   = $_POST['code_itbm'] ?? '';
                $kanban[$col][$idx]['link_code_itbm'] = $_POST['link_code_itbm'] ?? '';
                $kanban[$col][$idx]['prio']        = $_POST['prio'] ?? '';
                $kanban[$col][$idx]['acteur']      = $_POST['acteur'] ?? '';
                $kanban[$col][$idx]['date_debut']  = $_POST['date_debut'] ?? '';
                $kanban[$col][$idx]['date_fin']    = $_POST['date_fin'] ?? '';
                $kanban[$col][$idx]['charge_jh']   = isset($_POST['charge_jh']) && $_POST['charge_jh'] !== '' ? (float)$_POST['charge_jh'] : null;
                $kanban[$col][$idx]['prerequis']   = $_POST['prerequis'] ?? '';
                $kanban[$col][$idx]['maj']         = date('d/m');
                
                $kanban[$col][$idx]['lots']        = $kanban[$col][$idx]['lots'] ?? [];
                $kanban[$col][$idx]['attachments'] = $kanban[$col][$idx]['attachments'] ?? [];
                
                write_db($db_file, $kanban);
                
                log_action('Modification', "Les paramètres de la tâche '" . $kanban[$col][$idx]['titre'] . "' ont été mis à jour.");
            }
        }
        header('Location: index.php');
        exit;

    case 'add_lot':
        $data = json_decode(file_get_contents('php://input'), true);
        $col = $data['column'];
        $idx = (int)$data['index'];
        $titre = trim($data['titre'] ?? '');
        $code = trim($data['code'] ?? '');

        if (!empty($titre) && isset($kanban[$col][$idx])) {
            if (!isset($kanban[$col][$idx]['lots'])) { $kanban[$col][$idx]['lots'] = []; }
            $new_lot = [
                'id' => uniqid('lot_'),
                'titre' => $titre,
                'code_itbm' => $code,
                'notes' => []
            ];
            $kanban[$col][$idx]['lots'][] = $new_lot;
            $kanban[$col][$idx]['maj'] = date('d/m');
            
            write_db($db_file, $kanban);
            log_action('Lot', "Création du lot '{$titre}' dans la tâche '" . $kanban[$col][$idx]['titre'] . "'.");
            echo json_encode(['success' => true, 'task' => $kanban[$col][$idx]]);
        } else {
            echo json_encode(['success' => false]);
        }
        break;

    case 'add_note':
        $data = json_decode(file_get_contents('php://input'), true);
        $col = $data['column'];
        $idx = (int)$data['index'];
        $texte = $data['text'];
        $reunion = $data['reunion'] ?? '';
        $lot_id = $data['lot_id'] ?? '';
        
        $date_saisie = !empty($data['date']) ? date('d/m/Y', strtotime($data['date'])) : date('d/m/Y');

        if (!empty($texte) && isset($kanban[$col][$idx])) {
            $new_note = [
                'date'      => $date_saisie,
                'reunion'   => $reunion,
                'texte'     => $texte,
                'timestamp' => time()
            ];

            if (!empty($lot_id)) {
                foreach ($kanban[$col][$idx]['lots'] as &$lot) {
                    if ($lot['id'] === $lot_id) {
                        if (!isset($lot['notes'])) $lot['notes'] = [];
                        array_unshift($lot['notes'], $new_note);
                        break;
                    }
                }
            } else {
                if (!isset($kanban[$col][$idx]['notes'])) $kanban[$col][$idx]['notes'] = [];
                array_unshift($kanban[$col][$idx]['notes'], $new_note);
            }

            $kanban[$col][$idx]['maj'] = !empty($data['date']) ? date('d/m', strtotime($data['date'])) : date('d/m');
            
            write_db($db_file, $kanban);
            
            $ctxLog = !empty($reunion) ? " lors de : " . $reunion : "";
            log_action('Suivi', "Note de suivi ajoutée à la tâche '" . $kanban[$col][$idx]['titre'] . "'{$ctxLog}.");
            
            echo json_encode(['success' => true, 'task' => $kanban[$col][$idx]]);
        } else {
            echo json_encode(['success' => false]);
        }
        break;

    case 'edit_note':
        $data = json_decode(file_get_contents('php://input'), true);
        $col = $data['column'] ?? '';
        $idx = (int)($data['index'] ?? -1);
        $timestamp = (int)($data['timestamp'] ?? 0);
        $texte = $data['text'] ?? '';
        $reunion = $data['reunion'] ?? '';
        $lot_id = $data['lot_id'] ?? '';
        
        $date_saisie = !empty($data['date']) ? date('d/m/Y', strtotime($data['date'])) : date('d/m/Y');

        if ($col !== '' && $idx !== -1 && $timestamp > 0 && !empty($texte) && isset($kanban[$col][$idx])) {
            $task = &$kanban[$col][$idx];
            $found_note = null;

            if (isset($task['notes'])) {
                foreach ($task['notes'] as $k => $n) {
                    if (isset($n['timestamp']) && $n['timestamp'] == $timestamp) {
                        $found_note = $n;
                        array_splice($task['notes'], $k, 1);
                        break;
                    }
                }
            }
            
            if (!$found_note && isset($task['lots'])) {
                foreach ($task['lots'] as &$lot) {
                    if (isset($lot['notes'])) {
                        foreach ($lot['notes'] as $k => $n) {
                            if (isset($n['timestamp']) && $n['timestamp'] == $timestamp) {
                                $found_note = $n;
                                array_splice($lot['notes'], $k, 1);
                                break 2;
                            }
                        }
                    }
                }
            }

            if ($found_note) {
                $found_note['texte'] = $texte;
                $found_note['date'] = $date_saisie;
                $found_note['reunion'] = $reunion;

                if (!empty($lot_id)) {
                    foreach ($task['lots'] as &$lot) {
                        if ($lot['id'] === $lot_id) {
                            if (!isset($lot['notes'])) $lot['notes'] = [];
                            array_unshift($lot['notes'], $found_note);
                            break;
                        }
                    }
                } else {
                    if (!isset($task['notes'])) $task['notes'] = [];
                    array_unshift($task['notes'], $found_note);
                }

                $task['maj'] = !empty($data['date']) ? date('d/m', strtotime($data['date'])) : date('d/m');
                
                write_db($db_file, $kanban);
                log_action('Modification Suivi', "Une note de suivi a été modifiée sur la tâche '" . $task['titre'] . "'.");
                
                echo json_encode(['success' => true, 'task' => $task]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Note introuvable']);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Paramètres invalides']);
        }
        break;

    case 'upload_attachment':
        $col = $_POST['column'] ?? '';
        $idx = (int)($_POST['index'] ?? -1);

        if ($col !== '' && $idx !== -1 && isset($kanban[$col][$idx]) && isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
            $task = &$kanban[$col][$idx];
            if (!isset($task['attachments'])) { $task['attachments'] = []; }
            
            $file = $_FILES['file'];
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = uniqid('file_') . '.' . $ext;
            $path = $uploads_dir . '/' . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $path)) {
                $attachment = [
                    'id' => uniqid('att_'),
                    'original_name' => htmlspecialchars($file['name']),
                    'filename' => $filename,
                    'path' => 'uploads/' . $filename,
                    'size' => $file['size'],
                    'date' => date('d/m/Y H:i')
                ];
                array_unshift($task['attachments'], $attachment);
                $task['maj'] = date('d/m');
                
                write_db($db_file, $kanban);
                log_action('Pièce jointe', "Le fichier '{$file['name']}' a été ajouté à la tâche '{$task['titre']}'.");
                echo json_encode(['success' => true, 'task' => $task]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Erreur système lors du déplacement du fichier.']);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Données ou fichier invalide.']);
        }
        break;

    case 'delete_attachment':
        $data = json_decode(file_get_contents('php://input'), true);
        $col = $data['column'] ?? '';
        $idx = (int)($data['index'] ?? -1);
        $att_id = $data['attachment_id'] ?? '';
        
        if ($col !== '' && $idx !== -1 && isset($kanban[$col][$idx]) && !empty($att_id)) {
            $task = &$kanban[$col][$idx];
            if (isset($task['attachments'])) {
                foreach ($task['attachments'] as $k => $att) {
                    if ($att['id'] === $att_id) {
                        $filepath = __DIR__ . '/' . $att['path'];
                        if (file_exists($filepath)) { unlink($filepath); }
                        
                        $deleted_name = $att['original_name'];
                        array_splice($task['attachments'], $k, 1);
                        $task['maj'] = date('d/m');
                        
                        write_db($db_file, $kanban);
                        log_action('Pièce jointe', "Le fichier '{$deleted_name}' a été supprimé de la tâche '{$task['titre']}'.");
                        echo json_encode(['success' => true, 'task' => $task]);
                        exit;
                    }
                }
            }
            echo json_encode(['success' => false, 'error' => 'Fichier introuvable en base de données.']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Paramètres invalides.']);
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
            if (file_exists($history_file)) $zip->addFile($history_file, 'history.json');
            if (file_exists($admin_file)) $zip->addFile($admin_file, 'admin.json');
            
            if (is_dir($uploads_dir)) {
                $files = scandir($uploads_dir);
                foreach ($files as $f) {
                    if ($f !== '.' && $f !== '..') {
                        $zip->addFile($uploads_dir . '/' . $f, 'uploads/' . $f);
                    }
                }
            }
            
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
                if (file_exists($tmp_extract . '/admin.json')) {
                    copy($tmp_extract . '/admin.json', $admin_file);
                }
                
                if (is_dir($tmp_extract . '/uploads')) {
                    if (!is_dir($uploads_dir)) mkdir($uploads_dir, 0755, true);
                    $files = scandir($tmp_extract . '/uploads');
                    foreach ($files as $f) {
                        if ($f !== '.' && $f !== '..') {
                            copy($tmp_extract . '/uploads/' . $f, $uploads_dir . '/' . $f);
                        }
                    }
                }

                @unlink($tmp_extract . '/kanban.json');
                @unlink($tmp_extract . '/settings.json');
                @unlink($tmp_extract . '/history.json');
                @unlink($tmp_extract . '/admin.json');
                if (is_dir($tmp_extract . '/uploads')) {
                    foreach (scandir($tmp_extract . '/uploads') as $f) { if ($f !== '.' && $f !== '..') @unlink($tmp_extract . '/uploads/' . $f); }
                    @rmdir($tmp_extract . '/uploads');
                }
                @rmdir($tmp_extract);

                if ($success_kanban || $success_settings) {
                    log_action('Backup', "Restauration complète du système (incluant les PJ) opérée via l'importation d'une archive ZIP.");
                    header('Location: admin.php?status=import_ok');
                    exit;
                }
            }
        }
        header('Location: admin.php?status=import_error');
        exit;

    case 'get_history':
        echo file_get_contents($history_file);
        break;
}
?>
