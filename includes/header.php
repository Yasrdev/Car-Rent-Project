<?php
require_once('./config/config.php');
// Gestion des requêtes AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    require_once('./config/config.php');
    
    header('Content-Type: application/json');
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
// Initialisation des variables
$isLoggedIn = isLoggedIn();
$role = $_SESSION['role'] ?? '';
$userName = $_SESSION['first_name'] ?? '';

// Traitement des formulaires - UNIQUEMENT pour la soumission normale
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['ajax'])) {
    handleAuthForms();
}

function handleAuthForms() {
    if (isset($_POST['login_submit'])) {
        handleLogin();
    } elseif (isset($_POST['register_submit'])) {
        handleRegister();
    }
}

function handleLogin() {
    global $pdo;
    
    $email = cleanInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validation
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        addError('login', 'Email invalide.');
        return false;
    }
    
    if (empty($password)) {
        addError('login', 'Le mot de passe est requis.');
        return false;
    }
    
    // Vérification des identifiants
    try {
        $stmt = $pdo->prepare("SELECT id, first_name, last_name, email, password, status, role FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            if ($user['status'] !== 'active') {
                addError('login', 'Votre compte est désactivé.');
                return false;
            }
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['last_name'] = $user['last_name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['status'] = $user['status'];
            $_SESSION['role'] = $user['role'];

            addSuccess("Connexion réussie ! Bienvenue " . htmlspecialchars($user['first_name']));
            return true;
        } else {
            addError('login', 'Email ou mot de passe incorrect.');
            return false;
        }
    } catch (PDOException $e) {
        addError('login', 'Erreur lors de la connexion.');
        return false;
    }
}

function handleRegister() {
    global $pdo;
    $first_name = cleanInput($_POST['first_name'] ?? '');
    $last_name = cleanInput($_POST['last_name'] ?? '');
    $status = 'active';
    $role = 'user';
    $email = cleanInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    
    // Validation
    if (empty($first_name)) {
        addError('register', 'Le prénom est requis.');
        return false;
    }

    if (empty($last_name)) {
        addError('register', 'Le nom est requis.');
        return false;
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        addError('register', 'Email invalide.');
        return false;
    }
    
    if (empty($password) || strlen($password) < 6) {
        addError('register', 'Le mot de passe doit contenir au moins 6 caractères.');
        return false;
    }
    
    if ($password !== $password_confirm) {
        addError('register', 'Les mots de passe ne correspondent pas.');
        return false;
    }
    
    // Vérifier si l'email existe déjà
    try {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->fetch()) {
            addError('register', 'Cet email est déjà utilisé.');
            return false;
        }
    } catch (PDOException $e) {
        addError('register', 'Erreur lors de la vérification de l\'email.');
        return false;
    }
    
    // Insertion - CORRIGÉ pour correspondre à la nouvelle structure
    try {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Utilisation de date_creation au lieu de created_at
        $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, role, status, email, password, date_creation) VALUES (?, ?, ?, ?, ?, ?, NOW())");

        if ($stmt->execute([$first_name, $last_name, $role, $status, $email, $hashed_password])) {
            $_SESSION['user_id'] = $pdo->lastInsertId();
            $_SESSION['first_name'] = $first_name;
            $_SESSION['last_name'] = $last_name;
            $_SESSION['status'] = $status;
            $_SESSION['role'] = $role;
            $_SESSION['email'] = $email;
            addSuccess("Inscription réussie ! Bienvenue " . htmlspecialchars($first_name));
            return true;
        }
    } catch (PDOException $e) {
        addError('register', 'Erreur lors de l\'inscription.');
        return false;
    }
    
    return false;
}

// Nettoyer les messages après les avoir récupérés
$errors = $_SESSION['errors'] ?? [];
$success = getSuccess();

// Effacer les messages après les avoir récupérés pour éviter qu'ils persistent
clearErrors();
clearSuccess();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($PageName); ?> - Site Web Moderne</title>
    <!-- Bootstrap CSS -->
    <!-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"> -->
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- CSS Personnalisé (doit être après Bootstrap pour pouvoir le surcharger) -->
    <link rel="stylesheet" href="assets/css/style.css">
    <?php if (isset($PageName) && $PageName === 'dashboard'): ?>
        <link rel="stylesheet" href="assets/css/Dash_style.css">
    <?php endif; ?>
</head>
<body>
   <!-- HEADER -->
    <header>
        <div class="container header-container">
            <div class="logo">
                <a href="index.php">Modern<span>Site</span></a>
            </div>

            <nav class="desktop-nav">
                <ul>
                    <li><a href="index.php">Accueil</a></li>
                    <li><a href="cars_view.php">Voitures</a></li>
                    <li><a href="#contact">Contact</a></li>
                    <li><a href="#apropos">À propos</a></li>
                    <?php if ( $isLoggedIn &&($role === 'manager' || $role === 'admin')): ?>
                        <li><a href="dashboard.php">Dashboard</a></li>
                    <?php endif; ?>
                </ul>
            </nav>

            <div class="auth-buttons">
                <?php if ($isLoggedIn): ?>
                    <div class="user-dropdown">
                        <button class="user-dropdown-btn">
                            <i class="fas fa-user-circle"></i>
                            <span><?php echo htmlspecialchars($userName); ?></span>
                            <i class="fas fa-chevron-down dropdown-arrow"></i>
                        </button>
                        <div class="user-dropdown-content">
                            <a href="profile.php" class="dropdown-item">
                                <i class="fas fa-user"></i>
                                <span>Profil</span>
                            </a>
                            <a href="./modules/auth/logout.php" class="dropdown-item">
                                <i class="fas fa-sign-out-alt"></i>
                                <span>Déconnexion</span>
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <button class="btn btn-secondary" id="login-btn">Connexion</button>
                    <button class="btn btn-primary" id="register-btn">Inscription</button>
                <?php endif; ?>
            </div>

            <div class="burger-menu" id="burger-menu">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>

        <nav class="mobile-nav" id="mobile-nav">
            <ul>
                    <li><a href="index.php">Accueil</a></li>
                    <li><a href="cars_view.php">Voitures</a></li>
                    <li><a href="#contact">Contact</a></li>
                    <li><a href="#apropos">À propos</a></li>
                    <?php if ( $isLoggedIn &&($role === 'manager' || $role === 'admin')): ?>
                        <li><a href="dashboard.php">Dashboard</a></li>
                    <?php endif; ?>
            </ul>
            <div class="mobile-auth-buttons">
                <?php if ($isLoggedIn): ?>
                    <div class="mobile-user-menu">
                        <div class="mobile-user-info">
                            <i class="fas fa-user-circle"></i>
                            <span><?php echo htmlspecialchars($userName); ?></span>
                        </div>
                        <a href="profile.php" class="btn btn-secondary mobile-profile-btn">
                            <i class="fas fa-user"></i>
                            <span>Profil</span>
                        </a>
                        <a href="./modules/auth/logout.php" class="btn btn-primary mobile-logout-btn">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Déconnexion</span>
                        </a>
                    </div>
                <?php else: ?>
                    <button class="btn btn-secondary" id="mobile-login-btn">Connexion</button>
                    <button class="btn btn-primary" id="mobile-register-btn">Inscription</button>
                <?php endif; ?>
            </div>
        </nav>

        <div class="nav-overlay" id="nav-overlay"></div>
    </header>