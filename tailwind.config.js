/** Tailwind config. Colours are CSS variables so the admin's palette choices apply live. */
const ramp = (name) => Object.fromEntries([50, 100, 200, 300, 400, 500, 600, 700, 800, 900].map(s => [s, `rgb(var(--${name}-${s}) / <alpha-value>)`]));
const v = (name) => `rgb(var(--${name}) / <alpha-value>)`;

module.exports = {
  content: [
    './app/**/*.php', './templates/**/*.php', './includes/**/*.php', './install/**/*.php',
    './assets/js/**/*.js', './assets/src/**/*.html',
  ],
  darkMode: ['selector', '[data-theme="dark"]'],
  safelist: [
    { pattern: /^(bg|text|border|ring)-(primary|secondary|tertiary)-(50|100|200|300|400|500|600|700|800|900)$/ },
    { pattern: /^(grid-cols|col-span)-(1|2|3|4|5|6)$/, variants: ['sm', 'md', 'lg', 'xl'] },
    // Component classes defined in assets/src/*.css are always kept (modules add pages concurrently).
    { pattern: /^(btn|card|card-flat|card-glass|card-lift|card-pad|pcard|field|input|select|textarea|label|switch|switch-row|check|chip|chip-input|badge|avatar|avatar-wrap|avatar-ring|avatar-initials|tab|tabs|tab-link|seg|table|table-wrap|modal|empty|stat|page|pager|skeleton|progress|toast|flash|admin|app|side|drawer|menu|lock|presence|divider|kbd|price|scroll-x|blur|ch|pw|file|help|error|setting|settings|section|overline|prose|glass|mesh|noise|gradient|bg-brand|bg-premium|bg-vip|count|stack|panel|link|logo|header|footer|spinner|verified|float|cookie|range|hp|no-scrollbar|count-badge|stat)(-[a-z0-9-]+)?$/ },
    'hidden', 'block', 'flex', 'sm:flex', 'md:flex', 'lg:flex', 'lg:hidden', 'md:hidden', 'sm:hidden', 'md:block', 'lg:block',
  ],
  theme: {
    container: { center: true, padding: { DEFAULT: '1.25rem', sm: '1.5rem', lg: '2rem' }, screens: { sm: '640px', md: '768px', lg: '1024px', xl: '1200px', '2xl': '1320px' } },
    extend: {
      colors: {
        primary: ramp('primary'), secondary: ramp('secondary'), tertiary: ramp('tertiary'),
        surface: { 0: v('surface-0'), 1: v('surface-1'), 2: v('surface-2'), 3: v('surface-3'), card: v('card') },
        ink: { DEFAULT: v('text-1'), 2: v('text-2'), 3: v('text-3'), inverse: v('text-inv') },
        line: v('line'),
        success: v('success'), warning: v('warning'), danger: v('danger'), info: v('info'),
      },
      // text on a tint needs the darker 'ink' version of each semantic colour (backgrounds keep the bright one)
      textColor: { success: v('success-ink'), warning: v('warning-ink'), danger: v('danger-ink'), info: v('info-ink') },
      fontFamily: {
        heading: ['var(--font-heading)'],
        body: ['var(--font-body)'],
        mono: ["'DM Mono'", 'ui-monospace', 'SFMono-Regular', 'Menlo', 'monospace'],
      },
      fontSize: {
        display: ['clamp(2.5rem, 6vw + 0.5rem, 4.25rem)', { lineHeight: '1.04', letterSpacing: '-0.03em', fontWeight: '800' }],
        h1: ['clamp(2rem, 3vw + 1rem, 2.75rem)', { lineHeight: '1.15', letterSpacing: '-0.025em', fontWeight: '700' }],
        h2: ['clamp(1.625rem, 2vw + 1rem, 2.25rem)', { lineHeight: '1.2', letterSpacing: '-0.02em', fontWeight: '700' }],
        h3: ['1.375rem', { lineHeight: '1.3', letterSpacing: '-0.015em', fontWeight: '600' }],
        h4: ['1.125rem', { lineHeight: '1.35', fontWeight: '600' }],
        lead: ['1.125rem', { lineHeight: '1.65' }],
        small: ['0.875rem', { lineHeight: '1.5' }],
        caption: ['0.75rem', { lineHeight: '1.4', letterSpacing: '0.01em' }],
      },
      borderRadius: {
        sm: 'calc(6px * var(--radius-scale, 1))', DEFAULT: 'calc(10px * var(--radius-scale, 1))', md: 'calc(12px * var(--radius-scale, 1))',
        lg: 'calc(16px * var(--radius-scale, 1))', xl: 'calc(22px * var(--radius-scale, 1))', '2xl': 'calc(28px * var(--radius-scale, 1))', '3xl': 'calc(36px * var(--radius-scale, 1))',
      },
      boxShadow: {
        soft: '0 1px 2px rgb(var(--shadow) / 0.05), 0 4px 16px -4px rgb(var(--shadow) / 0.08)',
        lift: '0 2px 4px rgb(var(--shadow) / 0.05), 0 16px 40px -12px rgb(var(--shadow) / 0.22)',
        float: '0 24px 60px -20px rgb(var(--shadow) / 0.35)',
        glow: '0 8px 28px -6px rgb(var(--primary-500) / 0.45)',
        'glow-lg': '0 14px 44px -8px rgb(var(--primary-500) / 0.55)',
      },
      transitionTimingFunction: {
        out: 'cubic-bezier(0.16, 1, 0.3, 1)', spring: 'cubic-bezier(0.34, 1.56, 0.64, 1)',
        'in-out-soft': 'cubic-bezier(0.65, 0, 0.35, 1)', smooth: 'cubic-bezier(0.25, 0.1, 0.25, 1)',
      },
      transitionDuration: { 250: '250ms', 400: '400ms', 600: '600ms', 700: '700ms' },
      keyframes: {
        'fade-up': { from: { opacity: '0', transform: 'translateY(16px)' }, to: { opacity: '1', transform: 'translateY(0)' } },
        'fade-in': { from: { opacity: '0' }, to: { opacity: '1' } },
        'scale-in': { from: { opacity: '0', transform: 'scale(.94)' }, to: { opacity: '1', transform: 'scale(1)' } },
        'pop': { '0%': { transform: 'scale(1)' }, '40%': { transform: 'scale(1.25)' }, '100%': { transform: 'scale(1)' } },
        'shimmer': { '100%': { transform: 'translateX(100%)' } },
        'float-slow': { '0%,100%': { transform: 'translate3d(0,0,0)' }, '50%': { transform: 'translate3d(20px,-30px,0)' } },
        'pulse-ring': { '0%': { boxShadow: '0 0 0 0 rgb(var(--primary-500) / .45)' }, '100%': { boxShadow: '0 0 0 14px rgb(var(--primary-500) / 0)' } },
        'ken-burns': { from: { transform: 'scale(1.02) translate3d(0,0,0)' }, to: { transform: 'scale(1.14) translate3d(-1.5%,-1%,0)' } },
        'slide-up': { from: { transform: 'translateY(100%)' }, to: { transform: 'translateY(0)' } },
      },
      animation: {
        'fade-up': 'fade-up .7s cubic-bezier(.16,1,.3,1) both', 'fade-in': 'fade-in .4s ease both', 'scale-in': 'scale-in .35s cubic-bezier(.16,1,.3,1) both',
        pop: 'pop .45s cubic-bezier(.34,1.56,.64,1)', 'float-slow': 'float-slow 18s ease-in-out infinite', 'pulse-ring': 'pulse-ring 1.8s ease-out infinite',
        'ken-burns': 'ken-burns 14s ease-out forwards', 'slide-up': 'slide-up .35s cubic-bezier(.16,1,.3,1) both',
      },
    },
  },
  plugins: [],
};
