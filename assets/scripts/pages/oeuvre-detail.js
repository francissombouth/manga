/**
 * JavaScript pour la page de détail d'une œuvre
 * Version simplifiée et robuste
 */

// Variables globales
let oeuvreId = null;
let isInitialized = false;

// ================== INITIALISATION UNIQUE ==================
function initOeuvrePage(id) {
    // Éviter absolument les initialisations multiples
    if (isInitialized) {
        console.log('⚠️ Page déjà initialisée, arrêt');
        return;
    }
    
    oeuvreId = id;
    isInitialized = true;
    console.log('✅ Initialisation UNIQUE de la page œuvre:', oeuvreId);
    
    // Initialiser immédiatement sans attendre DOMContentLoaded
    initAllFeatures();
}

function initAllFeatures() {
    console.log('🎯 Initialisation de toutes les fonctionnalités');
    
    try {
        initTabs();
        initRatingStars();
        initCommentFormOnce();
        initInteractionButtons();
        loadAverageRating();
        checkFavoriteStatus();
        
        console.log('✅ Page entièrement initialisée');
    } catch (error) {
        console.error('❌ Erreur lors de l\'initialisation:', error);
    }
}

// ================== GESTION DES ONGLETS ==================
function initTabs() {
    console.log('🎯 Initialisation des onglets');
    
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
    console.log('📑 Changement vers onglet:', targetTab);
    
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
            }, 100);
        }
    }
}

// ================== SYSTÈME DE NOTATION ==================
function initRatingStars() {
    console.log('⭐ Initialisation du système de notation');
    
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
        
        if (response.ok) {
            showNotification('Note supprimée avec succès !', 'success');
            resetStars();
            hideRatingButtons();
            loadAverageRating();
        } else {
            const data = await response.json();
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
let commentSubmissionInProgress = false;

function initCommentFormOnce() {
    const form = document.getElementById('commentaire-form');
    if (!form) return;
    
    console.log('💬 Initialisation UNIQUE du formulaire commentaires');
    
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
                console.log('💬 BLOCAGE: Soumission déjà en cours');
                return false;
            }
            
            submitCommentSafely();
            return false;
        });
        
        console.log('✅ Formulaire commentaires configuré avec protection');
    }
}

async function submitCommentSafely() {
    if (commentSubmissionInProgress) {
        console.log('💬 BLOCAGE: submitCommentSafely appelée en double');
        return;
    }
    
    const contenu = document.getElementById('commentaire_contenu')?.value;
    if (!contenu || contenu.trim() === '') {
        showNotification('Veuillez saisir un commentaire', 'error');
        return;
    }
    
    console.log('💬 DÉBUT soumission commentaire');
    commentSubmissionInProgress = true;
    
    // Désactiver visuellement le formulaire
    const submitButton = document.querySelector('#commentaire-form button[type="submit"]');
    const textarea = document.getElementById('commentaire_contenu');
    
    if (submitButton) {
        submitButton.disabled = true;
        submitButton.textContent = '⏳ Envoi en cours...';
    }
    if (textarea) {
        textarea.disabled = true;
    }
    
    try {
        const response = await fetch(`/api/commentaires/oeuvre/${oeuvreId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ contenu: contenu.trim() })
        });
        
        const data = await response.json();
        
        if (response.ok) {
            showNotification('Commentaire ajouté avec succès !', 'success');
            if (textarea) textarea.value = '';
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showNotification(data.message || 'Erreur lors de l\'ajout du commentaire', 'error');
        }
    } catch (error) {
        console.error('Erreur:', error);
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
            console.log('💬 FIN protection soumission commentaire');
        }, 3000);
    }
}

// ================== BOUTONS D'INTERACTION (APPROCHE UNIFIÉE) ==================
function initInteractionButtons() {
    console.log('🔘 Initialisation des boutons d\'interaction');
    
    initLikeButtonsSimple();
    initReplyButtonsSimple();
    initToggleButtonsSimple();
}

// Exposer la fonction globalement pour qu'elle puisse être appelée depuis app.js
window.initInteractionButtons = initInteractionButtons;

function initLikeButtonsSimple() {
    const likeButtons = document.querySelectorAll('.like-btn:not([data-initialized])');
    console.log(`❤️ Initialisation ${likeButtons.length} nouveaux boutons like`);
    
    likeButtons.forEach(button => {
        button.setAttribute('data-initialized', 'true');
        button.addEventListener('click', handleLikeClick);
    });
}

async function handleLikeClick(e) {
    e.preventDefault();
    const commentaireId = this.getAttribute('data-commentaire-id');
    
    if (!document.body.dataset.user || document.body.dataset.user === 'false') {
        showNotification('Vous devez être connecté pour liker un commentaire', 'warning');
        return;
    }
    
    try {
        const response = await fetch(`/api/commentaires/${commentaireId}/likes`, {
            method: 'POST'
        });
        
        if (response.status === 404) {
            showNotification('Fonctionnalité de like non disponible', 'warning');
            return;
        }
        
        const data = await response.json();
        
        if (response.ok) {
            const icon = this.querySelector('.like-icon');
            const count = this.querySelector('.like-count');
            
            if (icon) icon.textContent = data.isLiked ? '❤️' : '🤍';
            if (count) count.textContent = data.likesCount;
        } else {
            showNotification(data.message || 'Erreur lors du like', 'error');
        }
    } catch (error) {
        showNotification('Fonctionnalité de like temporairement indisponible', 'warning');
    }
}

function initReplyButtonsSimple() {
    const replyButtons = document.querySelectorAll('.reply-btn:not([data-initialized])');
    const replyToReplyButtons = document.querySelectorAll('.reply-to-reply-btn:not([data-initialized])');
    
    console.log(`💬 Initialisation ${replyButtons.length} boutons réponse + ${replyToReplyButtons.length} boutons réponse-à-réponse`);
    
    replyButtons.forEach(button => {
        button.setAttribute('data-initialized', 'true');
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const commentaireId = this.getAttribute('data-commentaire-id');
            showReplyForm(commentaireId);
        });
    });
    
    replyToReplyButtons.forEach(button => {
        button.setAttribute('data-initialized', 'true');
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const commentaireId = this.getAttribute('data-commentaire-id');
            showReplyToReplyForm(commentaireId);
        });
    });
}

function initToggleButtonsSimple() {
    const toggleButtons = document.querySelectorAll('.toggle-replies-btn:not([data-initialized]), .toggle-replies-to-replies-btn:not([data-initialized])');
    console.log(`🔄 Initialisation ${toggleButtons.length} boutons toggle`);
    
    toggleButtons.forEach(button => {
        button.setAttribute('data-initialized', 'true');
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const commentaireId = this.getAttribute('data-commentaire-id');
            
            if (this.classList.contains('toggle-replies-btn')) {
                toggleReplies(commentaireId);
            } else {
                toggleRepliesToReplies(commentaireId);
            }
        });
    });
}

// ================== FONCTIONS DE RÉPONSES ==================
function showReplyForm(commentaireId) {
    const form = document.getElementById(`reply-form-${commentaireId}`);
    if (form) {
        const isVisible = form.style.display !== 'none';
        form.style.display = isVisible ? 'none' : 'block';
        
        if (!isVisible) {
            const textarea = document.getElementById(`reply-content-${commentaireId}`);
            if (textarea) setTimeout(() => textarea.focus(), 100);
        }
    }
}

function showReplyToReplyForm(commentaireId) {
    const form = document.getElementById(`reply-to-reply-form-${commentaireId}`);
    if (form) {
        const isVisible = form.style.display !== 'none';
        form.style.display = isVisible ? 'none' : 'block';
        
        if (!isVisible) {
            const textarea = document.getElementById(`reply-to-reply-content-${commentaireId}`);
            if (textarea) setTimeout(() => textarea.focus(), 100);
        }
    }
}

function toggleReplies(commentaireId) {
    const repliesDiv = document.getElementById(`replies-${commentaireId}`);
    const toggleBtn = document.querySelector(`[data-commentaire-id="${commentaireId}"].toggle-replies-btn`);
    
    if (repliesDiv && toggleBtn) {
        const isVisible = repliesDiv.style.display !== 'none';
        repliesDiv.style.display = isVisible ? 'none' : 'block';
        
        const toggleIcon = toggleBtn.querySelector('.toggle-icon');
        const toggleText = toggleBtn.querySelector('.toggle-text');
        
        if (toggleIcon) toggleIcon.textContent = isVisible ? '▶' : '▼';
        if (toggleText) {
            const replyCount = repliesDiv.querySelectorAll('.reponse-item').length;
            toggleText.textContent = isVisible ? 
                `Voir ${replyCount} réponse${replyCount > 1 ? 's' : ''}` :
                `Masquer ${replyCount} réponse${replyCount > 1 ? 's' : ''}`;
        }
        
        // Réinitialiser les boutons dans les réponses
        if (!isVisible) {
            setTimeout(() => initInteractionButtons(), 100);
        }
    }
}

function toggleRepliesToReplies(commentaireId) {
    const repliesDiv = document.getElementById(`replies-to-replies-${commentaireId}`);
    const toggleBtn = document.querySelector(`[data-commentaire-id="${commentaireId}"].toggle-replies-to-replies-btn`);
    
    if (repliesDiv && toggleBtn) {
        const isVisible = repliesDiv.style.display !== 'none';
        repliesDiv.style.display = isVisible ? 'none' : 'block';
        
        const toggleIcon = toggleBtn.querySelector('.toggle-icon');
        const toggleText = toggleBtn.querySelector('.toggle-text');
        
        if (toggleIcon) toggleIcon.textContent = isVisible ? '▶' : '▼';
        if (toggleText) {
            const replyCount = repliesDiv.children.length;
            toggleText.textContent = isVisible ? 
                `Voir ${replyCount} réponse${replyCount > 1 ? 's' : ''} à cette réponse` :
                `Masquer ${replyCount} réponse${replyCount > 1 ? 's' : ''} à cette réponse`;
        }
        
        // Réinitialiser les boutons dans les réponses
        if (!isVisible) {
            setTimeout(() => initInteractionButtons(), 100);
        }
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
        
        if (response.ok) {
            showNotification('Réponse ajoutée avec succès !', 'success');
            textarea.value = '';
            document.getElementById(`reply-form-${commentaireId}`).style.display = 'none';
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showNotification(data.message || 'Erreur lors de l\'ajout de la réponse', 'error');
        }
    } catch (error) {
        showNotification('Erreur lors de l\'ajout de la réponse', 'error');
    }
}

function cancelReply(commentaireId) {
    const form = document.getElementById(`reply-form-${commentaireId}`);
    const textarea = document.getElementById(`reply-content-${commentaireId}`);
    
    if (form) form.style.display = 'none';
    if (textarea) textarea.value = '';
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
        
        if (response.ok) {
            showNotification('Réponse ajoutée avec succès !', 'success');
            textarea.value = '';
            document.getElementById(`reply-to-reply-form-${reponseId}`).style.display = 'none';
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showNotification(data.message || 'Erreur lors de l\'ajout de la réponse', 'error');
        }
    } catch (error) {
        showNotification('Erreur lors de l\'ajout de la réponse', 'error');
    }
}

function cancelReplyToReply(reponseId) {
    const form = document.getElementById(`reply-to-reply-form-${reponseId}`);
    const textarea = document.getElementById(`reply-to-reply-content-${reponseId}`);
    
    if (form) form.style.display = 'none';
    if (textarea) textarea.value = '';
}

// ================== SYSTÈME DE FAVORIS ==================
async function checkFavoriteStatus() {
    if (!document.body.dataset.user || document.body.dataset.user === 'false') {
        return;
    }
    
    try {
        const response = await fetch(`/collections/verifier/${oeuvreId}`);
        if (response.ok) {
            const data = await response.json();
            updateFavoriteButton(oeuvreId, data.isFavorite);
        }
    } catch (error) {
        console.log('⚠️ Erreur statut favori (ignorée):', error);
    }
}

function toggleFavorite(oeuvreIdParam, button) {
    fetch(`/collections/toggle/${oeuvreIdParam}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateFavoriteButton(oeuvreIdParam, data.isFavorite);
            showNotification(data.message, 'success');
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        showNotification('Erreur lors de la modification des favoris', 'error');
    });
}

function updateFavoriteButton(oeuvreIdParam, isFavorite) {
    const button = document.getElementById(`favorite-btn-${oeuvreIdParam}`);
    const icon = document.getElementById(`favorite-icon-${oeuvreIdParam}`);
    const text = document.getElementById(`favorite-text-${oeuvreIdParam}`);
    
    if (button && icon) {
        if (isFavorite) {
            button.classList.add('favorited');
            button.setAttribute('title', 'Retirer des favoris');
            icon.textContent = '❤️';
            if (text) text.textContent = 'Retirer des favoris';
        } else {
            button.classList.remove('favorited');
            button.setAttribute('title', 'Ajouter aux favoris');
            icon.textContent = '🤍';
            if (text) text.textContent = 'Ajouter aux favoris';
        }
    }
}

// ================== UTILITAIRES ==================
function showNotification(message, type = 'info') {
    if (window.flashMessages) {
        window.flashMessages[type](message);
    } else {
        console.log(`${type.toUpperCase()}: ${message}`);
        if (type === 'error') {
            alert(`Erreur: ${message}`);
        } else if (type === 'success') {
            alert(`Succès: ${message}`);
        }
    }
}

// Fonctions pour la compatibilité (pas utilisées mais exposées)
function submitComment() { submitCommentSafely(); }
function initTabs() { /* déjà fait dans initAllFeatures */ }
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

console.log('🎯 JavaScript page œuvre VERSION SIMPLIFIÉE chargé'); 