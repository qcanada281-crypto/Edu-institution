// Admin Panel JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Check authentication on load
    checkAuth();
    
    // Initialize navigation
    initNavigation();
    
    // Initialize forms
    initForms();
    
    // Load dashboard data
    loadDashboardStats();
});

// Authentication Functions
async function checkAuth() {
    try {
        const response = await fetch('api/auth.php?action=check');
        const data = await response.json();
        
        if (data.success) {
            showDashboard(data.data.admin);
            applyRoleBasedUI(data.data.admin);
        } else {
            showLoginPage();
        }
    } catch (error) {
        console.error('Auth check error:', error);
        showLoginPage();
    }
}

async function login(email, password) {
    const feedback = document.getElementById('loginFeedback');
    const submitBtn = document.querySelector('#loginForm button[type="submit"]');
    
    try {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner"></span> جاري تسجيل الدخول...';
        
        const response = await fetch('api/auth.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ email, password })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showDashboard(data.data.admin);
            applyRoleBasedUI(data.data.admin);
            showFeedback(feedback, 'تم تسجيل الدخول بنجاح', 'success');
        } else {
            showFeedback(feedback, data.message, 'error');
        }
    } catch (error) {
        console.error('Login error:', error);
        showFeedback(feedback, 'حدث خطأ في الاتصال', 'error');
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fa-solid fa-right-to-bracket"></i> تسجيل الدخول';
    }
}

async function logout() {
    try {
        await fetch('api/auth.php', { method: 'DELETE' });
        showLoginPage();
    } catch (error) {
        console.error('Logout error:', error);
        showLoginPage();
    }
}

function showLoginPage() {
    document.getElementById('loginPage').style.display = 'flex';
    document.getElementById('dashboardPage').style.display = 'none';
}

function showDashboard(admin) {
    document.getElementById('loginPage').style.display = 'none';
    document.getElementById('dashboardPage').style.display = 'flex';
    
    // Update admin info
    document.getElementById('adminName').textContent = admin.full_name;
    document.getElementById('adminRole').textContent = admin.role === 'director' ? 'المدير' : 'السكرتارية';
    
    // Load dashboard stats
    loadDashboardStats();
}

function applyRoleBasedUI(admin) {
    const role = admin.role;
    const permissions = admin.permissions || [];
    
    // Store current admin ID for reference
    window.currentAdminId = admin.id;
    
    // Helper to check if user has a permission
    const hasPermission = (permName) => {
        return role === 'director' || permissions.some(p => p.name === permName);
    };
    
    // Hide/show navigation items based on permissions
    const navItems = document.querySelectorAll('.nav-item');
    navItems.forEach(item => {
        const page = item.dataset.page;
        let show = true;
        
        switch(page) {
            case 'teachers':
                show = hasPermission('teachers.view');
                break;
            case 'grades':
                show = hasPermission('grades.view');
                break;
            case 'attendance':
                show = hasPermission('attendance.view');
                break;
            case 'lessons':
                show = hasPermission('lessons.view');
                break;
            case 'messages':
                show = hasPermission('messages.view');
                break;
            case 'registrations':
                show = hasPermission('registrations.view');
                break;
            case 'accounts':
                show = hasPermission('accounts.manage');
                break;
            case 'settings':
                show = hasPermission('settings.manage');
                break;
        }
        
        item.style.display = show ? 'flex' : 'none';
    });
    
    // Hide action buttons based on permissions
    const addStudentBtn = document.getElementById('addStudentBtn');
    if (addStudentBtn) {
        addStudentBtn.style.display = hasPermission('students.create') ? 'inline-flex' : 'none';
    }
    
    const addTeacherBtn = document.getElementById('addTeacherBtn');
    if (addTeacherBtn) {
        addTeacherBtn.style.display = hasPermission('teachers.create') ? 'inline-flex' : 'none';
    }
    
    const addGradeBtn = document.getElementById('addGradeBtn');
    if (addGradeBtn) {
        addGradeBtn.style.display = hasPermission('grades.create') ? 'inline-flex' : 'none';
    }
    
    const addAttendanceBtn = document.getElementById('addAttendanceBtn');
    if (addAttendanceBtn) {
        addAttendanceBtn.style.display = hasPermission('attendance.create') ? 'inline-flex' : 'none';
    }
    
    const addLessonBtn = document.getElementById('addLessonBtn');
    if (addLessonBtn) {
        addLessonBtn.style.display = hasPermission('lessons.create') ? 'inline-flex' : 'none';
    }
    
    const addAccountBtn = document.getElementById('addAccountBtn');
    if (addAccountBtn) {
        addAccountBtn.style.display = hasPermission('accounts.create') ? 'inline-flex' : 'none';
    }
    
    // Show access note for secretary
    const secretaryNote = document.getElementById('secretaryAccessNote');
    if (secretaryNote) {
        secretaryNote.style.display = role === 'secretary' ? 'block' : 'none';
    }
}

// Navigation Functions
function initNavigation() {
    const navItems = document.querySelectorAll('.nav-item');
    
    navItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Remove active class from all items
            navItems.forEach(nav => nav.classList.remove('active'));
            
            // Add active class to clicked item
            this.classList.add('active');
            
            // Show corresponding content section
            const page = this.dataset.page;
            showPage(page);
        });
    });
    
    // Logout button
    document.getElementById('logoutBtn').addEventListener('click', logout);
}

function showPage(page) {
    // Hide all content sections
    document.querySelectorAll('.content-section').forEach(section => {
        section.classList.remove('active');
    });
    
    // Show selected section
    const contentSection = document.getElementById(page + 'Content');
    if (contentSection) {
        contentSection.classList.add('active');
    }
    
    // Update page title
    const titles = {
        'dashboard': { title: 'لوحة التحكم', subtitle: 'مرحباً بك في لوحة إدارة المؤسسة' },
        'students': { title: 'إدارة التلاميذ', subtitle: 'إضافة وتعديل وعرض بيانات التلاميذ' },
        'teachers': { title: 'إدارة الأساتذة', subtitle: 'إضافة وتعديل وعرض بيانات الأساتذة' },
        'grades': { title: 'إدارة النقط', subtitle: 'إدخال وعرض النقط الدراسية' },
        'attendance': { title: 'إدارة الغياب', subtitle: 'تسجيل وعرض سجلات الغياب' },
        'lessons': { title: 'إدارة الدروس', subtitle: 'إضافة وإدارة الدروس والمواد' },
        'messages': { title: 'رسائل التواصل', subtitle: 'عرض والرد على الرسائل الواردة' },
        'settings': { title: 'الإعدادات', subtitle: 'إعدادات النظام' }
    };
    
    if (titles[page]) {
        document.getElementById('pageTitle').textContent = titles[page].title;
        document.getElementById('pageSubtitle').textContent = titles[page].subtitle;
    }
    
    // Load page-specific data
    loadPageData(page);
}

// Form Functions
function initForms() {
    // Login form
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            login(email, password);
        });
    }
}

function togglePassword() {
    const passwordInput = document.getElementById('password');
    const toggleBtn = document.querySelector('.toggle-password i');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleBtn.classList.remove('fa-eye');
        toggleBtn.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        toggleBtn.classList.remove('fa-eye-slash');
        toggleBtn.classList.add('fa-eye');
    }
}

// Data Loading Functions
async function loadDashboardStats() {
    try {
        // Load dashboard statistics
        const response = await fetch('api/dashboard.php?action=stats');
        const data = await response.json();
        
        if (data.success) {
            const stats = data.data.stats;
            document.getElementById('studentsCount').textContent = stats.total_students;
            document.getElementById('teachersCount').textContent = stats.total_teachers;
            document.getElementById('gradesCount').textContent = stats.total_grades;
            document.getElementById('attendanceCount').textContent = stats.total_attendance;
        }
        
        // Load recent activity
        loadRecentActivity();
    } catch (error) {
        console.error('Error loading stats:', error);
    }
}

function loadRecentActivity() {
    const activityList = document.getElementById('recentActivityList');
    activityList.innerHTML = '<p class="muted">لا يوجد نشاط حديث</p>';
}

async function loadPageData(page) {
    switch(page) {
        case 'students':
            await loadStudents();
            break;
        case 'teachers':
            await loadTeachers();
            break;
        case 'grades':
            await loadGrades();
            break;
        case 'attendance':
            await loadAttendance();
            break;
        case 'lessons':
            await loadLessons();
            break;
        case 'messages':
            await loadMessages();
            break;
        case 'registrations':
            await loadRegistrations();
            break;
        case 'accounts':
            await loadAccounts();
            break;
    }
}

async function loadStudents() {
    const tbody = document.getElementById('studentsTableBody');
    tbody.innerHTML = '<tr><td colspan="7" class="text-center"><span class="spinner"></span> جاري التحميل...</td></tr>';
    
    try {
        const response = await fetch('api/students.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=list'
        });
        const data = await response.json();
        
        if (data.success && data.data.students.length > 0) {
            tbody.innerHTML = data.data.students.map(student => `
                <tr>
                    <td>${escapeHtml(student.student_code)}</td>
                    <td>${escapeHtml(student.first_name + ' ' + student.last_name)}</td>
                    <td>${escapeHtml(student.class_name)}</td>
                    <td>${escapeHtml(student.level)}</td>
                    <td>${escapeHtml(student.guardian_name)}</td>
                    <td><span class="status-badge active">${escapeHtml(student.registration_status || 'مسجل')}</span></td>
                    <td>
                        <button class="btn btn-sm btn-outline" onclick="editStudent(${student.id})">
                            <i class="fa-solid fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="deleteStudent(${student.id})">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `).join('');
        } else {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center">لا يوجد تلاميذ</td></tr>';
        }
    } catch (error) {
        console.error('Error loading students:', error);
        tbody.innerHTML = '<tr><td colspan="7" class="text-center">حدث خطأ في التحميل</td></tr>';
    }
}

async function loadTeachers() {
    const tbody = document.getElementById('teachersTableBody');
    tbody.innerHTML = '<tr><td colspan="6" class="text-center"><span class="spinner"></span> جاري التحميل...</td></tr>';
    
    try {
        const response = await fetch('api/teachers.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=list'
        });
        const data = await response.json();
        
        if (data.success && data.data.teachers.length > 0) {
            tbody.innerHTML = data.data.teachers.map(teacher => `
                <tr>
                    <td>${escapeHtml(teacher.full_name)}</td>
                    <td>${escapeHtml(teacher.email)}</td>
                    <td>${escapeHtml(teacher.subject_name || '-')}</td>
                    <td>${escapeHtml(teacher.phone || '-')}</td>
                    <td><span class="status-badge ${teacher.status === 'active' ? 'active' : 'inactive'}">${teacher.status === 'active' ? 'نشط' : 'غير نشط'}</span></td>
                    <td>
                        <button class="btn btn-sm btn-outline" onclick="editTeacher(${teacher.id})">
                            <i class="fa-solid fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="deleteTeacher(${teacher.id})">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `).join('');
        } else {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center">لا يوجد أساتذة</td></tr>';
        }
    } catch (error) {
        console.error('Error loading teachers:', error);
        tbody.innerHTML = '<tr><td colspan="6" class="text-center">حدث خطأ في التحميل</td></tr>';
    }
}

async function loadGrades() {
    const tbody = document.getElementById('gradesTableBody');
    tbody.innerHTML = '<tr><td colspan="7" class="text-center">جاري التحميل...</td></tr>';
    // Placeholder - implement actual loading
    tbody.innerHTML = '<tr><td colspan="7" class="text-center">ميزة قيد التطوير</td></tr>';
}

async function loadAttendance() {
    const tbody = document.getElementById('attendanceTableBody');
    tbody.innerHTML = '<tr><td colspan="6" class="text-center">جاري التحميل...</td></tr>';
    // Placeholder - implement actual loading
    tbody.innerHTML = '<tr><td colspan="6" class="text-center">ميزة قيد التطوير</td></tr>';
}

async function loadLessons() {
    const tbody = document.getElementById('lessonsTableBody');
    tbody.innerHTML = '<tr><td colspan="6" class="text-center"><span class="spinner"></span> جاري التحميل...</td></tr>';
    
    try {
        const response = await fetch('api/lessons.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=list'
        });
        const data = await response.json();
        
        if (data.success && data.data.lessons.length > 0) {
            tbody.innerHTML = data.data.lessons.map(lesson => `
                <tr>
                    <td>${escapeHtml(lesson.title)}</td>
                    <td>${escapeHtml(lesson.lesson_type)}</td>
                    <td>${escapeHtml(lesson.level || '-')}</td>
                    <td>${escapeHtml(lesson.subject_name || '-')}</td>
                    <td><span class="status-badge ${lesson.is_published ? 'active' : 'inactive'}">${lesson.is_published ? 'منشور' : 'مخفي'}</span></td>
                    <td>
                        <button class="btn btn-sm btn-outline" onclick="editLesson(${lesson.id})">
                            <i class="fa-solid fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="deleteLesson(${lesson.id})">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `).join('');
        } else {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center">لا يوجد دروس</td></tr>';
        }
    } catch (error) {
        console.error('Error loading lessons:', error);
        tbody.innerHTML = '<tr><td colspan="6" class="text-center">حدث خطأ في التحميل</td></tr>';
    }
}

async function loadMessages() {
    const tbody = document.getElementById('messagesTableBody');
    tbody.innerHTML = '<tr><td colspan="6" class="text-center"><span class="spinner"></span> جاري التحميل...</td></tr>';
    
    try {
        const response = await fetch('api/messages.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=list'
        });
        const data = await response.json();
        
        if (data.success && data.data.messages.length > 0) {
            tbody.innerHTML = data.data.messages.map(message => `
                <tr>
                    <td>${escapeHtml(message.name)}</td>
                    <td>${escapeHtml(message.email)}</td>
                    <td>${escapeHtml(message.subject || '-')}</td>
                    <td>${escapeHtml(message.created_at)}</td>
                    <td><span class="status-badge ${message.status === 'new' ? 'active' : 'inactive'}">${message.status === 'new' ? 'جديد' : message.status}</span></td>
                    <td>
                        <button class="btn btn-sm btn-outline" onclick="viewMessage(${message.id})">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="deleteMessage(${message.id})">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `).join('');
        } else {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center">لا توجد رسائل</td></tr>';
        }
    } catch (error) {
        console.error('Error loading messages:', error);
        tbody.innerHTML = '<tr><td colspan="6" class="text-center">حدث خطأ في التحميل</td></tr>';
    }
}

async function loadRegistrations() {
    const tbody = document.getElementById('registrationsTableBody');
    tbody.innerHTML = '<tr><td colspan="6" class="text-center"><span class="spinner"></span> جاري التحميل...</td></tr>';
    
    try {
        const response = await fetch('api/registrations.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=list'
        });
        const data = await response.json();
        
        if (data.success && data.data.requests.length > 0) {
            tbody.innerHTML = data.data.requests.map(req => `
                <tr>
                    <td>${escapeHtml(req.student_code)}</td>
                    <td>${escapeHtml(req.first_name + ' ' + req.last_name)}</td>
                    <td>${escapeHtml(req.class_name)}</td>
                    <td>${escapeHtml(req.level)}</td>
                    <td><span class="status-badge ${req.status === 'pending' ? 'warning' : req.status === 'approved' ? 'active' : 'inactive'}">${req.status === 'pending' ? 'قيد الانتظار' : req.status === 'approved' ? 'موافق عليه' : 'مرفوض'}</span></td>
                    <td>
                        ${req.status === 'pending' ? `
                            <button class="btn btn-sm btn-primary" onclick="approveRegistration(${req.id})">
                                <i class="fa-solid fa-check"></i>
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="rejectRegistration(${req.id})">
                                <i class="fa-solid fa-times"></i>
                            </button>
                        ` : ''}
                    </td>
                </tr>
            `).join('');
        } else {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center">لا توجد طلبات تسجيل</td></tr>';
        }
    } catch (error) {
        console.error('Error loading registrations:', error);
        tbody.innerHTML = '<tr><td colspan="6" class="text-center">حدث خطأ في التحميل</td></tr>';
    }
}

async function loadAccounts() {
    const tbody = document.getElementById('accountsTableBody');
    tbody.innerHTML = '<tr><td colspan="6" class="text-center"><span class="spinner"></span> جاري التحميل...</td></tr>';
    
    try {
        const response = await fetch('api/accounts.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=list'
        });
        const data = await response.json();
        
        if (data.success && data.data.admins.length > 0) {
            tbody.innerHTML = data.data.admins.map(admin => `
                <tr>
                    <td>${escapeHtml(admin.full_name)}</td>
                    <td>${escapeHtml(admin.email)}</td>
                    <td>${escapeHtml(admin.role === 'director' ? 'المدير' : 'السكرتارية')}</td>
                    <td><span class="status-badge ${admin.status === 'active' ? 'active' : 'inactive'}">${admin.status === 'active' ? 'نشط' : 'غير نشط'}</span></td>
                    <td>${escapeHtml(admin.last_login_at || '-')}</td>
                    <td>
                        <button class="btn btn-sm btn-outline" onclick="editAccount(${admin.id})">
                            <i class="fa-solid fa-edit"></i>
                        </button>
                        ${admin.id !== window.currentAdminId ? `
                            <button class="btn btn-sm btn-danger" onclick="deleteAccount(${admin.id})">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        ` : ''}
                    </td>
                </tr>
            `).join('');
        } else {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center">لا توجد حسابات</td></tr>';
        }
    } catch (error) {
        console.error('Error loading accounts:', error);
        tbody.innerHTML = '<tr><td colspan="6" class="text-center">حدث خطأ في التحميل</td></tr>';
    }
}

// Modal Functions
function openModal(title, content) {
    document.getElementById('modalTitle').textContent = title;
    document.getElementById('modalBody').innerHTML = content;
    document.getElementById('modal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('modal').style.display = 'none';
}

// Utility Functions
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showFeedback(element, message, type) {
    element.textContent = message;
    element.className = 'feedback ' + type;
    element.style.display = 'block';
    
    setTimeout(() => {
        element.style.display = 'none';
    }, 5000);
}

// Placeholder functions for edit/delete (to be implemented)
function editStudent(id) { console.log('Edit student:', id); }
function deleteStudent(id) { console.log('Delete student:', id); }
function editTeacher(id) { console.log('Edit teacher:', id); }
function deleteTeacher(id) { console.log('Delete teacher:', id); }
function editLesson(id) { console.log('Edit lesson:', id); }
function deleteLesson(id) { console.log('Delete lesson:', id); }
function viewMessage(id) { console.log('View message:', id); }
function deleteMessage(id) { console.log('Delete message:', id); }
function approveRegistration(id) { console.log('Approve registration:', id); }
function rejectRegistration(id) { console.log('Reject registration:', id); }
function editAccount(id) { console.log('Edit account:', id); }
function deleteAccount(id) { console.log('Delete account:', id); }
