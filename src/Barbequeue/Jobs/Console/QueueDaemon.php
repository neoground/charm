<?php
/**
 * This file contains a console command.
 */

namespace Charm\Barbequeue\Jobs\Console;

use Charm\Bob\Command;
use Charm\Crown\Crown;
use Charm\Vivid\C;
use Symfony\Component\Console\Input\InputArgument;

/**
 * Class QueueDaemon
 *
 * Managing the queue daemon
 *
 * Please note that this daemon can only run on linux systems and
 * that your PHP needs ext-pcntl and ext-posix.
 *
 * This daemon will run a queue worker for the default queue and can
 * be used as a replacement for calling it via cron or similar solutions.
 *
 * Runs as a daemon but will output to stdout, but you can pipe that.
 * Or simply use the systemd service provided in charm-wireframe.
 */
class QueueDaemon extends Command
{
    private bool $running = true;

    /**
     * The configuration
     */
    protected function configure()
    {
        $this->setName("queue:daemon")
            ->setDescription('Starts or stops the queue daemon')
            ->addArgument('action', InputArgument::REQUIRED, 'The action to perform (start/stop/status)');
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
            case 'status':
                $this->infoDaemon();
                break;
            default:
                $this->io->writeln('<error>Invalid action. Use "start", "stop", or "status".</error>');
                return false;
        }

        return true;
    }

    /**
     * Get the absolute path to the pid lock file
     */
    private function getPidFilePath(): string
    {
        return C::Storage()->getCachePath() . DS . 'queue_daemon.lock';
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

        if (!extension_loaded('pcntl')) {
            $this->io->error('Required PHP extension "pcntl" is missing!');
            return;
        }

        if (!C::has('Queue')) {
            $this->io->error('Queue module is not installed!');
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
        if (!extension_loaded('pcntl')) {
            $this->io->error('Required PHP extension "pcntl" is missing!');
            return;
        }

        // TODO Allow a custom queue name
        $name = 'default';

        // Set up signal handlers
        pcntl_signal(SIGTERM, [$this, 'signalHandler']);
        pcntl_signal(SIGINT, [$this, 'signalHandler']);

        while ($this->running) {
            $start_time = time();
            if (date('s', $start_time) === '00') {
                // Run queue
                $this->io->writeln('[' . date('H:i:s', $start_time) . '] Running queue worker');

                C::Queue()->run($name);

                // Sleep for a second, so this can not terminate at second 0 and run multiple times
                sleep(1);
            }

            // Ensure the loop starts at the beginning of the next minute / at second 30
            do {
                usleep(500000); // Sleep for 0.5 second
                $now = time();
            } while (!in_array(date('s', $now), ['00', '30']));

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