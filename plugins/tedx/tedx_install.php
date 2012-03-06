<?php
/**
 * Performs install/uninstall methods for the TedX Plugin
 *
 * @package    Ushahidi
 * @author     Ushahidi Team
 * @copyright  (c) 2008 Ushahidi Team
 * @license    http://www.ushahidi.com/license.html
 */
class Tedx_Install {

	/**
	 * Constructor to load the shared database library
	 */
	public function __construct()
	{
		$this->db =  new Database();
	}

	/**
	 * Creates the required database tables for the tedx module
	 */
	public function run_install()
	{
		// Create the database tables
		// Include the table_prefix
		$this->db->query("
			INSERT INTO `".Kohana::config('database.default.table_prefix')."scheduler` (`scheduler_name`, `scheduler_last`, `scheduler_weekday`, `scheduler_day`, `scheduler_hour`, `scheduler_minute`, `scheduler_controller`, `scheduler_active`) VALUES
			('TED', 1330503400, -1, -1, -1, -1, 's_ted', 1);
			");
	}

	/**
	 * Deletes the database tables for the tedx module
	 */
	public function uninstall()
	{
		$this->db->query("
			DELETE FROM `".Kohana::config('database.default.table_prefix')."scheduler` WHERE `scheduler_name` = 'TED';
			");
	}
}
