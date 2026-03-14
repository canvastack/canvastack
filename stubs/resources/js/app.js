/**
 * CanvaStack Application Entry Point
 * 
 * This file is published by CanvaStack package and serves as the main
 * JavaScript entry point for your Laravel application.
 * 
 * DO NOT MODIFY the CanvaStack imports unless you know what you're doing.
 * You can add your own custom JavaScript below the CanvaStack setup.
 */

import './bootstrap';
import Alpine from 'alpinejs';
import focus from '@alpinejs/focus';
import collapse from '@alpinejs/collapse';
import { createIcons, icons } from 'lucide';
import ApexCharts from 'apexcharts';
import { gsap } from 'gsap';
import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.min.css';

// Import TanStack Table
import * as TableCore from '@tanstack/table-core';
import * as VirtualCore from '@tanstack/virtual-core';

// ============================================================================
// CANVASTACK COMPONENTS (Required)
// ============================================================================
import { initTableTabs } from '@canvastack/components/table-tabs.js';
import { createFilterModal } from '@canvastack/components/filter/FilterModal.js';

// ============================================================================
// JQUERY & DATATABLES (Legacy Support)
// ============================================================================
import $ from 'jquery';
import 'datatables.net';
import 'datatables.net-buttons';
import 'datatables.net-buttons/js/buttons.html5.mjs';
import 'datatables.net-buttons/js/buttons.print.mjs';
import jszip from 'jszip';
import * as pdfMake from 'pdfmake/build/pdfmake';
import * as pdfFonts from 'pdfmake/build/vfs_fonts';

// Configure pdfMake
if (pdfMake.default) {
    window.pdfMake = pdfMake.default;
} else {
    window.pdfMake = pdfMake;
}

if (pdfFonts.default && pdfFonts.default.pdfMake) {
    window.pdfMake.vfs = pdfFonts.default.pdfMake.vfs;
} else if (pdfFonts.pdfMake) {
    window.pdfMake.vfs = pdfFonts.pdfMake.vfs;
}

window.JSZip = jszip;
window.$ = window.jQuery = $;

// ============================================================================
// GLOBAL LIBRARIES
// ============================================================================
window.TableCore = TableCore;
window.VirtualCore = VirtualCore;
window.ApexCharts = ApexCharts;
window.gsap = gsap;
window.flatpickr = flatpickr;
window.lucide = { createIcons, icons };

// ============================================================================
// CANVASTACK SETUP (Required)
// ============================================================================
window.CanvaStack = window.CanvaStack || {};
window.CanvaStack.createFilterModal = createFilterModal;

// Register CanvaStack Alpine components
document.addEventListener('alpine:init', () => {
    console.log('🎯 Registering CanvaStack Alpine components...');
    
    // Filter Modal Component
    Alpine.data('filterModal', (config) => {
        console.log('🏭 Creating filterModal instance');
        return createFilterModal(config || {});
    });
    
    console.log('✅ CanvaStack components registered');
});

// ============================================================================
// ALPINE.JS SETUP
// ============================================================================
Alpine.plugin(focus);
Alpine.plugin(collapse);
window.Alpine = Alpine;

// Initialize table tabs BEFORE Alpine starts
initTableTabs();

// Start Alpine
Alpine.start();

// ============================================================================
// LUCIDE ICONS
// ============================================================================
document.addEventListener('DOMContentLoaded', () => {
    createIcons({ icons });
    console.log('✅ Lucide icons initialized');
});

// ============================================================================
// DARK MODE
// ============================================================================
window.toggleDarkMode = function() {
    document.documentElement.classList.toggle('dark');
    localStorage.setItem('darkMode', document.documentElement.classList.contains('dark'));
};

if (localStorage.getItem('darkMode') === 'true' || 
    (!localStorage.getItem('darkMode') && 
     window.matchMedia('(prefers-color-scheme: dark)').matches)) {
    document.documentElement.classList.add('dark');
}

// ============================================================================
// GSAP ANIMATIONS
// ============================================================================
document.addEventListener('DOMContentLoaded', () => {
    gsap.from('.fade-in', {
        opacity: 0,
        y: 20,
        duration: 0.6,
        stagger: 0.1
    });
});

// ============================================================================
// YOUR CUSTOM CODE BELOW
// ============================================================================
// Add your application-specific JavaScript here
