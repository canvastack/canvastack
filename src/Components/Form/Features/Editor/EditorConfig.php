<?php

namespace Canvastack\Canvastack\Components\Form\Features\Editor;

/**
 * Editor Configuration Class.
 *
 * Manages CKEditor configuration including default settings, toolbar configurations,
 * and custom configuration merging.
 *
 * Requirements: 4.5, 4.6
 */
class EditorConfig
{
    /**
     * Custom configuration overrides.
     */
    protected array $customConfig;

    /**
     * Create a new editor configuration instance.
     *
     * @param array $customConfig Custom configuration to override defaults
     */
    public function __construct(array $customConfig = [])
    {
        $this->customConfig = $customConfig;
    }

    /**
     * Get default CKEditor configuration.
     *
     * @return array Default configuration array
     *
     * Requirements: 4.5
     */
    public function getDefaults(): array
    {
        return array_merge([
            'toolbar' => $this->getDefaultToolbar(),
            'language' => 'en',
            'height' => 300,
            'removePlugins' => [],
            'extraPlugins' => [],
            'contentsCss' => [],
            'bodyClass' => 'ckeditor-body',
            'format_tags' => 'p;h1;h2;h3;h4;h5;h6;pre;address;div',
            'removeButtons' => '',
            'allowedContent' => true,
            'extraAllowedContent' => '',
            'disallowedContent' => 'script; *[on*]',
            'forcePasteAsPlainText' => false,
            'pasteFromWordRemoveFontStyles' => true,
            'pasteFromWordRemoveStyles' => true,
            'enterMode' => 'CKEDITOR.ENTER_P',
            'shiftEnterMode' => 'CKEDITOR.ENTER_BR',
            'autoParagraph' => true,
            'fillEmptyBlocks' => true,
            'startupFocus' => false,
            'tabSpaces' => 0,
            'resize_enabled' => true,
            'resize_dir' => 'both',
            'resize_minWidth' => 450,
            'resize_minHeight' => 200,
            'resize_maxWidth' => 3000,
            'resize_maxHeight' => 3000,
        ], $this->customConfig);
    }

    /**
     * Get default toolbar configuration.
     *
     * Provides a balanced set of formatting options suitable for most use cases.
     *
     * @return array Default toolbar configuration
     *
     * Requirements: 4.6
     */
    public function getDefaultToolbar(): array
    {
        return [
            ['name' => 'document', 'items' => ['Source', '-', 'Save', 'NewPage', 'Preview', 'Print', '-', 'Templates']],
            ['name' => 'clipboard', 'items' => ['Cut', 'Copy', 'Paste', 'PasteText', 'PasteFromWord', '-', 'Undo', 'Redo']],
            ['name' => 'editing', 'items' => ['Find', 'Replace', '-', 'SelectAll', '-', 'Scayt']],
            '/',
            ['name' => 'basicstyles', 'items' => ['Bold', 'Italic', 'Underline', 'Strike', 'Subscript', 'Superscript', '-', 'CopyFormatting', 'RemoveFormat']],
            ['name' => 'paragraph', 'items' => ['NumberedList', 'BulletedList', '-', 'Outdent', 'Indent', '-', 'Blockquote', 'CreateDiv', '-', 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock', '-', 'BidiLtr', 'BidiRtl']],
            ['name' => 'links', 'items' => ['Link', 'Unlink', 'Anchor']],
            ['name' => 'insert', 'items' => ['Image', 'Table', 'HorizontalRule', 'SpecialChar', 'PageBreak', 'Iframe']],
            '/',
            ['name' => 'styles', 'items' => ['Styles', 'Format', 'Font', 'FontSize']],
            ['name' => 'colors', 'items' => ['TextColor', 'BGColor']],
            ['name' => 'tools', 'items' => ['Maximize', 'ShowBlocks']],
        ];
    }

    /**
     * Get minimal toolbar configuration.
     *
     * Provides only essential formatting options for simple content editing.
     *
     * @return array Minimal toolbar configuration
     *
     * Requirements: 4.6
     */
    public function getMinimalToolbar(): array
    {
        return [
            ['name' => 'basicstyles', 'items' => ['Bold', 'Italic', 'Underline']],
            ['name' => 'paragraph', 'items' => ['NumberedList', 'BulletedList']],
            ['name' => 'links', 'items' => ['Link', 'Unlink']],
            ['name' => 'clipboard', 'items' => ['Undo', 'Redo']],
        ];
    }

    /**
     * Get full toolbar configuration.
     *
     * Provides all available formatting options for advanced content editing.
     *
     * @return array Full toolbar configuration
     *
     * Requirements: 4.6
     */
    public function getFullToolbar(): array
    {
        return [
            ['name' => 'document', 'items' => ['Source', '-', 'Save', 'NewPage', 'ExportPdf', 'Preview', 'Print', '-', 'Templates']],
            ['name' => 'clipboard', 'items' => ['Cut', 'Copy', 'Paste', 'PasteText', 'PasteFromWord', '-', 'Undo', 'Redo']],
            ['name' => 'editing', 'items' => ['Find', 'Replace', '-', 'SelectAll', '-', 'Scayt']],
            ['name' => 'forms', 'items' => ['Form', 'Checkbox', 'Radio', 'TextField', 'Textarea', 'Select', 'Button', 'ImageButton', 'HiddenField']],
            '/',
            ['name' => 'basicstyles', 'items' => ['Bold', 'Italic', 'Underline', 'Strike', 'Subscript', 'Superscript', '-', 'CopyFormatting', 'RemoveFormat']],
            ['name' => 'paragraph', 'items' => ['NumberedList', 'BulletedList', '-', 'Outdent', 'Indent', '-', 'Blockquote', 'CreateDiv', '-', 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock', '-', 'BidiLtr', 'BidiRtl', 'Language']],
            ['name' => 'links', 'items' => ['Link', 'Unlink', 'Anchor']],
            ['name' => 'insert', 'items' => ['Image', 'Flash', 'Table', 'HorizontalRule', 'Smiley', 'SpecialChar', 'PageBreak', 'Iframe']],
            '/',
            ['name' => 'styles', 'items' => ['Styles', 'Format', 'Font', 'FontSize']],
            ['name' => 'colors', 'items' => ['TextColor', 'BGColor']],
            ['name' => 'tools', 'items' => ['Maximize', 'ShowBlocks']],
            ['name' => 'about', 'items' => ['About']],
        ];
    }

    /**
     * Get toolbar configuration by name.
     *
     * @param string $name Toolbar name: 'default', 'minimal', or 'full'
     * @return array Toolbar configuration
     */
    public function getToolbar(string $name = 'default'): array
    {
        return match (strtolower($name)) {
            'minimal' => $this->getMinimalToolbar(),
            'full' => $this->getFullToolbar(),
            default => $this->getDefaultToolbar(),
        };
    }

    /**
     * Merge custom configuration with defaults.
     *
     * @param array $custom Custom configuration to merge
     * @return array Merged configuration
     */
    public function merge(array $custom): array
    {
        return array_merge($this->getDefaults(), $custom);
    }

    /**
     * Get configuration for a specific context (admin or public).
     *
     * @param string $context Context name: 'admin' or 'public'
     * @return array Context-specific configuration
     */
    public function getContextConfig(string $context = 'admin'): array
    {
        $config = $this->getDefaults();

        if ($context === 'public') {
            // Simplified toolbar for public context
            $config['toolbar'] = $this->getMinimalToolbar();
            $config['height'] = 250;
            $config['removePlugins'] = ['elementspath'];
        }

        return $config;
    }

    /**
     * Get configuration with image upload support.
     *
     * @param string $uploadUrl Upload endpoint URL
     * @param string $csrfToken CSRF token for upload requests
     * @return array Configuration with image upload enabled
     */
    public function withImageUpload(string $uploadUrl, string $csrfToken): array
    {
        $config = $this->getDefaults();

        $config['filebrowserUploadUrl'] = $uploadUrl;
        $config['filebrowserUploadMethod'] = 'form';
        $config['fileTools_requestHeaders'] = [
            'X-CSRF-TOKEN' => $csrfToken,
        ];

        return $config;
    }

    /**
     * Get configuration for dark mode.
     *
     * @return array Dark mode configuration
     */
    public function getDarkModeConfig(): array
    {
        $config = $this->getDefaults();

        $config['contentsCss'] = array_merge(
            $config['contentsCss'],
            ['/css/ckeditor-dark.css']
        );
        $config['bodyClass'] = 'ckeditor-body dark-mode';

        return $config;
    }
}
