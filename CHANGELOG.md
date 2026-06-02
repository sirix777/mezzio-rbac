# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2026-06-02

### Added
- Added request-aware authorization through `RequestGuardInterface` and `RequestGuard`.
- Added request actor resolution through `RequestActorProviderInterface` and `RequestAttributeActorProvider`.
- Added `GenericActorAdapter` for authentication-like actors that expose `getRoles()`.
- Added a shared `AuthorizationEvaluator` used by both `Guard` and `RequestGuard`.
- Added `PermissionLookupInterface` as the read-only permission association lookup contract used by authorization internals.
- Added regression coverage for real `ServiceManager` wiring, shared permission state between read/write contracts, and multi-role authorization evaluation.

### Changed
- Factories now use `sirix/container-resolver` for strict service and configuration validation.
- `AuthorizeMiddleware` now uses `RequestGuardInterface` so HTTP authorization uses the current request actor.
- `AuthorizeMiddleware` now resolves RBAC metadata from request attributes first and matched route options second.
- Renamed the read-only permission lookup contract from `PermissionMapInterface` to `PermissionLookupInterface`.
- Updated routing integration dependencies to the stable `sirix/mezzio-routing-contracts:^1.0` and `sirix/mezzio-routing-attributes:^1.0` line.
- Moved `mezzio/mezzio-router` to runtime dependencies because `AuthorizeMiddleware` reads `RouteResult` metadata.
- Removed pre-1.0 stability metadata from `composer.json`.
- Updated README for stable request-aware HTTP authorization and `sirix/mezzio-authentication` integration.

### Fixed
- Fixed `#[Can]` authorization being skipped when permission metadata is stored in Mezzio route options.
- Fixed HTTP authorization using a container/static/guest actor instead of the actor stored on the current request.
- Fixed `ConfigProvider` so `PermissionStoreInterface` resolves to the invokable in-memory store instead of being treated as a factory.
- Fixed permission lookup wiring so `PermissionLookupInterface` aliases the same service instance as `PermissionsInterface`, preserving permissions registered through the write API.

### Upgrade Notes
- If you instantiate `AuthorizeMiddleware` manually, inject `RequestGuardInterface` instead of `GuardInterface`.
- `GuardInterface` remains available for non-HTTP authorization and keeps request-independent semantics.
- If you referenced `PermissionMapInterface` directly, update it to `PermissionLookupInterface`.

## [0.1.2] - 2026-05-11

### Fixed
- Fixed alias in `ConfigProvider` where `PermissionsInterface` was incorrectly aliased to `Permissions`.

### Changed
- Added badges for version, downloads, license, and PHP requirements to `README.md`.
- Added MIT license file.

## [0.1.1] - 2026-05-09

### Changed
- Updated `RouteAttributeModifierInterface` import to use `sirix/mezzio-routing-contracts` package instead of `sirix/mezzio-routing-attributes`.

## [0.1.0] - 2026-05-08

### Added
- Initial release of the RBAC authorization package for Mezzio.
- `Guard` for checking permissions with `allows()`, `denies()`, and `authorize()`.
- Support for dot-notation permissions (e.g., `posts.read`).
- Greedy wildcard matching for terminal `*` (e.g., `admin.*` matches `admin.users.delete`).
- "Allow wins over Deny" conflict resolution policy for actors with multiple roles.
- `AuthorizeMiddleware` for PSR-15 compliant request authorization.
- `#[Can]` attribute for optional integration with `sirix/mezzio-routing-attributes`.
- Extensible `ActorProviderInterface`, `PermissionStoreInterface`, and `RuleInterface`.
- Built-in `AllowRule` and `ForbidRule`.
- `InMemoryPermissionStore` for configuration-based or test usage.
- `RuleResolver` with PSR-11 container support and automatic instantiation.
