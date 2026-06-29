<?php

namespace PHPFullCalendar;

use PDO,
	ArrayObject,
	oTools\Sessions\Session,
	oTools\Sessions\Identifiers\Cookie,
	PHPFullCalendar\Authentications\AuthenticationInterface,
	PHPFullCalendar\Security\SecurityInterface,
	PHPFullCalendar\Controllers\ControllerInterface,
	PHPFullCalendar\Views\Unauthorized;

class _
{
	const FORBIDDEN = 403;
	const NOT_FOUND = 404;
	const     ERROR = 500;

	const CONF_FILE = 'config.php';
	const LOG_FILE = 'logs/error.log';

	protected static array $_errors = [
		E_USER_ERROR => 'user error',
		E_USER_WARNING => 'warning',
		E_USER_NOTICE => 'notice'
	];
	public static string $root;
	public static array $conf = [];
	public static array $sources = [];
	public static string $language = 'en';
	public static array $translations = [];
	public static string $log_dir;
	public static bool $noSession = false;
	protected static PDO|null $pdo = null;
	protected static Session|null $session = null;
	protected static ArrayObject $userData;

	public static function load(string $class)
	{
		if (is_file($path = sprintf('%s/src/%s.php',self::$root,strtr($class,'\\',DIRECTORY_SEPARATOR))))
			require $path;
		else
			_::debug('class %s not found in %s',$class,$path);
	}

	public static function debug(string $message, string ...$args)
	{
		error_log(sprintf('-------------------%s%s%s',PHP_EOL,date('d/m/Y H:i:s'),PHP_EOL),3,self::$log_dir.'/'.self::LOG_FILE);
		error_log(vsprintf($message,$args).PHP_EOL,3,self::$log_dir.'/'.self::LOG_FILE);
	}

	public static function log_error(string $message, string ...$args)
	{
		error_log(sprintf('-------------------%s%s%s',PHP_EOL,date('d/m/Y H:i:s'),PHP_EOL),3,self::$log_dir.'/'.self::LOG_FILE);
		error_log(vsprintf($message,$args).PHP_EOL,3,self::$log_dir.'/'.self::LOG_FILE);
	}

	public static function log_access(string $message, string ...$args)
	{
//		error_log(vsprintf($message,$args).PHP_EOL,3,self::get('BACKUPPC_LOG_PATH','/var/log/BackupPC').'/access.log');
	}

	public static function exception_handler(Exception $exception)
	{
		$message = sprintf('EXCEPTION : "%s"',$exception->getMessage());
		$message .= sprintf('%sin file "%s" line %s',PHP_EOL,$exception->getFile(),$exception->getLine());
		foreach ($exception->getTrace() as $trace)
			$message .= sprintf('%scalled by "%s" line %s',PHP_EOL,$trace['file'] ?? 'unknown',$trace['line'] ?? 'unknown');
		$message .= PHP_EOL;
		self::log_error($message);
		header('HTTP/1.1 500 Internal Server Error');
		echo json_encode($exception->getMessage());
	}

	public static function error_handler(int $number, string $message, string $file, int $line) : bool
	{
		$error = sprintf('%s : %s'.PHP_EOL,self::$_errors[$number] ?? 'UNKNOWN ERROR',$message);
		$error .= sprintf('%s on line %d'.PHP_EOL,$file,$line);
		self::log_error($error);
		return true;
	}

	public static function _(string $key) : string
	{
		return self::$translations[$key] ?? $key;
	}

	public static function uf(string $key) : string
	{
		return ucfirst(self::_($key));
	}

	public static function getPDO()
	{
		if (self::$pdo === null)
			self::$pdo = new PDO(sprintf('%s:dbname=%s;host=%s',self::$conf['database']['driver'],self::$conf['database']['name'],self::$conf['database']['host']),
				self::$conf['database']['user'],self::$conf['database']['pass']);
		return self::$pdo;
	}

	// Real client IP. X-Forwarded-For is only honoured when REMOTE_ADDR is a
	// trusted proxy (config 'trusted_proxies'), otherwise it is spoofable.
	public static function clientIp() : string
	{
		$remote = $_SERVER['REMOTE_ADDR'] ?? '';
		$trusted = self::$conf['trusted_proxies'] ?? [];
		if (! in_array($remote, $trusted, true))
			return $remote;
		$forwarded = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
		if ($forwarded === '')
			return $remote;
		// "client, proxy1, proxy2" : first non-trusted from the right is the real client
		foreach (array_reverse(array_map('trim', explode(',', $forwarded))) as $ip)
			if (filter_var($ip, FILTER_VALIDATE_IP) && ! in_array($ip, $trusted, true))
				return $ip;
		return $remote;
	}

	public static function getAuthentication(string $source) : AuthenticationInterface
	{
		if (! isset(self::$conf['authentication'][$source]))
			throw new Exception(self::NOT_FOUND,'unknown authentication source "%s"',$source);
		$method = self::$conf['authentication'][$source]['method'];
		$class = sprintf('PHPFullCalendar\\Authentications\\%s',$method);
		if (! class_exists($class))
			throw new Exception(self::NOT_FOUND,'unknown authentication method "%s"',$method);
		return new $class(
			$source,
			self::$conf['authentication'][$source]['parameters'] ?? [],
			self::$conf['information'][$source] ?? [],
			self::$conf['groups'][$source] ?? [],
		);
	}

	public static function getSecurity() : SecurityInterface|null
	{
		if (! isset(self::$conf['security']))
			return null;
		$backend = self::$conf['security']['backend'];
		$class = sprintf('PHPFullCalendar\\Security\\%s',$backend);
		if (! class_exists($class))
			throw new Exception(self::NOT_FOUND,'unknown security backend "%s"',$backend);
		return new $class(self::$conf['security']['parameters'] ?? []);
	}

	public static function getSession() : Session
	{
		if (self::$session === null)
		{
			switch (self::$conf['session']['handler']['name'])
			{
				case 'memcached':
					$memcached = new \Memcached();
					$memcached->addServer(self::$conf['session']['handler']['host'] ?? '127.0.0.1',
						self::$conf['session']['handler']['port'] ?? 11211);
					$handler = new \oTools\Sessions\Handlers\Memcached($memcached,
						self::$conf['session']['ttl'] ?? 3600,
						self::$conf['session']['handler']['prefix'] ?? 'PHPFullCalendar_');
					break;
				default:
					throw new Exception('session handler "%s" does not exists',self::$conf['session']['handler']['name']);
			}
			$cookie = new Cookie(self::$conf['session']['cookie']['name'] ?? 'pfc_session',
				self::$conf['session']['ttl'] ?? 3600,
				self::$conf['session']['cookie']['domain'] ?? $_SERVER['HTTP_HOST'],
				self::$conf['session']['cookie']['path'] ?? '/',
				self::$conf['session']['cookie']['secure'] ?? true,
				self::$conf['session']['cookie']['httponly'] ?? true);
			self::$session = new Session($handler,$cookie);
		}
		return self::$session;
	}

	public static function disconnect() : void
	{
		self::$userData = new ArrayObject();
		_::debug('disconnect');
		self::getSession()->destroy();
	}

	public static function getUserData() : Session|ArrayObject
	{
		if (self::$noSession)
			return self::$userData;
		return self::getSession();
	}

	public static function denyAccess() : Views\ViewInterface
	{
		$data = self::getUserData();
		self::debug('%s',print_r($data,true));
		if (! isset($data['user_id']))
			return new Views\Unauthorized();
		return new Views\Forbidden();
	}

	public static function init()
	{
		self::$root = __DIR__;
		self::$conf = require($_SERVER['PFC_CONFIG_PATH'] ?? self::$root.'/'.self::CONF_FILE);
		self::$sources = [''];
		foreach (_::$conf['authentication'] as $source => $data)
					self::$sources[] = $source;
		self::$log_dir = self::$conf['log_dir'] ?? __DIR__;
		self::$userData = new ArrayObject();
		ini_set('display_errors',1);
		error_reporting(E_ALL);
		spl_autoload_register('PHPFullCalendar\_::load',true,true);
		require self::$root.'/src/compat.php';
		set_exception_handler('PHPFullCalendar\_::exception_handler');
		set_error_handler('PHPFullCalendar\_::error_handler');
		date_default_timezone_set(self::$conf['timezone'] ?? 'Europe/Paris');
		if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']))
		{
			$q = 0;
			$parts = explode(',',$_SERVER['HTTP_ACCEPT_LANGUAGE']);
			foreach ($parts as $part)
			{
				if (preg_match('/([a-z]+)(-[A-Z]+)?(;q=([0-9.]*))?/',$part,$matches))
				{
					$v = (float)($matches[4] ?? 1);
					if (($v > $q) && file_exists(sprintf('%s/translations/%s.php',self::$root,$matches[1])))
					{
						self::$language = $matches[1];
						$q = $v;
					}
					if ($q === 1.0)
						break;
				}
			}
		}
//		setlocale(LC_ALL,self::$language.'.UTF-8');
		self::$translations = require sprintf('%s/translations/%s.php',self::$root,self::$language);
	}

	public static function response(string $protocol, string $verb, string $uri)
	{
		self::debug('http protocol:%s, verb: %s, uri:%s',$protocol,$verb,$uri);
		if (! preg_match('|^(/\\$)?(/[a-z]*)?(/[a-z]+)?(/[^?]+)?|i',$uri,$matches))
			throw new Exception(self::ERROR,'URI syntax error');
		self::$noSession = ($matches[1] === '/$');
		$controler = ($matches[2] === '/')?'Index':ucfirst(strtolower(substr($matches[2],1)));
		if (isset($matches[3]))
			$method = strtolower(substr($matches[3],1));
		if (isset($matches[4]))
			$parameters = substr($matches[4],1);

		// get authorization data when no session access
		if (self::$noSession)
		{
			$user_id = null;
			if (isset($_SERVER['PHP_AUTH_USER']))
				[$user_id,$password] = [$_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'] ?? ''];
			else
			{
				$authorization = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
				if (str_starts_with($authorization, 'Basic '))
				{
					$decoded = base64_decode(substr($authorization, 6));
					[$user_id, $password] = array_pad(explode(':', $decoded, 2), 2, '');
				}
			}
			if ($user_id !== null)
			{
				[$source,$login] = array_pad(explode('.',$user_id,2),2,'');
				if (isset(self::$conf['authentication'][$source]))
				{
					$secu = self::getSecurity();
					// blocked client : skip the LDAP check, request stays anonymous → denied by ACL
					if (($secu === null) || $secu->check(self::clientIp()))
					{
						$data = self::getAuthentication($source)->verify($login,$password);
						if (isset($data['user_id']))
							self::$userData = $data;
						elseif ($secu !== null)
							$secu->store(self::clientIp());
					}
				}
			}
		}

		// check connection protocol.
		if ((_::$conf['secure'] ?? true) && ($protocol !== 'https'))
			throw new Exception(self::FORBIDDEN,'https required.');
		// routing to the right controler is based on class existence.
		if (! class_exists($class = sprintf('\\PHPFullCalendar\\Controllers\\%s',$controler)))
			throw new Exception(self::NOT_FOUND,'"%s" not found.',$controler);
//		self::debug('http class:%s, method:%s',$class,$method ?? 'null');
		$view = (new $class($verb,$method ?? null,$parameters ?? null))->view();
//		self::debug('http view:%s',get_class($view));
		if (($code = $view->code()) !== 200)
			header(sprintf('HTTP/1.1 %d %s',$code,$view->message()));
		if (($mimetype = $view->mimeType()) !== 'text/html')
			header(sprintf('Content-Type: %s; charset=%s',$view->mimeType(),$view->charset()));
		// todo : caching
		header('Cache-Control: no-store');
		foreach (_::$conf['http-headers'] as $header)
			header($header);
		foreach ($view->extraHeaders() as $header)
			header($header);
		$view->body();
	}
}

_::init();
_::response(isset($_SERVER['HTTPS'])?'https':'http',strtolower($_SERVER['REQUEST_METHOD']),trim($_SERVER['REQUEST_URI']));
