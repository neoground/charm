<?php
/**
 * This file contains a console command.
 */

namespace Charm\Crown\Jobs\Console;

use Charm\Bob\Command;
use Charm\Crown\Crown;
use Charm\Vivid\C;
use Symfony\Component\Console\Input\InputArgument;

/**
 * Class CronDaemon
 *
 * Managing the cron daemon
 *
 * Please note that this daemon can only run on linux systems and
 * that your PHP needs ext-pcntl and ext-posix.
 *
 * This daemon will run cron:run each minute at second zero and can
 * be used as a replacement for calling it via cron or similar solutions.
 *
 * Runs as a daemon but will output to stdout, but you can pipe that.
 * Or simply use the systemd service provided in charm-wireframe.
 */
class CronDaemon extends Command
{
    private bool $running = true;

    /**
     * The configuration
     */
    protected function configure()
    {
        $this->setName("cron:daemon")
            ->setDescription('Starts or stops the cron daemon')
            ->addArgument('action', InputArgument::REQUIRED, 'The action to perform (start/stop/info)');
    }

    /**
     * The execution
     *
     * @return bool
     */
    public function main(): bool
    {
        $action = $this->io->getArgument('action');

        switch ($action) {
            case 'start':
                $this->startDaemon();
                break;
            case 'stop':
                $this->stopDaemon();
                break;
            case 'info':
                $this->infoDaemon();
                break;
            default:
                $this->io->writeln('<error>Invalid action. Use "start", "stop", or "info".</error>');
                return false;
        }

        return true;
    }

    /**
     * Get the absolute path to the pid lock file
     */
    private function getPidFilePath(): string
    {
        return C::Storage()->getCachePath() . DS . 'cron_daemon.lock';
    }

    /**
     * Starts the daemon if it is not already running.
     */
    private function startDaemon(): void
    {
        $pidfile = $this->getPidFilePath();
        if (file_exists($pidfile)) {
            $this->io->writeln('<error>👻 Daemon is already running.</error>');
            return;
        }

        $pid = pcntl_fork();

        if ($pid == -1) {
            $this->io->writeln('<error>❌ Could not start daemon.</error>');
        } else if ($pid) {
            // Parent process: save the PID and exit
            C::Storage()->createDirectoriesIfNotExisting(dirname($pidfile));
            file_put_contents($pidfile, $pid);
            $this->io->writeln('<info>👻 Daemon started.</info>');
            exit(0);
        } else {
            // Child process: run the daemon
            $this->runDaemon();
        }
    }

    /**
     * Stops the daemon if it is running.
     */
    private function stopDaemon(): void
    {
        $pidfile = $this->getPidFilePath();

        if (!file_exists($pidfile)) {
            $this->io->writeln('<error>❌ Daemon is not running.</error>');
            return;
        }

        $pid = file_get_contents($pidfile);
        posix_kill($pid, SIGTERM);
        unlink($pidfile);
        $this->io->writeln('<info>🛑 Daemon stopped.</info>');
    }

    /**
     * Displays information about the running daemon.
     */
    private function infoDaemon(): void
    {
        $pidfile = $this->getPidFilePath();
        if (!file_exists($pidfile)) {
            $this->io->writeln('❌ Daemon is not running.');
            return;
        }

        $pid = file_get_contents($pidfile);
        $this->io->writeln("<info>👻 Daemon is running with PID: $pid</info>");
    }

    /**
     * Runs the daemon, executing tasks at their scheduled times (running cron:run each minute at second zero).
     */
    private function runDaemon(): void
    {
        // Set up signal handlers
        pcntl_signal(SIGTERM, [$this, 'signalHandler']);
        pcntl_signal(SIGINT, [$this, 'signalHandler']);

        while ($this->running) {
            $start_time = time();
            if (date('s', $start_time) === '00') {
                // Run cron commands
                $this->io->writeln('[' . date('H:i:s', $start_time) . '] Running cron jobs');
                /** @var Crown $c */
                $c = C::Crown();
                $c->setConsoleOutput($this->output);
                $c->run();

                // Sleep for a second, so this can not terminate at second 0 and run multiple times
                sleep(1);
            }

            // Ensure the loop starts at the beginning of the next minute
            do {
                usleep(500000); // Sleep for 0.5 second
                $now = time();
            } while (date('s', $now) !== '00');

            pcntl_signal_dispatch();
        }

        $this->io->writeln('<info>🛑 Stopping daemon</info>');
    }

    /**
     * Handles termination signals to gracefully stop the daemon.
     *
     * @param int $signal
     */
    public function signalHandler(int $signal): void
    {
        switch ($signal) {
            case SIGTERM:
            case SIGINT:
                $this->running = false;
                break;
        }
    }
}