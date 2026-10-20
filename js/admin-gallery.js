/**
 * Admin Gallery Management
 * Handles adding, editing, and deleting photos from the admin panel
 */

class AdminGalleryManager {
    constructor() {
        this.uploadForm = document.getElementById('galleryUploadForm');
        this.photosContainer = document.getElementById('galleryPhotosList');
        this.loadAllBtn = document.getElementById('loadAllPhotosBtn');
        this.uploadFeedback = document.getElementById('uploadFeedback');
        this.galleryFeedback = document.getElementById('galleryListFeedback');
        
        this.init();
    }
    
    init() {
        if (!this.uploadForm) return;
        
        this.uploadForm.addEventListener('submit', (e) => this.handleUpload(e));
        this.loadAllBtn.addEventListener('click', () => this.loadAllPhotos());
    }
    
    /**
     * Handle photo upload
     */
    async handleUpload(e) {
        e.preventDefault();
        
        try {
            this.showLoadingFeedback(this.uploadFeedback, 'جاري رفع الصورة...');
            
            const formData = new FormData(this.uploadForm);
            formData.append('action', 'upload');
            
            const response = await fetch('backend/gallery_api.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (!data.success) {
                this.showErrorFeedback(this.uploadFeedback, data.error || 'فشل رفع الصورة');
                return;
            }
            
            this.showSuccessFeedback(this.uploadFeedback, '✓ تم رفع الصورة بنجاح!');
            this.uploadForm.reset();
            
            // Reload photos if already loaded
            if (this.photosContainer.children.length > 0 && !this.photosContainer.querySelector('.muted')) {
                this.loadAllPhotos();
            }
            
        } catch (error) {
            console.error('Upload error:', error);
            this.showErrorFeedback(this.uploadFeedback, 'حدث خطأ في الاتصال');
        }
    }
    
    /**
     * Load all photos
     */
    async loadAllPhotos() {
        try {
            this.showLoadingMessage('جاري تحميل الصور...');
            
            const response = await fetch('backend/gallery_api.php?action=admin_list');
            const data = await response.json();
            
            if (!data.success) {
                this.showErrorMessage('فشل تحميل الصور');
                return;
            }
            
            if (data.count === 0) {
                this.photosContainer.innerHTML = '<p class="muted" style="grid-column: 1/-1; text-align: center;">لا توجد صور حالياً</p>';
                return;
            }
            
            this.renderPhotos(data.data);
            this.galleryFeedback.textContent = `✓ تم تحميل ${data.count} صورة بنجاح`;
            this.galleryFeedback.className = 'feedback success';
            
        } catch (error) {
            console.error('Load error:', error);
            this.showErrorMessage('حدث خطأ في تحميل الصور');
        }
    }
    
    /**
     * Render photos
     */
    renderPhotos(photos) {
        this.photosContainer.innerHTML = '';
        
        photos.forEach(photo => {
            const card = document.createElement('div');
            card.className = 'photo-card';
            card.style.cssText = `
                border: 1px solid #e0e0e0;
                border-radius: 0.8rem;
                overflow: hidden;
                background: white;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                transition: transform 0.2s, box-shadow 0.2s;
            `;
            card.onmouseover = () => {
                card.style.transform = 'translateY(-4px)';
                card.style.boxShadow = '0 6px 16px rgba(0,0,0,0.15)';
            };
            card.onmouseout = () => {
                card.style.transform = 'none';
                card.style.boxShadow = '0 2px 8px rgba(0,0,0,0.1)';
            };
            
            const categoryLabel = this.getCategoryLabel(photo.category);
            const statusBadge = photo.is_published ? 
                '<span style="background: #4caf50; color: white; padding: 0.2rem 0.6rem; border-radius: 0.3rem; font-size: 0.75rem; font-weight: 700;">منشورة</span>' :
                '<span style="background: #ff9800; color: white; padding: 0.2rem 0.6rem; border-radius: 0.3rem; font-size: 0.75rem; font-weight: 700;">مخفية</span>';
            
            const photoDate = photo.photo_date ? new Date(photo.photo_date).toLocaleDateString('ar-MA') : '---';
            
            card.innerHTML = `
                <img src="${photo.image_path}" alt="${photo.title}" style="width: 100%; height: 200px; object-fit: cover;">
                <div style="padding: 1rem;">
                    <h4 style="margin: 0 0 0.5rem; font-size: 0.95rem;">${photo.title}</h4>
                    <p style="margin: 0 0 0.5rem; font-size: 0.85rem; color: #666;">
                        <strong>${categoryLabel}</strong> • ${photoDate}
                    </p>
                    <div style="margin-bottom: 0.8rem;">${statusBadge}</div>
                    <div style="display: flex; gap: 0.5rem;">
                        <button class="btn btn-small" onclick="adminGalleryManager.editPhoto(${photo.id})">
                            <i class="fa-solid fa-edit"></i> تعديل
                        </button>
                        <button class="btn btn-small btn-danger" onclick="adminGalleryManager.deletePhoto(${photo.id}, '${photo.title.replace(/'/g, "\\'")}')">
                            <i class="fa-solid fa-trash"></i> حذف
                        </button>
                    </div>
                </div>
            `;
            
            this.photosContainer.appendChild(card);
        });
    }
    
    /**
     * Edit photo
     */
    editPhoto(photoId) {
        alert(`سيتم تطوير ميزة تعديل الصورة رقم ${photoId} قريباً`);
    }
    
    /**
     * Delete photo
     */
    async deletePhoto(photoId, photoTitle) {
        if (!confirm(`هل أنت متأكد من حذف الصورة "${photoTitle}"؟`)) {
            return;
        }
        
        try {
            this.showLoadingMessage('جاري حذف الصورة...');
            
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', photoId);
            
            const response = await fetch('backend/gallery_api.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (!data.success) {
                this.showErrorMessage(data.error || 'فشل حذف الصورة');
                return;
            }
            
            this.showSuccessFeedback(this.galleryFeedback, '✓ تم حذف الصورة بنجاح');
            this.loadAllPhotos();
            
        } catch (error) {
            console.error('Delete error:', error);
            this.showErrorMessage('حدث خطأ في حذف الصورة');
        }
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
     * Show loading message
     */
    showLoadingMessage(message) {
        this.photosContainer.innerHTML = `
            <div style="grid-column: 1/-1; text-align: center; padding: 2rem; color: #666;">
                <i class="fa-solid fa-spinner" style="font-size: 2rem; animation: spin 1s linear infinite; margin-bottom: 1rem; display: block; color: #1fa8cf;"></i>
                <p>${message}</p>
            </div>
        `;
    }
    
    /**
     * Show success feedback
     */
    showSuccessFeedback(element, message) {
        element.textContent = message;
        element.className = 'feedback success';
        element.style.display = 'block';
    }
    
    /**
     * Show error feedback
     */
    showErrorFeedback(element, message) {
        element.textContent = '✗ ' + message;
        element.className = 'feedback error';
        element.style.display = 'block';
    }
    
    /**
     * Show error message
     */
    showErrorMessage(message) {
        this.photosContainer.innerHTML = `
            <div style="grid-column: 1/-1; text-align: center; padding: 2rem; color: #d32f2f;">
                <i class="fa-solid fa-exclamation-circle" style="font-size: 2rem; margin-bottom: 1rem; display: block;"></i>
                <p>${message}</p>
            </div>
        `;
    }
    
    /**
     * Show loading feedback
     */
    showLoadingFeedback(element, message) {
        element.innerHTML = `<i class="fa-solid fa-spinner" style="animation: spin 1s linear infinite; margin-right: 0.5rem;"></i>${message}`;
        element.className = 'feedback';
        element.style.display = 'block';
    }
}

// Initialize when admin is logged in
let adminGalleryManager;

document.addEventListener('DOMContentLoaded', () => {
    adminGalleryManager = new AdminGalleryManager();
});

// Add CSS for spin animation if not already present
const style = document.createElement('style');
style.innerHTML = `
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    
    .btn-small {
        padding: 0.4rem 0.8rem;
        font-size: 0.8rem;
        border-radius: 0.5rem;
        border: 1px solid #e0e0e0;
        background: #f5f5f5;
        color: #333;
        cursor: pointer;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        flex: 1;
    }
    
    .btn-small:hover {
        background: #efefef;
    }
    
    .btn-small.btn-danger {
        background: #ffebee;
        color: #c62828;
        border-color: #ef5350;
    }
    
    .btn-small.btn-danger:hover {
        background: #ffcdd2;
    }
    
    .photo-card {
        position: relative;
    }
`;
document.head.appendChild(style);
