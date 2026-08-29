/**
 * Student/Parent Portal - KAWKAB AL OULOU
 * DB-backed portal login and dashboard rendering.
 */

const PORTAL_SESSION_KEY = "portalSessionData";
const PORTAL_AUTH_ENDPOINT = "backend/portal_auth.php";

let currentSession = null;
let currentSemester = "S1";

document.addEventListener("DOMContentLoaded", () => {
    setupLoginForm();
    setupTestStudentFill();
    restoreSession();
});

function setupLoginForm() {
    const form = document.getElementById("loginForm");
    if (!form) {
        return;
    }

    form.addEventListener("submit", async (event) => {
        event.preventDefault();

        const studentCodeInput = document.getElementById("studentCode");
        const birthDateInput = document.getElementById("birthDate");
        const submitButton = form.querySelector("button[type='submit']");

        const studentCode = String(studentCodeInput ? studentCodeInput.value : "").trim().toUpperCase();
        const birthDate = String(birthDateInput ? birthDateInput.value : "").trim();

        if (studentCode === "" || birthDate === "") {
            setLoginFeedback("مرجو إدخال كود الطالب وتاريخ الازدياد.", "error");
            return;
        }

        const initialButtonHtml = submitButton ? submitButton.innerHTML : "";
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Chargement...';
        }

        clearLoginFeedback();

        const formData = new FormData();
        formData.append("student_code", studentCode);
        formData.append("birth_date", birthDate);

        try {
            const response = await fetch(PORTAL_AUTH_ENDPOINT, {
                method: "POST",
                body: formData,
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                },
            });

            const payload = await response.json();
            if (!payload.success) {
                setLoginFeedback(payload.message || "Echec de connexion.", "error");
                return;
            }

            currentSession = payload.data || null;
            if (!currentSession || !currentSession.student) {
                setLoginFeedback("Reponse serveur invalide.", "error");
                return;
            }

            sessionStorage.setItem(PORTAL_SESSION_KEY, JSON.stringify(currentSession));
            setLoginFeedback("Connexion reussie.", "success");
            showDashboard();
        } catch (error) {
            console.error("Portal login error:", error);
            setLoginFeedback("Erreur reseau. Verifie Apache/MySQL.", "error");
        } finally {
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.innerHTML = initialButtonHtml;
            }
        }
    });
}

function setupTestStudentFill() {
    const fillButton = document.getElementById("fillTestStudent");
    if (!fillButton) {
        return;
    }

    fillButton.addEventListener("click", () => {
        const studentCodeInput = document.getElementById("studentCode");
        const birthDateInput = document.getElementById("birthDate");

        if (studentCodeInput) {
            studentCodeInput.value = "STU2026001";
        }

        if (birthDateInput) {
            birthDateInput.value = "2008-04-16";
        }

        clearLoginFeedback();
    });
}

function restoreSession() {
    const serialized = sessionStorage.getItem(PORTAL_SESSION_KEY);
    if (!serialized) {
        return;
    }

    try {
        const parsed = JSON.parse(serialized);
        if (!parsed || !parsed.student) {
            sessionStorage.removeItem(PORTAL_SESSION_KEY);
            return;
        }

        currentSession = parsed;
        showDashboard();
    } catch (error) {
        console.error("Restore session error:", error);
        sessionStorage.removeItem(PORTAL_SESSION_KEY);
    }
}

function showDashboard() {
    if (!currentSession || !currentSession.student) {
        return;
    }

    const loginSection = document.getElementById("loginSection");
    const dashboardSection = document.getElementById("dashboardSection");

    if (loginSection) {
        loginSection.style.display = "none";
    }

    if (dashboardSection) {
        dashboardSection.style.display = "block";
    }

    fillStudentHeader();
    setupSemesterSelector();
    loadGrades();
    loadAttendance();
    loadStats();
    loadAnnouncements();
}

function fillStudentHeader() {
    const nameElement = document.getElementById("displayStudentName");
    const classElement = document.getElementById("displayStudentClass");
    const student = currentSession ? currentSession.student : null;

    if (nameElement) {
        nameElement.textContent = student && student.full_name ? student.full_name : "-";
    }

    if (classElement) {
        classElement.textContent = student && student.class_name ? student.class_name : "-";
    }
}

function setupSemesterSelector() {
    const select = document.getElementById("semesterSelect");
    if (!select) {
        return;
    }

    const semesters = Array.isArray(currentSession ? currentSession.semesters : null)
        ? currentSession.semesters
        : [];

    select.innerHTML = "";

    if (semesters.length === 0) {
        select.innerHTML = '<option value="S1">S1</option>';
        currentSemester = "S1";
        select.disabled = true;
        return;
    }

    const optionsHtml = semesters
        .map((semester) => `<option value="${escapeHtml(semester)}">${escapeHtml(semester)}</option>`)
        .join("");
    select.innerHTML = optionsHtml;
    select.disabled = false;

    currentSemester = semesters.includes("S1") ? "S1" : semesters[0];
    select.value = currentSemester;

    select.onchange = () => {
        currentSemester = select.value;
        loadGrades();
        loadStats();
    };
}

function loadGrades() {
    const tbody = document.getElementById("gradesBody");
    if (!tbody || !currentSession) {
        return;
    }

    const gradesBySemester = currentSession.grades_by_semester || {};
    const grades = Array.isArray(gradesBySemester[currentSemester])
        ? gradesBySemester[currentSemester]
        : [];

    if (grades.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center">Aucune note pour ce semestre</td></tr>';
        return;
    }

    const html = grades
        .map((grade) => {
            const avg = Number.parseFloat(String(grade.average || 0));
            let averageClass = "grade-good";
            if (avg < 10) {
                averageClass = "grade-bad";
            } else if (avg < 12) {
                averageClass = "grade-medium";
            }

            return `
                <tr>
                    <td>${escapeHtml(grade.subject_name || "-")}</td>
                    <td>${escapeHtml(String(grade.continuous_score || "-"))}</td>
                    <td>${escapeHtml(String(grade.exam_score || "-"))}</td>
                    <td>${escapeHtml(String(grade.coefficient || "-"))}</td>
                    <td><span class="${averageClass}">${escapeHtml(String(grade.average || "-"))}</span></td>
                </tr>
            `;
        })
        .join("");

    tbody.innerHTML = html;
}

function loadAttendance() {
    const tbody = document.getElementById("attendanceBody");
    if (!tbody || !currentSession) {
        return;
    }

    const attendance = currentSession.attendance || {};
    const records = Array.isArray(attendance.records) ? attendance.records : [];

    if (records.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center">Aucune absence enregistree</td></tr>';
        return;
    }

    const html = records
        .map((record) => {
            const justified = Boolean(record.justified);

            return `
                <tr>
                    <td>${escapeHtml(formatDate(record.absence_date || ""))}</td>
                    <td>${escapeHtml(record.session_label || "-")}</td>
                    <td>${justified ? '<span class="badge-success">Oui</span>' : '<span class="badge-danger">Non</span>'}</td>
                    <td>${escapeHtml(record.notes || "-")}</td>
                </tr>
            `;
        })
        .join("");

    tbody.innerHTML = html;
}

function loadStats() {
    if (!currentSession) {
        return;
    }

    const gradeValue = document.getElementById("averageGrade");
    const attendanceRateValue = document.getElementById("attendanceRate");
    const totalAbsencesValue = document.getElementById("totalAbsences");
    const levelValue = document.getElementById("studentLevel");

    const semesterGrades = Array.isArray((currentSession.grades_by_semester || {})[currentSemester])
        ? (currentSession.grades_by_semester || {})[currentSemester]
        : [];

    let semesterAverage = "--";
    if (semesterGrades.length > 0) {
        let weighted = 0;
        let coefficients = 0;

        semesterGrades.forEach((grade) => {
            const avg = Number.parseFloat(String(grade.average || 0));
            const coef = Number.parseFloat(String(grade.coefficient || 1));
            weighted += (avg * coef);
            coefficients += coef;
        });

        if (coefficients > 0) {
            semesterAverage = (weighted / coefficients).toFixed(2);
        }
    }

    const attendanceStats = currentSession.attendance && currentSession.attendance.stats
        ? currentSession.attendance.stats
        : {};

    const attendanceRate = Number(attendanceStats.attendance_rate || 0);
    const totalAbsences = Number(attendanceStats.total_absences || 0);
    const level = currentSession.student && currentSession.student.level ? currentSession.student.level : "--";

    if (gradeValue) {
        gradeValue.textContent = semesterAverage;
    }
    if (attendanceRateValue) {
        attendanceRateValue.textContent = `${attendanceRate}%`;
    }
    if (totalAbsencesValue) {
        totalAbsencesValue.textContent = String(totalAbsences);
    }
    if (levelValue) {
        levelValue.textContent = level;
    }
}

function logout() {
    currentSession = null;
    currentSemester = "S1";
    sessionStorage.removeItem(PORTAL_SESSION_KEY);

    const loginSection = document.getElementById("loginSection");
    const dashboardSection = document.getElementById("dashboardSection");
    const form = document.getElementById("loginForm");

    if (loginSection) {
        loginSection.style.display = "flex";
    }
    if (dashboardSection) {
        dashboardSection.style.display = "none";
    }
    if (form) {
        form.reset();
    }

    clearLoginFeedback();
}

function setLoginFeedback(message, type) {
    const feedback = document.getElementById("loginFeedback");
    if (!feedback) {
        return;
    }

    feedback.className = `login-feedback ${type}`;
    feedback.textContent = message;
}

function clearLoginFeedback() {
    const feedback = document.getElementById("loginFeedback");
    if (!feedback) {
        return;
    }

    feedback.className = "login-feedback";
    feedback.textContent = "";
}

function formatDate(dateString) {
    if (!dateString) {
        return "-";
    }

    const date = new Date(dateString);
    if (Number.isNaN(date.getTime())) {
        return dateString;
    }

    return date.toLocaleDateString("fr-FR", {
        year: "numeric",
        month: "2-digit",
        day: "2-digit",
    });
}

function escapeHtml(value) {
    const safeValue = value === null || value === undefined ? "" : value;
    return String(safeValue)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#39;");
}

async function loadAnnouncements() {
    const container = document.getElementById("announcementsList");
    if (!container || !currentSession) {
        return;
    }

    try {
        const res = await fetch("backend/portal_announcements.php");
        const data = await res.json();
        
        if (data.success) {
            const announcements = data.data.announcements;
            if (announcements.length === 0) {
                container.innerHTML = '<p class="text-center">Aucune annonce pour le moment.</p>';
                return;
            }
            
            let html = "";
            announcements.forEach(ann => {
                const isUrgent = ann.type === 'urgent';
                const icon = isUrgent ? 'exclamation-circle' : 'info-circle';
                const date = formatDate(ann.created_at);
                const attachmentUrl = escapeHtml(ann.file_path || ann.attachment_url || '');
                const attachmentHtml = attachmentUrl ? `
                    <div style="margin-top: 12px; display: flex; gap: 8px; flex-wrap: wrap;">
                        <a href="${attachmentUrl}" target="_blank" rel="noopener noreferrer"
                           style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 12px; border-radius: 6px; background: #667eea; color: white; text-decoration: none;">
                            <i class="fa-solid fa-eye"></i> عرض المرفق
                        </a>
                        <a href="${attachmentUrl}" download rel="noopener noreferrer"
                           style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 12px; border-radius: 6px; background: #f3f4f6; color: #374151; text-decoration: none;">
                            <i class="fa-solid fa-download"></i> تحميل
                        </a>
                    </div>` : '';
                
                html += `
                    <div class="announcement-item ${isUrgent ? 'urgent' : ''}" style="margin-bottom: 15px; padding: 15px; border: 1px solid #e5e7eb; border-radius: 8px; border-right: 4px solid ${isUrgent ? '#ef4444' : '#10b981'};">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                            <strong><i class="fas fa-${icon}" style="color: ${isUrgent ? '#ef4444' : '#3b82f6'};"></i> ${escapeHtml(ann.title)}</strong>
                            <small class="text-muted"><i class="fas fa-calendar-alt"></i> ${escapeHtml(date)}</small>
                        </div>
                        <p style="margin: 0; line-height: 1.5;">${escapeHtml(ann.content).replace(/\n/g, '<br>')}</p>
                        ${attachmentHtml}
                        <small class="text-muted" style="display: block; margin-top: 8px;">De: ${escapeHtml(ann.teacher_name)}</small>
                    </div>
                `;
            });
            container.innerHTML = html;
        } else {
            container.innerHTML = '<p class="text-center" style="color: red;">Erreur lors du chargement des annonces.</p>';
        }
    } catch (e) {
        console.error("Failed to load announcements", e);
        container.innerHTML = '<p class="text-center" style="color: red;">Erreur réseau.</p>';
    }
}
