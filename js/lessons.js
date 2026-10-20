document.addEventListener("DOMContentLoaded", () => {
    const lessonsGrid = document.getElementById("lessonsGrid");
    const lessonsFeedback = document.getElementById("lessonsFeedback");
    const lessonsSearch = document.getElementById("lessonsSearch");
    const typeTabs = Array.from(document.querySelectorAll(".type-tab[data-type]"));

    let activeType = "";
    let activeSearch = "";
    let cache = [];
    const manualLessons = Array.isArray(window.MANUAL_LESSONS) ? window.MANUAL_LESSONS : [];

    function escapeHtml(value) {
        const safeValue = value === null || value === undefined ? "" : value;
        return String(safeValue)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#39;");
    }

    function setFeedback(message, type = "") {
        if (!lessonsFeedback) {
            return;
        }
        lessonsFeedback.className = type ? `feedback ${type}` : "feedback";
        lessonsFeedback.textContent = message;
    }

    function getTypeLabel(type) {
        if (type === "video") {
            return "فيديو";
        }
        if (type === "pdf") {
            return "PDF";
        }
        if (type === "image") {
            return "صورة";
        }
        return "محتوى";
    }

    function getTypeIcon(type) {
        if (type === "video") {
            return "fa-solid fa-video";
        }
        if (type === "pdf") {
            return "fa-solid fa-file-pdf";
        }
        if (type === "image") {
            return "fa-solid fa-image";
        }
        return "fa-solid fa-book";
    }

    function formatDate(dateValue) {
        const date = new Date(dateValue);
        if (Number.isNaN(date.getTime())) {
            return "-";
        }
        return date.toLocaleDateString("ar-MA");
    }

    function renderLessons(lessons) {
        if (!lessonsGrid) {
            return;
        }

        if (!Array.isArray(lessons) || lessons.length === 0) {
            lessonsGrid.innerHTML = `
                <article class="lesson-card lesson-card-empty">
                    <p class="muted">لا توجد دروس مطابقة حاليا.</p>
                </article>
            `;
            return;
        }

        lessonsGrid.innerHTML = lessons
            .map((lesson) => {
                const type = String(lesson.lesson_type || "");
                const thumb = String(lesson.thumbnail_url || "").trim();
                const title = String(lesson.title || "");
                const description = String(lesson.description || "بدون وصف.");
                const level = String(lesson.level || "غير محدد");
                const subjectName = String(lesson.subject_name || "عام");
                const contentUrl = String(lesson.content_url || "#");
                const createdAt = formatDate(lesson.created_at);

                const coverHtml = thumb
                    ? `<img class="lesson-cover" src="${escapeHtml(thumb)}" alt="${escapeHtml(title)}">`
                    : `<div class="lesson-cover fallback"><i class="${escapeHtml(getTypeIcon(type))}"></i></div>`;

                return `
                    <article class="lesson-card">
                        ${coverHtml}
                        <div class="lesson-top">
                            <span class="pill ${escapeHtml(type)}"><i class="${escapeHtml(getTypeIcon(type))}"></i> ${escapeHtml(getTypeLabel(type))}</span>
                            <span class="pill">${escapeHtml(level)}</span>
                            <span class="pill">${escapeHtml(subjectName)}</span>
                        </div>
                        <h3>${escapeHtml(title)}</h3>
                        <p>${escapeHtml(description)}</p>
                        <div class="lesson-actions">
                            <span class="lesson-date">تاريخ النشر: ${escapeHtml(createdAt)}</span>
                            <a class="btn btn-primary" href="${escapeHtml(contentUrl)}" target="_blank" rel="noopener noreferrer">
                                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                فتح
                            </a>
                        </div>
                    </article>
                `;
            })
            .join("");
    }

    function applyLocalFilter() {
        const searchTerm = activeSearch.trim().toLowerCase();
        const filtered = cache.filter((lesson) => {
            const typeOk = activeType === "" || String(lesson.lesson_type || "") === activeType;
            if (!typeOk) {
                return false;
            }

            if (searchTerm === "") {
                return true;
            }

            const haystack = [
                lesson.title,
                lesson.description,
                lesson.level,
                lesson.subject_name,
            ]
                .map((item) => String(item || "").toLowerCase())
                .join(" ");
            return haystack.includes(searchTerm);
        });

        renderLessons(filtered);
    }

    function normalizeLesson(rawLesson) {
        const lesson = rawLesson || {};
        return {
            id: lesson.id || `manual-${Math.random().toString(36).slice(2, 9)}`,
            title: String(lesson.title || "").trim(),
            description: String(lesson.description || "").trim(),
            lesson_type: String(lesson.lesson_type || "").trim().toLowerCase(),
            level: String(lesson.level || "").trim(),
            subject_name: String(lesson.subject_name || "").trim(),
            content_url: String(lesson.content_url || "").trim(),
            thumbnail_url: String(lesson.thumbnail_url || "").trim(),
            created_at: String(lesson.created_at || "").trim(),
        };
    }

    function loadManualLessons(message = "") {
        cache = manualLessons.map(normalizeLesson).filter((lesson) => lesson.title !== "" && lesson.content_url !== "");
        if (message !== "") {
            setFeedback(message, "success");
        } else {
            setFeedback("تم تحميل الدروس من الملف اليدوي.");
        }
        applyLocalFilter();
    }

    async function loadLessons() {
        setFeedback("جاري تحميل الدروس...");
        if (lessonsGrid) {
            lessonsGrid.innerHTML = `
                <article class="lesson-card lesson-card-empty">
                    <p class="muted"><i class="fa-solid fa-spinner fa-spin"></i> جاري تحميل الدروس...</p>
                </article>
            `;
        }

        const formData = new FormData();
        try {
            const response = await fetch("backend/lessons_list.php", {
                method: "POST",
                body: formData,
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                },
            });
            const payload = await response.json();
            if (!payload.success) {
                if (manualLessons.length > 0) {
                    loadManualLessons("تم التحويل إلى الوضع اليدوي (بدون قاعدة بيانات).");
                    return;
                }
                setFeedback(payload.message || "تعذر تحميل الدروس.", "error");
                renderLessons([]);
                return;
            }

            cache = Array.isArray(payload.data && payload.data.lessons) ? payload.data.lessons : [];
            setFeedback("");
            applyLocalFilter();
        } catch (error) {
            console.error("Lessons load error:", error);
            if (manualLessons.length > 0) {
                loadManualLessons("تعذر الاتصال بقاعدة البيانات. تم تحميل الدروس من الملف اليدوي.");
                return;
            }
            setFeedback("تعذر الاتصال بالخادم أثناء جلب الدروس.", "error");
            renderLessons([]);
        }
    }

    typeTabs.forEach((tab) => {
        tab.addEventListener("click", () => {
            typeTabs.forEach((item) => item.classList.remove("is-active"));
            tab.classList.add("is-active");
            activeType = String(tab.getAttribute("data-type") || "");
            applyLocalFilter();
        });
    });

    if (lessonsSearch) {
        lessonsSearch.addEventListener("input", () => {
            activeSearch = lessonsSearch.value || "";
            applyLocalFilter();
        });
    }

    loadLessons();
});
