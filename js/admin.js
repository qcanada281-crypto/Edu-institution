window.__ADMIN_JS_LOADED__ = true;

document.addEventListener("DOMContentLoaded", () => {
    const loginPanel = document.getElementById("adminLoginPanel");
    const dashboardPanel = document.getElementById("adminDashboard");
    const loginForms = Array.from(document.querySelectorAll(".role-login-form"));
    const legacyLoginForm = document.getElementById("adminLoginForm");
    if (legacyLoginForm && !loginForms.includes(legacyLoginForm)) {
        loginForms.push(legacyLoginForm);
    }

    async function loadTeacherRequests() {
        if (!teacherRequestsTableBody || activeRole !== "director") {
            return;
        }

        teacherRequestsTableBody.innerHTML =
            '<tr><td colspan="6" style="text-align:center;"><i class="fa-solid fa-spinner fa-spin"></i> جاري تحميل الطلبات...</td></tr>';

        const formData = new FormData();
        formData.append("action", "list_requests");

        try {
            const { status, payload } = await postForm("backend/admin_teachers.php", formData);
            if (!payload.success) {
                if (status === 403) {
                    handleUnauthorized();
                    return;
                }
                renderTeacherRequestsTable([]);
                if (teacherRequestsTableFeedback) {
                    setFeedback(
                        teacherRequestsTableFeedback,
                        payload.message || "تعذر جلب طلبات الأساتذة.",
                        "error"
                    );
                }
                return;
            }

            const list = payload.data && Array.isArray(payload.data.requests) ? payload.data.requests : [];
            renderTeacherRequestsTable(list);
            clearFeedback(teacherRequestsTableFeedback);
        } catch (error) {
            console.error("Teacher requests load error:", error);
            renderTeacherRequestsTable([]);
            setFeedback(teacherRequestsTableFeedback, "وقع خطأ أثناء جلب طلبات الأساتذة.", "error");
        }
    }

    function renderTeacherRequestsTable(requests) {
        if (!teacherRequestsTableBody) {
            return;
        }

        const items = Array.isArray(requests) ? requests : [];
        if (items.length === 0) {
            teacherRequestsTableBody.innerHTML = '<tr><td colspan="6">لا توجد طلبات أساتذة قيد المراجعة.</td></tr>';
            return;
        }

        teacherRequestsTableBody.innerHTML = items
            .map((req) => {
                const createdAt = req.created_at ? escapeHtml(req.created_at) : "-";
                return `
                    <tr>
                        <td>${escapeHtml(req.full_name || "")}</td>
                        <td>${escapeHtml(req.subject_name || "-")}</td>
                        <td>${escapeHtml(req.email || "")}</td>
                        <td>${escapeHtml(req.phone || "-")}</td>
                        <td>${createdAt}</td>
                        <td>
                            <div class="student-actions">
                                <button type="button" class="btn btn-primary btn-small" data-action="verify-teacher" data-verify-status="approved" data-id="${escapeHtml(req.id)}">
                                    <i class="fa-solid fa-check"></i> قبول
                                </button>
                                <button type="button" class="btn btn-danger btn-small" data-action="verify-teacher" data-verify-status="rejected" data-id="${escapeHtml(req.id)}">
                                    <i class="fa-solid fa-ban"></i> رفض
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            })
            .join("");
    }
    const directorLoginFeedback = document.getElementById("directorLoginFeedback");
    const secretaryLoginFeedback = document.getElementById("secretaryLoginFeedback");
    const legacyLoginFeedback = document.getElementById("adminLoginFeedback");
    const loginRoleInput = document.getElementById("admin_role");
    const loginEmailInput = document.getElementById("admin_email");
    const loginCodeInput = document.getElementById("admin_code");
    const loginCodeLabel = document.getElementById("adminCodeLabel");
    const credentialsHint = document.getElementById("adminCredentialsHint");

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

    const requestsTableBody = document.getElementById("requestsTableBody");
    const requestsTableFeedback = document.getElementById("requestsTableFeedback");
    const pendingRequestsBadge = document.getElementById("pendingRequestsBadge");

    const teacherRequestsTableBody = document.getElementById("teacherRequestsTableBody");
    const teacherRequestsTableFeedback = document.getElementById("teacherRequestsTableFeedback");

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

    const teacherForm = document.getElementById("teacherAdminForm");
    const teacherSubmitBtn = document.getElementById("teacherSubmitBtn");
    const teacherResetBtn = document.getElementById("teacherResetBtn");
    const teacherFeedback = document.getElementById("teacherAdminFeedback");
    const teachersTableBody = document.getElementById("teachersTableBody");
    const teachersTableFeedback = document.getElementById("teachersTableFeedback");

    const teacherSelfProfileForm = document.getElementById("teacherProfileForm");
    const teacherSelfProfileFeedback = document.getElementById("teacherProfileFeedback");
    const teacherSelfCodeForm = document.getElementById("teacherCodeForm");
    const teacherSelfCodeFeedback = document.getElementById("teacherCodeFeedback");
    const teacherSelfTimetableForm = document.getElementById("teacherTimetableForm");
    const teacherSelfTimetableFeedback = document.getElementById("teacherTimetableFeedback");
    const teacherSelfTimetableTableBody = document.getElementById("teacherTimetableTableBody");

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
    let requestsCache = [];
    let teachersCache = [];
    let teacherProfileCache = null;
    let teacherTimetableCache = [];
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
        if (!element) {
            return;
        }

        element.className = `feedback ${type}`;
        element.textContent = message;
    }

    function clearFeedback(element) {
        if (!element) {
            return;
        }

        element.className = "feedback";
        element.textContent = "";
    }

    function getRoleLabel(role) {
        const normalizedRole = normalizeRoleValue(role);
        if (normalizedRole === "director") {
            return "المدير";
        }
        if (normalizedRole === "secretary") {
            return "السكرتارية";
        }
        if (normalizedRole === "teacher") {
            return "الأستاذ";
        }

        return "الإدارة";
    }

    function getRoleIcon(role) {
        const normalizedRole = normalizeRoleValue(role);
        if (normalizedRole === "director") {
            return "fa-user-tie";
        }
        if (normalizedRole === "secretary") {
            return "fa-user-pen";
        }
        if (normalizedRole === "teacher") {
            return "fa-chalkboard-user";
        }

        return "fa-user-shield";
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

    function syncLoginRoleInputs() {
        if (!loginRoleInput) {
            return;
        }

        const selectedRole = normalizeRoleValue(loginRoleInput.value || "director");
        if (loginEmailInput) {
            if (selectedRole === "teacher") {
                loginEmailInput.placeholder = "teacher.name@gmail.com";
            } else {
                loginEmailInput.placeholder =
                    selectedRole === "secretary"
                        ? "secretary@kawkab-ouloum.ma"
                        : "admin@kawkab-ouloum.ma";
            }
        }
        if (loginCodeInput) {
            if (selectedRole === "teacher") {
                loginCodeInput.placeholder = "Teacher@2026";
            } else {
                loginCodeInput.placeholder =
                    selectedRole === "secretary"
                        ? "Secretary@2026"
                        : "Admin@2026";
            }
        }

        if (loginCodeLabel) {
            loginCodeLabel.textContent = selectedRole === "teacher" ? "الكود *" : "الكود الإداري *";
        }

        if (credentialsHint) {
            credentialsHint.style.display = selectedRole === "teacher" ? "none" : "block";
        }
    }

    async function onTeacherRequestsTableAction(event) {
        const button = event.target instanceof Element ? event.target.closest("[data-action]") : null;
        if (!button) {
            return;
        }

        const action = String(button.getAttribute("data-action") || "");
        if (action !== "verify-teacher") {
            return;
        }

        if (activeRole !== "director") {
            setFeedback(teacherRequestsTableFeedback, "مراجعة طلبات الأساتذة متاحة للمدير فقط.", "error");
            return;
        }

        const id = Number(button.getAttribute("data-id") || 0);
        const verifyStatus = String(button.getAttribute("data-verify-status") || "");
        if (!id || (verifyStatus !== "approved" && verifyStatus !== "rejected")) {
            return;
        }

        const confirmText = verifyStatus === "approved"
            ? "هل تريد قبول هذا الأستاذ وتفعيل حسابه؟"
            : "هل تريد رفض هذا الطلب؟";
        if (!window.confirm(confirmText)) {
            return;
        }

        const formData = new FormData();
        formData.append("action", "verify");
        formData.append("teacher_id", String(id));
        formData.append("status", verifyStatus);

        try {
            const { status, payload } = await postForm("backend/admin_teachers.php", formData);
            if (!payload.success) {
                if (status === 403) {
                    handleUnauthorized();
                    return;
                }
                setFeedback(teacherRequestsTableFeedback, payload.message || "تعذر تحديث الطلب.", "error");
                return;
            }

            setFeedback(teacherRequestsTableFeedback, payload.message || "تم تحديث الطلب بنجاح.", "success");
            await loadTeacherRequests();
            await loadTeachers();
        } catch (error) {
            console.error("Teacher request verify error:", error);
            setFeedback(teacherRequestsTableFeedback, "وقع خطأ أثناء تحديث الطلب.", "error");
        }
    }

    function setDefaultTabForRole(role) {
        const normalizedRole = normalizeRoleValue(role);
        const preferredTabId =
            normalizedRole === "teacher"
                ? "teacherProfileTab"
                : normalizedRole === "secretary"
                    ? "gradesTab"
                    : "studentsTab";

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
            if (normalizedRole === "teacher") {
                button.hidden = requiredRole !== "teacher";
                return;
            }

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
        if (loginPanel) {
            if (isLoggedIn) {
                loginPanel.style.transition = "opacity 0.4s ease, transform 0.4s ease";
                loginPanel.style.opacity = "0";
                loginPanel.style.transform = "translateY(-10px)";
                setTimeout(() => {
                    loginPanel.hidden = true;
                    loginPanel.classList.remove("is-visible");
                }, 400);
            } else {
                loginPanel.hidden = false;
                loginPanel.style.opacity = "1";
                loginPanel.style.transform = "translateY(0)";
                loginPanel.classList.add("is-visible");
            }
        }
        
        if (dashboardPanel) {
            if (isLoggedIn) {
                dashboardPanel.hidden = false;
                dashboardPanel.style.opacity = "0";
                dashboardPanel.style.transform = "translateY(10px)";
                dashboardPanel.style.transition = "opacity 0.5s ease 0.2s, transform 0.5s ease 0.2s";
                setTimeout(() => {
                    dashboardPanel.style.opacity = "1";
                    dashboardPanel.style.transform = "translateY(0)";
                    dashboardPanel.classList.add("is-visible");
                }, 50);
            } else {
                dashboardPanel.hidden = true;
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
            studentsTableBody.innerHTML = '<tr><td colspan="6">لا توجد بيانات مطابقة حاليا.</td></tr>';
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

    function loadPendingRequests() {
        if (!requestsTableBody || activeRole !== "director") {
            return;
        }

        fetch("backend/admin_students.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8" },
            body: "action=list_requests",
        })
            .then((res) => res.json())
            .then((res) => {
                if (res.success && Array.isArray(res.data.requests)) {
                    requestsCache = res.data.requests;
                    renderRequestsTable(requestsCache);
                    updatePendingBadge(requestsCache.length);
                } else {
                    requestsCache = [];
                    renderRequestsTable([]);
                    updatePendingBadge(0);
                }
            })
            .catch(() => {
                requestsCache = [];
                renderRequestsTable([]);
                updatePendingBadge(0);
            });
    }

    function renderRequestsTable(requests) {
        if (!requestsTableBody) {
            return;
        }

        if (!Array.isArray(requests) || requests.length === 0) {
            requestsTableBody.innerHTML = '<tr><td colspan="8">لا توجد طلبات قيد المراجعة.</td></tr>';
            updatePendingBadge(0);
            return;
        }

        requestsTableBody.innerHTML = requests
            .map((req) => {
                const fullName = `${req.first_name || ""} ${req.last_name || ""}`.trim();
                let statusHtml = "";
                if (req.registration_status === "pending") {
                    statusHtml = '<span class="status-pill is-warning"><i class="fa-solid fa-clock"></i> قيد الانتظار</span>';
                } else if (req.registration_status === "approved") {
                    statusHtml = '<span class="status-pill is-success"><i class="fa-solid fa-check-double"></i> مقبول</span>';
                } else if (req.registration_status === "rejected") {
                    statusHtml = '<span class="status-pill is-error"><i class="fa-solid fa-xmark"></i> مرفوض</span>';
                }

                return `
                    <tr>
                        <td>${escapeHtml(req.student_code || "")}</td>
                        <td>${escapeHtml(fullName)}</td>
                        <td>${escapeHtml(req.class_name || "")}</td>
                        <td>${escapeHtml(req.level || "")}</td>
                        <td>${escapeHtml(req.guardian_name || "-")}</td>
                        <td>${escapeHtml(req.guardian_phone || "-")}</td>
                        <td>${statusHtml}</td>
                        <td>
                            <div class="student-actions">
                                <button type="button" class="btn btn-primary btn-small" data-action="verify" data-verify-status="approved" data-id="${escapeHtml(req.id)}" data-is-request="yes">
                                    <i class="fa-solid fa-check"></i> قبول
                                </button>
                                <button type="button" class="btn btn-danger btn-small" data-action="verify" data-verify-status="rejected" data-id="${escapeHtml(req.id)}" data-is-request="yes">
                                    <i class="fa-solid fa-ban"></i> رفض
                                </button>
                                <button type="button" class="btn btn-danger btn-small" data-action="delete" data-id="${escapeHtml(req.id)}" data-is-request="yes">
                                    <i class="fa-solid fa-trash"></i> حذف
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            })
            .join("");

        updatePendingBadge(requests.length);
    }

    function updatePendingBadge(count) {
        if (!pendingRequestsBadge) return;
        if (count > 0) {
            pendingRequestsBadge.style.display = "inline";
            pendingRequestsBadge.textContent = count;
        } else {
            pendingRequestsBadge.style.display = "none";
        }
    }

    function renderTeachersTable(teachers) {
        if (!teachersTableBody) {
            return;
        }

        const items = Array.isArray(teachers) ? teachers : [];
        if (items.length === 0) {
            teachersTableBody.innerHTML =
                '<tr><td colspan="7" style="text-align:center;">لا توجد حسابات أساتذة حاليا.</td></tr>';
            return;
        }

        teachersTableBody.innerHTML = items
            .map((teacher) => {
                const status = String(teacher.status || "active");
                const statusHtml =
                    status === "inactive"
                        ? '<span class="status-pill is-warning"><i class="fa-solid fa-pause"></i> غير مفعل</span>'
                        : '<span class="status-pill is-success"><i class="fa-solid fa-check"></i> مفعل</span>';

                const lastLogin = teacher.last_login ? escapeHtml(teacher.last_login) : "-";

                return `
                    <tr>
                        <td>${escapeHtml(teacher.full_name || "")}</td>
                        <td>${escapeHtml(teacher.email || "")}</td>
                        <td>${escapeHtml(teacher.subject_name || "-")}</td>
                        <td>${escapeHtml(teacher.phone || "-")}</td>
                        <td>${statusHtml}</td>
                        <td>${lastLogin}</td>
                        <td>
                            <div class="student-actions">
                                <button type="button" class="btn btn-outline btn-small" data-action="edit-teacher" data-id="${escapeHtml(teacher.id)}">
                                    <i class="fa-solid fa-pen-to-square"></i> تعديل
                                </button>
                                <button type="button" class="btn btn-danger btn-small" data-action="delete-teacher" data-id="${escapeHtml(teacher.id)}">
                                    <i class="fa-solid fa-trash"></i> حذف
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            })
            .join("");
    }

    async function loadTeachers() {
        if (!teachersTableBody || activeRole !== "director") {
            return;
        }

        teachersTableBody.innerHTML =
            '<tr><td colspan="7" style="text-align:center;"><i class="fa-solid fa-spinner fa-spin"></i> جاري تحميل الأساتذة...</td></tr>';

        const formData = new FormData();
        formData.append("action", "list");

        try {
            const { status, payload } = await postForm("backend/admin_teachers.php", formData);
            if (!payload.success) {
                if (status === 403) {
                    handleUnauthorized();
                    return;
                }

                teachersCache = [];
                renderTeachersTable([]);
                if (teachersTableFeedback) {
                    setFeedback(teachersTableFeedback, payload.message || "تعذر جلب الأساتذة.", "error");
                }
                return;
            }

            const list = payload.data && Array.isArray(payload.data.teachers) ? payload.data.teachers : [];
            teachersCache = list;
            renderTeachersTable(list);
            clearFeedback(teachersTableFeedback);
        } catch (error) {
            console.error("Teachers load error:", error);
            teachersCache = [];
            renderTeachersTable([]);
            setFeedback(teachersTableFeedback, "وقع خطأ أثناء جلب الأساتذة.", "error");
        }
    }

    function resetTeacherForm() {
        if (!teacherForm) {
            return;
        }

        teacherForm.reset();
        const idInput = teacherForm.querySelector('input[name="teacher_id"]');
        if (idInput) {
            idInput.value = "0";
        }

        if (teacherSubmitBtn) {
            teacherSubmitBtn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> حفظ الأستاذ';
        }
    }

    function fillTeacherFormForEdit(teacher) {
        if (!teacherForm || !teacher) {
            return;
        }

        const idInput = teacherForm.querySelector('input[name="teacher_id"]');
        if (idInput) {
            idInput.value = String(teacher.id || "0");
        }

        const fullNameInput = teacherForm.querySelector('input[name="full_name"]');
        const emailInput = teacherForm.querySelector('input[name="email"]');
        const phoneInput = teacherForm.querySelector('input[name="phone"]');
        const subjectInput = teacherForm.querySelector('input[name="subject_name"]');
        const statusSelect = teacherForm.querySelector('select[name="status"]');
        const codeInput = teacherForm.querySelector('input[name="code"]');

        if (fullNameInput) fullNameInput.value = teacher.full_name || "";
        if (emailInput) emailInput.value = teacher.email || "";
        if (phoneInput) phoneInput.value = teacher.phone || "";
        if (subjectInput) subjectInput.value = teacher.subject_name || "";
        if (statusSelect) statusSelect.value = teacher.status || "active";
        if (codeInput) codeInput.value = "";

        if (teacherSubmitBtn) {
            teacherSubmitBtn.innerHTML = '<i class="fa-solid fa-pen-to-square"></i> تحديث الأستاذ';
        }

        setActiveTab("teachersTab");
        teacherForm.scrollIntoView({ behavior: "smooth", block: "start" });
    }

    function buildTeacherIdentity(profile) {
        if (!profile) {
            return null;
        }

        return {
            full_name: profile.full_name || "أستاذ",
            email: profile.email || "",
            role: "teacher",
        };
    }

    function fillTeacherSelfProfileForm(profile) {
        if (!teacherSelfProfileForm || !profile) {
            return;
        }

        const fullNameInput = teacherSelfProfileForm.querySelector('input[name="full_name"]');
        const phoneInput = teacherSelfProfileForm.querySelector('input[name="phone"]');
        const subjectInput = teacherSelfProfileForm.querySelector('input[name="subject_name"]');
        const bioInput = teacherSelfProfileForm.querySelector('textarea[name="bio"]');

        if (fullNameInput) fullNameInput.value = profile.full_name || "";
        if (phoneInput) phoneInput.value = profile.phone || "";
        if (subjectInput) subjectInput.value = profile.subject_name || "";
        if (bioInput) bioInput.value = profile.bio || "";
    }

    async function loadTeacherProfile(force = false) {
        if (activeRole !== "teacher") {
            return;
        }
        if (!teacherSelfProfileForm) {
            return;
        }

        if (!force && teacherProfileCache && teacherProfileCache.email) {
            fillTeacherSelfProfileForm(teacherProfileCache);
            setIdentity(buildTeacherIdentity(teacherProfileCache));
            return;
        }

        const formData = new FormData();
        formData.append("action", "get");

        try {
            const { status, payload } = await postForm("backend/teacher_profile.php", formData);
            if (!payload.success) {
                if (status === 403) {
                    handleUnauthorized();
                    return;
                }

                setFeedback(
                    teacherSelfProfileFeedback,
                    payload.message || "تعذر جلب معلومات الأستاذ.",
                    "error"
                );
                return;
            }

            const profile = payload.data && payload.data.teacher ? payload.data.teacher : null;
            teacherProfileCache = profile;
            if (profile) {
                fillTeacherSelfProfileForm(profile);
                setIdentity(buildTeacherIdentity(profile));
            }
            clearFeedback(teacherSelfProfileFeedback);
        } catch (error) {
            console.error("Teacher profile load error:", error);
            setFeedback(teacherSelfProfileFeedback, "وقع خطأ أثناء جلب معلومات الأستاذ.", "error");
        }
    }

    function dayLabel(dayNumber) {
        const number = Number(dayNumber || 0);
        const labels = {
            1: "الإثنين",
            2: "الثلاثاء",
            3: "الأربعاء",
            4: "الخميس",
            5: "الجمعة",
            6: "السبت",
            7: "الأحد",
        };
        return labels[number] || String(dayNumber || "");
    }

    function renderTeacherTimetable(items) {
        if (!teacherSelfTimetableTableBody) {
            return;
        }

        const rows = Array.isArray(items) ? items : [];
        if (rows.length === 0) {
            teacherSelfTimetableTableBody.innerHTML =
                '<tr><td colspan="7" style="text-align:center;">لا توجد حصص بعد.</td></tr>';
            return;
        }

        teacherSelfTimetableTableBody.innerHTML = rows
            .map((item) => {
                const timeLabel = `${escapeHtml(item.start_time || "")} - ${escapeHtml(item.end_time || "")}`;
                return `
                    <tr>
                        <td>${escapeHtml(dayLabel(item.day_of_week))}</td>
                        <td>${timeLabel}</td>
                        <td>${escapeHtml(item.class_name || "")}</td>
                        <td>${escapeHtml(item.subject_name || "")}</td>
                        <td>${escapeHtml(item.room || "-")}</td>
                        <td>${escapeHtml(item.notes || "-")}</td>
                        <td>
                            <button type="button" class="btn btn-danger btn-small" data-action="delete-timetable" data-id="${escapeHtml(item.id)}">
                                <i class="fa-solid fa-trash"></i> حذف
                            </button>
                        </td>
                    </tr>
                `;
            })
            .join("");
    }

    async function loadTeacherTimetable() {
        if (activeRole !== "teacher") {
            return;
        }
        if (!teacherSelfTimetableTableBody) {
            return;
        }

        teacherSelfTimetableTableBody.innerHTML =
            '<tr><td colspan="7" style="text-align:center;"><i class="fa-solid fa-spinner fa-spin"></i> جاري تحميل جدول الحصص...</td></tr>';

        const formData = new FormData();
        formData.append("action", "list");

        try {
            const { status, payload } = await postForm("backend/teacher_timetable.php", formData);
            if (!payload.success) {
                if (status === 403) {
                    handleUnauthorized();
                    return;
                }

                teacherTimetableCache = [];
                renderTeacherTimetable([]);
                setFeedback(
                    teacherSelfTimetableFeedback,
                    payload.message || "تعذر جلب جدول الحصص.",
                    "error"
                );
                return;
            }

            const items = payload.data && Array.isArray(payload.data.items) ? payload.data.items : [];
            teacherTimetableCache = items;
            renderTeacherTimetable(items);
            clearFeedback(teacherSelfTimetableFeedback);
        } catch (error) {
            console.error("Teacher timetable load error:", error);
            teacherTimetableCache = [];
            renderTeacherTimetable([]);
            setFeedback(teacherSelfTimetableFeedback, "وقع خطأ أثناء جلب جدول الحصص.", "error");
        }
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
            if (status === 403) {
                handleUnauthorized();
                return;
            }
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
        let safeTabId = String(tabId || "");
        const requestedButton = tabButtons.find(
            (button) =>
                !button.hidden &&
                String(button.getAttribute("data-tab-target") || "") === safeTabId
        );

        if (!requestedButton) {
            const firstVisibleButton = tabButtons.find((button) => !button.hidden);
            safeTabId = firstVisibleButton
                ? String(firstVisibleButton.getAttribute("data-tab-target") || "")
                : "";
        }

        tabButtons.forEach((button) => {
            const isMatch =
                !button.hidden &&
                String(button.getAttribute("data-tab-target") || "") === safeTabId;
            button.classList.toggle("is-active", isMatch);
            button.setAttribute("aria-selected", isMatch ? "true" : "false");
        });

        tabPanels.forEach((panel) => {
            const isMatch = String(panel.id || "") === safeTabId;
            panel.classList.toggle("is-active", isMatch);
            panel.hidden = !isMatch;
        });

        // Load pending requests when switching to requests tab
        if (safeTabId === "requestsTab") {
            loadPendingRequests();
        }
        if (safeTabId === "teacherRequestsTab") {
            loadTeacherRequests();
        }
        if (safeTabId === "teachersTab") {
            loadTeachers();
        }
        if (safeTabId === "teacherProfileTab") {
            loadTeacherProfile();
        }
        if (safeTabId === "teacherTimetableTab") {
            loadTeacherTimetable();
        }
    }

    async function postForm(url, formData) {
        try {
            const response = await fetch(url, {
                method: "POST",
                body: formData,
                credentials: "same-origin", // Ensure session cookies are sent
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                },
            });

            let payload = null;
            try {
                payload = await response.json();
            } catch (error) {
                console.error("JSON parse error:", error, "Response text:", await response.text());
                throw new Error("Invalid JSON response from server");
            }

            return {
                status: response.status,
                payload,
            };
        } catch (networkError) {
            console.error("Network error:", networkError);
            // Try with absolute API URL if relative fails
            if (!url.startsWith('http')) {
                const apiEndpoint = url.replace(/^.*backend\//, '');
                const fullUrl = buildApiUrl(apiEndpoint);
                logDebug("Retrying with absolute URL: " + fullUrl);
                
                const response = await fetch(fullUrl, {
                    method: "POST",
                    body: formData,
                    credentials: "same-origin",
                    headers: {
                        "X-Requested-With": "XMLHttpRequest",
                    },
                });
                
                let payload = await response.json();
                return {
                    status: response.status,
                    payload,
                };
            }
            throw networkError;
        }
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
        teachersCache = [];
        teacherProfileCache = null;
        teacherTimetableCache = [];
        populateStudentSelects([]);
        if (studentsTableBody) {
            studentsTableBody.innerHTML = '<tr><td colspan="6">قم بتسجيل الدخول (المدير أو السكرتارية) أولا.</td></tr>';
        }
        if (lessonsTableBody) {
            lessonsTableBody.innerHTML = '<tr><td colspan="6">قم بتسجيل الدخول (المدير أو السكرتارية) أولا.</td></tr>';
        }
        if (teachersTableBody) {
            teachersTableBody.innerHTML = '<tr><td colspan="7">قم بتسجيل الدخول (المدير) أولا.</td></tr>';
        }
        if (teacherSelfTimetableTableBody) {
            teacherSelfTimetableTableBody.innerHTML = '<tr><td colspan="7">قم بتسجيل الدخول أولا.</td></tr>';
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
        if (teacherSelfProfileForm) {
            teacherSelfProfileForm.reset();
        }
        if (teacherSelfCodeForm) {
            teacherSelfCodeForm.reset();
        }
        if (teacherSelfTimetableForm) {
            teacherSelfTimetableForm.reset();
        }
        clearFeedback(teacherSelfProfileFeedback);
        clearFeedback(teacherSelfCodeFeedback);
        clearFeedback(teacherSelfTimetableFeedback);
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
        await loadLessonsData();
        loadPendingRequests();

        if (showSuccessMessage) {
            setFeedback(studentTableFeedback, "تم تحديث البيانات بنجاح.", "success");
        }
    }

    // Alias function for HTML form onsubmit="return handleAdminLoginSubmit(event)"
    async function handleAdminLoginSubmit(event) {
        try {
            await onRoleLoginSubmit(event);
        } catch (error) {
            console.error("handleAdminLoginSubmit error:", error);
            const feedback = document.getElementById("adminLoginFeedback");
            if (feedback) {
                feedback.textContent = "تعذر الاتصال بالخادم. تحقق من أن الخادم يعمل.";
                feedback.className = "feedback error";
            }
        }
        return false; // Prevent default form submission
    }

    async function onRoleLoginSubmit(event) {
        event.preventDefault();
        console.log("[DEBUG] onRoleLoginSubmit called");
        
        const form = event.currentTarget;
        if (!form) {
            console.error("[DEBUG] No form found");
            return;
        }
        console.log("[DEBUG] Form ID:", form.id, "Form action:", form.action);

        const selectedRole = normalizeRoleValue(new FormData(form).get("role") || "");
        console.log("[DEBUG] Selected role:", selectedRole);
        
        const feedbackElement = getLoginFeedbackElement(selectedRole, form.id);
        clearLoginFeedbacks();
        clearFeedback(feedbackElement);

        try {
            const formData = new FormData(form);
            const authEndpoint =
                selectedRole === "teacher" ? buildApiUrl("teacher_auth.php") : buildApiUrl(String(form.action || "admin_auth.php"));
            logDebug("[DEBUG] Auth endpoint: " + authEndpoint);

            const { payload, status } = await postForm(authEndpoint, formData);
            console.log("[DEBUG] Response status:", status, "Payload:", payload);
            if (!payload.success) {
                if (status === 403) {
                    handleUnauthorized();
                    return;
                }
                setFeedback(feedbackElement, payload.message || "فشل تسجيل الدخول.", "error");
                return;
            }

            const user = payload.data && payload.data.user ? payload.data.user : null;
            const normalizedUserRole = normalizeRoleValue(user && user.role ? user.role : "");
            const allowedRoles = ["director", "secretary", "teacher"];
            if (!user || !allowedRoles.includes(normalizedUserRole)) {
                await performLogout();
                setFeedback(feedbackElement, "نوع الحساب غير مسموح به.", "error");
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
            
            // Show dashboard immediately before loading data
            setDashboardState(true);
            
            form.reset();
            if (loginRoleInput) {
                loginRoleInput.value = activeRole;
                syncLoginRoleInputs();
            }

            setIdentity(user);
            applyRoleAccess(activeRole);

            if (activeRole === "teacher") {
                updateStats({ students_count: 0, grades_count: 0, attendance_count: 0 });
                studentsCache = [];
                lessonsCache = [];
                requestsCache = [];
                teachersCache = [];
                populateStudentSelects([]);
                teacherProfileCache = null;
                teacherTimetableCache = [];
                await loadTeacherProfile(true);
                setDefaultTabForRole(activeRole);
                return;
            }

            await loadDashboardData();
            setDefaultTabForRole(activeRole);
        } catch (error) {
            console.error("[DEBUG] Login error:", error);
            console.error("[DEBUG] Error message:", error.message);
            console.error("[DEBUG] Error stack:", error.stack);
            setFeedback(feedbackElement, "تعذر الاتصال بالخادم أثناء تسجيل الدخول.", "error");
        }
    }

    async function onLogoutClick() {
        const roleBeforeLogout = activeRole;
        await performLogout();
        handleUnauthorized();
        clearLoginFeedbacks();
        clearFeedback(studentFeedback);
        clearFeedback(teacherFeedback);
        clearFeedback(teachersTableFeedback);
        clearFeedback(gradeFeedback);
        clearFeedback(attendanceFeedback);
        const logoutFeedbackElement = getLoginFeedbackElement(roleBeforeLogout);
        setFeedback(logoutFeedbackElement, "تم تسجيل الخروج بنجاح.", "success");
    }

    async function onTeacherSubmit(event) {
        event.preventDefault();
        clearFeedback(teacherFeedback);

        if (!teacherForm) {
            return;
        }

        if (activeRole !== "director") {
            setFeedback(teacherFeedback, "إدارة حسابات الأساتذة متاحة للمدير فقط.", "error");
            return;
        }

        const formData = new FormData(teacherForm);
        const teacherId = Number(formData.get("teacher_id") || 0);
        const code = String(formData.get("code") || "").trim();
        if (teacherId <= 0 && code === "") {
            setFeedback(teacherFeedback, "الكود إلزامي عند إضافة أستاذ جديد.", "error");
            return;
        }

        formData.append("action", "save");

        const submitButton = teacherSubmitBtn;
        const initialHtml = submitButton ? submitButton.innerHTML : "";
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> جاري الحفظ...';
        }

        try {
            const { status, payload } = await postForm(teacherForm.action, formData);
            if (!payload.success) {
                if (status === 403) {
                    handleUnauthorized();
                    return;
                }

                setFeedback(teacherFeedback, payload.message || "تعذر حفظ الأستاذ.", "error");
                return;
            }

            setFeedback(teacherFeedback, payload.message || "تم حفظ الأستاذ بنجاح.", "success");
            resetTeacherForm();
            await loadTeachers();
        } catch (error) {
            console.error("Teacher save error:", error);
            setFeedback(teacherFeedback, "وقع خطأ أثناء حفظ الأستاذ.", "error");
        } finally {
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.innerHTML = initialHtml;
            }
        }
    }

    async function onTeachersTableAction(event) {
        const button = event.target instanceof Element ? event.target.closest("[data-action]") : null;
        if (!button) {
            return;
        }

        const action = String(button.getAttribute("data-action") || "");
        const id = Number(button.getAttribute("data-id") || 0);
        if (!id) {
            return;
        }

        if (action === "edit-teacher") {
            const teacher = teachersCache.find((item) => Number(item.id) === id);
            if (teacher) {
                fillTeacherFormForEdit(teacher);
            }
            return;
        }

        if (action === "delete-teacher") {
            if (activeRole !== "director") {
                setFeedback(teachersTableFeedback, "حذف الأساتذة متاح للمدير فقط.", "error");
                return;
            }

            const ok = window.confirm("هل تريد حذف هذا الأستاذ؟ سيتم حذف جدول حصصه أيضا.");
            if (!ok) {
                return;
            }

            const formData = new FormData();
            formData.append("action", "delete");
            formData.append("teacher_id", String(id));

            try {
                const { status, payload } = await postForm("backend/admin_teachers.php", formData);
                if (!payload.success) {
                    if (status === 403) {
                        handleUnauthorized();
                        return;
                    }
                    setFeedback(teachersTableFeedback, payload.message || "تعذر حذف الأستاذ.", "error");
                    return;
                }

                setFeedback(teachersTableFeedback, payload.message || "تم حذف الأستاذ.", "success");
                resetTeacherForm();
                await loadTeachers();
            } catch (error) {
                console.error("Teacher delete error:", error);
                setFeedback(teachersTableFeedback, "وقع خطأ أثناء حذف الأستاذ.", "error");
            }
        }
    }

    async function onTeacherSelfProfileSubmit(event) {
        event.preventDefault();
        clearFeedback(teacherSelfProfileFeedback);

        if (!teacherSelfProfileForm) {
            return;
        }

        if (activeRole !== "teacher") {
            setFeedback(teacherSelfProfileFeedback, "هذه الخدمة مخصصة لحساب الأستاذ.", "error");
            return;
        }

        const submitButton = teacherSelfProfileForm.querySelector('button[type="submit"]');
        const initialHtml = submitButton ? submitButton.innerHTML : "";
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> جاري الحفظ...';
        }

        const formData = new FormData(teacherSelfProfileForm);
        formData.append("action", "update");

        try {
            const { status, payload } = await postForm(teacherSelfProfileForm.action, formData);
            if (!payload.success) {
                if (status === 403) {
                    handleUnauthorized();
                    return;
                }

                setFeedback(
                    teacherSelfProfileFeedback,
                    payload.message || "تعذر تحديث معلومات الأستاذ.",
                    "error"
                );
                return;
            }

            const updated = payload.data && payload.data.teacher ? payload.data.teacher : null;
            teacherProfileCache = {
                ...(teacherProfileCache || {}),
                ...(updated || {}),
            };

            setIdentity(buildTeacherIdentity(teacherProfileCache));
            setFeedback(
                teacherSelfProfileFeedback,
                payload.message || "تم تحديث معلومات الأستاذ بنجاح.",
                "success"
            );
        } catch (error) {
            console.error("Teacher profile update error:", error);
            setFeedback(teacherSelfProfileFeedback, "وقع خطأ أثناء تحديث معلومات الأستاذ.", "error");
        } finally {
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.innerHTML = initialHtml;
            }
        }
    }

    async function onTeacherSelfCodeSubmit(event) {
        event.preventDefault();
        clearFeedback(teacherSelfCodeFeedback);

        if (!teacherSelfCodeForm) {
            return;
        }

        if (activeRole !== "teacher") {
            setFeedback(teacherSelfCodeFeedback, "هذه الخدمة مخصصة لحساب الأستاذ.", "error");
            return;
        }

        const submitButton = teacherSelfCodeForm.querySelector('button[type="submit"]');
        const initialHtml = submitButton ? submitButton.innerHTML : "";
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> جاري التحديث...';
        }

        const formData = new FormData(teacherSelfCodeForm);
        formData.append("action", "change_code");

        try {
            const { status, payload } = await postForm(teacherSelfCodeForm.action, formData);
            if (!payload.success) {
                if (status === 403) {
                    handleUnauthorized();
                    return;
                }

                setFeedback(
                    teacherSelfCodeFeedback,
                    payload.message || "تعذر تغيير الكود.",
                    "error"
                );
                return;
            }

            setFeedback(
                teacherSelfCodeFeedback,
                payload.message || "تم تغيير الكود بنجاح.",
                "success"
            );
            teacherSelfCodeForm.reset();
        } catch (error) {
            console.error("Teacher code change error:", error);
            setFeedback(teacherSelfCodeFeedback, "وقع خطأ أثناء تغيير الكود.", "error");
        } finally {
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.innerHTML = initialHtml;
            }
        }
    }

    async function onTeacherSelfTimetableSubmit(event) {
        event.preventDefault();
        clearFeedback(teacherSelfTimetableFeedback);

        if (!teacherSelfTimetableForm) {
            return;
        }

        if (activeRole !== "teacher") {
            setFeedback(teacherSelfTimetableFeedback, "هذه الخدمة مخصصة لحساب الأستاذ.", "error");
            return;
        }

        const submitButton = teacherSelfTimetableForm.querySelector('button[type="submit"]');
        const initialHtml = submitButton ? submitButton.innerHTML : "";
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> جاري الإضافة...';
        }

        const formData = new FormData(teacherSelfTimetableForm);
        formData.append("action", "save");

        try {
            const { status, payload } = await postForm(teacherSelfTimetableForm.action, formData);
            if (!payload.success) {
                if (status === 403) {
                    handleUnauthorized();
                    return;
                }

                setFeedback(
                    teacherSelfTimetableFeedback,
                    payload.message || "تعذر حفظ الحصة.",
                    "error"
                );
                return;
            }

            setFeedback(
                teacherSelfTimetableFeedback,
                payload.message || "تمت إضافة الحصة بنجاح.",
                "success"
            );
            teacherSelfTimetableForm.reset();
            await loadTeacherTimetable();
        } catch (error) {
            console.error("Teacher timetable save error:", error);
            setFeedback(teacherSelfTimetableFeedback, "وقع خطأ أثناء حفظ الحصة.", "error");
        } finally {
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.innerHTML = initialHtml;
            }
        }
    }

    async function onTeacherSelfTimetableTableAction(event) {
        const button = event.target instanceof Element ? event.target.closest("[data-action]") : null;
        if (!button) {
            return;
        }

        if (activeRole !== "teacher") {
            return;
        }

        const action = String(button.getAttribute("data-action") || "");
        if (action !== "delete-timetable") {
            return;
        }

        const timetableId = Number(button.getAttribute("data-id") || 0);
        if (!timetableId) {
            return;
        }

        const ok = window.confirm("واش متأكد بغيتي تحذف هاد الحصة؟");
        if (!ok) {
            return;
        }

        clearFeedback(teacherSelfTimetableFeedback);

        const formData = new FormData();
        formData.append("action", "delete");
        formData.append("id", String(timetableId));

        try {
            const { status, payload } = await postForm("backend/teacher_timetable.php", formData);
            if (!payload.success) {
                if (status === 403) {
                    handleUnauthorized();
                    return;
                }

                setFeedback(
                    teacherSelfTimetableFeedback,
                    payload.message || "تعذر حذف الحصة.",
                    "error"
                );
                return;
            }

            setFeedback(
                teacherSelfTimetableFeedback,
                payload.message || "تم حذف الحصة بنجاح.",
                "success"
            );
            await loadTeacherTimetable();
        } catch (error) {
            console.error("Teacher timetable delete error:", error);
            setFeedback(teacherSelfTimetableFeedback, "وقع خطأ أثناء حذف الحصة.", "error");
        }
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
        const selectedStudent = isRequest === "yes" 
            ? requestsCache.find((req) => Number(req.id) === studentId)
            : studentsCache.find((student) => Number(student.id) === studentId && (student.is_request || "no") === isRequest);
            
        if (!selectedStudent) {
            return;
        }

        const feedbackElement = isRequest === "yes" ? requestsTableFeedback : studentTableFeedback;

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
                    setFeedback(feedbackElement, payload.message, "success");
                    await loadDashboardData();
                } else {
                    setFeedback(feedbackElement, payload.message || "فشلت عملية التحقق.", "error");
                }
            } catch (error) {
                console.error("Verification error:", error);
                setFeedback(feedbackElement, "وقع خطأ أثناء عملية التحقق.", "error");
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

        clearFeedback(feedbackElement);
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

                setFeedback(feedbackElement, payload.message || "تعذر حذف الطالب.", "error");
                return;
            }

            setFeedback(feedbackElement, payload.message || "تم حذف الطالب بنجاح.", "success");
            resetStudentForm();
            await loadDashboardData();
        } catch (error) {
            console.error("Student delete error:", error);
            setFeedback(feedbackElement, "وقع خطأ أثناء حذف الطالب.", "error");
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

    if (teacherForm) {
        teacherForm.addEventListener("submit", onTeacherSubmit);
    }

    if (teacherResetBtn) {
        teacherResetBtn.addEventListener("click", () => {
            clearFeedback(teacherFeedback);
            resetTeacherForm();
        });
    }

    if (teachersTableBody) {
        teachersTableBody.addEventListener("click", onTeachersTableAction);
    }

    if (teacherSelfProfileForm) {
        teacherSelfProfileForm.addEventListener("submit", onTeacherSelfProfileSubmit);
    }

    if (teacherSelfCodeForm) {
        teacherSelfCodeForm.addEventListener("submit", onTeacherSelfCodeSubmit);
    }

    if (teacherSelfTimetableForm) {
        teacherSelfTimetableForm.addEventListener("submit", onTeacherSelfTimetableSubmit);
    }

    if (teacherSelfTimetableTableBody) {
        teacherSelfTimetableTableBody.addEventListener("click", onTeacherSelfTimetableTableAction);
    }

    if (studentsTableBody) {
        studentsTableBody.addEventListener("click", onTableAction);
    }

    if (requestsTableBody) {
        requestsTableBody.addEventListener("click", onTableAction);
    }

    if (teacherRequestsTableBody) {
        teacherRequestsTableBody.addEventListener("click", onTeacherRequestsTableAction);
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
        gradeForm.addEventListener("submit", function(event) {
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
        attendanceForm.addEventListener("submit", function(event) {
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

    async function restoreSessionIfExists() {
        // 1) Admin session (director/secretary)
        try {
            const response = await fetch("backend/admin_students.php", {
                method: "POST",
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                    "Content-Type": "application/x-www-form-urlencoded",
                },
                body: "action=list",
            });

            if (response.ok) {
                const data = await response.json();
                if (data.success && data.data && data.data.user && data.data.user.role) {
                    const user = data.data.user;
                    activeRole = normalizeRoleValue(user.role);
                    setIdentity(user);
                    setDashboardState(true);
                    applyRoleAccess(activeRole);
                    updateStats(data.data.stats || {});

                    const dataStudents = Array.isArray(data.data.students) ? data.data.students : [];
                    studentsCache = dataStudents;
                    populateStudentSelects(studentsCache);
                    applyStudentsFiltersAndRender();
                    await loadLessonsData();
                    loadPendingRequests();

                    if (loginRoleInput) {
                        loginRoleInput.value = activeRole;
                        syncLoginRoleInputs();
                    }

                    setDefaultTabForRole(activeRole);
                    return;
                }
            }
        } catch (error) {
            // ignore
        }

        // 2) Teacher session
        try {
            const formData = new FormData();
            formData.append("action", "get");

            const { payload } = await postForm("backend/teacher_profile.php", formData);
            if (!payload.success) {
                return;
            }

            const profile = payload.data && payload.data.teacher ? payload.data.teacher : null;
            if (!profile) {
                return;
            }

            activeRole = "teacher";
            teacherProfileCache = profile;
            setIdentity(buildTeacherIdentity(profile));
            setDashboardState(true);
            applyRoleAccess(activeRole);
            updateStats({ students_count: 0, grades_count: 0, attendance_count: 0 });
            fillTeacherSelfProfileForm(profile);

            if (loginRoleInput) {
                loginRoleInput.value = activeRole;
                syncLoginRoleInputs();
            }

            setDefaultTabForRole(activeRole);
        } catch (error) {
            // ignore
        }
    }

    initTabs();
    initPasswordToggles();

    const roleParam = normalizeRoleValue(
        new URLSearchParams(window.location.search).get("role") || ""
    );
    if (
        loginRoleInput &&
        ["director", "secretary", "teacher"].includes(roleParam)
    ) {
        loginRoleInput.value = roleParam;
    }

    syncLoginRoleInputs();
    checkDatabaseConnection();

    restoreSessionIfExists();
});
