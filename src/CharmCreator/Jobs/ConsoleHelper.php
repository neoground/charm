<?php
/**
 * This file contains the console helper class.
 */

namespace Charm\CharmCreator\Jobs;

use Charm\Bob\CommandHelper;
use Charm\Vivid\C;
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
    protected $ch;

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
        $this->ch = new CommandHelper($input, $output);
        if($questionhelper) {
            $this->questionhelper = $questionhelper;
        }
    }

    public function outputCharmHeader()
    {
        $this->ch->outputCharmHeader();
    }

    public function outputAsciiBox(string $text, string $align = 'left', bool $as_rainbow = false): void
    {
        $this->ch->outputAsciiBox($text, $align, $as_rainbow);
    }

    public function outputRainbow(string $text)
    {
        $this->ch->outputRainbow($text);
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
        $tplstring = $this->choice('Select wanted template', $available_templates, 0);
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

                $this->data[$name] = $this->ask($field['name'], $default);
            } elseif($field['type'] == 'choice') {
                $this->data[$name] = $this->choice($field['name'], explode(",", $field['choices']), $field['default']);
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
        return $this->ch->ask($question, $default);
    }

    public function askHidden($question)
    {
        return $this->ch->askHidden($question);
    }

    public function confirm($question, $default = false)
    {
        return $this->ch->confirm($question, $default);
    }

    public function choice($question, $arr, $default = null)
    {
        return $this->ch->choice($question, $arr, $default);
    }

    public function success(string $message): void {
        $this->ch->success(message: $message);
    }

    public function error(string $message): void {
        $this->ch->error(message: $message);
    }

}