import { createApp } from 'vue';
import { createPinia } from 'pinia';
import Teambuilder from './components/Teambuilder.vue';

const app = createApp(Teambuilder);
const pinia = createPinia();

app.use(pinia);
app.mount('#app');