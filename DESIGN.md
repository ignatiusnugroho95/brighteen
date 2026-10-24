---
name: Electric Editorial Glass
colors:
  surface: '#0f131d'
  surface-dim: '#0f131d'
  surface-bright: '#353944'
  surface-container-lowest: '#0a0e18'
  surface-container-low: '#171b26'
  surface-container: '#1c1f2a'
  surface-container-high: '#262a35'
  surface-container-highest: '#313540'
  on-surface: '#dfe2f1'
  on-surface-variant: '#c3c6d7'
  inverse-surface: '#dfe2f1'
  inverse-on-surface: '#2c303b'
  outline: '#8d90a0'
  outline-variant: '#434655'
  surface-tint: '#b4c5ff'
  primary: '#b4c5ff'
  on-primary: '#002a78'
  primary-container: '#2563eb'
  on-primary-container: '#eeefff'
  inverse-primary: '#0053db'
  secondary: '#4edea3'
  on-secondary: '#003824'
  secondary-container: '#00a572'
  on-secondary-container: '#00311f'
  tertiary: '#d2bbff'
  on-tertiary: '#3f008e'
  tertiary-container: '#8343f4'
  on-tertiary-container: '#f7edff'
  error: '#ffb4ab'
  on-error: '#690005'
  error-container: '#93000a'
  on-error-container: '#ffdad6'
  primary-fixed: '#dbe1ff'
  primary-fixed-dim: '#b4c5ff'
  on-primary-fixed: '#00174b'
  on-primary-fixed-variant: '#003ea8'
  secondary-fixed: '#6ffbbe'
  secondary-fixed-dim: '#4edea3'
  on-secondary-fixed: '#002113'
  on-secondary-fixed-variant: '#005236'
  tertiary-fixed: '#eaddff'
  tertiary-fixed-dim: '#d2bbff'
  on-tertiary-fixed: '#25005a'
  on-tertiary-fixed-variant: '#5a00c6'
  background: '#0f131d'
  on-background: '#dfe2f1'
  surface-variant: '#313540'
typography:
  display-hero:
    fontFamily: Plus Jakarta Sans
    fontSize: 56px
    fontWeight: '800'
    lineHeight: 64px
    letterSpacing: -0.03em
  display-hero-mobile:
    fontFamily: Plus Jakarta Sans
    fontSize: 36px
    fontWeight: '800'
    lineHeight: 44px
    letterSpacing: -0.02em
  headline-lg:
    fontFamily: Plus Jakarta Sans
    fontSize: 32px
    fontWeight: '700'
    lineHeight: 40px
    letterSpacing: -0.02em
  headline-lg-mobile:
    fontFamily: Plus Jakarta Sans
    fontSize: 26px
    fontWeight: '700'
    lineHeight: 34px
    letterSpacing: -0.01em
  headline-md:
    fontFamily: Plus Jakarta Sans
    fontSize: 24px
    fontWeight: '700'
    lineHeight: 32px
    letterSpacing: -0.01em
  headline-sm:
    fontFamily: Plus Jakarta Sans
    fontSize: 20px
    fontWeight: '600'
    lineHeight: 28px
  title-editorial:
    fontFamily: Plus Jakarta Sans
    fontSize: 18px
    fontWeight: '600'
    lineHeight: 26px
  body-lg:
    fontFamily: Inter
    fontSize: 16px
    fontWeight: '400'
    lineHeight: 26px
  body-md:
    fontFamily: Inter
    fontSize: 14px
    fontWeight: '400'
    lineHeight: 22px
  body-sm:
    fontFamily: Inter
    fontSize: 12px
    fontWeight: '400'
    lineHeight: 18px
  label-caps:
    fontFamily: Plus Jakarta Sans
    fontSize: 11px
    fontWeight: '700'
    lineHeight: 16px
    letterSpacing: 0.08em
  label-interactive:
    fontFamily: Plus Jakarta Sans
    fontSize: 13px
    fontWeight: '600'
    lineHeight: 18px
  stat-metric:
    fontFamily: Plus Jakarta Sans
    fontSize: 28px
    fontWeight: '800'
    lineHeight: 32px
    letterSpacing: -0.02em
rounded:
  sm: 0.5rem
  DEFAULT: 1rem
  md: 1.5rem
  lg: 2rem
  xl: 3rem
  full: 9999px
spacing:
  gutter: 1.5rem
  gutter-mobile: 0.75rem
  margin: 2rem
  margin-mobile: 1rem
  space-xs: 0.25rem
  space-sm: 0.5rem
  space-md: 1rem
  space-lg: 1.5rem
  space-xl: 2.5rem
---

## Brand & Style

This design system powers a Gen-Z-driven digital publishing ecosystem tailored for junior high school students, student journalists, faculty editors, and school administrators. The aesthetic merges the energetic micro-interaction culture of TikTok and Instagram with high-efficiency editorial and workflow tools.

The design movement synthesizes **Translucent Glassmorphism with Hyper-Saturated Neon Accents and High-Contrast Dark Surfaces**. 

- **Audience & Mindset:** Junior high school digital natives (12–15 years old) consuming bite-sized campus culture, alongside student reporters crafting long-form pieces and teachers moderating submissions. It must never feel like an antiquated corporate LMS; it must feel like a modern, consumer-grade social publishing platform.
- **Visual Personality:** Hyper-vibrant, luminous, kinetic, yet structurally disciplined. Dark-mode glass panes give depth and focus, allowing electric neon accents to command immediate visual hierarchy.
- **Emotional Resonance:** Inspires creative pride, peer celebration, active engagement, and real-world editorial agency.

## Colors

The color architecture is built natively for dark mode, using obsidian and deep slate base layers overlaid with vibrant multi-stop gradients.

- **Primary (`#2563EB` - Electric Blue):** Represents student identity, primary calls-to-action, structural navigation focus, and system highlights.
- **Secondary (`#10B981` - Neon Mint / `#84CC16` - Lime Accent):** Denotes freshness, active published states, live story updates, positive KPI sparkline metrics, and verified reader milestones.
- **Tertiary (`#7C3AED` - Deep Violet / `#F97316` - Sunset Orange):** Expresses creative energy, story badges, editorial award tiers, gamification streaks, and warning/review tags.
- **Neutral Canvas (`#0B0F19` - Midnight Obsidian):** The foundational substrate. Layered surfaces build up through translucent alpha steps:
  - Surface Tier 0 (Canvas): `#0B0F19`
  - Surface Tier 1 (Cards & Sidebars): `rgba(17, 24, 39, 0.70)` with backdrop blur.
  - Surface Tier 2 (Elevated Sheets & Modals): `rgba(31, 41, 55, 0.80)` with backdrop blur.
  - Stroke / Rim Light: `rgba(255, 255, 255, 0.12)` providing frosted glass bevels.

### Semantic Role Mappings
- **Role Badges:**
  - *Reader View:* Electric Blue tint (`rgba(37, 99, 235, 0.15)`)
  - *Contributor Portal:* Mint tint (`rgba(16, 185, 129, 0.15)`)
  - *Journalist Portal:* Sunset Orange tint (`rgba(249, 115, 22, 0.15)`)
  - *Editor Hub:* Deep Violet tint (`rgba(124, 58, 237, 0.15)`)
  - *Management Analytics:* Titanium Silver / White glow
- **Story Ring Gradients:** `linear-gradient(45deg, #F97316, #EC4899, #7C3AED, #2563EB, #10B981)`

## Typography

The typography pairs the expressive, geometric personality of **Plus Jakarta Sans** for headlines, labels, and metrics with the functional clarity of **Inter** for long-form editorial bodies and dense data interfaces.

- **Display & Headlines:** Set tight with negative tracking (`-0.01em` to `-0.03em`) to mimic poster design and high-fashion streetwear publications.
- **Editorial Body:** Relies on generous line heights (`1.6x` font size) in Inter to ensure reading comfort on backlit mobile and desktop screens.
- **Labels & Micro-data:** `label-caps` enforces uppercase styling with expanded letter-spacing (`+0.08em`) to act as category signposts (e.g., "CAMPUS BEAT", "STUDENT SPOTLIGHT", "SPORTS REPORT").

## Layout & Spacing

The layout model is anchored on a **12-column fluid grid** for desktop and tablet viewports, transitioning to a single dynamic stream on mobile viewports.

- **Desktop (1280px+):** 12 columns, 24px gutters, 32px safe margins. Shell features a persistent floating top navigation bar and contextual side docks for editorial analytics and workflow tools. Max content container is `1440px`.
- **Tablet (768px - 1279px):** 8 columns, 16px gutters, 24px outer margin. Side drawers collapse into overlay slide-ins. Editorial 2x2 grids reflow to 2x1 stacks.
- **Mobile (< 768px):** 4 columns (or single stack), 12px gutters, 16px outer margin. Horizontal swipe trays handle stories, role portals, and trending tags.
- **Rhythm:** Spacing follows a strict 4px/8px baseline matrix. Social engagement clusters (avatar, handle, timestamp) maintain `space-xs` to `space-sm` compactness, while editorial hero layouts use `space-xl` for breathing room.

## Elevation & Depth

Visual depth is achieved through **glassmorphic translucency, dual inner-rim highlights, and neon ambient diffusion** rather than muddy black drop-shadows.

- **Level 0 (Base Floor):** Deep pitch-black canvas `#0B0F19` with subtle multi-color radial gradient glows (`blur(120px)`) positioned under key content modules.
- **Level 1 (Panels & Feed Cards):** Background `rgba(17, 24, 39, 0.65)` layered with `backdrop-filter: blur(16px)` and a 1px border stroke `rgba(255, 255, 255, 0.08)`.
- **Level 2 (Hover States & Story Overlays):** Background `rgba(31, 41, 55, 0.75)` with `backdrop-filter: blur(24px)`, top rim border `rgba(255, 255, 255, 0.18)`, and colored ambient glow:
  - *Electric Blue Cards:* `0 8px 32px -4px rgba(37, 99, 235, 0.25)`
  - *Neon Mint Cards:* `0 8px 32px -4px rgba(16, 185, 129, 0.25)`
  - *Deep Violet Cards:* `0 8px 32px -4px rgba(124, 58, 237, 0.25)`
- **Level 3 (Floating Menus, Modals, Top Nav Switcher):** `rgba(15, 23, 42, 0.85)` with `backdrop-filter: blur(32px)`, border `rgba(255, 255, 255, 0.15)`, and a compound shadow: `0 20px 40px rgba(0, 0, 0, 0.6)`, accompanied by a top neon inset glow line (`height: 1px`).

## Shapes

The shape system adopts a **pill-shaped geometry (Level 3)** that echoes modern mobile operating systems and social feeds.

- **Badges, Tags, Buttons, Switchers:** Fully rounded pill silhouettes (`border-radius: 9999px`) provide an energetic, tactile, sticker-like quality.
- **Content Cards & Panels:** `rounded-xl` (`24px` to `32px` on desktop, `20px` on mobile), generating soft window frames for photography and rich text.
- **Micro Avatars & Story Highlights:** Strict 1:1 circular shapes (`border-radius: 50%`) enveloped in active gradient borders.
- **Inner Nested Elements:** Strictly adhere to the concentric radius formula: $R_{inner} = R_{outer} - Padding$ to prevent visual disharmony inside glass containers.

## Components

### 1. Multi-Role Top Navigation Switcher
- **Structure:** Pill-shaped glass container docked floating at the top center.
- **Interactions:** Segmented control toggling between 5 views: *Reader View*, *Contributor Portal*, *Journalist Portal*, *Editor Hub*, and *Management Analytics*.
- **Active State:** A moving neon pill backdrop beneath the active tab with high-intensity text contrast and a 1px soft color fringe.

### 2. Instagram-Style Story Circles
- **Ring Anatomy:** Outer diameter `68px` (desktop) / `58px` (mobile). Story ring consists of a 2px offset border with a vibrant rotating gradient (`#F97316` $\to$ `#EC4899` $\to$ `#7C3AED` $\to$ `#10B981`).
- **Unviewed State:** High saturation gradient ring with subtle pulsing glow.
- **Viewed State:** Reduced opacity slate ring (`rgba(255, 255, 255, 0.2)`).
- **Badge Indicator:** Floating `+` or role pill docked at the 4:30 clock position on the circle.

### 3. Social Media Feed Cards
- **Header:** Poster avatar, author handle, role badge pill, and timestamp.
- **Media Aspect:** 4:5 vertical media frame with `rounded-lg` corners and auto-play indicator.
- **Engagement Bar:** Horizontal dock featuring heart (like), chat bubble (comment), paper airplane (share), and bookmark buttons. Icon fills transition immediately to neon red/pink/mint with haptic spring scale transforms on click.

### 4. Editorial 2x2 Cards
- **Grid Layout:** Asymmetrical 2x2 grid displaying lead cover story (spanning 2 rows on desktop) paired with secondary stories.
- **Image Treatment:** Dark glass tint overlay gradient from bottom (`rgba(11, 15, 25, 0.95)`) to top transparent, keeping typography readable.

### 5. Rich-Text WYSIWYG Editor Surfaces
- **Canvas:** Distraction-free elevated dark glass sheet (`rgba(17, 24, 39, 0.5)`).
- **Floating Toolbar:** Pill-shaped glass bar featuring formatting controls (H1/H2/H3, quotes, embeds, image uploads) that stays pinned contextually above the active cursor.

### 6. Kanban Moderation Task Cards
- **Columns:** *Drafts*, *Pending Review*, *Editor Approved*, *Published*.
- **Cards:** Compact glass tiles containing priority dot, word count, assigned student journalist avatar, and a quick-action hover tray ("Approve", "Request Edit").

### 7. KPI Metric Widgets & Charts
- **Stat Cards:** Compact cards housing large `stat-metric` numbers, micro percentage delta pill (e.g., `+24.8%`), and an embedded SVG sparkline with a neon gradient area fill.
- **Interactive Charts:** Frosted tooltips that trigger on hover with glowing data markers.

### 8. Gamification Recognition Badges
- **Form:** 3D-inspired glass hex or shield tokens adorned with custom neon icons (e.g., "Top Storyteller", "Headline Hero", "100k Reads Club"). Glow borders match the earned badge tier (Bronze, Silver, Electric Gold, Neon Diamond).