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

	/**
	 * Constructor
	 *
	 * @param string $cfApiToken CloudFlare API token
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
			$successful = $this->cloudflareDns->addRecord($this->zoneId, $type, $name, $value, $ttl);
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
