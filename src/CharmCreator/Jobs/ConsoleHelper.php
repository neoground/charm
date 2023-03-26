<?php
/**
 * This file contains the console helper class.
 */

namespace Charm\CharmCreator\Jobs;

use Charm\Vivid\C;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
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
    protected SymfonyStyle $symfonyStyle;

    protected array $data;
    protected string $template;

    protected string $dir;
    protected string $namespace;
    protected string $name;

    protected $questionhelper;

    protected $types = [
        'model' => [
            'name' => 'model',
            'name_field' => 'MODEL_NAME',
            'namespace' => "App\\Models",
            'relative_dir' => 'Models',
            'file_suffix' => 'php'
        ],
        'controller' => [
            'name' => 'controller',
            'name_field' => 'CONTROLLER_NAME',
            'namespace' => "App\\Controllers",
            'relative_dir' => 'Controllers',
            'file_suffix' => 'php'
        ],
        'method' => [
            'name' => 'method',
            'name_field' => 'METHOD_NAME',
            'namespace' => "App\\Controllers",
            'relative_dir' => 'Controllers',
            'file_suffix' => 'php'
        ],
    ];

    public function __construct($input, $output, $questionhelper = false)
    {
        $this->types = C::Config()->get('CharmCreator#types:types', []);
        $this->setIO($input, $output);
        $this->initSymfonyStyle();
        if($questionhelper) {
            $this->questionhelper = $questionhelper;
        }
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

    public static function create($input, $output, $questionhelper, $type)
    {
        $x = new self($input, $output);
        $x->type = $x->types[$type];
        $x->questionhelper = $questionhelper;
        return $x;
    }

    public static function createAndHandle($input, $output, $questionhelper, $type, $header = false): bool|ConsoleHelper
    {
        $ch = self::create($input, $output, $questionhelper, $type);
        if($header) {
            $ch->outputCharmHeader();
            $ch->outputAsciiBox($header);
            $output->writeln(' ');
        }
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
        $available_templates = C::CharmCreator()->getAvailableTemplates($this->type['name']);

        // Extract template from text
        $tplstring = $this->choice('Select wanted template:', $available_templates, 'Default');
        $tplparts = explode("[", $tplstring);
        $this->template = rtrim($tplparts[1], "]");

        // Get custom fields from chosen tpl
        $tpl = C::CharmCreator()->getTemplate($this->type['name'], $this->template);

        // Extract YAML frontmatter
        $yaml = C::CharmCreator()->extract($tpl, 'yaml');
        $yaml_arr = Yaml::parse($yaml);

        $this->data = [];
        foreach($yaml_arr['fields'] as $name => $field) {
            if($field['type'] == 'input') {
                $default = null;
                if(array_key_exists('default', $field)) {
                    $default = $field['default'];
                }

                $this->data[$name] = $this->ask($field['name'] . ': ', $default);
            } elseif($field['type'] == 'choice') {
                $this->data[$name] = $this->choice($field['name'] . ': ', explode(",", $field['choices']), $field['default']);
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

    public function ask($question, mixed $default = null)
    {
        return $this->questionhelper->ask($this->input, $this->output, new Question($question, $default));
    }

    public function confirm($question, $default = false)
    {
        return $this->questionhelper->ask($this->input, $this->output, new ConfirmationQuestion($question, $default));
    }

    public function choice($question, $arr, $default = null)
    {
        $cq = new ChoiceQuestion($question, $arr, $default);
        return $this->questionhelper->ask($this->input, $this->output, $cq);
    }

    private function initSymfonyStyle(): void
    {
        $this->symfonyStyle = new SymfonyStyle($this->input, $this->output);
    }

    // Wrapper methods for public methods of SymfonyStyle
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