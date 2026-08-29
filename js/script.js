// ========== MENU MOBILE TOGGLE ==========
document.documentElement.classList.add("has-animations");

const menuToggle = document.getElementById('menuToggle');
const navMenu = document.getElementById('navMenu');

if (menuToggle && navMenu) {
    menuToggle.addEventListener('click', () => {
        navMenu.classList.toggle('active');
        
        // تغيير الأيقونة
        const icon = menuToggle.querySelector('i');
        if (icon) {
            if (navMenu.classList.contains('active')) {
                icon.classList.remove('fa-bars');
                icon.classList.add('fa-times');
                document.body.style.overflow = 'hidden'; // منع التمرير
            } else {
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
                document.body.style.overflow = '';
            }
        }
    });
}

// إغلاق القائمة عند النقر على رابط
document.querySelectorAll('.nav-menu a, .nav-list a').forEach(link => {
    link.addEventListener('click', () => {
        if (navMenu && navMenu.classList.contains('active')) {
            navMenu.classList.remove('active');
            const icon = menuToggle ? menuToggle.querySelector('i') : null;
            if (icon) {
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            }
            document.body.style.overflow = '';
        }
    });
});

// ========== ACTIVE NAVIGATION BASED ON URL ==========
const currentLocation = window.location.pathname.split('/').pop() || 'index.html';
const navLinks = document.querySelectorAll('.nav-list a, .nav-menu a');

navLinks.forEach(link => {
    const linkPath = link.getAttribute('href');
    if (linkPath && currentLocation.includes(linkPath) && linkPath !== '#') {
        link.classList.add('active');
        link.setAttribute('aria-current', 'page');
    }
});

// ========== SMOOTH SCROLL FOR ANCHOR LINKS ==========
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const targetId = this.getAttribute('href');
        if (targetId && targetId !== '#') {
            const target = document.querySelector(targetId);
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        }
    });
});

// ========== NAVBAR BACKGROUND CHANGE ON SCROLL ==========
const navbar = document.querySelector('.navbar');
const heroSection = document.querySelector('.hero');
let scrollTicking = false;
const requestFrame = window.requestAnimationFrame
    ? window.requestAnimationFrame.bind(window)
    : (callback) => setTimeout(callback, 16);

function updateScrollVisuals() {
    const scrollY = window.scrollY || window.pageYOffset || 0;

    if (navbar) {
        navbar.classList.toggle('navbar-scrolled', scrollY > 100);
    }

    if (heroSection) {
        const heroHeight = Math.max(heroSection.offsetHeight, 1);
        const progress = Math.min(Math.max(scrollY / heroHeight, 0), 1);
        document.documentElement.style.setProperty('--scroll-progress', progress.toFixed(3));
    }

    scrollTicking = false;
}

function handleScrollVisuals() {
    if (scrollTicking) {
        return;
    }

    scrollTicking = true;
    requestFrame(updateScrollVisuals);
}

window.addEventListener('scroll', handleScrollVisuals, { passive: true });
window.addEventListener('resize', updateScrollVisuals);
updateScrollVisuals();

// ========== DATA-REVEAL ANIMATION ==========
const revealElements = Array.from(document.querySelectorAll("[data-reveal]"));
const aosElements = Array.from(document.querySelectorAll("[data-aos]"));

if (revealElements.length > 0) {
    revealElements.forEach((element, index) => {
        if (!element.style.getPropertyValue("--delay")) {
            element.style.setProperty("--delay", `${Math.min(index * 0.06, 0.3)}s`);
        }
    });
}

if (aosElements.length > 0) {
    aosElements.forEach((element) => {
        const delayMs = Number.parseInt(element.getAttribute("data-aos-delay") || "0", 10);
        if (Number.isFinite(delayMs) && delayMs > 0) {
            element.style.transitionDelay = `${delayMs}ms`;
        }
    });
}

const animatedElements = Array.from(new Set([...revealElements, ...aosElements]));
if (animatedElements.length > 0) {
    if ("IntersectionObserver" in window) {
        const observer = new IntersectionObserver(
            (entries, observerInstance) => {
                entries.forEach((entry) => {
                    if (!entry.isIntersecting) {
                        return;
                    }

                    if (entry.target.hasAttribute("data-reveal")) {
                        entry.target.classList.add("revealed");
                    }

                    if (entry.target.hasAttribute("data-aos")) {
                        entry.target.classList.add("aos-animate");
                    }

                    observerInstance.unobserve(entry.target);
                });
            },
            {
                threshold: 0.16,
            }
        );

        animatedElements.forEach((element) => observer.observe(element));
    } else {
        revealElements.forEach((element) => element.classList.add("revealed"));
        aosElements.forEach((element) => element.classList.add("aos-animate"));
    }
}

// ========== ANIMATION STATS COUNTER ==========
const stats = document.querySelectorAll('.stat-number');
let animated = false;

function animateStats() {
    stats.forEach(stat => {
        const text = stat.innerText;
        const target = parseInt(text.replace(/[^0-9]/g, ''));
        const hasPercent = text.includes('%');
        const hasPlus = text.includes('+');
        
        if (isNaN(target)) return;
        
        let current = 0;
        const increment = Math.ceil(target / 50);
        
        const updateCount = () => {
            if (current < target) {
                current += increment;
                if (current > target) current = target;
                
                let displayText = current.toString();
                if (hasPercent) displayText += '%';
                if (hasPlus) displayText += '+';
                
                stat.innerText = displayText;
                setTimeout(updateCount, 30);
            } else {
                let displayText = target.toString();
                if (hasPercent) displayText += '%';
                if (hasPlus) displayText += '+';
                stat.innerText = displayText;
            }
        };
        
        updateCount();
    });
}

// تفعيل إحصائيات التمرير عند رؤيتها
window.addEventListener('scroll', () => {
    const heroStats = document.querySelector('.hero-stats');
    if (heroStats && !animated) {
        const rect = heroStats.getBoundingClientRect();
        if (rect.top < window.innerHeight && rect.bottom > 0) {
            animated = true;
            animateStats();
        }
    }
});

// ========== INITIALIZE AOS (Animation on Scroll) ==========
document.addEventListener('DOMContentLoaded', function() {
    if (typeof AOS !== 'undefined') {
        AOS.init({
            duration: 1000,
            once: true,
            offset: 100
        });
    }
});

// ========== UTILITY FUNCTIONS ==========
function escapeHtml(value) {
    const safeValue = value === null || value === undefined ? "" : value;
    return String(safeValue)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#39;");
}

async function postFormAsJson(form) {
    const response = await fetch(form.action, {
        method: "POST",
        body: new FormData(form),
        headers: {
            "X-Requested-With": "XMLHttpRequest",
        },
    });

    return response.json();
}

function setFeedback(element, message, type) {
    if (!element) {
        return;
    }

    element.className = `feedback ${type}`;
    element.textContent = message;
}

// ========== STUDENT REGISTRATION FORM ==========
const studentRegistrationForm = document.getElementById("studentRegistrationForm");
const registrationFeedback = document.getElementById("registrationFeedback");

if (studentRegistrationForm) {
    studentRegistrationForm.addEventListener("submit", async (event) => {
        event.preventDefault();

        const submitButton = studentRegistrationForm.querySelector("button[type='submit']");
        const initialHtml = submitButton ? submitButton.innerHTML : "";
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> جاري الحفظ...';
        }

        try {
            const payload = await postFormAsJson(studentRegistrationForm);
            if (!payload.success) {
                setFeedback(registrationFeedback, payload.message || "تعذر حفظ التسجيل.", "error");
                return;
            }

            const studentName = `${document.getElementById('first_name').value} ${document.getElementById('last_name').value}`;
            const studentCode = payload.data && payload.data.student_code ? payload.data.student_code : "-";

            const panel = studentRegistrationForm.closest('.panel');
            if (panel) {
                panel.innerHTML = `
                    <div class="registration-success-card">
                        <div class="success-icon">
                            <i class="fa-solid fa-check"></i>
                        </div>
                        <h3>تم استلام طلب التسجيل بنجاح!</h3>
                        <p class="muted" style="margin-bottom: 2rem;">
                            شكراً لك <strong>${escapeHtml(studentName)}</strong>. <br>
                            تم تسجيل طلبك بالكود <strong>${escapeHtml(studentCode)}</strong> وهو قيد المراجعة حالياً.
                        </p>
                        <div style="display: flex; gap: 1rem; justify-content: center;">
                            <button class="btn btn-primary" onclick="window.location.reload()">
                                <i class="fa-solid fa-plus"></i> تسجيل تلميذ آخر
                            </button>
                            <a href="index.html" class="btn btn-outline">
                                <i class="fa-solid fa-house"></i> العودة للرئيسية
                            </a>
                        </div>
                    </div>
                `;
            } else {
                setFeedback(registrationFeedback, "تم تسجيل الطالب بنجاح.", "success");
                studentRegistrationForm.reset();
            }
        } catch (error) {
            console.error("Registration error:", error);
            setFeedback(registrationFeedback, "وقع خطأ تقني أثناء التسجيل.", "error");
        } finally {
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.innerHTML = initialHtml;
            }
        }
    });
}

// ========== TRANSCRIPT FORM ==========
const transcriptForm = document.getElementById("transcriptForm");
const transcriptResult = document.getElementById("transcriptResult");

function getPrintTimestamp() {
    const now = new Date();
    const date = now.toLocaleDateString("ar-MA", { year: 'numeric', month: 'long', day: 'numeric' });
    const time = now.toLocaleTimeString("ar-MA", { hour: "2-digit", minute: "2-digit" });
    return { date, time };
}

function renderPrintHeader(title) {
    const stamp = getPrintTimestamp();
    return `
        <div class="print-header">
            <div class="print-brand">
                <img class="print-logo" src="images/kawkab_alouloum.jpeg" alt="Kawkab Al Ouloum">
                <div class="print-brand-text">
                    <div class="print-school">مؤسسة Kawkab Al Ouloum التعليمية</div>
                    <div class="print-contact">
                        <p><i class="fa-solid fa-location-dot"></i> المغرب -مؤسسة كوكب العلوم،المحاميد, مراكش</p>
                        <p><i class="fa-solid fa-phone"></i> 0676769888 | <i class="fa-solid fa-envelope"></i> contact@kawkab-ouloum.ma</p>
                    </div>
                </div>
            </div>
            <div class="print-meta">
                <div class="print-title">${escapeHtml(title)}</div>
                <div class="print-date">حرر بتاريخ: ${escapeHtml(stamp.date)}</div>
            </div>
        </div>
    `;
}

function triggerPrint(mode) {
    const body = document.body;
    if (!body) {
        window.print();
        return;
    }

    const prevMode = body.getAttribute("data-print-mode");
    body.setAttribute("data-print-mode", mode);
    
    window.print();
    
    // Simple restoration after a delay
    setTimeout(() => {
        if (prevMode) body.setAttribute("data-print-mode", prevMode);
        else body.removeAttribute("data-print-mode");
    }, 1000);
}

function renderTranscript(payload) {
    if (!transcriptResult) {
        return;
    }

    if (!payload.success) {
        transcriptResult.innerHTML = `<p class="muted">${escapeHtml(payload.message || "تعذر استخراج بيان النقط.")}</p>`;
        return;
    }

    const student = payload.data.student || {};
    const grades = payload.data.grades || [];
    const summary = payload.data.summary || {};

    const rows = grades
        .map(
            (grade) => {
                const avg = parseFloat(grade.average) || 0;
                let avgClass = '';
                if (avg >= 10) avgClass = 'score-good';
                else if (avg >= 5) avgClass = 'score-warning';
                else avgClass = 'score-bad';
                
                return `
        <tr>
            <td>${escapeHtml(grade.subject_name || '')}</td>
            <td><span class="score-normal">${escapeHtml(grade.continuous_score || '')}</span></td>
            <td><span class="score-normal">${escapeHtml(grade.exam_score || '')}</span></td>
            <td>${escapeHtml(grade.coefficient || '')}</td>
            <td><strong class="${avgClass}">${escapeHtml(grade.average || '')}</strong></td>
        </tr>
    `;
            }
        )
        .join("");

    transcriptResult.innerHTML = `
        <div class="print-document print-transcript">
            ${renderPrintHeader("بيان النقط السنوي")}
            <div class="result-meta">
                <span class="chip">الطالب(ة): <strong>${escapeHtml(student.full_name || '')}</strong></span>
                <span class="chip">رقم المسار: <strong>${escapeHtml(student.student_code || '')}</strong></span>
                <span class="chip">القسم: <strong>${escapeHtml(student.class_name || '')}</strong></span>
                <span class="chip">المستوى: <strong>${escapeHtml(student.level || '')}</strong></span>
            </div>
            
            <div class="transcript-stats">
                <div class="attendance-card">
                    <span class="stat-label">المعدل العام</span>
                    <strong class="stat-value highlight">${escapeHtml(summary.general_average || "-")}</strong>
                </div>
                <div class="attendance-card">
                    <span class="stat-label">الميزة</span>
                    <strong class="stat-value">${escapeHtml(summary.mention || "-")}</strong>
                </div>
                <div class="attendance-card">
                    <span class="stat-label">تاريخ الاصدار</span>
                    <strong class="stat-value" style="font-size: 1.1rem;">${escapeHtml(getPrintTimestamp().date)}</strong>
                </div>
            </div>
            
            <table class="result-table">
                
                <div class="action-buttons no-print" style="display: flex; gap: 10px; margin-top: 15px; justify-content: center;">
                    <button type="button" class="btn btn-outline" id="printTranscriptButton">
                        <i class="fa-solid fa-print"></i> طباعة البيان
                    </button>
                    <button type="button" class="btn btn-primary" id="downloadTranscriptPDF">
                        <i class="fa-solid fa-file-pdf"></i> تحميل PDF
                    </button>
                </div>
            </div>

            ${(payload.data && Array.isArray(payload.data.notifications) && payload.data.notifications.length > 0) ? `
                <div class="notifications-history no-print" style="margin-top: 1.5rem; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 0.8rem; padding: 1rem;">
                    <h4 style="margin: 0 0 0.8rem; color: #166534; font-size: 1rem;"><i class="fa-brands fa-whatsapp"></i> سجل إشعارات ورسائل ولي الأمر</h4>
                    <div style="display: flex; flex-direction: column; gap: 0.6rem;">
                        ${payload.data.notifications.map(n => `
                            <div style="background: white; border: 1px solid #dcfce7; padding: 0.8rem; border-radius: 0.6rem;">
                                <strong style="color: #15803d; font-size: 0.9rem;">${escapeHtml(n.title)}</strong>
                                <p style="margin: 0.3rem 0; font-size: 0.85rem; color: #374151;">${escapeHtml(n.message)}</p>
                                <small style="color: #9ca3af; font-size: 0.75rem;">📅 ${escapeHtml(n.created_at)}</small>
                            </div>
                        `).join('')}
                    </div>
                </div>
            ` : ''}

            <div class="print-signatures">
                <div class="print-signature">
                    <p>توقيع وختم السيد المدير</p>
                    <div class="signature-space"></div>
                </div>
                <div class="print-signature">
                    <p>توقيع ولي الأمر</p>
                    <div class="signature-space"></div>
                </div>
            </div>
        </div>
    `;

    const printButton = document.getElementById("printTranscriptButton");
    if (printButton) {
        printButton.addEventListener("click", () => triggerPrint("transcript"));
    }

    const downloadButton = document.getElementById("downloadTranscriptPDF");
    if (downloadButton) {
        downloadButton.addEventListener("click", () => downloadTranscriptAsPDF(student));
    }
}

function downloadTranscriptAsPDF(student) {
    const element = document.querySelector('.print-transcript');
    if (!element) return;

    const opt = {
        margin: 10,
        filename: `بيان_نقط_${student.full_name || 'student'}.pdf`,
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2, useCORS: true },
        jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
    };

    html2pdf().set(opt).from(element).save();
}

if (transcriptForm) {
    transcriptForm.addEventListener("submit", async (event) => {
        event.preventDefault();
        if (transcriptResult) {
            transcriptResult.innerHTML = '<p class="muted">جاري استخراج بيان النقط...</p>';
        }

        try {
            const payload = await postFormAsJson(transcriptForm);
            renderTranscript(payload);
        } catch (error) {
            console.error("Transcript error:", error);
            if (transcriptResult) {
                transcriptResult.innerHTML = '<p class="muted">تعذر الاتصال بالخادم.</p>';
            }
        }
    });
}

// ========== ATTENDANCE FORM ==========
const attendanceForm = document.getElementById("attendanceForm");
const attendanceResult = document.getElementById("attendanceResult");

function renderAttendance(payload) {
    if (!attendanceResult) {
        return;
    }

    if (!payload.success) {
        attendanceResult.innerHTML = `<p class="muted">${escapeHtml(payload.message || "تعذر عرض الغياب.")}</p>`;
        return;
    }

    const student = payload.data.student || {};
    const stats = payload.data.stats || { total_absences: 0, justified_absences: 0, unjustified_absences: 0 };
    const records = payload.data.records || [];

    const rows = records
        .map(
            (record) => `
        <tr>
            <td>${escapeHtml(record.absence_date || '')}</td>
            <td>${escapeHtml(record.session_label || '')}</td>
            <td>${escapeHtml(record.absence_subject || '-')}</td>
            <td>${record.justified ? "مبرر" : "غير مبرر"}</td>
            <td>${escapeHtml(record.notes || "-")}</td>
        </tr>
    `
        )
        .join("");

    attendanceResult.innerHTML = `
        <div class="print-document print-attendance">
            ${renderPrintHeader("سجل الغياب والتأخر")}
            <div class="result-meta">
                <span class="chip">الطالب(ة): <strong>${escapeHtml(student.full_name || '')}</strong></span>
                <span class="chip">القسم: <strong>${escapeHtml(student.class_name || '')}</strong></span>
                <span class="chip">ولي الأمر: <strong>${escapeHtml(student.guardian_name || '')}</strong></span>
            </div>
            
            <div class="attendance-stats">
                <div class="attendance-card">
                    <span class="stat-label">مجموع الغياب</span>
                    <strong class="stat-value">${escapeHtml(stats.total_absences)}</strong>
                </div>
                <div class="attendance-card justified">
                    <span class="stat-label">غياب مبرر</span>
                    <strong class="stat-value">${escapeHtml(stats.justified_absences)}</strong>
                </div>
                <div class="attendance-card unjustified">
                    <span class="stat-label">غياب غير مبرر</span>
                    <strong class="stat-value">${escapeHtml(stats.unjustified_absences)}</strong>
                </div>
            </div>

            <table class="result-table">
                <thead>
                    <tr>
                        <th>التاريخ</th>
                        <th>الحصة</th>
                        <th>المادة</th>
                        <th>الحالة</th>
                        <th>ملاحظات</th>
                    </tr>
                </thead>
                <tbody>${rows || '<tr><td colspan="5" style="text-align:center;">لا يوجد غياب مسجل لهذا التلميذ.</td></tr>'}</tbody>
            </table>

            <div class="summary-box no-print">
                <div class="action-buttons" style="display: flex; gap: 10px;">
                    <button type="button" class="btn btn-outline" id="printAttendanceButton">
                        <i class="fa-solid fa-print"></i> طباعة السجل
                    </button>
                    <button type="button" class="btn btn-primary" id="downloadAttendancePDF">
                        <i class="fa-solid fa-file-pdf"></i> تحميل PDF
                    </button>
                </div>
            </div>

            ${(payload.data && Array.isArray(payload.data.notifications) && payload.data.notifications.length > 0) ? `
                <div class="notifications-history no-print" style="margin-top: 1.5rem; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 0.8rem; padding: 1rem;">
                    <h4 style="margin: 0 0 0.8rem; color: #166534; font-size: 1rem;"><i class="fa-brands fa-whatsapp"></i> سجل إشعارات ورسائل ولي الأمر</h4>
                    <div style="display: flex; flex-direction: column; gap: 0.6rem;">
                        ${payload.data.notifications.map(n => `
                            <div style="background: white; border: 1px solid #dcfce7; padding: 0.8rem; border-radius: 0.6rem;">
                                <strong style="color: #15803d; font-size: 0.9rem;">${escapeHtml(n.title)}</strong>
                                <p style="margin: 0.3rem 0; font-size: 0.85rem; color: #374151;">${escapeHtml(n.message)}</p>
                                <small style="color: #9ca3af; font-size: 0.75rem;">📅 ${escapeHtml(n.created_at)}</small>
                            </div>
                        `).join('')}
                    </div>
                </div>
            ` : ''}

            <div class="print-signatures">
                <div class="print-signature">
                    <p>ختم وتوقيع الإدارة</p>
                    <div class="signature-space"></div>
                </div>
                <div class="print-signature">
                    <p>توقيع ولي الأمر</p>
                    <div class="signature-space"></div>
                </div>
            </div>
        </div>
    `;

    const printButton = document.getElementById("printAttendanceButton");
    if (printButton) {
        printButton.addEventListener("click", () => triggerPrint("attendance"));
    }

    const downloadButton = document.getElementById("downloadAttendancePDF");
    if (downloadButton) {
        downloadButton.addEventListener("click", () => downloadAttendanceAsPDF(student));
    }
}

function downloadAttendanceAsPDF(student) {
    const element = document.querySelector('.print-attendance');
    if (!element) return;

    const opt = {
        margin: 10,
        filename: `سجل_غياب_${student.full_name || 'student'}.pdf`,
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2, useCORS: true },
        jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
    };

    html2pdf().set(opt).from(element).save();
}

if (attendanceForm) {
    attendanceForm.addEventListener("submit", async (event) => {
        event.preventDefault();
        if (attendanceResult) {
            attendanceResult.innerHTML = '<p class="muted">جاري جلب سجل الغياب...</p>';
        }

        try {
            const payload = await postFormAsJson(attendanceForm);
            renderAttendance(payload);
        } catch (error) {
            console.error("Attendance error:", error);
            if (attendanceResult) {
                attendanceResult.innerHTML = '<p class="muted">تعذر الاتصال بالخادم.</p>';
            }
        }
    });
}

// ========== CONTACT FORM ==========
const contactForm = document.getElementById("contactForm");
const contactFeedback = document.getElementById("contactFeedback");

if (contactForm) {
    contactForm.addEventListener("submit", async (event) => {
        event.preventDefault();

        const submitButton = contactForm.querySelector("button[type='submit']");
        const initialHtml = submitButton ? submitButton.innerHTML : "";
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> جاري الإرسال...';
        }

        try {
            const payload = await postFormAsJson(contactForm);
            if (!payload.success) {
                setFeedback(contactFeedback, payload.message || "تعذر إرسال الرسالة.", "error");
                return;
            }

            setFeedback(contactFeedback, "تم إرسال الرسالة بنجاح، شكرا لتواصلك.", "success");
            contactForm.reset();
        } catch (error) {
            console.error("Contact error:", error);
            setFeedback(contactFeedback, "وقع خطأ أثناء إرسال الرسالة.", "error");
        } finally {
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.innerHTML = initialHtml;
            }
        }
    });
}

// ========== GALLERY PREVIEW IN INDEX ==========
class GalleryPreview {
    constructor() {
        this.galleryGrid = document.getElementById('galleryGridIndex');
        this.filterButtons = document.querySelectorAll('.filter-btn');
        this.currentFilter = 'all';
        this.photos = [];
        
        if (this.galleryGrid) {
            this.loadPhotos();
            this.setupFilters();
        }
    }
    
    async loadPhotos() {
        try {
            const response = await fetch('backend/gallery_api.php?action=list');
            if (!response.ok) throw new Error('Failed to load photos');
            
            const data = await response.json();
            if (data.success) {
                this.photos = data.data || [];
                this.renderPhotos();
            }
        } catch (error) {
            console.error('Gallery load error:', error);
        }
    }
    
    renderPhotos() {
        if (!this.galleryGrid) return;
        
        // عرض أول 6 صور فقط في الصفحة الرئيسية
        const photosToShow = this.photos.slice(0, 6);
        
        this.galleryGrid.innerHTML = photosToShow.map(photo => `
            <div class="gallery-item" data-category="${photo.category}">
                <img src="${photo.image_path}" alt="${photo.title}" loading="lazy">
                <div class="gallery-item-overlay">
                    <div class="gallery-item-overlay-content">
                        <h3>${photo.title}</h3>
                        <p>${photo.description || 'صورة من المعرض'}</p>
                    </div>
                </div>
            </div>
        `).join('');
        
        // إضافة حدث النقر للانتقال إلى المعرض الكامل
        document.querySelectorAll('#galleryGridIndex .gallery-item').forEach(item => {
            item.addEventListener('click', () => {
                const category = item.dataset.category;
                window.location.href = `gallery.html?category=${category}`;
            });
        });
    }
    
    setupFilters() {
        this.filterButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                this.filterButtons.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                this.currentFilter = btn.dataset.filter;
                this.applyFilter();
            });
        });
    }
    
    applyFilter() {
        const items = document.querySelectorAll('#galleryGridIndex .gallery-item');
        
        items.forEach(item => {
            const category = item.dataset.category;
            
            if (this.currentFilter === 'all' || this.currentFilter === category) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        });
    }
}

// تهيئة معاينة المعرض عند تحميل الصفحة
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        new GalleryPreview();
    });
} else {
    new GalleryPreview();
}
// ========== CONTACT MAP ==========
const contactMapContainer = document.querySelector("[data-contact-map]");

if (contactMapContainer) {
    const mapQuery = (contactMapContainer.getAttribute("data-map-query") || "").trim();

    if (mapQuery) {
        const encodedQuery = encodeURIComponent(mapQuery);
        const mapFrame = contactMapContainer.querySelector("#schoolMapFrame");
        const mapOpenLink = contactMapContainer.querySelector("[data-map-open]");

        if (mapFrame) {
            mapFrame.src = `https://www.google.com/maps?q=${encodedQuery}&hl=ar&z=16&output=embed`;
        }

        if (mapOpenLink) {
            mapOpenLink.href = `https://www.google.com/maps/search/?api=1&query=${encodedQuery}`;
        }
    }
}

// ========== NEWSLETTER FORM ==========
const newsletterForm = document.querySelector('.newsletter-form');

if (newsletterForm) {
    newsletterForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const emailInput = this.querySelector('input[type="email"]');
        const email = emailInput ? emailInput.value : '';
        
        if (email) {
            const btn = this.querySelector('button');
            if (btn) {
                const originalHtml = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                btn.disabled = true;
                
                setTimeout(() => {
                    btn.innerHTML = '<i class="fas fa-check"></i>';
                    btn.style.background = '#28a745';
                    
                    setTimeout(() => {
                        btn.innerHTML = originalHtml;
                        btn.style.background = '';
                        btn.disabled = false;
                        if (emailInput) emailInput.value = '';
                    }, 2000);
                }, 1500);
            }
        }
    });
}

// ========== CARD HOVER EFFECTS (CSS handles this better) ==========
// نترك CSS يتعامل مع تأثيرات hover بدلاً من JavaScript
// هذا يحسن الأداء ويقلل من الأخطاء

// ========== CLOSE MOBILE MENU ON RESIZE ==========
window.addEventListener('resize', function() {
    if (window.innerWidth > 768 && navMenu && navMenu.classList.contains('active')) {
        navMenu.classList.remove('active');
        const icon = menuToggle ? menuToggle.querySelector('i') : null;
        if (icon) {
            icon.classList.remove('fa-times');
            icon.classList.add('fa-bars');
        }
        document.body.style.overflow = '';
    }
});

// ========== FILL TEST DATE FOR DEMO ==========
function fillTestDate() {
    const birthDateInput = document.getElementById('birth_date');
    if (birthDateInput && birthDateInput.value === '2008-04-16') {
        console.log('Test date selected');
    }
}
