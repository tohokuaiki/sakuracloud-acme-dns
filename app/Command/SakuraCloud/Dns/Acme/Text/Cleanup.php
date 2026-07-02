<?php

namespace App\Command\SakuraCloud\Dns\Acme\Text;

use App\Command\SakuraCloud\Dns\Acme;
use Exception;
use GuzzleHttp\Client;
use Override;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Cleanup extends Acme
{
    protected static $defaultName = "dns:acme-cleanup";

    protected static $defaultDescription = "さくらのクラウドのDNSサービスで_acme-challengeのTXTレコードをクリーンアップする。";

    #[Override]
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $client = $this->getClient($input);
        $zone = $input->getArgument('zone');
        try {
            $this->cleanupTextRecord($client, $zone);
        } catch (Exception $e) {

            return Command::FAILURE;
        }
        return Command::SUCCESS;
    }

    private function cleanupTextRecord(Client $client, string $zone)
    {
        [$zoneItem, $validation, $resourceName] = $this->getZoneItem($client, $zone);
        $itemId       = $zoneItem['ID'];
        $settingsHash = $zoneItem['SettingsHash'];
        $settings     = $zoneItem['Settings'];

        $settings['DNS']['ResourceRecordSets'] = array_values(array_filter(
            $settings['DNS']['ResourceRecordSets'],
            fn(array $record): bool =>
            !($record['Name'] === $resourceName && $record['RData'] === $validation)
        ));

        $putBody = [
            'CommonServiceItem'    => ['Settings' => $settings],
            'OriginalSettingsHash' => $settingsHash,
        ];
        $client->put('commonserviceitem/' . $itemId, ['json' => $putBody]);
    }
}
