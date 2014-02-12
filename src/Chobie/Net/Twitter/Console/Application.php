<?php
namespace Chobie\Net\Twitter\Console;

use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

class Application extends BaseApplication
{
    public function __construct($version)
    {
        parent::__construct("ptig", $version);
        $this->setupCommands();
    }

    public function setupCommands()
    {
        $this->add(new Command\RunCommand());
    }

    public function doRun(InputInterface $input, OutputInterface $output)
    {
        return parent::doRun($input, $output);
    }
}