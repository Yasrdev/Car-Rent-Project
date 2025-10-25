<?php
require_once('../../config/config.php');

header('Content-Type: application/json');

// Vérifier les permissions
if (!isLoggedIn() || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'manager')) {
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['success' => false, 'message' => ''];
    
    $car_id = $_POST['car_id'] ?? '';
    $status = $_POST['status'] ?? '';

    // Validation
    if (empty($car_id) || !is_numeric($car_id)) {
        $response['message'] = 'ID voiture invalide';
        echo json_encode($response);
        exit();
    }

    $allowed_statuses = ['available', 'unavailable'];
    if (!in_array($status, $allowed_statuses)) {
        $response['message'] = 'Statut invalide';
        echo json_encode($response);
        exit();
    }

    try {
        // Vérifier si la voiture existe
        $stmt = $pdo->prepare("SELECT title FROM cars WHERE id = ?");
        $stmt->execute([$car_id]);
        $car = $stmt->fetch();

        if (!$car) {
            $response['message'] = 'Voiture non trouvée';
            echo json_encode($response);
            exit();
        }

        // Mettre à jour le statut
        $stmt = $pdo->prepare("UPDATE cars SET status = ? WHERE id = ?");
        $success = $stmt->execute([$status, $car_id]);

        if ($success) {
            $response['success'] = true;
            $status_label = $status === 'available' ? 'disponible' : 'réservé';
            $response['message'] = "Statut de \"{$car['title']}\" changé à {$status_label}";
        } else {
            $response['message'] = 'Erreur lors du changement de statut';
        }
    } catch (PDOException $e) {
        error_log("Erreur changement statut voiture: " . $e->getMessage());
        $response['message'] = 'Erreur de base de données: ' . $e->getMessage();
    }
    
    echo json_encode($response);
    exit();
}

echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
?>
