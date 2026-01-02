import vue from '@vitejs/plugin-vue'
import vueJsx from '@vitejs/plugin-vue-jsx'
import AutoImport from 'unplugin-auto-import/vite'
import Components from 'unplugin-vue-components/vite'
import DefineOptions from 'unplugin-vue-define-options/vite'
import { fileURLToPath } from 'url'
import { defineConfig } from 'vite'
import Pages from 'vite-plugin-pages'
import Layouts from 'vite-plugin-vue-layouts'
import vuetify from 'vite-plugin-vuetify'
import pluginSvgVue from '@vuetter/vite-plugin-vue-svg';

// https://vitejs.dev/config/
export default defineConfig({
  css: {
    preprocessorOptions: {
      scss: {
        quietDeps: true,
        silenceDeprecations: ['global-builtin', 'legacy-js-api'],
      },
      sass: {
        quietDeps: true,
        silenceDeprecations: ['global-builtin', 'legacy-js-api'],
      },
    },
  },
  plugins: [
    vue(),
    pluginSvgVue(),
    vueJsx(),

    // https://github.com/vuetifyjs/vuetify-loader/tree/next/packages/vite-plugin
    vuetify({
      styles: {
        configFile: 'src/styles/variables/_vuetify.scss',
      },
    }),
    Pages({
      dirs: ['./src/pages'],
    }),
    Layouts({
      layoutsDirs: './src/layouts/',
    }),
    Components({
      dirs: ['src/@core/components', 'src/views/demos'],
      dts: true,
    }),
    AutoImport({
      eslintrc: {
        enabled: true,
        filepath: './.eslintrc-auto-import.json',
      },
      imports: ['vue', 'vue-router', '@vueuse/core', '@vueuse/math', 'pinia'],
      vueTemplate: true,
    }),
    DefineOptions(),
  ],
  define: {
    'process.env': {},
    __VUE_OPTIONS_API__: true,
    __VUE_PROD_DEVTOOLS__: false,
    __VUE_PROD_HYDRATION_MISMATCH_DETAILS__: false,
  },
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
      '@themeConfig': fileURLToPath(new URL('./themeConfig.js', import.meta.url)),
      '@core': fileURLToPath(new URL('./src/@core', import.meta.url)),
      '@layouts': fileURLToPath(new URL('./src/@layouts', import.meta.url)),
      '@images': fileURLToPath(new URL('./src/assets/images/', import.meta.url)),
      '@styles': fileURLToPath(new URL('./src/styles/', import.meta.url)),
      '@configured-variables': fileURLToPath(new URL('./src/styles/variables/_template.scss', import.meta.url)),
      '@axios': fileURLToPath(new URL('./src/plugins/axios', import.meta.url)),
      '@validators': fileURLToPath(new URL('./src/@core/utils/validators', import.meta.url)),
      'apexcharts': fileURLToPath(new URL('node_modules/apexcharts-clevision', import.meta.url)),
    },
  },
  build: {
    chunkSizeWarningLimit: 5000,
    rollupOptions: {
      onwarn(warning, warn) {
        // Suppress baseline-browser-mapping warning
        if (warning.message && warning.message.includes('baseline-browser-mapping')) {
          return;
        }
        // Suppress CSS syntax warnings from dependencies
        if (warning.code === 'SOURCEMAP_ERROR') {
          return;
        }
        warn(warning);
      },
    },
  },
  esbuild: {
    // Suppress CSS syntax warnings during minification
    logOverride: {
      'css-syntax-error': 'silent',
    },
  },
  optimizeDeps: {
    exclude: ['vuetify'],
    entries: [
      './src/**/*.vue',
    ],
  },
})
