<?php
/**
 * User: ms
 * Date: 29.08.15
 * Time: 20:22
 * @see https://github.com/secondtruth/php-phar-compiler
 */
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'vendor/autoload.php';
use Secondtruth\Compiler\Compiler;

$compiler = new Compiler('./');

$compiler->addIndexFile('cli.php');


$compiler->addFile('vendor/autoload.php');
$compiler->addDirectory('vendor/composer', '!*.php');
$compiler->addDirectory('vendor/zendframework', '!*.php');

$compiler->compile("build/tst-cli.phar");