<?php
require_once('./config/config.php');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    $response = ['success' => false, 'message' => '', 'errors' => []];
    
    if (isset($_POST['login_submit'])) {
        $success = handleLogin();
        if ($success) {
            $response['success'] = true;
            $response['message'] = getSuccess();
            clearSuccess();
        } else {
            $response['errors'] = $_SESSION['errors'] ?? [];
            clearErrors();
        }
    } elseif (isset($_POST['register_submit'])) {
        $success = handleRegister();
        if ($success) {
            $response['success'] = true;
            $response['message'] = getSuccess();
            clearSuccess();
        } else {
            $response['errors'] = $_SESSION['errors'] ?? [];
            clearErrors();
        }
    }
    
    echo json_encode($response);
    exit();
}
?>