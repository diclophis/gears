<?php

/*
	see Migration.php
*/

final class Migrator extends ApplicationEntryPoint {

	public static function migrate ()
	{
		self::load(true);
		$version = self::current_version();
		while($migrations_to_run = self::get_migrations(++$version)) {
			echo sprintf("Migrating to version %03d... ", $version);
			foreach($migrations_to_run as $migration_to_run) {
				$migration_class_name = str_replace(array(sprintf("%03d_", $version), ".php"), "", basename($migration_to_run));
				require_once($migration_to_run);
				if (!class_exists($migration_class_name)) {
					throw new GearsException("Incorrect classname for migration filename");
				}
				if (is_callable(array($migration_class_name, "up"))) {
					$aok = call_user_func(array($migration_class_name, "up"));
				} else {
					throw new GearsException("up is not callable");
				}
			}
			if ($aok) {
				self::set_version($version);
				echo sprintf("successfully migrated to version %d\n", $version);
			} else {
				echo sprintf("unable to migrate to version %d\n", $version);
				break;
			}
		}
		echo "finishing successfully\n";
	}

	public static function current_version ()
	{
		try {
			try {
				$record = Model::execute_query("select version from schema_info limit 1");
				return intval($record[0][0]);
			} catch (PDOException $error) {
				throw new SchemaInfoMissingException();
			}
		} catch (SchemaInfoMissingException $schema_info_missing_error) {
			echo $schema_info_missing_error;
			echo "creating schema_info... ";
			Model::execute_system_query("CREATE TABLE IF NOT EXISTS schema_info (`version` int(3))");
			Model::execute_system_query("INSERT INTO schema_info VALUES(0)");
			echo "success\n";
			return 0;
		}
	}

	public static function set_version ($new_version)
	{
		Model::execute_system_query(sprintf("update schema_info set version = %d", $new_version));
	}

	public static function get_migrations ($for_version)
	{
		$migrations = glob(sprintf("%s/db/migrations/%03d*.php", $_SERVER['COMP_ROOT'], $for_version));
		if (count($migrations) > 0) {
			return $migrations;
		} else {
			echo sprintf("no migrations for version %03d were found...\n", $for_version);
		}
	}

}

?>
