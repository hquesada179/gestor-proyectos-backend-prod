import { defineConfig } from "vite";
import react from "@vitejs/plugin-react";
import tailwindcss from "@tailwindcss/vite";

export default defineConfig({
  plugins: [react(), tailwindcss()],
  // La app Laravel de la raíz tiene su propio PostCSS/Tailwind 3; se aísla
  // la landing para que Vite no herede esa configuración y use solo su plugin.
  css: { postcss: {} },
});
