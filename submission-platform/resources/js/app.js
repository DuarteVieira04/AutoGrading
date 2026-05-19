import './bootstrap';

import Alpine from 'alpinejs';
import { initSubmissionsPoll } from './submissions-poll';

window.Alpine = Alpine;

Alpine.start();

document.addEventListener('DOMContentLoaded', () => {
    initSubmissionsPoll();
});
