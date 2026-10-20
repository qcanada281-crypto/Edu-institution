document.addEventListener("DOMContentLoaded", () => {
    console.log("DEBUG: teacher.js loaded - DOMContentLoaded");
    
    // Utility functions
    async function postForm(url, formData) {
        console.log("DEBUG: postForm called, URL:", url);
        const response = await fetch(url, {
            method: "POST",
            body: formData,
            credentials: "same-origin",
            headers: {
                "X-Requested-With": "XMLHttpRequest",
                Accept: "application/json",
            },
        });
        console.log("DEBUG: fetch response status:", response.status);

        let payload = null;
        try {
            payload = await response.json();
            console.log("DEBUG: JSON parsed:", payload);
        } catch (error) {
            console.error("DEBUG: JSON parse error:", error);
            throw new Error("Invalid JSON response");
        }

        return {
            status: response.status,
            payload,
        };
    }

    function showFeedback(element, message, type = "success") {
        if (!element) return;
        element.textContent = message;
        element.className = `feedback ${type}`;
        element.style.display = "block";
        setTimeout(() => {
            element.style.display = "none";
            element.textContent = "";
            element.className = "feedback";
        }, 5000);
    }

    // Panels
    const loginPanel = document.getElementById("teacherLoginPanel");
    const registerPanel = document.getElementById("teacherRegisterPanel");
    const dashboardPanel = document.getElementById("teacherDashboard");
    console.log("DEBUG: Panels found:", { loginPanel: !!loginPanel, registerPanel: !!registerPanel, dashboardPanel: !!dashboardPanel });

    // Toggles
    const showRegisterBtn = document.getElementById("showRegisterBtn");
    const showLoginBtn = document.getElementById("showLoginBtn");

    if (showRegisterBtn) {
        showRegisterBtn.addEventListener("click", () => {
            loginPanel.style.display = "none";
            registerPanel.style.display = "block";
        });
    }

    if (showLoginBtn) {
        showLoginBtn.addEventListener("click", () => {
            registerPanel.style.display = "none";
            loginPanel.style.display = "block";
        });
    }

    // Password visibility toggles
    document.querySelectorAll(".password-toggle").forEach((btn) => {
        btn.addEventListener("click", (e) => {
            const targetId = btn.getAttribute("data-toggle-target");
            const input = document.getElementById(targetId);
            if (input) {
                const isPassword = input.type === "password";
                input.type = isPassword ? "text" : "password";
                btn.classList.toggle("is-visible", isPassword);
                btn.setAttribute("aria-pressed", isPassword ? "true" : "false");
            }
        });
    });

    // Login Form
    const loginForm = document.getElementById("teacherLoginForm");
    const loginFeedback = document.getElementById("teacherLoginFeedback");

    if (loginForm) {
        loginForm.addEventListener("submit", async (e) => {
            e.preventDefault();
            const submitBtn = loginForm.querySelector('button[type="submit"]');
            submitBtn.disabled = true;

            try {
                const formData = new FormData(loginForm);
                console.log("DEBUG: Sending login request...");
                const { payload, status } = await postForm(loginForm.action, formData);
                console.log("DEBUG: Response payload:", payload);
                console.log("DEBUG: Response status:", status);

                if (!payload || !payload.success) {
                    console.log("DEBUG: Login failed - success is false");
                    showFeedback(loginFeedback, (payload && payload.message) ? payload.message : "فشل تسجيل الدخول.", "error");
                    return;
                }

                const user = payload.data && payload.data.user ? payload.data.user : null;
                console.log("DEBUG: User data:", user);
                if (!user) {
                    console.log("DEBUG: No user data in response");
                    showFeedback(loginFeedback, "تعذر تحميل بيانات الأستاذ.", "error");
                    return;
                }

                console.log("DEBUG: Login successful, switching panels...");
                console.log("DEBUG: loginPanel:", loginPanel);
                console.log("DEBUG: dashboardPanel:", dashboardPanel);
                showFeedback(loginFeedback, payload.message || "تم تسجيل الدخول بنجاح.", "success");
                loginPanel.style.display = "none";
                registerPanel.style.display = "none";
                dashboardPanel.style.display = "grid";
                console.log("DEBUG: dashboardPanel display after change:", dashboardPanel.style.display);
                document.getElementById("teacherIdentity").innerHTML = `<p class="identity-line"><i class="fa-solid fa-chalkboard-user"></i> ${user.full_name}</p>`;
                loadTeacherData();
            } catch (error) {
                console.error("DEBUG: Error during login:", error);
                showFeedback(loginFeedback, error.message, "error");
            } finally {
                submitBtn.disabled = false;
            }
        });
    }

    // Register Form
    const registerForm = document.getElementById("teacherRegisterForm");
    const registerFeedback = document.getElementById("teacherRegisterFeedback");

    if (registerForm) {
        registerForm.addEventListener("submit", async (e) => {
            e.preventDefault();
            const code = document.getElementById("reg_code").value;
            const codeConfirm = document.getElementById("reg_code_confirm").value;
            
            if (code !== codeConfirm) {
                showFeedback(registerFeedback, "كلمتا السر غير متطابقتين.", "error");
                return;
            }

            const submitBtn = registerForm.querySelector('button[type="submit"]');
            submitBtn.disabled = true;

            try {
                const formData = new FormData(registerForm);
                const { payload } = await postForm(registerForm.action, formData);

                if (!payload || !payload.success) {
                    showFeedback(registerFeedback, (payload && payload.message) ? payload.message : "فشل إرسال الطلب.", "error");
                    return;
                }

                showFeedback(registerFeedback, payload.message || "تم إرسال الطلب بنجاح.", "success");
                    registerForm.reset();
                    setTimeout(() => {
                        registerPanel.style.display = "none";
                        loginPanel.style.display = "block";
                    }, 2000);
            } catch (error) {
                showFeedback(registerFeedback, error.message, "error");
            } finally {
                submitBtn.disabled = false;
            }
        });
    }

    // Logout
    const logoutBtn = document.getElementById("teacherLogoutBtn");
    if (logoutBtn) {
        logoutBtn.addEventListener("click", async () => {
            const formData = new FormData();
            formData.append("action", "logout");
            try {
                await postForm("backend/admin_auth.php", formData);
            } catch(e) {}
            window.location.reload();
        });
    }

    // Tab switching
    const tabBtns = document.querySelectorAll(".tab-btn");
    const tabPanels = document.querySelectorAll(".tab-panel");

    tabBtns.forEach(btn => {
        btn.addEventListener("click", () => {
            tabBtns.forEach(b => b.classList.remove("is-active"));
            tabPanels.forEach(p => {
                p.classList.remove("is-active");
                p.hidden = true;
            });

            btn.classList.add("is-active");
            const targetId = btn.getAttribute("data-tab-target");
            const targetPanel = document.getElementById(targetId);
            if (targetPanel) {
                targetPanel.classList.add("is-active");
                targetPanel.hidden = false;
            }
        });
    });

    // Profile update
    const profileForm = document.getElementById("teacherProfileForm");
    if (profileForm) {
        profileForm.addEventListener("submit", async (e) => {
            e.preventDefault();
            const feedback = document.getElementById("teacherProfileFeedback");
            const formData = new FormData(profileForm);
            formData.append("action", "update");
            
            try {
                const { payload } = await postForm(profileForm.action, formData);
                showFeedback(feedback, (payload && payload.message) ? payload.message : "تعذر حفظ المعلومات.", payload && payload.success ? "success" : "error");
            } catch (error) {
                showFeedback(feedback, error.message, "error");
            }
        });
    }

    // Code update
    const codeForm = document.getElementById("teacherCodeForm");
    if (codeForm) {
        codeForm.addEventListener("submit", async (e) => {
            e.preventDefault();
            const feedback = document.getElementById("teacherCodeFeedback");
            const formData = new FormData(codeForm);
            formData.append("action", "change_code");
            
            try {
                const { payload } = await postForm(codeForm.action, formData);
                const ok = payload && payload.success;
                showFeedback(feedback, (payload && payload.message) ? payload.message : "تعذر تغيير الكود.", ok ? "success" : "error");
                if (ok) codeForm.reset();
            } catch (error) {
                showFeedback(feedback, error.message, "error");
            }
        });
    }

    // Timetable additions
    const timetableForm = document.getElementById("teacherTimetableForm");
    if (timetableForm) {
        timetableForm.addEventListener("submit", async (e) => {
            e.preventDefault();
            const feedback = document.getElementById("teacherTimetableFeedback");
            const formData = new FormData(timetableForm);
            formData.append("action", "save");
            
            try {
                const { payload } = await postForm(timetableForm.action, formData);
                const ok = payload && payload.success;
                showFeedback(feedback, (payload && payload.message) ? payload.message : "تعذر إضافة الحصة.", ok ? "success" : "error");
                if (ok) {
                    timetableForm.reset();
                    loadTeacherTimetable();
                }
            } catch (error) {
                showFeedback(feedback, error.message, "error");
            }
        });
    }

    // Load data
    async function loadTeacherData() {
        const formData = new FormData();
        formData.append("action", "get");
        
        try {
            const { payload } = await postForm("backend/teacher_profile.php", formData);
            const teacher = payload && payload.data && payload.data.teacher ? payload.data.teacher : null;
            if (payload && payload.success && teacher) {
                const t = teacher;
                document.getElementById("teacher_full_name").value = t.full_name || "";
                document.getElementById("teacher_phone").value = t.phone || "";
                document.getElementById("teacher_subject_name").value = t.subject_name || "";
                document.getElementById("teacher_bio").value = t.bio || "";
            }
            loadTeacherTimetable();
        } catch (e) {
            console.error("Failed to load teacher data", e);
        }
    }

    async function loadTeacherTimetable() {
        const formData = new FormData();
        formData.append("action", "list");
        const tbody = document.getElementById("teacherTimetableTableBody");
        
        try {
            const { payload } = await postForm("backend/teacher_timetable.php", formData);
            const items = payload && payload.data && Array.isArray(payload.data.items) ? payload.data.items : [];
            if (payload && payload.success) {
                renderTimetable(items);
            }
        } catch (e) {
            if(tbody) tbody.innerHTML = `<tr><td colspan="7">خطأ في جلب الجدول</td></tr>`;
        }
    }

    function getDayName(num) {
        const days = {
            1: "الإثنين", 2: "الثلاثاء", 3: "الأربعاء",
            4: "الخميس", 5: "الجمعة", 6: "السبت", 7: "الأحد"
        };
        return days[num] || String(num);
    }

    function renderTimetable(items) {
        const tbody = document.getElementById("teacherTimetableTableBody");
        if (!tbody) return;
        
        if (items.length === 0) {
            tbody.innerHTML = `<tr><td colspan="7" class="text-center">لا توجد حصص مسجلة حاليا.</td></tr>`;
            return;
        }

        tbody.innerHTML = "";
        items.forEach(item => {
            const tr = document.createElement("tr");
            tr.innerHTML = `
                <td><strong>${getDayName(item.day_of_week)}</strong></td>
                <td style="direction:ltr; text-align:right;">${item.start_time.substring(0,5)} - ${item.end_time.substring(0,5)}</td>
                <td><span class="chip">${item.class_name}</span></td>
                <td>${item.subject_name}</td>
                <td>${item.room || "-"}</td>
                <td>${item.notes || "-"}</td>
                <td>
                    <button class="btn btn-danger btn-small delete-tt-btn" data-id="${item.id}" title="حذف">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </td>
            `;
            tbody.appendChild(tr);
        });

        tbody.querySelectorAll(".delete-tt-btn").forEach(btn => {
            btn.addEventListener("click", async (e) => {
                if (!confirm("هل أنت متأكد من حذف هذه الحصة؟")) return;
                const id = e.currentTarget.getAttribute("data-id");
                const formData = new FormData();
                formData.append("action", "delete");
                formData.append("id", id);
                
                try {
                    const { payload } = await postForm("backend/teacher_timetable.php", formData);
                    if (payload && payload.success) {
                        loadTeacherTimetable();
                    } else {
                        alert((payload && payload.message) ? payload.message : "تعذر حذف الحصة.");
                    }
                } catch(err) {
                    alert(err.message);
                }
            });
        });
    }

    // Avatar Upload
    const avatarUpload = document.getElementById("avatarUpload");
    if (avatarUpload) {
        avatarUpload.addEventListener("change", async (e) => {
            const file = e.target.files[0];
            if (!file) return;
            
            const formData = new FormData();
            formData.append("action", "upload_avatar");
            formData.append("avatar", file);
            
            try {
                const { payload } = await postForm("backend/teacher_profile.php", formData);
                if (payload && payload.success) {
                    // Update avatar display
                    const avatarDisplay = document.getElementById("teacherAvatarDisplay");
                    if (avatarDisplay && payload.data && payload.data.avatar_url) {
                        avatarDisplay.innerHTML = `<img src="${payload.data.avatar_url}" alt="" style="width:100%; height:100%; object-fit:cover;">`;
                    }
                    alert("تم رفع الصورة بنجاح!");
                } else {
                    alert((payload && payload.message) ? payload.message : "فشل رفع الصورة.");
                }
            } catch (error) {
                alert("وقع خطأ أثناء رفع الصورة.");
            }
        });
    }

    // Absence Form
    const absenceForm = document.getElementById("teacherAbsenceForm");
    if (absenceForm) {
        absenceForm.addEventListener("submit", async (e) => {
            e.preventDefault();
            const feedback = document.getElementById("teacherAbsenceFeedback");
            const formData = new FormData(absenceForm);
            formData.append("action", "submit");
            
            try {
                const { payload } = await postForm("backend/teacher_absence.php", formData);
                const ok = payload && payload.success;
                showFeedback(feedback, (payload && payload.message) ? payload.message : "تعذر إرسال الإشعار.", ok ? "success" : "error");
                if (ok) {
                    absenceForm.reset();
                    loadTeacherAbsences();
                }
            } catch (error) {
                showFeedback(feedback, error.message, "error");
            }
        });
    }

    // Load Teacher Absences
    async function loadTeacherAbsences() {
        const tbody = document.getElementById("teacherAbsenceTableBody");
        if (!tbody) return;
        
        const formData = new FormData();
        formData.append("action", "list");
        
        try {
            const { payload } = await postForm("backend/teacher_absence.php", formData);
            const absences = payload && payload.data && payload.data.absences ? payload.data.absences : [];
            
            if (absences.length === 0) {
                tbody.innerHTML = `<tr><td colspan="5" style="text-align:center;">لا توجد غيابات مسجلة</td></tr>`;
                return;
            }
            
            tbody.innerHTML = absences.map(a => `
                <tr>
                    <td>${a.absence_date}</td>
                    <td><span class="chip chip-primary">${a.type}</span></td>
                    <td>${a.reason}</td>
                    <td><span class="chip ${a.status === 'مقبول' ? 'chip-success' : (a.status === 'مرفوض' ? 'chip-danger' : 'chip-warning')}">${a.status}</span></td>
                    <td>${a.admin_notes || '-'}</td>
                </tr>
            `).join('');
        } catch (e) {
            tbody.innerHTML = `<tr><td colspan="5" style="text-align:center;">خطأ في جلب البيانات</td></tr>`;
        }
    }

    // Homework Form
    const homeworkForm = document.getElementById("teacherHomeworkForm");
    if (homeworkForm) {
        homeworkForm.addEventListener("submit", async (e) => {
            e.preventDefault();
            const feedback = document.getElementById("teacherHomeworkFeedback");
            const formData = new FormData(homeworkForm);
            formData.append("action", "create");
            
            try {
                const { payload } = await postForm("backend/teacher_homework.php", formData);
                const ok = payload && payload.success;
                showFeedback(feedback, (payload && payload.message) ? payload.message : "تعذر نشر الواجب.", ok ? "success" : "error");
                if (ok) {
                    homeworkForm.reset();
                    loadTeacherHomework();
                }
            } catch (error) {
                showFeedback(feedback, error.message, "error");
            }
        });
    }

    // Load Teacher Homework
    async function loadTeacherHomework() {
        const container = document.getElementById("homeworkList");
        if (!container) return;
        
        const formData = new FormData();
        formData.append("action", "list");
        
        try {
            const { payload } = await postForm("backend/teacher_homework.php", formData);
            const homework = payload && payload.data && payload.data.homework ? payload.data.homework : [];
            
            if (homework.length === 0) {
                container.innerHTML = `
                    <div style="padding: 2rem; text-align: center; color: #666;">
                        <i class="fa-solid fa-book-open" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                        <p>لم تقم بنشر أي واجب بعد</p>
                    </div>`;
                return;
            }
            
            container.innerHTML = homework.map(hw => `
                <div class="homework-card" style="background: #f8f9fa; border-radius: 12px; padding: 1rem; margin-bottom: 1rem;">
                    <div style="display: flex; justify-content: space-between; align-items: start;">
                        <div>
                            <h4 style="margin: 0; color: #667eea;"><i class="fa-solid fa-book"></i> ${hw.title}</h4>
                            <p style="margin: 0.5rem 0; color: #666;">
                                <i class="fa-solid fa-users"></i> ${hw.class_name} | 
                                <i class="fa-solid fa-book-open"></i> ${hw.subject}
                            </p>
                            <p style="margin: 0.5rem 0; font-size: 0.9rem;">${hw.description}</p>
                        </div>
                        <div style="text-align: center;">
                            <div style="background: #ffc107; color: #333; padding: 0.3rem 0.8rem; border-radius: 15px; font-size: 0.85rem; font-weight: 600;">
                                <i class="fa-solid fa-clock"></i> ${hw.due_date}
                            </div>
                            ${hw.has_file ? `<a href="${hw.file_path}" target="_blank" style="display: block; margin-top: 0.5rem; color: #667eea;"><i class="fa-solid fa-download"></i> الملف</a>` : ''}
                        </div>
                    </div>
                </div>
            `).join('');
        } catch (e) {
            container.innerHTML = `<div style="padding: 2rem; text-align: center; color: #c0392b;">خطأ في جلب الواجبات</div>`;
        }
    }

    // Load Header Data (Name, Subject, Rating)
    async function loadTeacherHeader() {
        const formData = new FormData();
        formData.append("action", "get");
        
        try {
            const { payload } = await postForm("backend/teacher_profile.php", formData);
            const teacher = payload && payload.data && payload.data.teacher ? payload.data.teacher : null;
            if (teacher) {
                // Update header
                const nameEl = document.getElementById("teacherHeaderName");
                const subjectEl = document.getElementById("teacherHeaderSubject");
                const specialtyEl = document.getElementById("teacherHeaderSpecialty");
                const ratingEl = document.getElementById("teacherRating");
                const avatarEl = document.getElementById("teacherAvatarDisplay");
                
                if (nameEl) nameEl.textContent = teacher.full_name || "اسم الأستاذ";
                if (subjectEl) subjectEl.innerHTML = `<i class="fa-solid fa-book"></i> ${teacher.subject_name || 'أستاذ'}`;
                if (specialtyEl) specialtyEl.innerHTML = `<i class="fa-solid fa-location-dot"></i> ${teacher.specialty || 'مؤسسة Kawkab Al Ouloum'}`;
                if (ratingEl) ratingEl.textContent = teacher.rating || '5.0';
                
                // Update avatar if exists
                if (avatarEl && teacher.avatar) {
                    avatarEl.innerHTML = `<img src="${teacher.avatar}" alt="" style="width:100%; height:100%; object-fit:cover;">`;
                }
            }
        } catch (e) {
            console.error("Failed to load header data", e);
        }
    }

    // Contact & WhatsApp handlers
    window.showContactModal = function() {
        alert("معلومات التواصل:\n\nيمكنك التواصل مع الإدارة عبر صفحة 'اتصل بنا'");
    };
    
    window.openWhatsApp = function() {
        alert("فتح واتساب...\n\n(هذه الميزة تتطلب رقم هاتف مسجل)");
    };
    
    window.showSearchModal = function() {
        window.location.href = "teachers.html";
    };

    // Override loadTeacherData to include header
    const originalLoadTeacherData = loadTeacherData;
    loadTeacherData = async function() {
        await originalLoadTeacherData();
        await loadTeacherHeader();
        await loadTeacherAbsences();
        await loadTeacherHomework();
    };
});
