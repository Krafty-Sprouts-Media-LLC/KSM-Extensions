# Settings page UI demos (2.0.0)

These are **static HTML demos** for the future 2.0.0 redesign of extension settings pages. Open each file in a browser to compare layouts and pick one to apply to all extensions (Media Counter, Image Title & Alt, Featured Image Manager, Auto Upload Images, etc.).

## How to view

1. Open the `demos/settings-ui/` folder in your file manager.
2. Double‑click any `.html` file to open it in your default browser.
   
   **Or** from the plugin root in a terminal:
   - Windows: `start demos\settings-ui\demo-a-classic-wp.html`
   - macOS: `open demos/settings-ui/demo-a-classic-wp.html`

3. Repeat for each demo and choose the design you prefer.

## Demos

| File | Style | Best for |
|------|--------|----------|
| **demo-a-classic-wp.html** | Classic WordPress: white box, `form-table`, section titles, familiar WP look | Sites that want a native Settings feel |
| **demo-b-card-stack.html** | Card stack: hero + separate cards per section, pill checkboxes, toggles right‑aligned | Clear separation of sections, modern but simple |
| **demo-c-sidebar-nav.html** | Sidebar nav + content: sticky left nav, panels per section | Many sections; “app‑like” settings |
| **demo-d-compact-minimal.html** | Compact minimal: single column, small toggles, little decoration, strong hierarchy | Dense, fast‑to‑scan pages |
| **demo-e-ksm-refined.html** | KSM refined: one card, section titles, pill list, toggle rows with descriptions, card footer for submit | Closest to current ksm-card/ksm-section; refined and consistent |

## After you pick

Tell me which demo (A, B, C, D, or E) you want. For 2.0.0 we will:

1. Add/update CSS in the plugin (e.g. `admin/assets/css/settings.css` or extend `admin.css`) to match the chosen demo.
2. Refactor each extension’s settings markup (Media Counter, Image Title & Alt, Featured Image Manager, Auto Upload Images) to use the same structure and classes so all settings pages share one design.

No PHP logic changes are implied; only HTML structure and CSS for the settings UI.
