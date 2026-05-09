# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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

