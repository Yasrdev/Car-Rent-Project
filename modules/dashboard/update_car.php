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
    $status = $_POST['status'] ?? 'available';
    $current_image_url = $_POST['current_image_url'] ?? '';

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
        // Vérifier si la voiture existe et récupérer l'image actuelle
        $stmt = $pdo->prepare("SELECT id, image_url FROM cars WHERE id = ?");
        $stmt->execute([$car_id]);
        $car = $stmt->fetch();
        
        if (!$car) {
            $response['message'] = 'Voiture non trouvée';
            echo json_encode($response);
            exit();
        }

        // Gestion de l'upload d'image
        $image_url = $current_image_url; // Par défaut, on garde l'image actuelle
        $oldImageToDelete = null;

        if (isset($_FILES['car_image']) && $_FILES['car_image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../../uploads/cars/';
            
            // Créer le dossier s'il n'existe pas
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Validation du fichier
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $fileType = mime_content_type($_FILES['car_image']['tmp_name']);
            $fileSize = $_FILES['car_image']['size'];
            $maxFileSize = 2 * 1024 * 1024; // 2MB
            
            if (!in_array($fileType, $allowedTypes)) {
                $response['message'] = 'Type de fichier non autorisé. Formats acceptés: JPG, PNG, GIF, WebP';
                echo json_encode($response);
                exit();
            }
            
            if ($fileSize > $maxFileSize) {
                $response['message'] = 'Fichier trop volumineux. Taille maximum: 2MB';
                echo json_encode($response);
                exit();
            }
            
            // Générer un nom de fichier unique
            $fileExtension = pathinfo($_FILES['car_image']['name'], PATHINFO_EXTENSION);
            $fileName = uniqid() . '_car_' . $car_id . '.' . $fileExtension;
            $uploadFile = $uploadDir . $fileName;
            
            // Déplacer le fichier uploadé
            if (move_uploaded_file($_FILES['car_image']['tmp_name'], $uploadFile)) {
                $image_url = './uploads/cars/' . $fileName;
                
                // Marquer l'ancienne image pour suppression si elle existe
                if (!empty($car['image_url']) && 
                    $car['image_url'] !== $image_url && 
                    strpos($car['image_url'], './uploads/cars/') !== false) {
                    $oldImageToDelete = $car['image_url'];
                }
            } else {
                $response['message'] = 'Erreur lors de l\'upload de l\'image';
                echo json_encode($response);
                exit();
            }
        }

        // Mettre à jour la voiture
        $stmt = $pdo->prepare("UPDATE cars SET title = ?, description = ?, category_id = ?, price = ?, status = ?, image_url = ? WHERE id = ?");
        $success = $stmt->execute([
            $title, 
            $description, 
            $category_id, 
            $price, 
            $status, 
            $image_url, 
            $car_id
        ]);

        if ($success) {
            // Supprimer l'ancienne image après la mise à jour réussie
            if ($oldImageToDelete) {
                $oldImagePath = '../../' . ltrim($oldImageToDelete, './');
                if (file_exists($oldImagePath) && is_file($oldImagePath)) {
                    unlink($oldImagePath);
                }
            }
            
            $response['success'] = true;
            $response['message'] = 'Voiture mise à jour avec succès';
            $response['image_url'] = $image_url;
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