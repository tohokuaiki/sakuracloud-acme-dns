<?php

declare(strict_types=1);

namespace App\Command;

use Dotenv\Dotenv;
use GuzzleHttp\Client;
use Override;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

class SakuraCloud extends Command
{

    private ?Client $client = null;

    private const API_URL = 'https://secure.sakura.ad.jp/cloud/zone/%s/api/cloud/1.1/';


    #[Override]
    protected function configure()
    {
        $this->addOption('config-file', 'f', InputArgument::OPTIONAL, 'APIのアカウント情報が入ったファイル', getcwd() . '/.env');
        $this->addOption('region', 'r', InputOption::VALUE_OPTIONAL, 'さくらのクラウドのリージョン', 'is1b');
        return parent::configure();
    }

    protected function getClient(InputInterface $input): Client
    {
        if (is_null($this->client)) {
            $dotfile = $input->getOption('config-file');
            $dotenv = Dotenv::createImmutable(dirname($dotfile), basename($dotfile));
            $dotenv->load();
            $this->client = new Client([
                'base_uri' => sprintf(self::API_URL, $input->getOption('region')),
                'auth'     => [$_ENV['API_TOKEN'], $_ENV['API_TOKEN_SECRET']],
                'timeout'  => 30,
            ]);
        }
        return $this->client;
    }

    public function __construct(?string $name = null)
    {
        return parent::__construct($name);
    }
}
