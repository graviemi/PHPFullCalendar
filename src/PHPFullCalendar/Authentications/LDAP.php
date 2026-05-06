<?php

namespace PHPFullCalendar\Authentications;

use ArrayObject,
	PHPFullCalendar\_,
	PHPFullCalendar\Exception as PFCException;

class LDAP extends AuthenticationAbstract
{
	protected \LDAP\Connection $connection;

	public function verify(string $login, string $password) : ArrayObject|null
	{
		ldap_set_option(null,LDAP_OPT_X_TLS_REQUIRE_CERT,LDAP_OPT_X_TLS_NEVER);
		$this->connection = ldap_connect($this->parameters['host'] ?? '');
		if ($this->connection === false)
			throw new PFCException(503,'connection to "%s" failed',$this->parameters['host'] ?? 'null');
		$result = ldap_set_option($this->connection, LDAP_OPT_PROTOCOL_VERSION, 3);
		$result = $result && ldap_set_option($this->connection, LDAP_OPT_NETWORK_TIMEOUT, $this->parameters['timeout'] ?? 3);
		$result = $result && ldap_set_option($this->connection, LDAP_OPT_TIMELIMIT, $this->parameters['timelimit'] ?? 3);
		if (! $result)
		{
			_::debug('set_option_failed');
			$this->error = ldap_error($this->connection);
			return null;
		}
		if (! (($this->parameters['startTLS'] ?? true) && ldap_start_tls($this->connection)))
		{
			_::debug('start_tls_failed');
			$this->error = ldap_error($this->connection);
			return null;
		}
		$dn = sprintf($this->parameters['bindDN'] ?? '%s', ldap_escape($login, '', LDAP_ESCAPE_DN));
		if ($result = @ldap_bind($this->connection, $dn, $password))
		{
			if (($this->information !== null) && (get_class($this->information) === '\\PHPFullCalendar\\Informations\\LDAP'))
				$this->information->setConnection($this->connection);
			if (($this->groups !== null) && (get_class($this->groups) === '\\PHPFullCalendar\\Groups\\LDAP'))
				$this->groups->setConnection($this->connection);
			$data = new ArrayObject([
				'user_id' => $login,
				'source' => $this->source,
				'informations' => ($this->information === null)?[]:$this->information->get($login),
				'groups' => ($this->groups === null)?[]:$this->groups->get($login)
			]);
			ldap_unbind($this->connection);
			return $data;
		}
		$this->failure();
		return null;
	}
}
