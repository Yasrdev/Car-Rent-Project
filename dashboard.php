<?php include_once('./includes/header.php'); ?>
<?php
require_once('./config/config.php');

// Vérifier si l'utilisateur est connecté et a les droits d'accès
if (!isLoggedIn() || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'manager')) {
    header('Location: index.php');
    exit();
}

$userName = $_SESSION['first_name'] ?? '';
$userRole = $_SESSION['role'] ?? '';
$userEmail = $_SESSION['email'] ?? '';

// ===== GESTION DES VOITURES - SIMILAIRE À CARS_VIEW.PHP =====

// Paramètres de pagination
$carsPerPage = 8;
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($currentPage - 1) * $carsPerPage;

// Récupérer le nombre total de voitures
$totalCars = 0;
try {
    $countSql = "SELECT COUNT(*) as total FROM cars c";
    $countStmt = $pdo->query($countSql);
    $totalCars = $countStmt->fetch()['total'];
} catch (PDOException $e) {
    error_log("Erreur lors du comptage des voitures: " . $e->getMessage());
}

// Calculer le nombre total de pages
$totalPages = ceil($totalCars / $carsPerPage);

// Récupérer les catégories pour le filtre
$categories = [];
try {
    $stmt = $pdo->query("SELECT id, name FROM categories ORDER BY name");
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    // Gérer l'erreur silencieusement
}

// Construire la requête SQL avec filtres
$whereConditions = [];
$params = [];

// Filtre par catégorie
if (isset($_GET['category']) && !empty($_GET['category'])) {
    $whereConditions[] = "c.category_id = ?";
    $params[] = $_GET['category'];
}

// Filtre par statut
if (isset($_GET['status']) && !empty($_GET['status'])) {
    $whereConditions[] = "c.status = ?";
    $params[] = $_GET['status'];
}

// Filtre par prix
if (isset($_GET['price_min']) && !empty($_GET['price_min'])) {
    $whereConditions[] = "c.price >= ?";
    $params[] = $_GET['price_min'];
}

if (isset($_GET['price_max']) && !empty($_GET['price_max'])) {
    $whereConditions[] = "c.price <= ?";
    $params[] = $_GET['price_max'];
}

// Recherche par titre
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $whereConditions[] = "c.title LIKE ?";
    $params[] = '%' . $_GET['search'] . '%';
}

// Construire la requête finale avec pagination
$sql = "SELECT c.*, cat.name as category_name, cat.icon 
        FROM cars c 
        LEFT JOIN categories cat ON c.category_id = cat.id";

if (!empty($whereConditions)) {
    $sql .= " WHERE " . implode(" AND ", $whereConditions);
}

$sql .= " ORDER BY c.created_at DESC LIMIT ? OFFSET ?";
$params[] = $carsPerPage;
$params[] = $offset;

// Exécuter la requête
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $cars = $stmt->fetchAll();
} catch (PDOException $e) {
    $cars = [];
    error_log("Erreur lors de la récupération des voitures: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - ModernSite</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Styles spécifiques au dashboard */
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, var(--primary-color), var(--secondary-color));
            color: white;
            height: 100vh;
            position: fixed;
            overflow-y: auto;
            transition: var(--transition);
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            z-index: 1000;
        }

        .sidebar-header {
            padding: 25px 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .profile {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 20px;
        }

        .profile-img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid rgba(255, 255, 255, 0.2);
            margin-bottom: 15px;
        }

        .profile-name {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .profile-role {
            font-size: 0.9rem;
            opacity: 0.8;
            background: rgba(255, 255, 255, 0.1);
            padding: 5px 15px;
            border-radius: 20px;
        }

        .sidebar-menu {
            padding: 20px 0;
        }

        .menu-item {
            padding: 15px 25px;
            display: flex;
            align-items: center;
            cursor: pointer;
            transition: var(--transition);
            border-left: 4px solid transparent;
        }

        .menu-item:hover {
            background-color: rgba(255, 255, 255, 0.1);
            border-left: 4px solid white;
        }

        .menu-item.active {
            background-color: rgba(255, 255, 255, 0.15);
            border-left: 4px solid white;
        }

        .menu-item i {
            margin-right: 15px;
            font-size: 1.2rem;
            width: 20px;
            text-align: center;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 30px;
            transition: var(--transition);
        }

        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        }

        .page-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--dark-color);
        }

        .user-actions {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .notification-icon {
            position: relative;
            cursor: pointer;
        }

        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: #e63946;
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .content-section {
            display: none;
            background-color: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }

        .content-section.active {
            display: block;
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .section-title {
            font-size: 1.5rem;
            margin-bottom: 20px;
            color: var(--primary-color);
            display: flex;
            align-items: center;
        }

        .section-title i {
            margin-right: 10px;
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
            transition: var(--transition);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 1.5rem;
            color: white;
        }

        .stat-info h3 {
            font-size: 1.8rem;
            margin-bottom: 5px;
        }

        .stat-info p {
            color: var(--text-light);
            font-size: 0.9rem;
        }

        .chart-container {
            height: 300px;
            margin-top: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
        }

        /* ===== STYLES POUR LA GESTION DES VOITURES ===== */
        .filter-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            border-radius: 10px;
        }

        .filter-header {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .filter-header h2 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            color: white;
            font-weight: 700;
        }

        .filter-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .filter-form {
            width: 100%;
        }

        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            align-items: end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-label {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--dark-color);
            font-size: 0.9rem;
        }

        .search-input-container {
            position: relative;
        }

        .search-input {
            width: 100%;
            padding: 10px 40px 10px 15px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 14px;
            transition: var(--transition);
            background: white;
        }

        .search-input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
            outline: none;
        }

        .search-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }

        .select-container {
            position: relative;
        }

        .filter-select {
            width: 100%;
            padding: 10px 40px 10px 15px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 14px;
            background: white;
            appearance: none;
            cursor: pointer;
            transition: var(--transition);
        }

        .filter-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
            outline: none;
        }

        .select-arrow {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            pointer-events: none;
        }

        .price-input-container {
            position: relative;
        }

        .price-input {
            width: 100%;
            padding: 10px 40px 10px 15px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 14px;
            transition: var(--transition);
        }

        .price-input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
            outline: none;
        }

        .price-currency {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            font-weight: 600;
        }

        .filter-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .filter-btn, .clear-btn {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 10px 15px;
            white-space: nowrap;
        }

        /* Grille des voitures dans le dashboard */
        .cars-grid-dashboard {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .car-card-dashboard {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            border: 1px solid #e1e5e9;
        }

        .car-card-dashboard:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .car-image-container-dashboard {
            position: relative;
            overflow: hidden;
            height: 180px;
        }

        .car-image-dashboard {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .car-card-dashboard:hover .car-image-dashboard {
            transform: scale(1.05);
        }

        .status-badge-dashboard {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            z-index: 2;
        }

        .status-badge-dashboard.available {
            background: #27ae60;
            color: white;
        }

        .status-badge-dashboard.unavailable {
            background: #e74c3c;
            color: white;
        }

        .car-card-content-dashboard {
            padding: 1.2rem;
        }

        .car-category-dashboard {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #6c757d;
            font-size: 0.85rem;
            margin-bottom: 0.5rem;
        }

        .category-icon-dashboard {
            font-size: 0.9rem;
            color: var(--primary-color);
        }

        .car-title-dashboard {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
            line-height: 1.3;
        }

        .car-description-dashboard {
            color: #6c757d;
            line-height: 1.5;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }

        .car-features-dashboard {
            display: flex;
            gap: 0.8rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }

        .feature-dashboard {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.8rem;
            color: #6c757d;
        }

        .feature-dashboard i {
            color: var(--primary-color);
            width: 14px;
        }

        .car-price-dashboard {
            display: flex;
            align-items: baseline;
            gap: 0.25rem;
            margin-bottom: 1rem;
        }

        .price-amount-dashboard {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--dark-color);
        }

        .price-period-dashboard {
            color: #6c757d;
            font-size: 0.85rem;
        }

        .car-actions-dashboard {
            display: flex;
            gap: 0.5rem;
        }

        .btn-edit, .btn-delete, .btn-status {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            padding: 8px 12px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            font-size: 0.85rem;
        }

        .btn-edit {
            background: #3498db;
            color: white;
        }

        .btn-edit:hover {
            background: #2980b9;
        }

        .btn-delete {
            background: #e74c3c;
            color: white;
        }

        .btn-delete:hover {
            background: #c0392b;
        }

        .btn-status {
            background: #2ecc71;
            color: white;
        }

        .btn-status:hover {
            background: #27ae60;
        }

        /* Pagination pour le dashboard */
        .pagination-dashboard {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 1rem;
            margin-top: 2rem;
            flex-wrap: wrap;
        }

        .pagination-btn-dashboard {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.6rem 1.2rem;
            border: 2px solid #e1e5e9;
            background: white;
            border-radius: 6px;
            cursor: pointer;
            transition: var(--transition);
            font-weight: 600;
            color: var(--dark-color);
            font-size: 0.9rem;
        }

        .pagination-btn-dashboard:hover:not(.disabled) {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }

        .pagination-btn-dashboard.disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .pagination-numbers-dashboard {
            display: flex;
            gap: 0.4rem;
            flex-wrap: wrap;
            justify-content: center;
        }

        .pagination-number-dashboard {
            padding: 0.6rem 0.8rem;
            border: 2px solid #e1e5e9;
            border-radius: 6px;
            cursor: pointer;
            transition: var(--transition);
            font-weight: 600;
            min-width: 40px;
            text-align: center;
            color: var(--dark-color);
            font-size: 0.9rem;
        }

        .pagination-number-dashboard:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }

        .pagination-number-dashboard.active {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        .pagination-dots-dashboard {
            padding: 0.6rem 0.4rem;
            color: #6c757d;
        }

        .pagination-info-dashboard {
            text-align: center;
            margin-top: 1rem;
            color: #6c757d;
            font-size: 0.85rem;
        }

        /* Tableaux améliorés */
        .table-container {
            overflow-x: auto;
            margin-top: 1rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #e1e5e9;
        }

        th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: var(--dark-color);
        }

        tr:hover {
            background-color: #f8f9fa;
        }

        /* Responsive */
        @media (max-width: 992px) {
            .sidebar {
                width: 80px;
                overflow: visible;
            }

            .sidebar-header {
                padding: 20px 10px;
            }

            .profile-name, .profile-role {
                display: none;
            }

            .menu-item span {
                display: none;
            }

            .menu-item i {
                margin-right: 0;
                font-size: 1.5rem;
            }

            .main-content {
                margin-left: 80px;
            }

            .filter-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .stats-container {
                grid-template-columns: 1fr;
            }
            
            .main-content {
                padding: 15px;
            }

            .cars-grid-dashboard {
                grid-template-columns: 1fr;
            }

            .car-actions-dashboard {
                flex-direction: column;
            }
        }
    </style>
</head>
<body class="dashboard-page">
    <!-- Dashboard Container -->
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <div class="profile">
                    <img src="https://randomuser.me/api/portraits/men/32.jpg" alt="Profile" class="profile-img">
                    <div class="profile-name"><?php echo htmlspecialchars($userName); ?></div>
                    <div class="profile-role"><?php echo ucfirst($userRole); ?></div>
                </div>
            </div>
            <div class="sidebar-menu">
                <div class="menu-item active" data-target="dashboard">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Tableau de bord</span>
                </div>
                <div class="menu-item" data-target="users">
                    <i class="fas fa-users"></i>
                    <span>Utilisateurs</span>
                </div>
                <div class="menu-item" data-target="employees">
                    <i class="fas fa-user-tie"></i>
                    <span>Employés</span>
                </div>
                <div class="menu-item" data-target="cars">
                    <i class="fas fa-car"></i>
                    <span>Voitures</span>
                </div>
                <div class="menu-item" data-target="statistics">
                    <i class="fas fa-chart-bar"></i>
                    <span>Statistiques</span>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="dashboard-header">
                <h1 class="page-title" id="page-title">Tableau de bord</h1>
                <div class="user-actions">
                    <div class="notification-icon">
                        <i class="fas fa-bell fa-lg"></i>
                        <span class="notification-badge">3</span>
                    </div>
                    <div class="user-profile">
                        <img src="https://randomuser.me/api/portraits/men/32.jpg" alt="Profile" style="width: 40px; height: 40px; border-radius: 50%;">
                    </div>
                </div>
            </div>

            <!-- Dashboard Section -->
            <div class="content-section active" id="dashboard">
                <h2 class="section-title"><i class="fas fa-tachometer-alt"></i> Vue d'ensemble</h2>
                
                <div class="stats-container">
                    <div class="stat-card">
                        <div class="stat-icon" style="background-color: #3498db;">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-info">
                            <h3>1,254</h3>
                            <p>Utilisateurs actifs</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background-color: #2ecc71;">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <div class="stat-info">
                            <h3>86</h3>
                            <p>Employés</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background-color: #e74c3c;">
                            <i class="fas fa-car"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $totalCars; ?></h3>
                            <p>Voitures totales</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background-color: #9b59b6;">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="stat-info">
                            <h3>24%</h3>
                            <p>Croissance ce mois</p>
                        </div>
                    </div>
                </div>
                
                <div class="chart-container">
                    <canvas id="dashboardChart"></canvas>
                </div>
            </div>

            <!-- Users Section -->
            <div class="content-section" id="users">
                <h2 class="section-title"><i class="fas fa-users"></i> Gestion des utilisateurs</h2>
                
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th>Email</th>
                                <th>Rôle</th>
                                <th>Date d'inscription</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Marie Martin</td>
                                <td>marie.martin@example.com</td>
                                <td>Utilisateur</td>
                                <td>15/03/2023</td>
                                <td>
                                    <button class="btn btn-secondary">Modifier</button>
                                    <button class="btn btn-primary">Désactiver</button>
                                </td>
                            </tr>
                            <tr>
                                <td>Pierre Dubois</td>
                                <td>pierre.dubois@example.com</td>
                                <td>Administrateur</td>
                                <td>22/01/2023</td>
                                <td>
                                    <button class="btn btn-secondary">Modifier</button>
                                    <button class="btn btn-primary">Désactiver</button>
                                </td>
                            </tr>
                            <tr>
                                <td>Sophie Lambert</td>
                                <td>sophie.lambert@example.com</td>
                                <td>Utilisateur</td>
                                <td>10/04/2023</td>
                                <td>
                                    <button class="btn btn-secondary">Modifier</button>
                                    <button class="btn btn-primary">Désactiver</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Employees Section -->
            <div class="content-section" id="employees">
                <h2 class="section-title"><i class="fas fa-user-tie"></i> Gestion des employés</h2>
                
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th>Poste</th>
                                <th>Département</th>
                                <th>Date d'embauche</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Thomas Moreau</td>
                                <td>Développeur</td>
                                <td>IT</td>
                                <td>12/02/2022</td>
                                <td>
                                    <button class="btn btn-secondary">Modifier</button>
                                    <button class="btn btn-primary">Voir détails</button>
                                </td>
                            </tr>
                            <tr>
                                <td>Julie Petit</td>
                                <td>Designer</td>
                                <td>Marketing</td>
                                <td>05/08/2021</td>
                                <td>
                                    <button class="btn btn-secondary">Modifier</button>
                                    <button class="btn btn-primary">Voir détails</button>
                                </td>
                            </tr>
                            <tr>
                                <td>David Leroy</td>
                                <td>Commercial</td>
                                <td>Ventes</td>
                                <td>20/11/2022</td>
                                <td>
                                    <button class="btn btn-secondary">Modifier</button>
                                    <button class="btn btn-primary">Voir détails</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Cars Section -->
            <div class="content-section" id="cars">
                <h2 class="section-title"><i class="fas fa-car"></i> Gestion des voitures</h2>
                
                <!-- Section de filtrage -->
                <section class="filter-section">
                    <div class="container">
                        <div class="filter-header">
                            <h2>Gestion du parc automobile</h2>
                            <p>Filtrez et gérez les véhicules de votre flotte</p>
                        </div>
                        
                        <div class="filter-card">
                            <form method="GET" action="dashboard.php" id="filter-form-dashboard" class="filter-form">
                                <input type="hidden" name="section" value="cars">
                                <div class="filter-grid">
                                    <!-- Recherche -->
                                    <div class="filter-group">
                                        <label class="filter-label">Recherche</label>
                                        <div class="search-input-container">
                                            <i class="fas fa-search search-icon"></i>
                                            <input type="text" class="search-input" name="search" placeholder="Marque, modèle..." 
                                                   value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                                        </div>
                                    </div>

                                    <!-- Catégorie -->
                                    <div class="filter-group">
                                        <label class="filter-label">Catégorie</label>
                                        <div class="select-container">
                                            <select class="filter-select" name="category">
                                                <option value="">Toutes les catégories</option>
                                                <?php foreach ($categories as $category): ?>
                                                    <option value="<?php echo $category['id']; ?>" 
                                                        <?php echo (isset($_GET['category']) && $_GET['category'] == $category['id']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($category['name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <i class="fas fa-chevron-down select-arrow"></i>
                                        </div>
                                    </div>

                                    <!-- Statut -->
                                    <div class="filter-group">
                                        <label class="filter-label">Statut</label>
                                        <div class="select-container">
                                            <select class="filter-select" name="status">
                                                <option value="">Tous les statuts</option>
                                                <option value="available" <?php echo (isset($_GET['status']) && $_GET['status'] == 'available') ? 'selected' : ''; ?>>Disponible</option>
                                                <option value="unavailable" <?php echo (isset($_GET['status']) && $_GET['status'] == 'unavailable') ? 'selected' : ''; ?>>Réservé</option>
                                            </select>
                                            <i class="fas fa-chevron-down select-arrow"></i>
                                        </div>
                                    </div>

                                    <!-- Prix Min -->
                                    <div class="filter-group">
                                        <label class="filter-label">Prix min</label>
                                        <div class="price-input-container">
                                            <input type="number" class="price-input" name="price_min" placeholder="0" 
                                                   value="<?php echo htmlspecialchars($_GET['price_min'] ?? ''); ?>">
                                            <span class="price-currency">€</span>
                                        </div>
                                    </div>

                                    <!-- Prix Max -->
                                    <div class="filter-group">
                                        <label class="filter-label">Prix max</label>
                                        <div class="price-input-container">
                                            <input type="number" class="price-input" name="price_max" placeholder="1000" 
                                                   value="<?php echo htmlspecialchars($_GET['price_max'] ?? ''); ?>">
                                            <span class="price-currency">€</span>
                                        </div>
                                    </div>

                                    <!-- Boutons -->
                                    <div class="filter-actions">
                                        <button type="submit" class="btn btn-primary filter-btn">
                                            <i class="fas fa-search"></i>
                                            <span>Rechercher</span>
                                        </button>
                                        <?php if (!empty($_GET)): ?>
                                            <a href="dashboard.php?section=cars" class="btn btn-secondary clear-btn">
                                                <i class="fas fa-times"></i>
                                                Effacer
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </section>

                <!-- Information de pagination -->
                <div class="pagination-info-dashboard">
                    Affichage des voitures <?php echo $offset + 1; ?> à <?php echo min($offset + count($cars), $totalCars); ?> sur <?php echo $totalCars; ?> au total
                </div>

                <!-- Grille des voitures -->
                <div class="cars-grid-dashboard">
                    <?php if (empty($cars)): ?>
                        <div class="no-cars-found" style="grid-column: 1 / -1; text-align: center; padding: 3rem 2rem;">
                            <div class="no-cars-icon">
                                <i class="fas fa-car-side" style="font-size: 3rem; color: #bdc3c7;"></i>
                            </div>
                            <h3 style="color: var(--dark-color); margin-bottom: 1rem;">Aucune voiture trouvée</h3>
                            <p style="color: #6c757d;">Essayez de modifier vos critères de recherche</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($cars as $car): ?>
                            <div class="car-card-dashboard">
                                <div class="car-image-container-dashboard">
                                    <img src="<?php echo htmlspecialchars($car['image_url'] ?: 'https://picsum.photos/400/300?random=' . $car['id']); ?>" 
                                         alt="<?php echo htmlspecialchars($car['title']); ?>" 
                                         class="car-image-dashboard">
                                    <div class="status-badge-dashboard <?php echo $car['status'] === 'available' ? 'available' : 'unavailable'; ?>">
                                        <?php echo $car['status'] === 'available' ? 'Disponible' : 'Réservé'; ?>
                                    </div>
                                </div>
                                <div class="car-card-content-dashboard">
                                    <div class="car-category-dashboard">
                                        <?php if (!empty($car['icon'])): ?>
                                            <i class="<?php echo htmlspecialchars($car['icon']); ?> category-icon-dashboard"></i>
                                        <?php endif; ?>
                                        <span><?php echo htmlspecialchars($car['category_name'] ?? 'Non catégorisé'); ?></span>
                                    </div>
                                    <h3 class="car-title-dashboard"><?php echo htmlspecialchars($car['title']); ?></h3>
                                    <p class="car-description-dashboard"><?php echo htmlspecialchars(substr($car['description'] ?? '', 0, 100)); ?>...</p>
                                    
                                    <div class="car-features-dashboard">
                                        <div class="feature-dashboard">
                                            <i class="fas fa-user-friends"></i>
                                            <span><?php echo htmlspecialchars($car['seats'] ?? '4'); ?> places</span>
                                        </div>
                                        <div class="feature-dashboard">
                                            <i class="fas fa-gas-pump"></i>
                                            <span><?php echo htmlspecialchars($car['fuel_type'] ?? 'Essence'); ?></span>
                                        </div>
                                        <div class="feature-dashboard">
                                            <i class="fas fa-cogs"></i>
                                            <span><?php echo htmlspecialchars($car['transmission'] ?? 'Manuelle'); ?></span>
                                        </div>
                                    </div>
                                    
                                    <div class="car-price-dashboard">
                                        <span class="price-amount-dashboard"><?php echo number_format($car['price'], 0, ',', ' '); ?></span>
                                        <span class="price-period-dashboard">€ / jour</span>
                                    </div>
                                    
                                    <div class="car-actions-dashboard">
                                        <button class="btn-edit" onclick="editCar(<?php echo $car['id']; ?>)">
                                            <i class="fas fa-edit"></i>
                                            Modifier
                                        </button>
                                        <button class="btn-status" onclick="toggleStatus(<?php echo $car['id']; ?>)">
                                            <i class="fas fa-sync-alt"></i>
                                            Statut
                                        </button>
                                        <button class="btn-delete" onclick="deleteCar(<?php echo $car['id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                            Supprimer
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="pagination-dashboard">
                        <!-- Bouton Précédent -->
                        <a href="?section=cars&page=<?php echo $currentPage - 1; ?><?php echo !empty($_GET) ? '&' . http_build_query(array_diff_key($_GET, ['page' => ''])) : ''; ?>" 
                           class="pagination-btn-dashboard <?php echo $currentPage <= 1 ? 'disabled' : ''; ?>">
                            <i class="fas fa-chevron-left"></i>
                            Précédent
                        </a>

                        <!-- Numéros de page -->
                        <div class="pagination-numbers-dashboard">
                            <?php
                            $startPage = max(1, $currentPage - 2);
                            $endPage = min($totalPages, $currentPage + 2);
                            
                            if ($startPage > 1) {
                                echo '<a href="?section=cars&page=1' . (!empty($_GET) ? '&' . http_build_query(array_diff_key($_GET, ['page' => ''])) : '') . '" class="pagination-number-dashboard">1</a>';
                                if ($startPage > 2) {
                                    echo '<span class="pagination-dots-dashboard">...</span>';
                                }
                            }
                            
                            for ($i = $startPage; $i <= $endPage; $i++) {
                                $activeClass = $i == $currentPage ? 'active' : '';
                                echo '<a href="?section=cars&page=' . $i . (!empty($_GET) ? '&' . http_build_query(array_diff_key($_GET, ['page' => ''])) : '') . '" class="pagination-number-dashboard ' . $activeClass . '">' . $i . '</a>';
                            }
                            
                            if ($endPage < $totalPages) {
                                if ($endPage < $totalPages - 1) {
                                    echo '<span class="pagination-dots-dashboard">...</span>';
                                }
                                echo '<a href="?section=cars&page=' . $totalPages . (!empty($_GET) ? '&' . http_build_query(array_diff_key($_GET, ['page' => ''])) : '') . '" class="pagination-number-dashboard">' . $totalPages . '</a>';
                            }
                            ?>
                        </div>

                        <!-- Bouton Suivant -->
                        <a href="?section=cars&page=<?php echo $currentPage + 1; ?><?php echo !empty($_GET) ? '&' . http_build_query(array_diff_key($_GET, ['page' => ''])) : ''; ?>" 
                           class="pagination-btn-dashboard <?php echo $currentPage >= $totalPages ? 'disabled' : ''; ?>">
                            Suivant
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Statistics Section -->
            <div class="content-section" id="statistics">
                <h2 class="section-title"><i class="fas fa-chart-bar"></i> Statistiques</h2>
                
                <div class="stats-container">
                    <div class="stat-card">
                        <div class="stat-icon" style="background-color: #3498db;">
                            <i class="fas fa-chart-pie"></i>
                        </div>
                        <div class="stat-info">
                            <h3>75%</h3>
                            <p>Taux d'occupation</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background-color: #2ecc71;">
                            <i class="fas fa-euro-sign"></i>
                        </div>
                        <div class="stat-info">
                            <h3>12,450€</h3>
                            <p>Revenus ce mois</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background-color: #e74c3c;">
                            <i class="fas fa-car-crash"></i>
                        </div>
                        <div class="stat-info">
                            <h3>3</h3>
                            <p>Incidents ce mois</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background-color: #f39c12;">
                            <i class="fas fa-star"></i>
                        </div>
                        <div class="stat-info">
                            <h3>4.8/5</h3>
                            <p>Satisfaction client</p>
                        </div>
                    </div>
                </div>
                
                <div class="chart-container">
                    <canvas id="statsChart"></canvas>
                </div>
            </div>
        </div>
    </div>
<script src="./assets/js/main.js"></script>
    <script>
        // Navigation du dashboard
        document.addEventListener('DOMContentLoaded', function() {
            const menuItems = document.querySelectorAll('.menu-item');
            const contentSections = document.querySelectorAll('.content-section');
            const pageTitle = document.getElementById('page-title');
            
            // Gérer les paramètres d'URL pour la section
            const urlParams = new URLSearchParams(window.location.search);
            const sectionParam = urlParams.get('section');
            
            menuItems.forEach(item => {
                item.addEventListener('click', function() {
                    const target = this.getAttribute('data-target');
                    
                    // Mettre à jour l'URL sans recharger la page
                    const url = new URL(window.location);
                    url.searchParams.set('section', target);
                    window.history.pushState({}, '', url);
                    
                    // Mettre à jour l'affichage
                    updateActiveSection(target);
                });
            });
            
            // Fonction pour mettre à jour la section active
            function updateActiveSection(target) {
                // Mettre à jour les éléments de menu
                menuItems.forEach(item => {
                    item.classList.remove('active');
                    if (item.getAttribute('data-target') === target) {
                        item.classList.add('active');
                    }
                });
                
                // Mettre à jour les sections de contenu
                contentSections.forEach(section => {
                    section.classList.remove('active');
                    if (section.id === target) {
                        section.classList.add('active');
                    }
                });
                
                // Mettre à jour le titre de la page
                const sectionTitles = {
                    'dashboard': 'Tableau de bord',
                    'users': 'Gestion des utilisateurs',
                    'employees': 'Gestion des employés',
                    'cars': 'Gestion des voitures',
                    'statistics': 'Statistiques'
                };
                
                pageTitle.textContent = sectionTitles[target] || 'Tableau de bord';
            }
            
            // Initialiser avec la section de l'URL ou le tableau de bord par défaut
            const initialSection = sectionParam || 'dashboard';
            updateActiveSection(initialSection);
        });

    </script>
</body>
</html>
