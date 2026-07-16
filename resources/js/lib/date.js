// Arabic date/time helpers, Asia/Riyadh timezone.
const TZ = 'Asia/Riyadh';

const AR_MONTHS = [
    'يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو',
    'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر',
];

function toDate(v) {
    if (!v) return null;
    const d = v instanceof Date ? v : new Date(v);
    return isNaN(d.getTime()) ? null : d;
}

export function fmtDateAr(v) {
    const d = toDate(v);
    if (!d) return '—';
    return new Intl.DateTimeFormat('ar-SA-u-ca-gregory', {
        timeZone: TZ, day: 'numeric', month: 'long', year: 'numeric',
    }).format(d).replace(/[٠-٩]/g, (x) => '٠١٢٣٤٥٦٧٨٩'.indexOf(x));
}

export function fmtFullDateTimeAr(v) {
    const d = toDate(v);
    if (!d) return '—';
    return new Intl.DateTimeFormat('ar-SA-u-ca-gregory', {
        timeZone: TZ, day: 'numeric', month: 'long', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
    }).format(d);
}

export function fmtTimeAr(v) {
    const d = toDate(v);
    if (!d) return '—';
    return new Intl.DateTimeFormat('ar-SA-u-ca-gregory', {
        timeZone: TZ, hour: '2-digit', minute: '2-digit',
    }).format(d);
}

/** Arabic relative time ("منذ 3 ساعات"). */
export function timeAgoAr(v) {
    const d = toDate(v);
    if (!d) return '—';
    const diff = (Date.now() - d.getTime()) / 1000; // seconds
    const abs = Math.abs(diff);
    const fut = diff < 0;
    const pick = (n, s, d2, plural, pluralMany) => {
        n = Math.floor(n);
        if (n === 1) return s;
        if (n === 2) return d2;
        if (n >= 3 && n <= 10) return `${n} ${plural}`;
        return `${n} ${pluralMany}`;
    };
    let body;
    if (abs < 60) body = 'الآن';
    else if (abs < 3600) body = pick(abs / 60, 'دقيقة', 'دقيقتين', 'دقائق', 'دقيقة');
    else if (abs < 86400) body = pick(abs / 3600, 'ساعة', 'ساعتين', 'ساعات', 'ساعة');
    else if (abs < 2592000) body = pick(abs / 86400, 'يوم', 'يومين', 'أيام', 'يوماً');
    else if (abs < 31536000) body = pick(abs / 2592000, 'شهر', 'شهرين', 'أشهر', 'شهراً');
    else body = pick(abs / 31536000, 'سنة', 'سنتين', 'سنوات', 'سنة');
    if (body === 'الآن') return body;
    return fut ? `بعد ${body}` : `منذ ${body}`;
}

export { AR_MONTHS };
