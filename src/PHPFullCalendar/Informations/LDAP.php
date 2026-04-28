<?php

namespace PHPFullCalendar\Informations;

use PHPFullCalendar\_;

class LDAP extends InformationAbstract
{
	protected \LDAP\Connection|null $connection = null;

	private function _getValue(array|string $entry, string $default = '') : string
	{
		if (is_array($entry) && (count($entry) > 0))
			return $entry[0];
		if (is_string($entry))
			return $entry;
		return $default;
	}

	public function setConnection(\LDAP\Connection $connection) : void
	{
		$this->connection = $connection;
	}

	public function get(string $user_id) : array
	{
		ldap_set_option(null,LDAP_OPT_X_TLS_REQUIRE_CERT,LDAP_OPT_X_TLS_NEVER);
		if ($this->connection === null)
		{
			$this->connection = ldap_connect($this->parameters['host']);
			if ($this->connection === false)
			{
				_::log_error('connection to "%s" failed',$this->parameters['host']);
				return [];
			}
			ldap_set_option($this->connection, LDAP_OPT_PROTOCOL_VERSION, 3);
			if ($this->parameters['startTLS'])
				ldap_start_tls($this->connection);
		}
		if (isset($this->parameters['bindDN']))
		{
			$result = @ldap_bind($this->connection, $this->parameters['bindDN'], $this->parameters['password']);
			if (! $result)
			{
				_::log_error('LDAP bind for "%s" failed',$this->parameters['bindDN']);
				return [];
			}
		}
		$data = [];
		if (($result = ldap_search($this->connection,$this->parameters['base'],sprintf($this->parameters['filter'],$user_id),$this->parameters['attributes'])) === false)
		{
			_::log_error('LDAPSearch base "%s", filter "%s" failed',$this->parameters['base'],$this->parameters['filter']);
			return [];
		}
		if (($entries = ldap_get_entries($this->connection,$result)) === false)
		{
			_::log_error('LDAPSearch get_entries failed');
			return [];
		}
		if ($this->parameters['name'] !== null)
			$data['name'] = $this->_getValue($entries[0][$this->parameters['name']],$user_id);
		if ($this->parameters['email'] !== null)
			$data['email'] = $this->_getValue($entries[0][$this->parameters['email']]);
		return $data;
	}
}