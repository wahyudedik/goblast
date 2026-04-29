# Requirements Document

## Introduction

This feature adds a dedicated API Documentation page within the Konektivitas (goblast) dashboard. Currently, API documentation exists only as a static markdown file (`docs/api/README.md`) with no in-app access. Users who create API tokens have no way to discover how to use them without leaving the application. This feature provides an interactive, in-dashboard documentation page that covers authentication, available endpoints, request/response examples, and error handling. It also adds navigation links from the API Tokens page and the sidebar to make the documentation easily discoverable.

## Glossary

- **Documentation_Page**: The Blade view that renders the API documentation content within the authenticated dashboard layout
- **Sidebar_Navigation**: The left-side navigation component (`sidebar-links.blade.php`) that provides links to all dashboard sections
- **API_Tokens_Page**: The existing page (`api-tokens.index`) where users create and manage their API tokens
- **Documentation_Controller**: The controller responsible for rendering the API Documentation page
- **Endpoint_Section**: A discrete section within the Documentation_Page that describes a single API endpoint including its method, URL, parameters, and response format
- **Code_Example_Block**: A styled code block within the Documentation_Page that displays sample request/response payloads in a specific programming language
- **Tenant_User**: An authenticated user belonging to a tenant organization who accesses the dashboard

## Requirements

### Requirement 1: Documentation Page Route and Controller

**User Story:** As a Tenant_User, I want to access an API documentation page from the dashboard, so that I can learn how to integrate with the Konektivitas API without leaving the application.

#### Acceptance Criteria

1. WHEN a Tenant_User navigates to the `/api-docs` URL, THE Documentation_Controller SHALL render the Documentation_Page within the authenticated dashboard layout
2. THE Documentation_Page SHALL require authentication and tenant middleware, consistent with other dashboard routes
3. THE Documentation_Page SHALL be accessible to all authenticated Tenant_Users regardless of their subscription plan
4. WHEN an unauthenticated user attempts to access the Documentation_Page, THE Documentation_Controller SHALL redirect the user to the login page

### Requirement 2: Sidebar Navigation Link

**User Story:** As a Tenant_User, I want to see an API Documentation link in the sidebar navigation, so that I can quickly find the documentation from any page in the dashboard.

#### Acceptance Criteria

1. THE Sidebar_Navigation SHALL display an "API Docs" link positioned directly after the "API Tokens" link in the navigation list
2. THE Sidebar_Navigation SHALL highlight the "API Docs" link with the active state style when the Tenant_User is on the Documentation_Page
3. THE Sidebar_Navigation SHALL display a book or document icon for the "API Docs" link, consistent with the existing icon style (Heroicons outline, 24x24)

### Requirement 3: API Tokens Page Cross-Link

**User Story:** As a Tenant_User, I want to see a link to the API documentation from the API Tokens page, so that I can quickly learn how to use the tokens I create.

#### Acceptance Criteria

1. THE API_Tokens_Page SHALL display a prominent link to the Documentation_Page in the page header area, near the description text
2. WHEN a Tenant_User clicks the documentation link on the API_Tokens_Page, THE API_Tokens_Page SHALL navigate the user to the Documentation_Page
3. THE API_Tokens_Page link SHALL use descriptive text that indicates it leads to API usage documentation (e.g., "Lihat Dokumentasi API")

### Requirement 4: Authentication Section

**User Story:** As a Tenant_User, I want to understand how to authenticate API requests, so that I can successfully connect my external systems to the Konektivitas API.

#### Acceptance Criteria

1. THE Documentation_Page SHALL display an Authentication section that explains the Bearer token authentication method
2. THE Documentation_Page SHALL display the `Authorization: Bearer {your_api_token}` header format in a Code_Example_Block
3. THE Documentation_Page SHALL include a link to the API_Tokens_Page so users can create or manage their tokens
4. THE Documentation_Page SHALL display a security notice advising users to keep tokens secret and revoke compromised tokens

### Requirement 5: Send Message Endpoint Documentation

**User Story:** As a Tenant_User, I want to see documentation for the send-message endpoint, so that I can send individual WhatsApp messages via the API.

#### Acceptance Criteria

1. THE Documentation_Page SHALL display an Endpoint_Section for `POST /api/v1/send-message` that includes the HTTP method, URL path, and description
2. THE Endpoint_Section SHALL list all request parameters (`device_id`, `to`, `message`, `template_id`) with their types, required status, and descriptions in a table format
3. THE Endpoint_Section SHALL display a sample request payload in a Code_Example_Block
4. THE Endpoint_Section SHALL display a sample success response (HTTP 202) in a Code_Example_Block
5. THE Endpoint_Section SHALL document the validation rules: `to` must match the international phone format, `message` is required when `template_id` is absent, and `message` has a maximum length of 4096 characters

### Requirement 6: Send Bulk Endpoint Documentation

**User Story:** As a Tenant_User, I want to see documentation for the send-bulk endpoint, so that I can send broadcast messages to multiple recipients via the API.

#### Acceptance Criteria

1. THE Documentation_Page SHALL display an Endpoint_Section for `POST /api/v1/send-bulk` that includes the HTTP method, URL path, and description
2. THE Endpoint_Section SHALL list all request parameters (`device_id`, `recipients`, `message`, `template_id`) with their types, required status, and descriptions in a table format
3. THE Endpoint_Section SHALL display a sample request payload in a Code_Example_Block
4. THE Endpoint_Section SHALL display a sample success response (HTTP 202) in a Code_Example_Block
5. THE Endpoint_Section SHALL document that the `recipients` array accepts a minimum of 1 and a maximum of 10,000 phone numbers

### Requirement 7: Message Status Endpoint Documentation

**User Story:** As a Tenant_User, I want to see documentation for the message-status endpoint, so that I can check the delivery status of messages I sent via the API.

#### Acceptance Criteria

1. THE Documentation_Page SHALL display an Endpoint_Section for `GET /api/v1/message-status/{jobId}` that includes the HTTP method, URL path, and description
2. THE Endpoint_Section SHALL document the `jobId` path parameter as a required string obtained from the send-message or send-bulk response
3. THE Endpoint_Section SHALL display a sample success response (HTTP 200) in a Code_Example_Block
4. THE Endpoint_Section SHALL list all possible status values (`pending`, `sent`, `failed`, `cancelled`, `retrying`) with their descriptions in a table format

### Requirement 8: Error Handling Documentation

**User Story:** As a Tenant_User, I want to understand the API error responses, so that I can handle errors gracefully in my integration code.

#### Acceptance Criteria

1. THE Documentation_Page SHALL display an Error Handling section that lists all HTTP status codes (200, 202, 401, 403, 404, 422, 429) with their descriptions in a table format
2. THE Documentation_Page SHALL display sample error response payloads for authentication errors (401), validation errors (422), quota exceeded errors (422), and forbidden errors (403) in Code_Example_Blocks
3. THE Documentation_Page SHALL document common error scenarios (invalid token, expired subscription, device not connected, invalid phone format, quota exceeded) with their corresponding HTTP codes

### Requirement 9: Code Examples Section

**User Story:** As a Tenant_User, I want to see code examples in multiple programming languages, so that I can quickly integrate the API with my preferred technology stack.

#### Acceptance Criteria

1. THE Documentation_Page SHALL display code examples for at least three languages/tools: PHP (Guzzle), JavaScript (Fetch), and cURL
2. WHEN a Tenant_User views the code examples, THE Documentation_Page SHALL display each example in a Code_Example_Block with syntax-appropriate formatting
3. THE Documentation_Page SHALL include a working example for the send-message endpoint in each language/tool
4. THE Documentation_Page SHALL include a working example for checking message status in each language/tool

### Requirement 10: Base URL and API Version Information

**User Story:** As a Tenant_User, I want to see the API base URL and version information, so that I can correctly construct API request URLs.

#### Acceptance Criteria

1. THE Documentation_Page SHALL display the base URL pattern (`{app_url}/api/v1`) at the top of the documentation content
2. THE Documentation_Page SHALL display the current API version (v1) prominently
3. THE Documentation_Page SHALL derive the base URL dynamically from the application configuration rather than hardcoding a domain name

### Requirement 11: Page Layout and Navigation Within Documentation

**User Story:** As a Tenant_User, I want the documentation page to have a clear structure with a table of contents, so that I can quickly navigate to the section I need.

#### Acceptance Criteria

1. THE Documentation_Page SHALL display a table of contents with anchor links to each major section (Authentication, Endpoints, Error Handling, Code Examples)
2. WHEN a Tenant_User clicks a table of contents link, THE Documentation_Page SHALL scroll to the corresponding section
3. THE Documentation_Page SHALL use consistent heading hierarchy and visual styling that matches the existing dashboard design (Tailwind CSS utility classes, green accent color scheme)
4. THE Documentation_Page SHALL be responsive and readable on both desktop and mobile screen sizes

### Requirement 12: Rate Limiting Documentation

**User Story:** As a Tenant_User, I want to understand the API rate limits, so that I can design my integration to stay within the allowed request volume.

#### Acceptance Criteria

1. THE Documentation_Page SHALL display a Rate Limiting section that documents the request limit (60 requests per minute per API token)
2. THE Documentation_Page SHALL document the rate limit response headers (`X-RateLimit-Limit`, `X-RateLimit-Remaining`, `X-RateLimit-Reset`)
3. THE Documentation_Page SHALL display a sample HTTP 429 error response in a Code_Example_Block
