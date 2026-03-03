<?php

namespace Canvastack\Canvastack\Tests\Unit\Components\Form;

use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Support\Facades\App;

class FormTranslationTest extends TestCase
{
    /** @test */
    public function file_upload_component_uses_translations_in_english()
    {
        App::setLocale('en');

        // Share errors bag with view
        view()->share('errors', session()->get('errors', new \Illuminate\Support\ViewErrorBag()));

        $view = view('canvastack::components.form.file', [
            'name' => 'test_file',
            'label' => 'Test File',
            'accept' => null,
            'multiple' => false,
            'required' => false,
            'disabled' => false,
            'error' => null,
            'hint' => null,
            'preview' => false,
            'attributes' => collect([]),
        ]);

        $html = $view->render();

        // Check that English translations are present
        $this->assertStringContainsString('Drag and drop files here', $html);
        $this->assertStringContainsString('or', $html);
        $this->assertStringContainsString('Browse files', $html);
    }

    /** @test */
    public function file_upload_component_uses_translations_in_indonesian()
    {
        App::setLocale('id');

        // Share errors bag with view
        view()->share('errors', session()->get('errors', new \Illuminate\Support\ViewErrorBag()));

        $view = view('canvastack::components.form.file', [
            'name' => 'test_file',
            'label' => 'Test File',
            'accept' => null,
            'multiple' => false,
            'required' => false,
            'disabled' => false,
            'error' => null,
            'hint' => null,
            'preview' => false,
            'attributes' => collect([]),
        ]);

        $html = $view->render();

        // Check that Indonesian translations are present
        $this->assertStringContainsString('Seret dan lepas file di sini', $html);
        $this->assertStringContainsString('atau', $html);
        $this->assertStringContainsString('Jelajahi file', $html);
    }

    /** @test */
    public function translation_keys_exist_in_english()
    {
        App::setLocale('en');

        $this->assertEquals('Drag and drop files here', __('canvastack::components.form.file_upload.drag_drop'));
        $this->assertEquals('or', __('canvastack::components.form.file_upload.or'));
        $this->assertEquals('Browse files', __('canvastack::components.form.file_upload.browse'));
        $this->assertEquals('file(s) selected', __('canvastack::components.form.file_upload.files_selected'));
    }

    /** @test */
    public function translation_keys_exist_in_indonesian()
    {
        App::setLocale('id');

        $this->assertEquals('Seret dan lepas file di sini', __('canvastack::components.form.file_upload.drag_drop'));
        $this->assertEquals('atau', __('canvastack::components.form.file_upload.or'));
        $this->assertEquals('Jelajahi file', __('canvastack::components.form.file_upload.browse'));
        $this->assertEquals('file dipilih', __('canvastack::components.form.file_upload.files_selected'));
    }
}
