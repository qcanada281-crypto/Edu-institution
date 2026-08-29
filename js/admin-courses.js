/**
 * Admin Courses & Programs Manager
 */
class AdminCoursesManager {
    constructor() {
        this.addForm = document.getElementById('courseAddForm');
        this.addFeedback = document.getElementById('courseAddFeedback');
        this.coursesListContainer = document.getElementById('adminCoursesList');
        this.coursesListFeedback = document.getElementById('adminCoursesListFeedback');
        this.loadAllBtn = document.getElementById('loadAllCoursesBtn');

        this.editPanel = document.getElementById('courseEditPanel');
        this.editForm = document.getElementById('courseEditForm');
        this.editFeedback = document.getElementById('courseEditFeedback');
        this.closeEditBtn = document.getElementById('closeCourseEditPanelBtn');
        this.cancelEditBtn = document.getElementById('cancelCourseEditBtn');

        this.courses = [];
        this.init();
    }

    init() {
        if (this.addForm) {
            this.addForm.addEventListener('submit', (e) => this.handleAdd(e));
        }
        if (this.loadAllBtn) {
            this.loadAllBtn.addEventListener('click', () => this.loadCourses());
        }
        if (this.editForm) {
            this.editForm.addEventListener('submit', (e) => this.handleEdit(e));
        }
        if (this.closeEditBtn) {
            this.closeEditBtn.addEventListener('click', () => this.closeEditPanel());
        }
        if (this.cancelEditBtn) {
            this.cancelEditBtn.addEventListener('click', () => this.closeEditPanel());
        }

        // Listen for tab activation
        document.querySelectorAll('.tab-btn[data-tab-target="coursesTab"]').forEach(btn => {
            btn.addEventListener('click', () => {
                this.loadCourses();
            });
        });
    }

    showFeedback(element, message, isError = false) {
        if (!element) return;
        element.style.display = 'block';
        element.className = `feedback ${isError ? 'error' : 'success'}`;
        element.textContent = message;
    }

    async loadCourses() {
        if (!this.coursesListContainer) return;
        this.coursesListContainer.innerHTML = '<p class="muted">جاري تحميل الشعب والبرامج...</p>';

        try {
            const response = await fetch('backend/courses_api.php?action=admin_list');
            const data = await response.json();

            if (data.success && Array.isArray(data.data)) {
                this.courses = data.data;
                this.renderCourses();
            } else {
                this.coursesListContainer.innerHTML = `<p class="error">${data.error || 'حدث خطأ أثناء تحميل البيانات'}</p>`;
            }
        } catch (err) {
            this.coursesListContainer.innerHTML = '<p class="error">فشل الاتصال بالخادم. يرجى التأكد من تسجيل الدخول بالإدارة.</p>';
        }
    }

    renderCourses() {
        if (!this.courses || this.courses.length === 0) {
            this.coursesListContainer.innerHTML = '<p class="muted">لا توجد أي شعب أو برامج مضافة حالياً.</p>';
            return;
        }

        const categoryLabels = {
            'middle': 'الثانوي الإعدادي',
            'science': 'العلوم والتكنولوجيا',
            'arts': 'الآداب والعلوم الإنسانية',
            'support': 'الدعم واللغات'
        };

        this.coursesListContainer.innerHTML = this.courses.map(course => `
            <div class="course-admin-card" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:0.8rem; overflow:hidden; display:flex; flex-direction:column;">
                <div style="height:140px; background:#e2e8f0; position:relative;">
                    <img src="${course.image_path || 'images/kaoukab_alouloum1.jpg'}" alt="${course.title}" style="width:100%; height:100%; object-fit:cover;" onerror="this.src='images/kawkab_alouloum.jpeg'">
                    <span style="position:absolute; top:0.5rem; right:0.5rem; background:${course.is_published == 1 ? '#10b981' : '#f59e0b'}; color:white; font-size:0.75rem; padding:0.2rem 0.6rem; border-radius:20px; font-weight:bold;">
                        ${course.is_published == 1 ? 'منشور' : 'مسودة'}
                    </span>
                </div>
                <div style="padding:1rem; flex-grow:1; display:flex; flex-direction:column;">
                    <h4 style="margin:0 0 0.4rem; font-size:1rem; color:#0f172a; font-weight:800;">${course.title}</h4>
                    <p style="margin:0 0 0.8rem; font-size:0.85rem; color:#64748b; flex-grow:1;">${course.description || ''}</p>
                    <div style="font-size:0.8rem; color:#0f4f8a; font-weight:700; margin-bottom:0.8rem;">
                        📁 ${categoryLabels[course.category] || course.category} ${course.level_tag ? '• ' + course.level_tag : ''}
                    </div>
                    <div style="display:flex; gap:0.5rem; border-top:1px solid #e2e8f0; padding-top:0.8rem; margin-top:auto;">
                        <button type="button" class="btn btn-outline" style="flex:1; padding:0.4rem; font-size:0.85rem;" onclick="adminCoursesManager.openEdit(${course.id})">
                            <i class="fa-solid fa-pen-to-square"></i> تعديل
                        </button>
                        <button type="button" class="btn btn-outline" style="color:#ef4444; border-color:#fca5a5; flex:1; padding:0.4rem; font-size:0.85rem;" onclick="adminCoursesManager.deleteCourse(${course.id})">
                            <i class="fa-solid fa-trash"></i> حذف
                        </button>
                    </div>
                </div>
            </div>
        `).join('');
    }

    async handleAdd(e) {
        e.preventDefault();
        try {
            this.showFeedback(this.addFeedback, 'جاري إضافة الشعبة...');
            const formData = new FormData(this.addForm);
            formData.append('action', 'add');

            const res = await fetch('backend/courses_api.php', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();

            if (data.success) {
                this.showFeedback(this.addFeedback, 'تمت إضافة الشعبة/البرنامج بنجاح!');
                this.addForm.reset();
                this.loadCourses();
            } else {
                this.showFeedback(this.addFeedback, data.error || 'فشلت الإضافة', true);
            }
        } catch (err) {
            this.showFeedback(this.addFeedback, 'فشل الاتصال بالخادم', true);
        }
    }

    openEdit(courseId) {
        const course = this.courses.find(c => c.id == courseId);
        if (!course) return;

        document.getElementById('edit_course_id').value = course.id;
        document.getElementById('edit_course_title').value = course.title;
        document.getElementById('edit_course_description').value = course.description || '';
        document.getElementById('edit_course_category').value = course.category || 'middle';
        document.getElementById('edit_course_level_tag').value = course.level_tag || '';
        document.getElementById('edit_course_published').value = course.is_published ?? 1;

        if (this.editPanel) {
            this.editPanel.style.display = 'block';
            this.editPanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    closeEditPanel() {
        if (this.editPanel) {
            this.editPanel.style.display = 'none';
        }
    }

    async handleEdit(e) {
        e.preventDefault();
        try {
            this.showFeedback(this.editFeedback, 'جاري حفظ التغييرات...');
            const formData = new FormData(this.editForm);
            formData.append('action', 'edit');

            const res = await fetch('backend/courses_api.php', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();

            if (data.success) {
                this.showFeedback(this.editFeedback, 'تم حفظ التعديلات بنجاح!');
                setTimeout(() => {
                    this.closeEditPanel();
                    this.loadCourses();
                }, 800);
            } else {
                this.showFeedback(this.editFeedback, data.error || 'فشل الحفظ', true);
            }
        } catch (err) {
            this.showFeedback(this.editFeedback, 'فشل الاتصال بالخادم', true);
        }
    }

    async deleteCourse(courseId) {
        if (!confirm('هل أنت تأكد من رغبتك في حذف هذه الشعبة؟')) return;

        try {
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', courseId);

            const res = await fetch('backend/courses_api.php', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();

            if (data.success) {
                this.loadCourses();
            } else {
                alert(data.error || 'فشل الحذف');
            }
        } catch (err) {
            alert('فشل الاتصال بالخادم');
        }
    }
}

let adminCoursesManager = null;
document.addEventListener('DOMContentLoaded', () => {
    adminCoursesManager = new AdminCoursesManager();
});
