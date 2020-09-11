<?php
/**
 * This file contains all debug bar routes
 */

use \Charm\Vivid\Router\Elements\Route;

Route::get('debugbar_handler')
    ->url('/charm/debugbar_handler')
    ->call('Charm\DebugBar\Controllers\DebugbarController.getHandler');
