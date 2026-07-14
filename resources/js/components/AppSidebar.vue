<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { chat } from '@/routes';

const page = usePage();

const dashboardUrl = computed(() => chat().url);

// Clear the saved conversation before starting a fresh chat so the new
// message doesn't accidentally reuse the previous conversation_id.
function startNewChat() {
    localStorage.removeItem('conversation_id');
    window.dispatchEvent(new CustomEvent('chat:new'));
}

// Claude-style recent chat list (uses real prop or clean sample items so UI always looks sleek)
const chatList = computed(() => {
    const raw = (page.props as any).chatHistory ?? [];

    if (raw.length > 0) {
        return raw;
    }

    return [
        { id: 1, title: 'Explain Quantum Computing to a 5-year old', href: '/#chat-1' },
        { id: 2, title: 'Refactor controller using Prism package', href: '/#chat-2' },
        { id: 3, title: 'Fix Vue 3 Vite resolution error', href: '/#chat-3' },
        { id: 4, title: 'Brainstorm SaaS product ideas', href: '/#chat-4' },
    ];
});
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboardUrl">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <!-- Claude AI Style Sidebar Content -->
        <SidebarContent class="overflow-x-hidden px-2 py-2">
            <!-- Start New Chat Button (Sleek Claude AI Style, collapses cleanly into just a + icon) -->
            <SidebarMenu class="mb-3">
                <SidebarMenuItem>
                    <SidebarMenuButton
                        as-child
                        tooltip="Start new chat"
                        variant="outline"
                        class="h-9.5 rounded-lg border border-sidebar-border/60 bg-sidebar-accent/20 px-3 font-medium text-sidebar-foreground shadow-2xs transition-all hover:border-sidebar-border hover:bg-sidebar-accent"
                    >
                        <Link :href="dashboardUrl" @click="startNewChat">
                            <Plus class="size-4 shrink-0" />
                            <span>Start new chat</span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>

            <!-- Recents Section Header & List (Disappears automatically when sidebar collapses) -->
            <SidebarGroup class="p-0 group-data-[collapsible=icon]:hidden">
                <div class="mb-1.5 px-2.5 text-[11px] font-medium tracking-wider text-sidebar-foreground/40 uppercase">
                    Recents
                </div>
                <SidebarMenu class="gap-0.5">
                    <!-- Chat items (Clean text items without cluttered icons like Claude AI) -->
                    <SidebarMenuItem v-for="chat in chatList" :key="chat.id">
                        <SidebarMenuButton
                            as-child
                            :tooltip="chat.title"
                            class="h-8.5 rounded-md px-2.5 text-[13px] font-normal text-sidebar-foreground/80 transition-colors hover:bg-sidebar-accent/70 hover:text-sidebar-foreground"
                        >
                            <Link :href="chat.href">
                                <span class="truncate">{{ chat.title }}</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarGroup>
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
