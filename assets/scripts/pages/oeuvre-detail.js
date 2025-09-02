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
    
        
        if (response.ok) {
            showNotification('Commentaire ajouté avec succès !', 'success');
            if (textarea) textarea.value = '';
            
            // Ajout dynamique du commentaire sans rechargement
    
            addCommentToList(data.commentaire);
            updateCommentairesCount();
        } else {
            showNotification(data.message || 'Erreur lors de l\'ajout du commentaire', 'error');
        }
    } catch (error) {
    
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
    
        console.log(`Initialisation des boutons toggle: ${toggleButtons.length} boutons trouvés`);
    
    toggleButtons.forEach(button => {
            button.addEventListener('click', (e) => {
            e.preventDefault();
                const commentaireId = button.getAttribute('data-commentaire-id');
                console.log(`Clic sur bouton toggle: commentaireId=${commentaireId}`);
            
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
        console.log(`toggleReplies appelé avec commentaireId=${commentaireId}`);
        const replies = document.getElementById(`replies-${commentaireId}`);
        const button = document.querySelector(`[data-commentaire-id="${commentaireId}"].toggle-replies-btn`);
    
        console.log(`Éléments trouvés: replies=${!!replies}, button=${!!button}`);
    
        if (replies && button) {
            // Utiliser une classe CSS au lieu de style.display pour éviter les conflits
            const isVisible = !replies.classList.contains('hidden');
            
            console.log(`État actuel: isVisible=${isVisible}, classe hidden=${replies.classList.contains('hidden')}`);
            
            if (isVisible) {
                replies.classList.add('hidden');
                replies.style.display = 'none';
            } else {
                replies.classList.remove('hidden');
                replies.style.display = 'block';
                
                // Utiliser setTimeout pour s'assurer que le changement persiste
                setTimeout(() => {
                    if (replies.style.display !== 'block') {
                        replies.style.display = 'block';
                        replies.classList.remove('hidden');
                    }
                }, 10);
            }
        
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
            
            console.log(`Nouvel état: replies.style.display=${replies.style.display}, classe hidden=${replies.classList.contains('hidden')}`);
        } else {
            console.warn(`Éléments manquants pour toggleReplies: commentaireId=${commentaireId}, replies=${!!replies}, button=${!!button}`);
        }
    }

function toggleRepliesToReplies(commentaireId) {
        const replies = document.getElementById(`replies-to-replies-${commentaireId}`);
        const button = document.querySelector(`[data-commentaire-id="${commentaireId}"].toggle-replies-to-replies-btn`);
    
        if (replies && button) {
            // Utiliser une classe CSS au lieu de style.display pour éviter les conflits
            const isVisible = !replies.classList.contains('hidden');
            
            if (isVisible) {
                replies.classList.add('hidden');
                replies.style.display = 'none';
            } else {
                replies.classList.remove('hidden');
                replies.style.display = 'block';
            }
        
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
            console.warn(`Éléments manquants pour toggleRepliesToReplies: commentaireId=${commentaireId}, replies=${!!replies}, button=${!!button}`);
        }
    }

// ================== FONCTIONS D'AJOUT DYNAMIQUE DE COMMENTAIRES ==================

function addCommentToList(commentaire) {
    const commentairesList = document.getElementById('commentaires-list');
    if (!commentairesList) return;
    
    // Vérifier s'il y a un état vide et le supprimer
    const emptyState = commentairesList.querySelector('.empty-state');
    if (emptyState) {
        emptyState.remove();
    }
    
    // Créer le HTML du nouveau commentaire
    const commentaireHtml = createCommentaireHTML(commentaire);
    
    // Ajouter au début de la liste
    commentairesList.insertAdjacentHTML('afterbegin', commentaireHtml);
    
    // Réinitialiser les événements sur le nouveau commentaire
    initInteractionButtons();
}

function addReplyToComment(commentaireId, reponse) {
    const repliesContainer = document.getElementById(`replies-${commentaireId}`);
    if (!repliesContainer) return;
    
    // Créer le HTML de la nouvelle réponse
    const reponseHtml = createReponseHTML(reponse, 30); // 30px de marge
    
    // Ajouter à la fin des réponses
    repliesContainer.insertAdjacentHTML('beforeend', reponseHtml);
    
    // S'assurer que les réponses sont visibles
    repliesContainer.style.display = 'block';
    
    // Mettre à jour le bouton toggle
    const toggleBtn = document.querySelector(`[data-commentaire-id="${commentaireId}"].toggle-replies-btn`);
    if (toggleBtn) {
        const currentCount = repliesContainer.children.length;
        const toggleText = toggleBtn.querySelector('.toggle-text');
        if (toggleText) {
            toggleText.textContent = `Masquer ${currentCount} réponse${currentCount > 1 ? 's' : ''}`;
        }
        const toggleIcon = toggleBtn.querySelector('.toggle-icon');
        if (toggleIcon) {
            toggleIcon.textContent = '▲';
        }
    }
    
    // Réinitialiser les événements
    initInteractionButtons();
}

function addReplyToReply(reponseId, reponseToReponse) {
    const repliesToRepliesContainer = document.getElementById(`replies-to-replies-${reponseId}`);
    if (!repliesToRepliesContainer) return;
    
    // Créer le HTML de la nouvelle réponse à la réponse
    const reponseHtml = createReponseToReponseHTML(reponseToReponse);
    
    // Ajouter à la fin
    repliesToRepliesContainer.insertAdjacentHTML('beforeend', reponseHtml);
    
    // S'assurer que les réponses sont visibles
    repliesToRepliesContainer.style.display = 'block';
    
    // Mettre à jour le bouton toggle
    const toggleBtn = document.querySelector(`[data-commentaire-id="${reponseId}"].toggle-replies-to-replies-btn`);
    if (toggleBtn) {
        const currentCount = repliesToRepliesContainer.children.length;
        const toggleText = toggleBtn.querySelector('.toggle-text');
        if (toggleText) {
            toggleText.textContent = `Masquer ${currentCount} réponse${currentCount > 1 ? 's' : ''} à cette réponse`;
        }
        const toggleIcon = toggleBtn.querySelector('.toggle-icon');
        if (toggleIcon) {
            toggleIcon.textContent = '▲';
        }
    }
    
    // Réinitialiser les événements
    initInteractionButtons();
}

function createCommentaireHTML(commentaire) {
    return `
        <div style="background: var(--surface); border-radius: 15px; padding: 2rem; border: 1px solid var(--border-color); margin-bottom: 1.5rem;">
            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <div style="background: var(--accent-purple); color: white; padding: 0.5rem; border-radius: 50%; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; font-weight: 600;">
                        ${commentaire.auteur.initial}
                    </div>
                    <div>
                        <h4 style="color: var(--text-primary); margin: 0; font-size: 1.1rem;">${commentaire.auteur.username}</h4>
                        <span style="color: var(--text-secondary); font-size: 0.9rem;">${commentaire.createdAt}</span>
                    </div>
                </div>
            </div>
            <p style="color: var(--text-secondary); line-height: 1.6; margin-bottom: 1.5rem;">${commentaire.contenu}</p>
            <div style="display: flex; align-items: center; gap: 1rem;">
                <button class="like-btn" data-commentaire-id="${commentaire.id}" style="background: none; border: 2px solid #e11d48; color: #e11d48; padding: 0.5rem 1rem; border-radius: 20px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; gap: 0.5rem;">
                    <span class="like-icon">🤍</span>
                    <span class="like-count">${commentaire.likes.count}</span> J'aime
                </button>
                <button class="reply-btn" data-commentaire-id="${commentaire.id}" style="background: none; border: 2px solid var(--accent-purple); color: var(--accent-purple); padding: 0.5rem 1rem; border-radius: 20px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; gap: 0.5rem;">
                    💬 Répondre
                </button>
            </div>

            <!-- Formulaire de réponse -->
            <div id="reply-form-${commentaire.id}" style="display: none; margin-top: 1rem; padding: 1rem; background: rgba(139, 92, 246, 0.05); border-radius: 10px;">
                <textarea id="reply-content-${commentaire.id}" placeholder="Écrivez votre réponse..." style="width: 100%; padding: 0.8rem; border: 2px solid var(--border-color); border-radius: 8px; background: var(--card-bg); color: var(--text-primary); resize: vertical; font-family: inherit; min-height: 80px; box-sizing: border-box;"></textarea>
                <div style="margin-top: 0.8rem; display: flex; gap: 0.8rem;">
                    <button onclick="submitReply(${commentaire.id})" style="background: var(--accent-purple); color: white; padding: 0.6rem 1.2rem; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; transition: all 0.3s ease;">
                        Publier
                    </button>
                    <button onclick="cancelReply(${commentaire.id})" style="background: var(--border-color); color: var(--text-primary); padding: 0.6rem 1.2rem; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; transition: all 0.3s ease;">
                        Annuler
                    </button>
                </div>
            </div>

            <!-- Conteneur pour les réponses -->
            <div id="replies-${commentaire.id}" style="display: none;"></div>
        </div>
    `;
}

function createReponseHTML(reponse, marginLeft = 30) {
    return `
        <div class="reponse-item" style="background: rgba(139, 92, 246, 0.05); border-radius: 15px; padding: 2rem; border: 1px solid var(--border-color); margin: 1rem 0 1rem ${marginLeft}px; border-left: 3px solid var(--accent-purple);">
            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <div style="background: var(--accent-purple); color: white; padding: 0.5rem; border-radius: 50%; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; font-weight: 600;">
                        ${reponse.auteur.initial}
                    </div>
                    <div>
                        <h4 style="color: var(--text-primary); margin: 0; font-size: 1rem;">${reponse.auteur.username}</h4>
                        <span style="color: var(--text-secondary); font-size: 0.9rem;">${reponse.createdAt}</span>
                    </div>
                </div>
            </div>
            <p style="color: var(--text-secondary); line-height: 1.6; margin-bottom: 1rem; font-size: 1rem;">${reponse.contenu}</p>
            <div style="display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;">
                <button class="like-btn" data-commentaire-id="${reponse.id}" style="background: none; border: 2px solid #e11d48; color: #e11d48; padding: 0.5rem 1rem; border-radius: 20px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; gap: 0.5rem;">
                    <span class="like-icon">🤍</span>
                    <span class="like-count">${reponse.likes.count}</span> J'aime
                </button>
                <button class="reply-to-reply-btn" data-commentaire-id="${reponse.id}" style="background: none; border: 2px solid var(--accent-purple); color: var(--accent-purple); padding: 0.5rem 1rem; border-radius: 20px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; gap: 0.5rem;">
                    💬 Répondre
                </button>
            </div>

            <!-- Formulaire de réponse à une réponse -->
            <div id="reply-to-reply-form-${reponse.id}" style="display: none; margin-top: 1rem; padding: 1rem; background: rgba(139, 92, 246, 0.05); border-radius: 10px; border-left: 3px solid var(--accent-purple);">
                <textarea id="reply-to-reply-content-${reponse.id}" placeholder="Répondre à cette réponse..." style="width: 100%; padding: 0.8rem; border: 2px solid var(--border-color); border-radius: 8px; background: var(--card-bg); color: var(--text-primary); resize: vertical; font-family: inherit; min-height: 80px; box-sizing: border-box;"></textarea>
                <div style="margin-top: 0.8rem; display: flex; gap: 0.8rem;">
                    <button onclick="submitReplyToReply(${reponse.id})" style="background: var(--accent-purple); color: white; padding: 0.6rem 1.2rem; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; transition: all 0.3s ease;">
                        Publier
                    </button>
                    <button onclick="cancelReplyToReply(${reponse.id})" style="background: var(--border-color); color: var(--text-primary); padding: 0.6rem 1.2rem; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; transition: all 0.3s ease;">
                        Annuler
                    </button>
                </div>
            </div>

            <!-- Conteneur pour les réponses aux réponses -->
            <div id="replies-to-replies-${reponse.id}" style="display: none;"></div>
        </div>
    `;
}

function createReponseToReponseHTML(reponseToReponse) {
    return `
        <div style="background: rgba(139, 92, 246, 0.03); border-radius: 12px; padding: 1.5rem; border: 1px solid var(--border-color); margin: 0.8rem 0 0.8rem 20px; border-left: 3px solid #fbbf24;">
            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.8rem;">
                <div style="display: flex; align-items: center; gap: 0.8rem;">
                    <div style="background: #fbbf24; color: white; padding: 0.4rem; border-radius: 50%; width: 35px; height: 35px; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 0.9rem;">
                        ${reponseToReponse.auteur.initial}
                    </div>
                    <div>
                        <h5 style="color: var(--text-primary); margin: 0; font-size: 0.9rem;">${reponseToReponse.auteur.username}</h5>
                        <span style="color: var(--text-secondary); font-size: 0.8rem;">${reponseToReponse.createdAt}</span>
                    </div>
                </div>
            </div>
            <p style="color: var(--text-secondary); line-height: 1.5; margin-bottom: 0.8rem; font-size: 0.9rem;">${reponseToReponse.contenu}</p>
            <div style="display: flex; align-items: center; gap: 0.8rem;">
                <button class="like-btn" data-commentaire-id="${reponseToReponse.id}" style="background: none; border: 2px solid #e11d48; color: #e11d48; padding: 0.4rem 0.8rem; border-radius: 15px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; gap: 0.4rem; font-size: 0.85rem;">
                    <span class="like-icon">🤍</span>
                    <span class="like-count">${reponseToReponse.likes.count}</span> J'aime
                </button>
            </div>
        </div>
    `;
}

function updateCommentairesCount() {
    // Mettre à jour le compteur dans l'onglet
    const tabBtn = document.querySelector('[data-tab="commentaires"]');
    if (tabBtn) {
        const currentText = tabBtn.textContent;
        const match = currentText.match(/\((\d+)\)/);
        if (match) {
            const currentCount = parseInt(match[1]);
            const newCount = currentCount + 1;
            tabBtn.textContent = currentText.replace(/\(\d+\)/, `(${newCount})`);
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
            
            // Ajout dynamique de la réponse sans rechargement
    
            addReplyToComment(commentaireId, data.commentaire);
            updateCommentairesCount();
        } else {
            showNotification(data.message || 'Erreur lors de l\'ajout de la réponse', 'error');
        }
    } catch (error) {
    
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
    
        
        if (response.ok) {
            showNotification('Réponse ajoutée avec succès !', 'success');
            textarea.value = '';
            document.getElementById(`reply-to-reply-form-${reponseId}`).style.display = 'none';
            
            // Ajout dynamique de la réponse à la réponse sans rechargement
    
            addReplyToReply(reponseId, data.commentaire);
            updateCommentairesCount();
        } else {
            showNotification(data.message || 'Erreur lors de l\'ajout de la réponse', 'error');
        }
    } catch (error) {
    
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