<?php
/**
 * This file contains a console command.
 */

namespace Charm\Bob\Jobs\Console;

use Charm\Vivid\Charm;
use Charm\Vivid\PathFinder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CacheClearCommand
 *
 * Handling general cache clearing
 *
 * @package Charm\Bob\Commands
 */
class CacheClearCommand extends Command
{

    /**
     * The configuration
     */
    protected function configure()
    {
        $this->setName("cache:clear")
            ->setDescription("Clear all caches");
    }

    /**
     * The execution
     *
     * @param InputInterface   $input
     * @param OutputInterface  $output
     *
     * @return bool
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // AppStorage
        $output->writeln('Removing AppStorage cache file');
        Charm::AppStorage()->clearCache();

        // Clear views cache
        $dir = PathFinder::getCachePath() . DIRECTORY_SEPARATOR . 'views';
        if(file_exists($dir)) {
            $output->writeln('Removing Views cache');
            $this->removeDirectoryContent($dir);
        }

        // Also clear opcache
        if(function_exists('opcache_reset')) {
            $output->writeln('Resetting opcache');
            opcache_reset();
        }

        $output->writeln('Done!');
        return true;
    }

    /**
     * Remove the directory content
     *
     * Idea: http://andy-carter.com/blog/recursively-remove-a-directory-in-php
     *
     * @param string $path the path
     * @param bool $recursion in recursion? Internal use!
     *
     * @return bool
     */
    private function removeDirectoryContent($path, $recursion = false)
    {
        $files = glob($path . '/*');
        foreach ($files as $file) {
            is_dir($file) ? $this->removeDirectoryContent($file, true) : unlink($file);
        }

        if ($recursion) {
            rmdir($path);
        }

        return true;
    }
}