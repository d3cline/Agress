/** @type {import('tailwindcss').Config} */
module.exports = {
  content: ["./public/*.html", "./public/scripts/**/*.js"],
  theme: {
    extend: {},
  },
  plugins: [require('daisyui')],
  daisyui: {
    themes: ["light", "dark"],
  },
}
