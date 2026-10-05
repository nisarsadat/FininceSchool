<template>
    <router-view />
    <div class="pointer-events-none fixed inset-x-0 bottom-4 z-[80] flex justify-center px-4">
        <Transition name="page">
            <div v-if="store.toast" class="toast" :class="{ 'toast-error': store.toast.type === 'error' }">
                <i class="mdi text-lg" :class="store.toast.type === 'error' ? 'mdi-alert-circle-outline' : 'mdi-check-circle-outline'"></i>
                {{ store.toast.message }}
            </div>
        </Transition>
    </div>
</template>

<script setup>
import { store } from './store';
</script>
