<?php

class DatabaseController {

	protected $dbh;
	private $stmt;

	function __construct() {
		try {
			$this->dbh = new PDO(
				DB_TYPE.':host='.DB_HOST.';dbname='.DB_NAME.';',
				DB_USER, DB_PASS,
				array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4')
			);
			$this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		} catch(Exception $e) {
			error_log($e->getMessage());
			throw new Exception('Failed to establish database connection to ›'.DB_HOST.'‹. Gentle panic.');
		}
	}

	public function getTickets($event) {
		$this->stmt = $this->dbh->prepare(
			'SELECT * FROM ticket WHERE event = :event'
		);
		$this->stmt->execute([':event' => $event]);
		return $this->stmt->fetchAll();
	}
	public function getTicketByCode($code) {
		$this->stmt = $this->dbh->prepare(
			'SELECT * FROM ticket WHERE code = :code'
		);
		$this->stmt->execute([':code' => $code]);
		foreach($this->stmt->fetchAll() as $ticket) {
			return $ticket;
		}
	}
	public function getTicketByEmailAndEvent($email, $event) {
		$this->stmt = $this->dbh->prepare(
			'SELECT * FROM ticket WHERE email = :email AND event = :event'
		);
		$this->stmt->execute([':email' => $email, ':event' => $event]);
		foreach($this->stmt->fetchAll() as $ticket) {
			return $ticket;
		}
	}
	public function insertTicket($event, $email, $code) {
		$this->stmt = $this->dbh->prepare(
			'INSERT INTO ticket (event, email, code) VALUES (:event, :email, :code)'
		);
		$this->stmt->execute([
			':event' => $event, ':email' => $email, ':code' => $code,
		]);
		return $this->dbh->lastInsertId();
	}
	public function setCheckIn($id, $checked_in) {
		$this->stmt = $this->dbh->prepare(
			'UPDATE ticket set checked_in = :checked_in WHERE id = :id'
		);
		$this->stmt->execute([
			':id' => $id, ':checked_in' => $checked_in,
		]);
		return $this->dbh->lastInsertId();
	}
	public function deleteTicket($id) {
		$this->stmt = $this->dbh->prepare(
			'DELETE FROM ticket WHERE id = :id'
		);
		return $this->stmt->execute([':id' => $id]);
	}

}
