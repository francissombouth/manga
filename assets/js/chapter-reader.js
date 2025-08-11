/**
 * Lecteur de chapitre optimisé avec lazy loading et preloading
 */
class ChapterReader {
    constructor(options = {}) {
        this.options = {
            lazyLoadOffset: 500, // Distance en px pour commencer le lazy loading
            preloadDistance: 2, // Nombre d'images à précharger
            imageQuality: 'auto', // auto, high, medium, low
            enableInfiniteScroll: false,
            ...options
        };
        
        this.images = [];
        this.loadedImages = new Set();
        this.preloadedImages = new Set();
        this.isLoading = false;
        
        this.init();
    }
    
    init() {
        this.setupLazyLoading();
        this.setupPreloading();
        this.setupErrorHandling();
        this.setupPerformanceMonitoring();
        this.addLoadingIndicators();
    }
    
    /**
     * Configuration du lazy loading avec Intersection Observer
     */
    setupLazyLoading() {
        // Observer pour le lazy loading
        this.lazyObserver = new IntersectionObserver(
            (entries) => this.handleLazyLoad(entries),
            {
                rootMargin: `${this.options.lazyLoadOffset}px`,
                threshold: 0.1
            }
        );
        
        // Observer pour les images dans le viewport (pour preloading)
        this.viewportObserver = new IntersectionObserver(
            (entries) => this.handleViewportChange(entries),
            {
                rootMargin: '200px',
                threshold: 0.5
            }
        );
        
        // Rechercher toutes les images de pages
        this.findPageImages();
    }
    
    /**
     * Trouve et configure toutes les images de pages
     */
    findPageImages() {
        const pageImages = document.querySelectorAll('.page-image');
        
        pageImages.forEach((img, index) => {
            const imageData = {
                element: img,
                index: index,
                src: img.dataset.src || img.src,
                loaded: false,
                error: false
            };
            
            this.images.push(imageData);
            
            // Convertir en lazy loading si l'image n'est pas encore dans le viewport
            if (!img.dataset.src && index > 0) {
                img.dataset.src = img.src;
                img.src = this.createPlaceholder(img.offsetWidth, img.offsetHeight);
                img.classList.add('lazy-image');
            }
            
            // Observer pour lazy loading
            this.lazyObserver.observe(img);
            this.viewportObserver.observe(img);
        });
        
        console.log(`📖 Lecteur initialisé avec ${this.images.length} images`);
    }
    
    /**
     * Gère le lazy loading des images
     */
    handleLazyLoad(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                this.loadImage(img);
            }
        });
    }
    
    /**
     * Gère le changement de viewport pour le preloading
     */
    handleViewportChange(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                const imageData = this.images.find(item => item.element === img);
                if (imageData) {
                    this.preloadNearbyImages(imageData.index);
                }
            }
        });
    }
    
    /**
     * Charge une image avec gestion d'erreur
     */
    async loadImage(imgElement) {
        if (imgElement.classList.contains('loading') || imgElement.classList.contains('loaded')) {
            return;
        }
        
        const originalSrc = imgElement.dataset.src || imgElement.src;
        if (!originalSrc || imgElement.src === originalSrc) {
            return;
        }
        
        imgElement.classList.add('loading');
        this.showLoadingIndicator(imgElement);
        
        try {
            // Créer une nouvelle image pour tester le chargement
            const tempImg = new Image();
            
            await new Promise((resolve, reject) => {
                tempImg.onload = resolve;
                tempImg.onerror = reject;
                tempImg.src = originalSrc;
            });
            
            // Si le chargement réussit, remplacer l'image
            imgElement.src = originalSrc;
            imgElement.classList.remove('loading', 'lazy-image');
            imgElement.classList.add('loaded');
            this.hideLoadingIndicator(imgElement);
            
            const imageData = this.images.find(item => item.element === imgElement);
            if (imageData) {
                imageData.loaded = true;
                this.loadedImages.add(imageData.index);
            }
            
            console.log(`✅ Image ${this.loadedImages.size}/${this.images.length} chargée`);
            
        } catch (error) {
            console.error('❌ Erreur de chargement d\'image:', error);
            this.handleImageError(imgElement, originalSrc);
        }
    }
    
    /**
     * Précharge les images à proximité
     */
    preloadNearbyImages(currentIndex) {
        const start = Math.max(0, currentIndex - this.options.preloadDistance);
        const end = Math.min(this.images.length - 1, currentIndex + this.options.preloadDistance);
        
        for (let i = start; i <= end; i++) {
            if (!this.preloadedImages.has(i) && !this.loadedImages.has(i)) {
                this.preloadImage(i);
            }
        }
    }
    
    /**
     * Précharge une image en arrière-plan
     */
    async preloadImage(index) {
        if (this.preloadedImages.has(index) || index >= this.images.length) {
            return;
        }
        
        const imageData = this.images[index];
        if (!imageData || imageData.loaded) {
            return;
        }
        
        this.preloadedImages.add(index);
        
        try {
            const img = new Image();
            img.src = imageData.src;
            console.log(`🔄 Préchargement image ${index + 1}`);
        } catch (error) {
            console.warn(`⚠️ Échec préchargement image ${index + 1}:`, error);
        }
    }
    
    /**
     * Crée un placeholder pour les images lazy
     */
    createPlaceholder(width = 800, height = 1200) {
        const canvas = document.createElement('canvas');
        canvas.width = Math.min(width, 50);
        canvas.height = Math.min(height, 75);
        
        const ctx = canvas.getContext('2d');
        
        // Gradient de fond
        const gradient = ctx.createLinearGradient(0, 0, 0, canvas.height);
        gradient.addColorStop(0, '#374151');
        gradient.addColorStop(1, '#1f2937');
        
        ctx.fillStyle = gradient;
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        
        // Icône de chargement
        ctx.fillStyle = '#9ca3af';
        ctx.font = '16px Arial';
        ctx.textAlign = 'center';
        ctx.fillText('📖', canvas.width / 2, canvas.height / 2);
        
        return canvas.toDataURL();
    }
    
    /**
     * Affiche un indicateur de chargement
     */
    showLoadingIndicator(imgElement) {
        let indicator = imgElement.parentNode.querySelector('.loading-indicator');
        if (!indicator) {
            indicator = document.createElement('div');
            indicator.className = 'loading-indicator';
            indicator.innerHTML = `
                <div class="loading-spinner">
                    <div class="spinner"></div>
                    <span>Chargement...</span>
                </div>
            `;
            imgElement.parentNode.style.position = 'relative';
            imgElement.parentNode.appendChild(indicator);
        }
        indicator.style.display = 'flex';
    }
    
    /**
     * Cache l'indicateur de chargement
     */
    hideLoadingIndicator(imgElement) {
        const indicator = imgElement.parentNode.querySelector('.loading-indicator');
        if (indicator) {
            indicator.style.display = 'none';
        }
    }
    
    /**
     * Gère les erreurs de chargement d'image
     */
    handleImageError(imgElement, originalSrc) {
        imgElement.classList.remove('loading');
        imgElement.classList.add('error');
        this.hideLoadingIndicator(imgElement);
        
        // Créer un placeholder d'erreur
        const errorDiv = document.createElement('div');
        errorDiv.className = 'image-error';
        errorDiv.innerHTML = `
            <div class="error-content">
                <div class="error-icon">⚠️</div>
                <p>Erreur de chargement</p>
                <button class="retry-btn" onclick="chapterReader.retryImage('${originalSrc}', this)">
                    🔄 Réessayer
                </button>
            </div>
        `;
        
        imgElement.parentNode.insertBefore(errorDiv, imgElement.nextSibling);
        imgElement.style.display = 'none';
    }
    
    /**
     * Réessaie de charger une image
     */
    retryImage(src, buttonElement) {
        const errorDiv = buttonElement.closest('.image-error');
        const imgElement = errorDiv.previousElementSibling;
        
        errorDiv.remove();
        imgElement.style.display = 'block';
        imgElement.classList.remove('error');
        
        this.loadImage(imgElement);
    }
    
    /**
     * Configure la gestion d'erreur globale
     */
    setupErrorHandling() {
        // Gestion globale des erreurs d'images
        document.addEventListener('error', (event) => {
            if (event.target.tagName === 'IMG' && event.target.classList.contains('page-image')) {
                this.handleImageError(event.target, event.target.src);
            }
        }, true);
    }
    
    /**
     * Configuration du monitoring des performances
     */
    setupPerformanceMonitoring() {
        this.startTime = performance.now();
        this.loadedCount = 0;
        
        // Surveiller le temps de chargement total
        const checkComplete = () => {
            if (this.loadedImages.size === this.images.length) {
                const totalTime = performance.now() - this.startTime;
                console.log(`🏁 Toutes les images chargées en ${Math.round(totalTime)}ms`);
                this.showPerformanceStats();
            } else {
                setTimeout(checkComplete, 500);
            }
        };
        
        setTimeout(checkComplete, 1000);
    }
    
    /**
     * Affiche les statistiques de performance
     */
    showPerformanceStats() {
        const totalTime = performance.now() - this.startTime;
        const avgTimePerImage = totalTime / this.images.length;
        
        // Notification discrète
        const notification = document.createElement('div');
        notification.className = 'performance-notification';
        notification.innerHTML = `
            <div class="perf-content">
                ✅ ${this.images.length} images chargées en ${Math.round(totalTime / 1000)}s
                <span class="perf-details">(${Math.round(avgTimePerImage)}ms/image)</span>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.classList.add('fade-out');
            setTimeout(() => notification.remove(), 500);
        }, 3000);
    }
    
    /**
     * Ajoute les indicateurs de chargement CSS
     */
    addLoadingIndicators() {
        if (document.getElementById('chapter-reader-styles')) return;
        
        const styles = document.createElement('style');
        styles.id = 'chapter-reader-styles';
        styles.textContent = `
            .loading-indicator {
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                background: rgba(0, 0, 0, 0.8);
                color: white;
                padding: 1rem;
                border-radius: 8px;
                display: none;
                align-items: center;
                gap: 0.5rem;
                z-index: 10;
            }
            
            .spinner {
                width: 20px;
                height: 20px;
                border: 2px solid #374151;
                border-top: 2px solid #ffffff;
                border-radius: 50%;
                animation: spin 1s linear infinite;
            }
            
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
            
            .lazy-image {
                opacity: 0.7;
                filter: blur(2px);
                transition: all 0.3s ease;
            }
            
            .page-image.loaded {
                opacity: 1;
                filter: none;
            }
            
            .image-error {
                background: var(--surface);
                border: 2px dashed var(--border-color);
                border-radius: 8px;
                padding: 2rem;
                text-align: center;
                margin: 1rem 0;
            }
            
            .error-content {
                color: var(--text-secondary);
            }
            
            .error-icon {
                font-size: 2rem;
                margin-bottom: 0.5rem;
            }
            
            .retry-btn {
                background: var(--accent-primary);
                color: white;
                border: none;
                padding: 0.5rem 1rem;
                border-radius: 6px;
                cursor: pointer;
                margin-top: 0.5rem;
            }
            
            .performance-notification {
                position: fixed;
                bottom: 20px;
                right: 20px;
                background: var(--surface);
                border: 1px solid var(--border-color);
                border-radius: 8px;
                padding: 1rem;
                box-shadow: var(--shadow-lg);
                z-index: 1000;
                animation: slideIn 0.3s ease;
            }
            
            .performance-notification.fade-out {
                opacity: 0;
                transform: translateX(100%);
                transition: all 0.5s ease;
            }
            
            .perf-details {
                color: var(--text-secondary);
                font-size: 0.9rem;
            }
            
            @keyframes slideIn {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
        `;
        
        document.head.appendChild(styles);
    }
    
    /**
     * Destruction propre du lecteur
     */
    destroy() {
        if (this.lazyObserver) {
            this.lazyObserver.disconnect();
        }
        if (this.viewportObserver) {
            this.viewportObserver.disconnect();
        }
        
        const styles = document.getElementById('chapter-reader-styles');
        if (styles) {
            styles.remove();
        }
    }
}

// Instance globale
let chapterReader;

// Initialisation automatique
document.addEventListener('DOMContentLoaded', () => {
    // Vérifier si nous sommes sur une page de chapitre
    if (document.querySelector('.page-image')) {
        chapterReader = new ChapterReader({
            lazyLoadOffset: 300,
            preloadDistance: 2,
            imageQuality: 'auto'
        });
        
        console.log('📖 Lecteur de chapitre optimisé initialisé');
    }
});

// Nettoyage avant navigation
window.addEventListener('beforeunload', () => {
    if (chapterReader) {
        chapterReader.destroy();
    }
});
