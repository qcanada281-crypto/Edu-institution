/**
 * Blog Media Gallery - Dynamic loading and filtering of blog posts with media
 */

class BlogMediaGallery {
    constructor() {
        this.allPosts = [];
        this.filteredPosts = [];
        this.currentFilter = 'all';
        this.mediaGrid = document.getElementById('mediaGrid');
        this.filterButtons = document.querySelectorAll('.filter-btn');
        this.viewMoreBtn = document.getElementById('viewMoreBtn');
        this.postsPerPage = 12;
        this.currentPage = 1;
        
        this.init();
    }
    
    init() {
        // Load all posts on page load
        this.loadMediaPosts();
        
        // Attach filter button listeners
        this.filterButtons.forEach(btn => {
            btn.addEventListener('click', (e) => this.handleFilterClick(e));
        });
        
        // Attach view more button listener
        if (this.viewMoreBtn) {
            this.viewMoreBtn.addEventListener('click', () => this.loadMorePosts());
        }
    }
    
    /**
     * Load media posts from backend API
     */
    async loadMediaPosts() {
        try {
            this.showLoading();
            
            const response = await fetch('/backend/blog_api.php?action=list');
            const data = await response.json();
            
            if (!data.success) {
                this.showError('فشل تحميل الفعاليات');
                return;
            }
            
            this.allPosts = data.data || [];
            this.filteredPosts = [...this.allPosts];
            
            // Render first batch of posts
            this.renderPosts(this.filteredPosts.slice(0, this.postsPerPage));
            
            // Show/hide view more button
            this.updateViewMoreButton();
            
            // Re-attach filter listeners in case they were needed
            this.attachPostListeners();
            
        } catch (error) {
            console.error('Error loading posts:', error);
            this.showError('حدث خطأ في تحميل الفعاليات');
        }
    }
    
    /**
     * Handle filter button clicks
     */
    async handleFilterClick(e) {
        const filterType = e.target.getAttribute('data-filter');
        this.currentFilter = filterType;
        this.currentPage = 1;
        
        // Update active button state
        this.filterButtons.forEach(btn => btn.classList.remove('active'));
        e.target.classList.add('active');
        
        try {
            this.showLoading();
            
            if (filterType === 'all') {
                this.filteredPosts = [...this.allPosts];
            } else {
                const response = await fetch(`/backend/blog_api.php?action=by_type&type=${filterType}`);
                const data = await response.json();
                
                if (data.success) {
                    this.filteredPosts = data.data || [];
                } else {
                    this.filteredPosts = [];
                }
            }
            
            // Render first batch
            this.renderPosts(this.filteredPosts.slice(0, this.postsPerPage));
            this.updateViewMoreButton();
            this.attachPostListeners();
            
        } catch (error) {
            console.error('Error filtering posts:', error);
            this.showError('حدث خطأ في تصفية الفعاليات');
        }
    }
    
    /**
     * Render posts to the grid
     */
    renderPosts(posts) {
        if (posts.length === 0) {
            this.mediaGrid.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <p>لا توجد فعاليات في هذه الفئة</p>
                </div>
            `;
            return;
        }
        
        // For first render, clear the grid. For appending, don't clear.
        if (this.currentPage === 1) {
            this.mediaGrid.innerHTML = '';
        }
        
        posts.forEach((post, index) => {
            const card = this.createMediaCard(post, index);
            this.mediaGrid.appendChild(card);
        });
    }
    
    /**
     * Create a media card element
     */
    createMediaCard(post, index) {
        const card = document.createElement('div');
        card.className = 'media-card';
        if (post.media_type === 'video') {
            card.classList.add('video-card');
        }
        card.setAttribute('data-id', post.id);
        card.setAttribute('data-reveal', '');
        card.style.setProperty('--delay', `${index * 0.05}s`);
        
        // Format date
        const date = new Date(post.publish_date).toLocaleDateString('ar-MA');
        
        // Get appropriate icon for media type
        let mediaIcon = 'fa-image';
        if (post.media_type === 'video') {
            mediaIcon = 'fa-video';
        } else if (post.media_type === 'gallery') {
            mediaIcon = 'fa-images';
        }
        
        // Get thumbnail
        let thumbnailHTML = '';
        if (post.featured_image) {
            thumbnailHTML = `<img src="${post.featured_image}" alt="${post.title}" loading="lazy">`;
        } else if (post.video_url) {
            // Extract video ID from YouTube URL if possible
            const videoThumb = this.getYouTubeThumbnail(post.video_url);
            if (videoThumb) {
                thumbnailHTML = `<img src="${videoThumb}" alt="${post.title}" loading="lazy">`;
            }
        }
        
        // Add play button for videos
        let playBtn = '';
        if (post.media_type === 'video') {
            playBtn = `<div class="media-play-btn"><i class="fas fa-play"></i></div>`;
        }
        
        card.innerHTML = `
            <div class="media-thumbnail">
                ${thumbnailHTML}
                ${playBtn}
            </div>
            <div class="media-info">
                <div class="media-type-badge">
                    <i class="fas ${mediaIcon}"></i>
                    <span>${this.getPostTypeLabel(post.post_type)}</span>
                </div>
                <h3>${post.title}</h3>
                <p>${post.description || ''}</p>
                <div class="media-footer">
                    <span class="media-author"><i class="fas fa-user"></i> ${post.author_name}</span>
                    <span class="media-date">${date}</span>
                </div>
            </div>
        `;
        
        card.addEventListener('click', () => this.openPostDetail(post));
        
        return card;
    }
    
    /**
     * Get label for post type
     */
    getPostTypeLabel(type) {
        const labels = {
            'event': 'فعالية',
            'seminar': 'ندوة',
            'workshop': 'ورشة عمل',
            'activity': 'نشاط',
            'competition': 'مسابقة',
            'article': 'مقالة'
        };
        return labels[type] || type;
    }
    
    /**
     * Get YouTube thumbnail from URL
     */
    getYouTubeThumbnail(url) {
        const videoId = url.match(/(?:youtube\.com\/embed\/|youtube\.com\/watch\?v=|youtu\.be\/)([^&\n?#]+)/);
        if (videoId && videoId[1]) {
            return `https://img.youtube.com/vi/${videoId[1]}/hqdefault.jpg`;
        }
        return null;
    }
    
    /**
     * Load more posts (pagination)
     */
    loadMorePosts() {
        if (!this.viewMoreBtn) return;
        
        this.currentPage++;
        const startIdx = (this.currentPage - 1) * this.postsPerPage;
        const endIdx = startIdx + this.postsPerPage;
        const newPosts = this.filteredPosts.slice(startIdx, endIdx);
        
        // Render new posts
        const tempContainer = document.createElement('div');
        newPosts.forEach((post, index) => {
            const card = this.createMediaCard(post, startIdx + index);
            tempContainer.appendChild(card);
        });
        
        // Append to grid
        this.mediaGrid.appendChild(tempContainer);
        
        // Update button visibility
        this.updateViewMoreButton();
    }
    
    /**
     * Update view more button visibility
     */
    updateViewMoreButton() {
        if (!this.viewMoreBtn) return;
        
        const totalDisplayed = this.currentPage * this.postsPerPage;
        if (totalDisplayed < this.filteredPosts.length) {
            this.viewMoreBtn.style.display = 'inline-flex';
        } else {
            this.viewMoreBtn.style.display = 'none';
        }
    }
    
    /**
     * Open post detail (can be extended for modal)
     */
    openPostDetail(post) {
        // If it's a video, open it in a modal or new window
        if (post.media_type === 'video' && post.video_url) {
            this.openVideoModal(post);
        } else {
            // Can be extended to show a detail page or modal
            console.log('Opening post detail:', post);
        }
    }
    
    /**
     * Open video in modal
     */
    openVideoModal(post) {
        const modal = document.createElement('div');
        modal.className = 'video-modal';
        modal.innerHTML = `
            <div class="video-modal-overlay"></div>
            <div class="video-modal-content">
                <button class="video-modal-close"><i class="fas fa-times"></i></button>
                <div class="video-container">
                    <iframe 
                        width="100%" 
                        height="600" 
                        src="${post.video_url}?autoplay=1" 
                        frameborder="0" 
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                        allowfullscreen>
                    </iframe>
                </div>
            </div>
        `;
        
        // Add modal styles if not already present
        if (!document.getElementById('video-modal-styles')) {
            const style = document.createElement('style');
            style.id = 'video-modal-styles';
            style.innerHTML = `
                .video-modal {
                    position: fixed;
                    inset: 0;
                    z-index: 1000;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }
                .video-modal-overlay {
                    position: absolute;
                    inset: 0;
                    background: rgba(0, 0, 0, 0.8);
                    backdrop-filter: blur(4px);
                }
                .video-modal-content {
                    position: relative;
                    width: min(90vw, 900px);
                    max-height: 90vh;
                    overflow: auto;
                    border-radius: 1rem;
                    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                }
                .video-modal-close {
                    position: absolute;
                    top: -40px;
                    right: 0;
                    background: none;
                    border: none;
                    color: #fff;
                    font-size: 28px;
                    cursor: pointer;
                    z-index: 1001;
                    transition: transform 0.2s ease;
                }
                .video-modal-close:hover {
                    transform: scale(1.2);
                }
                .video-container {
                    position: relative;
                    width: 100%;
                    padding-bottom: 56.25%;
                    height: 0;
                }
                .video-container iframe {
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                }
                @media (max-width: 700px) {
                    .video-modal-close {
                        top: 10px;
                        right: 10px;
                    }
                    .video-modal-content {
                        width: 95vw;
                    }
                }
            `;
            document.head.appendChild(style);
        }
        
        document.body.appendChild(modal);
        
        // Close on overlay click
        modal.querySelector('.video-modal-overlay').addEventListener('click', () => {
            modal.remove();
        });
        
        // Close on close button click
        modal.querySelector('.video-modal-close').addEventListener('click', () => {
            modal.remove();
        });
        
        // Close on Escape key
        const closeOnEscape = (e) => {
            if (e.key === 'Escape') {
                modal.remove();
                document.removeEventListener('keydown', closeOnEscape);
            }
        };
        document.addEventListener('keydown', closeOnEscape);
    }
    
    /**
     * Show loading spinner
     */
    showLoading() {
        this.mediaGrid.innerHTML = `
            <div class="loading-spinner">
                <i class="fas fa-spinner"></i>
                <p>جاري تحميل الفعاليات...</p>
            </div>
        `;
    }
    
    /**
     * Show error message
     */
    showError(message) {
        this.mediaGrid.innerHTML = `
            <div class="loading-spinner" style="color: #d32f2f;">
                <i class="fas fa-exclamation-circle"></i>
                <p>${message}</p>
            </div>
        `;
    }
    
    /**
     * Attach event listeners to posts (for reveal animations, etc.)
     */
    attachPostListeners() {
        // This can be extended later for additional interactions
        // For now, it's a placeholder for future functionality
    }
}

// Initialize gallery when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    new BlogMediaGallery();
});
