<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

if (!function_exists('canonical_request_url'))
{
	/**
	 * Return the canonical URL for an alternate-host request.
	 *
	 * The configured base URL is trusted deployment configuration. The request
	 * URI is preserved only when it is an absolute path without header breaks.
	 *
	 * @param string $base_url
	 * @param array $server
	 * @return string|bool
	 */
	function canonical_request_url($base_url, $server)
	{
		if (!is_string($base_url) || trim($base_url) === '')
		{
			return FALSE;
		}

		$canonical = @parse_url($base_url);
		if (!is_array($canonical) || empty($canonical['scheme']) || empty($canonical['host']))
		{
			return FALSE;
		}

		$current_authority = isset($server['HTTP_HOST']) ? trim((string) $server['HTTP_HOST']) : '';
		$current = @parse_url('//' . $current_authority);
		if (!is_array($current) || empty($current['host']))
		{
			return FALSE;
		}

		$canonical_host = strtolower(rtrim($canonical['host'], '.'));
		$current_host = strtolower(rtrim($current['host'], '.'));
		$canonical_port = isset($canonical['port']) ? (int) $canonical['port'] : NULL;
		$current_port = isset($current['port']) ? (int) $current['port'] : NULL;

		if ($canonical_host === $current_host && $canonical_port === $current_port)
		{
			return FALSE;
		}

		$authority = $canonical_host;
		if ($canonical_port !== NULL)
		{
			$authority .= ':' . $canonical_port;
		}

		$request_uri = isset($server['REQUEST_URI']) ? (string) $server['REQUEST_URI'] : '/';
		if ($request_uri === '' || $request_uri[0] !== '/' || strpbrk($request_uri, "\r\n") !== FALSE)
		{
			$request_uri = '/';
		}

		return strtolower($canonical['scheme']) . '://' . $authority . $request_uri;
	}
}

/* End of file canonical_url_helper.php */
/* Location: ./application/helpers/canonical_url_helper.php */
