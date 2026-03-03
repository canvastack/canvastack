<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Support\Theme;

use Canvastack\Canvastack\Contracts\ThemeInterface;

/**
 * CSS Injector.
 *
 * Handles runtime injection of CSS variables and theme styles
 * into the HTML document.
 */
class CssInjector
{
    /**
     * CSS variable generator.
     */
    protected CssVariableGenerator $generator;

    /**
     * Whether to minify CSS output.
     */
    protected bool $minify = false;

    /**
     * Create a new CSS injector instance.
     */
    public function __construct(?CssVariableGenerator $generator = null)
    {
        $this->generator = $generator ?? new CssVariableGenerator();
    }

    /**
     * Inject CSS variables for a theme.
     */
    public function inject(ThemeInterface $theme): string
    {
        $variables = $this->generator->generate($theme);

        return $this->generateStyleTag($variables);
    }

    /**
     * Inject CSS variables as inline style tag.
     */
    public function injectInline(ThemeInterface $theme): string
    {
        return $this->inject($theme);
    }

    /**
     * Generate CSS variables as style tag.
     */
    public function generateStyleTag(array $variables): string
    {
        $css = $this->generateCss($variables);

        return "<style id=\"canvastack-theme-variables\">\n{$css}\n</style>";
    }

    /**
     * Generate CSS from variables.
     */
    public function generateCss(array $variables): string
    {
        if (empty($variables)) {
            return '';
        }

        $indent = $this->minify ? '' : '  ';
        $newline = $this->minify ? '' : "\n";
        $space = $this->minify ? '' : ' ';

        $css = ":root{$space}{{$newline}";

        foreach ($variables as $name => $value) {
            $css .= "{$indent}{$name}:{$space}{$value};{$newline}";
        }

        $css .= '}';

        return $css;
    }

    /**
     * Generate CSS with dark mode support.
     */
    public function generateCssWithDarkMode(array $lightVariables, array $darkVariables): string
    {
        $indent = $this->minify ? '' : '  ';
        $newline = $this->minify ? '' : "\n";
        $space = $this->minify ? '' : ' ';

        // Light mode (default)
        $css = ":root{$space}{{$newline}";
        foreach ($lightVariables as $name => $value) {
            $css .= "{$indent}{$name}:{$space}{$value};{$newline}";
        }
        $css .= "}{$newline}";

        // Dark mode
        if (!empty($darkVariables)) {
            $css .= "{$newline}.dark{$space}{{$newline}";
            foreach ($darkVariables as $name => $value) {
                $css .= "{$indent}{$name}:{$space}{$value};{$newline}";
            }
            $css .= '}';
        }

        return $css;
    }

    /**
     * Inject JavaScript for dynamic theme switching.
     */
    public function injectThemeSwitcher(array $themes): string
    {
        $themesJson = json_encode($themes, JSON_HEX_TAG | JSON_HEX_AMP);

        $js = <<<JS
<script id="canvastack-theme-switcher">
(function() {
  window.CanvastackTheme = {
    themes: {$themesJson},
    current: null,
    
    init: function() {
      const saved = localStorage.getItem('canvastack-theme');
      if (saved && this.themes[saved]) {
        this.switch(saved);
      }
    },
    
    switch: function(themeName) {
      if (!this.themes[themeName]) {
        console.error('Theme not found:', themeName);
        return;
      }
      
      const theme = this.themes[themeName];
      this.current = themeName;
      
      // Update CSS variables
      this.updateVariables(theme.variables);
      
      // Save preference
      localStorage.setItem('canvastack-theme', themeName);
      
      // Dispatch event
      window.dispatchEvent(new CustomEvent('theme-changed', { 
        detail: { theme: themeName } 
      }));
    },
    
    updateVariables: function(variables) {
      const root = document.documentElement;
      for (const [name, value] of Object.entries(variables)) {
        root.style.setProperty(name, value);
      }
    },
    
    get: function(variableName) {
      return getComputedStyle(document.documentElement)
        .getPropertyValue(variableName);
    }
  };
  
  // Auto-initialize
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
      window.CanvastackTheme.init();
    });
  } else {
    window.CanvastackTheme.init();
  }
})();
</script>
JS;

        return $js;
    }

    /**
     * Generate theme data for JavaScript.
     */
    public function generateThemeData(array $themes): array
    {
        $data = [];

        foreach ($themes as $name => $theme) {
            if ($theme instanceof ThemeInterface) {
                $data[$name] = [
                    'name' => $theme->getName(),
                    'displayName' => $theme->getDisplayName(),
                    'variables' => $this->generator->generate($theme),
                ];
            }
        }

        return $data;
    }

    /**
     * Inject complete theme system (CSS + JS).
     */
    public function injectComplete(ThemeInterface $currentTheme, array $allThemes = []): string
    {
        $output = '';

        // Inject current theme CSS
        $output .= $this->inject($currentTheme);
        $output .= "\n";

        // Inject theme switcher if multiple themes available
        if (!empty($allThemes)) {
            $themeData = $this->generateThemeData($allThemes);
            $output .= $this->injectThemeSwitcher($themeData);
        }

        return $output;
    }

    /**
     * Set whether to minify CSS output.
     */
    public function setMinify(bool $minify): self
    {
        $this->minify = $minify;

        return $this;
    }

    /**
     * Get whether CSS output is minified.
     */
    public function isMinified(): bool
    {
        return $this->minify;
    }

    /**
     * Set the CSS variable generator.
     */
    public function setGenerator(CssVariableGenerator $generator): self
    {
        $this->generator = $generator;

        return $this;
    }

    /**
     * Get the CSS variable generator.
     */
    public function getGenerator(): CssVariableGenerator
    {
        return $this->generator;
    }
}
