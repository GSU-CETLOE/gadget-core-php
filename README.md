# gadget-core-php

Gadget is a small PHP library of helpers that come up in almost every project: type casting that throws instead of guessing, JSON and Base32 encoding, file and stream wrappers that throw instead of returning `false`, a few DOM conveniences for scraping HTML, a stopwatch, and HOTP/TOTP one-time password generation. It has no runtime dependencies beyond PHP itself and the `json` and `dom` extensions.

CETLOE at Georgia State University maintains this package. It started as `mjfk23/gadget-core`, written by Melody Kimball.

## Requirements

- PHP 8.1
- `ext-json` and `ext-dom` (both ship with most PHP builds)

## Setup

Install [Composer](https://getcomposer.org/) if you don't have it, then add the package to your project:

```sh
composer require gsu-cetloe/gadget-core-php
```

To work on the library itself, clone the repository and install the development dependencies:

```sh
git clone https://github.com/GSU-CETLOE/gadget-core-php.git
cd gadget-core-php
composer install
```

Your machine does not have to run PHP 8.1. Composer is told to resolve dependencies as if it were PHP 8.1.0 no matter what version runs it, so the lock file always reflects what the library targets. Day to day that works fine on a newer PHP. Before a release, though, run the suite on an actual 8.1 build.

## Development commands

| Command | What it does |
| --- | --- |
| `composer test` | Runs the PHPUnit suite in `tests/`. |
| `composer phpstan` | Runs PHPStan at level 10 over `src/` and `tests/`. |
| `composer phpcs` | Checks `src/` and `tests/` against PSR-12. |
| `composer check` | Runs all three in order. Run this before you commit. |

Anything generated, such as the PHPStan cache, the PHPUnit cache, and the scratch files the tests write, lands in `working/`. Git ignores that directory.

## What's in it

Everything lives under the `Gadget` namespace and autoloads from `src/`. The sections below go roughly in the order you're likely to reach for them.

### Casting: `Gadget\Io\Cast`

Static helpers that turn loosely typed input (request parameters, decoded JSON, environment variables) into the type you asked for, or throw a `CastException` when they can't.

```php
use Gadget\Io\Cast;

Cast::toInt('42');                 // 42
Cast::toBool('yes');               // true  (first letter in 1, O, T, X, Y)
Cast::toFloat('2.5');              // 2.5
Cast::toString(7);                 // '7'
Cast::toArray('{"a":1}');          // ['a' => 1]  (JSON strings are decoded)

Cast::toTypedArray('[1,"2"]', Cast::toInt(...));          // [1, 2]
Cast::toValueOrNull(null, Cast::toInt(...));              // null
Cast::toTypedMap(
    [['id' => 'a', 'n' => 1], ['id' => 'b', 'n' => 2]],
    Cast::toArray(...),
    fn(array $row): string => Cast::toString($row['id'])
);                                                         // ['a' => [...], 'b' => [...]]
```

### JSON: `Gadget\Io\JSON`

Thin wrappers around `json_decode` and `json_encode` that always decode to associative arrays and always throw `JsonException` on bad input.

```php
use Gadget\Io\JSON;

$data = JSON::decode('{"name":"Ada"}');     // ['name' => 'Ada']
$text = JSON::encode($data, JSON_PRETTY_PRINT);
```

### Base32: `Gadget\Io\Base32`

RFC 4648 Base32 encoding and decoding. Decoding accepts lowercase input and ignores padding.

```php
use Gadget\Io\Base32;

Base32::encode('foobar');          // 'MZXW6YTBOI'
Base32::encode('foobar', true);    // 'MZXW6YTBOI======'
Base32::decode('mzxw6ytboi');      // 'foobar'
```

### Files: `Gadget\Io\File` and `Gadget\Io\FileStream`

`File` wraps a path and exposes the usual filesystem checks plus `getContents()` and `putContents()`, which throw `FileException` instead of returning `false`. `File::glob()` returns `File` objects and accepts one pattern or a list of them.

```php
use Gadget\Io\File;

$file = new File('/var/data/report.csv');
if ($file->exists()) {
    echo $file->getContents();
}
$file->putContents("a,b\n1,2\n");

foreach (File::glob(['/var/data/*.csv', '/var/data/*.tsv']) as $match) {
    echo $match->getBaseName(), "\n";
}
```

`FileStream` wraps a stream resource and throws on failure the same way. The static helpers open the file, walk it, and close it for you, even if your loop body throws.

```php
use Gadget\Io\FileStream;

foreach (FileStream::foreachLine(new FileStream('/var/log/app.log', 'r')) as $number => $line) {
    echo "$number: $line";
}

foreach (FileStream::foreachCsvRow(new FileStream('/var/data/report.csv', 'r')) as $row) {
    // $row is the array fgetcsv returns; a blank line yields [null]
}

$out = (new FileStream('/tmp/out.csv', 'w'))->open();
$out->writeCsvRow(['id', 'name']);
$out->writeLine('done');
$out->close();
```

### HTML: `Gadget\Io\DOM`

Helpers for pulling data out of HTML with `DOMDocument`. `loadHTML()` swallows libxml's parse warnings, the finders throw `DOMException` when nothing matches, and `cleanNodeValue()` normalizes smart quotes, dashes, and stray whitespace.

```php
use Gadget\Io\DOM;

$document = DOM::loadHTML($html);
$table = DOM::getElementById($document, 'results');

foreach (DOM::findChildrenByName($table, 'tr', true) as $row) {
    foreach (DOM::childNodes($row) as $cell) {
        echo DOM::cleanNodeValue($cell), "\t";
    }
    echo "\n";
}
```

### Exceptions: `Gadget\Exception\*`

`Exception` accepts either a plain message or a `[format, [args]]` pair that is run through `sprintf`, and `setCode()` is chainable. `CastException`, `DOMException`, and `FileException` extend it. `StackTraceIterator` yields a Java-style, one-line-per-frame trace that follows the `previous` chain.

```php
use Gadget\Exception\StackTraceIterator;

try {
    doWork();
} catch (\Throwable $error) {
    foreach (new StackTraceIterator($error) as $line) {
        error_log($line);
    }
}
```

### Timer: `Gadget\Util\Timer`

A stopwatch with millisecond resolution. Cast it to a string and you get the elapsed time as `DD:HH:MM:SS.mmm`.

```php
use Gadget\Util\Timer;

$timer = (new Timer())->start();
// ... work ...
$timer->stop();
echo "Finished in $timer\n";        // Finished in 00:00:00:02.341
```

### One-time passwords: `Gadget\Util\MFA\HOTP` and `TOTP`

Counter-based (RFC 4226) and time-based (RFC 6238) one-time password generators. Keys are Base32 strings, which is what authenticator apps and most MFA setup screens use.

```php
use Gadget\Util\MFA\TOTP;

$code = (new TOTP())
    ->setKey('GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ')
    ->setCurrentTime(time())
    ->generate();                   // six digits, 30 second period, SHA-1
```

`setLength()`, `setAlgorithm()`, `setTimePeriod()`, and `setStartTime()` adjust the defaults. `HOTP` works the same way with `setCounter()` in place of the time setters.

## Testing

`composer test` runs the suite. The tests write real files under `working/tests/` and check the HOTP and TOTP generators against the vectors published in their RFCs. [TESTPLAN.md](TESTPLAN.md) has the manual walkthrough.

## License

MIT. See [LICENSE](LICENSE).
