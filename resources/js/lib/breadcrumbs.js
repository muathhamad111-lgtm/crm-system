import { buildNav } from './nav';

const HOME = { label: 'الرئيسية', href: '/' };

/**
 * Derive the breadcrumb trail for a URL from the sidebar navigation, so the
 * two never drift apart. Returns [home, section, item] — the section is a
 * label only (nav groups are not routable).
 *
 * @param {string} url        current page url (query string tolerated)
 * @param {object} ctx        { isStaff, isAdmin, isCustomer }
 * @returns {Array<{label:string, href?:string}>}
 */
export function trailFor(url, ctx) {
    const path = (url ?? '/').split('?')[0];
    if (path === '/') return [HOME];

    const sections = buildNav({ ...ctx, badges: {} });

    let best = null;
    for (const section of sections) {
        for (const item of section.items) {
            if (item.href === '/') continue;
            const matches = path === item.href || path.startsWith(item.href + '/');
            if (!matches) continue;
            if (!best || item.href.length > best.item.href.length) best = { section, item };
        }
    }

    if (!best) return [HOME];

    const trail = [HOME, { label: best.section.label }];
    // Skip a section crumb that just repeats the item (e.g. "المقترحات").
    if (best.section.label === best.item.label) trail.pop();
    trail.push({ label: best.item.label, href: best.item.href });
    return trail;
}
