/**
 * Flash Messages Management
 * Gestion automatique des messages flash avec animations
 */

// Vérifier si la classe existe déjà pour éviter les redéclarations
if (typeof window.FlashMessages === 'undefined') {

class FlashMessages {
    constructor() {
        this.messages = [];
        this.autoHideDelay = 5000; // 5 secondes
        this.init();
    }

    init() {
        // Attendre que le DOM soit chargé
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.setupMessages());
        } else {
            this.setupMessages();
        }
    }

    setupMessages() {
        this.messages = document.querySelectorAll('.flash-message');
        this.bindEvents();
        this.startAutoHide();
    }

    bindEvents() {
        this.messages.forEach(message => {
            // Gérer le bouton de fermeture
            const closeBtn = message.querySelector('.flash-message-close, button[onclick*="remove"]');
            if (closeBtn) {
                closeBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.hideMessage(message);
                });
            }

            // Fermer au clic sur le message (optionnel)
            message.addEventListener('click', (e) => {
                if (e.target === message) {
                    this.hideMessage(message);
                }
            });
        });
    }

    hideMessage(message, animated = true) {
        if (!message || message.classList.contains('removing')) return;

        if (animated) {
            message.classList.add('removing');
            setTimeout(() => {
                this.removeMessage(message);
            }, 300);
        } else {
            this.removeMessage(message);
        }
    }

    removeMessage(message) {
        if (message && message.parentNode) {
            message.parentNode.removeChild(message);
        }
    }

    startAutoHide() {
        setTimeout(() => {
            this.messages.forEach(message => {
                if (message.parentNode) {
                    this.hideMessage(message);
                }
            });
        }, this.autoHideDelay);
    }

    // Méthodes publiques pour ajouter des messages dynamiquement
    addMessage(type, content, autoHide = true) {
        const message = this.createMessageElement(type, content);
        document.body.appendChild(message);

        // Animer l'entrée
        setTimeout(() => {
            message.style.animation = 'slideInRight 0.3s ease';
        }, 10);

        if (autoHide) {
            setTimeout(() => {
                this.hideMessage(message);
            }, this.autoHideDelay);
        }

        return message;
    }

    createMessageElement(type, content) {
        const message = document.createElement('div');
        message.className = `flash-message flash-${type}`;
        
        const icons = {
            success: '✅',
            error: '❌',
            warning: '⚠️',
            info: 'ℹ️'
        };

        message.innerHTML = `
            <div class="flash-message-content">
                <span>${icons[type] || 'ℹ️'}</span>
                <span>${content}</span>
                <button class="flash-message-close">×</button>
            </div>
        `;

        // Ajouter les événements
        const closeBtn = message.querySelector('.flash-message-close');
        closeBtn.addEventListener('click', (e) => {
            e.preventDefault();
            this.hideMessage(message);
        });

        return message;
    }

    // Méthodes de convenance
    success(content, autoHide = true) {
        return this.addMessage('success', content, autoHide);
    }

    error(content, autoHide = true) {
        return this.addMessage('error', content, autoHide);
    }

    warning(content, autoHide = true) {
        return this.addMessage('warning', content, autoHide);
    }

    info(content, autoHide = true) {
        return this.addMessage('info', content, autoHide);
    }

    // Nettoyer tous les messages
    clearAll() {
        this.messages.forEach(message => {
            this.hideMessage(message, false);
        });
        this.messages = [];
    }
}

// Initialisation automatique
let flashMessagesInstance = null;

function initFlashMessages() {
    if (!flashMessagesInstance) {
        flashMessagesInstance = new FlashMessages();
    }
    return flashMessagesInstance;
}

// Exposer globalement
window.FlashMessages = FlashMessages;
window.initFlashMessages = initFlashMessages;
window.flashMessages = initFlashMessages();

// Export pour modules ES6 si nécessaire
if (typeof module !== 'undefined' && module.exports) {
    module.exports = FlashMessages;
}

} // Fin de la vérification d'existence 