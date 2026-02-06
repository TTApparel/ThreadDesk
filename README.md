# ThreadDesk

ThreadDesk is a WooCommerce customer portal that adds a dedicated "ThreadDesk" section inside **My Account** for profile insights, quotes, invoices, designs, and layouts.

## Installation

1. Upload the plugin folder to `/wp-content/plugins/thread-desk`.
2. Activate **ThreadDesk** in the WordPress admin.
3. Visit **WooCommerce → ThreadDesk** to configure the cover image and default company name.
4. Navigate to **My Account → ThreadDesk** to view the customer portal.

## Features

- Custom WooCommerce My Account endpoint with sidebar navigation.
- Profile dashboard showing order stats, recent activity, and account details.
- Quotes, Designs, and Layouts stored as private custom post types.
- Invoice/Order list with reorder actions.
- Demo data generator for quick previews.

## Roadmap

- Add real invoice PDF downloads.
- Quote-to-order conversion with checkout flow.
- Artwork approval workflows and notifications.
- More granular permissions and admin management UI.

## Development Notes

- Quotes are stored as `tta_quote` custom post types with metadata (`status`, `total`, `currency`, `items_json`, `created_at`).
- Designs and Layouts are stored as `tta_design` and `tta_layout` placeholders.
- Outstanding balance is calculated from unpaid WooCommerce orders with statuses `pending`, `on-hold`, and `failed`.

