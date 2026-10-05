<template>
    <Modal :open="open" :title="title" @close="$emit('close')">
        <p class="text-sm leading-6 text-slate-600">{{ message }}</p>
        <template #footer>
            <button class="btn btn-ghost" type="button" @click="$emit('close')">{{ t('common.cancel') }}</button>
            <button class="btn btn-danger" type="button" :disabled="busy" @click="$emit('confirm')">
                {{ busy ? t('common.working') : (confirmLabel || t('common.delete')) }}
            </button>
        </template>
    </Modal>
</template>

<script setup>
import Modal from './Modal.vue';
import { t } from '../i18n';

defineProps({
    open: Boolean,
    title: { type: String, default: 'Please confirm' },
    message: { type: String, default: '' },
    confirmLabel: { type: String, default: 'Delete' },
    busy: Boolean,
});

defineEmits(['close', 'confirm']);
</script>
