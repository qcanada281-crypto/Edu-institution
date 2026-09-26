/**
 * Admin Demo Mode & Vercel Mock API Handler
 * Allows full inspection, login, and testing of the Admin Dashboard on Vercel
 * or any static hosting without requiring a live PHP/MySQL backend.
 */
(function () {
    'use strict';

    const DEMO_STORAGE_PREFIX = 'kawkab_demo_';
    const DEMO_USER_KEY = DEMO_STORAGE_PREFIX + 'admin_user';
    const DEMO_ACTIVE_KEY = DEMO_STORAGE_PREFIX + 'active';
    const DEMO_STUDENTS_KEY = DEMO_STORAGE_PREFIX + 'students';
    const DEMO_LESSONS_KEY = DEMO_STORAGE_PREFIX + 'lessons';
    const DEMO_TEACHERS_KEY = DEMO_STORAGE_PREFIX + 'teachers';
    const DEMO_TEACHER_REQS_KEY = DEMO_STORAGE_PREFIX + 'teacher_requests';
    const DEMO_ABSENCES_KEY = DEMO_STORAGE_PREFIX + 'teacher_absences';
    const DEMO_GRADES_KEY = DEMO_STORAGE_PREFIX + 'grades';
    const DEMO_ATTENDANCE_KEY = DEMO_STORAGE_PREFIX + 'attendance';

    // Environment detection
    const isVercelHost = window.location.hostname.includes('vercel.app');
    const isStaticHost = window.location.protocol === 'file:' ||
        window.location.hostname.includes('github.io') ||
        window.location.hostname.includes('netlify.app') ||
        window.location.hostname.includes('pages.dev');
    const isDemoQuery = window.location.search.includes('demo=1') || window.location.search.includes('demo=true');
    const isLocalhost = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';

    function isDemoMode() {
        return isVercelHost || isStaticHost || isDemoQuery || sessionStorage.getItem(DEMO_ACTIVE_KEY) === 'true';
    }

    function activateDemoMode() {
        sessionStorage.setItem(DEMO_ACTIVE_KEY, 'true');
        updateDemoBadges();
    }

    // Default demo dataset
    const INITIAL_STUDENTS = [
        {
            id: 1,
            student_code: 'STU2026001',
            first_name: 'أمين',
            last_name: 'التازي',
            birth_date: '2008-04-16',
            gender: 'male',
            class_name: '1BAC-SC1',
            level: 'Lycée',
            email: 'amine.tazi@example.ma',
            phone: '0661122334',
            address: 'شارع الحسن الثاني، الرباط',
            guardian_name: 'محمد التازي',
            guardian_phone: '0661234567',
            guardian_email: 'm.tazi@example.com',
            registration_status: 'approved',
            is_request: 'no'
        },
        {
            id: 2,
            student_code: 'STU2026002',
            first_name: 'فاطمة الزهراء',
            last_name: 'بنجلون',
            birth_date: '2007-09-22',
            gender: 'female',
            class_name: '2BAC-SM',
            level: 'Lycée',
            email: 'fatima.benjelloun@example.ma',
            phone: '0662233445',
            address: 'حي أكدال، الرباط',
            guardian_name: 'كريم بنجلون',
            guardian_phone: '0662345678',
            guardian_email: 'k.benjelloun@example.com',
            registration_status: 'approved',
            is_request: 'no'
        },
        {
            id: 3,
            student_code: 'STU2026003',
            first_name: 'يوسف',
            last_name: 'العلمي',
            birth_date: '2010-02-14',
            gender: 'male',
            class_name: '3AC-1',
            level: 'Collège',
            email: 'youssef.alami@example.ma',
            phone: '0663344556',
            address: 'حي الرياض، الرباط',
            guardian_name: 'رشيد العلمي',
            guardian_phone: '0663456789',
            guardian_email: 'r.alami@example.com',
            registration_status: 'approved',
            is_request: 'no'
        },
        {
            id: 4,
            student_code: 'STU2026004',
            first_name: 'سارة',
            last_name: 'الإدريسي',
            birth_date: '2012-07-05',
            gender: 'female',
            class_name: '1AC-2',
            level: 'Collège',
            email: 'sara.idrissi@example.ma',
            phone: '0664455667',
            address: 'شارع فرنسا، الرباط',
            guardian_name: 'حنان الإدريسي',
            guardian_phone: '0664567890',
            guardian_email: 'h.idrissi@example.com',
            registration_status: 'pending',
            is_request: 'yes'
        },
        {
            id: 5,
            student_code: 'STU2026005',
            first_name: 'حمزة',
            last_name: 'الفاسي',
            birth_date: '2013-11-18',
            gender: 'male',
            class_name: '6AP-A',
            level: 'Primaire',
            email: 'hamza.fassi@example.ma',
            phone: '0665566778',
            address: 'حي حسان، الرباط',
            guardian_name: 'عزيز الفاسي',
            guardian_phone: '0665678901',
            guardian_email: 'a.fassi@example.com',
            registration_status: 'approved',
            is_request: 'no'
        },
        {
            id: 6,
            student_code: 'STU2026006',
            first_name: 'مريم',
            last_name: 'الصنهاجي',
            birth_date: '2009-05-30',
            gender: 'female',
            class_name: 'TC-SC1',
            level: 'Lycée',
            email: 'mariam.sanhaji@example.ma',
            phone: '0666677889',
            address: 'شارع محمد السادس، سلا',
            guardian_name: 'طارق الصنهاجي',
            guardian_phone: '0666789012',
            guardian_email: 't.sanhaji@example.com',
            registration_status: 'approved',
            is_request: 'no'
        }
    ];

    const INITIAL_LESSONS = [
        {
            id: 1,
            title: 'درس الجبر - الدوال العددية وتطبيقاتها',
            description: 'شرح مفصل في الحساب الجبري والدوال للمستوى الثانوي التأهيلي.',
            lesson_type: 'video',
            level: 'Lycée',
            subject_name: 'الرياضيات',
            content_url: 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            thumbnail_url: 'images/hero-bg.jpg',
            created_at: '2026-03-20 10:00:00'
        },
        {
            id: 2,
            title: 'ملخص شامل - ميكانيكا نيوتن والحركة',
            description: 'ملخص قوانين نيوتن وتطبيقاتها بصيغة PDF قابلة للتحميل.',
            lesson_type: 'pdf',
            level: 'Lycée',
            subject_name: 'الفيزياء والكيمياء',
            content_url: 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf',
            thumbnail_url: '',
            created_at: '2026-03-22 14:30:00'
        },
        {
            id: 3,
            title: 'رسم توضيحي - بنية الخلية النباتية والحيوانية',
            description: 'صورة علمية عالية الجودة توضح الفرق بين العضيات الخلوية.',
            lesson_type: 'image',
            level: 'Collège',
            subject_name: 'علوم الحياة والأرض',
            content_url: 'images/classroom.jpg',
            thumbnail_url: '',
            created_at: '2026-03-24 09:15:00'
        }
    ];

    const INITIAL_TEACHERS = [
        { id: 1, full_name: 'أ. أحمد مرابط', email: 'a.mourabit@kawkab-ouloum.ma', phone: '0661112233', subject_name: 'الرياضيات', status: 'active', created_at: '2026-01-10' },
        { id: 2, full_name: 'أستاذة نادية برادة', email: 'n.barrada@kawkab-ouloum.ma', phone: '0662223344', subject_name: 'الفيزياء والكيمياء', status: 'active', created_at: '2026-01-12' },
        { id: 3, full_name: 'أ. هشام الصقلي', email: 'h.seqli@kawkab-ouloum.ma', phone: '0663334455', subject_name: 'علوم الحياة والأرض', status: 'active', created_at: '2026-02-01' }
    ];

    const INITIAL_TEACHER_REQS = [
        { id: 101, full_name: 'أ. كريم البقالي', email: 'k.baqqali@gmail.com', phone: '0664445566', subject_name: 'اللغة الفرنسية', status: 'pending', created_at: '2026-03-25' }
    ];

    const INITIAL_ABSENCES = [
        {
            id: 1,
            teacher_name: 'أستاذة نادية برادة',
            teacher_phone: '0662223344',
            teacher_email: 'n.barrada@kawkab-ouloum.ma',
            teacher_subject: 'الفيزياء والكيمياء',
            absence_date: '2026-03-28',
            type: 'مرضي',
            reason: 'وعكة صحية طارئة ومراجعة المستشفى',
            document_path: '',
            is_urgent: true,
            status: 'pending',
            admin_notes: '',
            created_at: '2026-03-26 08:30:00'
        }
    ];

    // Local state helpers
    function getStoredData(key, fallback) {
        try {
            const raw = sessionStorage.getItem(key);
            if (raw) return JSON.parse(raw);
        } catch (e) { }
        return JSON.parse(JSON.stringify(fallback));
    }

    function setStoredData(key, value) {
        try {
            sessionStorage.setItem(key, JSON.stringify(value));
        } catch (e) { }
    }

    // Initialize mock datasets
    function initDemoStorage() {
        if (!sessionStorage.getItem(DEMO_STUDENTS_KEY)) {
            setStoredData(DEMO_STUDENTS_KEY, INITIAL_STUDENTS);
        }
        if (!sessionStorage.getItem(DEMO_LESSONS_KEY)) {
            setStoredData(DEMO_LESSONS_KEY, INITIAL_LESSONS);
        }
        if (!sessionStorage.getItem(DEMO_TEACHERS_KEY)) {
            setStoredData(DEMO_TEACHERS_KEY, INITIAL_TEACHERS);
        }
        if (!sessionStorage.getItem(DEMO_TEACHER_REQS_KEY)) {
            setStoredData(DEMO_TEACHER_REQS_KEY, INITIAL_TEACHER_REQS);
        }
        if (!sessionStorage.getItem(DEMO_ABSENCES_KEY)) {
            setStoredData(DEMO_ABSENCES_KEY, INITIAL_ABSENCES);
        }
    }

    initDemoStorage();

    // Helper to simulate JSON response
    function createJsonResponse(data, status = 200) {
        return new Response(JSON.stringify(data), {
            status: status,
            statusText: status === 200 ? 'OK' : 'Error',
            headers: {
                'Content-Type': 'application/json; charset=utf-8'
            }
        });
    }

    // Parse form-data / body parameters
    async function extractParams(init) {
        const params = {};
        if (!init || !init.body) return params;

        if (init.body instanceof FormData) {
            init.body.forEach((val, key) => {
                params[key] = val;
            });
        } else if (typeof init.body === 'string') {
            try {
                const parsed = JSON.parse(init.body);
                Object.assign(params, parsed);
            } catch (e) {
                const sp = new URLSearchParams(init.body);
                sp.forEach((val, key) => {
                    params[key] = val;
                });
            }
        }
        return params;
    }

    // Router for mock backend
    async function handleMockBackend(url, init) {
        const urlObj = new URL(url, window.location.href);
        const path = urlObj.pathname.toLowerCase();
        const queryAction = urlObj.searchParams.get('action') || '';
        const params = await extractParams(init);
        const action = String(params.action || queryAction || '').toLowerCase();

        // 1. Health check
        if (path.includes('db_health.php')) {
            return createJsonResponse({
                success: true,
                message: 'تم الاتصال بوضع المعاينة السحابي (Vercel Demo).',
                data: {
                    database: 'edu_institution (Vercel Demo)',
                    total_tables: 4,
                    found_tables: ['messages', 'personal_info', 'projects', 'skills'],
                    missing_tables: []
                }
            });
        }

        // 2. Admin Auth (Login)
        if (path.includes('admin_auth.php')) {
            const role = String(params.role || 'director').toLowerCase();
            const email = String(params.email || '').trim().toLowerCase();
            const code = String(params.code || '');

            const isDirector = role === 'director' || email.includes('admin');
            const targetRole = isDirector ? 'director' : 'secretary';

            const user = {
                id: isDirector ? 1 : 2,
                full_name: isDirector ? 'السيد المدير التربوي' : 'السكرتارية الإدارية',
                email: email || (isDirector ? 'admin@kawkab-ouloum.ma' : 'secretary@kawkab-ouloum.ma'),
                role: targetRole,
                role_label: isDirector ? 'المدير' : 'السكرتارية'
            };

            sessionStorage.setItem(DEMO_USER_KEY, JSON.stringify(user));
            activateDemoMode();

            return createJsonResponse({
                success: true,
                message: `تم تسجيل الدخول بنجاح كـ ${user.role_label} (وضع المعاينة).`,
                data: { user: user }
            });
        }

        // 3. Logout
        if (path.includes('logout.php')) {
            sessionStorage.removeItem(DEMO_USER_KEY);
            return createJsonResponse({
                success: true,
                message: 'تم تسجيل الخروج بنجاح.'
            });
        }

        // 4. Admin Students
        if (path.includes('admin_students.php')) {
            const currentUser = getStoredData(DEMO_USER_KEY, null);
            if (!currentUser) {
                return createJsonResponse({
                    success: false,
                    message: 'قم بتسجيل الدخول أولا.'
                }, 403);
            }

            let students = getStoredData(DEMO_STUDENTS_KEY, INITIAL_STUDENTS);

            if (action === 'list') {
                return createJsonResponse({
                    success: true,
                    message: 'تم جلب بيانات التلاميذ (وضع المعاينة).',
                    data: {
                        admin: currentUser,
                        user: currentUser,
                        students: students,
                        stats: {
                            students_count: students.length,
                            grades_count: 48,
                            attendance_count: 12
                        },
                        permissions: {
                            can_manage_students: currentUser.role === 'director',
                            can_view_reports: currentUser.role === 'director'
                        }
                    }
                });
            }

            if (action === 'save') {
                const sId = Number(params.student_id || 0);
                if (sId > 0) {
                    const idx = students.findIndex(s => Number(s.id) === sId);
                    if (idx !== -1) {
                        students[idx] = Object.assign({}, students[idx], {
                            first_name: params.first_name || students[idx].first_name,
                            last_name: params.last_name || students[idx].last_name,
                            birth_date: params.birth_date || students[idx].birth_date,
                            gender: params.gender || students[idx].gender,
                            class_name: params.class_name || students[idx].class_name,
                            level: params.level || students[idx].level,
                            email: params.email || students[idx].email,
                            phone: params.phone || students[idx].phone,
                            address: params.address || students[idx].address,
                            guardian_name: params.guardian_name || students[idx].guardian_name,
                            guardian_phone: params.guardian_phone || students[idx].guardian_phone,
                            guardian_email: params.guardian_email || students[idx].guardian_email
                        });
                    }
                } else {
                    const newId = students.length > 0 ? Math.max(...students.map(s => Number(s.id) || 0)) + 1 : 1;
                    const newCode = params.student_code || ('STU2026' + String(newId).padStart(3, '0'));
                    students.unshift({
                        id: newId,
                        student_code: newCode,
                        first_name: params.first_name || 'طالب',
                        last_name: params.last_name || 'جديد',
                        birth_date: params.birth_date || '2010-01-01',
                        gender: params.gender || 'male',
                        class_name: params.class_name || '1AC-1',
                        level: params.level || 'Collège',
                        email: params.email || '',
                        phone: params.phone || '',
                        address: params.address || '',
                        guardian_name: params.guardian_name || 'ولي أمر',
                        guardian_phone: params.guardian_phone || '0600000000',
                        guardian_email: params.guardian_email || '',
                        registration_status: 'approved',
                        is_request: 'no'
                    });
                }
                setStoredData(DEMO_STUDENTS_KEY, students);
                return createJsonResponse({
                    success: true,
                    message: 'تم حفظ بيانات الطالب بنجاح (وضع المعاينة).'
                });
            }

            if (action === 'delete') {
                const sId = Number(params.id || params.student_id || 0);
                students = students.filter(s => Number(s.id) !== sId);
                setStoredData(DEMO_STUDENTS_KEY, students);
                return createJsonResponse({
                    success: true,
                    message: 'تم حذف الطالب بنجاح (وضع المعاينة).'
                });
            }

            if (action === 'verify') {
                const sId = Number(params.id || 0);
                const newStatus = params.status || 'approved';
                const idx = students.findIndex(s => Number(s.id) === sId);
                if (idx !== -1) {
                    students[idx].registration_status = newStatus;
                    setStoredData(DEMO_STUDENTS_KEY, students);
                }
                return createJsonResponse({
                    success: true,
                    message: 'تم تحديث حالة طلب التسجيل بنجاح.'
                });
            }
        }

        // 5. Admin Records (Grades & Attendance)
        if (path.includes('admin_records.php')) {
            if (action === 'get_grades') {
                const sId = Number(params.student_id || 1);
                const semester = params.semester || 'S1';
                const sampleGrades = [
                    { id: 1, subject_name: 'الرياضيات', continuous_score: '17.00', exam_score: '16.50', coefficient: '7.00', semester: semester },
                    { id: 2, subject_name: 'الفيزياء والكيمياء', continuous_score: '15.50', exam_score: '16.00', coefficient: '5.00', semester: semester },
                    { id: 3, subject_name: 'علوم الحياة والأرض', continuous_score: '16.00', exam_score: '15.00', coefficient: '5.00', semester: semester },
                    { id: 4, subject_name: 'اللغة الفرنسية', continuous_score: '14.50', exam_score: '15.00', coefficient: '4.00', semester: semester },
                    { id: 5, subject_name: 'اللغة العربية', continuous_score: '16.50', exam_score: '16.00', coefficient: '2.00', semester: semester },
                    { id: 6, subject_name: 'اللغة الإنجليزية', continuous_score: '17.50', exam_score: '18.00', coefficient: '2.00', semester: semester },
                    { id: 7, subject_name: 'الفلسفة', continuous_score: '14.00', exam_score: '13.50', coefficient: '2.00', semester: semester }
                ];
                return createJsonResponse({
                    success: true,
                    message: 'تم جلب النقط بنجاح.',
                    data: sampleGrades
                });
            }

            if (action === 'add_grade' || action === 'update_grade') {
                return createJsonResponse({
                    success: true,
                    message: 'تم تسجيل النقطة بنجاح (وضع المعاينة).'
                });
            }

            if (action === 'get_attendance') {
                const sampleAttendance = [
                    {
                        id: 1,
                        absence_date: '2026-03-24',
                        session_label: 'الحصة الأولى (08:30 - 10:30)',
                        absence_subject: 'الرياضيات',
                        justified: 1,
                        notes: 'شهادة طبية مقبولة'
                    },
                    {
                        id: 2,
                        absence_date: '2026-03-18',
                        session_label: 'الحصة الثالثة (14:30 - 16:30)',
                        absence_subject: 'اللغة الفرنسية',
                        justified: 0,
                        notes: 'تأخر غير مبرر'
                    }
                ];
                return createJsonResponse({
                    success: true,
                    message: 'تم جلب الغياب بنجاح.',
                    data: sampleAttendance
                });
            }

            if (action === 'add_attendance') {
                return createJsonResponse({
                    success: true,
                    message: 'تم تسجيل الغياب بنجاح (وضع المعاينة).'
                });
            }
        }

        // 6. Admin Lessons
        if (path.includes('admin_lessons.php') || path.includes('lessons_list.php')) {
            let lessons = getStoredData(DEMO_LESSONS_KEY, INITIAL_LESSONS);
            if (action === 'list' || !action) {
                return createJsonResponse({
                    success: true,
                    data: { lessons: lessons }
                });
            }
            if (action === 'save') {
                const newId = lessons.length > 0 ? Math.max(...lessons.map(l => Number(l.id) || 0)) + 1 : 1;
                lessons.unshift({
                    id: newId,
                    title: params.title || 'درس جديد',
                    description: params.description || '',
                    lesson_type: params.lesson_type || 'video',
                    level: params.level || 'Lycée',
                    subject_name: params.subject_name || 'عام',
                    content_url: params.content_url || '#',
                    thumbnail_url: params.thumbnail_url || '',
                    created_at: new Date().toISOString().slice(0, 19).replace('T', ' ')
                });
                setStoredData(DEMO_LESSONS_KEY, lessons);
                return createJsonResponse({
                    success: true,
                    message: 'تم حفظ الدرس بنجاح (وضع المعاينة).'
                });
            }
            if (action === 'delete') {
                const lId = Number(params.id || 0);
                lessons = lessons.filter(l => Number(l.id) !== lId);
                setStoredData(DEMO_LESSONS_KEY, lessons);
                return createJsonResponse({
                    success: true,
                    message: 'تم حذف الدرس بنجاح.'
                });
            }
        }

        // 7. Admin Teachers
        if (path.includes('admin_teachers.php')) {
            let teachers = getStoredData(DEMO_TEACHERS_KEY, INITIAL_TEACHERS);
            let pending = getStoredData(DEMO_TEACHER_REQS_KEY, INITIAL_TEACHER_REQS);

            if (action === 'list') {
                return createJsonResponse({
                    success: true,
                    data: { teachers: teachers, pending: pending }
                });
            }

            if (action === 'approve') {
                const reqId = Number(params.request_id || 0);
                const req = pending.find(r => Number(r.id) === reqId);
                if (req) {
                    pending = pending.filter(r => Number(r.id) !== reqId);
                    teachers.unshift({
                        id: teachers.length + 1,
                        full_name: req.full_name,
                        email: req.email,
                        phone: req.phone,
                        subject_name: req.subject_name,
                        status: 'active',
                        created_at: new Date().toISOString().slice(0, 10)
                    });
                    setStoredData(DEMO_TEACHERS_KEY, teachers);
                    setStoredData(DEMO_TEACHER_REQS_KEY, pending);
                }
                return createJsonResponse({
                    success: true,
                    message: 'تمت الموافقة على طلب الأستاذ ونقله لقائمة الأساتذة.'
                });
            }

            if (action === 'reject') {
                const reqId = Number(params.request_id || 0);
                pending = pending.filter(r => Number(r.id) !== reqId);
                setStoredData(DEMO_TEACHER_REQS_KEY, pending);
                return createJsonResponse({
                    success: true,
                    message: 'تم رفض طلب الأستاذ.'
                });
            }
        }

        // 8. Admin Teacher Absences
        if (path.includes('admin_teacher_absences.php')) {
            const absences = getStoredData(DEMO_ABSENCES_KEY, INITIAL_ABSENCES);
            return createJsonResponse({
                success: true,
                data: {
                    can_review: true,
                    urgent_count: absences.filter(a => a.is_urgent && a.status === 'pending').length,
                    absences: absences
                }
            });
        }

        // 9. Annual Promotion (الترحيل السنوي)
        if (path.includes('promotion_annuelle.php')) {
            if (action === 'preview') {
                return createJsonResponse({
                    success: true,
                    data: {
                        annee_courante: '2025/2026',
                        total_eleves: 6,
                        stats_par_niveau: {
                            'Lycée': { admis: 4, redoublants: 0 },
                            'Collège': { admis: 2, redoublants: 0 }
                        },
                        preview: [
                            { student_code: 'STU2026001', full_name: 'أمين التازي', current_level: '1BAC', moyenne_s1: 15.5, moyenne_s2: 16.0, moyenne_gen: 15.75, mention: 'حسن جدا', resultat: 'admis', next_level: '2BAC' },
                            { student_code: 'STU2026002', full_name: 'فاطمة الزهراء بنجلون', current_level: '2BAC', moyenne_s1: 16.8, moyenne_s2: 17.2, moyenne_gen: 17.00, mention: 'ممتاز', resultat: 'admis', next_level: 'متخرج' },
                            { student_code: 'STU2026003', full_name: 'يوسف العلمي', current_level: '3AC', moyenne_s1: 14.2, moyenne_s2: 14.8, moyenne_gen: 14.50, mention: 'حسن', resultat: 'admis', next_level: 'TC' },
                            { student_code: 'STU2026005', full_name: 'حمزة الفاسي', current_level: '6AP', moyenne_s1: 16.0, moyenne_s2: 16.5, moyenne_gen: 16.25, mention: 'حسن جدا', resultat: 'admis', next_level: '1AC' },
                            { student_code: 'STU2026006', full_name: 'مريم الصنهاجي', current_level: 'TC', moyenne_s1: 13.8, moyenne_s2: 14.2, moyenne_gen: 14.00, mention: 'حسن', resultat: 'admis', next_level: '1BAC' }
                        ]
                    }
                });
            }

            if (action === 'execute') {
                return createJsonResponse({
                    success: true,
                    message: 'تم تنفيذ الترحيل السنوي بنجاح وحفظ النتائج في السجل.',
                    data: {
                        annee_from: '2025/2026',
                        annee_to: '2026/2027',
                        total_eleves: 6,
                        total_admis: 5,
                        total_redoublants: 0,
                        total_diplomes: 1,
                        total_classes: 4,
                        repartition: {
                            '2BAC': [{ nom_classe: '2BAC-SM', effectif: 1 }],
                            '1BAC': [{ nom_classe: '1BAC-SC1', effectif: 1 }],
                            'TC': [{ nom_classe: 'TC-SC', effectif: 1 }],
                            '1AC': [{ nom_classe: '1AC-1', effectif: 1 }]
                        }
                    }
                });
            }

            if (action === 'history') {
                return createJsonResponse({
                    success: true,
                    data: {
                        promotions: [
                            { annee_scolaire_from: '2024/2025', annee_scolaire_to: '2025/2026', total_eleves: 124, total_admis: 118, total_redoublants: 6, total_classes_creees: 6, date_promotion: '2025-06-30' },
                            { annee_scolaire_from: '2023/2024', annee_scolaire_to: '2024/2025', total_eleves: 110, total_admis: 105, total_redoublants: 5, total_classes_creees: 5, date_promotion: '2024-06-28' }
                        ]
                    }
                });
            }
        }

        // 10. Admin Contact Messages
        if (path.includes('admin_messages.php')) {
            return createJsonResponse({
                success: true,
                data: {
                    messages: [
                        {
                            id: 1,
                            full_name: 'محمد المنصوري',
                            email: 'm.mansouri@example.com',
                            phone: '0661998877',
                            subject: 'استفسار عن التسجيل في السلك الثانوي',
                            message: 'السلام عليكم، أود الاستفسار عن الوثائق المطلوبة ومواعيد التسجيل لمستوى الأولى باكالوريا علوم تجريبية.',
                            status: 'new',
                            created_at: '2026-03-25 11:20'
                        },
                        {
                            id: 2,
                            full_name: 'سعاد العمراني',
                            email: 'souad.amrani@example.com',
                            phone: '0662887766',
                            subject: 'طلب موعد مع الإدارة التربوية',
                            message: 'تحية طيبة، أرغب في حجز موعد مع السيد المدير لمناقشة التوجيه المدرسي لابني في الثالثة إعدادي.',
                            status: 'in_progress',
                            created_at: '2026-03-23 16:45'
                        }
                    ]
                }
            });
        }

        // 11. Courses API
        if (path.includes('courses_api.php') || path.includes('get_courses.php')) {
            const sampleCourses = [
                { id: 1, title: 'التعليم الابتدائي (Primaire)', description: 'برنامج تربوي متكامل يركز على بناء المهارات الأساسية وتنمية الذكاء.', category: 'primaire', level_tag: 'الابتدائي', is_published: 1, image_path: 'images/classroom.jpg' },
                { id: 2, title: 'التعليم الإعدادي (Collège)', description: 'تأطير علمي ولغوي متين لإعداد التلاميذ لاختيارات التوجيه السليمة.', category: 'college', level_tag: 'الإعدادي', is_published: 1, image_path: 'images/classroom.jpg' },
                { id: 3, title: 'التعليم الثانوي التأهيلي (Lycée)', description: 'شعب علمية وأدبية متميزة مع إعداد مكثف للباكالوريا والمباريات.', category: 'lycee', level_tag: 'الثانوي', is_published: 1, image_path: 'images/classroom.jpg' }
            ];
            return createJsonResponse({
                success: true,
                count: sampleCourses.length,
                data: sampleCourses,
                courses: sampleCourses
            });
        }

        // 12. Gallery API
        if (path.includes('gallery_api.php')) {
            const samplePhotos = [
                { id: 1, title: 'مختبر العلوم والفيزياء', category: 'facilities', image_path: 'images/labo.jpg', description: 'تجهيزات علمية متطورة لتطبيق التجارب المخبرية.', is_published: 1, photo_date: '2026-03-20', author_name: 'الإدارة' },
                { id: 2, title: 'الأسبوع الثقافي المدرسي', category: 'activities', image_path: 'images/classroom.jpg', description: 'أنشطة ومسابقات لتشجيع الإبداع والمطالعة.', is_published: 1, photo_date: '2026-03-15', author_name: 'الإدارة' }
            ];
            return createJsonResponse({
                success: true,
                count: samplePhotos.length,
                data: samplePhotos,
                photos: samplePhotos
            });
        }

        // 13. Notifications API
        if (path.includes('notifications_api.php')) {
            return createJsonResponse({
                success: true,
                whatsapp_url: 'https://wa.me/212600000000?text=' + encodeURIComponent('إشعار إداري من مؤسسة Kawkab Al Ouloum'),
                student_name: params.massar || 'التلميذ',
                notifications: [
                    { id: 1, title: 'تنبيه: اقتراب موعد فروض المراقبة المستمرة الثانية', created_at: '2026-03-26' }
                ]
            });
        }

        // 14. Portal Auth (Student/Parent login fallback)
        if (path.includes('portal_auth.php')) {
            const studentCode = String(params.student_code || '').trim().toUpperCase();
            return createJsonResponse({
                success: true,
                message: 'Connexion reussie (mode demo).',
                data: {
                    student: {
                        id: 1,
                        student_code: studentCode || 'G132026001',
                        first_name: 'أمين',
                        last_name: 'التازي',
                        class_name: '1BAC-SC1',
                        level: 'Lycée',
                        birth_date: '2008-04-16'
                    },
                    grades: [
                        { subject: 'Mathématiques', continu: '16.5', examen: '17.0', coeff: '7', moyenne: '16.75' },
                        { subject: 'Physique-Chimie', continu: '15.0', examen: '16.0', coeff: '5', moyenne: '15.50' }
                    ],
                    stats: {
                        average: '16.12',
                        attendance_rate: '98%',
                        total_absences: '1'
                    }
                }
            });
        }

        // Generic fallback for any other backend request
        return createJsonResponse({
            success: true,
            message: 'تمت العملية بنجاح (وضع المعاينة).'
        });
    }

    // Intercept window.fetch
    const originalFetch = window.fetch;
    window.fetch = async function (resource, init) {
        const url = typeof resource === 'string' ? resource : (resource && resource.url ? resource.url : '');

        // Check if targeting backend
        if (url.includes('backend/')) {
            if (isDemoMode()) {
                try {
                    return await handleMockBackend(url, init);
                } catch (mockErr) {
                    console.error('Demo Mock API Error:', mockErr);
                    return createJsonResponse({ success: false, message: mockErr.message }, 500);
                }
            }

            // On local or non-demo, try real server first
            try {
                const response = await originalFetch(resource, init);
                // If static host or 404/405/html error on a php endpoint, fallback to mock
                const contentType = response.headers.get('content-type') || '';
                if (!response.ok || !contentType.includes('application/json')) {
                    activateDemoMode();
                    return await handleMockBackend(url, init);
                }
                return response;
            } catch (networkError) {
                // Server down or network error: activate demo mode
                activateDemoMode();
                return await handleMockBackend(url, init);
            }
        }

        return originalFetch(resource, init);
    };

    // Update UI Elements
    function updateDemoBadges() {
        const notice = document.getElementById('vercelNotice');
        if (notice) {
            notice.style.display = 'block';
        }

        const badge = document.getElementById('demoModeBadge');
        if (badge) {
            badge.style.display = 'inline-flex';
        }

        const connStatus = document.getElementById('dbConnectionStatus');
        if (connStatus && isDemoMode()) {
            connStatus.className = 'status-pill is-success';
            connStatus.innerHTML = '<i class="fa-solid fa-cloud-check"></i> وضع المعاينة (Vercel Demo) شغال';
        }
    }

    // Bind Quick Login Buttons
    function setupDemoButtons() {
        const btnDirector = document.getElementById('demoLoginDirector');
        const btnSecretary = document.getElementById('demoLoginSecretary');
        const roleSelect = document.getElementById('admin_role');
        const emailInput = document.getElementById('admin_email');
        const codeInput = document.getElementById('admin_code');
        const loginForm = document.getElementById('adminLoginForm');

        if (btnDirector) {
            btnDirector.addEventListener('click', (e) => {
                e.preventDefault();
                activateDemoMode();
                if (roleSelect) roleSelect.value = 'director';
                if (emailInput) emailInput.value = 'admin@kawkab-ouloum.ma';
                if (codeInput) codeInput.value = 'Admin@2026';
                if (loginForm) {
                    loginForm.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
                }
            });
        }

        if (btnSecretary) {
            btnSecretary.addEventListener('click', (e) => {
                e.preventDefault();
                activateDemoMode();
                if (roleSelect) roleSelect.value = 'secretary';
                if (emailInput) emailInput.value = 'secretary@kawkab-ouloum.ma';
                if (codeInput) codeInput.value = 'Secretary@2026';
                if (loginForm) {
                    loginForm.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
                }
            });
        }

        if (isDemoMode()) {
            updateDemoBadges();
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', setupDemoButtons);
    } else {
        setupDemoButtons();
    }

    // Expose global helper for manual switching or debugging
    window.AdminDemo = {
        isDemoMode: isDemoMode,
        activate: activateDemoMode,
        resetData: function () {
            sessionStorage.removeItem(DEMO_STUDENTS_KEY);
            sessionStorage.removeItem(DEMO_LESSONS_KEY);
            sessionStorage.removeItem(DEMO_TEACHERS_KEY);
            sessionStorage.removeItem(DEMO_TEACHER_REQS_KEY);
            sessionStorage.removeItem(DEMO_ABSENCES_KEY);
            initDemoStorage();
            window.location.reload();
        }
    };
})();
