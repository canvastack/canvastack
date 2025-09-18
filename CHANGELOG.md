# Changelog

All notable changes to this project will be documented in this file.

## [Unreleased]
- Docs: Add comprehensive guides for Forms, Tables, Utility, Template Engine, and User & Role Management.
- Readme: Add badges, support matrix, changelog section, and documentation links.

## [2.2.0] - 2025-01-03
### BREAKING CHANGES
- Move Core components from `src/Controllers/Core/` to `src/Core/`
- Reorganize Action traits into modular structure (CrudOperations, DataOperations, etc.)
- Update namespace imports across all controllers and models
- Remove deprecated Tablify components and legacy traits

### Added
- Comprehensive test structure (Integration, Unit, Security)
- Enhanced Canvaser table system with new pipeline components
- Modularization documentation and Phase 4 integration guides
- New Core architecture with separated concerns
- ActionButtonsResolver and enhanced pipeline stages

### Removed
- Temporary documentation files (ASSETS_PUBLISHING, REPOSITORY_SYNC_STATUS)
- Deprecated DynamicDeleteTrait and legacy test files
- Unused Tablify HTTP handlers and services

### Changed
- 146 files updated with new Core namespace
- All controllers updated with new import paths
- Complete Publisher app structure updated

## [1.0.0-alpha] - 2025-09-02
- Rebrand from Incodiy/CODIY to CanvaStack.
- Introduce Canvatility facade for Utility consolidation (HTML, Table, Template, Data, Db, Json, Url, Assets).
- Yajra DataTables integration improvements: processing/deferRender defaults, server-side preview support.
- Publish tags: "CanvaStack" and "CanvaStack Public Folder" for assets and configs.
- CLI commands registered for snapshot validation, pipeline dry-run, DB checks, and benchmarks.

[Unreleased]: https://github.com/canvastack/canvastack/compare/1.0.0-alpha...HEAD
[1.0.0-alpha]: https://github.com/canvastack/canvastack/releases/tag/1.0.0-alpha