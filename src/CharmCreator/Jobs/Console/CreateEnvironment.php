<?php
/**
 * This file contains a console command.
 */

namespace Charm\CharmCreator\Jobs\Console;

use Charm\Bob\Command;
use Charm\CharmCreator\ConsoleHelper;
use Charm\Vivid\C;
use Symfony\Component\Console\Input\InputArgument;

/**
 * Class CreateEnvironment
 *
 * Creating config environment
 */
class CreateEnvironment extends Command
{
    /**
     * The configuration
     */
    protected function configure()
    {
        $this->setName("c:env")
            ->setDescription("Creating a new config environment")
            ->setHelp('This command allows you to add a new config environment.')
            ->addArgument('name', InputArgument::REQUIRED, 'Optional name of new config environment');
    }

    /**
     * The execution
     *
     * @return bool
     */
    public function main(): bool
    {
        $ch = new ConsoleHelper($this->input, $this->output, $this->getHelper('question'));

        $name = $this->io->getArgument('name');
        if (empty($name)) {
            $name = $ch->ask('Name of environment');
        }

        $this->io->writeln(sprintf('Creating environment "%s"...', $name));

        $envFolder = C::Storage()->getAppPath() . DS . 'Config' . DS . 'Environments' . DS . $name;

        // Check if environment already exists
        if (is_dir($envFolder)) {
            $this->io->writeln('<error>❌ Environment ' . $name . ' already exists.</error>');
            return false;
        }

        // Create environment folder and copy main.yaml and connections.yaml files
        C::Storage()->createDirectoriesIfNotExisting($envFolder);
        $this->io->writeln('✅ Created environment folder');

        $mainFile = $envFolder . DS . 'main.yaml';
        $connectionsFile = $envFolder . DS . 'connections.yaml';

        // Set main config
        $data = [
            'ENVIRONMENT_NAME' => $name,
            'BASE_PATH' => C::Storage()->getBasePath(),
            'DEBUG_MODE' => $ch->choice('Enable dev and debug mode?', ['false', 'true'], 1),
            'ERROR_STYLE' => $ch->choice('How should errors be returned?', ['default', 'view', 'json', 'exception'], 0),
            'BASE_URL' => $ch->ask('URL to app index (used as fallback)'),
        ];

        C::CharmCreator()->createFile('config', $mainFile, $data, 'main_env');

        // Set connections config
        $data = [
            ...$data,
            'ENVIRONMENT_NAME' => $name,
            // Defaults
            'DB_ENABLED' => 'false',
            'DB_DRIVER' => 'mysql',
            'DB_DATABASE' => 'charm',
            'DB_HOST' => 'localhost',
            'DB_USER' => '',
            'DB_PASS' => '',
            'REDIS_ENABLED' => 'false',
            'REDIS_HOST' => '127.0.0.1',
            'REDIS_PORT' => '6379',
            'REDIS_PASS' => '',
        ];

        if ($ch->confirm('Add a database connection? y/n')) {
            $data = [
                ...$data,
                'DB_ENABLED' => 'true',
                'DB_DRIVER' => $ch->choice('Select the database driver', ['mysql', 'pgsql', 'sqlite', 'sqlsrv'], 0),
                'DB_DATABASE' => $ch->ask('Database name'),
                'DB_HOST' => $ch->ask('Database hostname (localhost)', 'localhost'),
                'DB_USER' => $ch->ask('Database username'),
                'DB_PASS' => $ch->askHidden('Database password'),
            ];
        }

        if ($ch->confirm('Add a redis connection? y/n')) {
            $data = [
                ...$data,
                'REDIS_ENABLED' => 'true',
                'REDIS_HOST' => $ch->ask('Redis hostname (127.0.0.1)', '127.0.0.1'),
                'REDIS_PORT' => $ch->ask('Redis port (6379)', '6379'),
                'REDIS_PASS' => $ch->askHidden('Redis password'),
            ];
        }

        C::CharmCreator()->createFile('config', $connectionsFile, $data, 'connections_env');

        $this->io->writeln(sprintf('✅ Environment "%s" created and config files updated', $name));

        // Check if the current environment is different from the new environment
        $envFile = C::Storage()->getAppPath() . DS . 'app.env';
        if (!file_exists($envFile)) {
            // No environment yet -> use this as default
            file_put_contents($envFile, $name);
            $this->io->writeln(sprintf('✅ Environment changed to "%s"', $name));
        } else {
            $currentEnv = file_exists($envFile) ? trim(file_get_contents($envFile)) : 'dev';
            if ($currentEnv !== $name) {
                $answer = $ch->choice(sprintf('Current environment is "%s". Change to "%s"?', $currentEnv, $name),
                    ['yes', 'no'],
                    0);

                if ($answer === 'yes') {
                    file_put_contents($envFile, 'ENVIRONMENT=' . $name);
                    $this->io->writeln(sprintf('✅ Environment changed to "%s"', $name));
                } else {
                    $this->io->writeln('Environment not changed');
                }
            }
        }

        $this->io->writeln('');
        $this->io->success('✅ Created config environment ' . $name);
        $this->io->writeln('');

        return true;
    }
}