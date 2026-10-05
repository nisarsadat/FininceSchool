<template>
    <RouterLink class="person-link" :to="to">
        <span class="inline-flex items-center gap-2.5">
            <span v-if="name" class="avatar" :style="{ '--av-h': hue }">{{ initials }}</span>
            <span>{{ name }}</span>
        </span>
    </RouterLink>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    to: { type: String, required: true },
    name: { type: String, default: '' },
});

const initials = computed(() => {
    if (!props.name) return '';
    return props.name
        .trim()
        .split(/\s+/)
        .slice(0, 2)
        .map((part) => part[0])
        .join('')
        .toUpperCase();
});

const hue = computed(() => {
    let hash = 0;
    for (let i = 0; i < props.name.length; i += 1) {
        hash = (hash * 31 + props.name.charCodeAt(i)) % 360;
    }
    return hash;
});
</script>
