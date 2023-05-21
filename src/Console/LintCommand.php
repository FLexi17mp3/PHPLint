<?php

declare(strict_types=1);
namespace PHPLint\Console;

use PHPLint\Config\LintConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class LintCommand extends Command
{
    public function __construct(
        private readonly LintConfig $lintConfig
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('phplint');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $lintConfig = $this->lintConfig;

        dump($lintConfig);

        $output->writeln('<error>foo</error>');

        return self::SUCCESS;
    }
}