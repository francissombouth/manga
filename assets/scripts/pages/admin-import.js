/**
 * Admin Import Pages JavaScript
 * Gestion des imports massifs et progressions
 */

class AdminImportManager {
    constructor() {
        this.progressContainer = null;
        this.progressBar = null;
        this.progressText = null;
        this.outputLog = null;
        this.isImporting = false;
        
        this.init();
    }

    init() {
        // Attendre que le DOM soit chargé
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.bindEvents());
        } else {
            this.bindEvents();
        }
    }

    bindEvents() {
        // Gestionnaire pour les formulaires d'import
        const importForms = document.querySelectorAll('.import-form form');
        importForms.forEach(form => {
            form.addEventListener('submit', (e) => this.handleImportSubmit(e));
        });

        // Gestionnaire pour les boutons d'import massif
        const massiveImportBtn = document.getElementById('massive-import-btn');
        if (massiveImportBtn) {
            massiveImportBtn.addEventListener('click', (e) => this.handleMassiveImport(e));
        }

        // Initialiser les éléments de progression
        this.initProgressElements();
    }

    initProgressElements() {
        this.progressContainer = document.querySelector('.progress-container');
        this.progressBar = document.querySelector('.progress-fill');
        this.progressText = document.querySelector('.progress-text');
        this.outputLog = document.querySelector('.output-log');
    }

    async handleImportSubmit(event) {
        const form = event.currentTarget;
        const submitBtn = form.querySelector('.btn-submit');
        
        if (this.isImporting) {
            event.preventDefault();
            this.showNotification('Un import est déjà en cours...', 'warning');
            return;
        }

        // Validation basique
        const requiredFields = form.querySelectorAll('[required]');
        for (let field of requiredFields) {
            if (!field.value.trim()) {
                event.preventDefault();
                this.showNotification(`Le champ "${field.labels[0]?.textContent || field.name}" est requis`, 'error');
                field.focus();
                return;
            }
        }

        // Démarrer l'import
        this.startImport(submitBtn);
    }

    async handleMassiveImport(event) {
        event.preventDefault();
        
        if (this.isImporting) {
            this.showNotification('Un import est déjà en cours...', 'warning');
            return;
        }

        const confirmed = confirm('⚠️ Attention !\n\nVous êtes sur le point de générer des données massives.\nCette opération peut prendre plusieurs minutes.\n\nVoulez-vous continuer ?');
        
        if (!confirmed) {
            return;
        }

        this.startMassiveImport();
    }

    startImport(submitBtn) {
        this.isImporting = true;
        
        // Désactiver le bouton
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = '⏳ Import en cours...';
        }

        // Afficher la progression
        this.showProgress();
        this.updateProgress(0, 'Démarrage de l\'import...');
        
        // Simuler la progression (dans un vrai cas, cela viendrait du serveur)
        this.simulateProgress();
    }

    async startMassiveImport() {
        this.isImporting = true;
        
        const button = document.getElementById('massive-import-btn');
        if (button) {
            button.disabled = true;
            button.textContent = '⏳ Génération en cours...';
        }

        this.showProgress();
        this.updateProgress(0, 'Initialisation de la génération massive...');
        
        try {
            // Envoyer la requête au serveur
            const response = await fetch('/admin/import/massive', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ action: 'generate' })
            });

            if (response.ok) {
                await this.trackMassiveImportProgress();
            } else {
                throw new Error('Erreur lors du démarrage de l\'import massif');
            }
        } catch (error) {
            this.handleImportError(error);
        }
    }

    async trackMassiveImportProgress() {
        const pollInterval = 1000; // Vérifier toutes les secondes
        let attempts = 0;
        const maxAttempts = 300; // 5 minutes maximum

        const poll = async () => {
            try {
                const response = await fetch('/admin/import/massive/status');
                const data = await response.json();

                if (data.status === 'running') {
                    this.updateProgress(data.progress || 0, data.message || 'Import en cours...');
                    this.appendToLog(data.log || '');
                    
                    attempts++;
                    if (attempts < maxAttempts) {
                        setTimeout(poll, pollInterval);
                    } else {
                        this.handleImportError(new Error('Timeout: Import trop long'));
                    }
                } else if (data.status === 'completed') {
                    this.completeImport(data.message || 'Import terminé avec succès !');
                } else if (data.status === 'error') {
                    this.handleImportError(new Error(data.message || 'Erreur inconnue'));
                }
            } catch (error) {
                this.handleImportError(error);
            }
        };

        poll();
    }

    simulateProgress() {
        let progress = 0;
        const steps = [
            { progress: 10, message: 'Validation des données...' },
            { progress: 25, message: 'Connexion à l\'API...' },
            { progress: 50, message: 'Récupération des métadonnées...' },
            { progress: 75, message: 'Sauvegarde en base de données...' },
            { progress: 90, message: 'Finalisation...' },
            { progress: 100, message: 'Import terminé !' }
        ];

        let stepIndex = 0;
        
        const interval = setInterval(() => {
            if (stepIndex < steps.length) {
                const step = steps[stepIndex];
                this.updateProgress(step.progress, step.message);
                this.appendToLog(`[${new Date().toLocaleTimeString()}] ${step.message}\n`);
                stepIndex++;
            } else {
                clearInterval(interval);
                this.completeImport('Import terminé avec succès !');
            }
        }, 1500);
    }

    showProgress() {
        if (this.progressContainer) {
            this.progressContainer.style.display = 'block';
        }
        
        if (this.outputLog) {
            this.outputLog.classList.remove('hidden');
            this.outputLog.textContent = '=== Journal d\'import ===\n';
        }
    }

    hideProgress() {
        if (this.progressContainer) {
            this.progressContainer.style.display = 'none';
        }
    }

    updateProgress(percentage, message) {
        if (this.progressBar) {
            this.progressBar.style.width = `${percentage}%`;
        }
        
        if (this.progressText) {
            this.progressText.textContent = `${message} (${percentage}%)`;
        }
    }

    appendToLog(text) {
        if (this.outputLog && text) {
            this.outputLog.textContent += text;
            this.outputLog.scrollTop = this.outputLog.scrollHeight;
        }
    }

    completeImport(message) {
        this.isImporting = false;
        this.updateProgress(100, message);
        this.appendToLog(`[${new Date().toLocaleTimeString()}] ✅ ${message}\n`);
        
        // Réactiver les boutons
        this.resetButtons();
        
        // Masquer la progression après 3 secondes
        setTimeout(() => {
            this.hideProgress();
        }, 3000);

        this.showNotification(message, 'success');
    }

    handleImportError(error) {
        this.isImporting = false;

        
        const errorMessage = error.message || 'Erreur inconnue lors de l\'import';
        this.updateProgress(0, `Erreur: ${errorMessage}`);
        this.appendToLog(`[${new Date().toLocaleTimeString()}] ❌ Erreur: ${errorMessage}\n`);
        
        // Réactiver les boutons
        this.resetButtons();
        
        this.showNotification(`Erreur lors de l'import: ${errorMessage}`, 'error');
    }

    resetButtons() {
        // Réactiver le bouton de soumission
        const submitBtns = document.querySelectorAll('.btn-submit');
        submitBtns.forEach(btn => {
            btn.disabled = false;
            btn.textContent = btn.dataset.originalText || '📥 Importer';
        });

        // Réactiver le bouton d'import massif
        const massiveBtn = document.getElementById('massive-import-btn');
        if (massiveBtn) {
            massiveBtn.disabled = false;
            massiveBtn.textContent = '🚀 Générer les données';
        }
    }

    showNotification(message, type = 'info') {
        // Utiliser le système de flash messages si disponible
        if (window.flashMessages) {
            window.flashMessages[type](message);
            return;
        }

        // Créer la notification
        const notification = document.createElement('div');
        notification.className = `status-message ${type}`;
        notification.style.position = 'fixed';
        notification.style.top = '20px';
        notification.style.right = '20px';
        notification.style.zIndex = '1000';
        notification.style.maxWidth = '400px';
        notification.style.animation = 'slideInRight 0.3s ease';
        
        // Icônes selon le type
        const icons = {
            success: '✅',
            error: '❌',
            warning: '⚠️',
            info: 'ℹ️'
        };
        
        notification.innerHTML = `
            ${icons[type] || 'ℹ️'} ${message}
            <button onclick="this.parentElement.remove()" style="background: none; border: none; color: inherit; font-size: 1.2rem; cursor: pointer; margin-left: 1rem;">×</button>
        `;
        
        document.body.appendChild(notification);
        
        // Auto-suppression après 5 secondes
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 5000);
    }

    // Méthodes utilitaires publiques
    getCurrentStatus() {
        return {
            isImporting: this.isImporting,
            hasProgressBar: !!this.progressBar,
            hasOutputLog: !!this.outputLog
        };
    }

    forceStop() {
        if (this.isImporting) {
            this.handleImportError(new Error('Import arrêté par l\'utilisateur'));
        }
    }
}

// Initialisation automatique
let adminImportManager = null;

document.addEventListener('DOMContentLoaded', function() {
    adminImportManager = new AdminImportManager();
});

// Exposer globalement pour le debug
window.adminImportManager = adminImportManager;
window.AdminImportManager = AdminImportManager;

// Export pour modules ES6 si nécessaire
if (typeof module !== 'undefined' && module.exports) {
    module.exports = AdminImportManager;
} 