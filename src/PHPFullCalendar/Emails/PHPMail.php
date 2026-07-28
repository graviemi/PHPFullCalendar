<?php

namespace PHPFullCalendar\Emails;

class PHPMail implements EmailInterface
{
	protected string|null $from;
	protected array $destinations = [];

	public function __construct(array $parameters = [])
	{
		$this->from = $parameters['from'] ?? null;
	}

	public function AddDestination(string $address, string|null $name = null)
	{
		if (! filter_var($address, FILTER_VALIDATE_EMAIL) || isset($this->destinations[$address]))
			return;
		if ($name === null || ($name = trim(str_replace(["\r", "\n"], ' ', $name))) === '')
		{
			$this->destinations[$address] = $address;
			return;
		}
		if (preg_match('/[^\x20-\x7E]/', $name))
			$name = mb_encode_mimeheader($name, 'UTF-8', 'B');
		else
			$name = '"'.addcslashes($name, '"\\').'"';
		$this->destinations[$address] = sprintf('%s <%s>', $name, $address);
	}

	public function Send(string $subject, string $body)
	{
		if ($this->destinations === [])
			return false;
		$subject = mb_encode_mimeheader(str_replace(["\r", "\n"], ' ', $subject), 'UTF-8', 'B');
		$headers = [
			'MIME-Version' => '1.0',
			'Content-Type' => 'text/plain; charset=UTF-8',
			'Content-Transfer-Encoding' => '8bit',
		];
		if ($this->from !== null)
			$headers['From'] = $this->from;
		return mail(implode(', ', $this->destinations), $subject, $body, $headers);
	}
}
