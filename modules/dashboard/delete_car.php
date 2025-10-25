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

    // Validation
    if (empty($car_id) || !is_numeric($car_id)) {
        $response['message'] = 'ID voiture invalide';
        echo json_encode($response);
        exit();
    }

    try {
        // Récupérer les informations de la voiture
        $stmt = $pdo->prepare("SELECT title, image_url FROM cars WHERE id = ?");
        $stmt->execute([$car_id]);
        $car = $stmt->fetch();

        if (!$car) {
            $response['message'] = 'Voiture non trouvée';
            echo json_encode($response);
            exit();
        }

        // Supprimer l'image du dossier uploads si elle existe
        $image_url = $car['image_url'];
        if (!empty($image_url) && strpos($image_url, 'uploads/') === 0) {
            $image_path = '../../' . $image_url; // Chemin complet vers l'image
            
            // Vérifier si le fichier existe et le supprimer
            if (file_exists($image_path)) {
                if (unlink($image_path)) {
                    error_log("Image supprimée: " . $image_path);
                } else {
                    error_log("Erreur lors de la suppression de l'image: " . $image_path);
                }
            }
        }

        // Supprimer la voiture de la base de données
        $stmt = $pdo->prepare("DELETE FROM cars WHERE id = ?");
        $success = $stmt->execute([$car_id]);

        if ($success) {
            $response['success'] = true;
            $response['message'] = "Voiture \"{$car['title']}\" supprimée avec succès";
        } else {
            $response['message'] = 'Erreur lors de la suppression de la voiture';
        }
    } catch (PDOException $e) {
        error_log("Erreur suppression voiture: " . $e->getMessage());
        
        // Vérifier si c'est une erreur de clé étrangère
        if (strpos($e->getMessage(), 'foreign key constraint') !== false) {
            $response['message'] = 'Impossible de supprimer cette voiture car elle est liée à des réservations';
        } else {
            $response['message'] = 'Erreur de base de données: ' . $e->getMessage();
        }
    }
    
    echo json_encode($response);
    exit();
}

echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
?>