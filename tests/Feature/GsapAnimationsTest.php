<?php

namespace Canvastack\Canvastack\Tests\Feature;

use Canvastack\Canvastack\Tests\TestCase;

class GsapAnimationsTest extends TestCase
{
    /**
     * Test GSAP examples component renders.
     */
    public function test_gsap_examples_component_renders(): void
    {
        $view = $this->blade('<x-ui.gsap-examples />');

        // Check for page transitions section
        $view->assertSee('Page Transitions');
        $view->assertSee('fade-in');
        $view->assertSee('fade-in-left');
        $view->assertSee('fade-in-right');
        $view->assertSee('scale-in');

        // Check for modal animations section
        $view->assertSee('Modal Animations');
        $view->assertSee('Open Animated Modal');

        // Check for loading states section
        $view->assertSee('Loading States');
        $view->assertSee('Spinner');
        $view->assertSee('Pulse');
        $view->assertSee('Dots Loading');
        $view->assertSee('Progress Bar');

        // Check for utility animations section
        $view->assertSee('Utility Animations');
        $view->assertSee('Bounce');
        $view->assertSee('Shake');
        $view->assertSee('Scale');

        // Check for card hover section
        $view->assertSee('Card Hover Animation');
    }

    /**
     * Test GSAP animation classes are present.
     */
    public function test_gsap_animation_classes_present(): void
    {
        $view = $this->blade('<x-ui.gsap-examples />');

        // Check for animation trigger classes
        $view->assertSee('fade-in');
        $view->assertSee('fade-in-left');
        $view->assertSee('fade-in-right');
        $view->assertSee('scale-in');
        // Note: slide-up is not used in the component, it's defined in JS but not applied to elements

        // Check for loading state classes
        $view->assertSee('spinner');
        $view->assertSee('pulse');
        $view->assertSee('skeleton');

        // Check for card hover class
        $view->assertSee('card-hover');
    }

    /**
     * Test GSAP animation demo functions are defined.
     */
    public function test_gsap_demo_functions_defined(): void
    {
        $view = $this->blade('<x-ui.gsap-examples />');

        // Check for demo function calls
        $view->assertSee('demoSpinner()');
        $view->assertSee('demoPulse()');
        $view->assertSee('demoDots()');
        $view->assertSee('demoProgressBar()');
        $view->assertSee('demoSkeleton()');
        $view->assertSee('demoFadeLoading()');
        $view->assertSee('demoBounce(this)');
        $view->assertSee('demoShake(this)');
        $view->assertSee('demoScale(this)');
        $view->assertSee('demoSlideIn(this)');
    }

    /**
     * Test GSAP modal animations are integrated.
     */
    public function test_gsap_modal_animations_integrated(): void
    {
        $view = $this->blade('<x-ui.gsap-examples />');

        // Check for modal elements
        $view->assertSee('gsap-modal-backdrop');
        $view->assertSee('gsap-modal-content');

        // Check for modal animation initialization (unescaped HTML)
        $view->assertSee('animations.modalEnter', false);
    }

    /**
     * Test GSAP card hover animations are integrated.
     */
    public function test_gsap_card_hover_animations_integrated(): void
    {
        $view = $this->blade('<x-ui.gsap-examples />');

        // Check for card hover event handlers (unescaped HTML)
        $view->assertSee('onmouseenter="animations.cardHoverIn(this)"', false);
        $view->assertSee('onmouseleave="animations.cardHoverOut(this)"', false);
    }

    /**
     * Test GSAP loading states have proper structure.
     */
    public function test_gsap_loading_states_structure(): void
    {
        $view = $this->blade('<x-ui.gsap-examples />');

        // Check for spinner structure (unescaped HTML)
        $view->assertSee('class="spinner', false);

        // Check for pulse structure (unescaped HTML)
        $view->assertSee('class="pulse', false);

        // Check for dots container (unescaped HTML)
        $view->assertSee('id="dots-container"', false);
        $view->assertSee('class="dot', false);

        // Check for progress bar (unescaped HTML)
        $view->assertSee('id="progress-bar"', false);

        // Check for skeleton (unescaped HTML)
        $view->assertSee('class="skeleton', false);

        // Check for fade content (unescaped HTML)
        $view->assertSee('id="fade-content"', false);
    }

    /**
     * Test GSAP animations use Alpine.js integration.
     */
    public function test_gsap_animations_use_alpine_integration(): void
    {
        $view = $this->blade('<x-ui.gsap-examples />');

        // Check for Alpine.js directives
        $view->assertSee('@click');
        $view->assertSee('x-data');
        $view->assertSee('x-show');
        $view->assertSee('@open-modal.window');
        $view->assertSee('@close-modal.window');
    }

    /**
     * Test GSAP animations have proper accessibility.
     */
    public function test_gsap_animations_accessibility(): void
    {
        $view = $this->blade('<x-ui.gsap-examples />');

        // Check for semantic HTML (unescaped HTML)
        $view->assertSee('<section>', false);
        $view->assertSee('<button', false);

        // Check for proper heading structure (unescaped HTML)
        $view->assertSee('<h2', false);
        $view->assertSee('<h3', false);
    }

    /**
     * Test GSAP animations support dark mode.
     */
    public function test_gsap_animations_support_dark_mode(): void
    {
        $view = $this->blade('<x-ui.gsap-examples />');

        // Check for dark mode classes
        $view->assertSee('dark:bg-gray-900');
        $view->assertSee('dark:border-gray-800');
        $view->assertSee('dark:text-gray-400');
    }
}
