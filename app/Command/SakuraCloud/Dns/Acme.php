<?php

namespace App\Command\SakuraCloud\Dns;

use App\Command\SakuraCloud\Dns;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class Acme extends Dns
{

    /**
     * CNAME運用しているケースを想定し、_acme-challengeのTXTレコードをもつホスト名を取得する
     * Resolve the target FQDN where the ACME DNS-01 challenge TXT record
     * should be created for the given domain.
     *
     * If a CNAME record exists for "_acme-challenge.{domain}", the
     * function returns the CNAME target (without a trailing dot). Otherwise
     * it returns the original "_acme-challenge.{domain}" FQDN.
     *
     * @param string $domain The domain to resolve the ACME challenge for.
     * @return string The FQDN where the TXT record should be created.
     */
    protected function resolveChallengeTarget(string $domain): string
    {
        $challengeFqdn = '_acme-challenge.' . $domain;
        $records = dns_get_record($challengeFqdn, DNS_CNAME);

        if (!empty($records) && isset($records[0]['target'])) {
            // CNAME委任あり → 転送先を返す
            return rtrim($records[0]['target'], '.');
        }

        // CNAMEなし → 直接このFQDNにTXTを書く(通常運用のドメイン向け)
        return $challengeFqdn;
    }


    /**
     * Zone情報を取得する
     * 
     * @param Client $client Guzzle http client
     * @param string $zone 取得対象のDNSゾーン
     * @return array 0 => API返却値 , 1 => ACMEが発行したトークン , 2 => DNSのリソースレコードの名前
     */
    protected function getZoneItem(Client $client, string $zone): array
    {
        $filter = [
            'Filter' => [
                'Name'           => [$zone],
                'Provider.Class' => 'dns',
            ],
            'Include' => ['ID', 'Name', 'Settings', 'SettingsHash'],
        ];
        try {
            $response  = $client->get('commonserviceitem?' . rawurlencode(json_encode($filter)));
            $resp = json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            throw new Exception(sprintf(
                "status:%s\nmessage:%s",
                $e->getResponse()?->getStatusCode() ?? 'unknown',
                $e->getResponse()?->getBody()->getContents() ?? $e->getMessage()
            ), $e->getCode());
        }
        $zoneItem = $resp['CommonServiceItems'][0] ?? null;
        if (is_null($zoneItem)) {
            throw new Exception("ゾーン {$zone} が見つかりません");
        }
        $itemId       = $zoneItem['ID'] ?? null;
        $settingsHash = $zoneItem['SettingsHash'] ?? null;
        $settings     = $zoneItem['Settings'] ?? null;
        $zoneName     = $zoneItem['Name'] ?? null;
        $domain       = getenv('CERTBOT_DOMAIN');
        $validation   = getenv('CERTBOT_VALIDATION');
        if (!$itemId || !$settingsHash || !$settings || !$zoneName || !$domain || !$validation) {
            throw new Exception("パラメータが足りません。\n" .
                var_export([
                    'zoneItem'   => $zoneItem,
                    'domain'     => $domain,
                    'validation' => $validation
                ], true));
        }
        $acmeFqdn = $this->resolveChallengeTarget($domain);
        $resourceName = str_ends_with($acmeFqdn, $zoneName) ?
            rtrim(substr($acmeFqdn, 0, -strlen($zoneName)), '.') :
            $acmeFqdn;

        return [$zoneItem, $validation, $resourceName];
    }
}
