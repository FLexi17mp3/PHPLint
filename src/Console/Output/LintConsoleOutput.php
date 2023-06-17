<?php

declare(strict_types=1);

namespace PHPLint\Console\Output;

use PHPLint\Config\LintConfig;
use PHPLint\Console\OutputColorEnum;
use PHPLint\Process\LintProcessResult;
use PHPLint\Process\StatusEnum;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Style\SymfonyStyle;

final class LintConsoleOutput
{
    /**
     * @var int
     */
    private const SNIPPED_LINE = 5;

    /**
     * @var int
     */
    private const LINE_LENGTH = 5;

    private bool $isSuccess = true;

    private int $countFiles = 0;

    public function __construct(
        private readonly SymfonyStyle $symfonyStyle,
        private readonly LintConfig $lintConfig,
    ) {
    }

    public function startApplication(string $version): void
    {
        $argv = $_SERVER['argv'] ?? [];

        $message = sprintf(
            '<fg=blue;options=bold>PHP</><fg=yellow;options=bold>Lint</> %s - current PHP version: %s',
            $version,
            PHP_VERSION,
        );
        $this->symfonyStyle->writeln('> ' . implode('', $argv));
        $this->symfonyStyle->writeln($message);
        $this->symfonyStyle->writeln('');
    }

    public function finishApplication(string $executionTime): bool
    {
        $usageMemory = Helper::formatMemory(memory_get_usage(true));

        $this->symfonyStyle->writeln(sprintf('Memory usage: %s', $usageMemory));

        if (! $this->isSuccess) {
            $this->symfonyStyle->error(sprintf('Finished in %s', $executionTime));
            return true;
        }

        $this->symfonyStyle->success(sprintf('Finished in %s', $executionTime));
        return false; // false means success
    }

    public function progressBarStart(int $count): void
    {
        if ($this->lintConfig->isIgnoreProcessBar()) {
            return;
        }

        $this->symfonyStyle->writeln('Linting files...');
        $this->symfonyStyle->newLine();

        $this->symfonyStyle->progressStart($count);
    }

    public function progressBarAdvance(): void
    {
        if ($this->lintConfig->isIgnoreProcessBar()) {
            return;
        }

        $this->symfonyStyle->progressAdvance();
    }

    public function progressBarFinish(): void
    {
        if ($this->lintConfig->isIgnoreProcessBar()) {
            return;
        }

        $this->symfonyStyle->progressFinish();
    }

    public function messageByProcessResult(LintProcessResult $lintProcessResult): void
    {
        $outputColorEnum = match ($lintProcessResult->getStatus()) {
            StatusEnum::OK => OutputColorEnum::GREEN,
            StatusEnum::NOTICE => OutputColorEnum::BLUE,
            StatusEnum::WARNING => OutputColorEnum::YELLOW,
            default => OutputColorEnum::RED,
        };

        ++$this->countFiles;

        $line01 = sprintf(
            '<fg=white;options=bold>#%d - line %s </><fg=gray;options=bold>[%s]</>',
            $this->countFiles,
            $lintProcessResult->getLine(),
            $lintProcessResult->getFilename(),
        );
        $line02 = sprintf(
            '<fg=%s;options=bold>%s</>: <fg=%s>%s</>',
            $outputColorEnum->getBrightValue(),
            ucfirst($lintProcessResult->getStatus()->value),
            $outputColorEnum->value,
            $lintProcessResult->getResult(),
        );

        $this->symfonyStyle->writeln($line01);
        $this->symfonyStyle->writeln($line02);
        $this->loadCodeSnippet($lintProcessResult->getFilename(), (int) $lintProcessResult->getLine(), $outputColorEnum);
        $this->symfonyStyle->newLine();

        $this->isSuccess = false;
    }

    private function loadCodeSnippet(string $filename, int $line, OutputColorEnum $outputColorEnum): void
    {
        $lineStart = $line - self::SNIPPED_LINE;
        $lineEnd = $line + (self::SNIPPED_LINE - 1);

        if (!file_exists($filename)) {
            return;
        }

        $content = file_get_contents($filename);
        if ($content === false) {
            return;
        }

        $contentArray = explode("\n", $content);

        $lineCnt = 0;
        foreach ($contentArray as $contentLine) {
            if ($lineCnt >= $lineStart && $lineCnt < $lineEnd) {
                $lineNumber = $lineCnt + 1;
                $tmp = str_pad((string) $lineNumber, self::LINE_LENGTH, '0', STR_PAD_LEFT);
                $lineNumberPrefix = substr($tmp, 0, self::LINE_LENGTH - strlen((string) $lineNumber));

                if ($lineCnt + 1 === $line) {
                    $result = sprintf(
                        '<fg=%s>%s</><fg=%s;options=bold>%s</><fg=gray>|</> <fg=%s>%s</>',
                        $outputColorEnum->getBrightValue(),
                        $lineNumberPrefix,
                        $outputColorEnum->value,
                        $lineNumber,
                        $outputColorEnum->value,
                        $contentLine,
                    );
                } else {
                    $result = sprintf(
                        '<fg=gray>%s</><fg=white;options=bold>%s</><fg=gray>|</> <fg=white>%s</>',
                        $lineNumberPrefix,
                        $lineNumber,
                        $contentLine,
                    );
                }

                $this->symfonyStyle->writeln($result);
            }

            ++$lineCnt;
        }
    }
}
