# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [4.0.0] - 2026-08-08

`HtmlForm` generates elements. Every public method returns `Html` or an array
of `Html` now, apart from `makeName()` and the delimiter pair. See
[MIGRATION_3.1_to_4.0.md](MIGRATION_3.1_to_4.0.md).

### Breaking Changes
- **Removed the value display methods** - `value()`, `format()`, `number()`, `date()` and `selected()` each read a value out of the form and wrapped it in `Html`. Escaping is what `h()` does and the caller writes it anyway, paths are property access on the form, and coroq/form parses values into their own types already, so `date()` stringified a validated date only to hand it back to `strtotime()`. Read the value from the form item and escape it: `h($form->price->getValue())`, `h($form->created->getParsedValue()?->format('F d, Y'))`
- **Removed `getForm()`** - whoever constructs an `HtmlForm` holds the form already. This supersedes the 2.1-to-3.0 guide, which named it as the replacement for the removed `__call()` proxy
- **Item paths are strings** - an array path could only express a name containing the delimiter, and in exchange every signature was wider and a "not found" error printed `Array`. Use `setItemPathDelimiter()` for that case instead
- **`error()` takes item paths variadically** - `error('email', 'username')`, and spread an array with `error(...$paths)`
- **`inputCheckable()` and `inputCheckables()` are protected** - they hold the shared logic behind `inputCheckbox()`, `inputRadio()`, `inputCheckboxes()` and `inputRadios()`. A subclass can still override `inputCheckable()` to change checkboxes and radios together
- **Renamed `getGeneralAttributesFromInput()` to `getGeneralAttributes()`** - affects subclasses only; it stays protected
- **Requires `coroq/html` 1.x or 2.x** - was `^0.5`. Both are accepted, so the html upgrade can happen separately from this one. Code of yours that uses `coroq/html` directly is affected by that package's own breaking changes

### Added
- `getItemPathDelimiter()` and `setItemPathDelimiter()` - change the delimiter when an item name has to contain `/`. An item name cannot contain the delimiter, `[` or `]`
- `LICENSE` - MIT, matching what `composer.json` already declared
- Continuous integration across PHP 8.0 to 8.4, and 8.5 allowed to fail

### Fixed
Both of these change rendered markup. Nothing else does - attribute order and
`value=""` are unchanged, whatever the 3.1.0 README's output comments claimed.

- **An unbounded numeric item no longer carries `min` and `max`** - the guard compared `getMax()` against `INF`, which nothing returns: an unbounded `IntegerInput` reports the integer limit as a string and a `NumberInput` reports null. So every unbounded integer input shipped `max="9223372036854775807" min="-9223372036854775808"`
- **`inputCheckbox()` no longer emits `required`** - `required` on one box of a group demands that every box be checked. `inputCheckboxes()` already cleared it, so rendering the boxes one at a time to control layout behaved differently from rendering them together. `inputBoolean()` is a single checkbox that genuinely must be checked, so it keeps `required`

---

Releases before 4.0.0 predate this file. For 2.1.0 to 3.0.0 see
[MIGRATION_2.1_to_3.0.md](MIGRATION_2.1_to_3.0.md).
