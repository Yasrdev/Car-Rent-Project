<?php
require_once('../../config/config.php'); // Chemin corrigé

header('Content-Type: application/json');

// Vérifier les permissions
if (!isLoggedIn() || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'manager')) {
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['success' => false, 'message' => ''];
    
    $user_id = $_POST['user_id'] ?? '';
    $first_name = cleanInput($_POST['first_name'] ?? '');
    $last_name = cleanInput($_POST['last_name'] ?? '');
    $email = cleanInput($_POST['email'] ?? '');
    $role = $_POST['role'] ?? '';
    $status = $_POST['status'] ?? '';

    // Validation des données
    if (empty($user_id) || !is_numeric($user_id)) {
        $response['message'] = 'ID utilisateur invalide';
        echo json_encode($response);
        exit();
    }

    if (empty($first_name) || empty($last_name) || empty($email)) {
        $response['message'] = 'Tous les champs obligatoires doivent être remplis';
        echo json_encode($response);
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'Adresse email invalide';
        echo json_encode($response);
        exit();
    }

    // Valider le rôle
    $allowed_roles = ['admin', 'manager', 'user'];
    if (!in_array($role, $allowed_roles)) {
        $response['message'] = 'Rôle invalide';
        echo json_encode($response);
        exit();
    }

    // Valider le statut
    $allowed_statuses = ['active', 'inactive'];
    if (!in_array($status, $allowed_statuses)) {
        $response['message'] = 'Statut invalide';
        echo json_encode($response);
        exit();
    }

    try {
        // Vérifier si l'email existe déjà pour un autre utilisateur
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $user_id]);
        
        if ($stmt->fetch()) {
            $response['message'] = 'Cet email est déjà utilisé par un autre utilisateur';
            echo json_encode($response);
            exit();
        }

        // Mettre à jour l'utilisateur
        $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, role = ?, status = ? WHERE id = ?");
        $success = $stmt->execute([$first_name, $last_name, $email, $role, $status, $user_id]);

        if ($success) {
            $response['success'] = true;
            $response['message'] = 'Utilisateur mis à jour avec succès';
            
            // Si l'utilisateur modifié est l'utilisateur connecté, mettre à jour la session
            if ($user_id == $_SESSION['user_id']) {
                $_SESSION['first_name'] = $first_name;
                $_SESSION['last_name'] = $last_name;
                $_SESSION['email'] = $email;
                $_SESSION['role'] = $role;
                $_SESSION['status'] = $status;
            }
        } else {
            $response['message'] = 'Erreur lors de la mise à jour de l\'utilisateur';
        }
    } catch (PDOException $e) {
        error_log("Erreur mise à jour utilisateur: " . $e->getMessage());
        $response['message'] = 'Erreur de base de données: ' . $e->getMessage();
    }
    
    echo json_encode($response);
    exit();
}

// Si la méthode n'est pas POST
echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
?>