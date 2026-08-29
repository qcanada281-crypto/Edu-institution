/**
 * WhatsApp & Notifications Admin Script
 */
document.addEventListener('DOMContentLoaded', () => {
    const whatsappAbsenceBtn = document.getElementById('sendWhatsappAbsenceBtn');
    const whatsappGradesBtn = document.getElementById('sendWhatsappGradesBtn');

    function showFeedback(elementId, message, isError = false) {
        const feedback = document.getElementById(elementId);
        if (feedback) {
            feedback.style.display = 'block';
            feedback.className = `feedback ${isError ? 'error' : 'success'}`;
            feedback.textContent = message;
        }
    }

    if (whatsappAbsenceBtn) {
        whatsappAbsenceBtn.addEventListener('click', async () => {
            const studentCodeInput = document.getElementById('attendance_student_code');
            const studentCode = studentCodeInput ? studentCodeInput.value.trim() : '';
            const date = document.getElementById('absence_date')?.value || new Date().toISOString().split('T')[0];
            
            const subjectSelect = document.getElementById('absence_subject_select');
            const otherSubjectInput = document.getElementById('absence_subject_other_attendance');
            let subject = 'المادة المعنية';
            if (subjectSelect) {
                subject = (subjectSelect.value === 'أخرى' && otherSubjectInput && otherSubjectInput.value.trim())
                    ? otherSubjectInput.value.trim()
                    : (subjectSelect.value || 'المادة المعنية');
            }

            if (!studentCode) {
                showFeedback('attendanceAdminFeedback', 'المرجو إدخال أو اختيار كود التلميذ أولاً (مثال: STU2026004) لإرسال إشعار WhatsApp.', true);
                if (studentCodeInput) studentCodeInput.focus();
                return;
            }

            if (!subjectSelect || (!subjectSelect.value && (!otherSubjectInput || !otherSubjectInput.value.trim()))) {
                showFeedback('attendanceAdminFeedback', 'المرجو اختيار المادة المعنية بالغياب أولاً من القائمة.', true);
                if (subjectSelect) subjectSelect.focus();
                return;
            }

            try {
                showFeedback('attendanceAdminFeedback', 'جاري تحضير إشعار WhatsApp...');

                const formData = new FormData();
                formData.append('action', 'get_whatsapp_link');
                formData.append('massar', studentCode);
                formData.append('type', 'absence');
                formData.append('date', date);
                formData.append('subject', subject);

                const res = await fetch('backend/notifications_api.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();

                if (data.success && data.whatsapp_url) {
                    window.open(data.whatsapp_url, '_blank');
                    showFeedback('attendanceAdminFeedback', `تم تسجيل الإشعار وتوجيهه إلى WhatsApp (${data.student_name})`);
                } else {
                    showFeedback('attendanceAdminFeedback', data.error || 'تعذر استخراج رابط WhatsApp', true);
                }
            } catch (err) {
                showFeedback('attendanceAdminFeedback', 'فشل الاتصال بخادم الإشعارات', true);
            }
        });
    }

    if (whatsappGradesBtn) {
        whatsappGradesBtn.addEventListener('click', async () => {
            const studentCodeInput = document.getElementById('grade_student_code');
            const studentCode = studentCodeInput ? studentCodeInput.value.trim() : '';

            if (!studentCode) {
                showFeedback('gradeFeedback', 'المرجو إدخال أو اختيار كود التلميذ أولاً (مثال: STU2026004) لإرسال إشعار النقط.', true);
                if (studentCodeInput) studentCodeInput.focus();
                return;
            }

            try {
                showFeedback('gradeFeedback', 'جاري تحضير إشعار النقط...');

                const formData = new FormData();
                formData.append('action', 'get_whatsapp_link');
                formData.append('massar', studentCode);
                formData.append('type', 'grades');

                const res = await fetch('backend/notifications_api.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();

                if (data.success && data.whatsapp_url) {
                    window.open(data.whatsapp_url, '_blank');
                    showFeedback('gradeFeedback', `تم إرسال إشعار النقط عبر WhatsApp بنجاح (${data.student_name})`);
                } else {
                    showFeedback('gradeFeedback', data.error || 'تعذر استخراج رابط WhatsApp', true);
                }
            } catch (err) {
                showFeedback('gradeFeedback', 'فشل الاتصال بخادم الإشعارات', true);
            }
        });
    }
});
