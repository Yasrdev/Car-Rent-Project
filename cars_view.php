<?php
// cars_view.php
require_once 'config/config.php';

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

// Construire la requête finale
$sql = "SELECT c.*, cat.name as category_name, cat.icon 
        FROM cars c 
        LEFT JOIN categories cat ON c.category_id = cat.id";

if (!empty($whereConditions)) {
    $sql .= " WHERE " . implode(" AND ", $whereConditions);
}

$sql .= " ORDER BY c.created_at DESC";

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
                    <span><?php echo count($cars); ?> voiture(s) trouvée(s)</span>
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
                                <div class="car-actions">
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
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Pagination (optionnelle) -->
            <?php if (count($cars) > 0): ?>
                <div class="pagination">
                    <button class="pagination-btn prev" disabled>
                        <i class="fas fa-chevron-left"></i>
                        Précédent
                    </button>
                    <div class="pagination-numbers">
                        <span class="pagination-number active">1</span>
                        <span class="pagination-number">2</span>
                        <span class="pagination-number">3</span>
                        <span class="pagination-dots">...</span>
                        <span class="pagination-number">10</span>
                    </div>
                    <button class="pagination-btn next">
                        Suivant
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </section><br><br>

    <?php include 'includes/footer.php'; ?>

    <!-- JS Personnalisé -->
 <?php include 'includes/footer.php'; ?>
    <script>
        // Fonction pour réserver une voiture
        function reserveCar(carId) {
            if (confirm('Voulez-vous vraiment réserver cette voiture ?')) {
                // Ajouter un indicateur de chargement
                const button = event.target.closest('.btn-reserve') || event.target;
                const originalText = button.innerHTML;
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>Réservation en cours...</span>';
                button.disabled = true;

                fetch('reserve_car.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'car_id=' + carId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification('Réservation effectuée avec succès !', 'success');
                        setTimeout(() => {
                            location.reload();
                        }, 2000);
                    } else {
                        showNotification('Erreur lors de la réservation: ' + data.message, 'error');
                        button.innerHTML = originalText;
                        button.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    showNotification('Erreur réseau lors de la réservation', 'error');
                    button.innerHTML = originalText;
                    button.disabled = false;
                });
            }
        }

        // Fonction pour ouvrir le modal d'authentification
        function openAuthModal() {
            const authModal = document.getElementById('auth-modal');
            if (authModal) {
                authModal.classList.add('active');
                document.body.style.overflow = 'hidden';
            }
        }

        // Auto-submit du formulaire quand les sélecteurs changent
        document.addEventListener('DOMContentLoaded', function() {
            const selects = document.querySelectorAll('.filter-select');
            selects.forEach(select => {
                select.addEventListener('change', function() {
                    document.getElementById('filter-form').submit();
                });
            });

            // Recherche en temps réel
            const searchInput = document.querySelector('.search-input');
            if (searchInput) {
                let searchTimeout;
                searchInput.addEventListener('input', function() {
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(() => {
                        document.getElementById('filter-form').submit();
                    }, 800);
                });
            }

            // Supprimer les filtres individuels
            const removeFilters = document.querySelectorAll('.remove-filter');
            removeFilters.forEach(btn => {
                btn.addEventListener('click', function() {
                    const filterName = this.dataset.filter;
                    const url = new URL(window.location);
                    url.searchParams.delete(filterName);
                    window.location.href = url.toString();
                });
            });

            // Animation des cartes au scroll
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, observerOptions);

            document.querySelectorAll('.car-card').forEach(card => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(30px)';
                card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                observer.observe(card);
            });
        });

        // Fonction de notification personnalisée
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
    </script>
