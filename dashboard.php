<?php
require_once('./config/config.php');
$PageName = 'dashboard';
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
<?php include_once('./includes/header.php'); ?>
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
                            <h3>
                                <?php $totalUser = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'user' and status = 'active'");
                                      $stats['total'] = $totalUser->fetch()['total']; 
                                      echo $stats['total']; 
                                ?>
                            </h3>
                            <p>Utilisateurs actifs</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background-color: #2ecc71;">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php $totalEEmp = $pdo->query("SELECT COUNT(*) as total FROM users WHERE status = 'active' AND (role = 'admin' OR role = 'manager')");
                                      $stats['total'] = $totalEEmp->fetch()['total']; 
                                      echo $stats['total']; 
                                ?></h3>
                            <p>Employés actifs</p>
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
                            <h3><?php $totalCarsRent = $pdo->query("SELECT COUNT(*) as total FROM cars WHERE status = 'available'");
                                      $stats['total'] = $totalCarsRent->fetch()['total']; 
                                      echo $stats['total']; 
                                ?> / <?php echo $totalCars; ?></h3>
                            <p>Croissance</p>
                        </div>
                    </div>
                </div>
                
                <div class="chart-container">
                    <canvas id="dashboardChart"></canvas>
                </div>
            </div>

           <!-- Users Section -->
    <div class="content-section" id="users">
        <div class="users-section-header">
            <h2 class="section-title"><i class="fas fa-users"></i> Gestion des utilisateurs</h2>
            <div class="users-stats">
                <?php
                // Statistiques des utilisateurs
                $stats = [
                    'users' => 0,
                    'active' => 0,
                    'inactive' => 0
                ];
                
                try {
                    $totalStmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'user'");
                    $stats['total'] = $totalStmt->fetch()['total'];
                    
                    
                    $statusStmtactive = $pdo->query("SELECT status, COUNT(*) as countactive FROM users WHERE role = 'user' and status = 'active'");
                    $stats['active'] = $statusStmtactive->fetch()['countactive'];

                    $statusStmtinactive = $pdo->query("SELECT status, COUNT(*) as countinactive FROM users WHERE role = 'user' and status = 'inactive'");
                    $stats['inactive'] = $statusStmtinactive->fetch()['countinactive'];

                } catch (PDOException $e) {
                    error_log("Erreur statistiques utilisateurs: " . $e->getMessage());
                }
                ?>
                
                <div class="stat-badge total">
                    <i class="fas fa-users"></i>
                    <span>Total: <?php echo $stats['users']; ?></span>
                </div>
                <div class="stat-badge active">
                    <i class="fas fa-user"></i>
                    <span>actif: <?php echo $stats['active']; ?></span>
                </div>
                <div class="stat-badge inactive">
                    <i class="fas fa-user"></i>
                    <span>Inactif: <?php echo $stats['inactive']; ?></span>
                </div>
            </div>
        </div>
        
        <?php
        // Récupérer tous les utilisateurs
        $users = [];
        try {
            $usersStmt = $pdo->query("SELECT id, first_name, last_name, email, role, status, date_creation FROM users WHERE role = 'user' ORDER BY date_creation DESC");
            $users = $usersStmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des utilisateurs: " . $e->getMessage());
        }
        ?>
        
        <!-- Vue Desktop/Tablette (Tableau) -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Prénom</th>
                        <th>Nom</th>
                        <th>Email</th>
                        <th>Rôle</th>
                        <th>Statut</th>
                        <th>Date d'inscription</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 3rem;">
                                <i class="fas fa-users" style="font-size: 3rem; color: #bdc3c7; margin-bottom: 1rem;"></i>
                                <h3 style="color: var(--dark-color); margin-bottom: 0.5rem;">Aucun utilisateur trouvé</h3>
                                <p style="color: #6c757d;">Aucun utilisateur n'est inscrit pour le moment</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><strong>#<?php echo $user['id']; ?></strong></td>
                                <td><?php echo htmlspecialchars($user['first_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <span class="role-badge <?php echo $user['role']; ?>">
                                        <?php 
                                        $roleLabels = [
                                            'admin' => 'Admin',
                                            'manager' => 'Manager',
                                            'user' => 'User'
                                        ];
                                        echo $roleLabels[$user['role']] ?? $user['role']; 
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <span style="display: inline-block; padding: 0.4rem 0.8rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; min-width: 80px; text-align: center; color: white; background: <?php echo $user['status'] === 'active' ? '#27ae60' : '#95a5a6'; ?>">
                                        <?php echo $user['status'] === 'active' ? 'Actif' : 'Inactif'; ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($user['date_creation'])); ?></td>
                                <td>
                                    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                        <button class="btn-view-user" 
                                                data-user-id="<?php echo $user['id']; ?>"
                                                data-user-data='<?php echo json_encode($user); ?>'>
                                            <i class="fas fa-edit"></i> Modifier
                                        </button>
                                        <button class="btn-delete-user" 
                                                data-user-id="<?php echo $user['id']; ?>"
                                                data-user-name="<?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>">
                                            <i class="fas fa-trash"></i> Supprimer
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    
    <!-- Vue Mobile (Cartes) -->
    <div class="users-grid-mobile">
        <?php if (empty($users)): ?>
            <div style="text-align: center; padding: 3rem;">
                <i class="fas fa-users" style="font-size: 4rem; color: #bdc3c7; margin-bottom: 1rem;"></i>
                <h3 style="color: var(--dark-color); margin-bottom: 0.5rem;">Aucun utilisateur</h3>
                <p style="color: #6c757d;">Aucun utilisateur trouvé dans la base de données</p>
            </div>
        <?php else: ?>
            <?php foreach ($users as $user): ?>
                <div class="user-card-mobile">
                    <div class="user-header-mobile">
                        <div class="user-info-mobile">
                            <h4><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h4>
                            <div class="user-email-mobile"><?php echo htmlspecialchars($user['email']); ?></div>
                        </div>
                        <div class="user-meta-mobile">
                            <span class="role-badge <?php echo $user['role']; ?>">
                                <?php 
                                $roleLabels = [
                                    'admin' => 'Admin',
                                    'manager' => 'Manager',
                                    'user' => 'User'
                                ];
                                echo $roleLabels[$user['role']] ?? $user['role']; 
                                ?>
                            </span>
                            <span class="status-badge <?php echo $user['status']; ?>">
                                <?php echo $user['status'] === 'active' ? 'Actif' : 'Inactif'; ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="user-details-mobile">
                        <div class="detail-item-mobile">
                            <span class="detail-label-mobile">ID Utilisateur</span>
                            <span class="detail-value-mobile">#<?php echo $user['id']; ?></span>
                        </div>
                        <div class="detail-item-mobile">
                            <span class="detail-label-mobile">Date d'inscription</span>
                            <span class="detail-value-mobile"><?php echo date('d/m/Y H:i', strtotime($user['date_creation'])); ?></span>
                        </div>
                    </div> 
                    <div style="display: flex; flex-direction: column; gap: 0.5rem; margin-top: 1rem;">
                        <button class="btn-view-user" 
                                data-user-id="<?php echo $user['id']; ?>"
                                data-user-data='<?php echo json_encode($user); ?>'>
                            <i class="fas fa-edit"></i> Modifier l'utilisateur
                        </button>
                        <button class="btn-delete-user" 
                                data-user-id="<?php echo $user['id']; ?>"
                                data-user-name="<?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>">
                            <i class="fas fa-trash"></i> Supprimer l'utilisateur
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        </div>
    </div>

            <!-- Employees Section -->
            <div class="content-section" id="employees">
                <div class="users-section-header">
                    <h2 class="section-title"><i class="fas fa-user-tie"></i> Gestion des employés</h2>
                    <div class="users-stats">
                        <?php
                        // Statistiques des employés (admin + manager)
                        $employeeStats = [
                            'total' => 0,
                            'admins' => 0,
                            'managers' => 0,
                            'active' => 0,
                            'inactive' => 0
                        ];
                        
                        try {
                            $totalEmpStmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role IN ('admin', 'manager')");
                            $employeeStats['total'] = $totalEmpStmt->fetch()['total'];
                            
                            $roleEmpStmt = $pdo->query("SELECT role, COUNT(*) as count FROM users WHERE role IN ('admin', 'manager') GROUP BY role");
                            while ($role = $roleEmpStmt->fetch()) {
                                $employeeStats[$role['role'] . 's'] = $role['count'];
                            }
                            
                            $statusEmpStmt = $pdo->query("SELECT status, COUNT(*) as count FROM users WHERE role IN ('admin', 'manager') GROUP BY status");
                            while ($status = $statusEmpStmt->fetch()) {
                                $employeeStats[$status['status']] = $status['count'];
                            }
                        } catch (PDOException $e) {
                            error_log("Erreur statistiques employés: " . $e->getMessage());
                        }
                        ?>
                        
                        <div class="stat-badge total">
                            <i class="fas fa-users"></i>
                            <span>Total: <?php echo $employeeStats['total']; ?></span>
                        </div>
                        <div class="stat-badge admins">
                            <i class="fas fa-crown"></i>
                            <span>Admins: <?php echo $employeeStats['admins']; ?></span>
                        </div>
                        <div class="stat-badge managers">
                            <i class="fas fa-user-tie"></i>
                            <span>Managers: <?php echo $employeeStats['managers']; ?></span>
                        </div>
                        <div class="stat-badge active">
                            <i class="fas fa-check-circle"></i>
                            <span>Actifs: <?php echo $employeeStats['active']; ?></span>
                        </div>
                    </div>
                </div>
                
                <?php
                // Récupérer seulement les employés (admin + manager)
                $employees = [];
                try {
                    $employeesStmt = $pdo->query("SELECT id, first_name, last_name, email, role, status, date_creation FROM users WHERE role IN ('admin', 'manager') ORDER BY role, date_creation DESC");
                    $employees = $employeesStmt->fetchAll();
                } catch (PDOException $e) {
                    error_log("Erreur lors de la récupération des employés: " . $e->getMessage());
                }
                ?>
                
                <!-- Vue Desktop/Tablette (Tableau) -->
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Prénom</th>
                                <th>Nom</th>
                                <th>Email</th>
                                <th>Rôle</th>
                                <th>Statut</th>
                                <th>Date d'inscription</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($employees)): ?>
                                <tr>
                                    <td colspan="8" style="text-align: center; padding: 3rem;">
                                        <i class="fas fa-user-tie" style="font-size: 3rem; color: #bdc3c7; margin-bottom: 1rem;"></i>
                                        <h3 style="color: var(--dark-color); margin-bottom: 0.5rem;">Aucun employé trouvé</h3>
                                        <p style="color: #6c757d;">Aucun administrateur ou manager n'est inscrit pour le moment</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($employees as $employee): ?>
                                    <tr>
                                        <td><strong>#<?php echo $employee['id']; ?></strong></td>
                                        <td><?php echo htmlspecialchars($employee['first_name']); ?></td>
                                        <td><?php echo htmlspecialchars($employee['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($employee['email']); ?></td>
                                        <td>
                                            <span class="role-badge <?php echo $employee['role']; ?>">
                                                <?php 
                                                $roleLabels = [
                                                    'admin' => 'Admin',
                                                    'manager' => 'Manager',
                                                    'user' => 'User'
                                                ];
                                                echo $roleLabels[$employee['role']] ?? $employee['role']; 
                                                ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span style="display: inline-block; padding: 0.4rem 0.8rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; min-width: 80px; text-align: center; color: white; background: <?php echo $employee['status'] === 'active' ? '#27ae60' : '#95a5a6'; ?>">
                                                <?php echo $employee['status'] === 'active' ? 'Actif' : 'Inactif'; ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($employee['date_creation'])); ?></td>
                                        <td>
                                            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                                <button class="btn-view-user" 
                                                        data-user-id="<?php echo $employee['id']; ?>"
                                                        data-user-data='<?php echo json_encode($employee); ?>'>
                                                    <i class="fas fa-edit"></i> Modifier
                                                </button>
                                                <button class="btn-delete-user" 
                                                        data-user-id="<?php echo $employee['id']; ?>"
                                                        data-user-name="<?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?>">
                                                    <i class="fas fa-trash"></i> Supprimer
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            
                <!-- Vue Mobile (Cartes) -->
                <div class="users-grid-mobile">
                    <?php if (empty($employees)): ?>
                        <div style="text-align: center; padding: 3rem;">
                            <i class="fas fa-user-tie" style="font-size: 4rem; color: #bdc3c7; margin-bottom: 1rem;"></i>
                            <h3 style="color: var(--dark-color); margin-bottom: 0.5rem;">Aucun employé</h3>
                            <p style="color: #6c757d;">Aucun administrateur ou manager trouvé</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($employees as $employee): ?>
                            <div class="user-card-mobile">
                                <div class="user-header-mobile">
                                    <div class="user-info-mobile">
                                        <h4><?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?></h4>
                                        <div class="user-email-mobile"><?php echo htmlspecialchars($employee['email']); ?></div>
                                    </div>
                                    <div class="user-meta-mobile">
                                        <span class="role-badge <?php echo $employee['role']; ?>">
                                            <?php 
                                            $roleLabels = [
                                                'admin' => 'Admin',
                                                'manager' => 'Manager',
                                                'user' => 'User'
                                            ];
                                            echo $roleLabels[$employee['role']] ?? $employee['role']; 
                                            ?>
                                        </span>
                                        <span class="status-badge <?php echo $employee['status']; ?>">
                                            <?php echo $employee['status'] === 'active' ? 'Actif' : 'Inactif'; ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="user-details-mobile">
                                    <div class="detail-item-mobile">
                                        <span class="detail-label-mobile">ID Employé</span>
                                        <span class="detail-value-mobile">#<?php echo $employee['id']; ?></span>
                                    </div>
                                    <div class="detail-item-mobile">
                                        <span class="detail-label-mobile">Date d'inscription</span>
                                        <span class="detail-value-mobile"><?php echo date('d/m/Y H:i', strtotime($employee['date_creation'])); ?></span>
                                    </div>
                                </div>
                                
                                <div style="display: flex; flex-direction: column; gap: 0.5rem; margin-top: 1rem;">
                                    <button class="btn-view-user" 
                                            data-user-id="<?php echo $employee['id']; ?>"
                                            data-user-data='<?php echo json_encode($employee); ?>'>
                                        <i class="fas fa-edit"></i> Modifier l'employé
                                    </button>
                                    <button class="btn-delete-user" 
                                            data-user-id="<?php echo $employee['id']; ?>"
                                            data-user-name="<?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?>">
                                        <i class="fas fa-trash"></i> Supprimer l'employé
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Cars Section -->
            <div class="content-section" id="cars">
                                <div class="users-section-header">
                    <h2 class="section-title"><i class="fas fa-car"></i> Gestion des voitures</h2>
                    <div class="users-stats">
                        <div class="stat-badge total">
                            <i class="fas fa-car"></i>
                            <span>Total: <?php echo $totalCars; ?></span>
                        </div>
                        <div class="stat-badge available">
                            <i class="fas fa-check-circle"></i>
                            <span>Disponibles: 
                                <?php 
                                $availableCars = $pdo->query("SELECT COUNT(*) as count FROM cars WHERE status = 'available'")->fetch()['count'];
                                echo $availableCars;
                                ?>
                            </span>
                        </div>
                        <div class="stat-badge reserved">
                            <i class="fas fa-times-circle"></i>
                            <span>Réservées: 
                                <?php 
                                $reservedCars = $pdo->query("SELECT COUNT(*) as count FROM cars WHERE status = 'unavailable'")->fetch()['count'];
                                echo $reservedCars;
                                ?>
                            </span>
                        </div>
                    </div>
                    <button class="btn btn-primary" id="add-car-btn">
                        <i class="fas fa-plus"></i>
                        Ajouter une voiture
                    </button>
                </div>
                
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
                </div><br>

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
                                    <img src="<?php echo htmlspecialchars($car['image_url']); ?>" 
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
                                        <button class="btn-edit" 
                                                data-car-id="<?php echo $car['id']; ?>"
                                                data-car-data='<?php echo json_encode($car); ?>'>
                                            <i class="fas fa-edit"></i>
                                            Modifier
                                        </button>
                                        <button class="btn-status" 
                                                data-car-id="<?php echo $car['id']; ?>"
                                                data-car-title="<?php echo htmlspecialchars($car['title']); ?>"
                                                data-car-current-status="<?php echo $car['status']; ?>">
                                            <i class="fas fa-sync-alt"></i>
                                            Statut
                                        </button>
                                        <button class="btn-delete" 
                                                data-car-id="<?php echo $car['id']; ?>"
                                                data-car-title="<?php echo htmlspecialchars($car['title']); ?>">
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

         <!-- Modal d'ajout voiture -->
    <div class="modal-overlay" id="car-add-modal">
        <div class="modal" style="max-width: 600px;">
            <div class="modal-header">
                <h3 class="modal-title">Ajouter une nouvelle voiture</h3>
                <button class="close-modal" id="close-car-add-modal">&times;</button>
            </div>
            
            <div class="modal-body">
                <form id="car-add-form" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="add-car-title">Titre *</label>
                        <input type="text" id="add-car-title" name="title" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="add-car-description">Description</label>
                        <textarea id="add-car-description" name="description" rows="4" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; resize: vertical;"></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="add-car-category">Catégorie *</label>
                            <div class="select-container">
                                <select id="add-car-category" name="category_id" required class="filter-select">
                                    <option value="">Sélectionner une catégorie</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>">
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <i class="fas fa-chevron-down select-arrow"></i>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="add-car-price">Prix (€/jour) *</label>
                            <input type="number" id="add-car-price" name="price" step="0.01" min="0" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="add-car-status">Statut *</label>
                        <div class="select-container">
                            <select id="add-car-status" name="status" required class="filter-select">
                                <option value="available">Disponible</option>
                                <option value="unavailable">Réservé</option>
                            </select>
                            <i class="fas fa-chevron-down select-arrow"></i>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="car-image">Image de la voiture *</label>
                        <div class="file-upload-container">
                            <input type="file" id="car-image" name="car_image" accept="image/*" required 
                                   style="width: 100%; padding: 10px; border: 2px dashed #ddd; border-radius: 4px; background: #f9f9f9;">
                            <div class="file-upload-info">
                                <small>Formats acceptés: JPG, PNG, GIF (Max: 2MB)</small>
                            </div>
                        </div>
                        <div id="image-preview" style="margin-top: 10px; display: none;">
                            <img id="preview-img" src="#" alt="Aperçu" style="max-width: 100%; max-height: 200px; border-radius: 4px;">
                        </div>
                    </div>
              
                    <div class="form-actions" style="display: flex; gap: 10px; margin-top: 20px;">
                        <button type="button" class="btn btn-secondary" id="cancel-car-add">Annuler</button>
                        <button type="submit" class="btn btn-primary" id="save-new-car">
                            <i class="fas fa-plus"></i> Ajouter la voiture
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>                    

        <!-- Modal de modification voiture -->
<div class="modal-overlay" id="car-edit-modal">
    <div class="modal" style="max-width: 600px;">
        <div class="modal-header">
            <h3 class="modal-title" id="car-edit-modal-title">Modifier la voiture</h3>
            <button class="close-modal" id="close-car-edit-modal">&times;</button>
        </div>
        
        <div class="modal-body">
            <form id="car-edit-form" enctype="multipart/form-data">
                <input type="hidden" id="car-id" name="car_id">
                
                <div class="form-group">
                    <label for="car-title">Titre *</label>
                    <input type="text" id="car-title" name="title" required>
                </div>
                
                <div class="form-group">
                    <label for="car-description">Description</label>
                    <textarea id="car-description" name="description" rows="4" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; resize: vertical;"></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="car-category">Catégorie *</label>
                        <div class="select-container">
                            <select id="car-category" name="category_id" required class="filter-select">
                                <option value="">Sélectionner une catégorie</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>">
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <i class="fas fa-chevron-down select-arrow"></i>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="car-price">Prix (€/jour) *</label>
                        <input type="number" id="car-price" name="price" step="0.01" min="0" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="car-status">Statut *</label>
                    <div class="select-container">
                        <select id="car-status" name="status" required class="filter-select">
                            <option value="available">Disponible</option>
                            <option value="unavailable">Réservé</option>
                        </select>
                        <i class="fas fa-chevron-down select-arrow"></i>
                    </div>
                </div>
                
                <!-- Section image avec upload -->
                <div class="form-group">
                    <label for="car-image-edit">Image de la voiture</label>
                    <div class="file-upload-container">
                        <input type="file" id="car-image-edit" name="car_image" accept="image/*" 
                            style="width: 100%; padding: 10px; border: 2px dashed #ddd; border-radius: 4px; background: #f9f9f9;">
                        <div class="file-upload-info">
                            <small>Formats acceptés: JPG, PNG, GIF (Max: 2MB) - L'ancienne image sera supprimée si vous sélectionnez une nouvelle image</small>
                        </div>
                    </div>
                    <div id="image-preview-edit" style="margin-top: 10px;">
                        <img id="preview-img-edit" src="" alt="Aperçu" style="max-width: 100%; max-height: 200px; border-radius: 4px;">
                        <div id="current-image-info" style="margin-top: 5px; font-size: 0.8rem; color: #666;"></div>
                    </div>
                    
                    <!-- Champ caché pour l'URL actuelle de l'image -->
                    <input type="hidden" id="current-image-url" name="current_image_url">
                </div>
                
                <div class="form-actions" style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="button" class="btn btn-secondary" id="cancel-car-edit">Annuler</button>
                    <button type="submit" class="btn btn-primary" id="save-car-changes">
                        <i class="fas fa-save"></i> Enregistrer les modifications
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

    <!-- Modal de changement de statut voiture -->
    <div class="modal-overlay" id="car-status-modal">
        <div class="modal" style="max-width: 500px;">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-sync-alt" style="color: #3498db; margin-right: 0.5rem;"></i>
                    Changer le statut
                </h3>
                <button class="close-modal" id="close-car-status-modal">&times;</button>
            </div>
            
            <div class="modal-body">
                <div class="delete-confirmation">
                    <div class="delete-icon" style="color: #3498db;">
                        <i class="fas fa-exchange-alt"></i>
                    </div>
                    
                    <h4 id="car-status-message">Êtes-vous sûr de vouloir changer le statut de cette voiture ?</h4>
                    
                    <div class="delete-warning">
                        <i class="fas fa-info-circle"></i>
                        <div class="delete-warning-content">
                            <div class="delete-warning-title">Changement de statut</div>
                            <div class="delete-warning-text">
                                Cette action modifiera la disponibilité de la voiture pour les réservations.
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group" style="margin: 1.5rem 0;">
                        <label for="new-car-status" style="font-weight: 600; margin-bottom: 0.5rem; display: block;">Nouveau statut *</label>
                        <div class="select-container">
                            <select id="new-car-status" class="filter-select" style="width: 100%;">
                                <option value="available">🟢 Disponible</option>
                                <option value="unavailable">🔴 Réservé</option>
                            </select>
                            <i class="fas fa-chevron-down select-arrow"></i>
                        </div>
                    </div>
                    
                    <div class="delete-actions">
                        <button class="btn btn-secondary" id="cancel-car-status">
                            <i class="fas fa-times"></i>
                            Annuler
                        </button>
                        <button class="btn btn-primary" id="confirm-car-status">
                            <i class="fas fa-check"></i>
                            Confirmer le changement
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de suppression voiture -->
    <div class="modal-overlay" id="car-delete-modal">
        <div class="modal" style="max-width: 500px;">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-exclamation-triangle" style="color: #e74c3c; margin-right: 0.5rem;"></i>
                    Confirmer la suppression
                </h3>
                <button class="close-modal" id="close-car-delete-modal">&times;</button>
            </div>
            
            <div class="modal-body">
                <div class="delete-confirmation">
                    <div class="delete-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    
                    <h4 id="car-delete-message">Êtes-vous sûr de vouloir supprimer cette voiture ?</h4>
                    
                    <div class="delete-warning">
                        <i class="fas fa-info-circle"></i>
                        <div class="delete-warning-content">
                            <div class="delete-warning-title">Action irréversible</div>
                            <div class="delete-warning-text">
                                Toutes les données de la voiture seront définitivement supprimées. 
                                Cette action ne peut pas être annulée.
                            </div>
                        </div>
                    </div>
                    
                    <div class="delete-actions">
                        <button class="btn btn-secondary" id="cancel-car-delete">
                            <i class="fas fa-times"></i>
                            Annuler
                        </button>
                        <button class="btn btn-danger" id="confirm-car-delete">
                            <i class="fas fa-trash"></i>
                            Supprimer définitivement
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>                     

    
    <!-- Modal de détails utilisateur -->
        <div class="modal-overlay" id="user-modal">
            <div class="modal" style="max-width: 600px;">
                <div class="modal-header">
                    <h3 class="modal-title" id="user-modal-title">Détails de l'utilisateur</h3>
                    <button class="close-modal" id="close-user-modal">&times;</button>
                </div>
                
                <div class="modal-body">
                    <form id="user-form">
                        <input type="hidden" id="user-id" name="user_id">
                        <input type="hidden" name="update_user" value="1">
                        
                        <!-- Le reste des champs reste identique -->
                        <div class="form-row">
                            <div class="form-group">
                                <label for="user-first-name">Prénom *</label>
                                <input type="text" id="user-first-name" name="first_name" required>
                            </div>
                            <div class="form-group">
                                <label for="user-last-name">Nom *</label>
                                <input type="text" id="user-last-name" name="last_name" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="user-email">Email *</label>
                            <input type="email" id="user-email" name="email" required>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group select-with-badge">
                                <label for="user-role" class="form-label">
                                    <i class="fas fa-user-tag"></i>
                                    Rôle *</label>
                                <div class="select-wrapper">
                                    <select id="user-role" name="role" required class="modern-select">
                                        <option value="user">👤 Utilisateur</option>
                                        <option value="manager">💼 Manager</option>
                                        <option value="admin">👑 Administrateur</option>
                                    </select>
                                    <div class="select-arrow">
                                        <i class="fas fa-chevron-down"></i>
                                    </div>
                                    <div class="role-badge-preview user" id="role-badge">USER</div>
                                </div>
                            </div>
                            
                            <div class="form-group select-with-badge">
                                <label for="user-status" class="form-label">
                                    <i class="fas fa-user-check"></i>
                                    Statut *</label>
                                <div class="select-wrapper">
                                    <select id="user-status" name="status" required class="modern-select">
                                        <option value="active">🟢 Actif</option>
                                        <option value="inactive">🔴 Inactif</option>
                                    </select>
                                    <div class="select-arrow">
                                        <i class="fas fa-chevron-down"></i>
                                    </div>
                                    <div class="status-badge-preview active" id="status-badge">ACTIF</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="user-date-creation">Date d'inscription</label>
                            <input type="text" id="user-date-creation" readonly style="background-color: #f8f9fa;">
                        </div>
                        
                        <div class="form-actions" style="display: flex; gap: 10px; margin-top: 20px;">
                            <button type="button" class="btn btn-secondary" id="cancel-user-edit">Annuler</button>
                            <button type="submit" class="btn btn-primary" id="save-user-changes">
                                <i class="fas fa-save"></i> Enregistrer les modifications
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal de confirmation de suppression -->
        <div class="modal-overlay" id="delete-modal">
            <div class="modal" style="max-width: 500px;">
                <div class="modal-header">
                    <h3 class="modal-title">
                        <i class="fas fa-exclamation-triangle" style="color: #e74c3c; margin-right: 0.5rem;"></i>
                        Confirmer la suppression
                    </h3>
                    <button class="close-modal" id="close-delete-modal">&times;</button>
                </div>
                
                <div class="modal-body">
                    <div class="delete-confirmation">
                        <div class="delete-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        
                        <h4 id="delete-message">Êtes-vous sûr de vouloir supprimer cet utilisateur ?</h4>
                        
                        <div class="delete-warning">
                            <i class="fas fa-info-circle"></i>
                            <div class="delete-warning-content">
                                <div class="delete-warning-title">Action irréversible</div>
                                <div class="delete-warning-text">
                                    Toutes les données de l'utilisateur seront définitivement supprimées. 
                                    Cette action ne peut pas être annulée.
                                </div>
                            </div>
                        </div>
                        
                        <div class="delete-actions">
                            <button class="btn btn-secondary" id="cancel-delete">
                                <i class="fas fa-times"></i>
                                Annuler
                            </button>
                            <button class="btn btn-danger" id="confirm-delete">
                                <i class="fas fa-trash"></i>
                                Supprimer définitivement
                            </button>
                        </div>
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

        // Gestion du modal utilisateur - Version responsive
        document.addEventListener('DOMContentLoaded', function() {
            const userModal = document.getElementById('user-modal');
            const closeUserModal = document.getElementById('close-user-modal');
            const cancelUserEdit = document.getElementById('cancel-user-edit');
            const userForm = document.getElementById('user-form');
            const viewUserButtons = document.querySelectorAll('.btn-view-user');

            // Ouvrir le modal avec les données de l'utilisateur
            viewUserButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const userData = JSON.parse(this.getAttribute('data-user-data'));
                    openUserModal(userData);
                });
            });

            // Fermer le modal
            function closeUserModalFunc() {
                userModal.classList.remove('active');
                document.body.style.overflow = '';
            }

            if (closeUserModal) {
                closeUserModal.addEventListener('click', closeUserModalFunc);
            }

            if (cancelUserEdit) {
                cancelUserEdit.addEventListener('click', closeUserModalFunc);
            }

            userModal.addEventListener('click', (e) => {
                if (e.target === userModal) closeUserModalFunc();
            });

            // Touche Échap pour fermer le modal
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && userModal.classList.contains('active')) {
                    closeUserModalFunc();
                }
            });

            // Ouvrir le modal avec les données
            function openUserModal(userData) {
                document.getElementById('user-id').value = userData.id;
                document.getElementById('user-first-name').value = userData.first_name;
                document.getElementById('user-last-name').value = userData.last_name;
                document.getElementById('user-email').value = userData.email;
                document.getElementById('user-role').value = userData.role;
                document.getElementById('user-status').value = userData.status;
                document.getElementById('user-date-creation').value = new Date(userData.date_creation).toLocaleDateString('fr-FR');
                
                document.getElementById('user-modal-title').textContent = `Modifier ${userData.first_name} ${userData.last_name}`;
                userModal.classList.add('active');
                document.body.style.overflow = 'hidden';
                
                // Focus sur le premier champ
                setTimeout(() => {
                    document.getElementById('user-first-name').focus();
                }, 300);
            }

            // Soumission du formulaire
            if (userForm) {
                userForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    saveUserChanges();
                });
            }

            // Fonction pour sauvegarder les modifications
        async function saveUserChanges() {
            const formData = new FormData(userForm);
            const saveButton = document.getElementById('save-user-changes');
            const originalText = saveButton.innerHTML;

            try {
                // Afficher le loading
                saveButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enregistrement...';
                saveButton.disabled = true;

                // Utiliser le chemin correct
                const response = await fetch('./modules/dashboard/update_user.php', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                // Vérifier si la réponse est OK
                if (!response.ok) {
                    throw new Error(`Erreur HTTP: ${response.status}`);
                }

                const result = await response.json();

                if (result.success) {
                    showNotification(result.message, 'success');
                    // Fermer le modal après succès
                    setTimeout(() => {
                        closeUserModalFunc();
                        // Recharger la page pour voir les changements
                        location.reload();
                    }, 1500);
                } else {
                    showNotification(result.message || 'Erreur lors de la mise à jour', 'error');
                    saveButton.innerHTML = originalText;
                    saveButton.disabled = false;
                }
            } catch (error) {
                console.error('Erreur:', error);
                showNotification('Erreur réseau - Vérifiez votre connexion', 'error');
                saveButton.innerHTML = originalText;
                saveButton.disabled = false;
            }
        }

            // Adapter le modal sur les petits écrans
            function handleResize() {
                if (window.innerWidth <= 768) {
                    document.querySelectorAll('.modal').forEach(modal => {
                        modal.style.margin = '20px auto';
                        modal.style.width = '95%';
                    });
                } else {
                    document.querySelectorAll('.modal').forEach(modal => {
                        modal.style.margin = '';
                        modal.style.width = '';
                    });
                }
            }

            // Écouter les changements de taille
            window.addEventListener('resize', handleResize);
            handleResize(); // Initial call
        });

        // Fonction de notification améliorée pour mobile
        function showNotification(message, type = 'info') {
            // Supprimer les notifications existantes
            document.querySelectorAll('.custom-notification').forEach(notif => notif.remove());

            const notification = document.createElement('div');
            notification.className = `custom-notification ${type}`;
            
            const icons = {
                'success': 'fa-check-circle',
                'error': 'fa-exclamation-circle',
                'info': 'fa-info-circle',
                'warning': 'fa-exclamation-triangle'
            };
            
            notification.innerHTML = `
                <div class="notification-content">
                    <i class="fas ${icons[type] || 'fa-info-circle'}"></i>
                    <span>${message}</span>
                </div>
                <button class="notification-close" onclick="this.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            `;
            
            document.body.appendChild(notification);
            
            // Style responsive pour la notification
            if (window.innerWidth <= 768) {
                notification.style.cssText = `
                    position: fixed;
                    top: 20px;
                    left: 10px;
                    right: 10px;
                    max-width: none;
                    z-index: 9999;
                `;
            }
            
            setTimeout(() => {
                notification.classList.add('show');
            }, 10);
            
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.classList.remove('show');
                    setTimeout(() => {
                        if (notification.parentNode) {
                            notification.parentNode.removeChild(notification);
                        }
                    }, 300);
                }
            }, 5000);
        }

        // ===== GESTION DE LA SUPPRESSION D'UTILISATEUR =====
        document.addEventListener('DOMContentLoaded', function() {
            const deleteModal = document.getElementById('delete-modal');
            const closeDeleteModal = document.getElementById('close-delete-modal');
            const cancelDelete = document.getElementById('cancel-delete');
            const confirmDelete = document.getElementById('confirm-delete');
            const deleteMessage = document.getElementById('delete-message');
            const deleteButtons = document.querySelectorAll('.btn-delete-user');

            let userToDelete = null;
            let userNameToDelete = '';

            // Ouvrir le modal de suppression
            deleteButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const userId = this.getAttribute('data-user-id');
                    const userName = this.getAttribute('data-user-name');
                    
                    userToDelete = userId;
                    userNameToDelete = userName;
                    deleteMessage.textContent = `Êtes-vous sûr de vouloir supprimer l'utilisateur "${userName}" ?`;
                    
                    openDeleteModal();
                });
            });

            // Fonctions pour ouvrir/fermer le modal de suppression
            function openDeleteModal() {
                deleteModal.classList.add('active');
                document.body.style.overflow = 'hidden';
                
                // Focus sur le bouton d'annulation pour l'accessibilité
                setTimeout(() => {
                    cancelDelete.focus();
                }, 300);
            }

            function closeDeleteModalFunc() {
                deleteModal.classList.remove('active');
                document.body.style.overflow = '';
                userToDelete = null;
                userNameToDelete = '';
                
                // Réactiver le bouton de confirmation
                confirmDelete.disabled = false;
                confirmDelete.innerHTML = '<i class="fas fa-trash"></i> Supprimer définitivement';
            }

            // Événements de fermeture
            if (closeDeleteModal) {
                closeDeleteModal.addEventListener('click', closeDeleteModalFunc);
            }

            if (cancelDelete) {
                cancelDelete.addEventListener('click', closeDeleteModalFunc);
            }

            deleteModal.addEventListener('click', (e) => {
                if (e.target === deleteModal) closeDeleteModalFunc();
            });

            // Touche Échap
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && deleteModal.classList.contains('active')) {
                    closeDeleteModalFunc();
                }
            });

            // Confirmation de suppression
            if (confirmDelete) {
                confirmDelete.addEventListener('click', async function() {
                    if (!userToDelete) return;

                    const button = this;
                    const originalText = button.innerHTML;
                    
                    try {
                        // Afficher le loading
                        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Suppression...';
                        button.disabled = true;
                        button.classList.add('btn-loading');

                        const response = await fetch('./modules/dashboard/delete_user.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `user_id=${userToDelete}`
                        });

                        const result = await response.json();

                        if (result.success) {
                            showNotification(result.message, 'success');
                            closeDeleteModalFunc();
                            
                            // Recharger la page après un délai
                            setTimeout(() => {
                                location.reload();
                            }, 1500);
                        } else {
                            showNotification(result.message || 'Erreur lors de la suppression', 'error');
                            resetDeleteButton(button, originalText);
                        }
                    } catch (error) {
                        console.error('Erreur:', error);
                        showNotification('Erreur réseau - Vérifiez votre connexion', 'error');
                        resetDeleteButton(button, originalText);
                    }
                });
            }

            function resetDeleteButton(button, originalText) {
                button.innerHTML = originalText;
                button.disabled = false;
                button.classList.remove('btn-loading');
            }
        });

        // ===== GESTION DES EMPLOYÉS - RÉUTILISATION DU CODE UTILISATEURS =====
        document.addEventListener('DOMContentLoaded', function() {
            // Les boutons de modification et suppression des employés utilisent les mêmes classes
            // donc le code JavaScript existant fonctionnera automatiquement
            
            // Vérification que les boutons employés sont bien connectés
            const employeeEditButtons = document.querySelectorAll('#employees .btn-view-user');
            const employeeDeleteButtons = document.querySelectorAll('#employees .btn-delete-user');
            
            console.log(`Nombre de boutons modification employés: ${employeeEditButtons.length}`);
            console.log(`Nombre de boutons suppression employés: ${employeeDeleteButtons.length}`);
            
            // Le code existant pour les modals utilisateurs fonctionnera aussi pour les employés
            // car ils utilisent les mêmes classes CSS et structure HTML
        });

        // ===== GESTION DES VOITURES =====
    document.addEventListener('DOMContentLoaded', function() {
        // Modal de modification voiture
        const carEditModal = document.getElementById('car-edit-modal');
        const closeCarEditModal = document.getElementById('close-car-edit-modal');
        const cancelCarEdit = document.getElementById('cancel-car-edit');
        const carEditForm = document.getElementById('car-edit-form');
        const carEditButtons = document.querySelectorAll('.btn-edit');
        
        // Éléments pour l'aperçu d'image dans le modal de modification
        const carImageInputEdit = document.getElementById('car-image-edit');
        const imagePreviewEdit = document.getElementById('image-preview-edit');
        const previewImgEdit = document.getElementById('preview-img-edit');
        const currentImageInfo = document.getElementById('current-image-info');
        const currentImageUrlInput = document.getElementById('current-image-url');

        //Modal de l ajout voiture
        const carAddModal = document.getElementById('car-add-modal');
        const closeCarAddModal = document.getElementById('close-car-add-modal');
        const cancelCarAdd = document.getElementById('cancel-car-add');
        const carAddForm = document.getElementById('car-add-form');
        const addCarBtn = document.getElementById('add-car-btn');
        const carImageInput = document.getElementById('car-image');
        const imagePreview = document.getElementById('image-preview');
        const previewImg = document.getElementById('preview-img');

        // Modal de statut voiture
        const carStatusModal = document.getElementById('car-status-modal');
        const closeCarStatusModal = document.getElementById('close-car-status-modal');
        const cancelCarStatus = document.getElementById('cancel-car-status');
        const confirmCarStatus = document.getElementById('confirm-car-status');
        const carStatusButtons = document.querySelectorAll('.btn-status');

        // Modal de suppression voiture
        const carDeleteModal = document.getElementById('car-delete-modal');
        const closeCarDeleteModal = document.getElementById('close-car-delete-modal');
        const cancelCarDelete = document.getElementById('cancel-car-delete');
        const confirmCarDelete = document.getElementById('confirm-car-delete');
        const carDeleteButtons = document.querySelectorAll('.btn-delete');

        let currentCarId = null;
        let currentCarTitle = null;
        let currentCarStatus = null;

        // === MODAL D'AJOUT ===
        addCarBtn.addEventListener('click', function() {
            openCarAddModal();
        });

        function openCarAddModal() {
            carAddForm.reset();
            imagePreview.style.display = 'none';
            carAddModal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeCarAddModalFunc() {
            carAddModal.classList.remove('active');
            document.body.style.overflow = '';
        }

        if (closeCarAddModal) {
            closeCarAddModal.addEventListener('click', closeCarAddModalFunc);
        }

        if (cancelCarAdd) {
            cancelCarAdd.addEventListener('click', closeCarAddModalFunc);
        }

        carAddModal.addEventListener('click', (e) => {
            if (e.target === carAddModal) closeCarAddModalFunc();
        });

        // Aperçu de l'image pour l'ajout
        carImageInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    imagePreview.style.display = 'block';
                }
                reader.readAsDataURL(file);
            } else {
                imagePreview.style.display = 'none';
            }
        });

        // Soumission du formulaire d'ajout
        if (carAddForm) {
            carAddForm.addEventListener('submit', function(e) {
                e.preventDefault();
                addNewCar();
            });
        }

        async function addNewCar() {
            const formData = new FormData(carAddForm);
            const saveButton = document.getElementById('save-new-car');
            const originalText = saveButton.innerHTML;

            try {
                saveButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Ajout en cours...';
                saveButton.disabled = true;

                const response = await fetch('./modules/dashboard/add_car.php', {
                    method: 'POST',
                    body: formData
                });

                if (!response.ok) {
                    throw new Error(`Erreur HTTP: ${response.status}`);
                }

                const result = await response.json();

                if (result.success) {
                    showNotification(result.message, 'success');
                    setTimeout(() => {
                        closeCarAddModalFunc();
                        location.reload();
                    }, 1500);
                } else {
                    showNotification(result.message || 'Erreur lors de l\'ajout', 'error');
                    saveButton.innerHTML = originalText;
                    saveButton.disabled = false;
                }
            } catch (error) {
                console.error('Erreur:', error);
                showNotification('Erreur réseau - Vérifiez votre connexion', 'error');
                saveButton.innerHTML = originalText;
                saveButton.disabled = false;
            }
        }

        // === APERÇU DE L'IMAGE DANS LE MODAL DE MODIFICATION ===
        carImageInputEdit.addEventListener('change', function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                previewImgEdit.src = e.target.result;
                imagePreviewEdit.style.display = 'block';
                currentImageInfo.innerHTML = '<em style="color: #e74c3c;">Nouvelle image sélectionnée - L\'ancienne image sera supprimée</em>';
            }
            reader.readAsDataURL(file);
        } else {
            // Si aucun fichier n'est sélectionné, revenir à l'image actuelle
            const currentUrl = currentImageUrlInput.value;
            if (currentUrl) {
                previewImgEdit.src = currentUrl;
                currentImageInfo.innerHTML = '<em style="color: #27ae60;">Image actuelle</em>';
            } else {
                imagePreviewEdit.style.display = 'none';
                currentImageInfo.innerHTML = '';
            }
        }
    });

    // === MODAL DE MODIFICATION ===
    carEditButtons.forEach(button => {
        button.addEventListener('click', function() {
            const carData = JSON.parse(this.getAttribute('data-car-data'));
            openCarEditModal(carData);
        });
    });

    function openCarEditModal(carData) {
        document.getElementById('car-id').value = carData.id;
        document.getElementById('car-title').value = carData.title;
        document.getElementById('car-description').value = carData.description || '';
        document.getElementById('car-category').value = carData.category_id || '';
        document.getElementById('car-price').value = carData.price;
        document.getElementById('car-status').value = carData.status || 'available';
        
        // Gestion de l'image actuelle
        const currentImageUrl = carData.image_url || '';
        currentImageUrlInput.value = currentImageUrl;
        
        if (currentImageUrl) {
            previewImgEdit.src = currentImageUrl;
            imagePreviewEdit.style.display = 'block';
            currentImageInfo.innerHTML = '<em style="color: #27ae60;">Image actuelle</em>';
        } else {
            imagePreviewEdit.style.display = 'none';
            currentImageInfo.innerHTML = '';
        }
        
        // Réinitialiser le champ fichier
        carImageInputEdit.value = '';
        
        document.getElementById('car-edit-modal-title').textContent = `Modifier "${carData.title}"`;
        carEditModal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

        function closeCarEditModalFunc() {
            carEditModal.classList.remove('active');
            document.body.style.overflow = '';
        }

        if (closeCarEditModal) {
            closeCarEditModal.addEventListener('click', closeCarEditModalFunc);
        }

        if (cancelCarEdit) {
            cancelCarEdit.addEventListener('click', closeCarEditModalFunc);
        }

        carEditModal.addEventListener('click', (e) => {
            if (e.target === carEditModal) closeCarEditModalFunc();
        });

        // Soumission du formulaire de modification
        if (carEditForm) {
            carEditForm.addEventListener('submit', function(e) {
                e.preventDefault();
                saveCarChanges();
            });
        }

        async function saveCarChanges() {
            const formData = new FormData(carEditForm);
            const saveButton = document.getElementById('save-car-changes');
            const originalText = saveButton.innerHTML;

            try {
                saveButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enregistrement...';
                saveButton.disabled = true;

                const response = await fetch('./modules/dashboard/update_car.php', {
                    method: 'POST',
                    body: formData
                });

                if (!response.ok) {
                    throw new Error(`Erreur HTTP: ${response.status}`);
                }

                const result = await response.json();

                if (result.success) {
                    showNotification(result.message, 'success');
                    setTimeout(() => {
                        closeCarEditModalFunc();
                        location.reload();
                    }, 1500);
                } else {
                    showNotification(result.message || 'Erreur lors de la mise à jour', 'error');
                    saveButton.innerHTML = originalText;
                    saveButton.disabled = false;
                }
            } catch (error) {
                console.error('Erreur:', error);
                showNotification('Erreur réseau - Vérifiez votre connexion', 'error');
                saveButton.innerHTML = originalText;
                saveButton.disabled = false;
            }
        }

        // === MODAL DE STATUT ===
        carStatusButtons.forEach(button => {
            button.addEventListener('click', function() {
                currentCarId = this.getAttribute('data-car-id');
                currentCarTitle = this.getAttribute('data-car-title');
                currentCarStatus = this.getAttribute('data-car-current-status');
                
                document.getElementById('car-status-message').textContent = 
                    `Êtes-vous sûr de vouloir changer le statut de "${currentCarTitle}" ?`;
                
                // Définir la valeur actuelle dans le select
                document.getElementById('new-car-status').value = currentCarStatus;
                
                openCarStatusModal();
            });
        });

        function openCarStatusModal() {
            carStatusModal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeCarStatusModalFunc() {
            carStatusModal.classList.remove('active');
            document.body.style.overflow = '';
            currentCarId = null;
            currentCarTitle = null;
            currentCarStatus = null;
        }

        if (closeCarStatusModal) {
            closeCarStatusModal.addEventListener('click', closeCarStatusModalFunc);
        }

        if (cancelCarStatus) {
            cancelCarStatus.addEventListener('click', closeCarStatusModalFunc);
        }

        carStatusModal.addEventListener('click', (e) => {
            if (e.target === carStatusModal) closeCarStatusModalFunc();
        });

        // Confirmation changement de statut
        if (confirmCarStatus) {
            confirmCarStatus.addEventListener('click', async function() {
                if (!currentCarId) return;

                const button = this;
                const originalText = button.innerHTML;
                const newStatus = document.getElementById('new-car-status').value;

                try {
                    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Changement...';
                    button.disabled = true;

                    const response = await fetch('./modules/dashboard/update_car_status.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `car_id=${currentCarId}&status=${newStatus}`
                    });

                    const result = await response.json();

                    if (result.success) {
                        showNotification(result.message, 'success');
                        closeCarStatusModalFunc();
                        setTimeout(() => {
                            location.reload();
                        }, 1500);
                    } else {
                        showNotification(result.message || 'Erreur lors du changement de statut', 'error');
                        resetButton(button, originalText);
                    }
                } catch (error) {
                    console.error('Erreur:', error);
                    showNotification('Erreur réseau - Vérifiez votre connexion', 'error');
                    resetButton(button, originalText);
                }
            });
        }

        // === MODAL DE SUPPRESSION ===
        carDeleteButtons.forEach(button => {
            button.addEventListener('click', function() {
                currentCarId = this.getAttribute('data-car-id');
                currentCarTitle = this.getAttribute('data-car-title');
                
                document.getElementById('car-delete-message').textContent = 
                    `Êtes-vous sûr de vouloir supprimer la voiture "${currentCarTitle}" ?`;
                
                openCarDeleteModal();
            });
        });

        function openCarDeleteModal() {
            carDeleteModal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeCarDeleteModalFunc() {
            carDeleteModal.classList.remove('active');
            document.body.style.overflow = '';
            currentCarId = null;
            currentCarTitle = null;
        }

        if (closeCarDeleteModal) {
            closeCarDeleteModal.addEventListener('click', closeCarDeleteModalFunc);
        }

        if (cancelCarDelete) {
            cancelCarDelete.addEventListener('click', closeCarDeleteModalFunc);
        }

        carDeleteModal.addEventListener('click', (e) => {
            if (e.target === carDeleteModal) closeCarDeleteModalFunc();
        });

        // Confirmation suppression
        if (confirmCarDelete) {
            confirmCarDelete.addEventListener('click', async function() {
                if (!currentCarId) return;

                const button = this;
                const originalText = button.innerHTML;

                try {
                    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Suppression...';
                    button.disabled = true;
                    button.classList.add('btn-loading');

                    const response = await fetch('./modules/dashboard/delete_car.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `car_id=${currentCarId}`
                    });

                    const result = await response.json();

                    if (result.success) {
                        showNotification(result.message, 'success');
                        closeCarDeleteModalFunc();
                        setTimeout(() => {
                            location.reload();
                        }, 1500);
                    } else {
                        showNotification(result.message || 'Erreur lors de la suppression', 'error');
                        resetButton(button, originalText);
                        button.classList.remove('btn-loading');
                    }
                } catch (error) {
                    console.error('Erreur:', error);
                    showNotification('Erreur réseau - Vérifiez votre connexion', 'error');
                    resetButton(button, originalText);
                    button.classList.remove('btn-loading');
                }
            });
        }

        function resetButton(button, originalText) {
            button.innerHTML = originalText;
            button.disabled = false;
        }

        // Touche Échap pour tous les modals
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                if (carEditModal.classList.contains('active')) closeCarEditModalFunc();
                if (carStatusModal.classList.contains('active')) closeCarStatusModalFunc();
                if (carDeleteModal.classList.contains('active')) closeCarDeleteModalFunc();
                if (carAddModal.classList.contains('active')) closeCarAddModalFunc();
            }
        });
    });
    </script>
</body>
</html>