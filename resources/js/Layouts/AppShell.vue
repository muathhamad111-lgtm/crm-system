<script setup>
import { computed, ref } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import { buildNav } from '@/lib/nav';
import { cn, initials, formatBadge } from '@/lib/utils';
import Avatar from '@/Components/ui/Avatar.vue';
import { ROLE_LABELS } from '@/lib/labels';
import { Bell, LogOut, UserCircle, Menu, Moon, Sun, ChevronDown } from 'lucide-vue-next';

const page = usePage();
const auth = computed(() => page.props.auth ?? {});
const user = computed(() => auth.value.user ?? null);
const roles = computed(() => auth.value.roles ?? []);
const isStaff = computed(() => !!auth.value.isStaff);
const isAdmin = computed(() => !!auth.value.isAdmin);
const isCustomer = computed(() => !isStaff.value);
const badges = computed(() => auth.value.badges ?? {});

const sections = computed(() => buildNav({
    isStaff: isStaff.value, isAdmin: isAdmin.value, isCustomer: isCustomer.value, badges: badges.value,
}));

const currentPath = computed(() => page.url.split('?')[0]);
function isActive(href) {
    if (href === '/') return currentPath.value === '/';
    return currentPath.value === href || currentPath.value.startsWith(href + '/');
}

const roleLabel = computed(() => {
    const r = roles.value[0];
    return r ? (ROLE_LABELS[r] ?? r) : (isCustomer.value ? 'عميل' : '');
});
const contextLabel = computed(() => (isStaff.value ? 'لوحة الفرق الداخلية' : 'بوابة العميل'));

const mobileOpen = ref(false);
const userMenuOpen = ref(false);
const isDark = ref(typeof document !== 'undefined' && document.documentElement.classList.contains('dark'));

function toggleTheme() {
    isDark.value = !isDark.value;
    document.documentElement.classList.toggle('dark', isDark.value);
    localStorage.setItem('theme', isDark.value ? 'dark' : 'light');
}
function logout() {
    router.post('/logout');
}
</script>

<template>
    <div dir="rtl" class="min-h-screen bg-background text-foreground">
        <!-- Sidebar -->
        <aside :class="cn(
            'fixed inset-y-0 right-0 z-40 w-64 flex-col border-l border-sidebar-border bg-sidebar text-sidebar-foreground transition-transform lg:flex',
            mobileOpen ? 'flex translate-x-0' : 'hidden -translate-x-0 lg:translate-x-0',
        )">
            <div class="flex h-16 items-center gap-3 border-b border-sidebar-border px-5">
                <div class="flex size-9 items-center justify-center rounded-lg bg-sidebar-primary text-sidebar-primary-foreground font-bold">ط</div>
                <div class="leading-tight">
                    <p class="font-bold text-sm">نظام الطلبات</p>
                    <p class="text-xs text-sidebar-foreground/60">إدارة العملاء</p>
                </div>
            </div>

            <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-6">
                <div v-for="section in sections" :key="section.label">
                    <p class="px-3 pb-2 text-[11px] font-bold uppercase tracking-wider text-sidebar-foreground/40">{{ section.label }}</p>
                    <ul class="space-y-0.5">
                        <li v-for="item in section.items" :key="item.href">
                            <Link :href="item.href" @click="mobileOpen = false" :class="cn(
                                'group relative flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-all hover:-translate-x-0.5',
                                isActive(item.href)
                                    ? 'bg-sidebar-accent text-sidebar-accent-foreground'
                                    : 'text-sidebar-foreground/80 hover:bg-sidebar-accent/50 hover:text-sidebar-foreground',
                            )">
                                <span v-if="isActive(item.href)" class="absolute right-0 top-1/2 h-5 w-1 -translate-y-1/2 rounded-l bg-sidebar-primary"></span>
                                <component :is="item.icon" class="size-[18px] shrink-0" />
                                <span class="truncate">{{ item.label }}</span>
                                <span v-if="formatBadge(item.badge)" class="mr-auto rounded-full bg-sidebar-primary px-1.5 py-0.5 text-[10px] font-bold text-sidebar-primary-foreground">
                                    {{ formatBadge(item.badge) }}
                                </span>
                            </Link>
                        </li>
                    </ul>
                </div>
            </nav>
        </aside>

        <!-- Overlay (mobile) -->
        <div v-if="mobileOpen" class="fixed inset-0 z-30 bg-black/40 lg:hidden" @click="mobileOpen = false"></div>

        <!-- Main -->
        <div class="lg:pr-64">
            <header class="sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-border bg-background/80 px-4 backdrop-blur sm:px-6">
                <button class="lg:hidden rounded-md p-2 hover:bg-muted" @click="mobileOpen = true">
                    <Menu class="size-5" />
                </button>
                <p class="text-sm font-medium text-muted-foreground">{{ contextLabel }}</p>

                <div class="mr-auto flex items-center gap-1.5">
                    <button class="rounded-md p-2 hover:bg-muted transition-colors" @click="toggleTheme" title="السمة">
                        <Moon v-if="!isDark" class="size-5" />
                        <Sun v-else class="size-5" />
                    </button>
                    <Link href="/notifications" class="relative rounded-md p-2 hover:bg-muted transition-colors" title="الإشعارات">
                        <Bell class="size-5" />
                        <span v-if="badges.notifications" class="absolute top-1 left-1 size-2 rounded-full bg-accent"></span>
                    </Link>

                    <div class="relative">
                        <button class="flex items-center gap-2 rounded-lg py-1 pl-2 pr-1 hover:bg-muted transition-colors" @click="userMenuOpen = !userMenuOpen">
                            <Avatar :name="user?.name ?? user?.full_name" class="size-8" />
                            <div class="hidden text-right sm:block leading-tight">
                                <p class="text-sm font-medium">{{ user?.name ?? user?.full_name }}</p>
                                <p class="text-xs text-muted-foreground">{{ roleLabel }}</p>
                            </div>
                            <ChevronDown class="size-4 text-muted-foreground" />
                        </button>
                        <div v-if="userMenuOpen" class="absolute left-0 mt-2 w-52 rounded-lg border border-border bg-popover p-1.5 shadow-elevated"
                            @click="userMenuOpen = false">
                            <Link href="/account" class="flex items-center gap-2 rounded-md px-3 py-2 text-sm hover:bg-muted">
                                <UserCircle class="size-4" /> حسابي
                            </Link>
                            <button @click="logout" class="flex w-full items-center gap-2 rounded-md px-3 py-2 text-sm text-destructive hover:bg-muted">
                                <LogOut class="size-4" /> تسجيل الخروج
                            </button>
                        </div>
                    </div>
                </div>
            </header>

            <main class="p-4 sm:p-6">
                <slot />
            </main>
        </div>
    </div>
</template>
