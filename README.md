# ThreadDesk

ThreadDesk is a WooCommerce customer portal that adds a dedicated "ThreadDesk" section inside **My Account** for profile insights, quotes, invoices, designs, and layouts.

## Installation

1. Upload the plugin folder to `/wp-content/plugins/thread-desk`.
2. Activate **ThreadDesk** in the WordPress admin.
3. Visit **WooCommerce → ThreadDesk** to configure the cover image and default company name.
4. Navigate to **My Account → ThreadDesk** to view the customer portal.
5. For Elementor pages, add a Shortcode widget and insert `[threaddesk]`.

## Features

- Custom WooCommerce My Account endpoint with sidebar navigation.
- Profile dashboard showing order stats, recent activity, and account details.
- Quotes, Designs, and Layouts stored as private custom post types.
- Invoice/Order list with reorder actions.
- Demo data generator for quick previews.
- Shortcode support via `[threaddesk]` for Elementor or custom pages.

## Roadmap

- Add real invoice PDF downloads.
- Quote-to-order conversion with checkout flow.
- Artwork approval workflows and notifications.
- More granular permissions and admin management UI.

## Development Notes

- Quotes are stored as `tta_quote` custom post types with metadata (`status`, `total`, `currency`, `items_json`, `created_at`).
- Designs and Layouts are stored as `tta_design` and `tta_layout` placeholders.
- Outstanding balance is calculated from unpaid WooCommerce orders with statuses `pending`, `on-hold`, and `failed`.


## Designer Color Extraction Notes

- The DESIGN modal now analyzes uploaded PNG/JPG/SVG artwork in-browser using a canvas buffer scaled down to a maximum dimension of 1200px for performance.
- Color extraction uses deterministic k-means clustering (max 8 colors), then filters very small clusters (<0.5% of opaque pixels) and merges nearby clusters by RGB distance.
- The detected palette is rendered as editable swatches in `.threaddesk-designer__controls`; changing a swatch recolors the quantized preview in real time while preserving original alpha transparency.
- Swatch editing is restricted to the approved ThreadDesk/Pantone table colors; clicking a swatch opens selectable preset color chips from that table, including White and Black options.
- Expensive operations are throttled/debounced for responsiveness:
  - file analysis yields back to the UI thread before clustering,
  - maximum-color slider reanalysis is debounced.
- Stored design metadata fields include extracted palette, estimated color count, and analysis settings for future cart/session persistence wiring.

### Known limitations

- Recoloring is an approximation based on quantized clusters and does not preserve gradients or blend modes exactly.
- Very complex artwork may lose minor shades due to noise filtering and color merging.
- Current persistence stores metadata in form fields on the client side; server-side save integration must consume/validate these values in the submission flow.
