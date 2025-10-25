<?php
    // cars_view.php
    require_once 'config/config.php';
    $PageName = 'Voitures';
    
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

<?php include 'includes/header.php'; ?>

<!-- Section de filtrage -->
<section class="filter-section">
    <div class="container">
        <div class="filter-header">
            <h1>Trouvez la Voiture Parfaite</h1>
            <p>Découvrez notre large sélection de véhicules</p>
        </div>
        
        <div class="filter-card">
            <form method="GET" action="cars_view.php" id="filter-form" class="filter-form">
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
                            <a href="cars_view.php" class="btn btn-secondary clear-btn">
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

<!-- Résultats -->
<section class="cars-section">
    <div class="container">
        <!-- Compteur de résultats -->
        <div class="results-header">
            <div class="results-count">
                <i class="fas fa-car"></i>
                Affichage des voitures <?php echo $offset + 1; ?> à <?php echo min($offset + count($cars), $totalCars); ?> sur <?php echo $totalCars; ?> au total
            </div>
            
            <?php if (!empty($_GET)): ?>
                <div class="active-filters">
                    <span class="filters-label">Filtres actifs :</span>
                    <?php if (isset($_GET['search']) && !empty($_GET['search'])): ?>
                        <span class="filter-tag">
                            Recherche: "<?php echo htmlspecialchars($_GET['search']); ?>"
                            <a href="javascript:void(0)" class="remove-filter" data-filter="search">×</a>
                        </span>
                    <?php endif; ?>
                    <?php if (isset($_GET['category']) && !empty($_GET['category'])): ?>
                        <span class="filter-tag">
                            Catégorie: <?php 
                                $catName = 'Inconnue';
                                foreach ($categories as $cat) {
                                    if ($cat['id'] == $_GET['category']) {
                                        $catName = $cat['name'];
                                        break;
                                    }
                                }
                                echo htmlspecialchars($catName);
                            ?>
                            <a href="javascript:void(0)" class="remove-filter" data-filter="category">×</a>
                        </span>
                    <?php endif; ?>
                    <?php if (isset($_GET['status']) && !empty($_GET['status'])): ?>
                        <span class="filter-tag">
                            Statut: <?php echo $_GET['status'] == 'available' ? 'Disponible' : 'Réservé'; ?>
                            <a href="javascript:void(0)" class="remove-filter" data-filter="status">×</a>
                        </span>
                    <?php endif; ?>
                    <?php if (isset($_GET['price_min']) && !empty($_GET['price_min'])): ?>
                        <span class="filter-tag">
                            Prix min: <?php echo htmlspecialchars($_GET['price_min']); ?>€
                            <a href="javascript:void(0)" class="remove-filter" data-filter="price_min">×</a>
                        </span>
                    <?php endif; ?>
                    <?php if (isset($_GET['price_max']) && !empty($_GET['price_max'])): ?>
                        <span class="filter-tag">
                            Prix max: <?php echo htmlspecialchars($_GET['price_max']); ?>€
                            <a href="javascript:void(0)" class="remove-filter" data-filter="price_max">×</a>
                        </span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Grille des voitures -->
        <div class="cars-grid">
            <?php if (empty($cars)): ?>
                <div class="no-cars-found">
                    <div class="no-cars-icon">
                        <i class="fas fa-car-side"></i>
                    </div>
                    <h3>Aucune voiture trouvée</h3>
                    <p>Essayez de modifier vos critères de recherche ou <a href="cars_view.php">réinitialiser les filtres</a></p>
                </div>
            <?php else: ?>
                <?php foreach ($cars as $car): ?>
                    <div class="car-card">
                        <!-- Badge de statut -->
                        <div class="status-badge <?php echo $car['status'] == 'available' ? 'available' : 'unavailable'; ?>">
                            <?php echo $car['status'] == 'available' ? 'Disponible' : 'Réservé'; ?>
                        </div>

                        <!-- Image -->
                        <div class="car-image-container">
                            <img src="<?php echo htmlspecialchars($car['image_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($car['title']); ?>"
                                     class="car-image"
                                     onerror="this.src='images/default-car.jpg'">
                            <div class="car-image-overlay">
                                <button class="btn btn-secondary view-details-btn" 
                                        data-car-id="<?php echo $car['id']; ?>"
                                        data-car-data='<?php echo json_encode($car); ?>'>
                                    <i class="fas fa-eye"></i>
                                    Voir détails
                                </button>
                            </div>
                        </div>

                        <div class="car-card-content">
                            <!-- Catégorie -->
                            <div class="car-category">
                                <i class="fas <?php echo htmlspecialchars($car['icon'] ?? 'fa-car'); ?> category-icon"></i>
                                <?php echo htmlspecialchars($car['category_name'] ?? 'Non catégorisé'); ?>
                            </div>

                            <!-- Titre -->
                            <h3 class="car-title"><?php echo htmlspecialchars($car['title']); ?></h3>

                            <!-- Description -->
                            <p class="car-description">
                                <?php 
                                $description = $car['description'] ?: 'Voiture de qualité avec toutes les options de confort et de sécurité. Parfaite pour vos déplacements professionnels ou personnels.';
                                echo strlen($description) > 120 ? substr($description, 0, 120) . '...' : $description;
                                ?>
                            </p>

                            <!-- Caractéristiques -->
                            <div class="car-features">
                                <div class="feature">
                                    <i class="fas fa-users"></i>
                                    <span>5 places</span>
                                </div>
                                <div class="feature">
                                    <i class="fas fa-suitcase"></i>
                                    <span>3 bagages</span>
                                </div>
                                <div class="feature">
                                    <i class="fas fa-snowflake"></i>
                                    <span>Climatisation</span>
                                </div>
                            </div>

                            <!-- Prix -->
                            <div class="car-price">
                                <span class="price-amount"><?php echo number_format($car['price'], 2, ',', ' '); ?> €</span>
                                <span class="price-period">/jour</span>
                            </div>

                            <!-- Bouton de détails -->
                            <div class="car-actions">
                                <button class="btn btn-reserve view-details-btn" 
                                        data-car-id="<?php echo $car['id']; ?>"
                                        data-car-data='<?php echo json_encode($car); ?>'>
                                    <i class="fas fa-info-circle"></i>
                                    <span>Voir détails</span>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <!-- Bouton Précédent -->
                <button class="pagination-btn prev <?php echo $currentPage <= 1 ? 'disabled' : ''; ?>" 
                        <?php echo $currentPage <= 1 ? 'disabled' : ''; ?>
                        onclick="changePage(<?php echo $currentPage - 1; ?>)">
                    <i class="fas fa-chevron-left"></i>
                    Précédent
                </button>

                <!-- Numéros de page -->
                <div class="pagination-numbers">
                    <?php
                    // Afficher les numéros de page
                    $startPage = max(1, $currentPage - 2);
                    $endPage = min($totalPages, $currentPage + 2);
                    
                    // Première page
                    if ($startPage > 1) {
                        echo '<span class="pagination-number ' . ($currentPage == 1 ? 'active' : '') . '" onclick="changePage(1)">1</span>';
                        if ($startPage > 2) {
                            echo '<span class="pagination-dots">...</span>';
                        }
                    }
                    
                    // Pages autour de la page courante
                    for ($i = $startPage; $i <= $endPage; $i++) {
                        echo '<span class="pagination-number ' . ($currentPage == $i ? 'active' : '') . '" onclick="changePage(' . $i . ')">' . $i . '</span>';
                    }
                    
                    // Dernière page
                    if ($endPage < $totalPages) {
                        if ($endPage < $totalPages - 1) {
                            echo '<span class="pagination-dots">...</span>';
                        }
                        echo '<span class="pagination-number ' . ($currentPage == $totalPages ? 'active' : '') . '" onclick="changePage(' . $totalPages . ')">' . $totalPages . '</span>';
                    }
                    ?>
                </div>

                <!-- Bouton Suivant -->
                <button class="pagination-btn next <?php echo $currentPage >= $totalPages ? 'disabled' : ''; ?>" 
                        <?php echo $currentPage >= $totalPages ? 'disabled' : ''; ?>
                        onclick="changePage(<?php echo $currentPage + 1; ?>)">
                    Suivant
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Modal de détails de voiture -->
<div class="modal-overlay" id="car-details-modal">
    <div class="modal" style="max-width: 800px;">
        <div class="modal-header">
            <h3 class="modal-title" id="car-details-title">Détails de la voiture</h3>
            <button class="close-modal" id="close-car-details-modal">&times;</button>
        </div>
        
        <div class="modal-body">
            <div class="car-details-container">
                <div class="car-details-image">
                    <img id="details-car-image" src="" alt="" style="width: 100%; border-radius: 8px;">
                    <div class="status-badge-large" id="details-status-badge"></div>
                </div>
                
                <div class="car-details-content">
                    <div class="car-details-header">
                        <div class="car-category-details">
                            <i id="details-category-icon" class="fas fa-car"></i>
                            <span id="details-category-name">Catégorie</span>
                        </div>
                        <h2 id="details-car-title" class="car-details-title"></h2>
                    </div>
                    
                    <div class="car-details-info">
                        <div class="info-section">
                            <h4><i class="fas fa-info-circle"></i> Description</h4>
                            <p id="details-car-description" class="car-description-full"></p>
                        </div>
                        
                        <div class="info-section">
                            <h4><i class="fas fa-cogs"></i> Caractéristiques</h4>
                            <div class="features-grid">
                                <div class="feature-item">
                                    <i class="fas fa-users"></i>
                                    <span>5 places</span>
                                </div>
                                <div class="feature-item">
                                    <i class="fas fa-suitcase"></i>
                                    <span>3 bagages</span>
                                </div>
                                <div class="feature-item">
                                    <i class="fas fa-snowflake"></i>
                                    <span>Climatisation</span>
                                </div>
                                <div class="feature-item">
                                    <i class="fas fa-gas-pump"></i>
                                    <span>Essence</span>
                                </div>
                                <div class="feature-item">
                                    <i class="fas fa-cog"></i>
                                    <span>Manuelle</span>
                                </div>
                                <div class="feature-item">
                                    <i class="fas fa-tachometer-alt"></i>
                                    <span>Airbags</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="info-section">
                            <h4><i class="fas fa-euro-sign"></i> Tarifs</h4>
                            <div class="pricing-info">
                                <div class="price-main">
                                    <span id="details-car-price" class="price-amount-large"></span>
                                    <span class="price-period">/ jour</span>
                                </div>
                                <div class="price-details">
                                    <div class="price-item">
                                        <span>Semaine (7 jours)</span>
                                        <span id="details-week-price" class="price-calculated"></span>
                                    </div>
                                    <div class="price-item">
                                        <span>Weekend (3 jours)</span>
                                        <span id="details-weekend-price" class="price-calculated"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="car-details-actions">
                        <?php if (isLoggedIn()): ?>
                            <button class="btn btn-primary btn-reserve-large" id="details-reserve-btn">
                                <i class="fas fa-calendar-check"></i>
                                Réserver maintenant
                            </button>
                        <?php else: ?>
                            <button class="btn btn-primary btn-reserve-large" onclick="openAuthModal()">
                                <i class="fas fa-sign-in-alt"></i>
                                Se connecter pour réserver
                            </button>
                        <?php endif; ?>
                        <button class="btn btn-secondary" id="close-details-modal">
                            <i class="fas fa-times"></i>
                            Fermer
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
    
<script>
document.addEventListener('DOMContentLoaded', function() {
    const carDetailsModal = document.getElementById('car-details-modal');
    const closeCarDetailsModal = document.getElementById('close-car-details-modal');
    const closeDetailsBtn = document.getElementById('close-details-modal');
    const viewDetailsButtons = document.querySelectorAll('.view-details-btn');
    const reserveBtn = document.getElementById('details-reserve-btn');
    const removeFilterButtons = document.querySelectorAll('.remove-filter');

    // Gestion des filtres actifs
    removeFilterButtons.forEach(button => {
        button.addEventListener('click', function() {
            const filterName = this.getAttribute('data-filter');
            removeFilter(filterName);
        });
    });

    function removeFilter(filterName) {
        const url = new URL(window.location);
        url.searchParams.delete(filterName);
        window.location.href = url.toString();
    }

    // Ouvrir le modal de détails
    viewDetailsButtons.forEach(button => {
        button.addEventListener('click', function() {
            const carData = JSON.parse(this.getAttribute('data-car-data'));
            openCarDetailsModal(carData);
        });
    });

    function openCarDetailsModal(carData) {
        // Mettre à jour les informations de la voiture
        document.getElementById('car-details-title').textContent = `Détails - ${carData.title}`;
        document.getElementById('details-car-title').textContent = carData.title;
        document.getElementById('details-car-description').textContent = carData.description || 'Aucune description disponible.';
        
        // Image avec fallback
        const carImage = document.getElementById('details-car-image');
        carImage.src = carData.image_url || 'images/default-car.jpg';
        carImage.alt = carData.title;
        carImage.onerror = function() {
            this.src = 'images/default-car.jpg';
        };
        
        // Catégorie
        const categoryIcon = document.getElementById('details-category-icon');
        const categoryName = document.getElementById('details-category-name');
        if (carData.icon) {
            categoryIcon.className = 'fas ' + carData.icon;
        }
        categoryName.textContent = carData.category_name || 'Non catégorisé';
        
        // Statut
        const statusBadge = document.getElementById('details-status-badge');
        statusBadge.textContent = carData.status === 'available' ? 'Disponible' : 'Réservé';
        statusBadge.className = `status-badge-large ${carData.status === 'available' ? 'available' : 'unavailable'}`;
        
        // Prix
        const price = parseFloat(carData.price);
        document.getElementById('details-car-price').textContent = price.toFixed(2).replace('.', ',') + ' €';
        
        // Calcul des prix
        const weekPrice = (price * 7 * 0.9).toFixed(2); // 10% de réduction
        const weekendPrice = (price * 3).toFixed(2);
        document.getElementById('details-week-price').textContent = weekPrice.replace('.', ',') + ' €';
        document.getElementById('details-weekend-price').textContent = weekendPrice.replace('.', ',') + ' €';
        
        // Gestion du bouton de réservation
        if (reserveBtn) {
            if (carData.status === 'available') {
                reserveBtn.disabled = false;
                reserveBtn.innerHTML = '<i class="fas fa-calendar-check"></i> Réserver maintenant';
                reserveBtn.onclick = function() {
                    reserveCar(carData.id);
                };
            } else {
                reserveBtn.disabled = true;
                reserveBtn.innerHTML = '<i class="fas fa-times"></i> Indisponible';
                reserveBtn.onclick = null;
            }
        }

        // Ouvrir le modal
        carDetailsModal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeCarDetailsModalFunc() {
        carDetailsModal.classList.remove('active');
        document.body.style.overflow = '';
    }

    // Événements de fermeture
    if (closeCarDetailsModal) {
        closeCarDetailsModal.addEventListener('click', closeCarDetailsModalFunc);
    }

    if (closeDetailsBtn) {
        closeDetailsBtn.addEventListener('click', closeCarDetailsModalFunc);
    }

    carDetailsModal.addEventListener('click', (e) => {
        if (e.target === carDetailsModal) closeCarDetailsModalFunc();
    });

    // Touche Échap
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && carDetailsModal.classList.contains('active')) {
            closeCarDetailsModalFunc();
        }
    });

    // Auto-submit du formulaire lors du changement des sélecteurs
    const filterSelects = document.querySelectorAll('.filter-select');
    filterSelects.forEach(select => {
        select.addEventListener('change', function() {
            document.getElementById('filter-form').submit();
        });
    });
});

// Fonction de réservation
function reserveCar(carId) {
    // Redirection vers la page de réservation
    window.location.href = `reservation.php?car_id=${carId}`;
}

// Fonction de pagination
function changePage(page) {
    const url = new URL(window.location);
    url.searchParams.set('page', page);
    window.location.href = url.toString();
}

// Fonction de notification
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `custom-notification ${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'}"></i>
            <span>${message}</span>
        </div>
        <button class="notification-close" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    document.body.appendChild(notification);
    
    // Animation d'entrée
    setTimeout(() => {
        notification.classList.add('show');
    }, 10);
    
    // Supprimer automatiquement après 5 secondes
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

// Fonction utilitaire debounce
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Fonction pour gérer le chargement des boutons
function setButtonLoading(button, isLoading) {
    if (isLoading) {
        button.disabled = true;
        button.classList.add('btn-loading');
    } else {
        button.disabled = false;
        button.classList.remove('btn-loading');
    }
}

// Fonction pour ouvrir le modal d'authentification
function openAuthModal() {
    // Implémentez l'ouverture du modal d'authentification
    showNotification('Veuillez vous connecter pour effectuer une réservation', 'info');
    // Exemple: window.location.href = 'login.php?redirect=cars_view.php';
}

// Rendre les fonctions accessibles globalement
window.changePage = changePage;
window.showNotification = showNotification;
window.reserveCar = reserveCar;
window.openAuthModal = openAuthModal;
</script>