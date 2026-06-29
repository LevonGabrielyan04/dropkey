---
name: design-landing-page
description: Create high-converting, visually distinct landing pages and marketing sites. Use when users request a promotional website, product page, or portfolio.
---

# Landing Page Design Skill

## 1. Context & Strategy
Before writing any code, analyze the following:
- **Target Audience:** Who is this for?
- **Brand Personality:** Is it minimal, bold, technical, or playful?
- **The "One Thing":** What is the single most important action the user should take?

## 2. Technical Stack
- Use Laravel's Blade templates for component structure.
- Use Tailwind CSS for utility-first styling.

## 3. The 11 Essential Conversion Elements
Every landing page must include these functional sections:
1. **Hero Section:** Massive typography, SEO-optimized title/subtitle, and a prominent primary Call to Action (CTA).
2. **Social Proof:** Logos of trusted companies or user statistics immediately below the fold.
3. **Product Visuals:** High-quality images, interactive demos, or styled mockups (no generic placeholders).
4. **Features/Benefits:** 3-6 items with custom icons and an asymmetrical or grid-breaking layout.
5. **Testimonials:** 4-6 styled cards highlighting customer success.
6. **FAQ:** Smooth accordion layout for 5-10 common questions.
7. **Final CTA:** Dramatic, full-width section at the bottom of the page.
8. **Footer:** Clean multi-column layout with legal and contact links.

## 4. Visual Coherence & Design Excellence
- **Layout:** Generous whitespace, brutally clean alignment. Avoid boring rectangles—use pill shapes, subtle borders, or soft shadows.
- **Color:** Define a Dominant (60%), Neutral (30%), and Accent (10%) palette using CSS variables.
- **Motion:**
    - Hero text should fade in sequentially.
    - CTAs require micro-interactions (hover scale, shadow expansion).
    - Use sticky headers with backdrop blur on scroll.

## 5. Execution Rules
- Never use `Lorem Ipsum`. Always write relevant, persuasive placeholder copy.
- Ensure all color contrasts pass accessibility checks.
- Test responsiveness across mobile, tablet, and desktop viewports.
