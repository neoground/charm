<?php
/**
 * This file contains a console command.
 */

namespace Charm\Cache\Jobs\Console;

use Charm\Bob\Command;
use Charm\Vivid\C;

/**
 * Class CacheClearCommand
 *
 * Handling general cache clearing
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
     * @return bool
     */
    public function main(): bool
    {
        // AppStorage
        $this->io->writeln('Removing AppStorage cache file');
        C::AppStorage()->clearCache();

        // Clear views cache
        $dir = C::Storage()->getCachePath() . DS . 'views';
        if (file_exists($dir)) {
            $this->io->writeln('Removing Views cache');
            $this->removeDirectoryContent($dir);
        }

        // Also clear opcache
        if (function_exists('opcache_reset')) {
            $this->io->writeln('Resetting opcache');
            opcache_reset();
        }

        if (C::has('Event')) {
            $this->io->writeln('Firing cache clear event');
            C::Event()->fire('Cache', 'clear');
        }

        $this->io->success('✅ Done!');
        return true;
    }

    /**
     * Remove the directory content
     *
     * Idea: http://andy-carter.com/blog/recursively-remove-a-directory-in-php
     *
     * @param string $path      the path
     * @param bool   $recursion in recursion? Internal use!
     *
     * @return bool
     */
    private function removeDirectoryContent(string $path, bool $recursion = false): bool
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