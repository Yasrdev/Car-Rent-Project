 <?php include 'includes/header.php'; ?>

    <!-- CONTENU PRINCIPAL -->
    <main>
        <!-- Slider automatique -->
        <section class="slider">
            <!-- Slide 1 -->
            <div class="slide active" style="background-image: url('https://picsum.photos/1200/600?random=1');">
                <div class="slide-content">
                    <h2>Bienvenue sur notre site</h2>
                    <p>Découvrez nos services innovants et notre expertise</p>
                    <button class="btn btn-primary">En savoir plus</button>
                </div>
            </div>

            <!-- Slide 2 -->
            <div class="slide" style="background-image: url('https://picsum.photos/1200/600?random=2');">
                <div class="slide-content">
                    <h2>Solutions modernes</h2>
                    <p>Nous proposons des solutions adaptées à vos besoins</p>
                    <button class="btn btn-primary">Nos services</button>
                </div>
            </div>

            <!-- Slide 3 -->
            <div class="slide" style="background-image: url('https://picsum.photos/1200/600?random=3');">
                <div class="slide-content">
                    <h2>Excellence et qualité</h2>
                    <p>Notre équipe d'experts est à votre service</p>
                    <button class="btn btn-primary">Nous contacter</button>
                </div>
            </div>

            <!-- Navigation du slider -->
            <div class="slider-nav">
                <div class="slider-dot active" data-slide="0"></div>
                <div class="slider-dot" data-slide="1"></div>
                <div class="slider-dot" data-slide="2"></div>
            </div>

            <!-- Flèches de navigation -->
            <div class="slider-arrows">
                <div class="slider-arrow prev">&#10094;</div>
                <div class="slider-arrow next">&#10095;</div>
            </div>
        </section>

        <!-- Section avec image à gauche et texte à droite -->
        <section class="content-section">
            <div class="container">
                <div class="section-content">
                    <div class="section-image">
                        <img src="https://picsum.photos/600/400?random=4" alt="Description de l'image">
                    </div>
                    <div class="section-text">
                        <h2>Nos solutions innovantes</h2>
                        <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.</p>
                        <p>Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>
                        <button class="btn btn-primary">Découvrir</button>
                    </div>
                </div>
            </div>
        </section>

        <!-- Section avec image à droite et texte à gauche -->
        <section class="content-section" style="background-color: #f5f5f5;">
            <div class="container">
                <div class="section-content reverse">
                    <div class="section-image">
                        <img src="https://picsum.photos/600/400?random=5" alt="Description de l'image">
                    </div>
                    <div class="section-text">
                        <h2>Notre expertise</h2>
                        <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.</p>
                        <p>Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>
                        <button class="btn btn-primary">En savoir plus</button>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php include 'includes/footer.php'; ?>