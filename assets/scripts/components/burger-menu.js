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

        // Initialisation des événements
        this.bindEvents();
        this.isInitialized = true;
    }

    validateElements() {
        if (!this.burgerMenu) {
    
            return false;
        }
        if (!this.mobileNav) {
    
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

        // Fermer en cliquant sur l'overlay (désactivé)
        // this.mobileOverlay.addEventListener('click', (e) => this.handleOverlayClick(e));

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
        
        if (this.mobileNav.classList.contains('active')) {
            this.closeMobileMenu();
        } else {
            this.openMobileMenu();
        }
    }

    handleCloseClick(e) {
        e.preventDefault();
        this.closeMobileMenu();
    }

    handleOverlayClick(e) {
        if (e.target === this.mobileOverlay) {
            this.closeMobileMenu();
        }
    }

    handleLinkClick() {
        // Petit délai pour permettre la navigation
        setTimeout(() => this.closeMobileMenu(), 100);
    }

    handleKeyDown(e) {
        // Fermer avec Escape
        if (e.key === 'Escape' && this.mobileNav.classList.contains('active')) {
            this.closeMobileMenu();
        }
    }

    openMobileMenu() {
        this.burgerMenu.classList.add('active');
        this.mobileNav.classList.add('active');
        // this.mobileOverlay.style.display = 'block'; // Désactivé
        
        // Forcer un reflow pour l'animation
        // this.mobileOverlay.offsetHeight; // Désactivé
        // this.mobileOverlay.classList.add('active'); // Désactivé
        
        // Empêcher le scroll du body
        document.body.style.overflow = 'hidden';
        
        // Focus sur le premier élément du menu pour l'accessibilité
        this.focusFirstMenuItem();
    }

    closeMobileMenu() {
        this.burgerMenu.classList.remove('active');
        this.mobileNav.classList.remove('active');
        // this.mobileOverlay.classList.remove('active'); // Désactivé
        
        // setTimeout(() => {
        //     this.mobileOverlay.style.display = 'none';
        // }, 300); // Désactivé
        
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
        // this.mobileOverlay?.removeEventListener('click', this.handleOverlayClick); // Désactivé
        
        this.mobileNavLinks.forEach(link => {
            link.removeEventListener('click', this.handleLinkClick);
        });

        // Nettoyer les références
        this.burgerMenu = null;
        this.mobileNav = null;
        // this.mobileOverlay = null; // Désactivé
        this.mobileNavClose = null;
        this.mobileNavLinks = null;
        this.isInitialized = false;


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