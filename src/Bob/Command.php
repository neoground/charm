<?php
/**
 * This file contains the Bob class
 */

namespace Charm\Bob;

use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

/**
 * Class Command
 *
 * This class includes Charm-specific features to
 * enhance the functionality of console commands.
 * It allows developers to create and manage custom
 * console commands that perform various tasks,
 * such as generating files, processing data,
 * and executing other custom functionality.
 *
 * Based on symfony/console.
 */
class Command extends \Symfony\Component\Console\Command\Command
{
    public function ask($input, $output, $question)
    {
        return $this->getHelper('question')->ask($input, $output, new Question($question));
    }

    public function choice($input, $output, $question, $arr, $default = null)
    {
        $cq = new ChoiceQuestion($question, $arr, $default);
        return $this->getHelper('question')->ask($input, $output, $cq);
    }


}