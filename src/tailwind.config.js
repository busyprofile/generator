import defaultTheme from "tailwindcss/defaultTheme";
import forms from "@tailwindcss/forms";
const primeUi = require("tailwindcss-primeui");
const flowbitePlugin = require("flowbite/plugin");
/** @type {import('tailwindcss').Config} */
export default {
  darkMode: "class",
  content: [
    "./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php",
    "./storage/framework/views/*.php",
    "./resources/views/**/*.blade.php",
    "./resources/js/**/*.vue",
    "./node_modules/primevue/**/*.{vue,js,ts,jsx,tsx}",
    "./node_modules/flowbite/**/*.js",
  ],

  theme: {
    extend: {
      fontFamily: {
        sans: ["Inter", "system-ui", ...defaultTheme.fontFamily.sans],
        logo: ["Cinzel", "serif"],
        heading: ["Playfair Display", "serif"],
      },
      fontSize: {
        xxs: "0.65rem",
        xs: "0.75rem",
        sm: "0.875rem",
        base: "1rem",
        lg: "1.125rem",
        xl: "1.25rem",
        "2xl": "1.5rem",
      },
      blur: {
        xs: "2px",
      },
      borderRadius: {
        menu: "0.5rem",
        table: "0.5rem",
        plate: "0.5rem",
        alert: "0.5rem",
        "table-raw": "0.5rem",
        tag: "0.25rem",
        card: "0.375rem",
        btn: "0.375rem",
        badge: "5px", // Для круглых бейджей
        xl: "0.5rem",
      },
      spacing: {
        0.5: "0.125rem",
        1: "0.25rem",
        1.5: "0.375rem",
        2: "0.5rem",
        2.5: "0.625rem",
        3: "0.75rem",
        3.5: "0.875rem",
        4: "1rem",
        5: "1.25rem",
        6: "1.5rem",
        7: "1.75rem",
        8: "2rem",
        9: "2.25rem",
        10: "2.5rem",
      },
      colors: {
        // ── Семантические цвета из новой темы ──
        // Используем CSS-переменные напрямую (Tailwind v3 поддерживает это через JIT)
        background: "var(--background)",
        foreground: "var(--foreground)",
        primary: {
          DEFAULT: "#43E3F4",
          foreground: "#062830",
          hover: "#22d3ee",
          active: "#06b6d4",
        },
        secondary: {
          DEFAULT: "var(--secondary)",
          foreground: "var(--secondary-foreground)",
        },
        muted: {
          DEFAULT: "var(--muted)",
          foreground: "var(--muted-foreground)",
        },
        accent: {
          DEFAULT: "var(--accent)",
          foreground: "var(--accent-foreground)",
        },
        destructive: {
          DEFAULT: "oklch(var(--destructive-ch) / <alpha-value>)",
          foreground: "var(--destructive-foreground)",
        },
        border: "var(--border)",
        input: "var(--input)",
        ring: "var(--ring)",
        card: {
          DEFAULT: "var(--card)",
          foreground: "var(--card-foreground)",
        },
        popover: {
          DEFAULT: "var(--popover)",
          foreground: "var(--popover-foreground)",
        },
        sidebar: {
          DEFAULT: "var(--sidebar)",
          foreground: "var(--sidebar-foreground)",
          primary: {
            DEFAULT: "var(--sidebar-primary)",
            foreground: "var(--sidebar-primary-foreground)",
          },
          accent: {
            DEFAULT: "var(--sidebar-accent)",
            foreground: "var(--sidebar-accent-foreground)",
          },
          border: "var(--sidebar-border)",
          ring: "var(--sidebar-ring)",
        },

        // ── Активный пункт меню ──
        "menu-active": "#43E3F4",

        // ── Бейджи (статусы) ──
        badge: {
          info: "#43E3F4",
          "info-text": "#062830",
          "info-dark": "#0e7490",
          "info-dark-text": "#43E3F4",
          warning: "#43E3F4",
          "warning-text": "#43E3F4",
          danger: "#c5222a",
          "danger-text": "#ff4b4b",
          "info-solid": "#43E3F4",
        },

        // ── Расширенная шкала серого ──
        gray: {
          950: "#0A0B0D",
          900: "#111315",
          800: "#18181b",
          750: "#1F2125",
          700: "#27272a",
          650: "#2E3035",
          600: "#374151",
          550: "#454B58",
          500: "#6B7280",
          400: "#9CA3AF",
          300: "#D1D5DB",
          200: "#E5E7EB",
          100: "#F3F4F6",
          50:  "#F9FAFB",
        },
      },
      backgroundImage: {
        "gradient-sidebar": "linear-gradient(to bottom, #09090b, #18181b)",
        "gradient-card": "linear-gradient(to bottom, #18181b, #09090b)",
        "gradient-button": "linear-gradient(to bottom, #43E3F4, #22d3ee)",
        "gradient-primary": "linear-gradient(to bottom, #67e8f9, #43E3F4)",
        "gradient-badge": "linear-gradient(to bottom, #67e8f9, #43E3F4)",
      },
      boxShadow: {
        card: "0 1px 3px 0 rgba(0, 0, 0, 0.2), 0 1px 2px 0 rgba(0, 0, 0, 0.1)",
        dropdown:
          "0 4px 6px -1px rgba(0, 0, 0, 0.3), 0 2px 4px -1px rgba(0, 0, 0, 0.2)",
        sidebar: "1px 0 3px 0 rgba(0, 0, 0, 0.2)",
        button: "0 1px 2px 0 rgba(0, 0, 0, 0.15)",
        inner: "inset 0 1px 3px 0 rgba(0, 0, 0, 0.25)",
        glow: "0 0 8px 2px rgba(67, 227, 244, 0.3)",
      },
      transitionProperty: {
        height: "height",
        spacing: "margin, padding",
        opacity: "opacity",
        transform: "transform",
      },
      animation: {
        "fade-in": "fadeIn 0.2s ease-in-out",
        "slide-down": "slideDown 0.25s ease-in-out",
        "pulse-once": "pulse 1.5s ease-in-out 1",
      },
      keyframes: {
        fadeIn: {
          "0%": { opacity: "0" },
          "100%": { opacity: "1" },
        },
        slideDown: {
          "0%": { transform: "translateY(-10px)", opacity: "0" },
          "100%": { transform: "translateY(0)", opacity: "1" },
        },
        pulse: {
          "0%, 100%": { opacity: "1" },
          "50%": { opacity: "0.7" },
        },
      },
    },
  },

  plugins: [
    forms,
    primeUi,
    flowbitePlugin,
    // Можно оставить ваш кастомный плагин для скроллбара, если он нужен
    // plugin(function ({ addUtilities, theme }) { ... })
  ],
};
