/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
        './app/**/*.php',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', 'ui-sans-serif', 'system-ui'],
                mono: ['JetBrains Mono', 'ui-monospace', 'monospace'],
            },
            colors: {
                ink: {
                    50: '#f7f8fa',
                    100: '#eef0f4',
                    200: '#dee2ea',
                    300: '#c2c8d4',
                    400: '#8a92a3',
                    500: '#5b6271',
                    600: '#3e4553',
                    700: '#2a2f3a',
                    800: '#1c2028',
                    900: '#10131a',
                },
                brand: {
                    50: '#ecf7f8',
                    100: '#cfeaec',
                    200: '#9ed4d9',
                    300: '#6abac1',
                    400: '#3a9ca5',
                    500: '#177f8a',
                    600: '#0f6670',
                    700: '#0b4e56',
                    800: '#083a40',
                    900: '#052a2f',
                },
                amber: {
                    50: '#fef3e8',
                    100: '#fce8d0',
                    500: '#d18b1f',
                    600: '#a6700f',
                },
            },
            boxShadow: {
                card: '0 1px 0 rgba(16,19,26,0.04), 0 1px 2px rgba(16,19,26,0.05)',
                pop: '0 10px 30px rgba(16,19,26,0.10), 0 2px 6px rgba(16,19,26,0.06)',
            },
        },
    },
    plugins: [],
};
