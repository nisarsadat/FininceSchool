<template>
    <div v-if="visible.length" ref="root" class="inline-flex">
        <button class="icon-btn h-9! w-9!" type="button" :aria-label="t('common.actions')" :aria-expanded="open" @click.stop="toggle">
            <i class="mdi mdi-dots-vertical text-xl"></i>
        </button>
        <Teleport to="body">
            <div v-if="open" ref="menu" class="overflow-hidden rounded border border-[var(--line)] bg-[var(--surface)] py-1 shadow-2xl" :style="menuStyle">
                <component
                    :is="item.to ? RouterLink : 'button'"
                    v-for="(item, index) in visible"
                    :key="index"
                    :to="item.to"
                    class="mx-1 flex w-[calc(100%-8px)] items-center gap-2.5 rounded-sm px-3 py-1.5 text-start text-sm font-semibold transition-colors hover:bg-[var(--surface-2)]"
                    :class="item.danger ? 'text-[var(--rose-ink)]' : 'text-[var(--ink)]'"
                    :type="item.to ? undefined : 'button'"
                    @click="choose(item)"
                >
                    <i class="mdi text-lg" :class="item.icon"></i>
                    <span>{{ item.label }}</span>
                </component>
            </div>
        </Teleport>
    </div>
</template>

<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { RouterLink } from 'vue-router';
import { t } from '../i18n';

const props = defineProps({
    items: { type: Array, default: () => [] },
});

const root = ref(null);
const menu = ref(null);
const open = ref(false);
const menuStyle = ref({});

const visible = computed(() => props.items.filter((item) => item.show !== false));

function place(button) {
    const rect = button.getBoundingClientRect();
    const width = 220;
    const estimated = Math.min(360, visible.value.length * 40 + 12);
    const spaceBelow = window.innerHeight - rect.bottom;
    const top = spaceBelow < estimated && rect.top > estimated ? rect.top - estimated - 6 : rect.bottom + 6;
    const rtl = document.documentElement.dir === 'rtl';
    const preferred = rtl ? rect.left : rect.right - width;
    const left = Math.min(Math.max(8, preferred), window.innerWidth - width - 8);

    menuStyle.value = {
        position: 'fixed',
        top: `${Math.max(8, top)}px`,
        left: `${left}px`,
        width: `${width}px`,
        zIndex: 80,
    };
}

function toggle(event) {
    open.value = !open.value;

    if (open.value) {
        place(event.currentTarget);
    }
}

function close() {
    open.value = false;
}

function choose(item) {
    close();
    item.onClick?.();
}

function onDoc(event) {
    if (root.value?.contains(event.target) || menu.value?.contains(event.target)) {
        return;
    }

    close();
}

function onLayout() {
    if (open.value) {
        close();
    }
}

watch(open, (value) => {
    if (value) {
        document.addEventListener('pointerdown', onDoc);
        window.addEventListener('scroll', onLayout, true);
        window.addEventListener('resize', onLayout);
        return;
    }

    document.removeEventListener('pointerdown', onDoc);
    window.removeEventListener('scroll', onLayout, true);
    window.removeEventListener('resize', onLayout);
});

onBeforeUnmount(() => {
    document.removeEventListener('pointerdown', onDoc);
    window.removeEventListener('scroll', onLayout, true);
    window.removeEventListener('resize', onLayout);
});
</script>
