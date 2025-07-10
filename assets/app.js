import './bootstrap.js';
/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */
import './styles/app.css';

console.log('This log comes from assets/app.js - welcome to AssetMapper! 🎉');

// Gestion des onglets
document.addEventListener('DOMContentLoaded', function() {
    initTabs();
    initCommentairesForm();
    
    // Activer l'onglet spécifié dans l'URL
    const urlParams = new URLSearchParams(window.location.search);
    const ongletParam = urlParams.get('onglet');
    console.log('Paramètre onglet dans URL:', ongletParam);
    
    if (ongletParam === 'commentaires') {
        const targetButton = document.querySelector(`[data-tab="${ongletParam}"]`);
        console.log('Bouton trouvé pour paramètre URL:', targetButton);
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

    console.log('Initialisation des onglets - Boutons trouvés:', tabButtons.length);
    console.log('Initialisation des onglets - Contenus trouvés:', tabContents.length);

    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetTab = this.getAttribute('data-tab');
            console.log('Clic sur onglet:', targetTab);
            
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
            console.log('Contenu trouvé pour onglet:', activeContent);
            
            if (activeContent) {
                activeContent.classList.add('active');
                activeContent.style.display = 'block';
                console.log('Onglet affiché:', targetTab);
                
                // Si on clique sur l'onglet commentaires, charger les commentaires
                if (targetTab === 'commentaires') {
                    console.log('Chargement des commentaires...');
                    loadCommentaires();
                }
            } else {
                console.error('Impossible de trouver le contenu pour l\'onglet:', targetTab);
            }
        });
    });
}

function initCommentairesForm() {
    const form = document.getElementById('commentaire-form');
    console.log('Formulaire commentaire trouvé:', form);
    
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            submitCommentaire();
        });
    }
}

// Fonction de secours pour forcer l'affichage de l'onglet commentaires
function forceShowCommentaires() {
    console.log('Forçage de l\'affichage des commentaires...');
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
        
        console.log('Onglet commentaires forcé à s\'afficher');
        return true;
    }
    console.error('Impossible de forcer l\'affichage - éléments manquants');
    return false;
}

async function loadCommentaires() {
    const oeuvreId = getOeuvreIdFromUrl();
    console.log('ID de l\'oeuvre extraite:', oeuvreId);
    
    if (!oeuvreId) {
        console.error('Impossible de récupérer l\'ID de l\'oeuvre depuis l\'URL');
        return;
    }

    try {
        console.log('Chargement des commentaires via API...');
        const response = await fetch(`/api/commentaires/oeuvre/${oeuvreId}`);
        console.log('Réponse API reçue:', response.status);
        
        const data = await response.json();
        console.log('Données reçues:', data);
        
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
    const note = document.getElementById('commentaire_note')?.value;

    if (!contenu || !note) {
        showNotification('Veuillez remplir tous les champs', 'error');
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
                contenu: contenu,
                note: parseInt(note)
            })
        });

        const data = await response.json();

        if (response.ok) {
            showNotification('Commentaire ajouté avec succès !', 'success');
            document.getElementById('commentaire_contenu').value = '';
            document.getElementById('commentaire_note').value = '';
            loadCommentaires(); // Recharger les commentaires
        } else {
            showNotification(data.message || 'Erreur lors de l\'ajout du commentaire', 'error');
        }
    } catch (error) {
        console.error('Erreur:', error);
        showNotification('Erreur lors de l\'ajout du commentaire', 'error');
    }
}

function updateCommentairesDisplay(data) {
    const commentairesContainer = document.getElementById('commentaires-list');
    if (!commentairesContainer) return;

    // Mettre à jour la moyenne si elle existe
    const moyenneContainer = document.getElementById('moyenne-container');
    if (moyenneContainer && data.moyenne) {
        moyenneContainer.innerHTML = `
            <div style="background: linear-gradient(135deg, #fbbf24, #f59e0b); color: white; padding: 2rem; border-radius: 15px; margin-bottom: 2rem; text-align: center;">
                <h3 style="font-size: 2rem; margin-bottom: 0.5rem;">⭐ Note moyenne</h3>
                <div style="font-size: 3rem; font-weight: 800;">${data.moyenne}/5</div>
                <div style="font-size: 1.2rem; opacity: 0.9;">${data.total} avis</div>
            </div>
        `;
    }

    // Mettre à jour la liste des commentaires
    if (data.commentaires.length > 0) {
        commentairesContainer.innerHTML = data.commentaires.map(commentaire => `
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
                    <div style="background: linear-gradient(135deg, #fbbf24, #f59e0b); color: white; padding: 0.5rem 1rem; border-radius: 20px; font-weight: 600; font-size: 1.1rem;">
                        ⭐ ${commentaire.note}/5
                    </div>
                </div>
                <p style="color: var(--text-secondary); line-height: 1.6; margin: 0; font-size: 1.1rem;">${commentaire.contenu}</p>
            </div>
        `).join('');
    } else {
        commentairesContainer.innerHTML = `
            <div style="text-align: center; padding: 4rem 2rem; color: var(--text-secondary);">
                <div style="font-size: 4rem; margin-bottom: 1rem;">💬</div>
                <h3 style="color: var(--text-primary); margin-bottom: 1rem;">Aucun commentaire</h3>
                <p>Soyez le premier à donner votre avis sur cette œuvre !</p>
            </div>
        `;
    }
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
