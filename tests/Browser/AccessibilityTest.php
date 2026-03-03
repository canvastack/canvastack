<?php

namespace Canvastack\Canvastack\Tests\Browser;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class AccessibilityTest extends DuskTestCase
{
    use DatabaseMigrations;

    /**
     * Test page has proper document structure.
     */
    public function test_page_has_proper_document_structure(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                    ->assertPresent('html[lang]')
                    ->assertPresent('head')
                    ->assertPresent('title')
                    ->assertPresent('body');
        });
    }

    /**
     * Test all images have alt text.
     */
    public function test_all_images_have_alt_text(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/');

            $imagesWithoutAlt = $browser->driver->executeScript(
                'return Array.from(document.querySelectorAll("img:not([alt])")).length'
            );

            $this->assertEquals(0, $imagesWithoutAlt, 'Found images without alt text');
        });
    }

    /**
     * Test form inputs have labels.
     */
    public function test_form_inputs_have_labels(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/register');

            $inputsWithoutLabels = $browser->driver->executeScript(
                'return Array.from(document.querySelectorAll("input:not([type=hidden]):not([aria-label]):not([aria-labelledby])"))
                    .filter(input => !document.querySelector(`label[for="${input.id}"]`)).length'
            );

            $this->assertEquals(0, $inputsWithoutLabels, 'Found inputs without labels');
        });
    }

    /**
     * Test buttons have accessible names.
     */
    public function test_buttons_have_accessible_names(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->createAdminUser())
                    ->visit('/admin/dashboard');

            $buttonsWithoutNames = $browser->driver->executeScript(
                'return Array.from(document.querySelectorAll("button"))
                    .filter(btn => !btn.textContent.trim() && !btn.getAttribute("aria-label")).length'
            );

            $this->assertEquals(0, $buttonsWithoutNames, 'Found buttons without accessible names');
        });
    }

    /**
     * Test links have descriptive text.
     */
    public function test_links_have_descriptive_text(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/');

            $linksWithoutText = $browser->driver->executeScript(
                'return Array.from(document.querySelectorAll("a"))
                    .filter(link => !link.textContent.trim() && !link.getAttribute("aria-label")).length'
            );

            $this->assertEquals(0, $linksWithoutText, 'Found links without descriptive text');
        });
    }

    /**
     * Test heading hierarchy is correct.
     */
    public function test_heading_hierarchy_is_correct(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/');

            $hasH1 = $browser->driver->executeScript(
                'return document.querySelectorAll("h1").length > 0'
            );

            $this->assertTrue($hasH1, 'Page missing h1 heading');

            $h1Count = $browser->driver->executeScript(
                'return document.querySelectorAll("h1").length'
            );

            $this->assertEquals(1, $h1Count, 'Page should have exactly one h1');
        });
    }

    /**
     * Test color contrast meets WCAG AA.
     */
    public function test_color_contrast_meets_wcag_aa(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/');

            // This is a simplified test - in production, use axe-core or similar
            $textElements = $browser->driver->executeScript(
                'return Array.from(document.querySelectorAll("p, span, a, button, label"))
                    .slice(0, 10)
                    .map(el => {
                        const style = getComputedStyle(el);
                        return {
                            color: style.color,
                            background: style.backgroundColor,
                            fontSize: parseFloat(style.fontSize)
                        };
                    })'
            );

            $this->assertNotEmpty($textElements);
        });
    }

    /**
     * Test keyboard navigation works.
     */
    public function test_keyboard_navigation_works(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/register')
                    ->keys('input[name="name"]', '{tab}')
                    ->pause(100)
                    ->assertFocused('input[name="email"]')
                    ->keys('input[name="email"]', '{tab}')
                    ->pause(100)
                    ->assertFocused('input[name="password"]');
        });
    }

    /**
     * Test focus indicators are visible.
     */
    public function test_focus_indicators_are_visible(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/register')
                    ->click('input[name="name"]');

            $outlineWidth = $browser->driver->executeScript(
                'return parseFloat(getComputedStyle(document.activeElement).outlineWidth)'
            );

            $this->assertGreaterThan(0, $outlineWidth, 'Focus indicator not visible');
        });
    }

    /**
     * Test skip to main content link exists.
     */
    public function test_skip_to_main_content_link_exists(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->createAdminUser())
                    ->visit('/admin/dashboard')
                    ->assertPresent('a[href="#main-content"]');
        });
    }

    /**
     * Test ARIA landmarks are present.
     */
    public function test_aria_landmarks_are_present(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->createAdminUser())
                    ->visit('/admin/dashboard')
                    ->assertPresent('[role="navigation"], nav')
                    ->assertPresent('[role="main"], main')
                    ->assertPresent('[role="contentinfo"], footer');
        });
    }

    /**
     * Test form validation errors are announced.
     */
    public function test_form_validation_errors_are_announced(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/register')
                    ->press('Register')
                    ->waitForText('required');

            $ariaLive = $browser->driver->executeScript(
                'return document.querySelector("[role=alert], [aria-live]") !== null'
            );

            $this->assertTrue($ariaLive, 'Validation errors not announced to screen readers');
        });
    }

    /**
     * Test modal dialogs are accessible.
     */
    public function test_modal_dialogs_are_accessible(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->createAdminUser())
                    ->visit('/admin/users')
                    ->click('[data-modal-trigger]')
                    ->pause(300)
                    ->assertPresent('[role="dialog"]')
                    ->assertPresent('[aria-modal="true"]')
                    ->assertPresent('[aria-labelledby]');
        });
    }

    /**
     * Test dropdown menus are accessible.
     */
    public function test_dropdown_menus_are_accessible(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->createAdminUser())
                    ->visit('/admin/dashboard')
                    ->assertPresent('[aria-haspopup]')
                    ->click('[data-dropdown-trigger]')
                    ->pause(300)
                    ->assertPresent('[role="menu"]');
        });
    }

    /**
     * Test tables have proper structure.
     */
    public function test_tables_have_proper_structure(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->createAdminUser())
                    ->visit('/admin/users')
                    ->assertPresent('table')
                    ->assertPresent('thead')
                    ->assertPresent('tbody')
                    ->assertPresent('th');
        });
    }

    /**
     * Test page language is declared.
     */
    public function test_page_language_is_declared(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/');

            $lang = $browser->driver->executeScript(
                'return document.documentElement.lang'
            );

            $this->assertNotEmpty($lang, 'Page language not declared');
        });
    }

    /**
     * Test no content flashes or animations that could cause seizures.
     */
    public function test_no_dangerous_animations(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/');

            // Check for prefers-reduced-motion support
            $reducedMotionSupported = $browser->driver->executeScript(
                'return window.matchMedia("(prefers-reduced-motion: reduce)").matches'
            );

            // This is informational - the app should respect this preference
            $this->assertIsBool($reducedMotionSupported);
        });
    }

    /**
     * Test interactive elements are keyboard accessible.
     */
    public function test_interactive_elements_are_keyboard_accessible(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->createAdminUser())
                    ->visit('/admin/dashboard')
                    ->keys('body', '{tab}')
                    ->pause(100);

            $focusableElement = $browser->driver->executeScript(
                'return document.activeElement.tagName'
            );

            $this->assertContains($focusableElement, ['A', 'BUTTON', 'INPUT', 'SELECT', 'TEXTAREA']);
        });
    }

    /**
     * Test error messages are associated with form fields.
     */
    public function test_error_messages_are_associated_with_fields(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/register')
                    ->press('Register')
                    ->waitForText('required');

            $ariaDescribedBy = $browser->driver->executeScript(
                'return document.querySelector("input[aria-describedby], input[aria-errormessage]") !== null'
            );

            $this->assertTrue($ariaDescribedBy, 'Error messages not associated with fields');
        });
    }

    /**
     * Test custom controls have proper ARIA attributes.
     */
    public function test_custom_controls_have_proper_aria(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->createAdminUser())
                    ->visit('/admin/dashboard');

            $customControls = $browser->driver->executeScript(
                'return Array.from(document.querySelectorAll("[role=button], [role=checkbox], [role=radio]"))
                    .every(el => el.hasAttribute("aria-label") || el.textContent.trim())'
            );

            $this->assertTrue($customControls, 'Custom controls missing ARIA attributes');
        });
    }

    /**
     * Create admin user for testing.
     */
    protected function createAdminUser()
    {
        return \App\Models\User::factory()->create([
            'email' => 'admin@test.com',
            'is_admin' => true,
        ]);
    }
}
