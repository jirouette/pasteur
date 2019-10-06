<?php
require '../vendor/autoload.php';

$loop = \React\EventLoop\Factory::create();
$pasteur = new \Pasteur\Pasteur($loop);
$loop->run();
