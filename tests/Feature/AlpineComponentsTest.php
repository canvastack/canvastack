<?php

namespace Canvastack\Canvastack\Tests\Feature;

use Canvastack\Canvastack\Tests\TestCase;

class AlpineComponentsTest extends TestCase
{
    /** @test */
    public function it_renders_dropdown_component()
    {
        $view = $this->blade(
            '<x-ui.dropdown>
                <x-slot name="trigger">
                    <button>Options</button>
                </x-slot>
                <x-ui.dropdown-link href="/profile">Profile</x-ui.dropdown-link>
            </x-ui.dropdown>'
        );

        $view->assertSee('x-data');
        $view->assertSee('open: false');
        $view->assertSee('@click.away');
        $view->assertSee('Options');
        $view->assertSee('Profile');
    }

    /** @test */
    public function it_renders_modal_component()
    {
        $view = $this->blade(
            '<x-ui.modal name="test-modal">
                <x-slot name="header">
                    <h3>Test Modal</h3>
                </x-slot>
                Modal content
            </x-ui.modal>'
        );

        $view->assertSee('x-data');
        $view->assertSee('test-modal');
        $view->assertSee('Test Modal');
        $view->assertSee('Modal content');
        $view->assertSee('open-modal');
        $view->assertSee('close-modal');
    }

    /** @test */
    public function it_renders_sidebar_toggle_desktop()
    {
        $view = $this->blade('<x-ui.sidebar-toggle />');

        $view->assertSee('window.toggleSidebar()');
        $view->assertSee('Toggle sidebar');
        $view->assertSee('hidden lg:block');
    }

    /** @test */
    public function it_renders_sidebar_toggle_mobile()
    {
        $view = $this->blade('<x-ui.sidebar-toggle mobile />');

        $view->assertSee('window.openSidebarMobile()');
        $view->assertSee('Open sidebar');
        $view->assertSee('lg:hidden');
    }

    /** @test */
    public function it_renders_dark_mode_toggle_icon_variant()
    {
        $view = $this->blade('<x-ui.dark-mode-toggle />');

        $view->assertSee('window.toggleDark()', false);
        $view->assertSee('Toggle dark mode', false);
        $view->assertSee('data-lucide="moon"', false);
        $view->assertSee('data-lucide="sun"', false);
    }

    /** @test */
    public function it_renders_dark_mode_toggle_button_variant()
    {
        $view = $this->blade('<x-ui.dark-mode-toggle variant="button" />');

        $view->assertSee('window.toggleDark()');
        $view->assertSee('Dark Mode');
        $view->assertSee('Light Mode');
    }

    /** @test */
    public function it_renders_dark_mode_toggle_switch_variant()
    {
        $view = $this->blade('<x-ui.dark-mode-toggle variant="switch" />');

        $view->assertSee('window.toggleDark()', false);
        $view->assertSee('type="checkbox"', false);
        $view->assertSee('x-model="isDark"', false);
    }

    /** @test */
    public function dropdown_supports_different_alignments()
    {
        $leftAligned = $this->blade(
            '<x-ui.dropdown align="left">
                <x-slot name="trigger"><button>Left</button></x-slot>
            </x-ui.dropdown>'
        );

        $rightAligned = $this->blade(
            '<x-ui.dropdown align="right">
                <x-slot name="trigger"><button>Right</button></x-slot>
            </x-ui.dropdown>'
        );

        $leftAligned->assertSee('left-0');
        $rightAligned->assertSee('right-0');
    }

    /** @test */
    public function dropdown_supports_different_widths()
    {
        $narrow = $this->blade(
            '<x-ui.dropdown width="48">
                <x-slot name="trigger"><button>Narrow</button></x-slot>
            </x-ui.dropdown>'
        );

        $wide = $this->blade(
            '<x-ui.dropdown width="72">
                <x-slot name="trigger"><button>Wide</button></x-slot>
            </x-ui.dropdown>'
        );

        $narrow->assertSee('w-48');
        $wide->assertSee('w-72');
    }

    /** @test */
    public function modal_supports_different_max_widths()
    {
        $small = $this->blade('<x-ui.modal name="small" max-width="sm">Content</x-ui.modal>');
        $large = $this->blade('<x-ui.modal name="large" max-width="2xl">Content</x-ui.modal>');

        $small->assertSee('max-w-sm');
        $large->assertSee('max-w-2xl');
    }

    /** @test */
    public function modal_includes_escape_key_handler()
    {
        $view = $this->blade('<x-ui.modal name="test">Content</x-ui.modal>');

        $view->assertSee('x-on:keydown.escape.window');
    }

    /** @test */
    public function modal_includes_click_outside_handler()
    {
        $view = $this->blade('<x-ui.modal name="test">Content</x-ui.modal>');

        $view->assertSee('@click.self="show = false"', false);
    }

    /** @test */
    public function dark_mode_toggle_supports_different_sizes()
    {
        $small = $this->blade('<x-ui.dark-mode-toggle size="sm" />');
        $medium = $this->blade('<x-ui.dark-mode-toggle size="md" />');
        $large = $this->blade('<x-ui.dark-mode-toggle size="lg" />');

        $small->assertSee('p-1.5');
        $medium->assertSee('p-2');
        $large->assertSee('p-2.5');
    }

    /** @test */
    public function components_include_alpine_transitions()
    {
        $dropdown = $this->blade(
            '<x-ui.dropdown>
                <x-slot name="trigger"><button>Test</button></x-slot>
            </x-ui.dropdown>'
        );

        $modal = $this->blade('<x-ui.modal name="test">Content</x-ui.modal>');

        $dropdown->assertSee('x-transition');
        $modal->assertSee('x-transition');
    }

    /** @test */
    public function components_include_dark_mode_classes()
    {
        $dropdown = $this->blade(
            '<x-ui.dropdown>
                <x-slot name="trigger"><button>Test</button></x-slot>
            </x-ui.dropdown>'
        );

        $modal = $this->blade('<x-ui.modal name="test">Content</x-ui.modal>');

        $dropdown->assertSee('dark:bg-gray-900');
        $modal->assertSee('dark:bg-gray-900');
    }

    /** @test */
    public function dark_mode_toggle_listens_to_dark_mode_events()
    {
        $view = $this->blade('<x-ui.dark-mode-toggle />');

        $view->assertSee('x-on:darkmode:enabled.window');
        $view->assertSee('x-on:darkmode:disabled.window');
    }
}
