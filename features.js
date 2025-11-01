
class ShareManager {
    constructor() {
        this.attachEventListeners();
    }

    attachEventListeners() {
        document.addEventListener('click', (e) => {
            const btn = e.target.closest('.share-btn');
            if (btn) {
                e.preventDefault();
                e.stopPropagation();
                const bookId = btn.dataset.bookId;
                const bookTitle = btn.dataset.bookTitle;
                this.shareBook(bookId, bookTitle);
            }
        });
    }
}
class HistoryManager {
    constructor() {
        this.storageKey = 'recentViews';
        this.maxItems = 10;
    }

    addToHistory(bookData) {
        let history = this.getHistory();
        history = history.filter(item => item.id !== bookData.id);
        
        history.unshift({
            id: bookData.id,
            title: bookData.title,
            author: bookData.author,
            image: bookData.image,
            category: bookData.category,
            viewedAt: new Date().toISOString()
        });
        
        history = history.slice(0, this.maxItems);
        
        localStorage.setItem(this.storageKey, JSON.stringify(history));
    }

    getHistory() {
        const history = localStorage.getItem(this.storageKey);
        return history ? JSON.parse(history) : [];
    }

    clearHistory() {
        localStorage.removeItem(this.storageKey);
    }
}

class RatingsManager {
    constructor() {
        this.storageKey = 'userRatings';
    }

    addRating(userId, rating, review) {
        let ratings = this.getRatings(userId);
        
        ratings.push({
            rating: rating,
            review: review,
            reviewerName: 'Utilisateur',
            date: new Date().toISOString()
        });
        
        localStorage.setItem(`${this.storageKey}_${userId}`, JSON.stringify(ratings));
    }

    getRatings(userId) {
        const ratings = localStorage.getItem(`${this.storageKey}_${userId}`);
        return ratings ? JSON.parse(ratings) : [];
    }

    getAverageRating(userId) {
        const ratings = this.getRatings(userId);
        if (ratings.length === 0) return 0;
        
        const sum = ratings.reduce((acc, r) => acc + r.rating, 0);
        return (sum / ratings.length).toFixed(1);
    }

    showRatingModal(userId, userName) {
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.innerHTML = `
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Évaluer ${userName}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Votre note</label>
                            <div class="rating-stars" style="font-size: 2rem; color: #fbbf24; cursor: pointer;">
                                <span data-rating="1">☆</span>
                                <span data-rating="2">☆</span>
                                <span data-rating="3">☆</span>
                                <span data-rating="4">☆</span>
                                <span data-rating="5">☆</span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Votre avis (optionnel)</label>
                            <textarea class="form-control" id="reviewText" rows="3" placeholder="Partagez votre expérience..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="button" class="btn btn-primary" id="submitRating">Soumettre</button>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        const modalInstance = new bootstrap.Modal(modal);
        modalInstance.show();
        
        let selectedRating = 0;
        
        modal.querySelectorAll('.rating-stars span').forEach(star => {
            star.addEventListener('click', () => {
                selectedRating = parseInt(star.dataset.rating);
                modal.querySelectorAll('.rating-stars span').forEach((s, index) => {
                    s.textContent = index < selectedRating ? '★' : '☆';
                });
            });
            
            star.addEventListener('mouseover', () => {
                const rating = parseInt(star.dataset.rating);
                modal.querySelectorAll('.rating-stars span').forEach((s, index) => {
                    s.textContent = index < rating ? '★' : '☆';
                });
            });
        });
        
        modal.querySelector('.rating-stars').addEventListener('mouseleave', () => {
            modal.querySelectorAll('.rating-stars span').forEach((s, index) => {
                s.textContent = index < selectedRating ? '★' : '☆';
            });
        });
        
        modal.querySelector('#submitRating').addEventListener('click', () => {
            if (selectedRating === 0) {
                alert('Veuillez sélectionner une note');
                return;
            }
            
            const review = modal.querySelector('#reviewText').value;
            this.addRating(userId, selectedRating, review);
            
            modalInstance.hide();
            if (typeof showToast === 'function') {
                showToast('Évaluation soumise', 'Merci pour votre avis!', 'success');
            }
        });
        
        modal.addEventListener('hidden.bs.modal', () => {
            modal.remove();
        });
    }
}

class CalendarManager {
    constructor() {
        this.storageKey = 'bookAvailability';
    }

    showCalendar(bookId, bookTitle) {
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.innerHTML = `
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Disponibilité - ${bookTitle}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Sélectionnez vos dates</label>
                            <div class="row">
                                <div class="col-md-6">
                                    <input type="date" class="form-control" id="startDate" min="${new Date().toISOString().split('T')[0]}">
                                    <small class="text-muted">Date de début</small>
                                </div>
                                <div class="col-md-6">
                                    <input type="date" class="form-control" id="endDate" min="${new Date().toISOString().split('T')[0]}">
                                    <small class="text-muted">Date de fin</small>
                                </div>
                            </div>
                        </div>
                        <div class="alert alert-info">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="me-2" viewBox="0 0 16 16">
                                <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/>
                                <path d="m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0"/>
                            </svg>
                            Ce livre est disponible pour emprunt. Sélectionnez vos dates pour réserver.
                        </div>
                        <div id="reservedDates"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                        <button type="button" class="btn btn-primary" id="reserveBtn">Réserver</button>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        const modalInstance = new bootstrap.Modal(modal);
        modalInstance.show();
        
        modal.querySelector('#reserveBtn').addEventListener('click', () => {
            const startDate = modal.querySelector('#startDate').value;
            const endDate = modal.querySelector('#endDate').value;
            
            if (!startDate || !endDate) {
                alert('Veuillez sélectionner les dates');
                return;
            }
            
            if (new Date(endDate) < new Date(startDate)) {
                alert('La date de fin doit être après la date de début');
                return;
            }
            
            this.saveReservation(bookId, startDate, endDate);
            
            modalInstance.hide();
            if (typeof showToast === 'function') {
                showToast('Réservation confirmée', `Livre réservé du ${startDate} au ${endDate}`, 'success');
            }
        });
        
        modal.addEventListener('hidden.bs.modal', () => {
            modal.remove();
        });
    }

    saveReservation(bookId, startDate, endDate) {
        let reservations = this.getReservations();
        
        if (!reservations[bookId]) {
            reservations[bookId] = [];
        }
        
        reservations[bookId].push({
            startDate: startDate,
            endDate: endDate,
            reservedAt: new Date().toISOString()
        });
        
        localStorage.setItem(this.storageKey, JSON.stringify(reservations));
    }

    getReservations() {
        const reservations = localStorage.getItem(this.storageKey);
        return reservations ? JSON.parse(reservations) : {};
    }

    isAvailable(bookId, startDate, endDate) {
        const reservations = this.getReservations();
        if (!reservations[bookId]) return true;
        
        return !reservations[bookId].some(r => {
            return (
                (new Date(startDate) >= new Date(r.startDate) && new Date(startDate) <= new Date(r.endDate)) ||
                (new Date(endDate) >= new Date(r.startDate) && new Date(endDate) <= new Date(r.endDate))
            );
        });
    }
}

document.addEventListener('DOMContentLoaded', function() {
    window.shareManager = new ShareManager();
    window.historyManager = new HistoryManager();
    window.ratingsManager = new RatingsManager();
    window.calendarManager = new CalendarManager();
    
    console.log('✨ All features loaded successfully!');
});

