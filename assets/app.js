import './bootstrap.js';
/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */
import './styles/app.css';
import './styles/components/burger-menu.css';
import './styles/components/flash-messages.css';

// Import du lecteur de chapitre optimisé
import './js/chapter-reader.js';

// Gestion des onglets
document.addEventListener('DOMContentLoaded', function() {
    initTabs();
    initCommentairesForm();
    
    // Activer l'onglet spécifié dans l'URL
    const urlParams = new URLSearchParams(window.location.search);
    const ongletParam = urlParams.get('onglet');
    
    if (ongletParam === 'commentaires') {
        const targetButton = document.querySelector(`[data-tab="${ongletParam}"]`);
        if (targetButton) {
            setTimeout(() => {
                targetButton.click();
                // Si le clic ne fonctionne pas, forcer l'affichage
                setTimeout(() => {
                    const commentairesTab = document.getElementById('tab-commentaires');
                    if (commentairesTab && commentairesTab.style.display === 'none') {
                        forceShowCommentaires();
                    }
                }, 200);
            }, 100);
        }
    }
});

// Exposer les fonctions utiles globalement pour le débogage
window.forceShowCommentaires = forceShowCommentaires;
window.loadCommentaires = loadCommentaires;

function initTabs() {
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');

    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetTab = this.getAttribute('data-tab');
            
            // Retirer la classe active de tous les boutons et contenus
            tabButtons.forEach(btn => {
                btn.classList.remove('active');
                btn.style.background = 'rgba(139, 92, 246, 0.1)';
                btn.style.color = 'var(--text-secondary)';
            });
            
            tabContents.forEach(content => {
                content.classList.remove('active');
                content.style.display = 'none';
            });
            
            // Ajouter la classe active au bouton et contenu sélectionnés
            this.classList.add('active');
            this.style.background = 'var(--accent-purple)';
            this.style.color = 'white';
            
            const activeContent = document.getElementById(`tab-${targetTab}`);
            
            if (activeContent) {
                activeContent.classList.add('active');
                activeContent.style.display = 'block';
                
                // Si on clique sur l'onglet commentaires, garder le rendu Twig côté serveur
                if (targetTab === 'commentaires') {
                    // loadCommentaires(); // Désactivé pour garder les réponses et boutons "Voir réponse"
                }
            } else {
                console.error('Impossible de trouver le contenu pour l\'onglet:', targetTab);
            }
        });
    });
}

function initCommentairesForm() {
    const form = document.getElementById('commentaire-form');
    
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            submitCommentaire();
        });
    }
}

// Fonction de secours pour forcer l'affichage de l'onglet commentaires
function forceShowCommentaires() {
    const commentairesTab = document.getElementById('tab-commentaires');
    const chapitresTab = document.getElementById('tab-chapitres');
    const commentairesBtn = document.querySelector('[data-tab="commentaires"]');
    const chapitresBtn = document.querySelector('[data-tab="chapitres"]');
    
    if (commentairesTab && chapitresTab && commentairesBtn && chapitresBtn) {
        // Masquer l'onglet chapitres
        chapitresTab.style.display = 'none';
        chapitresTab.classList.remove('active');
        chapitresBtn.classList.remove('active');
        chapitresBtn.style.background = 'rgba(139, 92, 246, 0.1)';
        chapitresBtn.style.color = 'var(--text-secondary)';
        
        // Afficher l'onglet commentaires
        commentairesTab.style.display = 'block';
        commentairesTab.classList.add('active');
        commentairesBtn.classList.add('active');
        commentairesBtn.style.background = 'var(--accent-purple)';
        commentairesBtn.style.color = 'white';
        
        return true;
    }
    console.error('Impossible de forcer l\'affichage - éléments manquants');
    return false;
}

async function loadCommentaires() {
    const oeuvreId = getOeuvreIdFromUrl();
    
    if (!oeuvreId) {
        console.error('Impossible de récupérer l\'ID de l\'oeuvre depuis l\'URL');
        return;
    }

    try {
        const response = await fetch(`/api/commentaires/oeuvre/${oeuvreId}`);
        
        const data = await response.json();
        
        if (response.ok) {
            updateCommentairesDisplay(data);
            updateCommentairesCount(data.total);
        } else {
            console.error('Erreur API:', data);
            showNotification('Erreur lors du chargement des commentaires', 'error');
        }
    } catch (error) {
        console.error('Erreur lors du chargement des commentaires:', error);
        showNotification('Impossible de charger les commentaires. La page sera rechargée.', 'error');
        
        // Si l'API ne fonctionne pas, on peut recharger la page
        setTimeout(() => {
            window.location.reload();
        }, 2000);
    }
}

async function submitCommentaire() {
    const oeuvreId = getOeuvreIdFromUrl();
    if (!oeuvreId) return;

    const contenu = document.getElementById('commentaire_contenu')?.value;

    if (!contenu || contenu.trim() === '') {
        showNotification('Veuillez saisir un commentaire', 'error');
        return;
    }

    try {
        const response = await fetch(`/api/commentaires/oeuvre/${oeuvreId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                contenu: contenu.trim()
            })
        });

        const data = await response.json();

        if (response.ok) {
            showNotification('Commentaire ajouté avec succès !', 'success');
            document.getElementById('commentaire_contenu').value = '';
            // Recharger les commentaires dynamiquement au lieu de recharger la page
            await loadCommentaires();
        } else {
            showNotification(data.message || 'Erreur lors de l\'ajout du commentaire', 'error');
        }
    } catch (error) {
        console.error('Erreur:', error);
        showNotification('Erreur lors de l\'ajout du commentaire', 'error');
    }
}

// Nouvelle fonction pour soumettre une réponse
async function submitReply(commentaireId) {
    const oeuvreId = getOeuvreIdFromUrl();
    if (!oeuvreId) return;

    const contenu = document.getElementById(`reply-content-${commentaireId}`)?.value;

    if (!contenu || contenu.trim() === '') {
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
            body: JSON.stringify({
                contenu: contenu.trim()
            })
        });

        const data = await response.json();

        if (response.ok) {
            showNotification('Réponse ajoutée avec succès !', 'success');
            document.getElementById(`reply-content-${commentaireId}`).value = '';
            cancelReply(commentaireId);
            // Recharger les commentaires pour afficher la nouvelle réponse
            await loadCommentaires();
        } else {
            showNotification(data.message || 'Erreur lors de l\'ajout de la réponse', 'error');
        }
    } catch (error) {
        console.error('Erreur:', error);
        showNotification('Erreur lors de l\'ajout de la réponse', 'error');
    }
}

// Fonction pour annuler une réponse
function cancelReply(commentaireId) {
    const replyForm = document.getElementById(`reply-form-${commentaireId}`);
    const replyContent = document.getElementById(`reply-content-${commentaireId}`);
    
    if (replyForm) {
        replyForm.style.display = 'none';
    }
    if (replyContent) {
        replyContent.value = '';
    }
}

// Fonction pour afficher le formulaire de réponse
function showReplyForm(commentaireId) {
    const replyForm = document.getElementById(`reply-form-${commentaireId}`);
    if (replyForm) {
        replyForm.style.display = 'block';
        const textarea = document.getElementById(`reply-content-${commentaireId}`);
        if (textarea) {
            textarea.focus();
        }
    }
}

// Fonction pour initialiser les boutons d'interaction
function initInteractionButtons() {
    // Boutons "Répondre"
    document.querySelectorAll('.reply-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const commentaireId = this.dataset.commentaireId;
            showReplyForm(commentaireId);
        });
    });

    // Boutons "J'aime" - À implémenter si nécessaire
    document.querySelectorAll('.like-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const commentaireId = this.dataset.commentaireId;
            toggleLike(commentaireId);
        });
    });
}

// Fonction pour gérer les likes (optionnelle)
async function toggleLike(commentaireId) {
    try {
        const response = await fetch(`/api/commentaires/${commentaireId}/likes`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (response.ok) {
            // Recharger les commentaires pour mettre à jour les compteurs de likes
            await loadCommentaires();
        } else {
            showNotification('Erreur lors de l\'action sur le like', 'error');
        }
    } catch (error) {
        console.error('Erreur like:', error);
        showNotification('Erreur lors de l\'action sur le like', 'error');
    }
}

// Exposer les nouvelles fonctions globalement
window.submitReply = submitReply;
window.cancelReply = cancelReply;
window.showReplyForm = showReplyForm;
window.initInteractionButtons = initInteractionButtons;

function updateCommentairesDisplay(data) {
    const commentairesContainer = document.getElementById('commentaires-list');
    if (!commentairesContainer) return;

    // Mettre à jour la moyenne si elle existe
    const moyenneContainer = document.getElementById('moyenne-container');
    if (moyenneContainer && data.notes) {
        moyenneContainer.innerHTML = `
            <div style="background: linear-gradient(135deg, #fbbf24, #f59e0b); color: white; padding: 2rem; border-radius: 15px; margin-bottom: 2rem; text-align: center;">
                <h3 style="font-size: 2rem; margin-bottom: 0.5rem;">⭐ Note moyenne</h3>
                <div style="font-size: 3rem; font-weight: 800;">${data.notes.average || 0}/5</div>
                <div style="font-size: 1.2rem; opacity: 0.9;">${data.notes.total || 0} avis</div>
            </div>
        `;
    }

    // Fonction pour afficher récursivement les commentaires et leurs réponses
    function renderCommentaire(commentaire, isReponse = false) {
        const marginLeft = isReponse ? '2rem' : '0';
        const borderLeft = isReponse ? '3px solid var(--accent-purple)' : 'none';
        const paddingLeft = isReponse ? '1rem' : '0';
        
        let reponsesHtml = '';
        if (commentaire.reponses && commentaire.reponses.length > 0) {
            reponsesHtml = commentaire.reponses.map(reponse => renderCommentaire(reponse, true)).join('');
        }

        return `
            <div style="margin-left: ${marginLeft}; border-left: ${borderLeft}; padding-left: ${paddingLeft};">
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
                <p style="color: var(--text-secondary); line-height: 1.6; margin-bottom: 1.5rem; font-size: 1.1rem;">${commentaire.contenu}</p>
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <button class="like-btn" data-commentaire-id="${commentaire.id}" style="background: none; border: 2px solid #e11d48; color: #e11d48; padding: 0.5rem 1rem; border-radius: 20px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; gap: 0.5rem;">
                            <span class="like-icon">${commentaire.likes?.isLikedByUser ? '❤️' : '🤍'}</span>
                            <span class="like-count">${commentaire.likes?.count || 0}</span> J'aime
                    </button>
                        ${!isReponse ? `
                    <button class="reply-btn" data-commentaire-id="${commentaire.id}" style="background: none; border: 2px solid var(--accent-purple); color: var(--accent-purple); padding: 0.5rem 1rem; border-radius: 20px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; gap: 0.5rem;">
                        💬 Répondre
                    </button>
                        ` : ''}
                </div>
                
                    ${!isReponse ? `
                <!-- Formulaire de réponse (caché par défaut) -->
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
                    ` : ''}
                </div>
                ${reponsesHtml}
            </div>
        `;
    }

    // Mettre à jour la liste des commentaires
    if (data.commentaires && data.commentaires.length > 0) {
        commentairesContainer.innerHTML = data.commentaires.map(commentaire => renderCommentaire(commentaire)).join('');
    } else {
        commentairesContainer.innerHTML = `
            <div style="text-align: center; padding: 4rem 2rem; color: var(--text-secondary);">
                <div style="font-size: 4rem; margin-bottom: 1rem;">💬</div>
                <h3 style="color: var(--text-primary); margin-bottom: 1rem;">Aucun commentaire</h3>
                <p>Soyez le premier à donner votre avis sur cette œuvre !</p>
            </div>
        `;
    }
    
    // Initialiser les event listeners pour les nouveaux boutons
    initInteractionButtons();
}

function updateCommentairesCount(count) {
    const commentaireTab = document.querySelector('[data-tab="commentaires"]');
    if (commentaireTab) {
        commentaireTab.innerHTML = `💬 Commentaires (${count})`;
    }
}

function getOeuvreIdFromUrl() {
    const path = window.location.pathname;
    const matches = path.match(/\/oeuvres\/(\d+)/);
    return matches ? matches[1] : null;
}

function showNotification(message, type = 'info') {
    // Créer la notification
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6'};
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 10px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        z-index: 1000;
        max-width: 300px;
        animation: slideIn 0.3s ease;
    `;
    notification.textContent = message;

    // Ajouter l'animation CSS
    if (!document.querySelector('#notification-styles')) {
        const style = document.createElement('style');
        style.id = 'notification-styles';
        style.textContent = `
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOut {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
        `;
        document.head.appendChild(style);
    }

    document.body.appendChild(notification);

    // Supprimer la notification après 4 secondes
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => {
            document.body.removeChild(notification);
        }, 300);
    }, 4000);
}

// ================== SYSTÈME DE FAVORIS ==================

// Fonction pour basculer l'état de favori
async function toggleFavorite(oeuvreId, button) {
    try {
        const response = await fetch(`/collections/toggle/${oeuvreId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const data = await response.json();

        if (data.success) {
            updateFavoriteButton(oeuvreId, data.isFavorite);
            showNotification(data.message, 'success');
        } else {
            showNotification(data.message || 'Erreur lors de la modification des favoris', 'error');
        }
    } catch (error) {
        console.error('Erreur lors de la modification des favoris:', error);
        showNotification('Erreur lors de la modification des favoris', 'error');
    }
}

// Fonction pour mettre à jour l'affichage du bouton de favoris
function updateFavoriteButton(oeuvreId, isFavorite) {
    const button = document.getElementById(`favorite-btn-${oeuvreId}`);
    const icon = document.getElementById(`favorite-icon-${oeuvreId}`);
    const text = document.getElementById(`favorite-text-${oeuvreId}`);
    
    if (button && icon && text) {
        if (isFavorite) {
            button.classList.add('favorited');
            button.setAttribute('title', 'Retirer des favoris');
            icon.textContent = '❤️';
            text.textContent = 'Retirer des favoris';
        } else {
            button.classList.remove('favorited');
            button.setAttribute('title', 'Ajouter aux favoris');
            icon.textContent = '🤍';
            text.textContent = 'Ajouter aux favoris';
        }
    }
}

// Exposer les fonctions globalement
window.toggleFavorite = toggleFavorite;
window.updateFavoriteButton = updateFavoriteButton;
