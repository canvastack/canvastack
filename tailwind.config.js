/** @type {import('tailwindcss').Config} */
export default {
  content: [
    './resources/**/*.blade.php',
    './resources/**/*.js',
    './resources/**/*.vue',
    './src/**/*.php',
  ],
  darkMode: 'class',
  theme: {
    extend: {
      colors: {
        // Primary gradient colors (Indigo)
        indigo: {
          50: '#eef2ff',
          100: '#e0e7ff',
          200: '#c7d2fe',
          300: '#a5b4fc',
          400: '#818cf8',
          500: '#6366f1',  // Primary
          600: '#4f46e5',
          700: '#4338ca',
          800: '#3730a3',
          900: '#312e81',
          950: '#1e1b4b',
        },
        // Secondary gradient colors (Purple)
        purple: {
          50: '#faf5ff',
          100: '#f3e8ff',
          200: '#e9d5ff',
          300: '#d8b4fe',
          400: '#c084fc',
          500: '#8b5cf6',  // Secondary
          600: '#7c3aed',
          700: '#6d28d9',
          800: '#5b21b6',
          900: '#581c87',
          950: '#3b0764',
        },
        // Accent gradient colors (Fuchsia)
        fuchsia: {
          50: '#fdf4ff',
          100: '#fae8ff',
          200: '#f5d0fe',
          300: '#f0abfc',
          400: '#e879f9',
          500: '#a855f7',  // Accent
          600: '#c026d3',
          700: '#a21caf',
          800: '#86198f',
          900: '#701a75',
          950: '#4a044e',
        },
        // Semantic colors
        emerald: {
          100: '#d1fae5',
          400: '#34d399',
          600: '#059669',
        },
        amber: {
          100: '#fef3c7',
          400: '#fbbf24',
          600: '#d97706',
        },
        red: {
          100: '#fee2e2',
          400: '#f87171',
          600: '#dc2626',
        },
        blue: {
          100: '#dbeafe',
          400: '#60a5fa',
          600: '#2563eb',
        },
      },
      fontFamily: {
        sans: ['Inter', 'system-ui', '-apple-system', 'sans-serif'],
        mono: ['JetBrains Mono', 'Fira Code', 'monospace'],
      },
      fontSize: {
        xs: '0.75rem',      // 12px
        sm: '0.875rem',     // 14px
        base: '1rem',       // 16px
        lg: '1.125rem',     // 18px
        xl: '1.25rem',      // 20px
        '2xl': '1.5rem',    // 24px
        '3xl': '1.875rem',  // 30px
        '4xl': '2.25rem',   // 36px
        '5xl': '3rem',      // 48px
        '6xl': '3.75rem',   // 60px
        '7xl': '4.5rem',    // 72px
      },
      lineHeight: {
        tight: '1.25',
        normal: '1.5',
        relaxed: '1.75',
      },
      borderRadius: {
        sm: '0.375rem',   // 6px
        DEFAULT: '0.5rem', // 8px
        md: '0.5rem',     // 8px
        lg: '0.75rem',    // 12px
        xl: '1rem',       // 16px
        '2xl': '1.5rem',  // 24px
        '3xl': '2rem',    // 32px
        '4xl': '2rem',    // 32px (alias)
      },
      boxShadow: {
        sm: '0 1px 2px 0 rgb(0 0 0 / 0.05)',
        DEFAULT: '0 1px 3px 0 rgb(0 0 0 / 0.1)',
        md: '0 4px 6px -1px rgb(0 0 0 / 0.1)',
        lg: '0 10px 15px -3px rgb(0 0 0 / 0.1)',
        xl: '0 20px 25px -5px rgb(0 0 0 / 0.1)',
        '2xl': '0 25px 50px -12px rgb(0 0 0 / 0.25)',
      },
      maxWidth: {
        '7xl': '80rem',   // 1280px
        '8xl': '88rem',
        '9xl': '96rem',
      },
      container: {
        center: true,
        padding: '1rem',
        screens: {
          sm: '640px',
          md: '768px',
          lg: '1024px',
          xl: '1280px',
          '2xl': '1536px',
        },
      },
    },
  },
  plugins: [
    require('daisyui'),
    require('@tailwindcss/forms'),
    require('@tailwindcss/typography'),
    // RTL support plugin
    function({ addUtilities, addVariant }) {
      // Add RTL variant
      addVariant('rtl', '[dir="rtl"] &');
      addVariant('ltr', '[dir="ltr"] &');
      
      // Add RTL-specific utilities
      addUtilities({
        '.rtl-flip': {
          '[dir="rtl"] &': {
            transform: 'scaleX(-1)',
          },
        },
        '.rtl-rotate-180': {
          '[dir="rtl"] &': {
            transform: 'rotate(180deg)',
          },
        },
        '.force-ltr': {
          direction: 'ltr !important',
          textAlign: 'left !important',
        },
        '.force-rtl': {
          direction: 'rtl !important',
          textAlign: 'right !important',
        },
      });
    },
  ],
  daisyui: {
    themes: [
      {
        light: {
          'primary': '#6366f1',      // Indigo 500
          'secondary': '#8b5cf6',    // Purple 500
          'accent': '#a855f7',       // Fuchsia 500
          'neutral': '#1f2937',      // Gray 800
          'base-100': '#ffffff',     // White
          'base-200': '#f3f4f6',     // Gray 100
          'base-300': '#e5e7eb',     // Gray 200
          'info': '#60a5fa',         // Blue 400
          'success': '#34d399',      // Emerald 400
          'warning': '#fbbf24',      // Amber 400
          'error': '#f87171',        // Red 400
        },
        dark: {
          'primary': '#6366f1',      // Indigo 500
          'secondary': '#8b5cf6',    // Purple 500
          'accent': '#a855f7',       // Fuchsia 500
          'neutral': '#f3f4f6',      // Gray 100
          'base-100': '#030712',     // Gray 950
          'base-200': '#111827',     // Gray 900
          'base-300': '#1f2937',     // Gray 800
          'info': '#60a5fa',         // Blue 400
          'success': '#34d399',      // Emerald 400
          'warning': '#fbbf24',      // Amber 400
          'error': '#f87171',        // Red 400
        },
      },
    ],
    darkTheme: 'dark',
    base: true,
    styled: true,
    utils: true,
    logs: false,
  },
};
