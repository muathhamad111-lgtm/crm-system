import { ref, computed } from 'vue';

/**
 * Client-side sorting for an array of row objects.
 * Usage: const { sorted, sortKey, sortDir, toggle } = useClientSort(() => props.rows, 'created_at', 'desc');
 * Accessor can be a string key or a function(row).
 */
export function useClientSort(rowsGetter, initialKey = null, initialDir = 'asc', accessors = {}) {
    const sortKey = ref(initialKey);
    const sortDir = ref(initialDir);

    function toggle(key) {
        if (sortKey.value === key) sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
        else { sortKey.value = key; sortDir.value = 'asc'; }
    }

    function valueOf(row, key) {
        const acc = accessors[key];
        let v = typeof acc === 'function' ? acc(row) : (acc ? row[acc] : row[key]);
        if (v === null || v === undefined) return '';
        if (typeof v === 'string') {
            const d = Date.parse(v);
            if (!Number.isNaN(d) && /\d{4}-\d{2}-\d{2}/.test(v)) return d;
            return v;
        }
        return v;
    }

    const sorted = computed(() => {
        const rows = (typeof rowsGetter === 'function' ? rowsGetter() : rowsGetter) ?? [];
        if (!sortKey.value) return rows;
        const arr = [...rows];
        const sign = sortDir.value === 'asc' ? 1 : -1;
        arr.sort((a, b) => {
            const av = valueOf(a, sortKey.value);
            const bv = valueOf(b, sortKey.value);
            if (typeof av === 'number' && typeof bv === 'number') return (av - bv) * sign;
            return String(av).localeCompare(String(bv), 'ar') * sign;
        });
        return arr;
    });

    return { sorted, sortKey, sortDir, toggle };
}
