# Changelog
All notable changes to this project will be documented in this file.

## [0.9.2] Arrow Function Helpers - 2025-03-19
### Added
- Support for arrow function helpers.

### Fixed
- Parse error when using length with `@root` (from https://github.com/zordius/lightncandy/issues/370).


## [0.9.1] Better Return Type - 2025-03-18
### Added
- Detailed return annotation for `compile()` method.


## [0.9.0] Modern Cleanup - 2025-03-18
Initial release after forking from LightnCandy 1.2.6.

### Added
- New `compile` method which takes a template string and options and returns an executable `Closure`.

### Changed
- PHP 8.2+ is now required.
- Replaced compile options array with `Options` object.
- Replaced helper options array with `HelperOptions` object.
- Renamed old `compile` method to `precompile`.
- Replaced `prepare` method with much faster `template` method, and removed dependency on URL include and filesystem write access.

### Fixed
- Rendering data in `{{else}}` of `{{#each}}` (from https://github.com/zordius/lightncandy/pull/369).
- Parsing strings with escaped quotes and parentheses (based on https://github.com/zordius/lightncandy/pull/358).
- Argument count for built-in helpers is now validated.

### Removed
- Custom autoloader.
- Used feature tracking.
- Option to change delimiters.
- `partialresolver` option.
- `compilePartial` method.
- `prepartial` callback option.
- `renderex` option to inject compiled code.
- Option to change runtime class.
- HTML documentation.
- Dozens of unnecessary feature flags.

[0.9.2]: https://github.com/devtheorem/php-handlebars/compare/v0.9.1...v0.9.2
[0.9.1]: https://github.com/devtheorem/php-handlebars/compare/v0.9.0...v0.9.1
[0.9.0]: https://github.com/devtheorem/php-handlebars/tree/v0.9.0
