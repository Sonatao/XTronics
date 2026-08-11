# XTronics Ticketing Project Overview

This repository contains a LAMP-based PHP and MySQL ticketing dashboard for XTronics. The system is organized around a server-rendered PHP page shell and a PHP CRUD API that the browser talks to over JSON payloads.

## Main entry points

- `Ticketing/index.php` – top-level HTML shell for the dashboard. It loads CSS/theme assets, the standalone browser asset, and exposes the user role through a small PHP bootstrap block.
- `Ticketing/crud.php` – the CRUD API layer for create/read/search/fetch/update/delete/history/files operations.
- `Ticketing/db.php` – MySQL PDO connection configuration.
- `Ticketing/auth.php` – session-based authentication/authorization helpers.
- `Ticketing/app.js` – browser-side UI, DOM wiring, rendering, search, create/edit/delete, history, and attachment flows.

## Data model

The persisted schema is defined in `schema.sql`.

Core tables:

- `orders` – main ticket record.
  - `orderDate`
  - `customerName`
  - `buyer`
  - `vendorOrder`
  - `poNumber`
  - `partNumber`
  - `shippingMethod`
  - `notes`
  - `trackingNumber`
  - `status`
- `order_history` – audit history for prior versions of an order record.
- `ticket_files` – attachment metadata and upload history.
- `audit_log` – server-side user action log.

## Requested UI contract

The current UI contract is centered on a single combined vendor/order field named `vendorOrder`. The browser API calls use that field the same way the server uses it.

The UI also expects a free-form `status` value. Status rendering is tolerant of arbitrary status labels and falls back to a generic badge style when a status phrase does not map to a known CSS classification.

## Current implementation notes

- The browser JavaScript used to be embedded directly in the PHP page but now lives in a separate asset.
- The HTML forms are server-rendered and then populated/rendered by browser-side DOM logic.
- User-controlled strings are escaped on the client before injecting them into the ticket list and history tables.
- The API search path now accepts a free-form status filter and uses a LIKE-style comparison so multi-word or partial status strings are not rejected by an equality-only lookup.

## Migration support

The repository includes a migration artifact in `migrations/001_add_vendor_order.sql` to help bring legacy databases up to the requested normalized `vendorOrder` field shape.
