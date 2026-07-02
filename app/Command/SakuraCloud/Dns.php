<?php
namespace App\Command\SakuraCloud;

use App\Command\SakuraCloud;
use Symfony\Component\Console\Input\InputArgument;

class Dns extends SakuraCloud
{
    protected function configure()
    {
        $this->addArgument('zone', InputArgument::REQUIRED, '操作するZone名');
        return parent::configure();
    }
}
