<?php
/**
 * This file contains the CommandHelper class
 */

namespace Charm\Bob;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class CommandHelper
 *
 * Providing helpful functions for easier, better and more consistent
 * console handling and outputting.
 *
 * Mostly for pretty output, but also for simple inputs and common tasks.
 *
 * Also allows access to SymfonyStyle directly.
 */
class CommandHelper
{
    protected InputInterface $input;
    protected OutputInterface $output;
    protected SymfonyStyle $symfonyStyle;

    public function __construct(InputInterface $input = null, OutputInterface $output = null)
    {
        if(is_object($input)) {
            $this->setInput($input);
        }
        if(is_object($output)) {
            $this->setOutput($output);
        }
        $this->initStyleInstance();
    }

    /**
     * Sets the input object for the class.
     *
     * @param InputInterface $input The input object to be set.
     *
     * @return static Returns the current instance of the class.
     */
    public function setInput(InputInterface $input): static
    {
        $this->input = $input;
        return $this;
    }

    /**
     * Sets the output object for the class.
     *
     * @param OutputInterface $output The output object to be set.
     *
     * @return static Returns the current instance of the class.
     */
    public function setOutput(OutputInterface $output): static
    {
        $this->output = $output;
        return $this;
    }

    /**
     * Initializes the SymfonyStyle instance if both the input and output objects are set.
     *
     * @return static Returns the current instance of the class.
     */
    public function initStyleInstance(): static
    {
        if(is_object($this->input) && is_object($this->output)) {
            $this->symfonyStyle = new SymfonyStyle($this->input, $this->output);
        }
        return $this;
    }

    /**
     * Returns the instance of the SymfonyStyle object.
     *
     * This method returns the instance of the SymfonyStyle object which is used for console output formatting
     * and interaction. This object is stored in the `$symfonyStyle` property of the class.
     *
     * @return SymfonyStyle The instance of the SymfonyStyle object.
     */
    public function getStyleInstance(): SymfonyStyle
    {
        return $this->symfonyStyle;
    }

    public function outputCharmHeader()
    {
        $logo = "\n";
        $logo .= '        __                       ' . "\n";
        $logo .= '  _____/ /_  ____ __________ ___ ' . "\n";
        $logo .= ' / ___/ __ \/ __ `/ ___/ __ `__ \\' . "\n";
        $logo .= '/ /__/ / / / /_/ / /  / / / / / /' . "\n";
        $logo .= '\___/_/ /_/\__,_/_/  /_/ /_/ /_/ ' . "\n";
        $logo .= "\n";

        $this->outputRainbow($logo);
    }

    /**
     * Generates an ASCII box with the given text.
     *
     * @param string $text       The text to be displayed inside the box.
     * @param string $align      The alignment of the text. Valid values are 'left', 'center', and 'right'. Defaults to
     *                           'left'.
     * @param bool   $as_rainbow Determines whether the box should be outputted as a rainbow. Defaults to false.
     *
     * @return void
     */
    public function outputAsciiBox(string $text, string $align = 'left', bool $as_rainbow = false): void
    {
        $width = 42;
        $inner_width = $width - 4;

        $border_str = "\033[36m" . '+' . str_repeat('-', $width - 2) . '+' . "\033[37m";

        $output_str = '';

        $output_str .= $border_str . "\n";

        // Format lines
        $lines = explode("\n", $text);
        $arr = [];
        foreach ($lines as $line) {
            $arr += str_split($line, $inner_width);
        }

        foreach ($arr as $renderline) {
            if (strlen($renderline) < $inner_width) {
                // Fill up to inner width
                $renderline = str_pad($renderline, $inner_width, ' ');
            }
            $output_str .= "\033[36m" . '| ' . "\033[37m" . $renderline . "\033[36m" . ' |' . "\n\033[37m";
        }

        $output_str .= $border_str . "\n";

        if ($as_rainbow) {
            $this->outputRainbow($output_str);
        } else {
            foreach (explode("\n", $output_str) as $row) {
                $this->writeln($row);
            }
        }
    }

    /**
     * Outputs the provided text in various rainbow colors.
     *
     * @param string $text The text to be outputted.
     *
     * @return void
     */
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
            while ($new_color == $last_color) {
                $new_color = rand(0, count($colors) - 1);
            }
            $last_color = $new_color;
            $this->writeln($colors[$new_color] . $line . "\033[37m");
        }
    }

    /**
     * Asks the user a question and returns their answer.
     *
     * This method calls the SymfonyStyle ask method to ask the user a question and returns their answer.
     * The question is specified as a string and can be customized.
     * An optional default value can be provided to pre-fill the answer.
     * A callable validator can be passed to validate and sanitize the user's input.
     *
     * @param string        $question  The question to ask the user.
     * @param mixed         $default   The default value for the answer (optional).
     * @param callable|null $validator A callback function to validate the user's input (optional).
     *
     * @return mixed The user's input.
     */
    public function ask(string $question, mixed $default = null, callable $validator = null): mixed
    {
        return $this->symfonyStyle->ask($question, $default, $validator);
    }


    /**
     * Asks the user for input without displaying it on the screen.
     *
     * This method takes a question as input and prompts the user to provide a hidden input. The input is not displayed
     * on the screen, providing a way to securely gather sensitive information. The method internally uses the
     * SymfonyStyle askHidden method.
     *
     * @param string        $question  The question to prompt the user.
     * @param callable|null $validator (Optional) A callable that validates the user input. Should return true if input
     *                                 is valid, false otherwise.
     *
     * @return mixed   The user's input.
     */
    public function askHidden(string $question, callable $validator = null): mixed
    {
        return $this->symfonyStyle->askHidden($question, $validator);
    }

    /**
     * Confirm or prompt the user with a question.
     *
     * The "confirm" method is used to display a question to the user and prompt for a yes or no response.
     * It uses the SymfonyStyle class to handle user input and returns a boolean value indicating the user's response.
     *
     * @param string $question The question to display to the user.
     * @param bool   $default  The default answer to the question (default is false).
     *
     * @return bool True if the user answers yes, false if the user answers no.
     */
    public function confirm(string $question, bool $default = false): bool
    {
        return $this->symfonyStyle->confirm($question, $default);
    }

    /**
     * Prompts the user to select an option from a list of choices.
     *
     * @param string     $question The question to ask the user.
     * @param array      $arr      The array of available choices.
     * @param mixed|null $default  The default value to return if the user does not make a choice.
     *
     * @return mixed The user's selected choice or the default value.
     */
    public function choice(string $question, array $arr, mixed $default = null, $multiselect = false): mixed
    {
        return $this->symfonyStyle->choice($question, $arr, $default, $multiselect);
    }

    /**
     * Displays the main title with the given content.
     *
     * This method takes a string message and displays it as the main title using the SymfonyStyle section method.
     *
     * @param string $message The message to display as the title.
     *
     * @return void
     */
    public function title(string $message): void
    {
        $this->symfonyStyle->title(message: $message);
    }

    /**
     * Displays a section title with the given content.
     *
     * This method takes a string message and displays it as a section title using the SymfonyStyle section method.
     *
     * @param string $message The message to display as the title.
     *
     * @return void
     */
    public function section(string $message): void
    {
        $this->symfonyStyle->section(message: $message);
    }

    /**
     * Displays a list of elements.
     *
     * This method takes an array of elements and displays them as a list using the SymfonyStyle listing method.
     *
     * @param array $elements The elements to be listed.
     *
     * @return void
     */
    public function listing(array $elements): void
    {
        $this->symfonyStyle->listing(elements: $elements);
    }

    /**
     * Displays text in the console.
     *
     * This method takes a string or an array and displays it as text in the console
     * using the SymfonyStyle text method.
     *
     * @param string|array $message The message to be displayed. It can be either a string or an array.
     *
     * @return void
     */
    public function text(string|array $message): void
    {
        $this->symfonyStyle->text(message: $message);
    }

    /**
     * Displays a comment message.
     *
     * This method takes a string or an array as a message and displays it as a
     * comment using the SymfonyStyle comment method.
     *
     * @param string|array $message The message to be displayed.
     *
     * @return void
     */
    public function comment(string|array $message): void
    {
        $this->symfonyStyle->comment(message: $message);
    }

    /**
     * Displays a success message.
     *
     * This method takes a string or an array as a message and displays it as a
     * success admonition using the SymfonyStyle comment method.
     *
     * @param string|array $message The message to be displayed.
     *
     * @return void
     */
    public function success(string|array $message): void
    {
        $this->symfonyStyle->success(message: $message);
    }

    /**
     * Displays an error message.
     *
     * This method takes a string or an array as a message and displays it as an
     * error admonition using the SymfonyStyle comment method.
     *
     * @param string|array $message The message to be displayed.
     *
     * @return void
     */
    public function error(string|array $message): void
    {
        $this->symfonyStyle->error(message: $message);
    }

    /**
     * Displays a warning message.
     *
     * This method takes a string or an array as a message and displays it as a
     * warning admonition using the SymfonyStyle comment method.
     *
     * @param string|array $message The message to be displayed.
     *
     * @return void
     */
    public function warning(string|array $message): void
    {
        $this->symfonyStyle->warning(message: $message);
    }

    /**
     * Displays a caution message.
     *
     * This method takes a string or an array as a message and displays it as a
     * caution admonition using the SymfonyStyle comment method.
     *
     * @param string|array $message The message to be displayed.
     *
     * @return void
     */
    public function caution(string|array $message): void
    {
        $this->symfonyStyle->caution(message: $message);
    }

    /**
     * Displays an info message.
     *
     * This method takes a string or an array as a message and displays it as an
     * info admonition using the SymfonyStyle comment method.
     *
     * @param string|array $message The message to be displayed.
     *
     * @return void
     */
    public function info(string|array $message): void
    {
        $this->symfonyStyle->info(message: $message);
    }

    /**
     * Inserts new line(s).
     *
     * This method inserts a specified number of new lines into the output using the SymfonyStyle newLine method.
     *
     * @param int $amount The number of new lines to insert. Defaults to 1 if not specified.
     *
     * @return void
     */
    public function newLine(int $amount = 1): void
    {
        $this->symfonyStyle->newLine($amount);
    }

    /**
     * Display a table to the user.
     *
     * The "table" method is used to display a table to the user with given headers and rows.
     * It uses the SymfonyStyle class to handle the formatting and output of the table.
     *
     * @param array $headers An array of strings representing the column headers of the table.
     * @param array $rows    An array of arrays representing the rows of the table.
     *                       Each inner array represents a row in the table and contains values for each column.
     *                       The number of values in each inner array should match the number of headers.
     *                       If the number of values in a row is less than the number of headers,
     *                       the remaining cells will be filled with empty strings.
     *
     * @return void
     */
    public function table(array $headers, array $rows): void
    {
        $this->symfonyStyle->table($headers, $rows);
    }

    /**
     * Displays a table in horizontal format.
     *
     * This method takes an array of headers and an array of rows and displays them in a horizontal table format
     * using the SymfonyStyle horizontalTable method.
     *
     * @param array $headers The headers of the table.
     * @param array $rows    The rows of the table.
     *
     * @return void
     */
    public function horizontalTable(array $headers, array $rows): void
    {
        $this->symfonyStyle->horizontalTable($headers, $rows);
    }

    /**
     * Creates and returns a new ProgressBar instance.
     *
     * This method creates and returns a new ProgressBar instance using the given
     * Symfony\Component\Console\Output\OutputInterface instance and the maximum value.
     * If the maximum value is not provided, it defaults to 0.
     *
     * @param int $max The maximum value of the progress bar. Defaults to 0 if not provided.
     *
     * @return ProgressBar The created ProgressBar instance.
     */
    public function progressBar(int $max = 0): ProgressBar
    {
        return new ProgressBar($this->output, $max);
    }

    /**
     * Displays a heading (level 1) in the console output.
     *
     * This method takes a string content and displays it as a heading in the console output.
     * The content is surrounded by "::" and is colored in bright white and cyan.
     *
     * @param string $content The content of the heading.
     *
     * @return void
     */
    public function heading1(string $content): void
    {
        $this->writeln('');
        $this->writeln('<fg=bright-cyan>::</> <fg=bright-white;options=bold>' . $content . '</>');
    }

    /**
     * Displays a level 2 heading.
     *
     * This method takes a string content and displays it as a level 2 heading by writing it to the output
     * using the SymfonyStyle writeln method.
     *
     * @param string $content The content of the heading.
     *
     * @return void
     */
    public function heading2(string $content): void
    {
        $this->writeln('<fg=cyan>::</> <fg=white>' . $content . '</>');
    }

    /**
     * Displays a heading in the third level format.
     *
     * This method takes a string content and displays it as a heading in the third level format
     * using the output's writeln method.
     *
     * @param string $content The content of the heading.
     *
     * @return void
     */
    public function heading3(string $content): void
    {
        $this->writeln('<fg=green>=></> <fg=white>' . $content . '</>');
    }

    /**
     * Writes messages to the output and adds a new line at the end.
     *
     * This method takes a single message or an array of messages and writes them to the output
     * using the SymfonyStyle writeln method. An optional parameter $options can be provided to specify
     * the output format.
     *
     * @param string|string[] $messages The message or array of messages to write.
     * @param int             $options  The output format options. Defaults to OutputInterface::OUTPUT_NORMAL.
     *
     * @return void
     */
    public function writeln(array|string $messages, int $options = OutputInterface::OUTPUT_NORMAL): void
    {
        $this->output->writeln($messages, $options);
    }

    /**
     * Retrieves the value of the specified input argument.
     *
     * @param string $name The name of the argument to retrieve.
     *
     * @return mixed The value of the specified argument.
     */
    public function getArgument(string $name): mixed
    {
        return $this->input->getArgument($name);
    }

    /**
     * Retrieves all input arguments.
     *
     * @return array Returns an array containing all input arguments, indexed by their names.
     */
    public function getArguments(): array
    {
        return $this->input->getArguments();
    }

    /**
     * Checks if the specified input argument exists.
     *
     * @param string $name The name of the argument to check.
     *
     * @return bool True if the argument exists, false otherwise.
     */
    public function hasArgument(string $name): bool
    {
        return $this->input->hasArgument($name);
    }

    /**
     * Retrieves the value of the specified command-line option.
     *
     * @param string $name The name of the option to retrieve.
     *
     * @return mixed The value of the specified option.
     */
    public function getOption(string $name): mixed
    {
        return $this->input->getOption($name);
    }

    /**
     * Retrieves the command-line options from the input object.
     *
     * @return array The options retrieved from the input object.
     */
    public function getOptions(): array
    {
        return $this->input->getOptions();
    }

    /**
     * Checks if a command-line option with the given name exists.
     *
     * @param string $name The name of the option to check.
     *
     * @return bool True if the option exists, false otherwise.
     */
    public function hasOption(string $name): bool
    {
        return $this->input->hasOption($name);
    }
}