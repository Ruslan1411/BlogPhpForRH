<?php

namespace app\lib;

use PDO;

class Db {

	protected $db;
	
	public function __construct(){
		$config = require 'app/config/db.php';
		$dsn = 'mysql:host='. $config['host'].';dbname='. $config['name'].';charset='.$config['charset'];  
		$user = $config['user'];
		$pass = $config['password'];
		$options = [
			\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
			\PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
			\PDO::ATTR_EMULATE_PREPARES   => false,
		];
		try{
			$this->db = new PDO($dsn, $user, $pass, $options);
		}catch(\PDOException $e){
			throw new \PDOException($e->getMessage(), (int)$e->getCode());
		}
	}

	public function query($sql, $params = []) {
		$stmt = $this->db->prepare($sql);
		if (!empty($params)) {
			foreach ($params as $key => $val) {
				if (is_int($val)) {
					$type = PDO::PARAM_INT;
				} else {
					$type = PDO::PARAM_STR;
				}
				$stmt->bindValue(':'.$key, $val, $type);
			}
		}
		$stmt->execute();
		return $stmt;
	}

	public function row($sql, $params = []) {
		$result = $this->query($sql, $params);
		return $result->fetchAll(PDO::FETCH_ASSOC);
	}

	public function column($sql, $params = []) {
		$result = $this->query($sql, $params);
		return $result->fetchColumn();
	}

	public function lastInsertId() {
		return $this->db->lastInsertId();
	}

}