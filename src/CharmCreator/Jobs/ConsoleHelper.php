<?php
/**
 * This file contains the console helper class.
 */

namespace Charm\CharmCreator\Jobs;

use Charm\Vivid\C;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Yaml\Yaml;

/**
 * Class ConsoleHelper
 *
 * Helping the console commands in this module
 */
class ConsoleHelper
{
    protected array $type = [];

    protected $input;
    protected $output;

    protected array $data;
    protected string $template;

    protected string $dir;
    protected string $namespace;
    protected string $name;

    protected $questionhelper;

    protected array $types = [];

    public function __construct($input, $output)
    {
        $this->types = C::Config()->get('CharmCreator#types:types', []);
        $this->setIO($input, $output);
    }

    public function outputCharmHeader()
    {
        $this->output->writeln(' ');
        $this->output->writeln(' ██████ ██   ██  █████  ██████  ███    ███ ');
        $this->output->writeln('██      ██   ██ ██   ██ ██   ██ ████  ████ ');
        $this->output->writeln('██      ███████ ███████ ██████  ██ ████ ██ ');
        $this->output->writeln('██      ██   ██ ██   ██ ██   ██ ██  ██  ██ ');
        $this->output->writeln(' ██████ ██   ██ ██   ██ ██   ██ ██      ██ ');
        $this->output->writeln(' ');
    }

    public function outputAsciiBox(string $text, string $align = 'left'): void
    {
        // Determine the box width
        $boxWidth = 60;

        // Split the text into words
        $words = explode(' ', $text);

        // Initialize the output string
        $outputString = '';

        // Initialize the line length
        $lineLength = 0;

        // Loop through the words and build the lines
        foreach ($words as $word) {
            // Add a space before the word if necessary
            if ($lineLength > 0) {
                $word = ' ' . $word;
            }

            // Add the word to the current line
            $outputLine = $outputString . $word;

            // Determine the length of the line
            $lineLength = strlen($outputLine);

            // If the line is too long, wrap it to the next line
            if ($lineLength > $boxWidth) {
                $this->output->writeln(sprintf('| %-57s |', $outputString));
                $outputString = $word;
                $lineLength = strlen($outputString);
            } else {
                $outputString = $outputLine;
            }
        }

        $this->output->writeln('+----------------------------------------+');

        // Output the final line
        if (!empty($outputString)) {
            $this->output->writeln(sprintf('| %-57s |', $outputString));
        }

        $this->output->writeln('+----------------------------------------+');
    }

    public static function create($input, $output, $questionhelper, $type)
    {
        $x = new self($input, $output);
        $x->type = $x->types[$type];
        $x->questionhelper = $questionhelper;
        return $x;
    }

    public static function createAndHandle($input, $output, $questionhelper, $type): bool|ConsoleHelper
    {
        $ch = self::create($input, $output, $questionhelper, $type);
        $ch->askForTemplateAndData();

        $data = $ch->getData();
        $type = $ch->getType();

        $name = $data[$type['name_field']];
        if (empty($name)) {
            $output->writeln('<error>Error: No name provided!</error>');
            return false;
        }

        $ch->setDirAndNamespace();

        $ch->setData([
            ...$data,
            'TPL_NAMESPACE' => $ch->getNamespace(),
            $type['name_field'] => $ch->getName(),
        ]);

        if (file_exists($ch->getAbsolutePath())) {
            $output->writeln('<error>Error: File already existing!</error>');
            return false;
        }

        C::Storage()->createDirectoriesIfNotExisting($ch->getDir());

        return $ch;
    }

    public function askForTemplateAndData(): void
    {
        // Ask for template
        // TODO: Display template name instead of filename in selection
        $available_templates = C::CharmCreator()->getAvailableTemplates($this->type['name']);
        $this->template = $this->choice($this->input, $this->output, 'Select wanted template:', $available_templates, 'Default');

        // Get custom fields from chosen tpl
        $tpl = C::CharmCreator()->getTemplate($this->type['name'], $this->template);

        // Extract YAML frontmatter
        $parts = explode("---\n", $tpl);
        $yaml = $parts[1];

        $yaml_arr = Yaml::parse($yaml);

        $this->data = [];
        foreach($yaml_arr['fields'] as $name => $field) {
            if($field['type'] == 'input') {
                $this->data[$name] = $this->ask($this->input, $this->output, $field['name'] . ': ');
            } elseif($field['type'] == 'choice') {
                $this->data[$name] = $this->choice($this->input, $this->output, $field['name'] . ': ', explode(",", $field['choices']), $field['default']);
            }
        }
    }

    public function setDirAndNamespace()
    {
        $namespace = $this->type['namespace'];
        $dir = C::Storage()->getAppPath() . DS . $this->type['relative_dir'];
        $name = $this->data[$this->type['name_field']];

        if (str_contains($name, '.')) {
            $parts = explode('.', $name);

            // Name is last part
            $name = array_pop($parts);

            // Use rest parts for namespace
            $dir .= DS . implode(DS, $parts);
            $namespace .= "\\" . implode("\\", $parts);
        }

        $this->namespace = $namespace;
        $this->dir = $dir;
        $this->name = $name;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getTemplate() : string
    {
        return $this->template;
    }

    public function getDir() : string
    {
        return $this->dir;
    }

    public function getNamespace() : string
    {
        return $this->namespace;
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function getType() : array
    {
        return $this->type;
    }

    public function getFilename() : string
    {
        return $this->getName() . '.' . ltrim($this->type['file_suffix'], '.');
    }

    public function getAbsolutePath() : string
    {
        return $this->getDir() . DS . $this->getFilename();
    }

    public function setData($data): void
    {
        $this->data = $data;
    }

    private function setIO($input, $output) : void
    {
        $this->input = $input;
        $this->output = $output;
    }

    public function ask($input, $output, $question)
    {
        return $this->questionhelper->ask($input, $output, new Question($question));
    }

    public function choice($input, $output, $question, $arr, $default = null)
    {
        $cq = new ChoiceQuestion($question, $arr, $default);
        return $this->questionhelper->ask($input, $output, $cq);
    }

}