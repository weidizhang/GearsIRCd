<?php
/**
 * @package GearsIRCd
 * @author Weidi Zhang <weidiz999@yahoo.com>
 * @copyright 2014 Weidi Zhang
 * @license http://creativecommons.org/licenses/by-nc-sa/4.0/legalcode
 */
 
namespace GearsIRCd;

class Database
{
	private $connection;
	
	public function __construct($file) {
		try {
			$this->connection = new \PDO("sqlite:" . $file);
		}
		catch (PDOException $e) {
			die($e->getMessage());
		}
	}
	
	public function QueryAndFetch($query, $params = array()) {
		if ($this->connection) {
			$sqlQuery = $this->query($query, $params);
			$sqlFetch = $sqlQuery->fetchAll();
			return $sqlFetch;
		}
		return false;
	}
	
	public function Query($query, $params = array()) {
		if ($this->connection) {
			$sqlQuery = $this->connection->prepare($query);
			$sqlQuery->execute($params);
			return $sqlQuery;
		}
		return false;
	}
}
?>