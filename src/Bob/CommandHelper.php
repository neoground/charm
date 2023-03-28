<?php
/**
 * This file contains the Bob class
 */

namespace Charm\Bob;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class CommandHelper
 *
 * Providing helpful functions for
 * easier and better console handling.
 *
 * Mostly for pretty output, but also for simple
 * inputs.
 *
 * Also allows access to SymfonyStyle
 */
class CommandHelper
{
    protected InputInterface $input;
    protected OutputInterface $output;
    protected SymfonyStyle $symfonyStyle;

    public function __construct(InputInterface $input, OutputInterface $output)
    {
        $this->setIO($input, $output);
        $this->initSymfonyStyle();
    }

    private function setIO($input, $output) : void
    {
        $this->input = $input;
        $this->output = $output;
    }

    private function initSymfonyStyle(): void
    {
        $this->symfonyStyle = new SymfonyStyle($this->input, $this->output);
    }

    public function outputCharmHeader()
    {
        $logo = "\n";
        $logo .= ' ██████ ██   ██  █████  ██████  ███    ███ ' . "\n";
        $logo .= '██      ██   ██ ██   ██ ██   ██ ████  ████ ' . "\n";
        $logo .= '██      ███████ ███████ ██████  ██ ████ ██ ' . "\n";
        $logo .= '██      ██   ██ ██   ██ ██   ██ ██  ██  ██ ' . "\n";
        $logo .= ' ██████ ██   ██ ██   ██ ██   ██ ██      ██ ' . "\n";
        $logo .= "\n";

        $this->outputRainbow($logo);
    }

    public function outputAsciiBox(string $text, string $align = 'left', bool $as_rainbow = false): void
    {
        $width = 42;
        $inner_width = $width - 4;

        $border_str = "\033[36m" . '+' . str_repeat('-', $width-2) . '+' . "\033[37m";

        $output_str = '';

        $output_str .= $border_str . "\n";

        // Format lines
        $lines = explode("\n", $text);
        $arr = [];
        foreach($lines as $line) {
            $arr += str_split($line, $inner_width);
        }

        foreach($arr as $renderline) {
            if(strlen($renderline) < $inner_width) {
                // Fill up to inner width
                $renderline = str_pad($renderline, $inner_width, ' ');
            }
            $output_str .= "\033[36m" . '| ' . "\033[37m" . $renderline . "\033[36m" . ' |' . "\n\033[37m";
        }

        $output_str .= $border_str . "\n";

        if($as_rainbow) {
            $this->outputRainbow($output_str);
        } else {
            foreach(explode("\n", $output_str) as $row) {
                $this->output->writeln($row);
            }
        }
    }

    public function outputRainbow(string $text)
    {
        $colors = [
            "\033[31m", // red
            "\033[32m", // green
            "\033[33m", // yellow
            "\033[34m", // blue
            "\033[35m", // magenta
            "\033[36m", // cyan
            "\033[37m", // white
            "\033[90m", // gray
            "\033[91m", // bright-red
            "\033[92m", // bright-green
            "\033[93m", // bright-yellow
            "\033[94m", // bright-blue
            "\033[95m", // bright-magenta
            "\033[96m", // bright-cyan
            "\033[97m", // bright-white
        ];

        $lines = explode(PHP_EOL, $text);
        $last_color = null;

        foreach ($lines as $line) {
            $new_color = rand(0, count($colors) - 1);
            while($new_color == $last_color) {
                $new_color = rand(0, count($colors) - 1);
            }
            $last_color = $new_color;
            $this->output->writeln($colors[$new_color] . $line . "\033[37m");
        }
    }

    // Wrapper methods for public methods of SymfonyStyle

    public function ask($question, mixed $default = null)
    {
        return $this->symfonyStyle->ask($question, $default);
    }

    public function confirm($question, $default = false)
    {
        return $this->symfonyStyle->confirm($question, $default);
    }

    public function choice($question, $arr, $default = null)
    {
        return $this->symfonyStyle->choice($question, $arr, $default);
    }

    public function title(string $message): void {
        $this->symfonyStyle->title(message: $message);
    }

    public function section(string $message): void {
        $this->symfonyStyle->section(message: $message);
    }

    public function listing(array $elements): void {
        $this->symfonyStyle->listing(elements: $elements);
    }

    public function text(string $message): void {
        $this->symfonyStyle->text(message: $message);
    }

    public function comment(string $message): void {
        $this->symfonyStyle->comment(message: $message);
    }

    public function success(string $message): void {
        $this->symfonyStyle->success(message: $message);
    }

    public function error(string $message): void {
        $this->symfonyStyle->error(message: $message);
    }

    public function warning(string $message): void {
        $this->symfonyStyle->warning(message: $message);
    }

    public function caution(string $message): void {
        $this->symfonyStyle->caution(message: $message);
    }

}