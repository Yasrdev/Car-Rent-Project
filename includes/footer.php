<footer>
    <div class="container">
        <div class="footer-content">
            <div class="footer-column">
                <h3>À propos</h3>
                <p>ModernSite est une entreprise innovante spécialisée dans le développement de solutions web modernes et performantes.</p>
            </div>

            <div class="footer-column">
                <h3>Liens rapides</h3>
                <ul>
                    <li><a href="index.php">Accueil</a></li>
                    <li><a href="#services">Services</a></li>
                    <li><a href="#apropos">À propos</a></li>
                    <li><a href="#portfolio">Portfolio</a></li>
                    <li><a href="#contact">Contact</a></li>
                </ul>
            </div>

            <div class="footer-column">
                <h3>Services</h3>
                <ul>
                    <li><a href="#dev">Développement web</a></li>
                    <li><a href="#design">Design UI/UX</a></li>
                    <li><a href="#marketing">Marketing digital</a></li>
                    <li><a href="#consulting">Consulting</a></li>
                </ul>
            </div>

            <div class="footer-column">
                <h3>Contact</h3>
                <ul>
                    <li>123 Rue Example, Ville</li>
                    <li>+33 1 23 45 67 89</li>
                    <li>contact@modernsite.com</li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> ModernSite. Tous droits réservés.</p>
        </div>
    </div>
</footer>

<!-- MODAL D'AUTHENTIFICATION -->
<div class="modal-overlay" id="auth-modal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title" id="modal-title">Connexion</h3>
            <button class="close-modal" id="close-modal">&times;</button>
        </div>

        <!-- Messages d'erreur/succès -->
        <div id="form-messages"></div>

        <div class="form-tabs">
            <div class="form-tab active" data-tab="login">Connexion</div>
            <div class="form-tab" data-tab="register">Inscription</div>
        </div>

        <!-- Formulaire de connexion -->
        <div class="form-content active" id="login-form">
            <form id="login-form-element">
                <input type="hidden" name="ajax" value="1">
                <input type="hidden" name="login_submit" value="1">
                <div class="form-group">
                    <label for="login-email">Email</label>
                    <input type="email" id="login-email" name="email" placeholder="Votre email" required>
                </div>
                <div class="form-group">
                    <label for="login-password">Mot de passe</label>
                    <input type="password" id="login-password" name="password" placeholder="Votre mot de passe" required>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Se connecter</button>
                </div>
            </form>
        </div>

        <!-- Formulaire d'inscription -->
        <div class="form-content" id="register-form">
            <form id="register-form-element">
                <input type="hidden" name="ajax" value="1">
                <input type="hidden" name="register_submit" value="1">
                <div class="form-group">
                    <label for="register-first-name">Prénom</label>
                    <input type="text" id="register-first-name" name="first_name" placeholder="Votre prénom" required>
                </div>
                <div class="form-group">
                    <label for="register-last-name">Nom</label>
                    <input type="text" id="register-last-name" name="last_name" placeholder="Votre nom" required>
                </div>
                <div class="form-group">
                    <label for="register-email">Email</label>
                    <input type="email" id="register-email" name="email" placeholder="Votre email" required>
                </div>
                <div class="form-group">
                    <label for="register-password">Mot de passe</label>
                    <input type="password" id="register-password" name="password" placeholder="Votre mot de passe" required>
                </div>
                <div class="form-group">
                    <label for="register-confirm">Confirmer le mot de passe</label>
                    <input type="password" id="register-confirm" name="password_confirm" placeholder="Confirmer votre mot de passe" required>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">S'inscrire</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="assets/js/main.js"></script>
</body>
</html>