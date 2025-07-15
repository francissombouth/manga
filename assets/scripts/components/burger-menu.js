/**
 * Menu burger functionality
 * Gestion du menu burger mobile avec animations et interactions
 */

// Vérifier si la classe existe déjà pour éviter les redéclarations
if (typeof window.BurgerMenu === 'undefined') {

class BurgerMenu {
    constructor() {
        this.burgerMenu = null;
        this.mobileNav = null;
        this.mobileOverlay = null;
        this.mobileNavClose = null;
        this.mobileNavLinks = null;
        this.isInitialized = false;
        
        this.init();
    }

    init() {
        // Attendre que le DOM soit chargé
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.setupMenu());
        } else {
            this.setupMenu();
        }
    }

    setupMenu() {
        console.log('🍔 Initialisation du menu burger...');
        
        // Récupération des éléments
        this.burgerMenu = document.getElementById('burger-menu');
        this.mobileNav = document.getElementById('mobile-nav');
        this.mobileOverlay = document.getElementById('mobile-overlay');
        this.mobileNavClose = document.getElementById('mobile-nav-close');
        this.mobileNavLinks = document.querySelectorAll('.mobile-nav-link');

        // Vérifications de sécurité
        if (!this.validateElements()) {
            return;
        }

        console.log('✅ Tous les éléments du menu burger sont trouvés');

        // Initialisation des événements
        this.bindEvents();
        this.isInitialized = true;
    }

    validateElements() {
        if (!this.burgerMenu) {
            console.error('❌ Élément burger-menu introuvable !');
            return false;
        }
        if (!this.mobileNav) {
            console.error('❌ Élément mobile-nav introuvable !');
            return false;
        }
        if (!this.mobileOverlay) {
            console.error('❌ Élément mobile-overlay introuvable !');
            return false;
        }
        return true;
    }

    bindEvents() {
        // Toggle menu burger
        this.burgerMenu.addEventListener('click', (e) => this.handleBurgerClick(e));

        // Fermer avec le bouton X
        if (this.mobileNavClose) {
            this.mobileNavClose.addEventListener('click', (e) => this.handleCloseClick(e));
        }

        // Fermer en cliquant sur l'overlay
        this.mobileOverlay.addEventListener('click', (e) => this.handleOverlayClick(e));

        // Fermer en cliquant sur un lien
        this.mobileNavLinks.forEach(link => {
            link.addEventListener('click', () => this.handleLinkClick());
        });

        // Fermer avec la touche Escape
        document.addEventListener('keydown', (e) => this.handleKeyDown(e));
    }

    handleBurgerClick(e) {
        e.preventDefault();
        e.stopPropagation();
        console.log('🍔 Clic sur le menu burger');
        
        if (this.mobileNav.classList.contains('active')) {
            this.closeMobileMenu();
        } else {
            this.openMobileMenu();
        }
    }

    handleCloseClick(e) {
        e.preventDefault();
        console.log('❌ Clic sur le bouton fermer');
        this.closeMobileMenu();
    }

    handleOverlayClick(e) {
        if (e.target === this.mobileOverlay) {
            console.log('🌐 Clic sur l\'overlay');
            this.closeMobileMenu();
        }
    }

    handleLinkClick() {
        console.log('🔗 Clic sur un lien de navigation');
        // Petit délai pour permettre la navigation
        setTimeout(() => this.closeMobileMenu(), 100);
    }

    handleKeyDown(e) {
        // Fermer avec Escape
        if (e.key === 'Escape' && this.mobileNav.classList.contains('active')) {
            console.log('⌨️ Touche Escape pressée');
            this.closeMobileMenu();
        }
    }

    openMobileMenu() {
        console.log('📱 Ouverture du menu mobile');
        this.burgerMenu.classList.add('active');
        this.mobileNav.classList.add('active');
        this.mobileOverlay.style.display = 'block';
        
        // Forcer un reflow pour l'animation
        this.mobileOverlay.offsetHeight;
        this.mobileOverlay.classList.add('active');
        
        // Empêcher le scroll du body
        document.body.style.overflow = 'hidden';
        
        // Focus sur le premier élément du menu pour l'accessibilité
        this.focusFirstMenuItem();
    }

    closeMobileMenu() {
        console.log('📱 Fermeture du menu mobile');
        this.burgerMenu.classList.remove('active');
        this.mobileNav.classList.remove('active');
        this.mobileOverlay.classList.remove('active');
        
        setTimeout(() => {
            this.mobileOverlay.style.display = 'none';
        }, 300);
        
        // Rétablir le scroll du body
        document.body.style.overflow = '';
    }

    focusFirstMenuItem() {
        const firstLink = this.mobileNav.querySelector('.mobile-nav-link');
        if (firstLink) {
            firstLink.focus();
        }
    }

    // Méthodes publiques pour contrôle externe
    isOpen() {
        return this.mobileNav && this.mobileNav.classList.contains('active');
    }

    toggle() {
        if (this.isOpen()) {
            this.closeMobileMenu();
        } else {
            this.openMobileMenu();
        }
    }

    close() {
        this.closeMobileMenu();
    }

    open() {
        this.openMobileMenu();
    }

    destroy() {
        if (!this.isInitialized) return;

        // Supprimer tous les event listeners
        this.burgerMenu?.removeEventListener('click', this.handleBurgerClick);
        this.mobileNavClose?.removeEventListener('click', this.handleCloseClick);
        this.mobileOverlay?.removeEventListener('click', this.handleOverlayClick);
        
        this.mobileNavLinks.forEach(link => {
            link.removeEventListener('click', this.handleLinkClick);
        });

        // Nettoyer les références
        this.burgerMenu = null;
        this.mobileNav = null;
        this.mobileOverlay = null;
        this.mobileNavClose = null;
        this.mobileNavLinks = null;
        this.isInitialized = false;

        console.log('🧹 Menu burger détruit');
    }
}

// Initialisation automatique
let burgerMenuInstance = null;

// Créer l'instance globale
function initBurgerMenu() {
    if (!burgerMenuInstance) {
        burgerMenuInstance = new BurgerMenu();
    }
    return burgerMenuInstance;
}

// Exposer globalement pour debug et contrôle externe
window.BurgerMenu = BurgerMenu;
window.initBurgerMenu = initBurgerMenu;
window.burgerMenu = initBurgerMenu();

// Export pour modules ES6 si nécessaire
if (typeof module !== 'undefined' && module.exports) {
    module.exports = BurgerMenu;
}

} // Fin de la vérification d'existence 