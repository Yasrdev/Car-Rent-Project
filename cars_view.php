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
                    <!-- Information de pagination -->
                        <div class="pagination-info">
                            Affichage des voitures <?php echo $offset + 1; ?> à <?php echo min($offset + count($cars), $totalCars); ?> sur <?php echo $totalCars; ?> au total
                        </div>
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
                                <img src="<?php echo htmlspecialchars($car['image_url'] ?: 'https://picsum.photos/400/300?random=' . $car['id']); ?>" 
                                     class="car-image" 
                                     alt="<?php echo htmlspecialchars($car['title']); ?>"
                                     onerror="this.src='https://picsum.photos/400/300?random=<?php echo $car['id']; ?>'">
                                <div class="car-image-overlay">
                                    <button class="btn btn-secondary view-details">
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

                               <!-- Bouton de réservation -->
                                    <!-- <div class="car-actions">
                                        <?php if ($car['status'] == 'available'): ?>
                                            <?php if (isLoggedIn()): ?>
                                                <button class="btn btn-reserve" 
                                                        onclick="reserveCar(<?php echo $car['id']; ?>)">
                                                    <i class="fas fa-calendar-check"></i>
                                                    <span>Réserver maintenant</span>
                                                </button>
                                            <?php else: ?>
                                                <button class="btn btn-reserve" 
                                                        onclick="openAuthModal()">
                                                    <i class="fas fa-sign-in-alt"></i>
                                                    <span>Se connecter pour réserver</span>
                                                </button>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <button class="btn btn-disabled" disabled>
                                                <i class="fas fa-times"></i>
                                                <span>Indisponible</span>
                                            </button>
                                        <?php endif; ?>
                                    </div> -->

                                    <!-- Bouton de détails -->
                                <div class="car-actions">
                                    <button class="btn btn-reserve" onclick="showCarDetails(<?php echo $car['id']; ?>)">
                                        <i class="fas fa-info-circle"></i>
                                        <span>Voir détails</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
</section>
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
<br><br>

<?php include 'includes/footer.php'; ?>
    
<script>

    // ===== FONCTIONS DE PAGINATION =====
function changePage(page) {
    const url = new URL(window.location);
    url.searchParams.set('page', page);
    window.location.href = url.toString();
}

// ===== FONCTION POUR VOIR LES DÉTAILS =====
function showCarDetails(carId) {
    showNotification('Fonctionnalité de détails à implémenter pour la voiture ID: ' + carId, 'info');
    // Ici vous pouvez rediriger vers une page de détails ou ouvrir un modal
    // window.location.href = 'car_details.php?id=' + carId;
}


// ===== FONCTIONS DE NOTIFICATION =====
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

// ===== FONCTIONS GLOBALES ACCESSIBLES DEPUIS LE HTML =====
// Ces fonctions sont appelées directement depuis les attributs onclick dans le HTML
window.showCarDetails = showCarDetails;
window.changePage = changePage;
window.showNotification = showNotification;

// ===== GESTION DES ERREURS GLOBALES =====
window.addEventListener('error', (e) => {
    console.error('Erreur globale dans Cars View:', e.error);
});

// ===== OBSERVATEUR POUR LES NOUVEAUX ÉLÉMENTS (si contenu dynamique) =====
const observer_ = new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
        if (mutation.addedNodes.length) {
            mutation.addedNodes.forEach((node) => {
                if (node.nodeType === 1 && node.classList && node.classList.contains('car-card')) {
                    // Réinitialiser les interactions si de nouvelles cartes sont ajoutées
                    setupCardHoverEffects();
                    animateCardsOnScroll();
                }
            });
        }
    });
});

// Démarrer l'observation après l'initialisation
document.addEventListener('DOMContentLoaded', () => {
    observer_.observe(document.body, {
        childList: true,
        subtree: true
    });
});

// ===== FONCTIONS UTILITAIRES =====
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

function setButtonLoading(button, isLoading) {
    if (isLoading) {
        button.disabled = true;
        button.classList.add('btn-loading');
    } else {
        button.disabled = false;
        button.classList.remove('btn-loading');
    }
}



</script>
