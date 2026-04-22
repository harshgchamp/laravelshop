// eslint.config.js — ESLint flat config (ESLint 9+)
// Covers: JavaScript files and Vue 3 Single File Components (.vue)
// TypeScript syntax inside <script lang="ts"> is handled by @typescript-eslint/parser.

import js from '@eslint/js';
import pluginVue from 'eslint-plugin-vue';
import globals from 'globals';
import prettier from 'eslint-config-prettier';
import prettierPlugin from 'eslint-plugin-prettier';
import tsParser from '@typescript-eslint/parser';

export default [
    // ── 1. ESLint recommended JS rules ────────────────────────────────────────
    js.configs.recommended,

    // ── 2. Vue 3 essential rules ──────────────────────────────────────────────
    // flat/recommended: Vue 3 rules (essential + strongly-recommended + recommended)
    // In eslint-plugin-vue v10+, vue3 configs are under 'flat/recommended' (no 'vue3-' prefix)
    ...pluginVue.configs['flat/recommended'],

    // ── 3. TypeScript parser for Vue <script lang="ts"> blocks ────────────────
    // vue-eslint-parser handles the outer .vue file structure (template, script, style).
    // For <script lang="ts">, it delegates to the parser specified in parserOptions.parser.
    // Without this, TypeScript generics (defineProps<{...}>()) cause parsing errors.
    {
        files: ['**/*.vue'],
        languageOptions: {
            parserOptions: {
                parser: tsParser,
            },
        },
    },

    // ── 4. Project-wide config ────────────────────────────────────────────────
    {
        plugins: {
            prettier: prettierPlugin,
        },

        languageOptions: {
            globals: {
                // Browser globals: window, document, console, fetch, etc.
                ...globals.browser,
                // Node globals: __dirname, process — needed in vite.config.js
                ...globals.node,
                // Ziggy's route() helper is injected globally by ZiggyVue
                route: 'readonly',
            },
            parserOptions: {
                ecmaVersion: 'latest',
                sourceType: 'module',
            },
        },

        rules: {
            // ── Prettier formatting (runs Prettier as an ESLint rule) ─────────
            'prettier/prettier': 'warn',

            // ── Vue-specific ──────────────────────────────────────────────────
            // Allow single-word component names (e.g. <Toast />, <Column />)
            'vue/multi-word-component-names': 'off',

            // Allow <script setup> without explicit component name
            'vue/component-definition-name-casing': 'off',

            // Attributes on multiple lines — handled by Prettier instead
            'vue/max-attributes-per-line': 'off',
            'vue/html-self-closing': [
                'warn',
                {
                    html: { void: 'always', normal: 'always', component: 'always' },
                    svg: 'always',
                    math: 'always',
                },
            ],

            // Allow v-html (used in ProductDetail for rich-text description)
            'vue/no-v-html': 'off',

            // Props must have default values — off, Inertia props are always provided
            'vue/require-default-prop': 'off',

            // ── General JS ───────────────────────────────────────────────────
            // Warn on console.log left in code (use proper logging)
            'no-console': ['warn', { allow: ['warn', 'error'] }],

            // Allow unused variables that start with _ (convention for intentional ignores)
            'no-unused-vars': ['warn', { argsIgnorePattern: '^_', varsIgnorePattern: '^_' }],

            // Prefer const for variables that are never reassigned
            'prefer-const': 'warn',

            // Require === instead of ==
            eqeqeq: ['error', 'always'],
        },
    },

    // ── 5. Turn off ESLint formatting rules that conflict with Prettier ────────
    // eslint-config-prettier disables all rules that Prettier handles
    prettier,

    // ── 6. Files to ignore ────────────────────────────────────────────────────
    {
        ignores: [
            'node_modules/**',
            'public/build/**',
            'public/hot',
            'vendor/**',
            'storage/**',
            'bootstrap/cache/**',
        ],
    },
];
