import { defineConfig } from "vite";
import react from "@vitejs/plugin-react";
import tailwindcss from "@tailwindcss/vite";

export default defineConfig({
  // Sirve los assets bajo /landing/ porque Laravel entrega la landing compilada desde public/landing/.
  base: "/landing/",
  plugins: [react(), tailwindcss()],
  // La app Laravel de la raíz tiene su propio PostCSS/Tailwind 3; se aísla
  // la landing para que Vite no herede esa configuración y use solo su plugin.
  css: { postcss: {} },
});
