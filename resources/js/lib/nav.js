import {
    Home, Inbox, PlusCircle, CalendarDays, Lightbulb, LayoutList, ListChecks,
    GaugeCircle, Users, Store, Mailbox, CalendarClock, BarChart3, Plane,
    BookOpen, Settings, Star,
} from 'lucide-vue-next';

/**
 * Build the sidebar sections for the current user.
 * @param {{isStaff:boolean, isAdmin:boolean, isCustomer:boolean, badges?:object}} ctx
 */
export function buildNav({ isStaff, isAdmin, isCustomer, badges = {} }) {
    const sections = [];

    sections.push({
        label: 'عام',
        items: [{ label: 'الرئيسية', href: '/', icon: Home }],
    });

    if (isCustomer) {
        sections.push({
            label: 'طلباتي',
            items: [
                { label: 'طلباتي', href: '/requests', icon: Inbox, badge: badges.requests },
                { label: 'طلب جديد', href: '/requests/new', icon: PlusCircle },
                { label: 'مواعيدي', href: '/appointments', icon: CalendarDays, badge: badges.appointments },
            ],
        });
    }

    const suggestions = { label: 'المقترحات', items: [] };
    if (isCustomer) {
        suggestions.items.push({ label: 'مقترحاتي', href: '/suggestions/mine', icon: Lightbulb });
        suggestions.items.push({ label: 'مقترح جديد', href: '/suggestions/new', icon: PlusCircle });
    }
    suggestions.items.push({ label: 'لوحة المقترحات', href: '/suggestions', icon: LayoutList, badge: badges.suggestionsBoard });
    sections.push(suggestions);

    if (isStaff) {
        sections.push({
            label: 'العمليات',
            items: [
                { label: 'صندوق الطلبات', href: '/requests', icon: Inbox, badge: badges.requests },
                { label: 'صندوق المقترحات', href: '/suggestions/inbox', icon: ListChecks, badge: badges.suggestionsInbox },
                { label: 'إدارة SLA', href: '/sla-compliance', icon: GaugeCircle },
                { label: 'قائمة العملاء', href: '/customers', icon: Users },
                { label: 'عملاء المتجر', href: '/tts-customers', icon: Store },
                { label: 'رسائل المتجر العامة', href: '/store-contact-inbox', icon: Mailbox },
                { label: 'مواعيد العملاء', href: '/admin/appointments', icon: CalendarClock },
                { label: 'التقييمات', href: '/ratings', icon: Star },
                { label: 'التقارير', href: '/reports', icon: BarChart3 },
                { label: 'إجازاتي', href: '/leaves', icon: Plane, badge: badges.leaves },
                { label: 'قاعدة المعرفة', href: '/knowledge-base', icon: BookOpen },
            ],
        });
    }

    if (isAdmin) {
        sections.push({
            label: 'الإدارة',
            items: [{ label: 'إدارة النظام', href: '/admin', icon: Settings }],
        });
    }

    return sections;
}
