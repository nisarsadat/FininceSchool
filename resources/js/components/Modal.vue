<template>
    <Teleport to="body">
        <Transition name="modal">
            <div v-if="open" class="fixed inset-0 z-50 flex items-end justify-center p-3 sm:items-center sm:p-6">
                <button class="absolute inset-0 bg-[#131310]/55" type="button" aria-label="Close" @click="$emit('close')"></button>
                <div class="modal-card relative z-10 flex max-h-[92vh] w-full flex-col overflow-hidden rounded border border-[var(--line)] bg-[var(--surface)] shadow-2xl" :class="wide ? 'max-w-3xl' : 'max-w-lg'">
                    <div class="flex items-start justify-between gap-4 border-b border-[var(--line)] px-5 py-3.5">
                        <div>
                            <h2 class="text-base font-bold tracking-tight">{{ title }}</h2>
                            <p v-if="subtitle" class="mt-1 text-[13px] text-[var(--muted)]">{{ subtitle }}</p>
                        </div>
                        <button class="icon-btn h-9! w-9!" type="button" @click="$emit('close')"><i class="mdi mdi-close text-xl"></i></button>
                    </div>
                    <div class="overflow-y-auto px-5 py-4">
                        <slot />
                    </div>
                    <div v-if="$slots.footer" class="actions-end border-t border-[var(--line)] bg-[var(--surface-2)] px-5 py-3">
                        <slot name="footer" />
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
defineProps({
    open: Boolean,
    title: { type: String, required: true },
    subtitle: { type: String, default: '' },
    wide: Boolean,
});

defineEmits(['close']);
</script>
