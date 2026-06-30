<?php
header('Content-Type: application/json');

$db_dir = __DIR__ . '/db';
$db_file = $db_dir . '/kanban.json';

// Création du dossier et du fichier s'ils n'existent pas
if (!is_dir($db_dir)) {
    mkdir($db_dir, 0755, true);
}
if (!file_exists($db_file)) {
    $initial_data = ["todo" => [], "in_progress" => [], "blocked" => [], "done" => []];
    file_put_contents($db_file, json_encode($initial_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// Fonctions utilitaires pour lire/écrire
function read_db($file) {
    return json_decode(file_get_contents($file), true);
}

function write_db($file, $data) {
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

$action = $_GET['action'] ?? '';
$kanban = read_db($db_file);

switch ($action) {
    case 'get':
        echo json_encode($kanban);
        break;

    case 'move':
        // Déplacement par index de tableau (pas besoin d'ID)
        $data = json_decode(file_get_contents('php://input'), true);
        $from_col = $data['fromColumn'];
        $to_col   = $data['toColumn'];
        $from_idx = (int)$data['fromIndex'];
        $to_idx   = (int)$data['toIndex'];

        // Extraction de la tâche de sa colonne d'origine
        $task = array_splice($kanban[$from_col], $from_idx, 1)[0];
        
        // Insertion de la tâche dans sa colonne de destination
        array_splice($kanban[$to_col], $to_idx, 0, [$task]);
        
        write_db($db_file, $kanban);
        echo json_encode(['success' => true]);
        break;

    case 'add_task':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $new_task = [
                'projet'  => $_POST['projet'] ?? '',
                'titre'   => $_POST['titre'] ?? '',
                'prio'    => $_POST['prio'] ?? '',
                'porteur' => $_POST['porteur'] ?? '',
                'acteur'  => $_POST['acteur'] ?? '',
                'maj'     => date('d/m'),
                'notes'   => []
            ];
            if (!empty($_POST['note_initiale'])) {
                $new_task['notes'][] = [
                    'date'  => date('d/m'),
                    'texte' => $_POST['note_initiale']
                ];
            }
            array_unshift($kanban['todo'], $new_task);
            write_db($db_file, $kanban);
        }
        header('Location: index.php');
        exit;

    case 'add_note':
        $data = json_decode(file_get_contents('php://input'), true);
        $col = $data['column'];
        $idx = (int)$data['index'];
        $texte = $data['text'];

        if (!empty($texte) && isset($kanban[$col][$idx])) {
            // Ajout de la note au début du tableau de notes de la tâche
            array_unshift($kanban[$col][$idx]['notes'], [
                'date'  => date('d/m'),
                'texte' => $texte
            ]);
            $kanban[$col][$idx]['maj'] = date('d/m'); // Mise à jour de la date globale
            write_db($db_file, $kanban);
            echo json_encode(['success' => true, 'task' => $kanban[$col][$idx]]);
        } else {
            echo json_encode(['success' => false]);
        }
        break;
}
