<?php

require_once __DIR__.'/../vendor/autoload.php';
use App\classes\Cli;
use App\classes\File;
use App\classes\User;
use App\classes\Helpers;

$app = new Cli(new User(new File('../src/files/users.txt')), new File('../src/files/admin-cli.txt'), new Helpers(),[]);
$app->run();

?>