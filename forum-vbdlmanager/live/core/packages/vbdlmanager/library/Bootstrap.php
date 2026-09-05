<?php
/**
 * Bootstrap helpers for Download Manager.
 */

class vbdl_Bootstrap
{
	/** @var vbdl_Repository */
	public static $repo;
	/** @var vbdl_Acl */
	public static $acl;
	/** @var vbdl_DownloadService */
	public static $service;
	/** @var bool */
	protected static $loaded = false;

	public static function init($db = null, $tablePrefix = null)
	{
		if (self::$loaded)
		{
			return;
		}

		$dir = dirname(__FILE__);
		require_once $dir . '/Db.php';
		require_once $dir . '/Repository.php';
		require_once $dir . '/Acl.php';
		require_once $dir . '/DownloadService.php';

		if ($db === null)
		{
			$db = vbdl_Db::connection();
		}
		if ($tablePrefix === null)
		{
			$tablePrefix = vbdl_Db::tablePrefix();
		}

		if ($db === null)
		{
			throw new Exception('Download Manager bootstrap requires a database connection.');
		}

		if (!defined('TIMENOW'))
		{
			define('TIMENOW', time());
		}

		vbdl_Db::ensureSchema($db, $tablePrefix);

		self::$repo = new vbdl_Repository($db, $tablePrefix);
		self::$acl = new vbdl_Acl(self::$repo);
		self::$service = new vbdl_DownloadService(self::$repo, self::$acl);
		self::$loaded = true;
	}

	public static function packagePath()
	{
		return dirname(dirname(__FILE__));
	}

	public static function runSqlFile($db, $tablePrefix, $relativeSql)
	{
		$path = self::packagePath() . '/db/mysql/' . $relativeSql;
		if (!is_file($path))
		{
			throw new Exception('SQL file missing: ' . $relativeSql);
		}
		$sql = file_get_contents($path);
		$sql = str_replace('{TABLE_PREFIX}', $tablePrefix, $sql);
		$statements = array_filter(array_map('trim', preg_split('/;\s*[\r\n]+/', $sql)));
		foreach ($statements as $statement)
		{
			if ($statement === '' || strpos($statement, '--') === 0)
			{
				continue;
			}
			$db->query_write($statement);
		}
	}
}
