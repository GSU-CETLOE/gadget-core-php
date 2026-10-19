# Test Plan

Walk through this list before tagging a release or merging a change that touches `composer.json`, the analyzers, or the test suite. Run every command from the repository root.

## Setup

- [ ] `composer install` finishes without errors and without asking to change the lock file.
- [ ] `composer validate` reports the manifest is valid.
- [ ] `working/` exists after the first run and is not shown in `git status`.

## Automated checks

- [ ] `composer phpcs` reports no errors and no warnings for `src/` and `tests/`.
- [ ] `composer phpstan` reports `No errors` at level 10 for `src/` and `tests/`.
- [ ] `composer test` passes with no warnings, deprecations, or notices in the output.
- [ ] `composer check` runs all three in order and exits 0.

## Functional coverage

Confirm each area has passing tests under `tests/` that cover the listed behaviors.

- [ ] `Gadget\Exception\Exception`: string, `Stringable`, and `[format, args]` messages; `previous`; chainable `setCode()`.
- [ ] `Gadget\Exception\StackTraceIterator`: first line, dotted frame lines, `Caused by:` for previous exceptions, repeat iteration.
- [ ] `Gadget\Io\Cast`: every `to*()` method on accepted inputs and the `CastException` message on rejected inputs.
- [ ] `Gadget\Io\JSON`: decode to arrays, encode, flag pass-through, depth limits, exceptions on bad input.
- [ ] `Gadget\Io\Base32`: RFC 4648 vectors with and without padding, lowercase decode, full byte round trip.
- [ ] `Gadget\Io\File`: `glob()` with one or many patterns, path helpers, `stat()`, existence checks, read and write, and the `FileException` plus PHP warning on missing files.
- [ ] `Gadget\Io\FileStream`: open and close lifecycle, read and write round trips, CSV round trip, seek and truncate, locking, and the three `foreach*` helpers on files with and without a trailing newline.
- [ ] `Gadget\Io\DOM`: parse, find by id and by name, child and attribute iteration, `cleanNodeValue()` on strings and nodes.
- [ ] `Gadget\Util\Timer`: zero values before start, elapsed while running, frozen after stop, resume, reset.
- [ ] `Gadget\Util\MFA\HOTP`: RFC 4226 vectors, key validation, algorithm validation, digit length.
- [ ] `Gadget\Util\MFA\TOTP`: RFC 6238 vectors, time period, start time, counter derived from time.

## Known gaps

- `DOM::loadHTML()` only throws when libxml refuses the input. The libxml bundled with current PHP builds accepted every input tried (whitespace, a lone `<`, a null byte, a bare XML declaration), so that branch has no automated test. If you find an input that trips it, add a test.
- Tests run on whatever PHP is installed locally. Composer resolves dependencies as if PHP were 8.1.0, but the behavior checked here is that of the local interpreter.
