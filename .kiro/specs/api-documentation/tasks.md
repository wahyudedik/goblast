# Implementation Plan: API Documentation Page

## Overview

Add an in-dashboard API Documentation page to the Konektivitas application. The implementation follows the existing patterns: an invokable controller, a Blade view extending `layouts.app`, a route in the authenticated tenant middleware group, sidebar navigation entry, and a cross-link from the API Tokens page. No new models, migrations, or services are required.

## Tasks

- [x] 1. Create the ApiDocController and register the route
  - [x] 1.1 Create an invokable `ApiDocController` at `app/Http/Controllers/ApiDocController.php`
    - Use `php artisan make:controller ApiDocController --invokable`
    - The `__invoke` method computes `$baseUrl` from `config('app.url')` appended with `/api/v1` and returns the `api-docs.index` view
    - _Requirements: 1.1, 10.1, 10.3_
  - [x] 1.2 Register the `GET /api-docs` route in `routes/web.php`
    - Add `Route::get('api-docs', ApiDocController::class)->name('api-docs.index');` inside the existing `auth + verified + tenant` middleware group, after the API token routes
    - Add the `use App\Http\Controllers\ApiDocController;` import at the top of the file
    - _Requirements: 1.1, 1.2, 1.3, 1.4_

- [x] 2. Add sidebar navigation link and API Tokens page cross-link
  - [x] 2.1 Add "API Docs" entry to the sidebar navigation in `resources/views/layouts/sidebar-links.blade.php`
    - Insert a new entry in the `$navigation` array immediately after the "API Tokens" entry
    - Use the `book-open` Heroicons outline icon, route `api-docs.index`, and active state `request()->routeIs('api-docs.*')`
    - _Requirements: 2.1, 2.2, 2.3_
  - [x] 2.2 Add a cross-link to the API Documentation page in `resources/views/api-tokens/index.blade.php`
    - Add a "Lihat Dokumentasi API →" link in the header description paragraph, linking to `route('api-docs.index')`
    - Style with `text-green-600 hover:text-green-700 font-medium` to match the existing design
    - _Requirements: 3.1, 3.2, 3.3_

- [x] 3. Create the documentation Blade view with header, table of contents, and authentication section
  - [x] 3.1 Create `resources/views/api-docs/index.blade.php` extending `layouts.app`
    - Set `@section('page-title', 'Dokumentasi API')`
    - Add a page header with title, API version badge (v1), and dynamic base URL display using `{{ $baseUrl }}`
    - Add a table of contents with anchor links to: Autentikasi, Endpoints (Send Message, Send Bulk, Message Status), Penanganan Error, Rate Limiting, Contoh Kode
    - _Requirements: 10.1, 10.2, 11.1, 11.2, 11.3_
  - [x] 3.2 Add the Authentication section to the documentation view
    - Explain Bearer token authentication with `Authorization: Bearer {your_api_token}` in a dark-themed code block (`bg-gray-900 text-gray-100 rounded-lg p-4 overflow-x-auto text-sm`)
    - Include a link to the API Tokens page (`route('api-tokens.index')`) for token management
    - Add a security notice advising users to keep tokens secret and revoke compromised tokens
    - _Requirements: 4.1, 4.2, 4.3, 4.4_

- [x] 4. Add endpoint documentation sections
  - [x] 4.1 Add the Send Message endpoint section (`POST /api/v1/send-message`)
    - Display HTTP method, URL path, and description
    - Add a parameters table listing `device_id` (integer, required), `to` (string, required), `message` (string, conditional), `template_id` (integer, optional) with types, required status, and descriptions
    - Add sample request payload and sample success response (HTTP 202) in code blocks
    - Document validation rules: `to` must match international phone format, `message` required when `template_id` absent, `message` max 4096 characters
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5_
  - [x] 4.2 Add the Send Bulk endpoint section (`POST /api/v1/send-bulk`)
    - Display HTTP method, URL path, and description
    - Add a parameters table listing `device_id` (integer, required), `recipients` (array, required), `message` (string, conditional), `template_id` (integer, optional) with types, required status, and descriptions
    - Add sample request payload and sample success response (HTTP 202) in code blocks
    - Document that `recipients` array accepts min 1 and max 10,000 phone numbers
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5_
  - [x] 4.3 Add the Message Status endpoint section (`GET /api/v1/message-status/{jobId}`)
    - Display HTTP method, URL path, and description
    - Document the `jobId` path parameter as a required string from send-message or send-bulk response
    - Add sample success response (HTTP 200) in a code block
    - Add a status values table listing `pending`, `sent`, `failed`, `cancelled`, `retrying` with descriptions
    - _Requirements: 7.1, 7.2, 7.3, 7.4_

- [x] 5. Add error handling and rate limiting sections
  - [x] 5.1 Add the Error Handling section
    - Add an HTTP status codes table listing 200, 202, 401, 403, 404, 422, 429 with descriptions
    - Add sample error response code blocks for: authentication error (401), validation error (422), quota exceeded (422), forbidden (403)
    - Document common error scenarios (invalid token, expired subscription, device not connected, invalid phone format, quota exceeded) with corresponding HTTP codes
    - _Requirements: 8.1, 8.2, 8.3_
  - [x] 5.2 Add the Rate Limiting section
    - Document the 60 requests per minute per API token limit
    - Document rate limit response headers: `X-RateLimit-Limit`, `X-RateLimit-Remaining`, `X-RateLimit-Reset`
    - Add a sample HTTP 429 error response in a code block
    - _Requirements: 12.1, 12.2, 12.3_

- [x] 6. Add code examples section
  - [x] 6.1 Add code examples for the send-message endpoint
    - Add PHP (Guzzle), JavaScript (Fetch), and cURL examples in dark-themed code blocks
    - Each example should be a working request to `POST /api/v1/send-message` with Bearer token auth
    - _Requirements: 9.1, 9.2, 9.3_
  - [x] 6.2 Add code examples for the message-status endpoint
    - Add PHP (Guzzle), JavaScript (Fetch), and cURL examples in dark-themed code blocks
    - Each example should be a working request to `GET /api/v1/message-status/{jobId}` with Bearer token auth
    - _Requirements: 9.1, 9.2, 9.4_

- [x] 7. Ensure responsive layout and consistent styling
  - Review the full `api-docs/index.blade.php` view for responsive design (readable on desktop and mobile)
  - Ensure consistent heading hierarchy, Tailwind CSS utility classes, green accent color scheme matching the dashboard
  - Verify all anchor links in the table of contents work correctly with `id` attributes on section headings
  - _Requirements: 11.3, 11.4_

- [x] 8. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 9. Write feature tests for the API Documentation page
  - [x] 9.1 Create `tests/Feature/ApiDocPageTest.php` with PHPUnit
    - Use `php artisan make:test ApiDocPageTest --phpunit`
    - _Requirements: 1.1, 1.2, 1.3, 1.4_
  - [x] 9.2 Write test: authenticated tenant user can access the API docs page (GET /api-docs returns 200, correct view)
    - _Requirements: 1.1, 1.3_
  - [x] 9.3 Write test: unauthenticated user is redirected to login
    - _Requirements: 1.2, 1.4_
  - [x] 9.4 Write test: page displays dynamic base URL from config
    - _Requirements: 10.1, 10.2, 10.3_
  - [x] 9.5 Write test: page displays table of contents with anchor links
    - _Requirements: 11.1_
  - [x] 9.6 Write test: page displays authentication section with Bearer token format and link to API Tokens page
    - _Requirements: 4.1, 4.2, 4.3, 4.4_
  - [x] 9.7 Write test: page displays send-message endpoint documentation with parameters and sample payloads
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5_
  - [x] 9.8 Write test: page displays send-bulk endpoint documentation with parameters and recipient limits
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5_
  - [x] 9.9 Write test: page displays message-status endpoint documentation with status values
    - _Requirements: 7.1, 7.2, 7.3, 7.4_
  - [x] 9.10 Write test: page displays error handling section with HTTP status codes and sample error responses
    - _Requirements: 8.1, 8.2, 8.3_
  - [x] 9.11 Write test: page displays code examples for PHP, JavaScript, and cURL
    - _Requirements: 9.1, 9.3, 9.4_
  - [x] 9.12 Write test: page displays rate limiting section with headers and 429 example
    - _Requirements: 12.1, 12.2, 12.3_
  - [x] 9.13 Write test: sidebar contains "API Docs" link with correct route
    - _Requirements: 2.1, 2.2_
  - [x] 9.14 Write test: API Tokens page contains link to API documentation with descriptive text
    - _Requirements: 3.1, 3.2, 3.3_

- [x] 10. Final checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- No property-based tests are included — the design explicitly assessed PBT as not applicable for this read-only documentation feature
- The implementation language is PHP with Blade templates, matching the existing codebase
- All UI text follows the existing Indonesian (Bahasa) convention used throughout the application
- No new models, migrations, services, or dependencies are required
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation
