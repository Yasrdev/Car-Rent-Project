// ===== GESTIONNAIRE D'ÉVÉNEMENTS UNIFIÉ =====
class AppManager {
    constructor() {
        this.init();
    }

    init() {
        this.setupMobileMenu();
        this.setupAuthModal();
        this.setupSlider();
        this.setupGoToTop();
        this.setupAnimations();
        this.setupFormHandlers();
        this.setupCarsViewFeatures();
        
        console.log('Site initialisé avec succès!');
    }

    // ===== MENU MOBILE =====
    setupMobileMenu() {
        const burgerMenu = document.getElementById('burger-menu');
        const mobileNav = document.getElementById('mobile-nav');
        const navOverlay = document.getElementById('nav-overlay');
        
        if (!burgerMenu || !mobileNav || !navOverlay) return;

        const burgerSpans = burgerMenu.querySelectorAll('span');

        const toggleMobileMenu = () => {
            mobileNav.classList.toggle('active');
            navOverlay.classList.toggle('active');
            
            if (mobileNav.classList.contains('active')) {
                burgerSpans[0].style.transform = 'rotate(45deg) translate(5px, 5px)';
                burgerSpans[1].style.opacity = '0';
                burgerSpans[2].style.transform = 'rotate(-45deg) translate(7px, -6px)';
                document.body.style.overflow = 'hidden';
            } else {
                burgerSpans.forEach(span => {
                    span.style.transform = 'none';
                    span.style.opacity = '1';
                });
                document.body.style.overflow = '';
            }
        };

        burgerMenu.addEventListener('click', toggleMobileMenu);
        navOverlay.addEventListener('click', toggleMobileMenu);
        
        // Fermer le menu mobile lors du clic sur un lien
        const mobileLinks = mobileNav.querySelectorAll('a');
        mobileLinks.forEach(link => {
            link.addEventListener('click', toggleMobileMenu);
        });
    }

    // ===== MODAL D'AUTHENTIFICATION =====
    setupAuthModal() {
        const modalOverlay = document.getElementById('auth-modal');
        const closeModalBtn = document.getElementById('close-modal');
        const formTabs = document.querySelectorAll('.form-tab');

        // Ouvrir modal
        const openButtons = [
            'login-btn', 'register-btn', 
            'mobile-login-btn', 'mobile-register-btn'
        ];

        openButtons.forEach(btnId => {
            const btn = document.getElementById(btnId);
            if (btn) {
                btn.addEventListener('click', () => {
                    const formType = btnId.includes('login') ? 'login' : 'register';
                    this.openModal(formType);
                });
            }
        });

        // Fermer modal
        if (closeModalBtn) {
            closeModalBtn.addEventListener('click', () => this.closeModal());
        }

        if (modalOverlay) {
            modalOverlay.addEventListener('click', (e) => {
                if (e.target === modalOverlay) this.closeModal();
            });
        }

        // Onglets
        formTabs.forEach(tab => {
            tab.addEventListener('click', () => {
                this.switchTab(tab.dataset.tab);
            });
        });

        // Touche Échap
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modalOverlay?.classList.contains('active')) {
                this.closeModal();
            }
        });
    }

    openModal(formType) {
        const modalOverlay = document.getElementById('auth-modal');
        if (!modalOverlay) return;

        modalOverlay.classList.add('active');
        document.body.style.overflow = 'hidden';
        this.switchTab(formType);
        
        // Fermer le menu mobile si ouvert
        const mobileNav = document.getElementById('mobile-nav');
        if (mobileNav?.classList.contains('active')) {
            mobileNav.classList.remove('active');
        }
    }

    closeModal() {
        const modalOverlay = document.getElementById('auth-modal');
        if (modalOverlay) {
            modalOverlay.classList.remove('active');
            document.body.style.overflow = '';
        }
    }

    switchTab(tabName) {
        const formTabs = document.querySelectorAll('.form-tab');
        const formContents = document.querySelectorAll('.form-content');
        const modalTitle = document.getElementById('modal-title');
        
        // Mettre à jour les onglets
        formTabs.forEach(tab => {
            tab.classList.toggle('active', tab.dataset.tab === tabName);
        });
        
        // Mettre à jour le contenu
        formContents.forEach(content => {
            content.classList.toggle('active', content.id === `${tabName}-form`);
        });
        
        // Mettre à jour le titre
        if (modalTitle) {
            modalTitle.textContent = tabName === 'login' ? 'Connexion' : 'Inscription';
        }
    }

    // ===== GESTION DES FORMULAIRES =====
    setupFormHandlers() {
        const loginForm = document.getElementById('login-form-element');
        const registerForm = document.getElementById('register-form-element');

        if (loginForm) {
            loginForm.addEventListener('submit', (e) => this.handleFormSubmit(e, 'login'));
        }

        if (registerForm) {
            registerForm.addEventListener('submit', (e) => this.handleFormSubmit(e, 'register'));
        }
    }

    async handleFormSubmit(e, formType) {
        e.preventDefault();
        
        const form = e.target;
        const formData = new FormData(form);
        const submitButton = form.querySelector('button[type="submit"]');
        
        // Désactiver le bouton pendant la requête
        const originalText = submitButton.textContent;
        submitButton.textContent = 'Traitement...';
        submitButton.disabled = true;
        
        try {
            const response = await fetch('', {  // Envoyer vers la même page
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            
            if (result.success) {
                this.showMessage(result.message, 'success');
                // Rediriger après succès
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                // Afficher les erreurs
                if (result.errors && result.errors[formType]) {
                    this.showMessage(result.errors[formType], 'error');
                } else if (result.message) {
                    this.showMessage(result.message, 'error');
                } else if (result.errors && typeof result.errors === 'object') {
                    // Afficher la première erreur disponible
                    const firstError = Object.values(result.errors)[0];
                    this.showMessage(firstError, 'error');
                } else {
                    this.showMessage('Une erreur est survenue.', 'error');
                }
            }
        } catch (error) {
            this.showMessage('Une erreur réseau est survenue.', 'error');
            console.error('Erreur:', error);
        } finally {
            // Réactiver le bouton
            submitButton.textContent = originalText;
            submitButton.disabled = false;
        }
    }

    showMessage(message, type) {
        const messagesContainer = document.getElementById('form-messages');
        if (!messagesContainer) return;

        messagesContainer.innerHTML = `
            <div class="${type === 'error' ? 'error-message' : 'success-message'}">
                ${message}
            </div>
        `;

        // Faire défiler vers le haut pour voir le message
        messagesContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

        // Supprimer automatiquement après 5 secondes
        setTimeout(() => {
            messagesContainer.innerHTML = '';
        }, 5000);
    }

    // ===== SLIDER =====
    setupSlider() {
        const slides = document.querySelectorAll('.slide');
        if (slides.length === 0) return;

        let currentSlide = 0;
        let slideInterval;

        const showSlide = (index) => {
            if (index >= slides.length) currentSlide = 0;
            else if (index < 0) currentSlide = slides.length - 1;
            else currentSlide = index;

            slides.forEach(slide => slide.classList.remove('active'));
            document.querySelectorAll('.slider-dot').forEach(dot => dot.classList.remove('active'));

            slides[currentSlide].classList.add('active');
            const activeDot = document.querySelector(`.slider-dot[data-slide="${currentSlide}"]`);
            if (activeDot) activeDot.classList.add('active');
        };

        const nextSlide = () => showSlide(currentSlide + 1);
        const prevSlide = () => showSlide(currentSlide - 1);

        const resetInterval = () => {
            clearInterval(slideInterval);
            slideInterval = setInterval(nextSlide, 5000);
        };

        // Événements
        document.querySelector('.slider-arrow.next')?.addEventListener('click', () => {
            nextSlide();
            resetInterval();
        });

        document.querySelector('.slider-arrow.prev')?.addEventListener('click', () => {
            prevSlide();
            resetInterval();
        });

        document.querySelectorAll('.slider-dot').forEach((dot, index) => {
            dot.addEventListener('click', () => {
                showSlide(index);
                resetInterval();
            });
        });

        // Défilement automatique
        slideInterval = setInterval(nextSlide, 5000);

        const slider = document.querySelector('.slider');
        if (slider) {
            slider.addEventListener('mouseenter', () => clearInterval(slideInterval));
            slider.addEventListener('mouseleave', () => {
                slideInterval = setInterval(nextSlide, 5000);
            });
        }

        showSlide(0);
    }

    // ===== BOUTON GO TO TOP =====
    setupGoToTop() {
        const goToTopBtn = document.createElement('button');
        goToTopBtn.className = 'go-to-top';
        goToTopBtn.innerHTML = '↑';
        goToTopBtn.setAttribute('aria-label', 'Retour en haut');
        document.body.appendChild(goToTopBtn);

        window.addEventListener('scroll', () => {
            goToTopBtn.classList.toggle('visible', window.pageYOffset > 300);
        });

        goToTopBtn.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    // ===== ANIMATIONS =====
    setupAnimations() {
        const animateOnScroll = () => {
            document.querySelectorAll('.section-content').forEach(element => {
                const rect = element.getBoundingClientRect();
                if (rect.top < window.innerHeight && rect.bottom > 0) {
                    element.style.opacity = '1';
                    element.style.transform = 'translateY(0)';
                }
            });
        };

        document.querySelectorAll('.section-content').forEach(element => {
            element.style.opacity = '0';
            element.style.transform = 'translateY(30px)';
            element.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        });

        window.addEventListener('scroll', animateOnScroll);
        animateOnScroll();
    }

    // ===== FONCTIONNALITÉS SPÉCIFIQUES POUR CARS_VIEW =====
    setupCarsViewFeatures() {
        this.setupCarFilters();
        this.setupCarReservations();
        this.setupCarCardInteractions();
    }

    // Filtres des voitures
    setupCarFilters() {
        const filterForm = document.getElementById('filter-form');
        if (!filterForm) return;

        // Auto-submit quand les sélecteurs changent
        const selects = document.querySelectorAll('select[name="category"], select[name="status"]');
        selects.forEach(select => {
            select.addEventListener('change', () => {
                filterForm.submit();
            });
        });

        // Validation des prix
        const priceMin = document.querySelector('input[name="price_min"]');
        const priceMax = document.querySelector('input[name="price_max"]');

        if (priceMin && priceMax) {
            priceMin.addEventListener('change', () => {
                const min = parseInt(priceMin.value) || 0;
                const max = parseInt(priceMax.value) || 0;
                
                if (max > 0 && min > max) {
                    priceMin.value = max;
                    this.showFilterMessage('Le prix minimum ne peut pas être supérieur au prix maximum', 'warning');
                }
            });

            priceMax.addEventListener('change', () => {
                const min = parseInt(priceMin.value) || 0;
                const max = parseInt(priceMax.value) || 0;
                
                if (min > 0 && max > 0 && max < min) {
                    priceMax.value = min;
                    this.showFilterMessage('Le prix maximum ne peut pas être inférieur au prix minimum', 'warning');
                }
            });
        }

        // Effacer les filtres
        const clearFiltersBtn = document.querySelector('a[href="cars_view.php"]');
        if (clearFiltersBtn) {
            clearFiltersBtn.addEventListener('click', (e) => {
                e.preventDefault();
                window.location.href = 'cars_view.php';
            });
        }
    }

    // Réservations de voitures
    setupCarReservations() {
        // Cette fonction est appelée par les boutons de réservation dans le HTML
        window.reserveCar = this.reserveCar.bind(this);
        window.openAuthModal = this.openModal.bind(this);
    }

    async reserveCar(carId) {
        if (!confirm('Voulez-vous vraiment réserver cette voiture ?')) {
            return;
        }

        try {
            const response = await fetch('reserve_car.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'car_id=' + carId
            });

            const data = await response.json();
            
            if (data.success) {
                this.showNotification('Réservation effectuée avec succès !', 'success');
                // Recharger la page après un délai
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            } else {
                this.showNotification('Erreur lors de la réservation: ' + data.message, 'error');
            }
        } catch (error) {
            console.error('Erreur:', error);
            this.showNotification('Erreur réseau lors de la réservation', 'error');
        }
    }

    // Interactions avec les cartes de voitures
    setupCarCardInteractions() {
        const carCards = document.querySelectorAll('.car-card');
        
        carCards.forEach(card => {
            // Effet de zoom sur l'image au survol
            const image = card.querySelector('.car-image');
            if (image) {
                card.addEventListener('mouseenter', () => {
                    image.style.transform = 'scale(1.05)';
                });
                
                card.addEventListener('mouseleave', () => {
                    image.style.transform = 'scale(1)';
                });
            }

            // Click sur la carte (sauf les boutons)
            card.addEventListener('click', (e) => {
                if (!e.target.closest('button') && !e.target.closest('a')) {
                    const carTitle = card.querySelector('.card-title')?.textContent;
                    if (carTitle) {
                        console.log('Carte voiture cliquée:', carTitle);
                        // Ici vous pouvez ajouter une redirection vers une page détail
                    }
                }
            });
        });
    }

    // Messages pour les filtres
    showFilterMessage(message, type) {
        // Créer un toast/notification temporaire
        const toast = document.createElement('div');
        toast.className = `alert alert-${type === 'warning' ? 'warning' : 'info'} alert-dismissible fade show`;
        toast.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
        `;
        toast.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(toast);
        
        // Supprimer automatiquement après 3 secondes
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 3000);
    }

    // Notification moderne
    showNotification(message, type = 'info') {
        const types = {
            success: { class: 'success', icon: '✓' },
            error: { class: 'danger', icon: '✗' },
            warning: { class: 'warning', icon: '⚠' },
            info: { class: 'info', icon: 'ℹ' }
        };

        const config = types[type] || types.info;
        
        const notification = document.createElement('div');
        notification.className = `alert alert-${config.class} alert-dismissible fade show`;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            animation: slideInRight 0.3s ease;
        `;
        notification.innerHTML = `
            <strong>${config.icon}</strong> ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(notification);
        
        // Supprimer automatiquement après 5 secondes
        setTimeout(() => {
            if (notification.parentNode) {
                notification.style.animation = 'slideOutRight 0.3s ease';
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 300);
            }
        }, 5000);
    }

    // Recherche en temps réel (optionnel)
    setupRealTimeSearch() {
        const searchInput = document.querySelector('input[name="search"]');
        if (!searchInput) return;

        let searchTimeout;
        searchInput.addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                // Soumettre le formulaire après 500ms de pause
                document.getElementById('filter-form').submit();
            }, 500);
        });
    }
}

// ===== FONCTIONS GLOBALES =====

// Fonction pour réserver une voiture (accessible globalement)
function reserveCar(carId) {
    if (typeof appManager !== 'undefined') {
        appManager.reserveCar(carId);
    } else {
        console.error('AppManager non initialisé');
    }
}

// Fonction pour ouvrir le modal d'authentification
function openAuthModal() {
    if (typeof appManager !== 'undefined') {
        appManager.openModal('login');
    } else {
        console.error('AppManager non initialisé');
    }
}

// Fonction pour basculer les onglets du modal
function switchAuthTab(tabName) {
    if (typeof appManager !== 'undefined') {
        appManager.switchTab(tabName);
    }
}

// ===== ANIMATIONS CSS SUPPLEMENTAIRES =====
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
    
    .car-card {
        transition: all 0.3s ease;
    }
    
    .car-image {
        transition: transform 0.3s ease;
    }
    
    /* Loading spinner pour les boutons */
    .btn-loading {
        position: relative;
        color: transparent !important;
    }
    
    .btn-loading::after {
        content: '';
        position: absolute;
        width: 16px;
        height: 16px;
        top: 50%;
        left: 50%;
        margin-left: -8px;
        margin-top: -8px;
        border: 2px solid #ffffff;
        border-radius: 50%;
        border-right-color: transparent;
        animation: spin 0.8s linear infinite;
    }
    
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
`;
document.head.appendChild(style);

// ===== GESTION DES BOUTONS DE CHARGEMENT =====
function setButtonLoading(button, isLoading) {
    if (isLoading) {
        button.disabled = true;
        button.classList.add('btn-loading');
    } else {
        button.disabled = false;
        button.classList.remove('btn-loading');
    }
}

// ===== INITIALISATION =====
let appManager;

document.addEventListener('DOMContentLoaded', () => {
    appManager = new AppManager();
    
    // Exposer l'instance globalement pour le débogage
    window.appManager = appManager;
});

// ===== GESTION DES ERREURS GLOBALES =====
window.addEventListener('error', (e) => {
    console.error('Erreur globale:', e.error);
});

// ===== OBSERVATEUR POUR LES NOUVEAUX ÉLÉMENTS =====
// Utile si vous ajoutez du contenu dynamiquement
const observer = new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
        if (mutation.addedNodes.length) {
            // Réinitialiser les fonctionnalités si de nouveaux éléments sont ajoutés
            mutation.addedNodes.forEach((node) => {
                if (node.nodeType === 1) { // Element node
                    // Vérifier si de nouvelles cartes voitures sont ajoutées
                    if (node.classList && node.classList.contains('car-card')) {
                        appManager.setupCarCardInteractions();
                    }
                }
            });
        }
    });
});

// Démarrer l'observation après l'initialisation
document.addEventListener('DOMContentLoaded', () => {
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
});