<script setup>
import { computed, onMounted, onBeforeUnmount, ref, watch } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import {
    Bell, ChevronDown, LogOut, Menu, Moon, PanelRightClose, PanelRightOpen,
    Sun, UserCircle, X,
} from 'lucide-vue-next';
import { buildNav } from '@/lib/nav';
import { cn, formatBadge } from '@/lib/utils';
import { ROLE_LABELS } from '@/lib/labels';
import Avatar from '@/Components/ui/Avatar.vue';

/**
 * The authenticated application shell.
 *
 * Layout: a fixed navigation rail on the inline-start edge (screen right, since
 * the app is Arabic) and a sticky top bar over the content column. The rail can
 * be collapsed to an icon strip on desktop and becomes an overlay drawer below
 * the `lg` breakpoint. Both the collapsed state and the theme persist.
 */

const page = usePage();
const auth = computed(() => page.props.auth ?? {});
const user = computed(() => auth.value.user ?? null);
const roles = computed(() => auth.value.roles ?? []);
const isStaff = computed(() => !!auth.value.isStaff);
const isAdmin = computed(() => !!auth.value.isAdmin);
const isCustomer = computed(() => !isStaff.value);
const badges = computed(() => auth.value.badges ?? {});

const sections = computed(() => buildNav({
    isStaff: isStaff.value,
    isAdmin: isAdmin.value,
    isCustomer: isCustomer.value,
    badges: badges.value,
}));

const currentPath = computed(() => page.url.split('?')[0]);
function isActive(href) {
    if (href === '/') return currentPath.value === '/';
    return currentPath.value === href || currentPath.value.startsWith(href + '/');
}

const userName = computed(() => user.value?.name ?? user.value?.full_name ?? '');
const roleLabel = computed(() => {
    const r = roles.value[0];
    return r ? (ROLE_LABELS[r] ?? r) : (isCustomer.value ? 'عميل' : '');
});
const contextLabel = computed(() => (isStaff.value ? 'لوحة الفرق الداخلية' : 'بوابة العميل'));

/* ---------------------------------------------------------------- state --- */

const mobileOpen = ref(false);
const collapsed = ref(false);
const userMenuOpen = ref(false);
const isDark = ref(false);

/* The rail is only mounted when it can actually be seen. Parking it off-canvas
   instead would widen the document by its own width, and in RTL that phantom
   strip sits on the side the browser scrolls to first. */
const DESKTOP = '(min-width: 1024px)';
/* Resolved before first paint so the rail never flashes in on a phone. */
const isDesktop = ref(typeof window === 'undefined' || window.matchMedia(DESKTOP).matches);
let mq = null;
function onBreakpoint(e) {
    isDesktop.value = e.matches;
    if (e.matches) mobileOpen.value = false;
}

onMounted(() => {
    collapsed.value = localStorage.getItem('sidebar:collapsed') === '1';
    isDark.value = document.documentElement.classList.contains('dark');
    mq = window.matchMedia(DESKTOP);
    isDesktop.value = mq.matches;
    mq.addEventListener('change', onBreakpoint);
    document.addEventListener('keydown', onKeydown);
    document.addEventListener('click', onDocumentClick);
});
onBeforeUnmount(() => {
    mq?.removeEventListener('change', onBreakpoint);
    document.removeEventListener('keydown', onKeydown);
    document.removeEventListener('click', onDocumentClick);
});

function onKeydown(e) {
    if (e.key !== 'Escape') return;
    mobileOpen.value = false;
    userMenuOpen.value = false;
}
function onDocumentClick(e) {
    if (userMenuOpen.value && !e.target.closest('[data-user-menu]')) userMenuOpen.value = false;
}

function toggleCollapsed() {
    collapsed.value = !collapsed.value;
    localStorage.setItem('sidebar:collapsed', collapsed.value ? '1' : '0');
}
function toggleTheme() {
    isDark.value = !isDark.value;
    document.documentElement.classList.toggle('dark', isDark.value);
    localStorage.setItem('theme', isDark.value ? 'dark' : 'light');
}
function logout() {
    router.post('/logout');
}

/* Close the mobile drawer whenever navigation happens. */
watch(currentPath, () => { mobileOpen.value = false; });
/* The drawer owns the viewport while open. */
watch(mobileOpen, (open) => {
    document.body.style.overflow = open ? 'hidden' : '';
});
</script>

<template>
    <div dir="rtl" class="min-h-screen bg-background text-foreground">
        <!-- ============================== SIDEBAR ============================== -->
        <Transition
            enter-active-class="transition-transform duration-200 ease-out" enter-from-class="translate-x-full"
            leave-active-class="transition-transform duration-150 ease-in" leave-to-class="translate-x-full">
        <aside v-if="isDesktop || mobileOpen" :class="cn(
            'fixed inset-y-0 right-0 z-50 flex flex-col border-l border-sidebar-border bg-sidebar text-sidebar-foreground',
            'transition-[width] duration-200 ease-out',
            collapsed ? 'w-[4.5rem]' : 'w-64',
            mobileOpen && 'shadow-elevated',
        )">
            <!-- Brand -->
            <div class="flex h-16 shrink-0 items-center gap-3 border-b border-sidebar-border px-4">
                <Link href="/" class="flex min-w-0 items-center gap-3 rounded-md" aria-label="الانتقال إلى الرئيسية">
                    <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-primary text-lg font-bold text-primary-foreground">
                        ط
                    </span>
                    <span v-if="!collapsed" class="min-w-0 leading-tight">
                        <span class="block truncate text-base font-bold">نظام الطلبات</span>
                        <span class="block truncate text-xs text-sidebar-muted">إدارة العملاء</span>
                    </span>
                </Link>

                <button type="button" class="ms-auto rounded-md p-2 text-sidebar-muted hover:bg-sidebar-accent hover:text-sidebar-accent-foreground lg:hidden"
                    aria-label="إغلاق القائمة" @click="mobileOpen = false">
                    <X class="size-5" />
                </button>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 overflow-y-auto overflow-x-hidden px-3 py-4" aria-label="التنقل الرئيسي">
                <div v-for="section in sections" :key="section.label" class="mb-5 last:mb-0">
                    <p v-if="!collapsed" class="px-3 pb-1.5 text-2xs font-bold uppercase tracking-wider text-sidebar-muted">
                        {{ section.label }}
                    </p>
                    <div v-else class="mx-3 mb-2 h-px bg-sidebar-border first:hidden"></div>

                    <ul class="space-y-0.5">
                        <li v-for="item in section.items" :key="item.href">
                            <Link :href="item.href" :title="collapsed ? item.label : undefined"
                                :aria-current="isActive(item.href) ? 'page' : undefined"
                                :class="cn(
                                    'group relative flex items-center gap-3 rounded-md px-3 py-2 text-base font-semibold transition-colors',
                                    collapsed && 'justify-center px-0',
                                    isActive(item.href)
                                        ? 'bg-sidebar-accent text-sidebar-accent-foreground'
                                        : 'text-sidebar-foreground/75 hover:bg-sidebar-accent/60 hover:text-sidebar-accent-foreground',
                                )">
                                <span v-if="isActive(item.href)"
                                    class="absolute inset-y-1 right-0 w-[3px] rounded-s-full bg-sidebar-primary" aria-hidden="true"></span>

                                <component :is="item.icon" class="size-[18px] shrink-0" aria-hidden="true" />

                                <template v-if="!collapsed">
                                    <span class="truncate">{{ item.label }}</span>
                                    <span v-if="formatBadge(item.badge)"
                                        class="ms-auto shrink-0 rounded-full bg-sidebar-primary px-1.5 py-0.5 text-2xs font-bold tabular-nums text-sidebar-primary-foreground">
                                        {{ formatBadge(item.badge) }}
                                    </span>
                                </template>
                                <span v-else-if="item.badge"
                                    class="absolute right-2.5 top-1.5 size-2 rounded-full bg-sidebar-primary" aria-hidden="true"></span>
                            </Link>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Collapse control (desktop) -->
            <div class="hidden shrink-0 border-t border-sidebar-border p-2 lg:block">
                <button type="button" @click="toggleCollapsed"
                    :aria-label="collapsed ? 'توسيع القائمة' : 'طيّ القائمة'"
                    :class="cn(
                        'flex w-full items-center gap-3 rounded-md px-3 py-2 text-sm font-semibold text-sidebar-muted',
                        'transition-colors hover:bg-sidebar-accent hover:text-sidebar-accent-foreground',
                        collapsed && 'justify-center px-0',
                    )">
                    <component :is="collapsed ? PanelRightClose : PanelRightOpen" class="size-[18px] shrink-0" />
                    <span v-if="!collapsed">طيّ القائمة</span>
                </button>
            </div>
        </aside>
        </Transition>

        <!-- Mobile scrim -->
        <Transition enter-active-class="transition-opacity duration-150" enter-from-class="opacity-0"
            leave-active-class="transition-opacity duration-150" leave-to-class="opacity-0">
            <div v-if="mobileOpen" class="fixed inset-0 z-40 bg-foreground/40 lg:hidden" @click="mobileOpen = false" />
        </Transition>

        <!-- =============================== CONTENT ============================= -->
        <div :class="cn('transition-[padding] duration-200 ease-out', collapsed ? 'lg:pr-[4.5rem]' : 'lg:pr-64')">
            <header class="sticky top-0 z-30 flex h-16 items-center gap-2 border-b border-border bg-background/85 px-4 backdrop-blur-md sm:px-6">
                <button type="button" class="-ms-1 rounded-md p-2 hover:bg-muted lg:hidden" aria-label="فتح القائمة"
                    @click.stop="mobileOpen = true">
                    <Menu class="size-5" />
                </button>

                <p class="truncate text-sm font-semibold text-muted-foreground">{{ contextLabel }}</p>

                <div class="ms-auto flex items-center gap-1">
                    <button type="button" class="rounded-md p-2 text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                        :aria-label="isDark ? 'التبديل إلى الوضع الفاتح' : 'التبديل إلى الوضع الداكن'" @click="toggleTheme">
                        <Sun v-if="isDark" class="size-5" />
                        <Moon v-else class="size-5" />
                    </button>

                    <Link href="/notifications" class="relative rounded-md p-2 text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                        aria-label="الإشعارات">
                        <Bell class="size-5" />
                        <span v-if="badges.notifications"
                            class="absolute left-1.5 top-1.5 size-2 rounded-full bg-destructive ring-2 ring-background" aria-hidden="true"></span>
                    </Link>

                    <div class="relative ms-1" data-user-menu>
                        <button type="button" class="flex items-center gap-2 rounded-md p-1 transition-colors hover:bg-muted"
                            :aria-expanded="userMenuOpen" aria-haspopup="menu" @click.stop="userMenuOpen = !userMenuOpen">
                            <Avatar :name="userName" class="size-8" />
                            <span class="hidden min-w-0 text-start leading-tight sm:block">
                                <span class="block max-w-36 truncate text-sm font-semibold">{{ userName }}</span>
                                <span class="block text-xs text-muted-foreground">{{ roleLabel }}</span>
                            </span>
                            <ChevronDown class="size-4 shrink-0 text-muted-foreground" aria-hidden="true" />
                        </button>

                        <Transition enter-active-class="transition duration-100 ease-out"
                            enter-from-class="opacity-0 -translate-y-1" leave-active-class="transition duration-75 ease-in"
                            leave-to-class="opacity-0">
                            <div v-if="userMenuOpen" role="menu"
                                class="absolute left-0 mt-2 w-56 overflow-hidden rounded-lg border border-border bg-popover p-1.5 shadow-elevated">
                                <div class="border-b border-border px-3 pb-2 pt-1.5 sm:hidden">
                                    <p class="truncate text-sm font-semibold">{{ userName }}</p>
                                    <p class="text-xs text-muted-foreground">{{ roleLabel }}</p>
                                </div>
                                <Link href="/account" role="menuitem" class="flex items-center gap-2 rounded-md px-3 py-2 text-base hover:bg-muted"
                                    @click="userMenuOpen = false">
                                    <UserCircle class="size-4" /> حسابي
                                </Link>
                                <button type="button" role="menuitem" @click="logout"
                                    class="flex w-full items-center gap-2 rounded-md px-3 py-2 text-base text-destructive hover:bg-destructive/10">
                                    <LogOut class="size-4" /> تسجيل الخروج
                                </button>
                            </div>
                        </Transition>
                    </div>
                </div>
            </header>

            <main class="mx-auto w-full max-w-[100rem] p-4 sm:p-6">
                <slot />
            </main>
        </div>
    </div>
</template>
