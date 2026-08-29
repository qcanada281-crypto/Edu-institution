// Teachers Directory Page JavaScript - Premium Profile Edition
let allTeachers = [];
let currentTeacherId = null;

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    loadTeachers();
    setupMobileMenu();
});

// Load teachers from backend
async function loadTeachers() {
    try {
        const response = await fetch('backend/public_teachers.php?action=list');
        const data = await response.json();
        
        if (data.success && data.data && data.data.teachers) {
            allTeachers = data.data.teachers;
            renderTeachers(allTeachers);
        } else {
            showError('تعذر تحميل قائمة الأساتذة');
        }
    } catch (error) {
        console.error('Error loading teachers:', error);
        showError('وقع خطأ أثناء تحميل البيانات');
    }
}

// Render teachers grid
function renderTeachers(teachers) {
    const grid = document.getElementById('teachersGrid');
    
    if (teachers.length === 0) {
        grid.innerHTML = `
            <div class="empty-state" style="grid-column: 1 / -1;">
                <i class="fa-solid fa-users-slash"></i>
                <p>لا يوجد أساتذة مسجلون حالياً</p>
            </div>
        `;
        return;
    }
    
    grid.innerHTML = teachers.map(teacher => `
        <div class="teacher-card" onclick="showProfileView(${teacher.id})" style="cursor: pointer;">
            <div class="teacher-avatar" style="display:flex; align-items:center; justify-content:center; background:linear-gradient(135deg, #667eea, #764ba2); margin: 0 auto 1rem;">
                ${teacher.avatar ? 
                    `<img src="${teacher.avatar}" alt="" style="width:100%; height:100%; object-fit:cover; border-radius:50%;" onerror="this.parentElement.innerHTML='<i class=\\'fa-solid fa-user\\' style=\\'font-size:3rem;color:white\\'></i>'">` :
                    `<i class="fa-solid fa-user" style="font-size: 3rem; color: white;"></i>`
                }
            </div>
            <h3 class="teacher-name">${teacher.full_name}</h3>
            <span class="teacher-subject">${teacher.subject_name || 'أستاذ'}</span>
            <p class="teacher-specialty">
                <i class="fa-solid fa-location-dot"></i> 
                ${teacher.specialty || 'مؤسسة Kawkab Al Ouloum'}
            </p>
            <div class="teacher-bio">
                ${teacher.bio ? teacher.bio.substring(0, 100) + (teacher.bio.length > 100 ? '...' : '') : 'أستاذ متميز في مجاله'}
            </div>
            <div class="teacher-rating">
                <span class="rating-value">${teacher.rating || '5.0'}</span>
                <span class="rating-stars">
                    <i class="fa-solid fa-star"></i>
                    <i class="fa-solid fa-star"></i>
                    <i class="fa-solid fa-star"></i>
                    <i class="fa-solid fa-star"></i>
                    <i class="fa-solid fa-star"></i>
                </span>
            </div>
            <button class="btn-view-profile">
                <i class="fa-solid fa-user"></i>
                عرض الملف
            </button>
        </div>
    `).join('');
}

// Show profile view for a teacher
async function showProfileView(teacherId) {
    currentTeacherId = teacherId;
    const teacher = allTeachers.find(t => t.id === teacherId);
    
    if (!teacher) return;
    
    // Update header
    document.getElementById('profileName').textContent = teacher.full_name;
    document.getElementById('profileSubject').innerHTML = `<i class="fa-solid fa-book"></i> ${teacher.subject_name || 'أستاذ'}`;
    document.getElementById('profileSpecialty').innerHTML = `<i class="fa-solid fa-location-dot"></i> ${teacher.specialty || 'مؤسسة Kawkab Al Ouloum'}`;
    document.getElementById('profileRating').textContent = teacher.rating || '5.0';
    
    // Update avatar
    const avatarEl = document.getElementById('profileAvatar');
    if (teacher.avatar) {
        avatarEl.innerHTML = `<img src="${teacher.avatar}" alt="" onerror="this.parentElement.innerHTML='<i class=\\'fa-solid fa-user\\'></i>'">`;
    } else {
        avatarEl.innerHTML = '<i class="fa-solid fa-user"></i>';
    }
    
    // Update contact buttons
    const contactBtn = document.getElementById('contactBtn');
    const whatsappBtn = document.getElementById('whatsappBtn');
    if (teacher.phone) {
        contactBtn.href = `tel:${teacher.phone}`;
        whatsappBtn.href = `https://wa.me/${teacher.phone.replace(/\D/g, '')}`;
        contactBtn.style.display = 'inline-flex';
        whatsappBtn.style.display = 'inline-flex';
    } else {
        contactBtn.style.display = 'none';
        whatsappBtn.style.display = 'none';
    }
    
    // Update info tab
    document.getElementById('infoEmail').textContent = teacher.email || '-';
    document.getElementById('infoPhone').textContent = teacher.phone || '-';
    document.getElementById('infoSubject').textContent = teacher.subject_name || '-';
    document.getElementById('infoDate').textContent = teacher.created_at ? new Date(teacher.created_at).toLocaleDateString('ar-MA') : '-';
    
    // Reset to first tab
    document.querySelectorAll('.profile-tab-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelector('.profile-tab-btn').classList.add('active');
    document.querySelectorAll('.profile-content').forEach(c => c.classList.remove('active'));
    document.getElementById('tab-info').classList.add('active');
    
    // Switch views
    document.getElementById('listView').style.display = 'none';
    document.getElementById('profileView').style.display = 'block';
    document.getElementById('backToListBtn').classList.add('visible');
    
    // Load data
    await loadProfileTimetable(teacherId);
    await loadProfileHomework(teacherId);
    await loadProfileAnnouncements(teacherId);
    
    // Scroll to top
    window.scrollTo(0, 0);
}

// Show list view
function showListView() {
    document.getElementById('listView').style.display = 'block';
    document.getElementById('profileView').style.display = 'none';
    document.getElementById('backToListBtn').classList.remove('visible');
    currentTeacherId = null;
}

// Switch tabs
function switchTab(tabName) {
    // Update buttons
    document.querySelectorAll('.profile-tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Find the button that was clicked and add active class
    const clickedBtn = document.querySelector(`.profile-tab-btn[onclick="switchTab('${tabName}')"]`);
    if (clickedBtn) clickedBtn.classList.add('active');
    
    // Update content
    document.querySelectorAll('.profile-content').forEach(content => {
        content.classList.remove('active');
    });
    const tabContent = document.getElementById(`tab-${tabName}`);
    if (tabContent) tabContent.classList.add('active');
}

// Load timetable for profile view
async function loadProfileTimetable(teacherId) {
    const tbody = document.getElementById('profileTimetableBody');
    tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; padding: 2rem;"><i class="fa-solid fa-spinner fa-spin"></i> جاري التحميل...</td></tr>';
    
    try {
        const response = await fetch(`backend/public_teachers.php?action=timetable&teacher_id=${teacherId}`);
        const data = await response.json();
        
        if (data.success && data.data && data.data.timetable) {
            const timetable = data.data.timetable;
            
            if (timetable.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; padding: 2rem; color: #666;">لا توجد حصص مسجلة</td></tr>';
                return;
            }
            
            const days = {
                1: 'الإثنين', 2: 'الثلاثاء', 3: 'الأربعاء',
                4: 'الخميس', 5: 'الجمعة', 6: 'السبت', 7: 'الأحد'
            };
            
            tbody.innerHTML = timetable.map(item => `
                <tr>
                    <td><strong>${days[item.day_of_week] || item.day_of_week}</strong></td>
                    <td style="direction: ltr; text-align: center;">${item.start_time?.substring(0, 5) || '-'} - ${item.end_time?.substring(0, 5) || '-'}</td>
                    <td><span class="chip chip-primary">${item.class_name}</span></td>
                    <td>${item.subject_name}</td>
                    <td>${item.room || '-'}</td>
                </tr>
            `).join('');
        } else {
            tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; padding: 2rem; color: #666;">لا توجد حصص مسجلة</td></tr>';
        }
    } catch (error) {
        console.error('Error loading timetable:', error);
        tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; padding: 2rem; color: #666;">وقع خطأ أثناء تحميل الجدول</td></tr>';
    }
}

// Load homework for profile view
async function loadProfileHomework(teacherId) {
    const container = document.getElementById('profileHomeworkList');
    container.innerHTML = '<div style="text-align: center; padding: 2rem;"><i class="fa-solid fa-spinner fa-spin"></i> جاري تحميل الواجبات...</div>';
    
    try {
        const response = await fetch(`backend/public_teachers.php?action=homework&teacher_id=${teacherId}`);
        const data = await response.json();
        
        if (data.success && data.data && data.data.homework) {
            const homework = data.data.homework;
            
            if (homework.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="fa-solid fa-book-open"></i>
                        <p>لا توجد واجبات منشورة حالياً</p>
                    </div>
                `;
                return;
            }
            
            container.innerHTML = homework.map(hw => `
                <div class="homework-card">
                    <div class="homework-header">
                        <div>
                            <h4 class="homework-title"><i class="fa-solid fa-book"></i> ${hw.title}</h4>
                            <p class="homework-meta">
                                <i class="fa-solid fa-users"></i> ${hw.class_name} | 
                                <i class="fa-solid fa-book-open"></i> ${hw.subject}
                            </p>
                        </div>
                        <div class="due-date">
                            <i class="fa-solid fa-clock"></i> ${hw.due_date}
                        </div>
                    </div>
                    <p class="homework-description">${hw.description}</p>
                    ${hw.has_file ? `
                        <a href="${hw.file_path}" target="_blank" class="homework-file">
                            <i class="fa-solid fa-download"></i>
                            تحميل الملف
                        </a>
                    ` : ''}
                </div>
            `).join('');
        } else {
            container.innerHTML = `
                <div class="empty-state">
                    <i class="fa-solid fa-book-open"></i>
                    <p>لا توجد واجبات منشورة حالياً</p>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error loading homework:', error);
        container.innerHTML = `
            <div class="empty-state">
                <i class="fa-solid fa-circle-exclamation"></i>
                <p>وقع خطأ أثناء تحميل الواجبات</p>
            </div>
        `;
    }
}

// Show error
function showError(message) {
    const grid = document.getElementById('teachersGrid');
    grid.innerHTML = `
        <div class="empty-state" style="grid-column: 1 / -1;">
            <i class="fa-solid fa-circle-exclamation"></i>
            <p>${message}</p>
        </div>
    `;
}

// Mobile menu setup
function setupMobileMenu() {
    const menuToggle = document.getElementById('menuToggle');
    const navMenu = document.getElementById('navMenu');
    
    if (menuToggle && navMenu) {
        menuToggle.addEventListener('click', () => {
            navMenu.classList.toggle('active');
        });
    }
}

// Load announcements for profile view
async function loadProfileAnnouncements(teacherId) {
    const container = document.getElementById('profileAnnouncementsList');
    container.innerHTML = '<div style="text-align: center; padding: 2rem;"><i class="fa-solid fa-spinner fa-spin"></i> جاري تحميل الإعلانات...</div>';
    
    try {
        const response = await fetch(`backend/public_teachers.php?action=announcements&teacher_id=${teacherId}`);
        const data = await response.json();
        
        if (data.success && data.data && data.data.announcements) {
            const announcements = data.data.announcements;
            
            if (announcements.length === 0) {
                container.innerHTML = `
                    <div class="empty-state" id="noAnnouncements">
                        <i class="fa-solid fa-bullhorn"></i>
                        <p>لا توجد أخبار أو إعلانات حالياً</p>
                    </div>
                `;
                return;
            }
            
            container.innerHTML = announcements.map(ann => {
                const isUrgent = ann.type === 'urgent';
                const cardClass = isUrgent ? 'announcement-card urgent' : 'announcement-card';
                const badgeClass = isUrgent ? 'announcement-badge urgent' : 'announcement-badge';
                const typeLabel = isUrgent ? 'هام' : 'إعلان';
                const dateStr = ann.created_at ? new Date(ann.created_at).toLocaleDateString('ar-MA', {
                    year: 'numeric', month: 'long', day: 'numeric'
                }) : '';
                
                return `
                    <div class="${cardClass}">
                        <div class="announcement-header">
                            <span class="${badgeClass}">
                                <i class="fa-solid ${isUrgent ? 'fa-triangle-exclamation' : 'fa-info-circle'}"></i> 
                                ${typeLabel}
                            </span>
                            <span class="announcement-date">
                                <i class="fa-solid fa-calendar-day"></i> ${dateStr}
                            </span>
                        </div>
                        <h4 class="announcement-title">${ann.title}</h4>
                        <p class="announcement-text">${ann.content}</p>
                        ${(ann.file_path || ann.attachment_url) ? `
                            <a href="${ann.file_path || ann.attachment_url}" target="_blank" rel="noopener noreferrer" class="homework-file" style="margin-top: 0.75rem; display: inline-flex;">
                                <i class="fa-solid fa-paperclip"></i> تحميل المرفق
                            </a>` : ''}
                    </div>
                `;
            }).join('');
        } else {
            container.innerHTML = `
                <div class="empty-state" id="noAnnouncements">
                    <i class="fa-solid fa-bullhorn"></i>
                    <p>لا توجد أخبار أو إعلانات حالياً</p>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error loading announcements:', error);
        container.innerHTML = `
            <div class="empty-state">
                <i class="fa-solid fa-circle-exclamation"></i>
                <p>وقع خطأ أثناء تحميل الإعلانات</p>
            </div>
        `;
    }
}
