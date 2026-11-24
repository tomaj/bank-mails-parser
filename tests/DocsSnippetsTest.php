<?php

declare(strict_types=1);

namespace Tomaj\BankMailsParser\Tests;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Runs every ```php block of README.md, UPGRADE-5.0.md and docs/ against the current code.
 *
 * - A block runs in its own PHP process and must exit with status 0 without writing to stderr.
 *   The Composer autoloader is loaded first, and these variables are defined, so a block can show
 *   only the interesting lines: `$emailBody` (the Tatra banka notification of
 *   tests/fixtures/tatrabanka/notifikacia.txt), `$sender` (`b-mail@tatrabanka.sk`), `$subject`
 *   and `$mailContent` (that mail parsed). `processTransaction(MailContent $mailContent)` stands
 *   for the application's own code and does nothing. `error_log()` goes to /dev/null.
 *   A `require 'vendor/autoload.php';` line in a block is dropped: the autoloader is already loaded.
 * - `<!-- snippet: mail csob-cz/viac-transakcii.txt -->` directly before a block sets `$emailBody`
 *   to that file of tests/fixtures/ instead (`$mailContent` is then null).
 * - A block directly after `<!-- snippet: skip -->` is not run: a signature, a fragment of a class,
 *   or the 4.x side of an upgrade step.
 * - A ```text block directly after `<!-- snippet: output -->` is the output of the PHP block before
 *   it. Both are compared line by line, ignoring trailing spaces and blank lines.
 * - docs/4.x/ and docs/sk/4.x/ document the 4.x API and are not run.
 */
#[CoversNothing]
final class DocsSnippetsTest extends TestCase
{
    private const string SKIP = '<!-- snippet: skip -->';
    private const string OUTPUT = '<!-- snippet: output -->';
    private const string MAIL = '/^<!-- snippet: mail (\\S+) -->$/';
    private const string DEFAULT_MAIL = 'tatrabanka/notifikacia.txt';

    /**
     * @return iterable<string, array{string, ?string, string}>
     */
    public static function snippetProvider(): iterable
    {
        $root = dirname(__DIR__);
        $files = [$root . '/README.md', $root . '/UPGRADE-5.0.md'];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root . '/docs', \FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            \assert($file instanceof \SplFileInfo);
            $path = str_replace('\\', '/', $file->getPathname());
            if ($file->getExtension() !== 'md') {
                continue;
            }
            if (str_contains($path, '/node_modules/') || str_contains($path, '/.vitepress/') || str_contains($path, '/4.x/')) {
                continue;
            }
            $files[] = $path;
        }
        sort($files);

        foreach ($files as $path) {
            $relative = substr($path, strlen($root) + 1);
            foreach (self::blocks((string) file_get_contents($path), $relative) as $line => [$code, $output, $mail]) {
                yield $relative . ':' . $line => [$code, $output, $mail];
            }
        }
    }

    #[Test]
    public function documentationHasSnippets(): void
    {
        $count = 0;
        $outputs = 0;
        foreach (self::snippetProvider() as [, $output]) {
            ++$count;
            if ($output !== null) {
                ++$outputs;
            }
        }

        self::assertGreaterThan(50, $count);
        self::assertGreaterThan(5, $outputs);
    }

    #[Test]
    #[DataProvider('snippetProvider')]
    public function snippetRuns(string $code, ?string $expectedOutput, string $mail): void
    {
        $script = tempnam(sys_get_temp_dir(), 'snippet');
        $prepend = tempnam(sys_get_temp_dir(), 'prepend');
        self::assertIsString($script);
        self::assertIsString($prepend);

        $fixture = __DIR__ . '/fixtures/' . $mail;
        self::assertFileExists($fixture);
        $setup = '<?php require ' . var_export(__DIR__ . '/../vendor/autoload.php', true) . ';'
            . ' $emailBody = (string) file_get_contents(' . var_export($fixture, true) . ');'
            . ' $sender = "b-mail@tatrabanka.sk"; $subject = "b-mail notifikacia";'
            . ' $mailContent = ' . ($mail === self::DEFAULT_MAIL ? '(new Tomaj\BankMailsParser\BankMailsParser())->parse($emailBody, $sender)' : 'null') . ';'
            . ' function processTransaction(Tomaj\BankMailsParser\MailContent $mailContent): void {}';

        $header = str_contains($code, 'declare(strict_types=1)') ? "<?php\n\n" : "<?php\n\ndeclare(strict_types=1);\n\n";

        try {
            file_put_contents($script, $header . $code);
            file_put_contents($prepend, $setup);

            $process = proc_open(
                [PHP_BINARY, '-d', 'auto_prepend_file=' . $prepend, '-d', 'display_errors=stderr', '-d', 'log_errors=0', '-d', 'error_log=/dev/null', '-d', 'error_reporting=-1', '-d', 'assert.exception=1', '-d', 'zend.assertions=1', $script],
                [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
                $pipes,
                sys_get_temp_dir(),
            );
            self::assertIsResource($process);
            $stdout = (string) stream_get_contents($pipes[1]);
            $stderr = (string) stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $status = proc_close($process);
        } finally {
            unlink($script);
            unlink($prepend);
        }

        self::assertSame('', $stderr, "The snippet wrote to stderr:\n" . $code . "\n" . $stderr);
        self::assertSame(0, $status, "The snippet failed:\n" . $code . "\n" . $stdout);
        if ($expectedOutput !== null) {
            self::assertSame($this->normalize($expectedOutput), $this->normalize($stdout), "The documented output differs:\n" . $code);
        }
    }

    /**
     * @return array<int, array{string, ?string, string}> line number => [php code, expected output, mail fixture]
     */
    private static function blocks(string $markdown, string $file): array
    {
        $lines = explode("\n", $markdown);
        $code = [];
        $mails = [];
        $outputs = [];
        $lastPhp = null;
        $previous = '';
        $count = count($lines);
        for ($i = 0; $i < $count; ++$i) {
            if (preg_match('/^```(\w+)/', $lines[$i], $match) !== 1) {
                if (trim($lines[$i]) !== '') {
                    $previous = trim($lines[$i]);
                }

                continue;
            }
            $start = $i + 1;
            $body = [];
            for (++$i; $i < $count && !str_starts_with($lines[$i], '```'); ++$i) {
                $body[] = $lines[$i];
            }
            $text = implode("\n", $body) . "\n";
            if ($match[1] === 'php' && $previous !== self::SKIP) {
                $text = (string) preg_replace('/^<\?php\s*/', '', $text);
                $code[$start] = (string) preg_replace('/^require(?:_once)? (?:__DIR__ \. )?\'\/?vendor\/autoload\.php\';\n/m', '', $text);
                $mails[$start] = preg_match(self::MAIL, $previous, $mail) === 1 ? $mail[1] : self::DEFAULT_MAIL;
                $lastPhp = $start;
            } elseif ($match[1] === 'text' && $previous === self::OUTPUT) {
                self::assertNotNull($lastPhp, $file . ':' . $start . ': an output block must follow a PHP block.');
                $outputs[$lastPhp] = $text;
            }
            $previous = '```';
        }

        $blocks = [];
        foreach ($code as $line => $php) {
            $blocks[$line] = [$php, $outputs[$line] ?? null, $mails[$line]];
        }

        return $blocks;
    }

    private function normalize(string $output): string
    {
        $lines = array_filter(array_map(rtrim(...), explode("\n", $output)), static fn(string $line): bool => $line !== '');

        return implode("\n", $lines);
    }
}
