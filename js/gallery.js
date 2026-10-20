/**
 * Professional Gallery System
 * Handles filtering, lightbox display, and smooth animations
 * Loads photos from database via API
 */

class GalleryManager {
    constructor() {
        this.currentFilter = 'all';
        this.currentLocationFilter = 'all';
        this.currentImageIndex = 0;
        this.allGalleryItems = [];
        this.filteredItems = [];
        this.locations = new Set();
        
        this.cacheDOM();
        this.init();
    }
    
    cacheDOM() {
        this.filterBtns = document.querySelectorAll('.filter-btn');
        this.galleryGrid = document.getElementById('galleryGrid');
        this.emptyState = document.querySelector('.gallery-empty-state');
        this.locationSelect = document.getElementById('locationSelect');
        
        // Lightbox elements
        this.lightbox = document.getElementById('galleryLightbox');
        this.lightboxImage = document.getElementById('lightboxImage');
        this.lightboxInfo = document.getElementById('lightboxInfo');
        this.lightboxClose = document.getElementById('lightboxClose');
        this.lightboxOverlay = document.getElementById('lightboxOverlay');
        this.lightboxPrev = document.getElementById('lightboxPrev');
        this.lightboxNext = document.getElementById('lightboxNext');
    }
    
    init() {
        this.attachFilterListeners();
        this.attachLocationFilterListener();
        this.attachLightboxListeners();
        this.attachKeyboardShortcuts();
        this.loadPhotosFromDatabase();
    }
    
    /**
     * Load photos from database API
     */
    async loadPhotosFromDatabase() {
        try {
            const response = await fetch('backend/gallery_api.php?action=list');
            const data = await response.json();
            
            if (data.success && data.data) {
                this.allGalleryItems = data.data;
                this.renderGalleryItems();
                this.attachGalleryListeners();
            }
        } catch (error) {
            console.error('Error loading photos:', error);
        }
    }
    
    /**
     * Render gallery items from database
     */
    renderGalleryItems() {
        this.galleryGrid.innerHTML = '';
        
        this.allGalleryItems.forEach((photo, index) => {
            const item = document.createElement('div');
            item.className = 'gallery-item';
            item.setAttribute('data-category', photo.category);
            item.setAttribute('data-location', photo.location || '');
            item.setAttribute('data-reveal', '');
            item.style.setProperty('--delay', `${index * 0.05}s`);
            
            const photoDate = photo.photo_date ? 
                new Date(photo.photo_date).toLocaleDateString('ar-MA') : '';
            const categoryLabel = this.getCategoryLabel(photo.category);
            const locationDisplay = photo.location ? `<p class="gallery-location"><i class="fas fa-location-dot"></i> ${photo.location}</p>` : '';
            
            item.innerHTML = `
                <div class="gallery-image-wrapper">
                    <img src="${photo.image_path}" alt="${photo.title}" loading="lazy">
                    <div class="gallery-overlay">
                        <button class="gallery-open-btn" title="عرض كامل الحجم">
                            <i class="fas fa-expand"></i>
                        </button>
                    </div>
                </div>
                <div class="gallery-info">
                    <h3>${photo.title}</h3>
                    <p class="gallery-category"><i class="fas fa-map-marker-alt"></i> ${categoryLabel}</p>
                    ${locationDisplay}
                    <p class="gallery-date">${photoDate}</p>
                </div>
            `;
            
            this.galleryGrid.appendChild(item);
        });
        
        // Populate location filter
        this.populateLocationFilter();
        
        // Apply initial filter
        this.filterGallery();
    }
    
    /**
     * Get category label
     */
    getCategoryLabel(category) {
        const labels = {
            'trips': 'الرحلات',
            'events': 'الفعاليات',
            'ceremonies': 'الحفلات',
            'activities': 'الأنشطة'
        };
        return labels[category] || category;
    }
    
    /**
     * Filter button event listeners
     */
    attachFilterListeners() {
        this.filterBtns.forEach(btn => {
            btn.addEventListener('click', (e) => this.handleFilterClick(e));
        });
    }
    
    /**
     * Location filter event listener
     */
    attachLocationFilterListener() {
        if (this.locationSelect) {
            this.locationSelect.addEventListener('change', (e) => this.handleLocationChange(e));
        }
    }
    
    /**
     * Populate location dropdown from photos
     */
    populateLocationFilter() {
        // Get unique locations from all photos
        this.allGalleryItems.forEach(photo => {
            if (photo.location && photo.location.trim()) {
                this.locations.add(photo.location);
            }
        });
        
        // Add locations to select dropdown
        if (this.locationSelect && this.locations.size > 0) {
            const sortedLocations = Array.from(this.locations).sort();
            sortedLocations.forEach(location => {
                const option = document.createElement('option');
                option.value = location;
                option.textContent = location;
                this.locationSelect.appendChild(option);
            });
        }
    }
    
    /**
     * Handle location filter change
     */
    handleLocationChange(e) {
        this.currentLocationFilter = e.target.value;
        this.filterGallery();
        this.galleryGrid.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
    handleFilterClick(e) {
        const filterValue = e.currentTarget.getAttribute('data-filter');
        
        // Update active button state
        this.filterBtns.forEach(btn => btn.classList.remove('active'));
        e.currentTarget.classList.add('active');
        
        // Update current filter
        this.currentFilter = filterValue;
        
        // Apply filter
        this.filterGallery();
        
        // Smooth scroll to gallery
        this.galleryGrid.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
    
    /**
     * Filter gallery items
     */
    filterGallery() {
        let visibleCount = 0;
        
        this.galleryGrid.querySelectorAll('.gallery-item').forEach((item, index) => {
            const category = item.getAttribute('data-category');
            const location = item.getAttribute('data-location');
            
            const categoryMatch = this.currentFilter === 'all' || category === this.currentFilter;
            const locationMatch = this.currentLocationFilter === 'all' || location === this.currentLocationFilter;
            const isVisible = categoryMatch && locationMatch;
            
            if (isVisible) {
                item.style.display = 'block';
                item.style.animation = `fadeInUp 0.6s ease ${visibleCount * 0.05}s forwards`;
                item.style.opacity = '0';
                setTimeout(() => {
                    item.style.animation = '';
                    item.style.opacity = '1';
                }, 10);
                visibleCount++;
            } else {
                item.style.display = 'none';
            }
        });
        
        // Show/hide empty state
        if (visibleCount === 0) {
            this.emptyState.style.display = 'grid';
        } else {
            this.emptyState.style.display = 'none';
        }
    }
    
    /**
     * Gallery image click listeners
     */
    attachGalleryListeners() {
        document.querySelectorAll('.gallery-open-btn').forEach((btn) => {
            btn.addEventListener('click', () => {
                const item = btn.closest('.gallery-item');
                this.openLightbox(item);
            });
        });
    }
    
    /**
     * Open lightbox with selected image
     */
    openLightbox(item) {
        const isVisible = item.offsetParent !== null;
        if (!isVisible) return;
        
        // Find this item in the current filtered list
        const category = item.getAttribute('data-category');
        this.filteredItems = Array.from(document.querySelectorAll('.gallery-item')).filter(el => {
            if (this.currentFilter === 'all') return el.offsetParent !== null;
            return el.getAttribute('data-category') === category && el.offsetParent !== null;
        });
        
        this.currentImageIndex = this.filteredItems.indexOf(item);
        
        this.displayLightboxImage();
        this.lightbox.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    
    /**
     * Display image in lightbox
     */
    displayLightboxImage() {
        const item = this.filteredItems[this.currentImageIndex];
        const img = item.querySelector('img');
        const h3 = item.querySelector('h3');
        const category = item.querySelector('.gallery-category').textContent;
        const date = item.querySelector('.gallery-date').textContent;
        
        this.lightboxImage.src = img.src;
        this.lightboxImage.alt = img.alt;
        
        this.lightboxInfo.innerHTML = `
            <div>
                <h3>${h3.textContent}</h3>
                <p>${category} • ${date}</p>
            </div>
        `;
    }
    
    /**
     * Lightbox event listeners
     */
    attachLightboxListeners() {
        this.lightboxClose.addEventListener('click', () => this.closeLightbox());
        this.lightboxOverlay.addEventListener('click', () => this.closeLightbox());
        this.lightboxPrev.addEventListener('click', () => this.previousImage());
        this.lightboxNext.addEventListener('click', () => this.nextImage());
    }
    
    /**
     * Close lightbox
     */
    closeLightbox() {
        this.lightbox.classList.remove('active');
        document.body.style.overflow = '';
    }
    
    /**
     * Navigate to next image
     */
    nextImage() {
        this.currentImageIndex = (this.currentImageIndex + 1) % this.filteredItems.length;
        this.displayLightboxImage();
    }
    
    /**
     * Navigate to previous image
     */
    previousImage() {
        this.currentImageIndex = (this.currentImageIndex - 1 + this.filteredItems.length) % this.filteredItems.length;
        this.displayLightboxImage();
    }
    
    /**
     * Attach keyboard shortcuts
     */
    attachKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            if (!this.lightbox.classList.contains('active')) return;
            
            if (e.key === 'Escape') this.closeLightbox();
            if (e.key === 'ArrowRight') this.nextImage();
            if (e.key === 'ArrowLeft') this.previousImage();
        });
    }
}

// Initialize gallery when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    new GalleryManager();
});

// Add animations on scroll
document.addEventListener('DOMContentLoaded', () => {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('revealed');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1 });
    
    document.querySelectorAll('[data-reveal]').forEach(el => {
        observer.observe(el);
    });
});
