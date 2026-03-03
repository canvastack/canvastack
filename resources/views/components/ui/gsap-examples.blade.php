{{-- GSAP Animation Examples --}}
<div class="space-y-8">
    {{-- Page Transitions --}}
    <section>
        <h2 class="text-2xl font-bold mb-4">Page Transitions</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Fade In --}}
            <div class="fade-in bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-6">
                <div class="w-12 h-12 gradient-bg rounded-xl flex items-center justify-center mb-4">
                    <i data-lucide="zap" class="w-6 h-6 text-white"></i>
                </div>
                <h3 class="text-lg font-semibold mb-2">Fade In</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Smooth fade in animation with upward motion
                </p>
            </div>

            {{-- Fade In Left --}}
            <div class="fade-in-left bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-6">
                <div class="w-12 h-12 gradient-bg rounded-xl flex items-center justify-center mb-4">
                    <i data-lucide="arrow-left" class="w-6 h-6 text-white"></i>
                </div>
                <h3 class="text-lg font-semibold mb-2">Fade In Left</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Slides in from the left side
                </p>
            </div>

            {{-- Fade In Right --}}
            <div class="fade-in-right bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-6">
                <div class="w-12 h-12 gradient-bg rounded-xl flex items-center justify-center mb-4">
                    <i data-lucide="arrow-right" class="w-6 h-6 text-white"></i>
                </div>
                <h3 class="text-lg font-semibold mb-2">Fade In Right</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Slides in from the right side
                </p>
            </div>

            {{-- Scale In --}}
            <div class="scale-in bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-6">
                <div class="w-12 h-12 gradient-bg rounded-xl flex items-center justify-center mb-4">
                    <i data-lucide="maximize" class="w-6 h-6 text-white"></i>
                </div>
                <h3 class="text-lg font-semibold mb-2">Scale In</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Scales up with bounce effect
                </p>
            </div>
        </div>
    </section>

    {{-- Modal Animations --}}
    <section>
        <h2 class="text-2xl font-bold mb-4">Modal Animations</h2>
        <div class="flex gap-4">
            <button 
                @click="$dispatch('open-modal', 'gsap-modal')"
                class="px-4 py-2.5 gradient-bg text-white rounded-xl text-sm font-semibold hover:opacity-90 transition shadow-lg shadow-indigo-500/25"
            >
                Open Animated Modal
            </button>
            
            <button 
                onclick="demoModalShake()"
                class="px-4 py-2.5 bg-red-100 dark:bg-red-900/50 text-red-600 dark:text-red-400 rounded-xl text-sm font-semibold hover:bg-red-200 dark:hover:bg-red-900 transition"
            >
                Demo Shake Animation
            </button>
        </div>

        {{-- Modal with GSAP animations --}}
        <div 
            x-data="{ open: false }"
            @open-modal.window="if ($event.detail === 'gsap-modal') open = true"
            @close-modal.window="if ($event.detail === 'gsap-modal') open = false"
            x-show="open"
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
            style="display: none;"
        >
            {{-- Backdrop --}}
            <div 
                x-show="open"
                class="absolute inset-0 bg-black/50 backdrop-blur-sm"
                @click="open = false"
                id="gsap-modal-backdrop"
            ></div>
            
            {{-- Modal --}}
            <div 
                x-show="open"
                class="relative bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-6 max-w-md w-full"
                id="gsap-modal-content"
                @click.stop
            >
                <div class="flex items-start justify-between mb-4">
                    <h3 class="text-lg font-bold">GSAP Animated Modal</h3>
                    <button 
                        @click="open = false"
                        class="p-1 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition"
                    >
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
                
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">
                    This modal uses GSAP for smooth enter and exit animations with scale and opacity effects.
                </p>
                
                <div class="flex gap-3 justify-end">
                    <button 
                        @click="open = false"
                        class="px-4 py-2 text-sm border border-gray-300 dark:border-gray-700 rounded-xl font-semibold hover:bg-gray-50 dark:hover:bg-gray-800 transition"
                    >
                        Cancel
                    </button>
                    <button 
                        @click="open = false"
                        class="px-4 py-2 text-sm gradient-bg text-white rounded-xl font-semibold hover:opacity-90 transition shadow-lg shadow-indigo-500/25"
                    >
                        Confirm
                    </button>
                </div>
            </div>
        </div>
    </section>

    {{-- Loading States --}}
    <section>
        <h2 class="text-2xl font-bold mb-4">Loading States</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            {{-- Spinner --}}
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-6">
                <h3 class="text-lg font-semibold mb-4">Spinner</h3>
                <div class="flex justify-center">
                    <div class="spinner w-8 h-8 border-4 border-indigo-200 border-t-indigo-600 rounded-full"></div>
                </div>
                <button 
                    onclick="demoSpinner()"
                    class="mt-4 w-full px-4 py-2 text-sm border border-gray-300 dark:border-gray-700 rounded-xl font-semibold hover:bg-gray-50 dark:hover:bg-gray-800 transition"
                >
                    Start Spinner
                </button>
            </div>

            {{-- Pulse --}}
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-6">
                <h3 class="text-lg font-semibold mb-4">Pulse</h3>
                <div class="flex justify-center">
                    <div class="pulse w-16 h-16 gradient-bg rounded-full"></div>
                </div>
                <button 
                    onclick="demoPulse()"
                    class="mt-4 w-full px-4 py-2 text-sm border border-gray-300 dark:border-gray-700 rounded-xl font-semibold hover:bg-gray-50 dark:hover:bg-gray-800 transition"
                >
                    Start Pulse
                </button>
            </div>

            {{-- Dots Loading --}}
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-6">
                <h3 class="text-lg font-semibold mb-4">Dots Loading</h3>
                <div class="flex justify-center gap-2" id="dots-container">
                    <div class="dot w-3 h-3 bg-indigo-600 rounded-full"></div>
                    <div class="dot w-3 h-3 bg-indigo-600 rounded-full"></div>
                    <div class="dot w-3 h-3 bg-indigo-600 rounded-full"></div>
                </div>
                <button 
                    onclick="demoDots()"
                    class="mt-4 w-full px-4 py-2 text-sm border border-gray-300 dark:border-gray-700 rounded-xl font-semibold hover:bg-gray-50 dark:hover:bg-gray-800 transition"
                >
                    Start Dots
                </button>
            </div>

            {{-- Progress Bar --}}
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-6">
                <h3 class="text-lg font-semibold mb-4">Progress Bar</h3>
                <div class="bg-gray-200 dark:bg-gray-800 rounded-full h-2 overflow-hidden">
                    <div id="progress-bar" class="h-full gradient-bg" style="width: 0%"></div>
                </div>
                <button 
                    onclick="demoProgressBar()"
                    class="mt-4 w-full px-4 py-2 text-sm border border-gray-300 dark:border-gray-700 rounded-xl font-semibold hover:bg-gray-50 dark:hover:bg-gray-800 transition"
                >
                    Animate Progress
                </button>
            </div>

            {{-- Skeleton Loading --}}
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-6">
                <h3 class="text-lg font-semibold mb-4">Skeleton</h3>
                <div class="space-y-3">
                    <div class="skeleton h-4 bg-gradient-to-r from-gray-200 via-gray-300 to-gray-200 dark:from-gray-800 dark:via-gray-700 dark:to-gray-800 rounded" style="background-size: 200% 100%;"></div>
                    <div class="skeleton h-4 bg-gradient-to-r from-gray-200 via-gray-300 to-gray-200 dark:from-gray-800 dark:via-gray-700 dark:to-gray-800 rounded w-3/4" style="background-size: 200% 100%;"></div>
                </div>
                <button 
                    onclick="demoSkeleton()"
                    class="mt-4 w-full px-4 py-2 text-sm border border-gray-300 dark:border-gray-700 rounded-xl font-semibold hover:bg-gray-50 dark:hover:bg-gray-800 transition"
                >
                    Start Skeleton
                </button>
            </div>

            {{-- Fade Loading --}}
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-6">
                <h3 class="text-lg font-semibold mb-4">Fade Loading</h3>
                <div id="fade-content" class="text-center py-4">
                    <p class="text-sm text-gray-600 dark:text-gray-400">Original Content</p>
                </div>
                <button 
                    onclick="demoFadeLoading()"
                    class="mt-4 w-full px-4 py-2 text-sm border border-gray-300 dark:border-gray-700 rounded-xl font-semibold hover:bg-gray-50 dark:hover:bg-gray-800 transition"
                >
                    Replace Content
                </button>
            </div>
        </div>
    </section>

    {{-- Utility Animations --}}
    <section>
        <h2 class="text-2xl font-bold mb-4">Utility Animations</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <button 
                onclick="demoBounce(this)"
                class="px-4 py-2.5 gradient-bg text-white rounded-xl text-sm font-semibold hover:opacity-90 transition shadow-lg shadow-indigo-500/25"
            >
                Bounce
            </button>
            
            <button 
                onclick="demoShake(this)"
                class="px-4 py-2.5 bg-red-100 dark:bg-red-900/50 text-red-600 dark:text-red-400 rounded-xl text-sm font-semibold hover:bg-red-200 dark:hover:bg-red-900 transition"
            >
                Shake
            </button>
            
            <button 
                onclick="demoScale(this)"
                class="px-4 py-2.5 bg-emerald-100 dark:bg-emerald-900/50 text-emerald-600 dark:text-emerald-400 rounded-xl text-sm font-semibold hover:bg-emerald-200 dark:hover:bg-emerald-900 transition"
            >
                Scale
            </button>
            
            <button 
                onclick="demoSlideIn(this)"
                class="px-4 py-2.5 bg-blue-100 dark:bg-blue-900/50 text-blue-600 dark:text-blue-400 rounded-xl text-sm font-semibold hover:bg-blue-200 dark:hover:bg-blue-900 transition"
            >
                Slide In
            </button>
        </div>
    </section>

    {{-- Card Hover Animation --}}
    <section>
        <h2 class="text-2xl font-bold mb-4">Card Hover Animation</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @for ($i = 1; $i <= 3; $i++)
            <div 
                class="card-hover bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-6 shadow-sm cursor-pointer"
                onmouseenter="animations.cardHoverIn(this)"
                onmouseleave="animations.cardHoverOut(this)"
            >
                <div class="w-12 h-12 gradient-bg rounded-xl flex items-center justify-center mb-4">
                    <i data-lucide="star" class="w-6 h-6 text-white"></i>
                </div>
                <h3 class="text-lg font-semibold mb-2">Hover Card {{ $i }}</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Hover over this card to see the smooth GSAP animation with shadow effect.
                </p>
            </div>
            @endfor
        </div>
    </section>
</div>

<script>
// Demo functions for loading states
let spinnerAnim, pulseAnim, dotsAnim, skeletonAnim;

function demoSpinner() {
    const spinner = document.querySelector('.spinner');
    if (spinnerAnim) spinnerAnim.kill();
    spinnerAnim = animations.spinnerAnimation(spinner);
}

function demoPulse() {
    const pulse = document.querySelector('.pulse');
    if (pulseAnim) pulseAnim.kill();
    pulseAnim = animations.pulseAnimation(pulse);
}

function demoDots() {
    if (dotsAnim) dotsAnim.kill();
    dotsAnim = animations.dotsLoadingAnimation('#dots-container');
}

function demoProgressBar() {
    const bar = document.getElementById('progress-bar');
    animations.progressBarAnimation(bar, 0);
    setTimeout(() => animations.progressBarAnimation(bar, 100), 100);
}

function demoSkeleton() {
    const skeletons = document.querySelectorAll('.skeleton');
    skeletons.forEach(skeleton => {
        if (skeletonAnim) skeletonAnim.kill();
        skeletonAnim = animations.skeletonAnimation(skeleton);
    });
}

function demoFadeLoading() {
    const content = document.getElementById('fade-content');
    animations.fadeLoading(content, () => {
        content.innerHTML = '<p class="text-sm text-indigo-600 dark:text-indigo-400 font-semibold">New Content Loaded!</p>';
    });
}

// Demo functions for utility animations
function demoBounce(element) {
    animations.bounceAnimation(element);
}

function demoShake(element) {
    animations.shakeAnimation(element);
}

function demoScale(element) {
    animations.scaleAnimation(element, 1.1);
    setTimeout(() => animations.scaleAnimation(element, 1), 300);
}

function demoSlideIn(element) {
    animations.slideIn(element, 'up');
}

function demoModalShake() {
    const modal = document.getElementById('gsap-modal-content');
    if (modal) {
        animations.modalShake(modal);
    }
}

// Initialize modal animations
document.addEventListener('alpine:init', () => {
    window.addEventListener('open-modal', (e) => {
        if (e.detail === 'gsap-modal') {
            setTimeout(() => {
                const modal = document.getElementById('gsap-modal-content');
                const backdrop = document.getElementById('gsap-modal-backdrop');
                if (modal && backdrop) {
                    animations.modalEnter(modal, backdrop);
                }
            }, 50);
        }
    });
});
</script>
