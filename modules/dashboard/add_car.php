
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
    
    $title = cleanInput($_POST['title'] ?? '');
    $description = cleanInput($_POST['description'] ?? '');
    $category_id = $_POST['category_id'] ?? '';
    $price = $_POST['price'] ?? '';
    $status = $_POST['status'] ?? 'available';
    $image_url = cleanInput($_POST['image_url'] ?? '');

    // Validation des données
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

    $allowed_statuses = ['available', 'unavailable'];
    if (!in_array($status, $allowed_statuses)) {
        $response['message'] = 'Statut invalide';
        echo json_encode($response);
        exit();
    }

    try {
        // Gestion de l'upload d'image
        $uploaded_image_url = '';
        
        // Vérifier si un fichier est uploadé
        if (isset($_FILES['car_image']) && $_FILES['car_image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['car_image'];
            
            // Vérifications de sécurité
            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            $max_size = 2 * 1024 * 1024; // 2MB
            
            if (!in_array($file['type'], $allowed_types)) {
                $response['message'] = 'Type de fichier non autorisé. Formats acceptés: JPG, PNG, GIF';
                echo json_encode($response);
                exit();
            }
            
            if ($file['size'] > $max_size) {
                $response['message'] = 'Fichier trop volumineux. Taille maximale: 2MB';
                echo json_encode($response);
                exit();
            }
            
            // Créer le dossier uploads s'il n'existe pas
            $upload_dir = '../../uploads/cars/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // Générer un nom de fichier unique
            $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '_' . time() . '.' . $file_extension;
            $file_path = $upload_dir . $filename;
            
            // Déplacer le fichier uploadé
            if (move_uploaded_file($file['tmp_name'], $file_path)) {
                $uploaded_image_url = 'uploads/cars/' . $filename;
            } else {
                $response['message'] = 'Erreur lors de l\'upload du fichier';
                echo json_encode($response);
                exit();
            }
        }
        
        // Déterminer l'URL de l'image à utiliser
        $final_image_url = '';
        if (!empty($image_url)) {
            // Priorité à l'URL fournie
            $final_image_url = $image_url;
        } elseif (!empty($uploaded_image_url)) {
            // Sinon utiliser l'image uploadée
            $final_image_url = $uploaded_image_url;
        } else {
            $response['message'] = 'Veuillez fournir une image (upload ou URL)';
            echo json_encode($response);
            exit();
        }

        // Insérer la nouvelle voiture
        $stmt = $pdo->prepare("INSERT INTO cars (title, description, category_id, price, status, image_url, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $success = $stmt->execute([$title, $description, $category_id, $price, $status, $final_image_url]);

        if ($success) {
            $response['success'] = true;
            $response['message'] = 'Voiture ajoutée avec succès';
        } else {
            $response['message'] = 'Erreur lors de l\'ajout de la voiture';
            
            // Supprimer l'image uploadée en cas d'erreur
            if (!empty($uploaded_image_url) && file_exists('../../' . $uploaded_image_url)) {
                unlink('../../' . $uploaded_image_url);
            }
        }
    } catch (PDOException $e) {
        error_log("Erreur ajout voiture: " . $e->getMessage());
        $response['message'] = 'Erreur de base de données: ' . $e->getMessage();
        
        // Supprimer l'image uploadée en cas d'erreur
        if (!empty($uploaded_image_url) && file_exists('../../' . $uploaded_image_url)) {
            unlink('../../' . $uploaded_image_url);
        }
    }
    
    echo json_encode($response);
    exit();
}

echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
?>
