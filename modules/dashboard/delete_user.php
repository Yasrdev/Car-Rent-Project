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
    
    $user_id = $_POST['user_id'] ?? '';

    // Validation
    if (empty($user_id) || !is_numeric($user_id)) {
        $response['message'] = 'ID utilisateur invalide';
        echo json_encode($response);
        exit();
    }

    // Empêcher l'utilisateur de se supprimer lui-même
    if ($user_id == $_SESSION['user_id']) {
        $response['message'] = 'Vous ne pouvez pas supprimer votre propre compte';
        echo json_encode($response);
        exit();
    }

    try {
        // Vérifier si l'utilisateur existe
        $stmt = $pdo->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        if (!$user) {
            $response['message'] = 'Utilisateur non trouvé';
            echo json_encode($response);
            exit();
        }

        // Supprimer l'utilisateur
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $success = $stmt->execute([$user_id]);

        if ($success) {
            $response['success'] = true;
            $response['message'] = "Utilisateur {$user['first_name']} {$user['last_name']} supprimé avec succès";
        } else {
            $response['message'] = 'Erreur lors de la suppression de l\'utilisateur';
        }
    } catch (PDOException $e) {
        error_log("Erreur suppression utilisateur: " . $e->getMessage());
        
        // Vérifier si c'est une erreur de clé étrangère
        if (strpos($e->getMessage(), 'foreign key constraint') !== false) {
            $response['message'] = 'Impossible de supprimer cet utilisateur car il est lié à d\'autres données';
        } else {
            $response['message'] = 'Erreur de base de données: ' . $e->getMessage();
        }
    }
    
    echo json_encode($response);
    exit();
}

// Si la méthode n'est pas POST
echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
?>