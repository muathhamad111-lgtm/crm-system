// Arabic labels + tone mapping for enums, mirroring the original TTS system.

export const REQUEST_STATUS = {
    new: { label: 'جديد', tone: 'info' },
    under_review: { label: 'قيد المراجعة', tone: 'info' },
    awaiting_customer: { label: 'بانتظار العميل', tone: 'warning' },
    in_progress: { label: 'قيد المعالجة', tone: 'accent' },
    awaiting_internal: { label: 'بانتظار داخلي', tone: 'warning' },
    escalated: { label: 'تم التصعيد', tone: 'destructive' },
    completed: { label: 'مكتمل', tone: 'success' },
    closed: { label: 'مغلق', tone: 'muted' },
    rejected: { label: 'مرفوض', tone: 'destructive' },
    reopened: { label: 'أعيد فتحه', tone: 'warning' },
    cancelled: { label: 'ملغى', tone: 'muted' },
};

export const REQUEST_PRIORITY = {
    urgent: { label: 'عاجل', tone: 'destructive' },
    high: { label: 'مرتفع', tone: 'warning' },
    medium: { label: 'متوسط', tone: 'info' },
    low: { label: 'منخفض', tone: 'muted' },
};

// Soft "service status" shown to customers (SLA hidden).
export const SERVICE_STATUS = {
    on_track: { label: 'ضمن الوقت', tone: 'success' },
    due_soon: { label: 'يوشك على التجاوز', tone: 'warning' },
    overdue: { label: 'متأخر', tone: 'destructive' },
    awaiting_customer: { label: 'بانتظارك', tone: 'warning' },
    done: { label: 'مكتمل', tone: 'success' },
};

export const ROLE_LABELS = {
    customer: 'عميل',
    support_staff: 'موظف دعم',
    support_supervisor: 'مشرف دعم',
    product_team: 'مختص المنتج',
    product_manager: 'مدير المنتج',
    tech_team: 'مختص التقنية',
    tech_manager: 'مدير التقنية',
    'management team': 'أعضاء الإدارة',
    system_admin: 'مدير النظام',
};

export const APPOINTMENT_STATUS = {
    pending_confirmation: { label: 'بانتظار التأكيد', tone: 'warning' },
    confirmed: { label: 'مؤكد', tone: 'success' },
    completed: { label: 'منتهٍ', tone: 'muted' },
    cancelled: { label: 'ملغى', tone: 'muted' },
    no_show: { label: 'لم يحضر', tone: 'destructive' },
    rescheduled: { label: 'أعيدت جدولته', tone: 'info' },
};

export const IDEA_STAGE = {
    received: { label: 'مُستلم', tone: 'info' },
    under_review: { label: 'قيد الدراسة', tone: 'info' },
    under_study: { label: 'قيد الدراسة', tone: 'info' },
    shortlisted: { label: 'مرشّح', tone: 'accent' },
    accepted: { label: 'مقبول', tone: 'success' },
    approved: { label: 'معتمد', tone: 'success' },
    on_roadmap: { label: 'على خارطة الطريق', tone: 'accent' },
    scheduled: { label: 'مجدول', tone: 'info' },
    in_progress: { label: 'قيد التنفيذ', tone: 'accent' },
    implemented: { label: 'مُنفّذ', tone: 'success' },
    published: { label: 'منشور', tone: 'success' },
    voting: { label: 'قيد التصويت', tone: 'warning' },
    postponed: { label: 'مؤجل', tone: 'muted' },
    rejected: { label: 'مرفوض', tone: 'destructive' },
    archived: { label: 'مؤرشف', tone: 'muted' },
};

export function statusLabel(map, key) {
    return map[key] ?? { label: key ?? '—', tone: 'muted' };
}
