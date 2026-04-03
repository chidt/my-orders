---
name: tailwindcss-development
description: >-
  Styles applications using Tailwind CSS v4 utilities. Activates when adding styles, restyling components,
  working with gradients, spacing, layout, flex, grid, responsive design, dark mode, colors,
  typography, or borders; or when the user mentions CSS, styling, classes, Tailwind, restyle,
  hero section, cards, buttons, or any visual/UI changes.
---

# Tailwind CSS Development

## When to Apply

Activate this skill when:

- Adding styles to components or pages
- Working with responsive design
- Implementing dark mode
- Extracting repeated patterns into components
- Debugging spacing or layout issues

## Documentation

Use `search-docs` for detailed Tailwind CSS v4 patterns and documentation.

## Basic Usage

- Use Tailwind CSS classes to style HTML. Check and follow existing Tailwind conventions in the project before introducing new patterns.
- Offer to extract repeated patterns into components that match the project's conventions (e.g., Blade, JSX, Vue).
- Consider class placement, order, priority, and defaults. Remove redundant classes, add classes to parent or child elements carefully to reduce repetition, and group elements logically.

## Tailwind CSS v4 Specifics

- Always use Tailwind CSS v4 and avoid deprecated utilities.
- `corePlugins` is not supported in Tailwind v4.

### CSS-First Configuration

In Tailwind v4, configuration is CSS-first using the `@theme` directive — no separate `tailwind.config.js` file is needed:

<code-snippet name="CSS-First Config" lang="css">
@theme {
  --color-brand: oklch(0.72 0.11 178);
}
</code-snippet>

### Import Syntax

In Tailwind v4, import Tailwind with a regular CSS `@import` statement instead of the `@tailwind` directives used in v3:

<code-snippet name="v4 Import Syntax" lang="diff">
- @tailwind base;
- @tailwind components;
- @tailwind utilities;
+ @import "tailwindcss";
</code-snippet>

### Replaced Utilities

Tailwind v4 removed deprecated utilities. Use the replacements shown below. Opacity values remain numeric.

| Deprecated | Replacement |
|------------|-------------|
| bg-opacity-* | bg-black/* |
| text-opacity-* | text-black/* |
| border-opacity-* | border-black/* |
| divide-opacity-* | divide-black/* |
| ring-opacity-* | ring-black/* |
| placeholder-opacity-* | placeholder-black/* |
| flex-shrink-* | shrink-* |
| flex-grow-* | grow-* |
| overflow-ellipsis | text-ellipsis |
| decoration-slice | box-decoration-slice |
| decoration-clone | box-decoration-clone |

## Spacing

Use `gap` utilities instead of margins for spacing between siblings:

<code-snippet name="Gap Utilities" lang="html">
<div class="flex gap-8">
    <div>Item 1</div>
    <div>Item 2</div>
</div>
</code-snippet>

## Dark Mode

If existing pages and components support dark mode, new pages and components must support it the same way, typically using the `dark:` variant:

<code-snippet name="Dark Mode" lang="html">
<div class="bg-white dark:bg-gray-900 text-gray-900 dark:text-white">
    Content adapts to color scheme
</div>
</code-snippet>

## Common Patterns

### Flexbox Layout

<code-snippet name="Flexbox Layout" lang="html">
<div class="flex items-center justify-between gap-4">
    <div>Left content</div>
    <div>Right content</div>
</div>
</code-snippet>

### Grid Layout

<code-snippet name="Grid Layout" lang="html">
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <div>Card 1</div>
    <div>Card 2</div>
    <div>Card 3</div>
</div>
</code-snippet>

## Common Pitfalls

- Using deprecated v3 utilities (bg-opacity-*, flex-shrink-*, etc.)
- Using `@tailwind` directives instead of `@import "tailwindcss"`
- Trying to use `tailwind.config.js` instead of CSS `@theme` directive
- Using margins for spacing between siblings instead of gap utilities
- Forgetting to add dark mode variants when the project uses dark mode

## Mobile-Friendly Development Rules

### Mobile-First Approach

Always design for mobile first, then enhance for larger screens using responsive breakpoints:

<code-snippet name="Mobile-First Design" lang="html">
<!-- Start with mobile styles, add larger screen variants -->
<div class="p-4 md:p-6 lg:p-8">
    <h1 class="text-xl md:text-2xl lg:text-3xl">Responsive Title</h1>
</div>
</code-snippet>

### Responsive Breakpoints

Use Tailwind's responsive prefixes consistently:
- **sm:** 640px+ (small tablets)
- **md:** 768px+ (tablets)
- **lg:** 1024px+ (laptops)
- **xl:** 1280px+ (desktops)
- **2xl:** 1536px+ (large desktops)

### Touch-Friendly Interactions

Ensure all interactive elements meet mobile touch targets (minimum 44px):

<code-snippet name="Touch Targets" lang="html">
<!-- Buttons with adequate touch targets -->
<button class="min-h-11 px-4 py-2 md:min-h-10 md:px-3 md:py-1.5">
    Mobile-friendly button
</button>

<!-- Form inputs with proper sizing -->
<input class="h-12 px-4 md:h-10 md:px-3" type="text" placeholder="Input field" />
</code-snippet>

### Mobile Layout Patterns

#### Stacked to Horizontal Layout

<code-snippet name="Responsive Layout" lang="html">
<!-- Stack on mobile, side-by-side on tablet+ -->
<div class="flex flex-col md:flex-row gap-4">
    <div class="w-full md:w-1/2">Content A</div>
    <div class="w-full md:w-1/2">Content B</div>
</div>
</code-snippet>

#### Navigation Adaptations

<code-snippet name="Mobile Navigation" lang="html">
<!-- Hidden on mobile, visible on desktop -->
<nav class="hidden md:flex items-center gap-6">
    <a href="#">Desktop Nav Item</a>
</nav>

<!-- Mobile menu button (visible only on small screens) -->
<button class="md:hidden p-2" aria-label="Toggle menu">
    <svg class="h-6 w-6"><!-- hamburger icon --></svg>
</button>
</code-snippet>

### Content Optimization

#### Typography Scaling

<code-snippet name="Responsive Typography" lang="html">
<!-- Scale text appropriately for different screens -->
<h1 class="text-2xl md:text-3xl lg:text-4xl font-bold">
    Responsive Heading
</h1>
<p class="text-sm md:text-base leading-relaxed">
    Body text that scales with screen size
</p>
</code-snippet>

#### Image Handling

<code-snippet name="Responsive Images" lang="html">
<!-- Full width on mobile, constrained on larger screens -->
<img 
    class="w-full md:w-auto md:max-w-lg h-auto object-cover rounded" 
    src="image.jpg" 
    alt="Description"
/>
</code-snippet>

#### Spacing Adjustments

<code-snippet name="Responsive Spacing" lang="html">
<!-- Tighter spacing on mobile, more generous on desktop -->
<div class="p-4 md:p-6 lg:p-8 space-y-4 md:space-y-6">
    <div class="mb-4 md:mb-6">Section content</div>
</div>
</code-snippet>

### Performance Considerations

#### Reduce Visual Complexity

<code-snippet name="Progressive Enhancement" lang="html">
<!-- Simpler shadows/effects on mobile -->
<div class="bg-white border md:shadow-lg md:border-0 rounded-lg">
    <!-- Simplified mobile styling, enhanced desktop -->
</div>
</code-snippet>

### Form Design

#### Mobile-Optimized Forms

<code-snippet name="Mobile Forms" lang="html">
<form class="space-y-4">
    <!-- Full-width inputs on mobile -->
    <input 
        class="w-full h-12 px-4 border rounded-lg md:h-10" 
        type="text" 
        placeholder="Mobile-optimized input"
    />
    
    <!-- Stack form groups on mobile -->
    <div class="flex flex-col md:flex-row gap-4">
        <input class="flex-1 h-12 px-4 border rounded-lg md:h-10" />
        <input class="flex-1 h-12 px-4 border rounded-lg md:h-10" />
    </div>
    
    <!-- Full-width buttons on mobile -->
    <button class="w-full md:w-auto h-12 px-6 bg-blue-600 text-white rounded-lg">
        Submit
    </button>
</form>
</code-snippet>

### Table Responsiveness

<code-snippet name="Responsive Tables" lang="html">
<!-- Hide less important columns on mobile -->
<table class="w-full">
    <thead>
        <tr>
            <th class="text-left">Name</th>
            <th class="text-left hidden md:table-cell">Email</th>
            <th class="text-left">Status</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="py-2">John Doe</td>
            <td class="hidden md:table-cell">john@example.com</td>
            <td class="py-2">Active</td>
        </tr>
    </tbody>
</table>

<!-- Alternative: Card layout on mobile -->
<div class="block md:hidden space-y-4">
    <div class="border rounded-lg p-4">
        <h3 class="font-semibold">John Doe</h3>
        <p class="text-sm text-gray-600">john@example.com</p>
        <span class="inline-block bg-green-100 text-green-800 px-2 py-1 rounded text-xs">
            Active
        </span>
    </div>
</div>
</code-snippet>

## Common Patterns

### Flexbox Layout

<code-snippet name="Flexbox Layout" lang="html">
<div class="flex items-center justify-between gap-4">
    <div>Left content</div>
    <div>Right content</div>
</div>
</code-snippet>

### Grid Layout

<code-snippet name="Grid Layout" lang="html">
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <div>Card 1</div>
    <div>Card 2</div>
    <div>Card 3</div>
</div>
</code-snippet>

## Common Pitfalls

- Using deprecated v3 utilities (bg-opacity-*, flex-shrink-*, etc.)
- Using `@tailwind` directives instead of `@import "tailwindcss"`
- Trying to use `tailwind.config.js` instead of CSS `@theme` directive
- Using margins for spacing between siblings instead of gap utilities
- Forgetting to add dark mode variants when the project uses dark mode