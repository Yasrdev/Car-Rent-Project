[file name]: update_car.php
[file content begin]
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
    $title = cleanInput($_POST['title'] ?? '');
    $description = cleanInput($_POST['description'] ?? '');
    $category_id = $_POST['category_id'] ?? '';
    $price = $_POST['price'] ?? '';
    $image_url = cleanInput($_POST['image_url'] ?? '');

    // Validation des données
    if (empty($car_id) || !is_numeric($car_id)) {
        $response['message'] = 'ID voiture invalide';
        echo json_encode($response);
        exit();
    }

    if (empty($title) || empty($category_id) || empty($price)) {
        $response['message'] = 'Tous les champs obligatoires doivent être remplis';
        echo json_encode($response);
        exit();
    }

    if (!is_numeric($price) || $price < 0) {
        $response['message'] = 'Prix invalide';
        echo json_encode($response);
        exit();
    }

    try {
        // Vérifier si la voiture existe
        $stmt = $pdo->prepare("SELECT id FROM cars WHERE id = ?");
        $stmt->execute([$car_id]);
        
        if (!$stmt->fetch()) {
            $response['message'] = 'Voiture non trouvée';
            echo json_encode($response);
            exit();
        }

        // Mettre à jour la voiture
        $stmt = $pdo->prepare("UPDATE cars SET title = ?, description = ?, category_id = ?, price = ?, image_url = ? WHERE id = ?");
        $success = $stmt->execute([$title, $description, $category_id, $price, $image_url, $car_id]);

        if ($success) {
            $response['success'] = true;
            $response['message'] = 'Voiture mise à jour avec succès';
        } else {
            $response['message'] = 'Erreur lors de la mise à jour de la voiture';
        }
    } catch (PDOException $e) {
        error_log("Erreur mise à jour voiture: " . $e->getMessage());
        $response['message'] = 'Erreur de base de données: ' . $e->getMessage();
    }
    
    echo json_encode($response);
    exit();
}

echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
?>
[file content end]