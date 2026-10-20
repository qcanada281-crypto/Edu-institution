/**
 * Universal Site Demo & Vercel Mock API Handler
 * KAWKAB AL OULOUM - مؤسسة كوكب العلوم التعليمية
 * 
 * Provides a complete client-side mock backend for all features across the site:
 * - Admin Portal (management, approvals, students, grades, attendance, promotions)
 * - Teacher Portal (login, registration, profile, timetable, absences, homework, lessons, announcements)
 * - Student/Parent Portal (login, report card, transcripts, attendance, notifications)
 * - Public Directory (teachers list, details, courses, gallery, contact, newsletter)
 * 
 * Automatically activates on Vercel, static hosts, or when local PHP/MySQL is unavailable.
 */
(function () {
    'use strict';

    const DEMO_STORAGE_PREFIX = 'kawkab_demo_';
    const DEMO_ACTIVE_KEY = DEMO_STORAGE_PREFIX + 'active';
    const DEMO_USER_KEY = DEMO_STORAGE_PREFIX + 'admin_user';
    const DEMO_TEACHER_USER_KEY = DEMO_STORAGE_PREFIX + 'teacher_user';
    const DEMO_STUDENTS_KEY = DEMO_STORAGE_PREFIX + 'students';
    const DEMO_LESSONS_KEY = DEMO_STORAGE_PREFIX + 'lessons';
    const DEMO_TEACHERS_KEY = DEMO_STORAGE_PREFIX + 'teachers';
    const DEMO_TEACHER_REQS_KEY = DEMO_STORAGE_PREFIX + 'teacher_requests';
    const DEMO_ABSENCES_KEY = DEMO_STORAGE_PREFIX + 'teacher_absences';
    const DEMO_GRADES_KEY = DEMO_STORAGE_PREFIX + 'grades';
    const DEMO_ATTENDANCE_KEY = DEMO_STORAGE_PREFIX + 'attendance';
    const DEMO_TIMETABLE_KEY = DEMO_STORAGE_PREFIX + 'teacher_timetable';
    const DEMO_HOMEWORK_KEY = DEMO_STORAGE_PREFIX + 'teacher_homework';
    const DEMO_ANNOUNCEMENTS_KEY = DEMO_STORAGE_PREFIX + 'teacher_announcements';
    const DEMO_MESSAGES_KEY = DEMO_STORAGE_PREFIX + 'contact_messages';
    const DEMO_GALLERY_KEY = DEMO_STORAGE_PREFIX + 'gallery_photos';
    const DEMO_COURSES_KEY = DEMO_STORAGE_PREFIX + 'school_courses';

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

    // Default Seed Data
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
            address: 'شارع الحسن الثاني، مراكش',
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
            address: 'حي المحاميد، مراكش',
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
            address: 'حي تاركة، مراكش',
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
            address: 'حي جيليز، مراكش',
            guardian_name: 'حمزة الإدريسي',
            guardian_phone: '0664567890',
            guardian_email: 'h.idrissi@example.com',
            registration_status: 'approved',
            is_request: 'no'
        },
        {
            id: 5,
            student_code: 'G132026001',
            first_name: 'عمر',
            last_name: 'المرابط',
            birth_date: '2008-04-16',
            gender: 'male',
            class_name: '1BAC-SC1',
            level: 'Lycée',
            email: 'omar.morabit@example.ma',
            phone: '0665566778',
            address: 'المدينة العتيقة، مراكش',
            guardian_name: 'سعيد المرابط',
            guardian_phone: '0665678901',
            guardian_email: 's.morabit@example.com',
            registration_status: 'approved',
            is_request: 'no'
        },
        {
            id: 6,
            student_code: 'STU2026005',
            first_name: 'زينب',
            last_name: 'الشرايبي',
            birth_date: '2015-11-30',
            gender: 'female',
            class_name: '5AP-1',
            level: 'Primaire',
            email: 'zaynab.chraibi@example.ma',
            phone: '0666677889',
            address: 'حي النخيل، مراكش',
            guardian_name: 'خالد الشرايبي',
            guardian_phone: '0666789012',
            guardian_email: 'k.chraibi@example.com',
            registration_status: 'pending',
            is_request: 'yes'
        }
    ];

    const INITIAL_TEACHERS = [
        {
            id: 1,
            full_name: 'الأستاذ رشيد بنعلي',
            subject_name: 'الرياضيات',
            specialty: 'مؤسسة Kawkab Al Ouloum - الثانوي التأهيلي',
            email: 'r.bennani@kawkab-ouloum.ma',
            phone: '0661122334',
            code: '123456',
            rating: '5.0',
            bio: 'أستاذ مبرز في مادة الرياضيات، خبرة أكثر من 15 سنة في تدريس البكالوريا والتحضير للمباريات الوطنية.',
            status: 'active',
            avatar: '',
            created_at: '2025-09-01'
        },
        {
            id: 2,
            full_name: 'الأستاذة نادية برادة',
            subject_name: 'الفيزياء والكيمياء',
            specialty: 'مؤسسة Kawkab Al Ouloum - الإعدادي والثانوي',
            email: 'n.barrada@kawkab-ouloum.ma',
            phone: '0662223344',
            code: '123456',
            rating: '4.9',
            bio: 'أستاذة متميزة حاصلة على دكتوراه في الكيمياء العضوية، تشرف على المختبر العلمي للمؤسسة.',
            status: 'active',
            avatar: '',
            created_at: '2025-09-01'
        },
        {
            id: 3,
            full_name: 'الأستاذ طارق العلمي',
            subject_name: 'علوم الحياة والأرض',
            specialty: 'مؤسسة Kawkab Al Ouloum - الجذع المشترك والأولى باك',
            email: 't.alami@kawkab-ouloum.ma',
            phone: '0663334455',
            code: '123456',
            rating: '4.8',
            bio: 'أستاذ مجاز ومؤطر للأندية البيئية بالمؤسسة، يركز على التجارب التطبيقية والتفاعلية.',
            status: 'active',
            avatar: '',
            created_at: '2025-09-01'
        },
        {
            id: 4,
            full_name: 'الأستاذة مريم الصنهاجي',
            subject_name: 'اللغة الفرنسية',
            specialty: 'مؤسسة Kawkab Al Ouloum - جميع الأسلاك',
            email: 'm.senhaji@kawkab-ouloum.ma',
            phone: '0664445566',
            code: '123456',
            rating: '5.0',
            bio: 'أستاذة متمكنة في اللغة والآداب الفرنسية، حاصلة على شهادات تدريب دولية في البيداغوجيا الحديثة.',
            status: 'active',
            avatar: '',
            created_at: '2025-09-01'
        },
        {
            id: 5,
            full_name: 'أستاذ تجريبي',
            subject_name: 'الرياضيات',
            specialty: 'مؤسسة Kawkab Al Ouloum',
            email: 'teacher@kawkab-ouloum.ma',
            phone: '0650032824',
            code: '123456',
            rating: '5.0',
            bio: 'حساب تجريبي لاختبار كافة وظائف فضاء الأستاذ.',
            status: 'active',
            avatar: '',
            created_at: '2026-01-01'
        }
    ];

    const INITIAL_TEACHER_REQS = [
        {
            id: 101,
            full_name: 'الأستاذ حسن المنصوري',
            email: 'h.mansouri@gmail.com',
            phone: '0665556677',
            subject_name: 'الفلسفة',
            code: '123456',
            status: 'pending',
            created_at: '2026-03-24 11:20:00'
        },
        {
            id: 102,
            full_name: 'الأستاذة كوثر الفاسي',
            email: 'k.fassi@gmail.com',
            phone: '0667778899',
            subject_name: 'اللغة الإنجليزية',
            code: '123456',
            status: 'pending',
            created_at: '2026-03-25 15:45:00'
        }
    ];

    const INITIAL_TIMETABLE = [
        { id: 1, teacher_id: 1, day_of_week: 1, start_time: '08:30:00', end_time: '10:30:00', class_name: '2BAC-SM', subject_name: 'الرياضيات', room: 'قاعة 4', notes: 'التحليل الرياضي' },
        { id: 2, teacher_id: 1, day_of_week: 2, start_time: '10:30:00', end_time: '12:30:00', class_name: '1BAC-SC1', subject_name: 'الرياضيات', room: 'قاعة 5', notes: 'الجبر والهندسة' },
        { id: 3, teacher_id: 1, day_of_week: 4, start_time: '14:30:00', end_time: '16:30:00', class_name: '2BAC-PC', subject_name: 'الرياضيات', room: 'قاعة 2', notes: 'تمارين تطبيقية' },
        { id: 4, teacher_id: 2, day_of_week: 1, start_time: '10:30:00', end_time: '12:30:00', class_name: '2BAC-PC', subject_name: 'الفيزياء والكيمياء', room: 'المختبر 1', notes: 'أعمال تطبيقية' },
        { id: 5, teacher_id: 2, day_of_week: 3, start_time: '08:30:00', end_time: '10:30:00', class_name: '1BAC-SC1', subject_name: 'الفيزياء والكيمياء', room: 'قاعة 3', notes: 'درس الكهرباء' },
        { id: 6, teacher_id: 3, day_of_week: 2, start_time: '14:30:00', end_time: '16:30:00', class_name: '1BAC-SC1', subject_name: 'علوم الحياة والأرض', room: 'المختبر 2', notes: 'علم الوراثة' },
        { id: 7, teacher_id: 5, day_of_week: 1, start_time: '08:30:00', end_time: '10:30:00', class_name: '1BAC-SC1', subject_name: 'الرياضيات', room: 'قاعة 1', notes: 'حصة تجريبية' },
        { id: 8, teacher_id: 5, day_of_week: 3, start_time: '10:30:00', end_time: '12:30:00', class_name: '2BAC-SM', subject_name: 'الرياضيات', room: 'قاعة 4', notes: 'حساب التكامل' }
    ];

    const INITIAL_HOMEWORK = [
        {
            id: 1,
            teacher_id: 1,
            title: 'تمارين حساب النهايات والاتصال',
            class_name: '2BAC-SM',
            subject: 'الرياضيات',
            description: 'إنجاز السلسلة رقم 3 من كتاب التمارين، الصفحات 45 و 46.',
            due_date: '2026-03-31',
            has_file: false,
            file_path: ''
        },
        {
            id: 2,
            teacher_id: 1,
            title: 'واجب منزلي: المتتاليات العددية',
            class_name: '1BAC-SC1',
            subject: 'الرياضيات',
            description: 'حل المسألة الشاملة المتعلقة بالبرهان بالترجع والمتتاليات الهندسية.',
            due_date: '2026-04-02',
            has_file: false,
            file_path: ''
        },
        {
            id: 3,
            teacher_id: 5,
            title: 'سلسلة تمارين الدوال اللوغاريتمية',
            class_name: '2BAC-SM',
            subject: 'الرياضيات',
            description: 'تحضير المسألة 1 و 2 للمناقشة داخل الفصل.',
            due_date: '2026-04-05',
            has_file: false,
            file_path: ''
        }
    ];

    const INITIAL_LESSONS = [
        {
            id: 1,
            title: 'درس النهايات والاتصال - ملخص شامل',
            description: 'ملخص مركز مع أمثلة تطبيقية لامتحانات البكالوريا الوطنية.',
            lesson_type: 'pdf',
            level: '2BAC-SM',
            subject_name: 'الرياضيات',
            content_url: 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf',
            thumbnail_url: 'images/kawkab_alouloum.jpeg',
            created_at: '2026-03-15 10:00:00',
            is_published: 1
        },
        {
            id: 2,
            title: 'شرح تفاعلي: قوانين نيوتن وتطبيقاتها',
            description: 'فيديو تعليمي عالي الجودة يشرح حركة الدقائق المشحونة في مجال كهرساكن.',
            lesson_type: 'video',
            level: '2BAC-PC',
            subject_name: 'الفيزياء والكيمياء',
            content_url: 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            thumbnail_url: 'images/kawkab_alouloum.jpeg',
            created_at: '2026-03-18 14:30:00',
            is_published: 1
        },
        {
            id: 3,
            title: 'ملخص درس الخبر الوراثي وآليات التعبير',
            description: 'خطاطات ورسومات توضيحية لتركيب البروتينات ومراحل الانقسام غير المباشر.',
            lesson_type: 'image',
            level: '1BAC-SC1',
            subject_name: 'علوم الحياة والأرض',
            content_url: 'images/kawkab_alouloum.jpeg',
            thumbnail_url: 'images/kawkab_alouloum.jpeg',
            created_at: '2026-03-20 09:15:00',
            is_published: 1
        }
    ];

    const INITIAL_ABSENCES = [
        {
            id: 1,
            teacher_id: 2,
            teacher_name: 'الأستاذة نادية برادة',
            teacher_phone: '0662223344',
            teacher_email: 'n.barrada@kawkab-ouloum.ma',
            teacher_subject: 'الفيزياء والكيمياء',
            absence_date: '2026-03-28',
            type: 'مرضي',
            reason: 'وعكة صحية طارئة ومراجعة المستشفى',
            document_path: '',
            is_urgent: true,
            status: 'مقبول',
            admin_notes: 'شفاك الله وعافاك، تم تكليف أستاذ معوض للحصص الصباحية.',
            created_at: '2026-03-26 08:30:00'
        }
    ];

    const INITIAL_ANNOUNCEMENTS = [
        {
            id: 1,
            teacher_id: 1,
            title: 'تغيير موعد حصة الدعم ليوم الخميس',
            type: 'normal',
            class_name: '1BAC-SC1',
            content: 'نحيط تلاميذ الأولى باك علماً بأن حصة الدعم في مادة الرياضيات قد تأجلت إلى الساعة الرابعة بعد الزوال.',
            created_at: '2026-03-22 11:00:00',
            file_path: ''
        },
        {
            id: 2,
            teacher_id: 1,
            title: 'استعداد للاختبار المحروس الثاني',
            type: 'urgent',
            class_name: 'الجميع',
            content: 'يرجى مراجعة التمارين التطبيقية تحسباً للامتحان المقرر نهاية الأسبوع القادم.',
            created_at: '2026-03-24 16:00:00',
            file_path: ''
        }
    ];

    const INITIAL_MESSAGES = [
        {
            id: 1,
            name: 'عبد الرحيم العلمي',
            email: 'a.alami@gmail.com',
            phone: '0661122334',
            subject: 'استفسار حول التسجيل بالسلك الثانوي',
            message: 'السلام عليكم، أرغب في معرفة شروط وآجال تسجيل ابني بالسنة الأولى بكالوريا علوم رياضية للموسم المقبل وشكراً.',
            created_at: '2026-03-25 10:15:00',
            status: 'unread'
        },
        {
            id: 2,
            name: 'فاطمة الزهراء البقالي',
            email: 'f.baqqali@yahoo.fr',
            phone: '0662233445',
            subject: 'طلب موعد مع الإدارة التربوية',
            message: 'تحية طيبة، أرجو تحديد موعد مع السيد المدير لمناقشة التوجيه المدرسي لابنتي. تقبلوا فائق التقدير.',
            created_at: '2026-03-24 14:20:00',
            status: 'read'
        }
    ];

    const INITIAL_COURSES = [
        {
            id: 1,
            title: 'برنامج التميز في الرياضيات والعلوم الدقيقة',
            category: 'علمي',
            level_tag: 'الثانوي التأهيلي',
            description: 'برنامج مكثف يشمل الجبر المتقدم، التحليل الرياضي، والميكانيك الكلاسيكية مع التركيز على التحضير للأقسام التحضيرية CPGE.',
            image_path: 'images/kawkab_alouloum.jpeg',
            is_published: 1
        },
        {
            id: 2,
            title: 'دورة اللغات والتواصل الدبلوماسي',
            category: 'لغات',
            level_tag: 'الإعدادي والثانوي',
            description: 'تطوير المهارات الخطابية والكتابية في اللغات الفرنسية والإنجليزية مع شهادات معتمدة دولياً (DELF / Cambridge).',
            image_path: 'images/kawkab_alouloum.jpeg',
            is_published: 1
        },
        {
            id: 3,
            title: 'نادي الروبوتات والذكاء الاصطناعي للناشئة',
            category: 'تكنولوجيا',
            level_tag: 'الابتدائي والإعدادي',
            description: 'ورشات تطبيقية لتعلم أساسيات البرمجة بلغة بايثون وتركيب الروبوتات الذكية والمشاركة في المسابقات الوطنية.',
            image_path: 'images/kawkab_alouloum.jpeg',
            is_published: 1
        }
    ];

    const INITIAL_GALLERY = [
        {
            id: 1,
            title: 'حفل التميز السنوي وتوزيع الجوائز 2026',
            category: 'ceremonies',
            description: 'تكريم المتفوقين في امتحانات البكالوريا والأنشطة الموازية بحضور أولياء الأمور والأطر التربوية.',
            image_path: 'images/kawkab_alouloum.jpeg',
            photo_date: '2026-02-28',
            is_published: 1
        },
        {
            id: 2,
            title: 'المعرض العلمي السنوي لمشاريع الروبوتيك',
            category: 'activities',
            description: 'ابتكارات تلاميذ المؤسسة في مجالات الطاقة المتجددة والأتمتة الذكية.',
            image_path: 'images/kawkab_alouloum.jpeg',
            photo_date: '2026-03-10',
            is_published: 1
        },
        {
            id: 3,
            title: 'رحلة استكشافية بيئية إلى جبال الأطلس',
            category: 'trips',
            description: 'دراسة التنوع البيولوجي والجيولوجي لنادي البيئة وعلوم الأرض.',
            image_path: 'images/kawkab_alouloum.jpeg',
            photo_date: '2026-03-18',
            is_published: 1
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
        if (!sessionStorage.getItem(DEMO_STUDENTS_KEY)) setStoredData(DEMO_STUDENTS_KEY, INITIAL_STUDENTS);
        if (!sessionStorage.getItem(DEMO_TEACHERS_KEY)) setStoredData(DEMO_TEACHERS_KEY, INITIAL_TEACHERS);
        if (!sessionStorage.getItem(DEMO_TEACHER_REQS_KEY)) setStoredData(DEMO_TEACHER_REQS_KEY, INITIAL_TEACHER_REQS);
        if (!sessionStorage.getItem(DEMO_TIMETABLE_KEY)) setStoredData(DEMO_TIMETABLE_KEY, INITIAL_TIMETABLE);
        if (!sessionStorage.getItem(DEMO_HOMEWORK_KEY)) setStoredData(DEMO_HOMEWORK_KEY, INITIAL_HOMEWORK);
        if (!sessionStorage.getItem(DEMO_LESSONS_KEY)) setStoredData(DEMO_LESSONS_KEY, INITIAL_LESSONS);
        if (!sessionStorage.getItem(DEMO_ABSENCES_KEY)) setStoredData(DEMO_ABSENCES_KEY, INITIAL_ABSENCES);
        if (!sessionStorage.getItem(DEMO_ANNOUNCEMENTS_KEY)) setStoredData(DEMO_ANNOUNCEMENTS_KEY, INITIAL_ANNOUNCEMENTS);
        if (!sessionStorage.getItem(DEMO_MESSAGES_KEY)) setStoredData(DEMO_MESSAGES_KEY, INITIAL_MESSAGES);
        if (!sessionStorage.getItem(DEMO_COURSES_KEY)) setStoredData(DEMO_COURSES_KEY, INITIAL_COURSES);
        if (!sessionStorage.getItem(DEMO_GALLERY_KEY)) setStoredData(DEMO_GALLERY_KEY, INITIAL_GALLERY);
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

    // Comprehensive Mock Router for Backend PHP Endpoints
    async function handleMockBackend(url, init) {
        const urlObj = new URL(url, window.location.href);
        const path = urlObj.pathname.toLowerCase();
        const queryAction = urlObj.searchParams.get('action') || '';
        const params = await extractParams(init);
        const action = String(params.action || queryAction || '').toLowerCase();

        // 1. Health check & DB Diagnostics
        if (path.includes('db_health.php') || path.includes('health_check.php') || path.includes('login_diagnostic.php')) {
            return createJsonResponse({
                success: true,
                message: 'تم الاتصال بوضع المعاينة السحابي (Vercel Demo).',
                data: {
                    database: 'edu_institution (Vercel Demo)',
                    total_tables: 12,
                    found_tables: ['students', 'teachers', 'grades', 'attendance', 'lessons', 'messages'],
                    missing_tables: []
                }
            });
        }

        // ==========================================
        // 2. TEACHER REGISTRATION & AUTH
        // ==========================================
        if (path.includes('teacher_register.php')) {
            const fullName = String(params.full_name || '').trim();
            const email = String(params.email || '').trim().toLowerCase();
            const phone = String(params.phone || '').trim();
            const subject = String(params.subject_name || '').trim();
            const code = String(params.code || '123456').trim();

            if (!fullName || !email || !subject) {
                return createJsonResponse({
                    success: false,
                    message: 'المرجو ملء جميع الحقول الإلزامية بدقة.'
                }, 400);
            }

            let pending = getStoredData(DEMO_TEACHER_REQS_KEY, INITIAL_TEACHER_REQS);
            let teachers = getStoredData(DEMO_TEACHERS_KEY, INITIAL_TEACHERS);

            const newTeacher = {
                id: Date.now(),
                full_name: fullName,
                email: email,
                phone: phone,
                subject_name: subject,
                code: code,
                specialty: 'مؤسسة Kawkab Al Ouloum',
                bio: 'أستاذ متميز في مادة ' + subject + '، انضم حديثاً للمؤسسة.',
                rating: '5.0',
                status: 'active',
                avatar: '',
                created_at: new Date().toISOString().slice(0, 10)
            };

            // Add to pending for admin inspection
            pending.unshift({
                id: newTeacher.id,
                full_name: fullName,
                email: email,
                phone: phone,
                subject_name: subject,
                code: code,
                status: 'approved',
                created_at: new Date().toISOString().slice(0, 19).replace('T', ' ')
            });

            // Also add directly to active teachers so they can log in immediately!
            teachers.unshift(newTeacher);

            setStoredData(DEMO_TEACHER_REQS_KEY, pending);
            setStoredData(DEMO_TEACHERS_KEY, teachers);
            activateDemoMode();

            return createJsonResponse({
                success: true,
                message: 'تم إرسال طلب تسجيلك بنجاح! تم تفعيل حسابك ويمكنك تسجيل الدخول الآن بالكود السري الذي اخترته.'
            });
        }

        if (path.includes('teacher_auth.php')) {
            if (action === 'logout') {
                sessionStorage.removeItem(DEMO_TEACHER_USER_KEY);
                return createJsonResponse({
                    success: true,
                    message: 'تم تسجيل الخروج بنجاح.'
                });
            }

            const email = String(params.email || '').trim().toLowerCase();
            const code = String(params.code || '').trim();

            let teachers = getStoredData(DEMO_TEACHERS_KEY, INITIAL_TEACHERS);
            let matchedTeacher = teachers.find(t => 
                (t.email && t.email.toLowerCase() === email) ||
                (t.phone && t.phone === email)
            );

            // If not found in active, check pending
            if (!matchedTeacher) {
                let pending = getStoredData(DEMO_TEACHER_REQS_KEY, INITIAL_TEACHER_REQS);
                matchedTeacher = pending.find(r => 
                    (r.email && r.email.toLowerCase() === email) ||
                    (r.phone && r.phone === email)
                );
            }

            // Universal fallback for demo: if email contains teacher or demo
            if (!matchedTeacher) {
                if (email === 'teacher@kawkab-ouloum.ma' || email.includes('teacher') || email.includes('gmail') || isDemoMode()) {
                    matchedTeacher = {
                        id: 999,
                        full_name: fullNameFromEmail(email) || 'الأستاذ عبد الإله العابد',
                        email: email || 'teacher@kawkab-ouloum.ma',
                        phone: '0650032824',
                        subject_name: 'الرياضيات',
                        specialty: 'مؤسسة Kawkab Al Ouloum',
                        bio: 'أستاذ متميز في مجاله.',
                        rating: '5.0',
                        avatar: '',
                        code: code || '123456'
                    };
                }
            }

            if (!matchedTeacher) {
                return createJsonResponse({
                    success: false,
                    message: 'البريد الإلكتروني أو الكود السري غير صحيح.'
                }, 401);
            }

            sessionStorage.setItem(DEMO_TEACHER_USER_KEY, JSON.stringify(matchedTeacher));
            activateDemoMode();

            return createJsonResponse({
                success: true,
                message: 'تم تسجيل الدخول بنجاح! مرحباً بك.',
                data: {
                    user: matchedTeacher
                }
            });
        }

        // 3. Teacher Profile
        if (path.includes('teacher_profile.php')) {
            let currentTeacher = getStoredData(DEMO_TEACHER_USER_KEY, null);
            if (!currentTeacher) {
                const teachers = getStoredData(DEMO_TEACHERS_KEY, INITIAL_TEACHERS);
                currentTeacher = teachers[0];
            }

            if (action === 'get') {
                return createJsonResponse({
                    success: true,
                    data: { teacher: currentTeacher }
                });
            }

            if (action === 'update') {
                currentTeacher.full_name = params.full_name || currentTeacher.full_name;
                currentTeacher.phone = params.phone || currentTeacher.phone;
                currentTeacher.subject_name = params.subject_name || currentTeacher.subject_name;
                currentTeacher.bio = params.bio || currentTeacher.bio;

                setStoredData(DEMO_TEACHER_USER_KEY, currentTeacher);
                return createJsonResponse({
                    success: true,
                    message: 'تم تحديث البيانات الشخصية بنجاح.',
                    data: { teacher: currentTeacher }
                });
            }

            if (action === 'change_code') {
                return createJsonResponse({
                    success: true,
                    message: 'تم تغيير الكود السري بنجاح.'
                });
            }

            if (action === 'upload_avatar') {
                return createJsonResponse({
                    success: true,
                    message: 'تم رفع الصورة الشخصية بنجاح.',
                    data: { avatar_url: 'images/kawkab_alouloum.jpeg' }
                });
            }
        }

        // 4. Teacher Timetable
        if (path.includes('teacher_timetable.php')) {
            let timetables = getStoredData(DEMO_TIMETABLE_KEY, INITIAL_TIMETABLE);
            const currentTeacher = getStoredData(DEMO_TEACHER_USER_KEY, { id: 1 });

            if (action === 'list') {
                const filtered = timetables.filter(t => !t.teacher_id || t.teacher_id === currentTeacher.id || currentTeacher.id === 999);
                return createJsonResponse({
                    success: true,
                    data: { items: filtered.length > 0 ? filtered : timetables.slice(0, 3) }
                });
            }

            if (action === 'save') {
                const newId = Date.now();
                timetables.push({
                    id: newId,
                    teacher_id: currentTeacher.id,
                    day_of_week: Number(params.day_of_week || 1),
                    start_time: params.start_time || '08:30:00',
                    end_time: params.end_time || '10:30:00',
                    class_name: params.class_name || '1BAC-SC1',
                    subject_name: params.subject_name || currentTeacher.subject_name || 'الرياضيات',
                    room: params.room || 'قاعة 1',
                    notes: params.notes || ''
                });
                setStoredData(DEMO_TIMETABLE_KEY, timetables);
                return createJsonResponse({
                    success: true,
                    message: 'تمت إضافة الحصة إلى جدول الأستاذ بنجاح.'
                });
            }

            if (action === 'delete') {
                const id = Number(params.id || 0);
                timetables = timetables.filter(t => t.id !== id);
                setStoredData(DEMO_TIMETABLE_KEY, timetables);
                return createJsonResponse({
                    success: true,
                    message: 'تم حذف الحصة بنجاح.'
                });
            }
        }

        // 5. Teacher Absence
        if (path.includes('teacher_absence.php')) {
            let absences = getStoredData(DEMO_ABSENCES_KEY, INITIAL_ABSENCES);
            const currentTeacher = getStoredData(DEMO_TEACHER_USER_KEY, { id: 1, full_name: 'أستاذ' });

            if (action === 'list') {
                return createJsonResponse({
                    success: true,
                    data: { absences: absences }
                });
            }

            if (action === 'submit') {
                const newAbs = {
                    id: Date.now(),
                    teacher_id: currentTeacher.id,
                    teacher_name: currentTeacher.full_name,
                    teacher_phone: currentTeacher.phone || '0650032824',
                    teacher_email: currentTeacher.email || '',
                    teacher_subject: currentTeacher.subject_name || 'عام',
                    absence_date: params.absence_date || new Date().toISOString().slice(0, 10),
                    type: params.type || 'مرضي',
                    reason: params.reason || 'إشعار غياب مبرر',
                    document_path: '',
                    is_urgent: Boolean(params.is_urgent),
                    status: 'قيد المراجعة',
                    admin_notes: '',
                    created_at: new Date().toISOString().slice(0, 19).replace('T', ' ')
                };
                absences.unshift(newAbs);
                setStoredData(DEMO_ABSENCES_KEY, absences);
                return createJsonResponse({
                    success: true,
                    message: 'تم إرسال إشعار الغياب بنجاح للإدارة وسيقوم المدير بمراجعته.'
                });
            }
        }

        // 6. Teacher Homework
        if (path.includes('teacher_homework.php')) {
            let homework = getStoredData(DEMO_HOMEWORK_KEY, INITIAL_HOMEWORK);
            const currentTeacher = getStoredData(DEMO_TEACHER_USER_KEY, { id: 1 });

            if (action === 'list') {
                return createJsonResponse({
                    success: true,
                    data: { homework: homework }
                });
            }

            if (action === 'create') {
                homework.unshift({
                    id: Date.now(),
                    teacher_id: currentTeacher.id,
                    title: params.title || 'واجب منزلي جديد',
                    class_name: params.class_name || '1BAC-SC1',
                    subject: params.subject || currentTeacher.subject_name || 'الرياضيات',
                    description: params.description || '',
                    due_date: params.due_date || new Date().toISOString().slice(0, 10),
                    has_file: false,
                    file_path: ''
                });
                setStoredData(DEMO_HOMEWORK_KEY, homework);
                return createJsonResponse({
                    success: true,
                    message: 'تم نشر الواجب المنزلي بنجاح.'
                });
            }
        }

        // 7. Teacher Lessons
        if (path.includes('teacher_lessons.php')) {
            let lessons = getStoredData(DEMO_LESSONS_KEY, INITIAL_LESSONS);
            const currentTeacher = getStoredData(DEMO_TEACHER_USER_KEY, { id: 1 });

            if (action === 'list') {
                return createJsonResponse({
                    success: true,
                    data: { lessons: lessons }
                });
            }

            if (action === 'create') {
                lessons.unshift({
                    id: Date.now(),
                    teacher_id: currentTeacher.id,
                    title: params.title || 'درس تعليمي',
                    lesson_type: params.lesson_type || 'pdf',
                    level: params.level || '1BAC-SC1',
                    subject_name: params.subject_name || currentTeacher.subject_name || 'الرياضيات',
                    content_url: params.content_url || 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf',
                    thumbnail_url: 'images/kawkab_alouloum.jpeg',
                    created_at: new Date().toISOString().slice(0, 19).replace('T', ' '),
                    is_published: 1
                });
                setStoredData(DEMO_LESSONS_KEY, lessons);
                return createJsonResponse({
                    success: true,
                    message: 'تم نشر الدرس بنجاح للطلاب.'
                });
            }

            if (action === 'delete') {
                const id = Number(params.lesson_id || 0);
                lessons = lessons.filter(l => l.id !== id);
                setStoredData(DEMO_LESSONS_KEY, lessons);
                return createJsonResponse({
                    success: true,
                    message: 'تم حذف الدرس بنجاح.'
                });
            }
        }

        // 8. Teacher Announcements
        if (path.includes('teacher_announcements.php')) {
            let announcements = getStoredData(DEMO_ANNOUNCEMENTS_KEY, INITIAL_ANNOUNCEMENTS);
            const currentTeacher = getStoredData(DEMO_TEACHER_USER_KEY, { id: 1 });

            if (action === 'list') {
                return createJsonResponse({
                    success: true,
                    data: { announcements: announcements }
                });
            }

            if (action === 'create') {
                announcements.unshift({
                    id: Date.now(),
                    teacher_id: currentTeacher.id,
                    title: params.title || 'إعلان للأقسام',
                    type: params.type || 'normal',
                    class_name: params.class_name || 'الجميع',
                    content: params.content || '',
                    created_at: new Date().toISOString().slice(0, 19).replace('T', ' '),
                    file_path: ''
                });
                setStoredData(DEMO_ANNOUNCEMENTS_KEY, announcements);
                return createJsonResponse({
                    success: true,
                    message: 'تم نشر الإعلان بنجاح.'
                });
            }

            if (action === 'delete') {
                const id = Number(params.announcement_id || 0);
                announcements = announcements.filter(a => a.id !== id);
                setStoredData(DEMO_ANNOUNCEMENTS_KEY, announcements);
                return createJsonResponse({
                    success: true,
                    message: 'تم حذف الإعلان بنجاح.'
                });
            }
        }

        // ==========================================
        // 9. PUBLIC TEACHERS DIRECTORY (teachers.html)
        // ==========================================
        if (path.includes('public_teachers.php')) {
            const teachers = getStoredData(DEMO_TEACHERS_KEY, INITIAL_TEACHERS);
            const timetables = getStoredData(DEMO_TIMETABLE_KEY, INITIAL_TIMETABLE);
            const homework = getStoredData(DEMO_HOMEWORK_KEY, INITIAL_HOMEWORK);
            const announcements = getStoredData(DEMO_ANNOUNCEMENTS_KEY, INITIAL_ANNOUNCEMENTS);

            if (action === 'list' || !action) {
                return createJsonResponse({
                    success: true,
                    data: { teachers: teachers }
                });
            }

            if (action === 'timetable') {
                const tId = Number(urlObj.searchParams.get('teacher_id') || params.teacher_id || 1);
                const teacherTt = timetables.filter(t => !t.teacher_id || t.teacher_id === tId);
                return createJsonResponse({
                    success: true,
                    data: { timetable: teacherTt.length > 0 ? teacherTt : timetables.slice(0, 3) }
                });
            }

            if (action === 'homework') {
                const tId = Number(urlObj.searchParams.get('teacher_id') || params.teacher_id || 1);
                const teacherHw = homework.filter(h => !h.teacher_id || h.teacher_id === tId);
                return createJsonResponse({
                    success: true,
                    data: { homework: teacherHw.length > 0 ? teacherHw : homework.slice(0, 2) }
                });
            }

            if (action === 'announcements') {
                return createJsonResponse({
                    success: true,
                    data: { announcements: announcements }
                });
            }
        }

        // ==========================================
        // 10. STUDENT / PARENT PORTAL (login.html)
        // ==========================================
        if (path.includes('portal_auth.php')) {
            const studentCode = String(params.student_code || '').trim().toUpperCase();
            const birthDate = String(params.birth_date || '').trim();

            const students = getStoredData(DEMO_STUDENTS_KEY, INITIAL_STUDENTS);
            let student = students.find(s => 
                s.student_code.toUpperCase() === studentCode
            );

            // Fallback for test button G132026001 or STU2026001
            if (!student) {
                student = students[0];
            }

            const fullName = `${student.first_name} ${student.last_name}`;

            const grades = [
                { subject_name: 'الرياضيات', continuous_score: '17.50', exam_score: '18.00', coefficient: '7.00', average: '17.80' },
                { subject_name: 'الفيزياء والكيمياء', continuous_score: '16.00', exam_score: '17.00', coefficient: '7.00', average: '16.60' },
                { subject_name: 'علوم الحياة والأرض', continuous_score: '15.50', exam_score: '16.00', coefficient: '5.00', average: '15.80' },
                { subject_name: 'اللغة الفرنسية', continuous_score: '14.50', exam_score: '15.00', coefficient: '4.00', average: '14.80' },
                { subject_name: 'اللغة العربية', continuous_score: '16.50', exam_score: '16.00', coefficient: '2.00', average: '16.20' },
                { subject_name: 'اللغة الإنجليزية', continuous_score: '18.00', exam_score: '18.50', coefficient: '2.00', average: '18.30' },
                { subject_name: 'الفلسفة', continuous_score: '14.00', exam_score: '14.50', coefficient: '2.00', average: '14.30' },
                { subject_name: 'التربية الإسلامية', continuous_score: '18.50', exam_score: '19.00', coefficient: '2.00', average: '18.80' }
            ];

            const attendanceRecords = [
                { absence_date: '2026-03-10', session_label: '08:30 - 10:30', absence_subject: 'الرياضيات', justified: true, notes: 'شهادة طبية مقبولة' },
                { absence_date: '2026-02-18', session_label: '14:30 - 16:30', absence_subject: 'اللغة الفرنسية', justified: true, notes: 'إذن كتابي من ولي الأمر' }
            ];

            const notifications = [
                { id: 1, title: 'استدعاء لاجتماع أولياء الأمور', message: 'نحيطكم علماً بأنه سينعقد اجتماع أولياء الأمور يوم السبت المقبل ابتداءً من الساعة العاشرة صباحاً.', created_at: '2026-03-20 10:00:00' },
                { id: 2, title: 'تهنئة بالنتائج المتميزة', message: `نهنئ التلميذ ${fullName} على نتائجه المشرفة بالدورة الأولى ونتمنى له دوام التألق.`, created_at: '2026-02-05 14:30:00' }
            ];

            return createJsonResponse({
                success: true,
                message: 'تم تسجيل الدخول بنجاح.',
                data: {
                    student: {
                        id: student.id,
                        student_code: student.student_code,
                        full_name: fullName,
                        first_name: student.first_name,
                        last_name: student.last_name,
                        class_name: student.class_name,
                        level: student.level,
                        birth_date: student.birth_date,
                        guardian_name: student.guardian_name,
                        guardian_phone: student.guardian_phone
                    },
                    grades: grades,
                    summary: {
                        general_average: '16.78',
                        mention: 'حسن جداً (Très Bien)',
                        class_rank: '2 / 34'
                    },
                    attendance: {
                        stats: {
                            total_absences: 2,
                            justified_absences: 2,
                            unjustified_absences: 0
                        },
                        records: attendanceRecords
                    },
                    notifications: notifications
                }
            });
        }

        if (path.includes('portal_announcements.php')) {
            const announcements = getStoredData(DEMO_ANNOUNCEMENTS_KEY, INITIAL_ANNOUNCEMENTS);
            return createJsonResponse({
                success: true,
                data: { announcements: announcements }
            });
        }

        // ==========================================
        // 11. STUDENT REGISTRATION (inscription.html)
        // ==========================================
        if (path.includes('student_register.php')) {
            let students = getStoredData(DEMO_STUDENTS_KEY, INITIAL_STUDENTS);
            const newId = students.length > 0 ? Math.max(...students.map(s => Number(s.id) || 0)) + 1 : 1;
            const generatedCode = params.student_code || ('STU2026' + String(newId).padStart(3, '0'));

            const newStudent = {
                id: newId,
                student_code: generatedCode,
                first_name: params.first_name || 'تلميذ',
                last_name: params.last_name || 'جديد',
                birth_date: params.birth_date || '2010-01-01',
                gender: params.gender || 'male',
                class_name: params.class_name || '1AC-1',
                level: params.level || 'Collège',
                email: params.email || '',
                phone: params.phone || '',
                address: params.address || '',
                guardian_name: params.guardian_name || 'ولي الأمر',
                guardian_phone: params.guardian_phone || '0600000000',
                guardian_email: params.guardian_email || '',
                registration_status: 'pending',
                is_request: 'yes'
            };

            students.unshift(newStudent);
            setStoredData(DEMO_STUDENTS_KEY, students);
            activateDemoMode();

            return createJsonResponse({
                success: true,
                message: `تم استلام طلب التسجيل بنجاح! كود التلميذ هو ${generatedCode} وهو قيد المراجعة الإدارية.`,
                data: {
                    student_code: generatedCode
                }
            });
        }

        // ==========================================
        // 12. TRANSCRIPT & ATTENDANCE LOOKUPS
        // ==========================================
        if (path.includes('transcript_lookup.php')) {
            const studentCode = String(params.student_code || '').trim().toUpperCase();
            const students = getStoredData(DEMO_STUDENTS_KEY, INITIAL_STUDENTS);
            const student = students.find(s => s.student_code.toUpperCase() === studentCode) || students[0];

            return createJsonResponse({
                success: true,
                message: 'تم استخراج بيان النقط بنجاح.',
                data: {
                    student: {
                        full_name: `${student.first_name} ${student.last_name}`,
                        student_code: student.student_code,
                        class_name: student.class_name,
                        level: student.level
                    },
                    grades: [
                        { subject_name: 'الرياضيات', continuous_score: '17.50', exam_score: '18.00', coefficient: '7.00', average: '17.80' },
                        { subject_name: 'الفيزياء والكيمياء', continuous_score: '16.00', exam_score: '17.00', coefficient: '7.00', average: '16.60' },
                        { subject_name: 'علوم الحياة والأرض', continuous_score: '15.50', exam_score: '16.00', coefficient: '5.00', average: '15.80' },
                        { subject_name: 'اللغة الفرنسية', continuous_score: '14.50', exam_score: '15.00', coefficient: '4.00', average: '14.80' },
                        { subject_name: 'اللغة العربية', continuous_score: '16.50', exam_score: '16.00', coefficient: '2.00', average: '16.20' },
                        { subject_name: 'اللغة الإنجليزية', continuous_score: '18.00', exam_score: '18.50', coefficient: '2.00', average: '18.30' }
                    ],
                    summary: {
                        general_average: '16.78',
                        mention: 'حسن جداً (Très Bien)'
                    },
                    notifications: [
                        { id: 1, title: 'تهنئة بالنتائج', message: 'معدل عام مشرف ومستوى ممتاز.', created_at: '2026-03-20' }
                    ]
                }
            });
        }

        if (path.includes('parent_attendance.php')) {
            const studentCode = String(params.student_code || '').trim().toUpperCase();
            const students = getStoredData(DEMO_STUDENTS_KEY, INITIAL_STUDENTS);
            const student = students.find(s => s.student_code.toUpperCase() === studentCode) || students[0];

            return createJsonResponse({
                success: true,
                message: 'تم جلب سجل الغياب بنجاح.',
                data: {
                    student: {
                        full_name: `${student.first_name} ${student.last_name}`,
                        student_code: student.student_code,
                        class_name: student.class_name,
                        guardian_name: student.guardian_name
                    },
                    stats: {
                        total_absences: 2,
                        justified_absences: 2,
                        unjustified_absences: 0
                    },
                    records: [
                        { absence_date: '2026-03-10', session_label: '08:30 - 10:30', absence_subject: 'الرياضيات', justified: true, notes: 'شهادة طبية مقبولة' },
                        { absence_date: '2026-02-18', session_label: '14:30 - 16:30', absence_subject: 'اللغة الفرنسية', justified: true, notes: 'إذن ولي الأمر' }
                    ],
                    notifications: []
                }
            });
        }

        // ==========================================
        // 13. CONTACT & NEWSLETTER
        // ==========================================
        if (path.includes('contact_process.php')) {
            let messages = getStoredData(DEMO_MESSAGES_KEY, INITIAL_MESSAGES);
            messages.unshift({
                id: Date.now(),
                name: params.name || 'زائر',
                email: params.email || '',
                phone: params.phone || '',
                subject: params.subject || 'استفسار عام',
                message: params.message || '',
                created_at: new Date().toISOString().slice(0, 19).replace('T', ' '),
                status: 'unread'
            });
            setStoredData(DEMO_MESSAGES_KEY, messages);

            return createJsonResponse({
                success: true,
                message: 'تم إرسال رسالتك بنجاح! شكراً لتواصلك مع إدارة المؤسسة، سنرد عليك في أقرب وقت.'
            });
        }

        if (path.includes('subscribe_newletter.php')) {
            return createJsonResponse({
                success: true,
                message: 'تم الاشتراك في النشرة الإخبارية للمؤسسة بنجاح.'
            });
        }

        // ==========================================
        // 14. COURSES, GALLERY, LESSONS & BLOG
        // ==========================================
        if (path.includes('courses_api.php') || path.includes('get_courses.php')) {
            const courses = getStoredData(DEMO_COURSES_KEY, INITIAL_COURSES);
            return createJsonResponse({
                success: true,
                courses: courses,
                data: courses
            });
        }

        if (path.includes('gallery_api.php')) {
            const photos = getStoredData(DEMO_GALLERY_KEY, INITIAL_GALLERY);
            return createJsonResponse({
                success: true,
                count: photos.length,
                data: photos
            });
        }

        if (path.includes('lessons_list.php')) {
            const lessons = getStoredData(DEMO_LESSONS_KEY, INITIAL_LESSONS);
            return createJsonResponse({
                success: true,
                message: 'تم جلب الدروس بنجاح.',
                data: { lessons: lessons }
            });
        }

        if (path.includes('blog_api.php')) {
            return createJsonResponse({
                success: true,
                articles: [
                    {
                        id: 1,
                        title: 'انطلاق الموسم الدراسي بحلة جديدة وبرامج تعليمية رائدة',
                        type: 'news',
                        content: 'يسر مؤسسة كوكب العلوم الترحيب بكافة التلاميذ والأطر التربوية للموسم الدراسي الجديد.',
                        created_at: '2026-03-01'
                    }
                ]
            });
        }

        if (path.includes('forgot_password.php') || path.includes('reset_password.php')) {
            return createJsonResponse({
                success: true,
                message: 'تم إرسال رابط استعادة الحساب بنجاح.'
            });
        }

        // ==========================================
        // 15. ADMIN PORTAL (admin_*.php)
        // ==========================================
        if (path.includes('admin_auth.php')) {
            const role = String(params.role || 'director').toLowerCase();
            const email = String(params.email || '').trim().toLowerCase();
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

        if (path.includes('admin_students.php')) {
            const currentUser = getStoredData(DEMO_USER_KEY, { role: 'director', full_name: 'المدير' });
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
                            can_manage_students: true,
                            can_view_reports: true
                        }
                    }
                });
            }

            if (action === 'save') {
                const sId = Number(params.student_id || 0);
                if (sId > 0) {
                    const idx = students.findIndex(s => Number(s.id) === sId);
                    if (idx !== -1) {
                        students[idx] = Object.assign({}, students[idx], params);
                    }
                } else {
                    const newId = students.length > 0 ? Math.max(...students.map(s => Number(s.id) || 0)) + 1 : 1;
                    students.unshift(Object.assign({
                        id: newId,
                        student_code: params.student_code || ('STU2026' + String(newId).padStart(3, '0')),
                        registration_status: 'approved',
                        is_request: 'no'
                    }, params));
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

        if (path.includes('admin_records.php')) {
            if (action === 'get_grades') {
                return createJsonResponse({
                    success: true,
                    data: [
                        { id: 1, subject_name: 'الرياضيات', continuous_score: '17.00', exam_score: '16.50', coefficient: '7.00' },
                        { id: 2, subject_name: 'الفيزياء والكيمياء', continuous_score: '15.50', exam_score: '16.00', coefficient: '5.00' }
                    ]
                });
            }
            if (action === 'add_grade' || action === 'update_grade') {
                return createJsonResponse({ success: true, message: 'تم حفظ النقطة بنجاح.' });
            }
            if (action === 'get_attendance') {
                return createJsonResponse({
                    success: true,
                    data: [
                        { id: 1, absence_date: '2026-03-24', session_label: '08:30 - 10:30', absence_subject: 'الرياضيات', justified: 1, notes: 'شهادة طبية' }
                    ]
                });
            }
            if (action === 'add_attendance') {
                return createJsonResponse({ success: true, message: 'تم تسجيل الغياب بنجاح.' });
            }
        }

        if (path.includes('admin_lessons.php')) {
            let lessons = getStoredData(DEMO_LESSONS_KEY, INITIAL_LESSONS);
            if (action === 'list') {
                return createJsonResponse({ success: true, data: { lessons: lessons } });
            }
            if (action === 'save') {
                lessons.unshift(Object.assign({ id: Date.now() }, params));
                setStoredData(DEMO_LESSONS_KEY, lessons);
                return createJsonResponse({ success: true, message: 'تم حفظ الدرس بنجاح.' });
            }
            if (action === 'delete') {
                lessons = lessons.filter(l => l.id !== Number(params.id));
                setStoredData(DEMO_LESSONS_KEY, lessons);
                return createJsonResponse({ success: true, message: 'تم حذف الدرس بنجاح.' });
            }
        }

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
                        rating: '5.0',
                        created_at: new Date().toISOString().slice(0, 10)
                    });
                    setStoredData(DEMO_TEACHERS_KEY, teachers);
                    setStoredData(DEMO_TEACHER_REQS_KEY, pending);
                }
                return createJsonResponse({
                    success: true,
                    message: 'تمت الموافقة على طلب الأستاذ بنجاح.'
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

        if (path.includes('admin_teacher_absences.php')) {
            let absences = getStoredData(DEMO_ABSENCES_KEY, INITIAL_ABSENCES);
            if (action === 'list' || !action) {
                return createJsonResponse({
                    success: true,
                    data: { absences: absences }
                });
            }
            if (action === 'update_status') {
                const aId = Number(params.id || 0);
                const idx = absences.findIndex(a => Number(a.id) === aId);
                if (idx !== -1) {
                    absences[idx].status = params.status || 'مقبول';
                    absences[idx].admin_notes = params.admin_notes || '';
                    setStoredData(DEMO_ABSENCES_KEY, absences);
                }
                return createJsonResponse({
                    success: true,
                    message: 'تم البت في طلب الغياب بنجاح.'
                });
            }
        }

        if (path.includes('promotion_annuelle.php')) {
            const students = getStoredData(DEMO_STUDENTS_KEY, INITIAL_STUDENTS);
            if (action === 'preview') {
                const previewList = students.map(s => ({
                    id: s.id,
                    student_code: s.student_code,
                    full_name: `${s.first_name} ${s.last_name}`,
                    current_class: s.class_name,
                    current_level: s.level,
                    annual_average: '16.50',
                    next_class: '2BAC-SM',
                    next_level: 'Lycée',
                    decision: 'admitted'
                }));
                return createJsonResponse({
                    success: true,
                    data: {
                        candidates: previewList,
                        total_candidates: previewList.length,
                        admitted_count: previewList.length,
                        repeating_count: 0
                    }
                });
            }
            if (action === 'promote') {
                return createJsonResponse({
                    success: true,
                    message: 'تمت عملية إعادة التسجيل والترقية السنوية بنجاح.'
                });
            }
        }

        if (path.includes('admin_messages.php')) {
            let messages = getStoredData(DEMO_MESSAGES_KEY, INITIAL_MESSAGES);
            if (action === 'list' || !action) {
                return createJsonResponse({
                    success: true,
                    data: { messages: messages }
                });
            }
            if (action === 'delete') {
                const id = Number(params.id || 0);
                messages = messages.filter(m => m.id !== id);
                setStoredData(DEMO_MESSAGES_KEY, messages);
                return createJsonResponse({ success: true, message: 'تم حذف الرسالة بنجاح.' });
            }
        }

        if (path.includes('notifications_api.php')) {
            return createJsonResponse({
                success: true,
                data: { notifications: [] }
            });
        }

        if (path.includes('logout.php')) {
            sessionStorage.removeItem(DEMO_USER_KEY);
            sessionStorage.removeItem(DEMO_TEACHER_USER_KEY);
            return createJsonResponse({ success: true, message: 'تم تسجيل الخروج بنجاح.' });
        }

        // Generic fallback for any unhandled backend PHP file
        return createJsonResponse({
            success: true,
            message: 'تمت العملية بنجاح (وضع المعاينة).',
            data: {}
        });
    }

    function fullNameFromEmail(email) {
        if (!email) return '';
        const namePart = email.split('@')[0].replace(/[._-]/g, ' ');
        return namePart.charAt(0).toUpperCase() + namePart.slice(1);
    }

    // ==========================================
    // Fetch Interception
    // ==========================================
    const originalFetch = window.fetch;

    window.fetch = async function (resource, init) {
        const url = typeof resource === 'string' ? resource : (resource && resource.url ? resource.url : '');
        const isBackendPhp = url.toLowerCase().includes('backend/') && url.toLowerCase().includes('.php');

        if (isBackendPhp) {
            // If already in demo mode or on static host (Vercel) -> directly mock
            if (isDemoMode()) {
                console.log('[SiteDemo] Intercepting fetch (Demo Active):', url);
                return await handleMockBackend(url, init);
            }

            // Otherwise, attempt real network fetch first
            try {
                const response = await originalFetch(resource, init);
                // If static host returns 404 or 405 (Method Not Allowed) on PHP files, switch to mock
                if (!response.ok && (response.status === 404 || response.status === 405 || response.status === 500)) {
                    console.warn('[SiteDemo] Server returned error, switching to mock:', response.status);
                    activateDemoMode();
                    return await handleMockBackend(url, init);
                }
                return response;
            } catch (networkError) {
                console.warn('[SiteDemo] Network error, activating mock engine:', networkError);
                activateDemoMode();
                return await handleMockBackend(url, init);
            }
        }

        return originalFetch(resource, init);
    };

    // ==========================================
    // UI Badges & Quick Login Enhancements
    // ==========================================
    function updateDemoBadges() {
        const notice = document.getElementById('vercelNotice');
        if (notice) notice.style.display = 'block';

        const badge = document.getElementById('demoModeBadge');
        if (badge) badge.style.display = 'inline-flex';

        const connStatus = document.getElementById('dbConnectionStatus');
        if (connStatus && isDemoMode()) {
            connStatus.className = 'status-pill is-success';
            connStatus.innerHTML = '<i class="fa-solid fa-cloud-check"></i> وضع المعاينة (Vercel Demo) شغال';
        }
    }

    function setupPageHelpers() {
        // Teacher Login Helpers
        const teacherLoginForm = document.getElementById('teacherLoginForm');
        const teacherLoginPanel = document.getElementById('teacherLoginPanel');
        if (teacherLoginPanel && !document.getElementById('teacherDemoQuickLogin')) {
            const demoBox = document.createElement('div');
            demoBox.id = 'teacherDemoQuickLogin';
            demoBox.style.cssText = 'margin-top: 1rem; padding: 0.85rem; background: #eef2ff; border: 1px solid #c7d2fe; border-radius: 12px; text-align: center;';
            demoBox.innerHTML = `
                <div style="font-size: 0.88rem; font-weight: 700; color: #3730a3; margin-bottom: 0.5rem;">
                    <i class="fa-solid fa-wand-magic-sparkles"></i> تجربة سريعة على Vercel:
                </div>
                <button type="button" id="btnFillDemoTeacher" class="btn btn-secondary" style="font-size: 0.85rem; padding: 0.45rem 1.1rem; border-radius: 20px; cursor: pointer;">
                    <i class="fa-solid fa-chalkboard-user"></i> دخول أستاذ تجريبي بنقرة واحدة
                </button>
            `;
            teacherLoginPanel.appendChild(demoBox);

            const btnFill = document.getElementById('btnFillDemoTeacher');
            if (btnFill) {
                btnFill.addEventListener('click', (e) => {
                    e.preventDefault();
                    activateDemoMode();
                    const emailInput = document.getElementById('teacher_email');
                    const codeInput = document.getElementById('teacher_code');
                    if (emailInput) emailInput.value = 'teacher@kawkab-ouloum.ma';
                    if (codeInput) codeInput.value = '123456';
                    if (teacherLoginForm) {
                        teacherLoginForm.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
                    }
                });
            }
        }

        // Admin Login Quick Buttons
        const btnDirector = document.getElementById('demoLoginDirector');
        const btnSecretary = document.getElementById('demoLoginSecretary');
        const adminRole = document.getElementById('admin_role');
        const adminEmail = document.getElementById('admin_email');
        const adminCode = document.getElementById('admin_code');
        const adminLoginForm = document.getElementById('adminLoginForm');

        if (btnDirector) {
            btnDirector.addEventListener('click', (e) => {
                e.preventDefault();
                activateDemoMode();
                if (adminRole) adminRole.value = 'director';
                if (adminEmail) adminEmail.value = 'admin@kawkab-ouloum.ma';
                if (adminCode) adminCode.value = 'Admin@2026';
                if (adminLoginForm) {
                    adminLoginForm.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
                }
            });
        }

        if (btnSecretary) {
            btnSecretary.addEventListener('click', (e) => {
                e.preventDefault();
                activateDemoMode();
                if (adminRole) adminRole.value = 'secretary';
                if (adminEmail) adminEmail.value = 'secretary@kawkab-ouloum.ma';
                if (adminCode) adminCode.value = 'Secretary@2026';
                if (adminLoginForm) {
                    adminLoginForm.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
                }
            });
        }

        if (isDemoMode()) {
            updateDemoBadges();
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', setupPageHelpers);
    } else {
        setupPageHelpers();
    }

    // Expose global controller
    window.SiteDemo = {
        isDemoMode: isDemoMode,
        activate: activateDemoMode,
        resetData: function () {
            sessionStorage.clear();
            initDemoStorage();
            window.location.reload();
        }
    };

    window.AdminDemo = window.SiteDemo;
})();
