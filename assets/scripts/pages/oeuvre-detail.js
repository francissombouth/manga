/**
 * JavaScript pour la page de détail d'une œuvre
 * Version encapsulée et compatible Turbo/Hotwire
 */
(() => {
    // Variables locales (pas globales)
let oeuvreId = null;
let isInitialized = false;
    let commentSubmissionInProgress = false;

// ================== INITIALISATION UNIQUE ==================
function initOeuvrePage(id) {
    // Éviter absolument les initialisations multiples
    if (isInitialized) {
        return;
    }
    
    oeuvreId = id;
    isInitialized = true;
    
    // Initialiser immédiatement sans attendre DOMContentLoaded
    initAllFeatures();
}

function initAllFeatures() {
    
    try {
        initTabs();
        initRatingStars();
        initCommentFormOnce();
        initInteractionButtons();
        loadAverageRating();
        checkFavoriteStatus();
        
    } catch (error) {
        console.error('❌ Erreur lors de l\'initialisation:', error);
    }
}

// ================== GESTION DES ONGLETS ==================
function initTabs() {
    
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabButtons.forEach(button => {
        // Supprimer tous les event listeners existants
        const newButton = button.cloneNode(true);
        button.parentNode.replaceChild(newButton, button);
    });
    
    // Attacher les nouveaux event listeners
    document.querySelectorAll('.tab-btn').forEach(button => {
        button.addEventListener('click', handleTabClick);
    });
    
    // Activer le premier onglet
    const firstTab = document.querySelector('.tab-btn');
    if (firstTab) {
        firstTab.click();
    }
}

function handleTabClick(e) {
    e.preventDefault();
    
    const targetTab = this.getAttribute('data-tab');
    
    // Désactiver tous les onglets
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(content => {
        content.style.display = 'none';
        content.classList.remove('active');
    });
    
    // Activer l'onglet sélectionné
    this.classList.add('active');
    const activeContent = document.getElementById(`tab-${targetTab}`);
    if (activeContent) {
        activeContent.style.display = 'block';
        activeContent.classList.add('active');
        
        // Réinitialiser les boutons si on va sur commentaires
        if (targetTab === 'commentaires') {
            setTimeout(() => {
                initInteractionButtons();
                    // Réinitialiser aussi le formulaire de commentaire
                    initCommentFormOnce();
                    // Réinitialiser spécifiquement les boutons toggle
                    initToggleButtonsSimple();
            }, 100);
        }
    }
}

// ================== SYSTÈME DE NOTATION ==================
function initRatingStars() {
    
    const stars = document.querySelectorAll('.star, .star-main');
    const submitBtn = document.getElementById('submit-rating-main');
    const removeBtn = document.getElementById('remove-rating-main');
    
    let selectedRating = 0;
    
    stars.forEach(star => {
        star.addEventListener('click', (e) => {
            const rating = parseInt(star.getAttribute('data-rating'));
            selectedRating = rating;
            
            updateStarsDisplay(stars, rating);
            
            if (submitBtn) submitBtn.style.display = 'inline-flex';
            if (removeBtn) removeBtn.style.display = 'inline-flex';
        });
        
        star.addEventListener('mouseenter', (e) => {
            const rating = parseInt(star.getAttribute('data-rating'));
            updateStarsDisplay(stars, rating);
        });
        
        star.addEventListener('mouseleave', (e) => {
            if (selectedRating > 0) {
                updateStarsDisplay(stars, selectedRating);
            } else {
                resetStars();
            }
        });
    });
    
    if (submitBtn) {
        submitBtn.addEventListener('click', () => submitRating(selectedRating));
    }
    
    if (removeBtn) {
        removeBtn.addEventListener('click', () => removeRating());
    }
}

function updateStarsDisplay(stars, rating) {
    stars.forEach(star => {
        const starRating = parseInt(star.getAttribute('data-rating'));
        const svg = star.querySelector('svg');
        
        if (starRating <= rating) {
            star.classList.add('selected');
            if (svg) {
                svg.setAttribute('fill', 'currentColor');
                svg.style.color = '#fbbf24';
            }
        } else {
            star.classList.remove('selected');
            if (svg) {
                svg.setAttribute('fill', 'none');
                svg.style.color = '#e5e7eb';
            }
        }
    });
}

function resetStars() {
    const stars = document.querySelectorAll('.star, .star-main');
    stars.forEach(star => {
        star.classList.remove('selected');
        const svg = star.querySelector('svg');
        if (svg) {
            svg.setAttribute('fill', 'none');
            svg.style.color = '#e5e7eb';
        }
    });
}

async function submitRating(rating) {
    try {
        const response = await fetch(`/api/oeuvres/${oeuvreId}/rating`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ rating: rating })
        });
        
        const data = await response.json();
        
        if (response.ok) {
            showNotification('Note enregistrée avec succès !', 'success');
            hideRatingButtons();
            loadAverageRating();
        } else {
            showNotification(data.message || 'Erreur lors de l\'enregistrement', 'error');
        }
    } catch (error) {
        showNotification('Erreur lors de l\'enregistrement de la note', 'error');
    }
}

async function removeRating() {
    try {
        const response = await fetch(`/api/oeuvres/${oeuvreId}/rating`, {
            method: 'DELETE'
        });
            
            const data = await response.json();
        
        if (response.ok) {
            showNotification('Note supprimée avec succès !', 'success');
            resetStars();
            hideRatingButtons();
            loadAverageRating();
        } else {
            showNotification(data.message || 'Erreur lors de la suppression', 'error');
        }
    } catch (error) {
        showNotification('Erreur lors de la suppression de la note', 'error');
    }
}

function hideRatingButtons() {
    const submitBtn = document.getElementById('submit-rating-main');
    const removeBtn = document.getElementById('remove-rating-main');
    if (submitBtn) submitBtn.style.display = 'none';
    if (removeBtn) removeBtn.style.display = 'none';
}

async function loadAverageRating() {
    try {
        const response = await fetch(`/api/oeuvres/${oeuvreId}/rating`);
        const data = await response.json();
        
        const avgDisplay = document.getElementById('average-header-display');
        if (avgDisplay && data.average !== undefined && data.average !== null) {
            avgDisplay.textContent = data.average.toFixed(1);
        } else if (avgDisplay) {
            avgDisplay.textContent = 'Aucune note';
        }
        
        if (data.rating && data.rating > 0) {
            const stars = document.querySelectorAll('.star, .star-main');
            updateStarsDisplay(stars, data.rating);
            
            const currentRatingText = document.getElementById('current-rating-text-main');
            if (currentRatingText) {
                currentRatingText.textContent = `Votre note: ${data.rating}/5 ⭐`;
            }
        }
    } catch (error) {
        console.log('⚠️ Erreur chargement moyenne (ignorée):', error);
    }
}

// ================== SYSTÈME DE COMMENTAIRES (SIMPLIFIÉ) ==================
function initCommentFormOnce() {
    const form = document.getElementById('commentaire-form');
    if (!form) return;
    
    
    // Supprimer TOUS les event listeners
    const newForm = form.cloneNode(true);
    form.parentNode.replaceChild(newForm, form);
    
    // Ajouter UN SEUL event listener avec une protection renforcée
    const finalForm = document.getElementById('commentaire-form');
    if (finalForm) {
        finalForm.addEventListener('submit', function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();
            
            // Protection absolue contre la double soumission
            if (commentSubmissionInProgress) {
                return false;
            }
            
            submitCommentSafely();
            return false;
        });
        
    }
}

async function submitCommentSafely() {
        if (commentSubmissionInProgress) {
        return;
    }
    
        commentSubmissionInProgress = true;
        
        const textarea = document.getElementById('commentaire_contenu');
        const contenu = textarea?.value?.trim();
        if (!contenu) {
        showNotification('Veuillez saisir un commentaire', 'error');
        return;
    }
    
        const submitButton = document.getElementById('submit-comment-btn');
    if (submitButton) {
        submitButton.disabled = true;
        submitButton.textContent = '⏳ Envoi en cours...';
    }
        if (textarea) textarea.disabled = true;
    
    try {
        const response = await fetch(`/api/commentaires/oeuvre/${oeuvreId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
                body: JSON.stringify({ contenu: contenu })
        });
        
        const data = await response.json();
            console.log('[DEBUG] Réponse API commentaire:', data);
        
        if (response.ok) {
            showNotification('Commentaire ajouté avec succès !', 'success');
            if (textarea) textarea.value = '';
                setTimeout(() => {
                    console.log('[DEBUG] Reload page après ajout commentaire');
                    window.location.reload();
                }, 1000);
        } else {
            showNotification(data.message || 'Erreur lors de l\'ajout du commentaire', 'error');
        }
    } catch (error) {
            console.error('[DEBUG] Erreur submitCommentSafely:', error);
        showNotification('Erreur lors de l\'ajout du commentaire', 'error');
    } finally {
        // Réactiver le formulaire
        if (submitButton) {
            submitButton.disabled = false;
            submitButton.textContent = '💬 Publier le commentaire';
        }
        if (textarea) {
            textarea.disabled = false;
        }
        
        // Réinitialiser le flag après un délai de sécurité
        setTimeout(() => {
            commentSubmissionInProgress = false;
        }, 3000);
    }
}

    // ================== BOUTONS D'INTERACTION ==================
function initInteractionButtons() {
        initLikeButtonsSimple();
    initReplyButtonsSimple();
    initToggleButtonsSimple();
}

function initLikeButtonsSimple() {
        document.querySelectorAll('.like-btn').forEach(button => {
        button.addEventListener('click', handleLikeClick);
    });
}

async function handleLikeClick(e) {
    e.preventDefault();
    const commentaireId = this.getAttribute('data-commentaire-id');
    
    try {
            const response = await fetch(`/api/commentaires/${commentaireId}/like`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
        });
        
        const data = await response.json();
        
        if (response.ok) {
                const likeIcon = this.querySelector('.like-icon');
                const likeCount = this.querySelector('.like-count');
            
                if (data.liked) {
                    likeIcon.textContent = '❤️';
        } else {
                    likeIcon.textContent = '🤍';
                }
                
                if (likeCount) {
                    likeCount.textContent = data.likesCount;
                }
        }
    } catch (error) {
            console.error('Erreur like:', error);
    }
}

function initReplyButtonsSimple() {
        document.querySelectorAll('.reply-btn, .reply-to-reply-btn').forEach(button => {
            button.addEventListener('click', (e) => {
            e.preventDefault();
                const commentaireId = button.getAttribute('data-commentaire-id');
                if (button.classList.contains('reply-to-reply-btn')) {
                    showReplyToReplyForm(commentaireId);
                } else {
            showReplyForm(commentaireId);
                }
        });
    });
}

function initToggleButtonsSimple() {
        const toggleButtons = document.querySelectorAll('.toggle-replies-btn, .toggle-replies-to-replies-btn');
    
    toggleButtons.forEach(button => {
            button.addEventListener('click', (e) => {
            e.preventDefault();
                const commentaireId = button.getAttribute('data-commentaire-id');
            
                if (button.classList.contains('toggle-replies-to-replies-btn')) {
                    toggleRepliesToReplies(commentaireId);
                } else {
                toggleReplies(commentaireId);
            }
        });
    });
}

function showReplyForm(commentaireId) {
    const form = document.getElementById(`reply-form-${commentaireId}`);
    if (form) {
            form.style.display = 'block';
            form.querySelector('textarea').focus();
    }
}

function showReplyToReplyForm(commentaireId) {
    const form = document.getElementById(`reply-to-reply-form-${commentaireId}`);
    if (form) {
            form.style.display = 'block';
            form.querySelector('textarea').focus();
    }
}

function toggleReplies(commentaireId) {
        const replies = document.getElementById(`replies-${commentaireId}`);
        const button = document.querySelector(`[data-commentaire-id="${commentaireId}"].toggle-replies-btn`);
    
        
        if (replies && button) {
            const isVisible = replies.style.display !== 'none';
            
            replies.style.display = isVisible ? 'none' : 'block';
        
            const icon = button.querySelector('.toggle-icon');
            const text = button.querySelector('.toggle-text');
        
            if (icon) {
                icon.textContent = isVisible ? '▼' : '▲';
            }
            if (text) {
                const count = replies.children.length;
                text.textContent = isVisible ? 
                    `Voir ${count} réponse${count > 1 ? 's' : ''}` : 
                    'Masquer les réponses';
            }
        } else {
    }
}

function toggleRepliesToReplies(commentaireId) {
        const replies = document.getElementById(`replies-to-replies-${commentaireId}`);
        const button = document.querySelector(`[data-commentaire-id="${commentaireId}"].toggle-replies-to-replies-btn`);
    
        
        if (replies && button) {
            const isVisible = replies.style.display !== 'none';
            
            replies.style.display = isVisible ? 'none' : 'block';
        
            const icon = button.querySelector('.toggle-icon');
            const text = button.querySelector('.toggle-text');
        
            if (icon) {
                icon.textContent = isVisible ? '▼' : '▲';
            }
            if (text) {
                const count = replies.children.length;
                text.textContent = isVisible ? 
                    `Voir ${count} réponse${count > 1 ? 's' : ''} à cette réponse` : 
                    'Masquer les réponses';
            }
        } else {
    }
}

// ================== FONCTIONS APPELÉES PAR LE TEMPLATE ==================
async function submitReply(commentaireId) {
        const textarea = document.getElementById(`reply-content-${commentaireId}`);
    const contenu = textarea?.value?.trim();
    
    if (!contenu) {
        showNotification('Veuillez saisir une réponse', 'error');
        return;
    }
    
    try {
        const response = await fetch(`/api/commentaires/${commentaireId}/repondre`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ contenu: contenu })
        });
        
        const data = await response.json();
            console.log('[DEBUG] Réponse API reply:', data);
        
        if (response.ok) {
            showNotification('Réponse ajoutée avec succès !', 'success');
            textarea.value = '';
            document.getElementById(`reply-form-${commentaireId}`).style.display = 'none';
                setTimeout(() => {
                    console.log('[DEBUG] Reload page après ajout réponse');
                    window.location.reload();
                }, 1000);
        } else {
            showNotification(data.message || 'Erreur lors de l\'ajout de la réponse', 'error');
        }
    } catch (error) {
            console.error('[DEBUG] Erreur submitReply:', error);
        showNotification('Erreur lors de l\'ajout de la réponse', 'error');
    }
}

function cancelReply(commentaireId) {
    const form = document.getElementById(`reply-form-${commentaireId}`);
        if (form) {
            form.style.display = 'none';
            form.querySelector('textarea').value = '';
        }
}

async function submitReplyToReply(reponseId) {
        const textarea = document.getElementById(`reply-to-reply-content-${reponseId}`);
    const contenu = textarea?.value?.trim();
    
    if (!contenu) {
        showNotification('Veuillez saisir une réponse', 'error');
        return;
    }
    
    try {
        const response = await fetch(`/api/commentaires/${reponseId}/repondre`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ contenu: contenu })
        });
        
        const data = await response.json();
            console.log('[DEBUG] Réponse API reply-to-reply:', data);
        
        if (response.ok) {
            showNotification('Réponse ajoutée avec succès !', 'success');
            textarea.value = '';
            document.getElementById(`reply-to-reply-form-${reponseId}`).style.display = 'none';
                setTimeout(() => {
                    console.log('[DEBUG] Reload page après ajout réponse à réponse');
                    window.location.reload();
                }, 1000);
        } else {
            showNotification(data.message || 'Erreur lors de l\'ajout de la réponse', 'error');
        }
    } catch (error) {
            console.error('[DEBUG] Erreur submitReplyToReply:', error);
        showNotification('Erreur lors de l\'ajout de la réponse', 'error');
    }
}

function cancelReplyToReply(reponseId) {
    const form = document.getElementById(`reply-to-reply-form-${reponseId}`);
        if (form) {
            form.style.display = 'none';
            form.querySelector('textarea').value = '';
        }
}

// ================== SYSTÈME DE FAVORIS ==================
async function checkFavoriteStatus() {
    try {
        const response = await fetch(`/collections/verifier/${oeuvreId}`);
            const data = await response.json();
            
            if (response.ok) {
            updateFavoriteButton(oeuvreId, data.isFavorite);
        }
    } catch (error) {
            console.log('⚠️ Erreur vérification favoris (ignorée):', error);
    }
}

    async function toggleFavorite(oeuvreIdParam, button) {
        try {
            const response = await fetch(`/collections/toggle/${oeuvreIdParam}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' }
            });
            
            const data = await response.json();
            
            if (response.ok) {
            updateFavoriteButton(oeuvreIdParam, data.isFavorite);
                showNotification(
                    data.isFavorite ? 'Ajouté aux favoris !' : 'Retiré des favoris !', 
                    'success'
                );
        }
        } catch (error) {
        showNotification('Erreur lors de la modification des favoris', 'error');
        }
}

function updateFavoriteButton(oeuvreIdParam, isFavorite) {
    const button = document.getElementById(`favorite-btn-${oeuvreIdParam}`);
    const icon = document.getElementById(`favorite-icon-${oeuvreIdParam}`);
    const text = document.getElementById(`favorite-text-${oeuvreIdParam}`);
    
        if (button) {
        if (isFavorite) {
            button.classList.add('favorited');
        } else {
            button.classList.remove('favorited');
            }
        }
        
        if (icon) {
            icon.textContent = isFavorite ? '❤️' : '🤍';
        }
        
        if (text) {
            text.textContent = isFavorite ? 'Retirer des favoris' : 'Ajouter aux favoris';
    }
}

// ================== UTILITAIRES ==================
function showNotification(message, type = 'info') {
        // Créer une notification simple
        const notification = document.createElement('div');
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            color: white;
            font-weight: 600;
            z-index: 9999;
            animation: slideIn 0.3s ease;
        `;
        
        switch (type) {
            case 'success':
                notification.style.backgroundColor = '#10b981';
                break;
            case 'error':
                notification.style.backgroundColor = '#ef4444';
                break;
            default:
                notification.style.backgroundColor = '#3b82f6';
        }
        
        notification.textContent = message;
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }

    // ================== FONCTIONS DE COMPATIBILITÉ ==================
function submitComment() { submitCommentSafely(); }
function initLikeButtons() { initLikeButtonsSimple(); }
function initReplies() { initReplyButtonsSimple(); }

// ================== EXPOSITION GLOBALE ==================
window.initOeuvrePage = initOeuvrePage;
window.submitComment = submitComment;
window.submitReply = submitReply;
window.cancelReply = cancelReply;
window.submitReplyToReply = submitReplyToReply;
window.cancelReplyToReply = cancelReplyToReply;
window.showReplyForm = showReplyForm;
window.showReplyToReplyForm = showReplyToReplyForm;
window.toggleReplies = toggleReplies;
window.toggleRepliesToReplies = toggleRepliesToReplies;
window.toggleFavorite = toggleFavorite;
window.updateFavoriteButton = updateFavoriteButton;
window.checkFavoriteStatus = checkFavoriteStatus;
window.loadAverageRating = loadAverageRating;
window.showNotification = showNotification;
window.initTabs = initTabs;
window.initLikeButtons = initLikeButtons;
window.initReplies = initReplies;

    // ================== INITIALISATION IMMÉDIATE ==================
    // Essayer d'initialiser immédiatement au chargement du script
    const mainDivImmediate = document.querySelector('div[data-oeuvre-id]');
    if (mainDivImmediate) {
        const oeuvreIdImmediate = mainDivImmediate.dataset.oeuvreId;
        if (!isInitialized) {
            window.oeuvrePageInstance = initOeuvrePage(parseInt(oeuvreIdImmediate));
        }
    }

    // ================== INITIALISATION COMPATIBLE TURBO ==================
    // Écouter l'événement turbo:load pour l'initialisation
    document.addEventListener('turbo:load', function() {
        // Récupérer l'ID de l'œuvre depuis l'attribut data du div principal
        const mainDiv = document.querySelector('div[data-oeuvre-id]');
        if (mainDiv) {
            const oeuvreIdFromData = mainDiv.dataset.oeuvreId;
            // Réinitialiser le flag pour permettre une nouvelle initialisation
            isInitialized = false;
            window.oeuvrePageInstance = initOeuvrePage(parseInt(oeuvreIdFromData));
        }
    });

    // Écouter aussi DOMContentLoaded pour les chargements initiaux
    document.addEventListener('DOMContentLoaded', function() {
        const mainDiv = document.querySelector('div[data-oeuvre-id]');
        if (mainDiv) {
            const oeuvreIdFromData = mainDiv.dataset.oeuvreId;
            if (!isInitialized) {
                window.oeuvrePageInstance = initOeuvrePage(parseInt(oeuvreIdFromData));
            }
        }
    });

    // ================== FALLBACK AVEC TIMEOUT ==================
    // Si rien ne fonctionne, essayer après un délai
    setTimeout(() => {
        if (!isInitialized) {
            const mainDivFallback = document.querySelector('div[data-oeuvre-id]');
            if (mainDivFallback) {
                const oeuvreIdFallback = mainDivFallback.dataset.oeuvreId;
                window.oeuvrePageInstance = initOeuvrePage(parseInt(oeuvreIdFallback));
            }
        }
    }, 1000);

})(); 