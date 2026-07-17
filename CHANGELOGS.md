# Changelog

All notable changes to this project will be documented in this file.

## [Unreleased]

### Added
- Comprehensive realtime monitoring dashboard for MikroTik infrastructure
- Laravel Reverb integration for WebSocket broadcasting
- RouterService for MikroTik ROS6 API communication
- Continuous broadcast command (`app:broadcast-router-updates`)
- Traffic monitoring (RX/TX) with Chart.js visualization
- System utilization gauges (CPU, RAM, Storage) with canvas rendering
- Network information display (LAN, GW, DNS, DHCP leases)
- Top connections tracking (Sources/Destinations)
- Router connection status indicator
- Connected clients monitoring component
- Queue monitoring with recursive nested queue rendering
- Client bandwidth usage tracking (Download/Upload bytes)
- Dark mode support with localStorage persistence and system preference detection
- Theme toggle functionality
- Shared utility CSS classes and JS helpers for consistent styling and logic
- Skeleton loading states and CSS-based sweep animations
- CHANGELOGS.md to track project evolution
- Comprehensive README.md with project-specific details, features, and tech stack
- Feature tests for RouterController (21 tests covering all endpoints)
- Unit tests for RouterService (20 tests covering private helpers)
- Proper TestCase base class with `createApplication()` for Laravel bootstrapping
- RefreshDatabase trait integration for feature tests

### Changed
- Refactored RouterController to delegate logic to RouterService (thin controller pattern)
- Implemented singleton pattern and internal caching in RouterService
- Optimized TrafficMonitor from class-based to lightweight IIFE pattern
- Streamlined component structure and modularized JavaScript
- Reorganized frontend components into `resources/js/components/` directory
- Abstracted repetitive UI patterns into `utilities.css`
- Centralized shared logic in `utils.js`
- Updated all components to use shared utilities and standardized data extraction
- Increased broadcast interval to 2 seconds for reduced CPU usage
- Increased TTL for connections, clients, and queue data from 5s to 10s
- Implemented identity caching to avoid redundant `/system/identity/print` calls
- Used `.proplist` in MikroTik commands to reduce payload size
- Updated application timezone to `Asia/Jakarta`
- Removed LocalStorage chart data caching for reduced complexity
- Simplified CSS by removing unnecessary pseudo-classes
- Stripped debug console.log statements from all monitoring modules
- Updated canvas-based charts and gauges to dynamically adapt colors based on theme
- Replaced boilerplate ExampleTest.php with specific RouterController and RouterService test suites
- Updated Pest.php configuration to use RefreshDatabase trait and correct TestCase extension

### Removed
- Playwright MCP temporary snapshots and metadata files
- ExampleTest.php boilerplate tests from Feature and Unit directories

### Performance
- Added signal handling in BroadcastRouterUpdates for graceful shutdowns
- Implemented exponential backoff/retry logic for WebSocket connections
- LocalStorage-based chart data caching for TrafficMonitor
- Smooth animation loops using RequestAnimationFrame
- Dynamic speed color coding (low/medium/high) for traffic values

---

## [1.0.0] - 2026-07-17

### Added
- Initial commit with basic Laravel project structure
