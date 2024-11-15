#!/bin/bash

# Exit immediately if a command exits with a non-zero status
set -e

echo "Initializing project..."

# Initialize npm and install dependencies
npm init -y

echo "Installing Tailwind CSS and Daisy UI..."
npm install tailwindcss@latest postcss@latest autoprefixer@latest daisyui@latest

echo "Creating Tailwind configuration..."
npx tailwindcss init -p

echo "Configuring Tailwind CSS..."
# Overwrite tailwind.config.js with correct content
cat > tailwind.config.js <<EOL
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
EOL

echo "Setting up input CSS..."
mkdir -p src
cat > src/input.css <<EOL
@tailwind base;
@tailwind components;
@tailwind utilities;
EOL

echo "Building CSS..."
npx tailwindcss -i ./src/input.css -o ./public/dist/output.css --minify

echo "Build complete!"
