document.addEventListener("DOMContentLoaded", () => {
    const loginPanel = document.getElementById("adminLoginPanel");
    const dashboardPanel = document.getElementById("adminDashboard");
    const loginForms = Array.from(document.querySelectorAll(".role-login-form"));
    const legacyLoginForm = document.getElementById("adminLoginForm");
    if (legacyLoginForm && !loginForms.includes(legacyLoginForm)) {
        loginForms.push(legacyLoginForm);
    }
    const directorLoginFeedback = document.getElementById("directorLoginFeedback");
    const secretaryLoginFeedback = document.getElementById("secretaryLoginFeedback");
    const legacyLoginFeedback = document.getElementById("adminLoginFeedback");
    const loginRoleInput = document.getElementById("admin_role");
    const loginEmailInput = document.getElementById("admin_email");
    const loginCodeInput = document.getElementById("admin_code");

    const dbConnectionStatus = document.getElementById("dbConnectionStatus");
    const dbConnectionDetails = document.getElementById("dbConnectionDetails");

    const identityBox = document.getElementById("adminIdentity");
    const logoutBtn = document.getElementById("adminLogoutBtn");
    const dashboardTitle = document.getElementById("dashboardTitle");
    const dashboardHelper = document.getElementById("dashboardHelper");

    const studentForm = document.getElementById("studentAdminForm");
    const studentSubmitBtn = document.getElementById("studentSubmitBtn");
    const studentResetBtn = document.getElementById("studentResetBtn");
    const studentFeedback = document.getElementById("studentAdminFeedback");
    const studentTableFeedback = document.getElementById("studentTableFeedback");
    const studentsTableBody = document.getElementById("studentsTableBody");
    const levelFilter = document.getElementById("levelFilter");
    const studentsSearch = document.getElementById("studentsSearch");

    const gradeForm = document.getElementById("gradeAdminForm");
    const gradeFeedback = document.getElementById("gradeFeedback");
    const gradeStudentCodeInput = document.getElementById("grade_student_code");
    const gradeStudentIdInput = document.getElementById("grade_student_id");
    const gradeStudentHint = document.getElementById("gradeStudentHint");

    const attendanceForm = document.getElementById("attendanceAdminForm");
    const attendanceFeedback = document.getElementById("attendanceAdminFeedback");
    const attendanceStudentCodeInput = document.getElementById("attendance_student_code");
    const attendanceStudentIdInput = document.getElementById("attendance_student_id");
    const attendanceStudentHint = document.getElementById("attendanceStudentHint");
    const studentsCodeSuggestions = document.getElementById("studentsCodeSuggestions");
    const lessonForm = document.getElementById("lessonAdminForm");
    const lessonSubmitBtn = document.getElementById("lessonSubmitBtn");
    const lessonResetBtn = document.getElementById("lessonResetBtn");
    const lessonFeedback = document.getElementById("lessonAdminFeedback");
    const lessonsTableBody = document.getElementById("lessonsTableBody");
    const lessonsTableFeedback = document.getElementById("lessonsTableFeedback");

    const gradesViewStudent = document.getElementById("gradesViewStudent");
    const gradesSemesterFilter = document.getElementById("gradesSemesterFilter");
    const viewGradesBtn = document.getElementById("viewGradesBtn");
    const gradesViewResult = document.getElementById("gradesViewResult");

    const attendanceViewStudent = document.getElementById("attendanceViewStudent");
    const viewAttendanceBtn = document.getElementById("viewAttendanceBtn");
    const attendanceViewResult = document.getElementById("attendanceViewResult");

    const statStudents = document.getElementById("statStudents");
    const statGrades = document.getElementById("statGrades");
    const statAttendance = document.getElementById("statAttendance");

    const tabButtons = Array.from(document.querySelectorAll(".tab-btn[data-tab-target]"));
    const tabPanels = Array.from(document.querySelectorAll(".tab-panel"));
    const roleScopedElements = Array.from(document.querySelectorAll("[data-role-only]"));

    let studentsCache = [];
    let lessonsCache = [];
    let activeRole = "";
    const gradeCodeHintDefault = "أدخل كود الطالب كما هو مسجل.";
    const attendanceCodeHintDefault = "أدخل كود الطالب كما هو مسجل.";

    function escapeHtml(value) {
        const safeValue = value === null || value === undefined ? "" : value;
        return String(safeValue)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#39;");
    }
    function setFeedback(element, message, type) {
        if (!element) return;

        element.className = `feedback ${type}`;
        element.textContent = message;
    }

    function clearFeedback(element) {
        if (!element) return;
        element.className = 'feedback';
        element.textContent = '';
    }

    function getLoginFeedbackElement(role, formId = "") {
        const normalizedRole = normalizeRoleValue(role);
        if (normalizedRole === "director") {
            return directorLoginFeedback || legacyLoginFeedback;
        }
        if (normalizedRole === "secretary") {
            return secretaryLoginFeedback || legacyLoginFeedback;
        }

        const normalizedFormId = String(formId || "").toLowerCase();
        if (normalizedFormId.includes("director")) {
            return directorLoginFeedback || legacyLoginFeedback;
        }
        if (normalizedFormId.includes("secretary")) {
            return secretaryLoginFeedback || legacyLoginFeedback;
        }

        return legacyLoginFeedback || directorLoginFeedback || secretaryLoginFeedback;
    }

    function clearLoginFeedbacks() {
        clearFeedback(directorLoginFeedback);
        clearFeedback(secretaryLoginFeedback);
        clearFeedback(legacyLoginFeedback);
    }

    function normalizeRoleValue(role) {
        const normalizedRole = String(role || "").trim().toLowerCase();
        if (normalizedRole === "admin") {
            return "director";
        }

        return normalizedRole;
    }

    function getRoleLabel(role) {
        const r = normalizeRoleValue(role);
        if (r === 'director') return 'المدير';
        if (r === 'secretary') return 'السكرتارية';
        return 'الإدارة';
    }

    function getRoleIcon(role) {
        const r = normalizeRoleValue(role);
        if (r === 'director') return 'fa-user-tie';
        if (r === 'secretary') return 'fa-user-shield';
        return 'fa-user';
    }

    function syncLoginRoleInputs() {
        if (!loginRoleInput) {
            return;
        }

        const selectedRole = normalizeRoleValue(loginRoleInput.value || "director");
        if (loginEmailInput) {
            loginEmailInput.placeholder =
                selectedRole === "secretary"
                    ? "secretary@kawkab-ouloum.ma"
                    : "admin@kawkab-ouloum.ma";
        }
        if (loginCodeInput) {
            loginCodeInput.placeholder =
                selectedRole === "secretary"
                    ? "Secretary@2026"
                    : "Admin@2026";
        }
    }

    function setDefaultTabForRole(role) {
        const normalizedRole = normalizeRoleValue(role);
        const preferredTabId = normalizedRole === "secretary" ? "studentsTab" : "studentsListTab";

        const preferredButton = tabButtons.find(
            (button) =>
                !button.hidden &&
                String(button.getAttribute("data-tab-target") || "") === preferredTabId
        );
        if (preferredButton) {
            setActiveTab(preferredTabId);
            return;
        }

        const fallbackButton = tabButtons.find((button) => !button.hidden);
        if (fallbackButton) {
            const target = String(fallbackButton.getAttribute("data-tab-target") || "");
            if (target !== "") {
                setActiveTab(target);
            }
        }
    }

    function applyRoleAccess(role) {
        const normalizedRole = normalizeRoleValue(role);

        roleScopedElements.forEach((element) => {
            const requiredRole = normalizeRoleValue(
                String(element.getAttribute("data-role-only") || "")
            );
            const isAllowed = requiredRole !== "" && requiredRole === normalizedRole;
            element.hidden = !isAllowed;
            if (!isAllowed) {
                element.classList.remove("is-active");
            }
        });

        tabButtons.forEach((button) => {
            const requiredRole = normalizeRoleValue(
                String(button.getAttribute("data-role-only") || "")
            );
            if (requiredRole === "") {
                button.hidden = false;
                return;
            }

            button.hidden = requiredRole !== normalizedRole;
        });



        if (normalizedRole === "secretary") {
            setCodeHintText(gradeStudentHint, "أدخل كود الطالب وسيتم التحقق منه عند الحفظ.");
            setCodeHintText(attendanceStudentHint, "أدخل كود الطالب وسيتم التحقق منه عند الحفظ.");
        } else {
            setCodeHintText(gradeStudentHint, gradeCodeHintDefault);
            setCodeHintText(attendanceStudentHint, attendanceCodeHintDefault);
        }
    }

    function setConnectionStatus(state, message, details) {
        if (dbConnectionStatus) {
            dbConnectionStatus.className = `status-pill is-${state}`;
            dbConnectionStatus.innerHTML = message;
        }

        if (dbConnectionDetails && typeof details === "string" && details !== "") {
            dbConnectionDetails.textContent = details;
        }
    }

    async function checkDatabaseConnection() {
        setConnectionStatus(
            "loading",
            '<i class="fa-solid fa-spinner fa-spin"></i> جاري فحص الربط مع قاعدة البيانات...',
            "القاعدة المستهدفة: edu_institution"
        );

        try {
            const response = await fetch("backend/db_health.php", {
                method: "GET",
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                },
            });

            const payload = await response.json();
            if (!payload.success) {
                setConnectionStatus(
                    "error",
                    `<i class="fa-solid fa-triangle-exclamation"></i> ${escapeHtml(payload.message || "فشل فحص الربط.")}`,
                    "تأكد من تشغيل MySQL واستيراد ملف database.sql"
                );
                return;
            }

            const data = payload.data || {};
            const missingTables = Array.isArray(data.missing_tables) ? data.missing_tables : [];
            if (missingTables.length > 0) {
                setConnectionStatus(
                    "warning",
                    `<i class="fa-solid fa-circle-exclamation"></i> القاعدة متصلة ولكن ناقصة جداول (${missingTables.length})`,
                    `الجداول الناقصة: ${missingTables.join(", ")}`
                );
                return;
            }

            setConnectionStatus(
                "success",
                '<i class="fa-solid fa-circle-check"></i> الربط مع قاعدة البيانات شغال',
                `تم التحقق من ${Number(data.total_tables || 0)} جداول مطلوبة`
            );
        } catch (error) {
            console.error("DB health check error:", error);
            setConnectionStatus(
                "error",
                '<i class="fa-solid fa-triangle-exclamation"></i> تعذر الوصول لخدمة فحص قاعدة البيانات',
                "تأكد من تشغيل Apache + MySQL ومسار المشروع داخل htdocs"
            );
        }
    }

    function setDashboardState(isLoggedIn) {
        const isCurrentlyLoggedIn = dashboardPanel && !dashboardPanel.hidden && dashboardPanel.classList.contains("is-visible");

        if (loginPanel) {
            if (isLoggedIn) {
                loginPanel.style.transition = "opacity 0.3s ease, transform 0.3s ease";
                loginPanel.style.opacity = "0";
                loginPanel.style.transform = "translateY(-10px)";
                setTimeout(() => {
                    loginPanel.hidden = true;
                    loginPanel.classList.remove("is-visible");
                    loginPanel.style.display = "none";
                }, 300);
            } else {
                loginPanel.hidden = false;
                loginPanel.style.display = "block";
                loginPanel.style.opacity = "1";
                loginPanel.style.transform = "translateY(0)";
                loginPanel.classList.add("is-visible");
            }
        }

        if (dashboardPanel) {
            if (isLoggedIn) {
                dashboardPanel.hidden = false;
                dashboardPanel.style.display = "grid";
                if (!isCurrentlyLoggedIn) {
                    dashboardPanel.style.opacity = "0";
                    dashboardPanel.style.transform = "translateY(10px)";
                    dashboardPanel.style.transition = "opacity 0.4s ease 0.1s, transform 0.4s ease 0.1s";
                    setTimeout(() => {
                        dashboardPanel.style.opacity = "1";
                        dashboardPanel.style.transform = "translateY(0)";
                        dashboardPanel.classList.add("is-visible");
                    }, 30);
                } else {
                    dashboardPanel.style.opacity = "1";
                    dashboardPanel.style.transform = "translateY(0)";
                    dashboardPanel.classList.add("is-visible");
                }
            } else {
                dashboardPanel.hidden = true;
                dashboardPanel.style.display = "none";
                dashboardPanel.style.opacity = "0";
                dashboardPanel.classList.remove("is-visible");
            }
        }

        if (logoutBtn) {
            logoutBtn.disabled = !isLoggedIn;
        }

        const asidePanel = document.querySelector(".admin-aside");
        if (asidePanel) {
            asidePanel.hidden = isLoggedIn;
        }
    }

    function setIdentity(user) {
        if (!identityBox) {
            return;
        }

        if (!user) {
            identityBox.innerHTML = '<p class="muted">غير مسجل الدخول.</p>';
            return;
        }

        const role = String(user.role || "");
        const roleLabel = getRoleLabel(role);
        const roleIcon = getRoleIcon(role);

        identityBox.innerHTML = `
            <div class="premium-identity-card">
                <div class="identity-avatar">
                   <i class="fa-solid ${escapeHtml(roleIcon)}"></i>
                </div>
                <div class="identity-details">
                    <p class="identity-name">${escapeHtml(user.full_name || "Admin")}</p>
                    <p class="identity-meta">
                        <span><i class="fa-solid fa-id-badge"></i> ${escapeHtml(roleLabel)}</span>
                        <span><i class="fa-solid fa-envelope"></i> ${escapeHtml(user.email || "-")}</span>
                    </p>
                </div>
            </div>
        `;
    }

    function updateStats(stats) {
        const values = stats || {};
        if (statStudents) {
            statStudents.textContent = String(values.students_count || 0);
        }
        if (statGrades) {
            statGrades.textContent = String(values.grades_count || 0);
        }
        if (statAttendance) {
            statAttendance.textContent = String(values.attendance_count || 0);
        }
    }

    function normalizeStudentCode(value) {
        return String(value || "").trim().toUpperCase();
    }

    function buildStudentLabel(student) {
        if (!student) {
            return "";
        }

        const studentCode = String(student.student_code || "").trim();
        const fullName = `${student.first_name || ""} ${student.last_name || ""}`.trim();
        const className = String(student.class_name || "").trim();
        const parts = [];

        if (studentCode !== "") {
            parts.push(studentCode);
        }
        if (fullName !== "") {
            parts.push(fullName);
        }
        if (className !== "") {
            parts.push(className);
        }

        return parts.join(" - ");
    }

    function setCodeHintText(element, message) {
        if (!element) {
            return;
        }

        element.textContent = message;
    }

    function syncStudentCodeSelection({
        codeInput,
        idInput,
        hintElement,
        defaultHint,
        normalizeInput = false,
        allowUnknownCode = false,
    }) {
        if (!codeInput || !idInput) {
            return null;
        }

        const normalizedCode = normalizeStudentCode(codeInput.value);
        if (normalizeInput) {
            codeInput.value = normalizedCode;
        }

        if (normalizedCode === "") {
            idInput.value = "";
            setCodeHintText(hintElement, defaultHint);
            return null;
        }

        const matchedStudent =
            studentsCache.find((student) => normalizeStudentCode(student.student_code) === normalizedCode) || null;

        if (!matchedStudent || Number(matchedStudent.id) <= 0) {
            idInput.value = "";
            if (allowUnknownCode) {
                setCodeHintText(hintElement, "سيتم التحقق من الكود عند الحفظ.");
                return {
                    id: 0,
                    student_code: normalizedCode,
                    unresolved: true,
                };
            }
            setCodeHintText(hintElement, "الكود غير موجود، تأكد من كتابته بشكل صحيح.");
            return null;
        }

        idInput.value = String(matchedStudent.id);
        setCodeHintText(hintElement, `تم التعرف على: ${buildStudentLabel(matchedStudent)}`);

        if (normalizeInput) {
            codeInput.value = String(matchedStudent.student_code || normalizedCode);
        }

        return matchedStudent;
    }

    function resetStudentCodeSelection(codeInput, idInput, hintElement, defaultHint) {
        if (codeInput) {
            codeInput.value = "";
        }
        if (idInput) {
            idInput.value = "";
        }
        setCodeHintText(hintElement, defaultHint);
    }

    function getFilteredStudents() {
        const search = String(studentsSearch ? studentsSearch.value : "").trim().toLowerCase();
        const selectedLevel = String(levelFilter ? levelFilter.value : "");

        return studentsCache.filter((student) => {
            const levelOk = selectedLevel === "" || String(student.level || "") === selectedLevel;
            if (!levelOk) {
                return false;
            }

            if (search === "") {
                return true;
            }

            const fullName = `${student.first_name || ""} ${student.last_name || ""}`.toLowerCase();
            const studentCode = String(student.student_code || "").toLowerCase();
            const className = String(student.class_name || "").toLowerCase();

            return (
                fullName.includes(search) ||
                studentCode.includes(search) ||
                className.includes(search)
            );
        });
    }

    function renderStudentsTable(students) {
        if (!studentsTableBody) {
            return;
        }

        if (!Array.isArray(students) || students.length === 0) {
            studentsTableBody.innerHTML = '<tr><td colspan="7">لا توجد بيانات مطابقة حاليا.</td></tr>';
            return;
        }

        studentsTableBody.innerHTML = students
            .map((student) => {
                const fullName = `${student.first_name || ""} ${student.last_name || ""}`.trim();
                const guardianText = `${student.guardian_name || "-"} | ${student.guardian_phone || "-"}`;
                const status = student.registration_status || "approved";

                let statusHtml = "";
                if (status === "pending") {
                    statusHtml = '<span class="status-pill is-warning"><i class="fa-solid fa-clock"></i> قيد الانتظار</span>';
                } else if (status === "approved") {
                    statusHtml = '<span class="status-pill is-success"><i class="fa-solid fa-check-double"></i> مقبول</span>';
                } else if (status === "rejected") {
                    statusHtml = '<span class="status-pill is-error"><i class="fa-solid fa-xmark"></i> مرفوض</span>';
                }

                return `
                    <tr>
                        <td>${escapeHtml(student.student_code || "")}</td>
                        <td>${escapeHtml(fullName)}</td>
                        <td>${escapeHtml(student.class_name || "")}</td>
                        <td>${escapeHtml(student.level || "")}</td>
                        <td>${escapeHtml(guardianText)}</td>
                        <td>${statusHtml}</td>
                        <td>
                            <div class="student-actions">
                                ${activeRole === "director" ? `
                                    ${status === "pending" ? `
                                        <button type="button" class="btn btn-primary btn-small" data-action="verify" data-verify-status="approved" data-id="${escapeHtml(student.id)}" data-is-request="${escapeHtml(student.is_request)}">
                                            <i class="fa-solid fa-check"></i> قبول
                                        </button>
                                        <button type="button" class="btn btn-danger btn-small" data-action="verify" data-verify-status="rejected" data-id="${escapeHtml(student.id)}" data-is-request="${escapeHtml(student.is_request)}">
                                            <i class="fa-solid fa-ban"></i> رفض
                                        </button>
                                    ` : ""}
                                    <button type="button" class="btn btn-outline btn-small" data-action="edit" data-id="${escapeHtml(student.id)}" data-is-request="${escapeHtml(student.is_request)}">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                        تعديل
                                    </button>
                                    <button type="button" class="btn btn-danger btn-small" data-action="delete" data-id="${escapeHtml(student.id)}" data-is-request="${escapeHtml(student.is_request)}">
                                        <i class="fa-solid fa-trash"></i>
                                        حذف
                                    </button>
                                ` : `
                                    <span class="muted small"><i class="fa-solid fa-lock"></i> عرض فقط</span>
                                `}
                            </div>
                        </td>
                    </tr>
                `;
            })
            .join("");
    }

    function populateStudentSelects(students) {
        const allStudents = Array.isArray(students) ? students : [];
        const reportOptions = ['<option value="">اختر تلميذا</option>']
            .concat(
                allStudents.map((student) => {
                    const fullName = `${student.first_name || ""} ${student.last_name || ""}`.trim();
                    return `<option value="${escapeHtml(student.id)}">${escapeHtml(student.student_code)} - ${escapeHtml(fullName)}</option>`;
                })
            )
            .join("");

        if (gradesViewStudent) {
            gradesViewStudent.innerHTML = reportOptions;
        }
        if (attendanceViewStudent) {
            attendanceViewStudent.innerHTML = reportOptions;
        }
        if (studentsCodeSuggestions) {
            studentsCodeSuggestions.innerHTML = allStudents
                .map((student) => {
                    const studentCode = escapeHtml(student.student_code || "");
                    const fullName = `${student.first_name || ""} ${student.last_name || ""}`.trim();
                    return `<option value="${studentCode}" label="${escapeHtml(fullName)}"></option>`;
                })
                .join("");
        }

        syncStudentCodeSelection({
            codeInput: gradeStudentCodeInput,
            idInput: gradeStudentIdInput,
            hintElement: gradeStudentHint,
            defaultHint: gradeCodeHintDefault,
        });
        syncStudentCodeSelection({
            codeInput: attendanceStudentCodeInput,
            idInput: attendanceStudentIdInput,
            hintElement: attendanceStudentHint,
            defaultHint: attendanceCodeHintDefault,
        });
    }

    function resetStudentForm() {
        if (!studentForm) {
            return;
        }

        studentForm.reset();
        const idField = studentForm.querySelector('input[name="student_id"]');
        if (idField) {
            idField.value = "";
        }

        if (studentSubmitBtn) {
            studentSubmitBtn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> حفظ الطالب';
        }
        const isReqInput = studentForm.querySelector('[name="is_request"]');
        if (isReqInput) isReqInput.value = "no";
    }

    function getLessonTypeLabel(type) {
        if (type === "video") {
            return "Video";
        }
        if (type === "pdf") {
            return "PDF";
        }
        if (type === "image") {
            return "Image";
        }
        return "Unknown";
    }

    function renderLessonsTable(lessons) {
        if (!lessonsTableBody) {
            return;
        }

        if (!Array.isArray(lessons) || lessons.length === 0) {
            lessonsTableBody.innerHTML = '<tr><td colspan="6">لا توجد دروس حاليا.</td></tr>';
            return;
        }

        lessonsTableBody.innerHTML = lessons
            .map((lesson) => `
                <tr>
                    <td>${escapeHtml(lesson.title || "")}</td>
                    <td>${escapeHtml(getLessonTypeLabel(String(lesson.lesson_type || "")))}</td>
                    <td>${escapeHtml(lesson.level || "-")}</td>
                    <td>${escapeHtml(lesson.subject_name || "-")}</td>
                    <td>${Number(lesson.is_published) === 1 ? "منشور" : "مسودة"}</td>
                    <td>
                        <div class="student-actions">
                            <a class="btn btn-outline btn-small" href="${escapeHtml(lesson.content_url || "#")}" target="_blank" rel="noopener noreferrer">
                                <i class="fa-solid fa-up-right-from-square"></i>
                                فتح
                            </a>
                            <button type="button" class="btn btn-outline btn-small" data-lesson-action="edit" data-lesson-id="${escapeHtml(lesson.id)}">
                                <i class="fa-solid fa-pen-to-square"></i>
                                تعديل
                            </button>
                            <button type="button" class="btn btn-danger btn-small" data-lesson-action="delete" data-lesson-id="${escapeHtml(lesson.id)}">
                                <i class="fa-solid fa-trash"></i>
                                حذف
                            </button>
                        </div>
                    </td>
                </tr>
            `)
            .join("");
    }

    function resetLessonForm() {
        if (!lessonForm) {
            return;
        }
        lessonForm.reset();
        const idField = lessonForm.querySelector('input[name="lesson_id"]');
        if (idField) {
            idField.value = "";
        }
        const typeField = lessonForm.querySelector('select[name="lesson_type"]');
        if (typeField && typeField.value === "") {
            typeField.value = "video";
        }
        const publishedField = lessonForm.querySelector('select[name="is_published"]');
        if (publishedField && publishedField.value === "") {
            publishedField.value = "1";
        }
        if (lessonSubmitBtn) {
            lessonSubmitBtn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> حفظ الدرس';
        }
    }

    function fillLessonForm(lesson) {
        if (!lessonForm || !lesson) {
            return;
        }
        const mapping = {
            lesson_id: lesson.id,
            title: lesson.title,
            lesson_type: lesson.lesson_type,
            level: lesson.level,
            subject_name: lesson.subject_name,
            description: lesson.description,
            content_url: lesson.content_url,
            thumbnail_url: lesson.thumbnail_url,
            is_published: String(Number(lesson.is_published) === 1 ? 1 : 0),
        };

        Object.keys(mapping).forEach((name) => {
            const input = lessonForm.querySelector(`[name="${name}"]`);
            if (input) {
                input.value = mapping[name] || "";
            }
        });

        const fileInput = lessonForm.querySelector('input[name="lesson_file"]');
        if (fileInput) {
            fileInput.value = "";
        }

        if (lessonSubmitBtn) {
            lessonSubmitBtn.innerHTML = '<i class="fa-solid fa-pen-to-square"></i> تحديث الدرس';
        }
        setFeedback(
            lessonFeedback,
            "يمكنك تعديل الدرس، ثم اضغط على تحديث الدرس للحفظ.",
            "success"
        );
        setActiveTab("lessonsTab");
        lessonForm.scrollIntoView({ behavior: "smooth", block: "start" });
    }

    async function loadLessonsData(showSuccessMessage = false) {
        if (!lessonForm && !lessonsTableBody) {
            return;
        }
        clearFeedback(lessonsTableFeedback);

        const formData = new FormData();
        formData.append("action", "list");

        const { status, payload } = await postForm("backend/admin_lessons.php", formData);
        if (!payload.success) {
            setFeedback(lessonsTableFeedback, payload.message || "تعذر جلب الدروس.", "error");
            return;
        }

        lessonsCache = Array.isArray(payload.data && payload.data.lessons) ? payload.data.lessons : [];
        renderLessonsTable(lessonsCache);
        if (showSuccessMessage) {
            setFeedback(lessonsTableFeedback, "تم تحديث لائحة الدروس.", "success");
        }
    }

    function fillStudentForm(student) {
        if (!studentForm || !student) {
            return;
        }

        const mapping = {
            student_id: student.id,
            student_code: student.student_code,
            first_name: student.first_name,
            last_name: student.last_name,
            birth_date: student.birth_date,
            gender: student.gender,
            class_name: student.class_name,
            level: student.level,
            email: student.email,
            phone: student.phone,
            address: student.address,
            guardian_name: student.guardian_name,
            guardian_phone: student.guardian_phone,
            guardian_email: student.guardian_email,
            is_request: student.is_request || "no",
        };

        Object.keys(mapping).forEach((name) => {
            const input = studentForm.querySelector(`[name="${name}"]`);
            if (input) {
                input.value = mapping[name] || "";
            }
        });

        if (studentSubmitBtn) {
            studentSubmitBtn.innerHTML = '<i class="fa-solid fa-pen-to-square"></i> تحديث الطالب';
        }

        setActiveTab("studentsTab");
        studentForm.scrollIntoView({ behavior: "smooth", block: "start" });
    }

    function clearViewPanels() {
        if (gradesViewResult) {
            gradesViewResult.innerHTML = '<p class="muted">اختر تلميذا لعرض نقاطه.</p>';
        }
        if (attendanceViewResult) {
            attendanceViewResult.innerHTML = '<p class="muted">اختر تلميذا لعرض سجل غيابه.</p>';
        }
    }

    function applyStudentsFiltersAndRender() {
        renderStudentsTable(getFilteredStudents());
    }

    function setActiveTab(tabId) {
        let safeTabId = String(tabId || "").trim();

        // Check if target panel exists
        let targetPanel = tabPanels.find((panel) => String(panel.id || "") === safeTabId);

        // If specific requested tab panel not found or inactive, fallback to role default or first panel
        if (!targetPanel) {
            const preferredId = activeRole === "secretary" ? "studentsTab" : "studentsListTab";
            targetPanel = tabPanels.find((panel) => String(panel.id || "") === preferredId) || tabPanels[0];
            safeTabId = targetPanel ? String(targetPanel.id || "") : "studentsListTab";
        }

        tabButtons.forEach((button) => {
            const buttonTarget = String(button.getAttribute("data-tab-target") || "");
            const isMatch = buttonTarget === safeTabId;
            button.classList.toggle("is-active", isMatch);
            button.setAttribute("aria-selected", isMatch ? "true" : "false");
        });

        tabPanels.forEach((panel) => {
            const isMatch = String(panel.id || "") === safeTabId;
            panel.classList.toggle("is-active", isMatch);
            panel.hidden = !isMatch;
            panel.style.display = isMatch ? "block" : "none";
        });
    }

    async function postForm(url, formData) {
        let response;
        try {
            response = await fetch(url, {
                method: "POST",
                body: formData,
                credentials: "same-origin", // Ensure session cookies are sent
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                },
            });
        } catch (fetchError) {
            // Network-level failure (server unreachable, CORS, DNS, etc.)
            return {
                status: 0,
                payload: {
                    success: false,
                    message: `تعذر الاتصال بالخادم: ${fetchError.message}`,
                },
            };
        }

        let payload = null;
        const contentType = (response.headers && response.headers.get)
            ? (response.headers.get('Content-Type') || '')
            : '';

        if (contentType.includes('application/json')) {
            try {
                payload = await response.json();
            } catch (error) {
                // Server responded but not with valid JSON
                let text = '';
                try {
                    text = await response.text();
                } catch (e) {
                    /* ignore */
                }
                payload = {
                    success: false,
                    message: 'رد الخادم ليس بصيغة JSON صالحة.',
                    debug: text,
                };
            }
        } else {
            // Unexpected content-type (HTML error page, etc.)
            let text = '';
            try {
                text = await response.text();
            } catch (e) {
                /* ignore */
            }
            payload = {
                success: false,
                message: `رد الخادم غير متوقع (Content-Type: ${contentType}).`,
                debug: text,
            };
        }

        return {
            status: response.status,
            payload,
        };
    }

    async function performLogout() {
        try {
            await postForm("backend/logout.php", new FormData());
        } catch (error) {
            console.error("Logout error:", error);
        }
    }

    function handleUnauthorized() {
        activeRole = "";
        setDashboardState(false);
        applyRoleAccess("");
        setIdentity(null);
        studentsCache = [];
        lessonsCache = [];
        populateStudentSelects([]);
        if (studentsTableBody) {
            studentsTableBody.innerHTML = '<tr><td colspan="7">قم بتسجيل الدخول (المدير أو السكرتارية) أولا.</td></tr>';
        }
        if (lessonsTableBody) {
            lessonsTableBody.innerHTML = '<tr><td colspan="6">قم بتسجيل الدخول (المدير أو السكرتارية) أولا.</td></tr>';
        }
        updateStats({
            students_count: 0,
            grades_count: 0,
            attendance_count: 0,
        });
        resetStudentForm();
        if (gradeForm) {
            gradeForm.reset();
        }
        if (attendanceForm) {
            attendanceForm.reset();
        }
        resetLessonForm();
        resetStudentCodeSelection(
            gradeStudentCodeInput,
            gradeStudentIdInput,
            gradeStudentHint,
            gradeCodeHintDefault
        );
        resetStudentCodeSelection(
            attendanceStudentCodeInput,
            attendanceStudentIdInput,
            attendanceStudentHint,
            attendanceCodeHintDefault
        );
        clearViewPanels();
        if (loginRoleInput) {
            loginRoleInput.value = "director";
            syncLoginRoleInputs();
        }
    }

    async function loadDashboardData(showSuccessMessage = false) {
        clearFeedback(studentTableFeedback);
        clearLoginFeedbacks();

        const formData = new FormData();
        formData.append("action", "list");

        const { status, payload } = await postForm("backend/admin_students.php", formData);

        if (!payload.success) {
            if (status === 403) {
                handleUnauthorized();
                return;
            }

            setFeedback(studentTableFeedback, payload.message || "تعذر جلب بيانات التلاميذ.", "error");
            // Still show identity if available from session elsewhere or just keep dashboard visible
            return;
        }

        // Only show dashboard after successful session verification
        setDashboardState(true);

        const data = payload.data || {};
        studentsCache = Array.isArray(data.students) ? data.students : [];
        const sessionUser = data.user || data.admin || null;

        setIdentity(sessionUser);
        activeRole = normalizeRoleValue(sessionUser && sessionUser.role ? sessionUser.role : "");
        applyRoleAccess(activeRole);
        updateStats(data.stats || {});
        populateStudentSelects(studentsCache);
        applyStudentsFiltersAndRender();
        try {
            await loadLessonsData();
        } catch (errLessons) {
            console.error("Lessons auto-load error:", errLessons);
        }

        /* ------------------ Teacher Absences Management (Admin) ------------------ */
        const teacherAbsencesTableBody = document.getElementById('teacherAbsencesAdminTableBody');
        const refreshTeacherAbsencesBtn = document.getElementById('refreshTeacherAbsencesBtn');
        const teacherAbsenceTabBtn = document.querySelector('[data-tab-target="teacherAbsencesTab"]');
        const filterStatusAbsence = document.getElementById('teacherAbsenceFilterStatus');
        const filterUrgentAbsence = document.getElementById('teacherAbsenceFilterUrgent');
        const urgentBadge = document.getElementById('urgentAbsencesBadge');
        const adminAbsencesFeedback = document.getElementById('teacherAbsencesAdminFeedback');

        let teacherAbsencesCache = [];
        let canReviewTeacherAbsences = false;

        async function loadTeacherAbsencesAdmin() {
            if (!teacherAbsencesTableBody) return;

            teacherAbsencesTableBody.innerHTML = '<tr><td colspan="6" class="muted"><i class="fa-solid fa-spinner fa-spin"></i> جاري تحميل إشعارات الغياب...</td></tr>';
            clearFeedback(adminAbsencesFeedback);

            const formData = new FormData();
            formData.append('action', 'list');

            const { status, payload } = await postForm('backend/admin_teacher_absences.php', formData);
            if (!payload.success) {
                teacherAbsencesTableBody.innerHTML = `<tr><td colspan="6" class="muted">خطأ: ${escapeHtml(payload.message || 'تعذر جلب البيانات')}</td></tr>`;
                return;
            }

            const data = payload.data || {};
            teacherAbsencesCache = Array.isArray(data.absences) ? data.absences : [];
            canReviewTeacherAbsences = !!data.can_review;

            const urgentCount = Number(data.urgent_count || 0);
            if (urgentBadge) {
                if (urgentCount > 0) {
                    urgentBadge.textContent = `🚨 ${urgentCount} عاجل`;
                    urgentBadge.style.display = 'inline-block';
                } else {
                    urgentBadge.style.display = 'none';
                }
            }

            renderTeacherAbsencesAdminTable();
        }

        function renderTeacherAbsencesAdminTable() {
            if (!teacherAbsencesTableBody) return;

            const selectedStatus = String(filterStatusAbsence ? filterStatusAbsence.value : '');
            const selectedUrgent = String(filterUrgentAbsence ? filterUrgentAbsence.value : '');

            const filtered = teacherAbsencesCache.filter((item) => {
                if (selectedStatus !== '' && item.status !== selectedStatus) return false;
                if (selectedUrgent === 'urgent' && !item.is_urgent) return false;
                if (selectedUrgent === 'normal' && item.is_urgent) return false;
                return true;
            });

            if (filtered.length === 0) {
                teacherAbsencesTableBody.innerHTML = '<tr><td colspan="6" class="muted">لا توجد إشعارات غياب مطابقة.</td></tr>';
                return;
            }

            teacherAbsencesTableBody.innerHTML = filtered.map((item) => {
                const isUrgentBadge = item.is_urgent
                    ? `<span style="background:#fee2e2; color:#dc2626; border:1px solid #fca5a5; padding:0.25rem 0.6rem; border-radius:20px; font-weight:800; font-size:0.82rem; display:inline-flex; align-items:center; gap:0.3rem;"><i class="fa-solid fa-triangle-exclamation"></i> عاجل / طوارئ</span>`
                    : `<span style="background:#f1f5f9; color:#475569; padding:0.25rem 0.6rem; border-radius:20px; font-weight:600; font-size:0.82rem;">عادي</span>`;

                let statusBadge = '';
                if (item.status === 'pending') {
                    statusBadge = '<span class="status-pill is-warning"><i class="fa-solid fa-clock"></i> قيد المراجعة</span>';
                } else if (item.status === 'approved') {
                    statusBadge = '<span class="status-pill is-success"><i class="fa-solid fa-check"></i> مقبول</span>';
                } else if (item.status === 'rejected') {
                    statusBadge = '<span class="status-pill is-error"><i class="fa-solid fa-xmark"></i> مرفوض</span>';
                }

                const docLink = item.document_path
                    ? `<a href="${escapeHtml(item.document_path)}" target="_blank" class="btn btn-small btn-outline" style="margin-top:0.3rem; display:inline-flex; gap:0.3rem;"><i class="fa-solid fa-paperclip"></i> الوصل/المستند</a>`
                    : '';

                const phoneClean = (item.teacher_phone || '').replace(/[^0-9+]/g, '');
                const waNumber = phoneClean.startsWith('0') ? '212' + phoneClean.substring(1) : phoneClean;
                const contactButtons = `
                    <div style="display:flex; gap:0.4rem; flex-wrap:wrap; margin-bottom:0.4rem;">
                        ${item.teacher_phone ? `
                            <a href="tel:${escapeHtml(item.teacher_phone)}" class="btn btn-small btn-outline" style="background:#f0f9ff; color:#0369a1; border-color:#bae6fd;" title="اتصال مباشر">
                                <i class="fa-solid fa-phone"></i> اتصال
                            </a>
                            <a href="https://wa.me/${escapeHtml(waNumber)}?text=${encodeURIComponent('السلام عليكم أستاذ ' + item.teacher_name + '، بخصوص إشعار غيابكم بتاريخ ' + item.absence_date)}" target="_blank" rel="noopener" class="btn btn-small btn-outline" style="background:#f0fdf4; color:#15803d; border-color:#bbf7d0;" title="تواصل واتساب">
                                <i class="fa-brands fa-whatsapp"></i> واتساب
                            </a>
                        ` : ''}
                        ${item.teacher_email ? `
                            <a href="mailto:${escapeHtml(item.teacher_email)}?subject=${encodeURIComponent('إشعار غياب - ' + item.absence_date)}" class="btn btn-small btn-outline" style="background:#fcf5ff; color:#7e22ce; border-color:#e9d5ff;" title="إرسال إيميل">
                                <i class="fa-solid fa-envelope"></i> إيميل
                            </a>
                        ` : ''}
                    </div>
                `;

                let reviewSection = '';
                if (canReviewTeacherAbsences) {
                    reviewSection = `
                        <div style="margin-top:0.4rem; background:#ffffff; padding:0.6rem; border-radius:0.6rem; border:1px solid #e2e8f0; box-shadow:0 2px 6px rgba(0,0,0,0.03);">
                            <label style="font-size:0.8rem; font-weight:bold; display:block; margin-bottom:0.3rem; color:#1e293b;">رد المدير والتوضيح للاستاذ:</label>
                            <input type="text" id="adminNoteInput_${item.id}" value="${escapeHtml(item.admin_notes || '')}" placeholder="اكتب ردك أو ملاحظتك هنا..." style="font-size:0.85rem; padding:0.4rem 0.6rem; border-radius:0.5rem; border:1px solid #cbd5e1; width:100%; margin-bottom:0.4rem;">
                            <div style="display:flex; gap:0.4rem;">
                                <button type="button" class="btn btn-primary btn-small" data-review-absence-id="${item.id}" data-review-decision="approved">
                                    <i class="fa-solid fa-check"></i> موافقة
                                </button>
                                <button type="button" class="btn btn-danger btn-small" data-review-absence-id="${item.id}" data-review-decision="rejected">
                                    <i class="fa-solid fa-xmark"></i> رفض
                                </button>
                            </div>
                        </div>
                    `;
                } else {
                    reviewSection = `
                        <div style="margin-top:0.4rem;">
                            <span class="muted small" style="background:#f1f5f9; padding:0.3rem 0.6rem; border-radius:6px; display:inline-block;"><i class="fa-solid fa-lock"></i> البت والرد اختصاص حصري للمدير</span>
                        </div>
                    `;
                }

                return `
                    <tr>
                        <td>
                            <strong style="color:var(--admin-brand-strong); font-size:0.98rem;">${escapeHtml(item.teacher_name)}</strong>
                            <div class="muted small">${escapeHtml(item.teacher_subject || 'أستاذ')}</div>
                            <div class="muted small"><i class="fa-solid fa-phone"></i> ${escapeHtml(item.teacher_phone || '-')}</div>
                        </td>
                        <td>
                            <strong style="color:#0f172a;">${escapeHtml(item.absence_date)}</strong>
                            <div class="muted small">نوع: ${escapeHtml(item.type)}</div>
                            <div class="muted small" style="font-size:0.75rem;">${escapeHtml(item.created_at)}</div>
                        </td>
                        <td>${isUrgentBadge}</td>
                        <td>
                            <div style="white-space:pre-wrap; max-width:250px;">${escapeHtml(item.reason)}</div>
                            ${docLink}
                        </td>
                        <td>
                            <div>${statusBadge}</div>
                            ${item.admin_notes ? `<div style="margin-top:0.4rem; background:#f8fafc; padding:0.4rem 0.6rem; border-right:3px solid var(--admin-accent); font-size:0.85rem; border-radius:4px;"><strong>رد الإدارة:</strong> ${escapeHtml(item.admin_notes)}</div>` : ''}
                        </td>
                        <td>
                            ${contactButtons}
                            ${reviewSection}
                        </td>
                    </tr>
                `;
            }).join('');
        }

        async function processReviewTeacherAbsence(absenceId, decision) {
            const noteInput = document.getElementById(`adminNoteInput_${absenceId}`);
            const notes = noteInput ? noteInput.value.trim() : '';

            const decisionLabel = decision === 'approved' ? 'قبول' : 'رفض';
            if (!confirm(`هل أنت متأكد من ${decisionLabel} إشعار الغياب؟`)) return;

            const fd = new FormData();
            fd.append('action', 'review');
            fd.append('absence_id', String(absenceId));
            fd.append('decision', decision);
            fd.append('admin_notes', notes);

            const { payload } = await postForm('backend/admin_teacher_absences.php', fd);
            if (!payload.success) {
                alert('خطأ: ' + (payload.message || 'فشلت معالجة الإشعار'));
                return;
            }

            setFeedback(adminAbsencesFeedback, payload.message || 'تم تحديث حالة الإشعار.', 'success');
            await loadTeacherAbsencesAdmin();
        }

        if (teacherAbsencesTableBody) {
            teacherAbsencesTableBody.addEventListener('click', (e) => {
                const btn = e.target.closest('[data-review-absence-id]');
                if (!btn) return;
                const absenceId = btn.getAttribute('data-review-absence-id');
                const decision = btn.getAttribute('data-review-decision');
                if (absenceId && decision) {
                    processReviewTeacherAbsence(absenceId, decision);
                }
            });
        }

        if (refreshTeacherAbsencesBtn) {
            refreshTeacherAbsencesBtn.addEventListener('click', loadTeacherAbsencesAdmin);
        }
        if (teacherAbsenceTabBtn) {
            teacherAbsenceTabBtn.addEventListener('click', loadTeacherAbsencesAdmin);
        }
        if (filterStatusAbsence) {
            filterStatusAbsence.addEventListener('change', renderTeacherAbsencesAdminTable);
        }
        if (filterUrgentAbsence) {
            filterUrgentAbsence.addEventListener('change', renderTeacherAbsencesAdminTable);
        }

        try {
            await loadTeacherAbsencesAdmin();
        } catch (errAbsences) {
            console.error("Teacher absences auto-load error:", errAbsences);
        }

        /* ------------------ Teachers management (admin) ------------------ */
        const teachersPendingStatus = document.getElementById('teachersPendingStatus');
        const teachersPendingList = document.getElementById('teachersPendingList');
        const teachersRegisteredList = document.getElementById('teachersRegisteredList');
        const refreshTeachersBtn = document.getElementById('refreshTeachersBtn');
        const teachersTabBtn = document.querySelector('[data-tab-target="teachersTab"]');

        function renderTeacherMeta(email, subject) {
            const parts = [];
            if (email) parts.push(escapeHtml(email));
            if (subject) parts.push(escapeHtml(subject));
            return parts.join(' ▪ ');
        }

        async function loadTeachersList() {
            if (!teachersRegisteredList) return;

            if (teachersPendingStatus) {
                teachersPendingStatus.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> جاري جلب البيانات...';
            }
            teachersRegisteredList.innerHTML = '<p class="teacher-empty muted"><i class="fa-solid fa-spinner fa-spin"></i> جاري التحميل...</p>';
            if (teachersPendingList) {
                teachersPendingList.hidden = true;
                teachersPendingList.innerHTML = '';
            }

            const formData = new FormData();
            formData.append('action', 'list');

            const { status, payload } = await postForm('backend/admin_teachers.php', formData);
            if (!payload.success) {
                const err = escapeHtml(payload.message || 'تعذر جلب البيانات');
                if (teachersPendingStatus) teachersPendingStatus.textContent = 'ملاحظة: ' + err;
                teachersRegisteredList.innerHTML = `<p class="teacher-empty muted">ملاحظة: ${err}</p>`;
                return;
            }

            const allRequests = Array.isArray(payload.data && payload.data.pending) ? payload.data.pending : [];
            const pending = allRequests.filter((r) => String(r.status || 'pending').toLowerCase() === 'pending');
            const teachers = Array.isArray(payload.data && payload.data.teachers) ? payload.data.teachers : [];

            if (teachersPendingStatus) {
                teachersPendingStatus.textContent = pending.length > 0
                    ? `طلبات معلقة: ${pending.length}`
                    : 'لا توجد طلبات معلقة.';
            }

            if (teachersPendingList) {
                if (pending.length > 0) {
                    teachersPendingList.hidden = false;
                    teachersPendingList.innerHTML = pending.map((r) => `
                        <article class="teacher-card pending">
                            <div class="teacher-info">
                                <div class="teacher-name">${escapeHtml(r.full_name || r.email || '—')}</div>
                                <div class="teacher-meta">${renderTeacherMeta(r.email, r.subject_name)}</div>
                                ${r.created_at ? `<div class="teacher-meta" style="font-size:0.85rem;">${escapeHtml(r.created_at)}</div>` : ''}
                            </div>
                            <div class="teacher-actions">
                                <button type="button" class="btn btn-primary" data-action="approve-teacher" data-request-id="${escapeHtml(String(r.id))}">قبول</button>
                                <button type="button" class="btn btn-danger" data-action="reject-teacher" data-request-id="${escapeHtml(String(r.id))}">رفض</button>
                            </div>
                        </article>
                    `).join('');
                } else {
                    teachersPendingList.hidden = true;
                    teachersPendingList.innerHTML = '';
                }
            }

            if (teachers.length === 0) {
                teachersRegisteredList.innerHTML = '<p class="teacher-empty muted">لا يوجد أساتذة مسجّلون بعد.</p>';
                return;
            }

            teachersRegisteredList.innerHTML = teachers.map((t) => `
                <article class="teacher-card active">
                    <div class="teacher-info">
                        <div class="teacher-name">${escapeHtml(t.full_name || '—')}</div>
                        <div class="teacher-meta">${renderTeacherMeta(t.email, t.subject_name)}</div>
                    </div>
                </article>
            `).join('');
        }

        async function approveTeacher(requestId) {
            if (!confirm('هل أنت متأكد من الموافقة على هذا الطلب وإضافة الأستاذ؟')) return;
            const fd = new FormData(); fd.append('action', 'approve'); fd.append('request_id', String(requestId));
            const { status, payload } = await postForm('backend/admin_teachers.php', fd);
            if (!payload.success) {
                alert('خطأ: ' + (payload.message || 'فشل الموافقة'));
                return;
            }
            await loadTeachersList();
            setFeedback(studentTableFeedback, 'تمت الموافقة على طلب الأستاذ.', 'success');
        }

        async function rejectTeacher(requestId, reason) {
            if (!confirm('تأكيد: هل تريد رفض هذا الطلب؟')) return;
            const fd = new FormData(); fd.append('action', 'reject'); fd.append('request_id', String(requestId)); fd.append('reason', String(reason || ''));
            const { status, payload } = await postForm('backend/admin_teachers.php', fd);
            if (!payload.success) {
                alert('خطأ: ' + (payload.message || 'فشل الرفض'));
                return;
            }
            await loadTeachersList();
            setFeedback(studentTableFeedback, 'تم رفض طلب الأستاذ.', 'success');
        }

        function onTeachersPanelClick(ev) {
            const btn = ev.target.closest('[data-action]');
            if (!btn) return;
            const action = btn.getAttribute('data-action');
            const reqId = btn.getAttribute('data-request-id');
            if (action === 'approve-teacher') {
                approveTeacher(reqId);
            } else if (action === 'reject-teacher') {
                const reason = prompt('أدخل سبب الرفض (اختياري)');
                if (reason !== null) {
                    rejectTeacher(reqId, reason);
                }
            }
        }

        if (teachersPendingList) {
            teachersPendingList.addEventListener('click', onTeachersPanelClick);
        }

        if (refreshTeachersBtn) {
            refreshTeachersBtn.addEventListener('click', loadTeachersList);
        }
        if (teachersTabBtn) {
            teachersTabBtn.addEventListener('click', loadTeachersList);
        }

        if (activeRole === "director") {
            try {
                await loadTeachersList();
            } catch (errTeachers) {
                console.error("Teachers list auto-load error:", errTeachers);
            }
        }

        if (showSuccessMessage) {
            setFeedback(studentTableFeedback, "تم تحديث البيانات بنجاح.", "success");
        }
    }

    async function onRoleLoginSubmit(event) {
        event.preventDefault();
        const form = event.currentTarget;
        if (!form) {
            return;
        }

        const selectedRole = normalizeRoleValue(new FormData(form).get("role") || "");
        const feedbackElement = getLoginFeedbackElement(selectedRole, form.id);
        clearLoginFeedbacks();
        clearFeedback(feedbackElement);

        try {
            const { payload } = await postForm(form.action, new FormData(form));
            if (!payload.success) {
                setFeedback(feedbackElement, payload.message || "فشل تسجيل الدخول.", "error");
                return;
            }

            const user = payload.data && payload.data.user ? payload.data.user : null;
            const normalizedUserRole = normalizeRoleValue(user && user.role ? user.role : "");
            if (!user || (normalizedUserRole !== "director" && normalizedUserRole !== "secretary")) {
                await performLogout();
                setFeedback(feedbackElement, "هذا الحساب لا يملك صلاحيات الإدارة.", "error");
                return;
            }

            if (
                selectedRole !== "" &&
                normalizedUserRole !== selectedRole
            ) {
                await performLogout();
                setFeedback(feedbackElement, "الدور المحدد لا يطابق بيانات الحساب.", "error");
                return;
            }

            activeRole = normalizedUserRole;
            setFeedback(feedbackElement, `تم تسجيل الدخول بنجاح كـ ${getRoleLabel(activeRole)}.`, "success");

            // Disable buttons to avoid re-validation errors during transition
            const btns = form.querySelectorAll('button[type="submit"]');
            btns.forEach(b => b.disabled = true);

            // Apply role access rules first so scoped tabs/elements are enabled
            applyRoleAccess(activeRole);

            // Show dashboard immediately before loading data
            setDashboardState(true);

            form.reset();
            if (loginRoleInput) {
                loginRoleInput.value = activeRole;
                syncLoginRoleInputs();
            }
            await loadDashboardData();
            setDefaultTabForRole(activeRole);
        } catch (error) {
            console.error("Login error:", error);
            setFeedback(
                feedbackElement,
                "تعذر الاتصال بالخادم أثناء تسجيل الدخول. تأكد من تشغيل XAMPP/MySQL أو افتح backend/login_diagnostic.php.",
                "error"
            );
        }
    }

    async function onLogoutClick() {
        const roleBeforeLogout = activeRole;
        await performLogout();
        handleUnauthorized();
        clearLoginFeedbacks();
        clearFeedback(studentFeedback);
        clearFeedback(gradeFeedback);
        clearFeedback(attendanceFeedback);
        const logoutFeedbackElement = getLoginFeedbackElement(roleBeforeLogout);
        setFeedback(logoutFeedbackElement, "تم تسجيل الخروج بنجاح.", "success");
    }

    async function onStudentSubmit(event) {
        event.preventDefault();
        clearFeedback(studentFeedback);

        if (!studentForm) {
            return;
        }
        const studentId = String(new FormData(studentForm).get("student_id") || "0");
        if (activeRole === "secretary" && studentId !== "0") {
            setFeedback(studentFeedback, "تعديل بيانات التلاميذ متاح للمدير فقط، يمكنك فقط إضافة تلاميذ جدد.", "error");
            return;
        }

        if (activeRole !== "director" && activeRole !== "secretary") {
            setFeedback(studentFeedback, "ليس لديك صلاحية لإدارة ملفات التلاميذ.", "error");
            return;
        }

        const formData = new FormData(studentForm);
        formData.append("action", "save");

        try {
            const { status, payload } = await postForm(studentForm.action, formData);
            if (!payload.success) {
                if (status === 403) {
                    handleUnauthorized();
                    return;
                }

                setFeedback(studentFeedback, payload.message || "تعذر حفظ الطالب.", "error");
                return;
            }

            setFeedback(studentFeedback, payload.message || "تم حفظ الطالب بنجاح.", "success");
            resetStudentForm();
            await loadDashboardData(true);
        } catch (error) {
            console.error("Student save error:", error);
            setFeedback(studentFeedback, "وقع خطأ أثناء حفظ الطالب.", "error");
        }
    }

    async function onGradeSubmit(event) {
        event.preventDefault();
        clearFeedback(gradeFeedback);

        if (!gradeForm) {
            return;
        }

        const allowUnknownCode = activeRole === "secretary" || studentsCache.length === 0;
        const matchedStudent = syncStudentCodeSelection({
            codeInput: gradeStudentCodeInput,
            idInput: gradeStudentIdInput,
            hintElement: gradeStudentHint,
            defaultHint: gradeCodeHintDefault,
            normalizeInput: true,
            allowUnknownCode,
        });
        const normalizedCode = normalizeStudentCode(gradeStudentCodeInput ? gradeStudentCodeInput.value : "");
        if (!matchedStudent && normalizedCode === "") {
            setFeedback(gradeFeedback, "المرجو إدخال كود طالب صحيح قبل إضافة النقطة.", "error");
            return;
        }

        const formData = new FormData(gradeForm);
        if (matchedStudent && Number(matchedStudent.id) > 0) {
            formData.set("student_id", String(matchedStudent.id));
        } else if (normalizedCode !== "") {
            formData.set("student_code", normalizedCode);
            formData.delete("student_id");
        }
        formData.append("action", "add_grade");

        try {
            const { status, payload } = await postForm(gradeForm.action, formData);
            if (!payload.success) {
                if (status === 403) {
                    handleUnauthorized();
                    return;
                }

                setFeedback(gradeFeedback, payload.message || "تعذر إضافة النقطة.", "error");
                return;
            }

            setFeedback(gradeFeedback, payload.message || "تمت إضافة النقطة بنجاح.", "success");
            gradeForm.reset();
            resetStudentCodeSelection(
                gradeStudentCodeInput,
                gradeStudentIdInput,
                gradeStudentHint,
                gradeCodeHintDefault
            );
            await loadDashboardData();
        } catch (error) {
            console.error("Grade save error:", error);
            setFeedback(gradeFeedback, "وقع خطأ أثناء إضافة النقطة.", "error");
        }
    }

    async function onAttendanceSubmit(event) {
        event.preventDefault();
        clearFeedback(attendanceFeedback);

        if (!attendanceForm) {
            return;
        }

        const allowUnknownCode = activeRole === "secretary" || studentsCache.length === 0;
        const matchedStudent = syncStudentCodeSelection({
            codeInput: attendanceStudentCodeInput,
            idInput: attendanceStudentIdInput,
            hintElement: attendanceStudentHint,
            defaultHint: attendanceCodeHintDefault,
            normalizeInput: true,
            allowUnknownCode,
        });
        const normalizedCode = normalizeStudentCode(
            attendanceStudentCodeInput ? attendanceStudentCodeInput.value : ""
        );
        if (!matchedStudent && normalizedCode === "") {
            setFeedback(attendanceFeedback, "المرجو إدخال كود طالب صحيح قبل تسجيل الغياب.", "error");
            return;
        }

        const formData = new FormData(attendanceForm);
        if (matchedStudent && Number(matchedStudent.id) > 0) {
            formData.set("student_id", String(matchedStudent.id));
        } else if (normalizedCode !== "") {
            formData.set("student_code", normalizedCode);
            formData.delete("student_id");
        }
        formData.append("action", "add_attendance");

        try {
            const { status, payload } = await postForm(attendanceForm.action, formData);
            if (!payload.success) {
                if (status === 403) {
                    handleUnauthorized();
                    return;
                }

                setFeedback(attendanceFeedback, payload.message || "تعذر تسجيل الغياب.", "error");
                return;
            }

            setFeedback(attendanceFeedback, payload.message || "تم تسجيل الغياب بنجاح.", "success");
            attendanceForm.reset();
            resetStudentCodeSelection(
                attendanceStudentCodeInput,
                attendanceStudentIdInput,
                attendanceStudentHint,
                attendanceCodeHintDefault
            );
            await loadDashboardData();
        } catch (error) {
            console.error("Attendance save error:", error);
            setFeedback(attendanceFeedback, "وقع خطأ أثناء تسجيل الغياب.", "error");
        }
    }

    async function onTableAction(event) {
        if (activeRole !== "director") {
            return;
        }

        const target = event.target.closest("button[data-action][data-id]");
        if (!target) {
            return;
        }

        const studentId = Number(target.getAttribute("data-id") || 0);
        if (!Number.isFinite(studentId) || studentId <= 0) {
            return;
        }

        const action = target.getAttribute("data-action");
        const isRequest = target.getAttribute("data-is-request") || "no";
        const selectedStudent = studentsCache.find((student) =>
            Number(student.id) === studentId && (student.is_request || "no") === isRequest
        );
        if (!selectedStudent) {
            return;
        }

        if (action === "edit") {
            fillStudentForm(selectedStudent);
            clearFeedback(studentFeedback);
            return;
        }

        if (action === "verify") {
            const verifyStatus = target.getAttribute("data-verify-status");
            const isApprove = verifyStatus === "approved";
            const confirmMsg = isApprove ? "تأكيد قبول تسجيل الطالب؟" : "تأكيد رفض تسجيل الطالب؟";
            if (!confirm(confirmMsg)) return;

            const formData = new FormData();
            formData.append("action", "verify");
            formData.append("student_id", String(studentId));
            formData.append("status", verifyStatus);
            formData.append("is_request", isRequest);

            try {
                const { payload } = await postForm("backend/admin_students.php", formData);
                if (payload.success) {
                    setFeedback(studentTableFeedback, payload.message, "success");
                    await loadDashboardData();
                } else {
                    setFeedback(studentTableFeedback, payload.message || "فشلت عملية التحقق.", "error");
                }
            } catch (error) {
                console.error("Verification error:", error);
                setFeedback(studentTableFeedback, "وقع خطأ أثناء عملية التحقق.", "error");
            }
            return;
        }

        if (action !== "delete") {
            return;
        }

        const confirmed = window.confirm(
            `تأكيد حذف الطالب ${selectedStudent.first_name || ""} ${selectedStudent.last_name || ""}؟`
        );
        if (!confirmed) {
            return;
        }

        clearFeedback(studentTableFeedback);
        const formData = new FormData();
        formData.append("action", "delete");
        formData.append("student_id", String(studentId));
        formData.append("is_request", isRequest);

        try {
            const { status, payload } = await postForm("backend/admin_students.php", formData);
            if (!payload.success) {
                if (status === 403) {
                    handleUnauthorized();
                    return;
                }

                setFeedback(studentTableFeedback, payload.message || "تعذر حذف الطالب.", "error");
                return;
            }

            setFeedback(studentTableFeedback, payload.message || "تم حذف الطالب بنجاح.", "success");
            resetStudentForm();
            await loadDashboardData();
        } catch (error) {
            console.error("Student delete error:", error);
            setFeedback(studentTableFeedback, "وقع خطأ أثناء حذف الطالب.", "error");
        }
    }

    async function onLessonSubmit(event) {
        event.preventDefault();
        clearFeedback(lessonFeedback);
        if (!lessonForm) {
            return;
        }

        const formData = new FormData(lessonForm);
        formData.append("action", "save");

        try {
            const { status, payload } = await postForm(lessonForm.action, formData);
            if (!payload.success) {
                if (status === 403) {
                    handleUnauthorized();
                    return;
                }
                setFeedback(lessonFeedback, payload.message || "تعذر حفظ الدرس.", "error");
                return;
            }

            setFeedback(lessonFeedback, payload.message || "تم حفظ الدرس بنجاح.", "success");
            resetLessonForm();
            await loadLessonsData(true);
        } catch (error) {
            console.error("Lesson save error:", error);
            setFeedback(lessonFeedback, "وقع خطأ أثناء حفظ الدرس.", "error");
        }
    }

    async function onLessonTableAction(event) {
        const target = event.target.closest("button[data-lesson-action][data-lesson-id]");
        if (!target) {
            return;
        }

        const lessonId = Number(target.getAttribute("data-lesson-id") || 0);
        if (!Number.isFinite(lessonId) || lessonId <= 0) {
            return;
        }

        const action = String(target.getAttribute("data-lesson-action") || "");
        const selectedLesson = lessonsCache.find((lesson) => Number(lesson.id) === lessonId);
        if (!selectedLesson) {
            return;
        }

        if (action === "edit") {
            fillLessonForm(selectedLesson);
            clearFeedback(lessonFeedback);
            return;
        }

        if (action !== "delete") {
            return;
        }

        const confirmed = window.confirm(`تأكيد حذف الدرس "${selectedLesson.title || ""}" ؟`);
        if (!confirmed) {
            return;
        }

        const formData = new FormData();
        formData.append("action", "delete");
        formData.append("lesson_id", String(lessonId));

        try {
            const { status, payload } = await postForm("backend/admin_lessons.php", formData);
            if (!payload.success) {
                if (status === 403) {
                    handleUnauthorized();
                    return;
                }
                setFeedback(lessonsTableFeedback, payload.message || "تعذر حذف الدرس.", "error");
                return;
            }

            setFeedback(lessonsTableFeedback, payload.message || "تم حذف الدرس بنجاح.", "success");
            resetLessonForm();
            await loadLessonsData();
        } catch (error) {
            console.error("Lesson delete error:", error);
            setFeedback(lessonsTableFeedback, "وقع خطأ أثناء حذف الدرس.", "error");
        }
    }

    function renderGradesView(grades) {
        if (!gradesViewResult) {
            return;
        }

        if (!Array.isArray(grades) || grades.length === 0) {
            gradesViewResult.innerHTML = '<p class="muted">لا توجد نقاط لهذا التلميذ.</p>';
            return;
        }

        let weightedSum = 0;
        let coefficientSum = 0;

        const rows = grades
            .map((grade) => {
                const continuous = Number.parseFloat(String(grade.continuous_score || 0));
                const exam = Number.parseFloat(String(grade.exam_score || 0));
                const coefficient = Number.parseFloat(String(grade.coefficient || 1));
                const average = (continuous * 0.4) + (exam * 0.6);
                weightedSum += average * coefficient;
                coefficientSum += coefficient;

                return `
                    <tr>
                        <td>${escapeHtml(grade.subject_name || "")}</td>
                        <td>${continuous.toFixed(2)}</td>
                        <td>${exam.toFixed(2)}</td>
                        <td>${coefficient.toFixed(2)}</td>
                        <td>${average.toFixed(2)}</td>
                    </tr>
                `;
            })
            .join("");

        const globalAverage = coefficientSum > 0 ? weightedSum / coefficientSum : 0;

        gradesViewResult.innerHTML = `
            <div class="summary-box">
                <span class="chip">عدد المواد: ${grades.length}</span>
                <span class="chip">مجموع المعاملات: ${coefficientSum.toFixed(2)}</span>
                <span class="chip">المعدل العام: ${globalAverage.toFixed(2)}</span>
            </div>
            <table class="result-table compact-result-table">
                <thead>
                    <tr>
                        <th>المادة</th>
                        <th>المراقبة</th>
                        <th>الامتحان</th>
                        <th>المعامل</th>
                        <th>المعدل</th>
                    </tr>
                </thead>
                <tbody>${rows}</tbody>
            </table>
        `;
    }

    function renderAttendanceView(records) {
        if (!attendanceViewResult) {
            return;
        }

        if (!Array.isArray(records) || records.length === 0) {
            attendanceViewResult.innerHTML = '<p class="muted">لا توجد غيابات لهذا التلميذ.</p>';
            return;
        }

        const justifiedCount = records.filter((item) => Number(item.justified) === 1).length;
        const unjustifiedCount = records.length - justifiedCount;

        const rows = records
            .map((record) => {
                const statusText = Number(record.justified) === 1 ? "مبرر" : "غير مبرر";
                return `
                    <tr>
                        <td>${escapeHtml(record.absence_date || "")}</td>
                        <td>${escapeHtml(record.session_label || "")}</td>
                        <td>${escapeHtml(record.absence_subject || "-")}</td>
                        <td>${statusText}</td>
                        <td>${escapeHtml(record.notes || "-")}</td>
                    </tr>
                `;
            })
            .join("");

        attendanceViewResult.innerHTML = `
            <div class="summary-box">
                <span class="chip">إجمالي الغياب: ${records.length}</span>
                <span class="chip">مبرر: ${justifiedCount}</span>
                <span class="chip">غير مبرر: ${unjustifiedCount}</span>
            </div>
            <table class="result-table compact-result-table">
                <thead>
                    <tr>
                        <th>التاريخ</th>
                        <th>الحصة</th>
                        <th>المادة</th>
                        <th>الحالة</th>
                        <th>ملاحظة</th>
                    </tr>
                </thead>
                <tbody>${rows}</tbody>
            </table>
        `;
    }

    async function onViewGrades() {
        if (activeRole !== "director") {
            return;
        }
        if (!gradesViewStudent) {
            return;
        }

        const studentId = String(gradesViewStudent.value || "");
        if (studentId === "") {
            if (gradesViewResult) {
                gradesViewResult.innerHTML = '<p class="muted">الرجاء اختيار تلميذ.</p>';
            }
            return;
        }

        if (gradesViewResult) {
            gradesViewResult.innerHTML = '<p class="muted"><i class="fa-solid fa-spinner fa-spin"></i> جاري تحميل النقط...</p>';
        }

        const formData = new FormData();
        formData.append("action", "get_grades");
        formData.append("student_id", studentId);
        if (gradesSemesterFilter && gradesSemesterFilter.value) {
            formData.append("semester", String(gradesSemesterFilter.value));
        }

        try {
            const { status, payload } = await postForm("backend/admin_records.php", formData);
            if (!payload.success) {
                if (status === 403) {
                    handleUnauthorized();
                    return;
                }

                if (gradesViewResult) {
                    gradesViewResult.innerHTML = `<p class="muted">${escapeHtml(payload.message || "تعذر جلب النقط.")}</p>`;
                }
                return;
            }

            renderGradesView(payload.data || []);
        } catch (error) {
            console.error("View grades error:", error);
            if (gradesViewResult) {
                gradesViewResult.innerHTML = '<p class="muted">وقع خطأ أثناء جلب النقط.</p>';
            }
        }
    }

    async function onViewAttendance() {
        if (activeRole !== "director") {
            return;
        }
        if (!attendanceViewStudent) {
            return;
        }

        const studentId = String(attendanceViewStudent.value || "");
        if (studentId === "") {
            if (attendanceViewResult) {
                attendanceViewResult.innerHTML = '<p class="muted">الرجاء اختيار تلميذ.</p>';
            }
            return;
        }

        if (attendanceViewResult) {
            attendanceViewResult.innerHTML = '<p class="muted"><i class="fa-solid fa-spinner fa-spin"></i> جاري تحميل الغياب...</p>';
        }

        const formData = new FormData();
        formData.append("action", "get_attendance");
        formData.append("student_id", studentId);

        try {
            const { status, payload } = await postForm("backend/admin_records.php", formData);
            if (!payload.success) {
                if (status === 403) {
                    handleUnauthorized();
                    return;
                }

                if (attendanceViewResult) {
                    attendanceViewResult.innerHTML = `<p class="muted">${escapeHtml(payload.message || "تعذر جلب الغياب.")}</p>`;
                }
                return;
            }

            renderAttendanceView(payload.data || []);
        } catch (error) {
            console.error("View attendance error:", error);
            if (attendanceViewResult) {
                attendanceViewResult.innerHTML = '<p class="muted">وقع خطأ أثناء جلب الغياب.</p>';
            }
        }
    }

    function initTabs() {
        if (tabButtons.length === 0) {
            return;
        }

        tabButtons.forEach((button) => {
            button.addEventListener("click", () => {
                const target = button.getAttribute("data-tab-target");
                if (!target) {
                    return;
                }
                setActiveTab(target);
                if (target === "studentsListTab" || target === "studentsTab") {
                    applyStudentsFiltersAndRender();
                }
            });
        });

        const firstVisibleButton = tabButtons.find((button) => !button.hidden) || tabButtons[0];
        const firstTab = firstVisibleButton ? firstVisibleButton.getAttribute("data-tab-target") : "";
        if (firstTab) {
            setActiveTab(firstTab);
        }
    }

    function initPasswordToggles() {
        const toggleButtons = Array.from(document.querySelectorAll(".password-toggle"));
        toggleButtons.forEach((toggleButton) => {
            const inputId =
                toggleButton.getAttribute("data-toggle-target") ||
                toggleButton.getAttribute("aria-controls") ||
                "";
            if (inputId === "") {
                return;
            }

            const passwordInput = document.getElementById(inputId);
            if (!passwordInput) {
                return;
            }

            toggleButton.addEventListener("click", () => {
                const type = passwordInput.getAttribute("type") === "password" ? "text" : "password";
                passwordInput.setAttribute("type", type);

                const icon = toggleButton.querySelector("i");
                if (icon) {
                    icon.classList.toggle("fa-eye", type === "password");
                    icon.classList.toggle("fa-eye-slash", type === "text");
                }

                const isVisible = type === "text";
                toggleButton.classList.toggle("is-visible", isVisible);
                toggleButton.setAttribute("aria-pressed", isVisible ? "true" : "false");
                toggleButton.setAttribute("aria-label", isVisible ? "إخفاء الكود" : "إظهار الكود");
            });
        });
    }

    if (loginForms.length > 0) {
        loginForms.forEach((form) => {
            form.addEventListener("submit", onRoleLoginSubmit);
        });
    }

    if (loginRoleInput) {
        loginRoleInput.addEventListener("change", () => {
            syncLoginRoleInputs();
            clearLoginFeedbacks();
        });
    }

    if (logoutBtn) {
        logoutBtn.addEventListener("click", onLogoutClick);
    }

    if (studentForm) {
        studentForm.addEventListener("submit", onStudentSubmit);
    }

    if (studentResetBtn) {
        studentResetBtn.addEventListener("click", () => {
            clearFeedback(studentFeedback);
            resetStudentForm();
        });
    }

    if (studentsTableBody) {
        studentsTableBody.addEventListener("click", onTableAction);
    }

    if (levelFilter) {
        levelFilter.addEventListener("change", applyStudentsFiltersAndRender);
    }

    if (studentsSearch) {
        studentsSearch.addEventListener("input", applyStudentsFiltersAndRender);
    }

    if (gradeStudentCodeInput) {
        gradeStudentCodeInput.addEventListener("input", () => {
            syncStudentCodeSelection({
                codeInput: gradeStudentCodeInput,
                idInput: gradeStudentIdInput,
                hintElement: gradeStudentHint,
                defaultHint: gradeCodeHintDefault,
                allowUnknownCode: activeRole === "secretary" || studentsCache.length === 0,
            });
        });
        gradeStudentCodeInput.addEventListener("blur", () => {
            syncStudentCodeSelection({
                codeInput: gradeStudentCodeInput,
                idInput: gradeStudentIdInput,
                hintElement: gradeStudentHint,
                defaultHint: gradeCodeHintDefault,
                normalizeInput: true,
                allowUnknownCode: activeRole === "secretary" || studentsCache.length === 0,
            });
        });
    }

    if (attendanceStudentCodeInput) {
        attendanceStudentCodeInput.addEventListener("input", () => {
            syncStudentCodeSelection({
                codeInput: attendanceStudentCodeInput,
                idInput: attendanceStudentIdInput,
                hintElement: attendanceStudentHint,
                defaultHint: attendanceCodeHintDefault,
                allowUnknownCode: activeRole === "secretary" || studentsCache.length === 0,
            });
        });
        attendanceStudentCodeInput.addEventListener("blur", () => {
            syncStudentCodeSelection({
                codeInput: attendanceStudentCodeInput,
                idInput: attendanceStudentIdInput,
                hintElement: attendanceStudentHint,
                defaultHint: attendanceCodeHintDefault,
                normalizeInput: true,
                allowUnknownCode: activeRole === "secretary" || studentsCache.length === 0,
            });
        });
    }

    if (gradeForm) {
        gradeForm.addEventListener("submit", function (event) {
            // معالجة المادة: إذا اختار "أخرى"، نأخذ النص من input، وإلا من select
            var select = document.getElementById('subject_name_select');
            var otherInput = document.getElementById('subject_name_other_grades');
            if (select && otherInput) {
                if (select.value === 'أخرى') {
                    // إذا اختار أخرى، نأخذ النص من input
                    // نضبط قيمة input المخفي subject_name
                    otherInput.name = 'subject_name';
                    select.name = 'subject_name_select';
                } else {
                    // إذا اختار مادة من القائمة، نرسلها باسم subject_name
                    select.name = 'subject_name';
                    otherInput.name = 'subject_name_other_grades';
                    otherInput.value = '';
                }
            }
            onGradeSubmit(event);
        });
    }

    if (attendanceForm) {
        attendanceForm.addEventListener("submit", function (event) {
            var select = document.getElementById('absence_subject_select');
            var otherInput = document.getElementById('absence_subject_other_attendance');
            if (select && otherInput) {
                if (select.value === 'أخرى') {
                    otherInput.name = 'absence_subject';
                    select.name = 'absence_subject_select';
                } else {
                    select.name = 'absence_subject';
                    otherInput.name = 'absence_subject_other_attendance';
                    otherInput.value = '';
                }
            }
            onAttendanceSubmit(event);
        });
    }

    if (lessonForm) {
        lessonForm.addEventListener("submit", onLessonSubmit);
    }

    if (lessonResetBtn) {
        lessonResetBtn.addEventListener("click", () => {
            clearFeedback(lessonFeedback);
            resetLessonForm();
        });
    }

    if (lessonsTableBody) {
        lessonsTableBody.addEventListener("click", onLessonTableAction);
    }

    if (viewGradesBtn) {
        viewGradesBtn.addEventListener("click", onViewGrades);
    }

    if (viewAttendanceBtn) {
        viewAttendanceBtn.addEventListener("click", onViewAttendance);
    }

    initTabs();
    initPasswordToggles();
    syncLoginRoleInputs();
    checkDatabaseConnection();

    // Check for existing admin session and restore dashboard if logged in
    checkAndRestoreSession();

    // ============================================================
    // PROMOTION ANNUELLE - GESTION DU TAB
    // ============================================================
    const promotionPreviewBtn = document.getElementById("promotionPreviewBtn");
    const promotionExecuteBtn = document.getElementById("promotionExecuteBtn");
    const promotionHistoryBtn = document.getElementById("promotionHistoryBtn");
    const promotionFeedback = document.getElementById("promotionFeedback");
    const promotionStatsCards = document.getElementById("promotionStatsCards");
    const promotionPreviewBlock = document.getElementById("promotionPreviewBlock");
    const promotionPreviewBody = document.getElementById("promotionPreviewBody");
    const promoAnneeLabel = document.getElementById("promoAnneeLabel");
    const promoFilterResult = document.getElementById("promoFilterResult");
    const promoFilterLevel = document.getElementById("promoFilterLevel");
    const promotionRepartitionBlock = document.getElementById("promotionRepartitionBlock");
    const promotionRepartitionContent = document.getElementById("promotionRepartitionContent");
    const promotionHistoryBlock = document.getElementById("promotionHistoryBlock");
    const promotionHistoryBody = document.getElementById("promotionHistoryBody");
    const promoStatTotal = document.getElementById("promoStatTotal");
    const promoStatAdmis = document.getElementById("promoStatAdmis");
    const promoStatRedoublants = document.getElementById("promoStatRedoublants");
    const promoStatClasses = document.getElementById("promoStatClasses");

    let promotionPreviewData = [];
    let promotionRepartitionData = null;

    function showPromotionFeedback(message, type) {
        if (!promotionFeedback) return;
        promotionFeedback.className = 'feedback ' + type;
        promotionFeedback.textContent = message;
        promotionFeedback.hidden = false;
    }

    function clearPromotionFeedback() {
        if (!promotionFeedback) return;
        promotionFeedback.className = 'feedback';
        promotionFeedback.textContent = '';
        promotionFeedback.hidden = true;
    }

    function hideAllPromotionBlocks() {
        if (promotionStatsCards) promotionStatsCards.hidden = true;
        if (promotionPreviewBlock) promotionPreviewBlock.hidden = true;
        if (promotionRepartitionBlock) promotionRepartitionBlock.hidden = true;
        if (promotionHistoryBlock) promotionHistoryBlock.hidden = true;
    }

    function populatePromoLevelFilter() {
        if (!promoFilterLevel) return;
        const levels = new Set();
        promotionPreviewData.forEach(function (item) {
            if (item.current_level) levels.add(item.current_level);
        });
        promoFilterLevel.innerHTML = '<option value="">كل المستويات</option>' +
            Array.from(levels).sort().map(function (l) {
                return '<option value="' + escapeHtml(l) + '">' + escapeHtml(l) + '</option>';
            }).join('');
    }

    function renderPromotionPreview() {
        if (!promotionPreviewBody) return;

        var filtered = promotionPreviewData.slice();
        var filterResult = promoFilterResult ? promoFilterResult.value : '';
        var filterLevel = promoFilterLevel ? promoFilterLevel.value : '';

        if (filterResult !== '') {
            filtered = filtered.filter(function (item) { return item.resultat === filterResult; });
        }
        if (filterLevel !== '') {
            filtered = filtered.filter(function (item) { return item.current_level === filterLevel; });
        }

        if (filtered.length === 0) {
            promotionPreviewBody.innerHTML = '<tr><td colspan="9" class="muted">لا توجد نتائج مطابقة.</td></tr>';
            return;
        }

        promotionPreviewBody.innerHTML = filtered.map(function (item) {
            var resultatLabel, resultatClass;
            if (item.resultat === 'admis') {
                resultatLabel = '✅ ناجح';
                resultatClass = 'status-pill is-success';
            } else if (item.resultat === 'redoublant') {
                resultatLabel = '🔄 راسب';
                resultatClass = 'status-pill is-error';
            } else {
                resultatLabel = '⚠ بدون نقط';
                resultatClass = 'status-pill is-warning';
            }

            return '<tr>' +
                '<td>' + escapeHtml(item.student_code || '') + '</td>' +
                '<td>' + escapeHtml(item.full_name || '') + '</td>' +
                '<td>' + escapeHtml(item.current_level || '') + '</td>' +
                '<td>' + (item.moyenne_s1 !== null ? item.moyenne_s1 : '-') + '</td>' +
                '<td>' + (item.moyenne_s2 !== null ? item.moyenne_s2 : '-') + '</td>' +
                '<td><strong>' + (item.moyenne_gen !== null ? item.moyenne_gen : '-') + '</strong></td>' +
                '<td>' + escapeHtml(item.mention || '-') + '</td>' +
                '<td><span class="' + resultatClass + '">' + resultatLabel + '</span></td>' +
                '<td>' + escapeHtml(item.next_level || (item.resultat === 'admis' ? 'متخرج' : item.current_level)) + '</td>' +
                '</tr>';
        }).join('');
    }

    function renderPromotionRepartition(repartition) {
        if (!promotionRepartitionContent) return;

        if (!repartition || Object.keys(repartition).length === 0) {
            promotionRepartitionContent.innerHTML = '<p class="muted">لا توجد أقسام لعرضها.</p>';
            return;
        }

        var html = '';
        Object.keys(repartition).sort().forEach(function (level) {
            var classes = repartition[level];
            html += '<div class="repartition-level-block">';
            html += '<h4><i class="fa-solid fa-layer-group"></i> ' + escapeHtml(level) + ' (' + classes.length + ' أقسام)</h4>';
            html += '<div class="repartition-classes-grid">';
            classes.forEach(function (cls) {
                html += '<div class="repartition-class-card">';
                html += '<div class="class-card-header">' + escapeHtml(cls.nom_classe) + '</div>';
                html += '<div class="class-card-body">';
                html += '<span class="class-effectif"><i class="fa-solid fa-users"></i> ' + cls.effectif + ' تلميذ</span>';
                html += '</div>';
                html += '</div>';
            });
            html += '</div></div>';
        });
        promotionRepartitionContent.innerHTML = html;
    }

    function renderPromotionHistory(promotions) {
        if (!promotionHistoryBody) return;

        if (!Array.isArray(promotions) || promotions.length === 0) {
            promotionHistoryBody.innerHTML = '<tr><td colspan="8" class="muted">لا توجد ترحيلات سابقة.</td></tr>';
            return;
        }

        promotionHistoryBody.innerHTML = promotions.map(function (p, idx) {
            return '<tr>' +
                '<td>' + (idx + 1) + '</td>' +
                '<td>' + escapeHtml(p.annee_scolaire_from || '') + '</td>' +
                '<td>' + escapeHtml(p.annee_scolaire_to || '') + '</td>' +
                '<td>' + (p.total_eleves || 0) + '</td>' +
                '<td style="color:green;">' + (p.total_admis || 0) + '</td>' +
                '<td style="color:red;">' + (p.total_redoublants || 0) + '</td>' +
                '<td>' + (p.total_classes_creees || 0) + '</td>' +
                '<td>' + escapeHtml(p.date_promotion || p.created_at || '') + '</td>' +
                '</tr>';
        }).join('');
    }

    async function promotionPreview() {
        clearPromotionFeedback();
        hideAllPromotionBlocks();
        showPromotionFeedback('<i class="fa-solid fa-spinner fa-spin"></i> جاري حساب المعدلات ومعاينة النتائج...', 'info');

        try {
            var formData = new FormData();
            formData.append('action', 'preview');
            var result = await postForm('backend/promotion_annuelle.php', formData);
            var payload = result.payload;

            if (!payload.success) {
                showPromotionFeedback(payload.message || 'تعذرت المعاينة.', 'error');
                return;
            }

            promotionPreviewData = payload.data.preview || [];
            promotionRepartitionData = null;

            if (promoAnneeLabel) promoAnneeLabel.textContent = payload.data.annee_courante || '';
            populatePromoLevelFilter();
            renderPromotionPreview();

            if (promotionStatsCards) promotionStatsCards.hidden = false;
            if (promoStatTotal) promoStatTotal.textContent = payload.data.total_eleves || 0;

            var stats = payload.data.stats_par_niveau || {};
            var totalAdmis = 0, totalRedoublants = 0;
            Object.values(stats).forEach(function (s) {
                totalAdmis += s.admis || 0;
                totalRedoublants += s.redoublants || 0;
            });
            if (promoStatAdmis) promoStatAdmis.textContent = totalAdmis;
            if (promoStatRedoublants) promoStatRedoublants.textContent = totalRedoublants;
            if (promoStatClasses) promoStatClasses.textContent = '-';

            if (promotionPreviewBlock) promotionPreviewBlock.hidden = false;
            if (promotionRepartitionBlock) promotionRepartitionBlock.hidden = true;
            if (promotionHistoryBlock) promotionHistoryBlock.hidden = true;

            showPromotionFeedback('تمت المعاينة بنجاح. راجع النتائج ثم اضغط على "لانسي الترحيل" للتأكيد.', 'success');
        } catch (e) {
            showPromotionFeedback('خطأ أثناء المعاينة: ' + e.message, 'error');
        }
    }

    async function promotionExecute() {
        if (promotionPreviewData.length === 0) {
            showPromotionFeedback('الرجاء معاينة النتائج أولا قبل تنفيذ الترحيل.', 'warning');
            return;
        }

        if (!confirm('⚠️ تنبيه هام:\n\nسيتم تنفيذ الترحيل السنوي وحفظ جميع النتائج في السجل التاريخي.\n\n- سيتم إنشاء الأقسام الجديدة تلقائياً\n- سيتم توزيع التلاميذ (30 كحد أقصى لكل قسم)\n- لا يمكن التراجع عن هذه العملية\n\nهل أنت متأكد من المتابعة؟')) {
            return;
        }

        if (!confirm('تأكيد نهائي: هل تريد فعلاً تنفيذ الترحيل السنوي؟')) return;

        clearPromotionFeedback();
        showPromotionFeedback('<i class="fa-solid fa-spinner fa-spin"></i> جاري تنفيذ الترحيل السنوي... يرجى الانتظار.', 'info');

        try {
            var formData = new FormData();
            formData.append('action', 'execute');
            var result = await postForm('backend/promotion_annuelle.php', formData);
            var payload = result.payload;

            if (!payload.success) {
                showPromotionFeedback(payload.message || 'فشل الترحيل.', 'error');
                return;
            }

            // Update stats
            if (promoStatTotal) promoStatTotal.textContent = payload.data.total_eleves || 0;
            if (promoStatAdmis) promoStatAdmis.textContent = payload.data.total_admis || 0;
            if (promoStatRedoublants) promoStatRedoublants.textContent = payload.data.total_redoublants || 0;
            if (promoStatClasses) promoStatClasses.textContent = payload.data.total_classes || 0;

            // Show repartition
            if (payload.data.repartition) {
                renderPromotionRepartition(payload.data.repartition);
                if (promotionRepartitionBlock) promotionRepartitionBlock.hidden = false;
            }

            showPromotionFeedback('✅ ' + (payload.message || 'تم الترحيل السنوي بنجاح!') +
                ' | السنة: ' + (payload.data.annee_from || '') + ' → ' + (payload.data.annee_to || '') +
                ' | الناجحون: ' + (payload.data.total_admis || 0) +
                ' | الراسبون: ' + (payload.data.total_redoublants || 0) +
                ' | المتخرجون: ' + (payload.data.total_diplomes || 0), 'success');

            // Refresh student data
            await loadDashboardData();
        } catch (e) {
            showPromotionFeedback('خطأ أثناء الترحيل: ' + e.message, 'error');
        }
    }

    async function promotionHistory() {
        clearPromotionFeedback();
        hideAllPromotionBlocks();
        if (promotionHistoryBlock) promotionHistoryBlock.hidden = false;
        if (promotionHistoryBody) promotionHistoryBody.innerHTML = '<tr><td colspan="8" class="muted"><i class="fa-solid fa-spinner fa-spin"></i> جاري التحميل...</td></tr>';

        try {
            var formData = new FormData();
            formData.append('action', 'history');
            var result = await postForm('backend/promotion_annuelle.php', formData);
            var payload = result.payload;

            if (!payload.success) {
                showPromotionFeedback(payload.message || 'تعذر جلب السجل.', 'error');
                return;
            }

            renderPromotionHistory(payload.data.promotions || []);
        } catch (e) {
            showPromotionFeedback('خطأ: ' + e.message, 'error');
        }
    }

    if (promotionPreviewBtn) promotionPreviewBtn.addEventListener('click', promotionPreview);
    if (promotionExecuteBtn) promotionExecuteBtn.addEventListener('click', promotionExecute);
    if (promotionHistoryBtn) promotionHistoryBtn.addEventListener('click', promotionHistory);

    if (promoFilterResult) promoFilterResult.addEventListener('change', renderPromotionPreview);
    if (promoFilterLevel) promoFilterLevel.addEventListener('change', renderPromotionPreview);

    // Show promotion tab button for director role (it's hidden by default in HTML)
    var promotionTabBtn = document.querySelector('[data-tab-target="promotionTab"]');
    if (promotionTabBtn && !promotionTabBtn.hasAttribute('data-role-only')) {
        // Only show for director
        promotionTabBtn.setAttribute('data-role-only', 'director');
        promotionTabBtn.hidden = true; // Will be shown by applyRoleAccess
    }

    // Check for existing admin session and restore dashboard if logged in
    async function checkAndRestoreSession() {
        try {
            await loadDashboardData();
            if (activeRole) {
                setDefaultTabForRole(activeRole);
            }
        } catch (error) {
            // Ignore: user is simply not logged in yet.
        }
    }
});
