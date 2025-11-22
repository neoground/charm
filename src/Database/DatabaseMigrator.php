<?php
/**
 * This file contains the DatabaseMigrator class.
 */

namespace Charm\Database;

use Charm\Vivid\C;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class DatabaseMigrator
 *
 * Handling database migrations
 */
class DatabaseMigrator
{
    protected OutputInterface $output;

    protected array $synced_tables = [];

    /**
     * Constructor.
     *
     * @param OutputInterface|null $output set optional output for console output
     */
    public function __construct(OutputInterface|null $output = null)
    {
        if (!is_object($output)) {
            $output = new NullOutput();
        }

        $this->output = $output;

        $this->synced_tables = [
            'dropped' => [],
            'created' => [],
            'altered' => [],
            'ignored' => [],
            'processed' => 0,
        ];
    }

    /**
     * Run all database migrations of a module
     *
     * @param string      $method method to call (up / down)
     * @param string|null $file   optional filename (part) for single migration
     * @param string|null $module optional module name which should be migrated
     */
    public function runMigrations(string $method, ?string $file = null, ?string $module = "App"): void
    {
        // Get needed data from module
        $mod = C::get($module);

        // Defaults
        $path = C::Storage()->getAppPath() . DS . 'System' . DS . 'Migrations';
        $namespace = "\\App\\System\\Migrations";

        // Module specific
        if (method_exists($mod, 'getReflectionClass')) {
            $path = C::get($module)->getBaseDirectory() . DS . 'System' . DS . 'Migrations';

            $namespace = $mod->getReflectionClass()->getNamespaceName() . "\\System\\Migrations";
        }

        // Get all migration files
        if (!file_exists($path)) {
            $this->output->writeln('No migrations found for module: ' . $module, OutputInterface::VERBOSITY_VERBOSE);
        }

        $files = glob($path . DS . '*.php');

        // Descending order for down
        if ($method == 'down') {
            $files = array_reverse($files);
        }

        // Is $file set? Single migration?
        if (!empty($file)) {
            $this->output->writeln('ℹ Single migration of: ' . $file);

            // Remove every file which is not like the wanted name!
            foreach ($files as $k => $m) {
                if (!str_contains($m, $file)) {
                    // Remove from array
                    unset($files[$k]);
                }
            }
        }

        // Go through each php file and run migration
        foreach ($files as $m) {
            require_once($m);

            // Get class name based on filename without prefix and suffix
            $class_raw = basename($m, '.php');
            $class_parts = explode("_", $class_raw);

            // Remove all numeric prefixes
            while (is_numeric($class_parts[0])) {
                array_shift($class_parts);
            }

            // Create class name with namespace
            $class = $namespace . "\\" . implode("", array_map("ucfirst", $class_parts));

            if (!class_exists($class)) {

                // Append table suffix. Some people like that.
                $class = $class . 'Table';

                if (!class_exists($class)) {
                    // Still not found. Ignore.
                    $this->output->writeln('<error>❌ Invalid class in: ' . $class_raw
                        . '. Expected: ' . $class . '</error>');
                    continue;
                }
            }

            $migration = new $class;

            $this->output->writeln('Migrating: ' . $class);

            if ($method == 'up') {
                $migration->up();
            } else {
                $migration->down();
            }
        }

        // Run migrations in models
        $this->output->writeln('<info>:: Running ' . $method . ' migrations in models</info>', OutputInterface::VERBOSITY_VERBOSE);
        $this->runModelMigrations($method, $module);
    }

    /**
     * Run all database migrations of all app modules
     *
     * @param string $method method to call (up / down)
     */
    public function runAllMigrations(string $method): void
    {
        foreach (C::getAllAppModules() as $name => $module) {
            $this->output->writeln('<info>:: Running ' . $method . ' migrations for module: ' . $name . '</info>');

            $this->runMigrations($method, null, $name);
        }
    }

    /**
     * Run migrations of all model files of a module
     *
     * @param string $method migration method (up / down)
     * @param string $module wanted module
     */
    private function runModelMigrations(string $method, string $module = "App"): void
    {
        $this->output->writeln('Model Migration ' . $method . ': ' . $module, OutputInterface::VERBOSITY_VERBOSE);
        try {
            $mod = C::get($module);

            if (is_object($mod)) {
                $models_dir = $mod->getBaseDirectory() . DS . 'Models';
                $namespace = $mod->getReflectionClass()->getNamespaceName() . "\\Models";

                if (file_exists($models_dir)) {
                    $this->scanDirForModelMigration($models_dir, $method, $namespace);
                }
            }
        } catch (\Exception $e) {
            // Invalid module or file -> ignore.
        }

    }

    /**
     * Scan a dir for model migrations recursively and execute migrations
     *
     * @param string $dir       absolute path to dir
     * @param string $method    wanted method up / down
     * @param string $namespace namespace of classes in this dir
     */
    private function scanDirForModelMigration(string $dir, string $method, string $namespace)
    {
        foreach (C::Storage()->scanDir($dir) as $file) {
            $fullpath = $dir . DS . $file;
            $pathinfo = pathinfo($fullpath);

            $class = $namespace . "\\" . $pathinfo['filename'];

            if (is_dir($fullpath)) {
                $this->output->writeln('Checking sub directory: ' . $fullpath, OutputInterface::VERBOSITY_VERBOSE);
                $this->scanDirForModelMigration($fullpath, $method, $class);

                // This is a dir -> don't process. Go to next file.
                continue;
            }

            $this->output->writeln('Checking model file: ' . $fullpath, OutputInterface::VERBOSITY_VERBOSE);

            require_once($fullpath);

            if (method_exists($class, "getTableStructure")) {
                $this->synced_tables['processed']++;

                // TODO If class is already declared (in most cases due to classic migration), ignore this

                $obj = new $class;
                $tablename = $obj->getTable();
                $connection = $obj->getConnection();

                $schema_builder = C::Database()->getDatabaseConnection($connection)->getSchemaBuilder();

                if ($method == 'down') {

                    // DOWN migration
                    $this->output->writeln('🔥 Dropping table: ' . $tablename);
                    if (!$schema_builder->hasTable($tablename)) {
                        $schema_builder->drop($tablename);
                        $this->synced_tables['dropped'][] = $tablename;
                    } else {
                        $this->output->writeln('ℹ Ignoring non-existing table: ' . $tablename, OutputInterface::VERBOSITY_VERBOSE);
                        $this->synced_tables['ignored'][] = $tablename;
                    }

                } else {

                    // UP migration
                    if (!$schema_builder->hasTable($tablename)) {
                        $this->output->writeln('✨ Creating table: ' . $tablename);
                        $schema_builder->create($tablename, $obj::getTableStructure());
                        $this->synced_tables['created'][] = $tablename;
                    } else {
                        $this->output->writeln('ℹ Ignoring existing table: ' . $tablename, OutputInterface::VERBOSITY_VERBOSE);
                        $this->synced_tables['ignored'][] = $tablename;

                        // TODO Check if fields changed / added / removed and alter them
                    }

                }
            }

        }
    }

    /**
     * Output model migration stats via $this->output
     *
     * @return void
     */
    public function outputStats(): void
    {
        $counter_processed = $this->synced_tables['processed'];
        $counter_created = count($this->synced_tables['created']);
        $counter_dropped = count($this->synced_tables['dropped']);
        $counter_ignored = count($this->synced_tables['ignored']);

        $this->output->writeln('');
        $this->output->writeln('<info>:: SUMMARY</info>');
        $this->output->writeln('');
        $this->output->writeln('<info>' . $counter_processed . '</info> processed, <info>'
            . $counter_created . '</info> created, <info>'
            . $counter_dropped . '</info> dropped, <info>'
            . $counter_ignored . '</info> existing.');

        if ($counter_created > 0) {
            $this->output->writeln('');
            $this->output->writeln('<info>:: Created</info>');
            $this->output->writeln(implode(', ', $this->synced_tables['created']));
        }

        if ($counter_dropped > 0) {
            $this->output->writeln('');
            $this->output->writeln('<info>:: Dropped</info>');
            $this->output->writeln(implode(', ', $this->synced_tables['dropped']));
            $this->output->writeln('');

        }

        $this->output->writeln('');
    }
}