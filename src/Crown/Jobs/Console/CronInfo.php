<?php
/**
 * This file contains a console command.
 */

namespace Charm\Crown\Jobs\Console;

use Carbon\Carbon;
use Charm\Bob\Command;
use Charm\Crown\Cronjob;
use Charm\Vivid\C;
use Cron\CronExpression;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Process\Process;

/**
 * Class CronInfo
 *
 * Info about cron jobs and system integration
 */
class CronInfo extends Command
{

    /**
     * The configuration
     */
    protected function configure()
    {
        $this->setName("cron:info")
            ->setDescription("Info about cron jobs and system integration")
            ->addArgument('tool', InputArgument::OPTIONAL, 'Optional tool to use. Available: systemd');
    }

    /**
     * The execution
     *
     * @return bool
     */
    public function main(): bool
    {
        $tool = $this->io->getArgument('tool');

        // Get all cron jobs
        $cronjobs = C::Crown()->getAllCronJobs();
        $this->io->heading1('Loaded cron jobs');
        $this->io->newLine();

        $jobs = [];
        /** @var Cronjob $job */
        foreach ($cronjobs as $job) {
            $ce = new CronExpression($job->getExpression());
            $next_run = new Carbon($ce->getNextRunDate());
            $prev_run = new Carbon($ce->getPreviousRunDate());

            $jobs[] = [$job->getName() . "\n" . get_class($job) . "\n", $job->getExpression(),
                $prev_run->toDateTimeString() . "\n" . $next_run->toDateTimeString()];
        }

        $this->io->table(['Name & Class', 'Cron Expression', 'Last & Next Run'], $jobs);

        $this->io->heading1('System integration');
        $this->io->newLine();

        // Detect if system has systemd available
        if (file_exists('/run/systemd/system') || $tool == 'systemd') {
            // Systemd

            // Build service name and check if it exists
            $composer = C::Storage()->getBasePath() . DS . 'composer.json';
            if (!file_exists($composer)) {
                $this->io->error('File composer.json not existing! Aborting.');
                return false;
            }

            $cdata = json_decode(file_get_contents($composer), true);
            if (!is_array($cdata) || !array_key_exists('name', $cdata)) {
                $this->io->error('File composer.json is invalid or does not contain a valid name! Aborting.');
                return false;
            }

            $name = str_replace('/', '-', $cdata['name']);
            $service_filename = 'charm-' . $name . '.service';
            $systemd_dir = '/etc/systemd/system';

            if (file_exists($systemd_dir . DS . $service_filename)) {
                $this->io->success('✅ Detected valid systemd service file "' . $service_filename . '".');

                if ($tool == 'systemd') {
                    $this->io->writeln('Recreating systemd files');
                    $this->createSystemdFiles($name, $systemd_dir);

                    $this->io->writeln('To enable the new config, run:');
                    $this->io->writeln('<info>sudo systemctl daemon-reload</info>');
                }

                return true;
            } else {
                // Missing
                if ($this->io->confirm('Could not detect valid systemd service for ' . $name .
                    '. Should it be created?', true)) {
                    $this->createSystemdFiles($name, $systemd_dir);
                }

                $this->io->newLine();
                $this->io->writeln('To enable the service, please run:');
                $this->io->writeln('<info>sudo systemctl daemon-reload</info>');
                $this->io->writeln('<info>sudo systemctl enable --now ' . $service_filename . '</info>');
                $this->io->newLine();
            }

            $this->io->writeln('If you have any trouble with this systemd service, you can also try using cron instead.');

        } else {
            // Non-systemd
            $this->io->newLine();
            $this->io->writeln('Could not detect systemd on your host.');
            $this->io->writeln('Please make sure the cron:run command is called every minute. We recommend using cron.');
            $this->io->writeln('You can find sample service files for systemd, init, openrc and cron in your project\'s var/templates/Services.');
        }

        $this->io->newLine();
        $this->io->writeln('Add this to your crontab if you want to use cron, maybe adjust the user and php path:');
        $this->io->newLine();
        $this->io->writeln('<info>* * * * *   ' . get_current_user() . '   ' . PHP_BINARY . ' ' . C::Storage()->getBasePath() . DS . 'bob.php cron:run >> /dev/null 2>&1</info>');
        $this->io->newLine();

        return true;
    }

    private function createSystemdFiles(string $name, string $systemd_dir): bool
    {
        $service_filename = 'charm-' . $name . '.service';

        $service_path = C::Storage()->getVarPath() . DS . $service_filename;

        $user = $this->io->ask('Please enter the user who will call cron:run', get_current_user());

        $service_content = $this->getSystemdServiceFileContent($name, $user);

        if (file_put_contents($service_path, $service_content)) {
            $this->io->writeln('✅ Successfully created systemd service file.');
        } else {
            $this->io->error('Error while creating systemd service file! Aborting.');
            return false;
        }

        $this->io->newLine();
        if ($this->io->confirm('Should the files be linked in ' . $systemd_dir . '?')) {
            $this->linkSystemdFiles($service_path, $systemd_dir, 'charm-' . $name);
        } else {
            $this->io->writeln('Please link the systemd files manually via:');
            $this->io->writeln('<info>sudo ln -sf ' . $service_path . ' ' . $systemd_dir . DS . $service_filename . '</info>');
        }

        return true;
    }

    private function getSystemdServiceFileContent($name, $user): string
    {
        return "[Unit]
Description=Cron daemon for charm project " . $name . "
After=network.target

[Service]
Type=forking
User=" . $user . "
StandardOutput=journal
StandardError=journal
ExecStart=" . PHP_BINARY . " " . C::Storage()->getBasePath() . DS . 'bob.php' . " cron:daemon start
ExecStop=" . PHP_BINARY . " " . C::Storage()->getBasePath() . DS . 'bob.php' . " cron:daemon stop
Restart=always
KillSignal=SIGINT
SendSIGKILL=no

WantedBy=multi-user.target
";
    }

    private function linkSystemdFiles($service_path, $systemd_dir, $name): void
    {
        if ($this->linkSystemdFile($service_path, $systemd_dir . DS . $name . '.service')) {
            $this->io->writeln('✅ Successfully linked service file to systemd.');
        } else {
            $this->io->writeln('Please link the service file manually via:');
            $this->io->writeln('<info>sudo ln -sf ' . $service_path . ' ' . $systemd_dir . DS . $name . '.service</info>');
        }
    }

    private function linkSystemdFile($src, $dest)
    {
        $process = new Process(['ln', '-sf', $src, $dest]);
        $process->run();
        return $process->isSuccessful();
    }
}