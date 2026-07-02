<?php

namespace App\Command\SakuraCloud\Dns\Acme\Text;

use App\Command\SakuraCloud\Dns\Acme;
use Exception;
use GuzzleHttp\Client;
use Override;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;

class Register extends Acme
{
    protected static $defaultName = "dns:acme-register";

    protected static $defaultDescription = "さくらのクラウドのDNSサービスで_acme-challengeのTXTレコードを登録する。";

    #[Override]
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $client = $this->getClient($input);
        $zone = $input->getArgument('zone');
        try {
            $this->registerTextRecord($client, $zone);
        } catch (Exception $e) {
            $output->writeln($e->getMessage());
            return Command::FAILURE;
        }

        sleep(60);
        return Command::SUCCESS;
    }

    // _acme-challengeリソースのTXTレコードをアップデートする
    private function registerTextRecord(Client $client, string $zone)
    {
        [$zoneItem, $validation, $resourceName] = $this->getZoneItem($client, $zone);
        $itemId       = $zoneItem['ID'];
        $settingsHash = $zoneItem['SettingsHash'];
        $settings     = $zoneItem['Settings'];

        $alreadyExists = false;
        foreach ($settings['DNS']['ResourceRecordSets'] as $set) {
            if ($set['Name'] === $resourceName && $set['Type'] === 'TXT' && $set['RData'] === $validation) {
                $alreadyExists = true;
                break;
            }
        }
        if (!$alreadyExists) {
            $settings['DNS']['ResourceRecordSets'][] = [
                'Name'  => $resourceName,
                'Type'  => 'TXT',
                'RData' => $validation,
                'TTL'   => 10,
            ];
        }
        $putBody = [
            'CommonServiceItem'    => ['Settings' => $settings],
            'OriginalSettingsHash' => $settingsHash,
        ];
        $client->put('commonserviceitem/' . $itemId, ['json' => $putBody]);
    }
}
