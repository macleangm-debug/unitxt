import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            colors: {
                // Loop Energy — violet + electric lime
                ink: {
                    DEFAULT: '#111114',
                    soft: '#1C1C22',
                    muted: '#6B6B76',
                },
                violet: {
                    DEFAULT: '#5B2EFF',
                    deep: '#3F18D9',
                    soft: '#E9E5FF',
                },
                lime: {
                    DEFAULT: '#C8FF3D',
                    deep: '#1B5E20',
                    soft: '#E8F5E9',
                },
                chalk: {
                    DEFAULT: '#F7F7F4',
                    warm: '#F0F0EC',
                },
                // Legacy aliases — deep green for readable text on light surfaces
                mint: {
                    DEFAULT: '#2E7D32',
                    deep: '#1B5E20',
                    soft: '#E8F5E9',
                },
                coral: '#FF4F70',
            },
            fontFamily: {
                display: ['Sora', ...defaultTheme.fontFamily.sans],
                sans: ['DM Sans', ...defaultTheme.fontFamily.sans],
            },
            keyframes: {
                'fade-up': {
                    '0%': { opacity: '0', transform: 'translateY(18px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                'loop-spin': {
                    '0%': { transform: 'rotate(0deg)' },
                    '100%': { transform: 'rotate(360deg)' },
                },
                float: {
                    '0%, 100%': { transform: 'translateY(0)' },
                    '50%': { transform: 'translateY(-10px)' },
                },
            },
            animation: {
                'fade-up': 'fade-up 0.7s ease-out both',
                'fade-up-delay': 'fade-up 0.7s ease-out 0.15s both',
                'fade-up-delay-2': 'fade-up 0.7s ease-out 0.3s both',
                'loop-spin': 'loop-spin 18s linear infinite',
                float: 'float 5s ease-in-out infinite',
            },
        },
    },

    plugins: [forms],
};
