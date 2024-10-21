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

	public function getEvents() {
		$this->stmt = $this->dbh->prepare('SELECT * FROM event');
		$this->stmt->execute();
		$events = [];
		foreach($this->stmt->fetchAll() as $row) {
			$events[$row['id']] = $row;
		}
		return $events;
	}
	public function insertEvent($id, $title, $max, $start, $end, $location, $voucher_only, $tickets_per_email) {
		$this->stmt = $this->dbh->prepare(
			'INSERT INTO event (id, title, max, start, end, location, voucher_only, tickets_per_email)
			VALUES (:id, :title, :max, :start, :end, :location, :voucher_only, :tickets_per_email)'
		);
		$this->stmt->execute([
			':id' => $id, ':title' => $title, ':max' => $max,
			':start' => $start, ':end' => $end, ':location' => $location,
			':voucher_only' => $voucher_only, ':tickets_per_email' => $tickets_per_email,
		]);
		return $this->dbh->lastInsertId();
	}
	public function updateEvent($id, $title, $max, $start, $end, $location, $voucher_only, $tickets_per_email) {
		$this->stmt = $this->dbh->prepare(
			'UPDATE event SET title=:title, max=:max, start=:start, end=:end, location=:location, voucher_only=:voucher_only, tickets_per_email=:tickets_per_email WHERE id=:id'
		);
		$this->stmt->execute([
			':id' => $id, ':title' => $title, ':max' => $max,
			':start' => $start, ':end' => $end, ':location' => $location,
			':voucher_only' => $voucher_only, ':tickets_per_email' => $tickets_per_email,
		]);
		return $this->dbh->lastInsertId();
	}
	public function deleteEvent($id) {
		$this->stmt = $this->dbh->prepare('DELETE FROM event WHERE id=:id');
		return $this->stmt->execute([':id' => $id]);
	}

	public function getVouchers() {
		$this->stmt = $this->dbh->prepare('SELECT * FROM voucher');
		$this->stmt->execute();
		$vouchers = [];
		foreach($this->stmt->fetchAll() as $row) {
			$vouchers[$row['code']] = $row;
		}
		return $vouchers;
	}
	public function getVoucherByCode($code) {
		$this->stmt = $this->dbh->prepare('SELECT * FROM voucher WHERE code = :code');
		$this->stmt->execute([':code' => $code]);
		foreach($this->stmt->fetchAll() as $voucher) {
			return $voucher;
		}
	}
	public function insertVoucher($code, $event_id, $valid_amount) {
		$this->stmt = $this->dbh->prepare(
			'INSERT INTO voucher (code, event_id, valid_amount)
			VALUES (:code, :event_id, :valid_amount)'
		);
		$this->stmt->execute([
			':code' => $code, ':event_id' => $event_id, ':valid_amount' => $valid_amount,
		]);
		return $this->dbh->lastInsertId();
	}
	public function updateVoucher($code, $event_id, $valid_amount) {
		$this->stmt = $this->dbh->prepare(
			'UPDATE voucher SET event_id=:event_id, valid_amount=:valid_amount WHERE code=:code'
		);
		$this->stmt->execute([
			':code' => $code, ':event_id' => $event_id, ':valid_amount' => $valid_amount,
		]);
		return $this->dbh->lastInsertId();
	}
	public function deleteVoucher($code) {
		$this->stmt = $this->dbh->prepare('DELETE FROM voucher WHERE code=:code');
		return $this->stmt->execute([':code' => $code]);
	}

	public function getTickets($event_id) {
		$this->stmt = $this->dbh->prepare('SELECT * FROM ticket WHERE event_id = :event_id');
		$this->stmt->execute([':event_id' => $event_id]);
		return $this->stmt->fetchAll();
	}
	public function getTicketByCode($code) {
		$this->stmt = $this->dbh->prepare('SELECT * FROM ticket WHERE code = :code');
		$this->stmt->execute([':code' => $code]);
		foreach($this->stmt->fetchAll() as $ticket) {
			return $ticket;
		}
	}
	public function getTicketsByVoucherCode($voucher_code) {
		$this->stmt = $this->dbh->prepare('SELECT * FROM ticket WHERE voucher_code = :voucher_code');
		$this->stmt->execute([':voucher_code' => $voucher_code]);
		return $this->stmt->fetchAll();
	}
	public function getTicketsByEmailAndEvent($email, $event_id) {
		$this->stmt = $this->dbh->prepare('SELECT * FROM ticket WHERE revoked IS NULL AND email = :email AND event_id = :event_id');
		$this->stmt->execute([':email' => $email, ':event_id' => $event_id]);
		return $this->stmt->fetchAll();
	}
	public function insertTicket($code, $event_id, $email, $token, $voucher_code) {
		$this->stmt = $this->dbh->prepare(
			'INSERT INTO ticket (code, event_id, email, token, voucher_code)
			VALUES (:code, :event_id, :email, :token, :voucher_code)'
		);
		$this->stmt->execute([
			':code' => $code, ':event_id' => $event_id, ':email' => $email, ':token' => $token, ':voucher_code' => $voucher_code,
		]);
		return $this->dbh->lastInsertId();
	}
	public function updateTicketCheckIn($code) {
		$this->stmt = $this->dbh->prepare('UPDATE ticket set checked_in = CURRENT_TIMESTAMP WHERE code = :code');
		$this->stmt->execute([':code' => $code]);
		return $this->dbh->lastInsertId();
	}
	public function updateTicketCheckOut($code) {
		$this->stmt = $this->dbh->prepare('UPDATE ticket set checked_out = CURRENT_TIMESTAMP WHERE code = :code');
		$this->stmt->execute([':code' => $code]);
		return $this->dbh->lastInsertId();
	}
	public function updateTicketRevoked($code) {
		$this->stmt = $this->dbh->prepare('UPDATE ticket set revoked = CURRENT_TIMESTAMP WHERE code = :code');
		$this->stmt->execute([':code' => $code]);
		return $this->dbh->lastInsertId();
	}
	public function deleteTicket($code) {
		$this->stmt = $this->dbh->prepare('DELETE FROM ticket WHERE code = :code');
		return $this->stmt->execute([':code' => $code]);
	}

}
