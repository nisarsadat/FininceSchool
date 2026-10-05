import '@mdi/font/css/materialdesignicons.min.css';
import { createApp } from 'vue';
import App from './App.vue';
import router from './router';
import { applyDocument } from './store';

applyDocument();
createApp(App).use(router).mount('#app');
