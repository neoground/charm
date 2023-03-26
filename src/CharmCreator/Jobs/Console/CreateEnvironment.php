<?php
/**
 * This file contains a console command.
 */

namespace Charm\CharmCreator\Jobs\Console;

use Charm\CharmCreator\Jobs\ConsoleHelper;
use Charm\Vivid\C;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

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
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $ch = new ConsoleHelper($input, $output, $this->getHelper('question'));

        $name = $input->getArgument('name');
        if(empty($name)) {
            $name = $ch->ask('Name of environment: ');
        }

        $output->writeln(sprintf('Creating environment "%s"...', $name));

        $envFolder = C::Storage()->getAppPath() . DS .'Config' . DS . 'Environments' . DS . $name;

        // Check if environment already exists
        if (is_dir($envFolder)) {
            $output->writeln(sprintf('Environment "%s" already exists.', $name));
            return self::FAILURE;
        }

        // Create environment folder and copy main.yaml and connections.yaml files
        C::Storage()->createDirectoriesIfNotExisting($envFolder);
        $output->writeln(sprintf('Created environment folder "%s".', $envFolder));

        $mainFile = $envFolder . DS . 'main.yaml';
        $connectionsFile = $envFolder . DS . 'connections.yaml';

        // Set main config
        $data = [
            'ENVIRONMENT_NAME' => $name,
            'BASE_PATH' => C::Storage()->getBasePath(),
            'DEBUG_MODE' => $ch->choice('Enable dev and debug mode?', ['false', 'true'], 1),
            'ERROR_STYLE' => $ch->choice('How should errors be returned?', ['default', 'view', 'json', 'exception'], 0),
            'BASE_URL' => $ch->ask('URL to app index (used as fallback): '),
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

        if($ch->confirm('Add a database connection? y/n: ')) {
            $data = [
                ...$data,
                'DATABASE_ENABLED' => 'true',
                'DB_DRIVER' => $ch->choice('Select the database driver: ', ['mysql', 'pgsql', 'sqlite', 'sqlsrv'], 0),
                'DB_DATABASE' => $ch->ask('Database name: '),
                'DB_HOST' => $ch->ask('Database hostname: ', 'localhost'),
                'DB_USER' => $ch->ask('Database username: '),
                'DB_PASS' => $ch->ask('Database password: '),
            ];
        }

        if($ch->confirm('Add a redis connection? y/n: ')) {
            $data = [
                ...$data,
                'REDIS_ENABLED' => 'true',
                'REDIS_HOST' => $ch->ask('Redis hostname: ', '127.0.0.1'),
                'REDIS_PORT' => $ch->ask('Redis port: ', '6379'),
                'REDIS_PASS' => $ch->ask('Redis password: '),
            ];
        }

        C::CharmCreator()->createFile('config', $connectionsFile, $data, 'connections_env');

        $output->writeln(sprintf('✅ Environment "%s" created and config files updated.', $name));

        // Check if the current environment is different from the new environment
        $envFile = C::Storage()->getAppPath() . DS . 'app.env';
        if(!file_exists($envFile)) {
            // No environment yet -> use this as default
            file_put_contents($envFile, $name);
            $output->writeln(sprintf('✅ Environment changed to "%s".', $name));
        } else {
            $currentEnv = file_exists($envFile) ? trim(file_get_contents($envFile)) : 'dev';
            if ($currentEnv !== $name) {
                $answer = $ch->choice( sprintf('Current environment is "%s". Change to "%s"?', $currentEnv, $name),
                    ['yes', 'no'],
                    0);

                if ($answer === 'yes') {
                    file_put_contents($envFile, $name);
                    $output->writeln(sprintf('✅ Environment changed to "%s".', $name));
                } else {
                    $output->writeln('Environment not changed.');
                }
            }
        }

        $output->writeln(' ');
        $ch->success('✅ Created config environment ' . $name);
        $output->writeln(' ');

        return self::SUCCESS;
    }
}