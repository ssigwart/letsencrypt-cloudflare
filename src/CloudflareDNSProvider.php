<?php

namespace ssigwart\LetsEncryptCloudflareDNSClient;

use LetsEncryptDNSClient\DNSProviderInterface;
use LetsEncryptDNSClient\LetsEncryptDNSClientException;
use Throwable;

/** Route53 provider interface */
class CloudflareDNSProvider implements DNSProviderInterface
{
	/** @var \Cloudflare\API\Endpoints\DNS Cloudflare DNS */
	private $cloudflareDns = null;

	/** Zone ID */
	private $zoneId = null;

	/** @var bool[] Hash of type|name to true to indicate if cleanup was done */
	private array $didOldRecordCleanupHash = [];

	/**
	 * Constructor
	 *
	 * @param string $cfApiToken Cloudflare API token
	 * @param string $zoneId Zone ID
	 */
	public function __construct(string $cfApiToken, string $zoneId)
	{
		$key = new \Cloudflare\API\Auth\APIToken($cfApiToken);
		$adapter = new \Cloudflare\API\Adapter\Guzzle($key);
		$this->cloudflareDns = new \Cloudflare\API\Endpoints\DNS($adapter);
		$this->zoneId = $zoneId;
	}

	/**
	 * Add a DNS record
	 *
	 * @param string $type DNS record type
	 * @param string $name DNS name
	 * @param string $value DNS value
	 * @param int $ttl TTL
	 *
	 * @throws LetsEncryptDNSClientException
	 */
	public function addDnsValue(string $type, string $name, string $value, int $ttl): void
	{
		try
		{
			$hashKey = $type . '|' . $name;
			if (!isset($this->didOldRecordCleanupHash[$hashKey]))
			{
				$this->didOldRecordCleanupHash[$hashKey] = true;
				$records = $this->cloudflareDns->listRecords($this->zoneId, $type, $name);
				foreach ($records->result as $dnsRecord)
				{
					// Just to be safe, make sure it's an ACME challenge
					if (preg_match('/^_acme-challenge[.]/', $dnsRecord->name) && $dnsRecord->type === 'TXT')
						$this->cloudflareDns->deleteRecord($this->zoneId, $dnsRecord->id);
				}
			}
			$successful = $this->cloudflareDns->addRecord($this->zoneId, $type, $name, $value, $ttl, false);
			if (!$successful)
				throw new LetsEncryptDNSClientException('Failed to add DNS record.');
		} catch (\Cloudflare\API\Adapter\ResponseException $e) {
			// Set message to include on exception
			$addlExceptionInfo = ' Cloudflare Error: ' . $e->getCode() . ' - ' . $e->getMessage();
			throw new LetsEncryptDNSClientException('Failed to add DNS record.' . $addlExceptionInfo, 0, $e);
		} catch (LetsEncryptDNSClientException $e) {
			throw $e;
		} catch (Throwable $e) {
			throw new LetsEncryptDNSClientException('Failed to add DNS record.', 0, $e);
		}
	}
}
