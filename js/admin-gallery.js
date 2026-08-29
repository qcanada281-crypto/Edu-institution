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
        this.placeholderImage = 'images/kawkab_alouloum.jpeg';
        
        // Modal elements
        this.editModal = document.getElementById('galleryEditModal');
        this.editForm = document.getElementById('galleryEditForm');
        this.editFeedback = document.getElementById('editFeedback');
        this.closeModalBtn = document.getElementById('closeGalleryEditModalBtn');
        this.cancelModalBtn = document.getElementById('cancelGalleryEditBtn');
        this.previewImg = document.getElementById('edit_photo_preview');
        this.imageFileInput = document.getElementById('edit_photo_image');
        
        this.photosCache = new Map();
        
        this.init();
    }

    escapeHtml(text) {
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return String(text || '').replace(/[&<>"']/g, (char) => map[char]);
    }

    getPhotoSrc(photo) {
        const raw = photo && typeof photo === 'object' ? photo.image_path : '';
        if (typeof raw !== 'string') {
            return this.placeholderImage;
        }

        const path = raw.trim();
        if (!path || path.includes('[object')) {
            return this.placeholderImage;
        }

        return path;
    }
    
    init() {
        if (this.uploadForm) {
            this.uploadForm.addEventListener('submit', (e) => this.handleUpload(e));
        }
        if (this.loadAllBtn) {
            this.loadAllBtn.addEventListener('click', () => this.loadAllPhotos());
        }
        if (this.editForm) {
            this.editForm.addEventListener('submit', (e) => this.handleEditSubmit(e));
        }
        if (this.closeModalBtn) {
            this.closeModalBtn.addEventListener('click', () => this.closeEditModal());
        }
        if (this.cancelModalBtn) {
            this.cancelModalBtn.addEventListener('click', () => this.closeEditModal());
        }
        if (this.editModal) {
            this.editModal.addEventListener('click', (e) => {
                if (e.target === this.editModal) {
                    this.closeEditModal();
                }
            });
        }

        // Live image preview & filename display when selecting a replacement file
        if (this.imageFileInput) {
            this.imageFileInput.addEventListener('change', (e) => {
                const file = e.target.files[0];
                const fileInfoBox = document.getElementById('selected_file_info');
                const fileNameText = document.getElementById('file_name_text');

                if (file) {
                    if (this.previewImg) {
                        const reader = new FileReader();
                        reader.onload = (evt) => {
                            this.previewImg.src = evt.target.result;
                        };
                        reader.readAsDataURL(file);
                    }
                    if (fileInfoBox && fileNameText) {
                        fileNameText.textContent = `تم اختيار: ${file.name}`;
                        fileInfoBox.style.display = 'block';
                    }
                } else if (fileInfoBox) {
                    fileInfoBox.style.display = 'none';
                }
            });
        }

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.editModal && this.editModal.style.display !== 'none') {
                this.closeEditModal();
            }
        });
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
                body: formData,
                credentials: 'same-origin'
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
            
            const response = await fetch('backend/gallery_api.php?action=admin_list', {
                credentials: 'same-origin'
            });
            const data = await response.json();
            
            if (!data.success) {
                this.showErrorMessage(data.error || 'فشل تحميل الصور');
                return;
            }
            
            if (data.count === 0) {
                this.photosContainer.innerHTML = '<p class="muted" style="grid-column: 1/-1; text-align: center;">لا توجد صور حالياً</p>';
                return;
            }
            
            const photos = Array.isArray(data.data) ? data.data : [];
            this.renderPhotos(photos);
            if (this.galleryFeedback) {
                this.galleryFeedback.textContent = `✓ تم تحميل ${data.count} صورة بنجاح`;
                this.galleryFeedback.className = 'feedback success';
            }
            
        } catch (error) {
            console.error('Load error:', error);
            this.showErrorMessage('حدث خطأ في تحميل الصور');
        }
    }
    
    /**
     * Render photos
     */
    renderPhotos(photos) {
        if (!this.photosContainer) return;

        this.photosContainer.innerHTML = '';
        this.photosCache.clear();
        
        photos.forEach((photo) => {
            this.photosCache.set(String(photo.id), photo);

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

            const image = document.createElement('img');
            image.src = this.getPhotoSrc(photo);
            image.alt = photo.title || 'صورة المعرض';
            image.style.cssText = 'width: 100%; height: 200px; object-fit: cover;';
            image.loading = 'lazy';
            image.addEventListener('error', () => {
                image.src = this.placeholderImage;
            });

            const body = document.createElement('div');
            body.style.padding = '1rem';

            const categoryLabel = this.getCategoryLabel(photo.category);
            const statusBadge = Number(photo.is_published) === 1
                ? '<span style="background: #4caf50; color: white; padding: 0.2rem 0.6rem; border-radius: 0.3rem; font-size: 0.75rem; font-weight: 700;">منشورة</span>'
                : '<span style="background: #ff9800; color: white; padding: 0.2rem 0.6rem; border-radius: 0.3rem; font-size: 0.75rem; font-weight: 700;">مخفية</span>';
            const photoDate = photo.photo_date ? new Date(photo.photo_date).toLocaleDateString('ar-MA') : '---';
            const safeTitle = this.escapeHtml(photo.title || 'بدون عنوان');

            body.innerHTML = `
                <h4 style="margin: 0 0 0.5rem; font-size: 0.95rem;">${safeTitle}</h4>
                <p style="margin: 0 0 0.5rem; font-size: 0.85rem; color: #666;">
                    <strong>${this.escapeHtml(categoryLabel)}</strong> • ${this.escapeHtml(photoDate)}
                </p>
                <div style="margin-bottom: 0.8rem;">${statusBadge}</div>
                <div style="display: flex; gap: 0.5rem;">
                    <button type="button" class="btn btn-small" data-gallery-action="edit" data-photo-id="${this.escapeHtml(photo.id)}">
                        <i class="fa-solid fa-edit"></i> تعديل
                    </button>
                    <button type="button" class="btn btn-small btn-danger" data-gallery-action="delete" data-photo-id="${this.escapeHtml(photo.id)}" data-photo-title="${safeTitle}">
                        <i class="fa-solid fa-trash"></i> حذف
                    </button>
                </div>
            `;

            card.appendChild(image);
            card.appendChild(body);
            this.photosContainer.appendChild(card);
        });

        if (!this.photosContainer.dataset.boundActions) {
            this.photosContainer.dataset.boundActions = '1';
            this.photosContainer.addEventListener('click', (event) => {
                const button = event.target.closest('[data-gallery-action]');
                if (!button) return;

                const photoId = button.getAttribute('data-photo-id');
                const photoTitle = button.getAttribute('data-photo-title') || '';
                const action = button.getAttribute('data-gallery-action');

                if (action === 'edit') {
                    this.editPhoto(photoId);
                } else if (action === 'delete') {
                    this.deletePhoto(photoId, photoTitle);
                }
            });
        }
    }
    
    /**
     * Edit photo
     */
    async editPhoto(photoId) {
        let photo = this.photosCache.get(String(photoId));
        if (!photo) {
            try {
                const res = await fetch(`backend/gallery_api.php?action=get&id=${photoId}`, { credentials: 'same-origin' });
                const json = await res.json();
                if (json.success && json.data) {
                    photo = json.data;
                }
            } catch (e) {
                console.error('Fetch single photo error:', e);
            }
        }

        if (!photo) {
            alert('تعذر العثور على بيانات الصورة');
            return;
        }

        // Populate form fields
        document.getElementById('edit_photo_id').value = photo.id;
        document.getElementById('edit_photo_title').value = photo.title || '';
        document.getElementById('edit_photo_description').value = photo.description || '';
        document.getElementById('edit_photo_category').value = photo.category || 'trips';
        document.getElementById('edit_photo_location').value = photo.location_label || photo.location || '';
        document.getElementById('edit_photo_date').value = photo.photo_date || '';
        document.getElementById('edit_photo_author').value = photo.author_name || '';
        document.getElementById('edit_photo_status').value = String(photo.is_published ?? 1);
        
        if (this.previewImg) {
            this.previewImg.src = this.getPhotoSrc(photo);
        }
        if (this.imageFileInput) {
            this.imageFileInput.value = '';
        }
        const fileInfoBox = document.getElementById('selected_file_info');
        if (fileInfoBox) {
            fileInfoBox.style.display = 'none';
        }
        if (this.editFeedback) {
            this.editFeedback.style.display = 'none';
            this.editFeedback.textContent = '';
        }

        if (this.editModal) {
            this.editModal.style.display = 'block';
            this.editModal.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    closeEditModal() {
        if (this.editModal) {
            this.editModal.style.display = 'none';
        }
        if (this.photosContainer) {
            this.photosContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    /**
     * Handle edit form submit
     */
    async handleEditSubmit(e) {
        e.preventDefault();
        try {
            this.showLoadingFeedback(this.editFeedback, 'جاري حفظ التغييرات...');
            
            const formData = new FormData(this.editForm);
            formData.append('action', 'edit');

            const response = await fetch('backend/gallery_api.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            });

            const data = await response.json();

            if (!data.success) {
                this.showErrorFeedback(this.editFeedback, data.error || 'فشل تحديث بيانات الصورة');
                return;
            }

            this.showSuccessFeedback(this.editFeedback, '✓ تم حفظ التغييرات بنجاح!');

            setTimeout(() => {
                this.closeEditModal();
                this.loadAllPhotos();
            }, 700);

        } catch (error) {
            console.error('Edit error:', error);
            this.showErrorFeedback(this.editFeedback, 'حدث خطأ أثناء حفظ البيانات');
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
            'activities': 'الأنشطة',
            'other': 'أخرى'
        };
        return labels[category] || category;
    }
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
                body: formData,
                credentials: 'same-origin'
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
