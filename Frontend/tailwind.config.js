
/** @type {import('tailwindcss').Config} */
export default {
  content: [
  './index.html',
  './src/**/*.{js,ts,jsx,tsx}'
],
  theme: {
    extend: {
      fontFamily: {
        serif: ['"Playfair Display"', 'serif'],
        sans: ['Inter', 'sans-serif'],
      },
      colors: {
        gastro: {
          bg: '#FDFBF7', // Cream/off-white
          surface: '#FFFFFF',
          text: '#2D2422', // Dark brown/charcoal
          accent: '#C67A3F', // Terracotta/amber
          accentHover: '#B36A32',
          border: '#EAE3D9',
          muted: '#8C7E7A'
        }
      },
      boxShadow: {
        'soft': '0 4px 20px -2px rgba(45, 36, 34, 0.05)',
      }
    },
  },
  plugins: [],
}
