/** Prototype build (dev/prototype/build.sh). Same token names as app/tailwind.config.js. */
const token = (name) => `rgb(var(--jp-${name}) / <alpha-value>)`;
const tone = (name) => ({ DEFAULT: token(name), soft: token(`${name}-soft`), text: token(`${name}-text`) });

module.exports = {
  content: {
    relative: true,
    files: ['./views/**/*.php', './Chart.php', './public/charts.js', '../../app/src/Support/Ui.php', '../../app/src/View.php'],
  },
  darkMode: ['selector', '[data-theme="dark"]'],
  theme: {
    extend: {
      colors: {
        canvas: token('canvas'),
        surface: { DEFAULT: token('surface'), 2: token('surface-2'), 3: token('surface-3') },
        line: { DEFAULT: token('line'), strong: token('line-strong') },
        fg: token('fg'),
        muted: token('muted'),
        subtle: token('subtle'),
        accent: { DEFAULT: token('accent'), hover: token('accent-hover'), fg: token('accent-fg'), soft: token('accent-soft'), text: token('accent-text') },
        success: tone('success'),
        warning: tone('warning'),
        danger: tone('danger'),
        info: tone('info'),
      },
      fontFamily: {
        sans: ['"IBM Plex Sans"', 'ui-sans-serif', 'system-ui', '-apple-system', '"Segoe UI"', 'sans-serif'],
        mono: ['"IBM Plex Mono"', 'ui-monospace', 'SFMono-Regular', 'Menlo', 'monospace'],
      },
      boxShadow: { pop: 'var(--jp-shadow-pop)' },
    },
  },
  plugins: [],
};
