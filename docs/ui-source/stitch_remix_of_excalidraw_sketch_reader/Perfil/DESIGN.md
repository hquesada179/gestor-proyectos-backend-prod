---
name: Efficiency AI
colors:
  surface: '#051424'
  surface-dim: '#051424'
  surface-bright: '#2c3a4c'
  surface-container-lowest: '#010f1f'
  surface-container-low: '#0d1c2d'
  surface-container: '#122131'
  surface-container-high: '#1c2b3c'
  surface-container-highest: '#273647'
  on-surface: '#d4e4fa'
  on-surface-variant: '#c4c6cd'
  inverse-surface: '#d4e4fa'
  inverse-on-surface: '#233143'
  outline: '#8e9197'
  outline-variant: '#44474c'
  surface-tint: '#c5c6ce'
  primary: '#c5c6ce'
  on-primary: '#2e3037'
  primary-container: '#111319'
  on-primary-container: '#7c7e85'
  inverse-primary: '#5c5e65'
  secondary: '#c3c0ff'
  on-secondary: '#1d00a5'
  secondary-container: '#3422cc'
  on-secondary-container: '#afadff'
  tertiary: '#ddb8ff'
  on-tertiary: '#490080'
  tertiary-container: '#20003e'
  on-tertiary-container: '#a84dff'
  error: '#ffb4ab'
  on-error: '#690005'
  error-container: '#93000a'
  on-error-container: '#ffdad6'
  primary-fixed: '#e1e2ea'
  primary-fixed-dim: '#c5c6ce'
  on-primary-fixed: '#191c21'
  on-primary-fixed-variant: '#44474d'
  secondary-fixed: '#e2dfff'
  secondary-fixed-dim: '#c3c0ff'
  on-secondary-fixed: '#0f0069'
  on-secondary-fixed-variant: '#3422cc'
  tertiary-fixed: '#f0dbff'
  tertiary-fixed-dim: '#ddb8ff'
  on-tertiary-fixed: '#2c0051'
  on-tertiary-fixed-variant: '#6800b3'
  background: '#051424'
  on-background: '#d4e4fa'
  surface-variant: '#273647'
typography:
  display-lg:
    fontFamily: Inter
    fontSize: 48px
    fontWeight: '700'
    lineHeight: '1.1'
    letterSpacing: -0.02em
  headline-md:
    fontFamily: Inter
    fontSize: 24px
    fontWeight: '600'
    lineHeight: '1.3'
    letterSpacing: -0.01em
  body-base:
    fontFamily: Inter
    fontSize: 16px
    fontWeight: '400'
    lineHeight: '1.6'
    letterSpacing: 0em
  body-sm:
    fontFamily: Inter
    fontSize: 14px
    fontWeight: '400'
    lineHeight: '1.5'
    letterSpacing: 0em
  label-caps:
    fontFamily: Inter
    fontSize: 12px
    fontWeight: '600'
    lineHeight: '1'
    letterSpacing: 0.05em
  micro-tag:
    fontFamily: Inter
    fontSize: 10px
    fontWeight: '500'
    lineHeight: '1'
    letterSpacing: 0em
rounded:
  sm: 0.25rem
  DEFAULT: 0.5rem
  md: 0.75rem
  lg: 1rem
  xl: 1.5rem
  full: 9999px
spacing:
  unit: 8px
  margin: 32px
  gutter: 24px
  container-max: 1440px
  stack-gap: 16px
  message-gap: 24px
---

## Brand & Style

Efficiency AI embodies a "Futuristic Corporate" aesthetic, blending the reliability of a high-end SaaS platform with the cutting-edge energy of artificial intelligence. The brand is designed for professionals who need high-velocity data processing without sacrificing clarity.

The visual style is a refined **Glassmorphism**, characterized by deep nocturnal surfaces, translucent frosted layers, and vibrant, luminous accents that suggest a "living" intelligence. It uses high-performance visuals—like backdrop blurs and subtle glows (AI-glow)—to create a sense of depth and technical sophistication. The emotional response should be one of focused calm, precision, and empowered decision-making.

## Colors

The palette is rooted in a deep "Midnight Navy" (#051424), providing a high-contrast foundation for data visualization. 

- **Primary & Neutrals:** Uses cool grays and off-whites to maintain a professional, utilitarian feel for text and secondary actions.
- **AI Signature (Tertiary):** A vibrant violet/purple is reserved for AI-driven elements, used in glows and container backgrounds to distinguish machine intelligence from human input.
- **Action (Secondary):** A deep electric indigo drives the user's primary path, appearing in gradients and active states.
- **Functional Colors:** Success and warning states use standard semantic mapping but are tempered with transparency to fit the dark aesthetic.

## Typography

The system relies exclusively on **Inter** to project a systematic, neutral, and utilitarian personality. 

Hierarchy is established through weight and case rather than dramatic size shifts. **Label-caps** are used for metadata and category headers to provide a structural "frame" to the data. **Body-sm** is the workhorse for chat interfaces and descriptive text, ensuring high information density while maintaining readability. Tight letter spacing on larger headlines gives the UI a modern, "shrunk-wrapped" tech feel.

## Layout & Spacing

The layout utilizes a **Fixed Grid** model for desktop (max-width 1440px) while transitioning to a fluid, high-margin model for mobile devices. 

A strict 8px base unit (the "unit") governs all padding and margins. Vertical rhythm is driven by a 24px (3 units) gap between major sections like chat messages and metric cards. The "Main Canvas" features a generous top-padding (80px) to clear the fixed navigation and a significant bottom-padding (160px) to ensure content is never obscured by the floating command bar.

## Elevation & Depth

Depth is not achieved through traditional drop shadows, but through **Tonal Stacked Layers** and **Backdrop Blurs**:

1.  **Base Layer:** The solid #051424 background.
2.  **Surface Layer:** Glass panels with 20px blur and 10% white borders (used for cards and navigation).
3.  **Active AI Layer:** Elements featuring the "AI-glow"—a soft, 20px diffused shadow using `rgba(167, 75, 254, 0.15)` and a semi-transparent purple border.
4.  **Interaction Layer:** Hover states use a 5% white overlay rather than a color shift, maintaining the translucency of the system.

## Shapes

The shape language is "Hyper-Rounded." Most containers use a 1rem (16px) radius to soften the technical nature of the data. 

Special exceptions are made for chat bubbles: they use 2xl (24px) rounding on three corners with a sharp 4px radius on the "origin" corner (top-left for AI, top-right for user) to indicate the speaker. Icons and small action buttons use a 12px (xl) radius to maintain a consistent "squircle" appearance across the UI.

## Components

- **Chat Bubbles:** AI bubbles use a semi-transparent gray-blue surface (`message-ai`); User bubbles use a vibrant Indigo-to-Black gradient. Both feature 16px padding.
- **Glass Cards:** Used for metrics and project health. Must include `backdrop-filter: blur(20px)` and a 1px `border: white/10`.
- **Command Bar:** A floating element using the highest elevation. It features a nested input field with no background and a prominent secondary-container colored "send" button.
- **Action Buttons:** 
    - *Primary:* Solid Indigo or high-contrast subtle tint (Primary/10) with matching border.
    - *Ghost:* Transparent with `outline-variant` borders and `on-surface-variant` text.
- **Data Visualization:** Bars use semi-transparent fills with 2px "Top-borders" in a brighter tint of the same color to simulate glowing light.
- **Bottom Navigation:** A high-blur (2xl) panel with persistent active states using a tinted background block (blue-500/10) and matching icon color.