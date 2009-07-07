<?php
#
# Copyright, Mesoconcepts <http://www.mesoconcepts.com>
# All rights reserved
#

/**
 * db
 *
 * @package DB
 **/

abstract class db {
	static protected $dbh;
	
	
	/**
	 * connect()
	 *
	 * @param string $db_type
	 * @return void
	 **/

	static public function connect($db_type) {
		$db_host = constant($db_type . '_host');
		$db_name = constant($db_type . '_name');
		$db_user = constant($db_type . '_user');
		$db_pass = constant($db_type . '_pass');
		
		try {
			self::$dbh = new PDO(
				$db_type . ':host=' . $db_host . ';dbname=' . $db_name,
				$db_user,
				$db_pass
				);
			
			self::$dbh->setAttribute(PDO::ATTR_STATEMENT_CLASS, array('dbs'));
		} catch ( PDOException $e ) {
			throw new exception('err_db_connect');
		}
	} # connect()
	
	
	/**
	 * disconnect()
	 *
	 * @return void
	 **/
	
	static public function disconnect() {
		self::$dbh = null;
	} # disconnect()


	/**
	 * escape()
	 *
	 * @param string $var
	 * @return string $var
	 **/
	
	static public function escape($var) {
		return self::$dbh->quote($var);
	} # escape()


	/**
	 * start()
	 *
	 * @return bool $success
	 **/

	static public function start() {
		return self::$dbh->beginTransaction();
	} # start()


	/**
	 * commit()
	 *
	 * @return bool $success
	 **/

	static public function commit() {
		return self::$dbh->commit();
	} # commit()


	/**
	 * rollback()
	 *
	 * @return bool $success
	 **/

	static public function rollback() {
		return self::$dbh->rollBack();
	} # rollback()


	/**
	 * prepare()
	 *
	 * @param string $sql
	 * @return object $dbs
	 **/

	static public function prepare($sql) {
		return self::$dbh->prepare($sql, array(PDO::ATTR_EMULATE_PREPARES => true));
	} # prepare()


	/**
	 * query()
	 *
	 * @param string $sql
	 * @param array $args
	 * @return object $dbs
	 **/

	static public function query($sql, $args = null) {
		$dbs = self::prepare($sql);

		if ( $dbs->execute($args) === false ) {
			$error = $dbs->errorInfo();
			
			if ( isset($error[2]) )
				throw new exception($error[2]);
		}

		return $dbs;
	} # query()
	
	
	/**
	 * get_results()
	 *
	 * @param string $sql
	 * @param array $args
	 * @return array $results
	 **/

	static public function get_results($sql, $args = null) {
		$dbs = self::query($sql, $args);

		return $dbs->get_results();
	} # get_results()
	
	
	/**
	 * get_row()
	 *
	 * @param string $sql
	 * @param array $args
	 * @return array $row
	 **/

	static public function get_row($sql, $args = null) {
		$dbs = self::query($sql, $args);

		return $dbs->get_row();
	} # get_row()
	
	
	/**
	 * get_col()
	 *
	 * @param string $sql
	 * @param array $args
	 * @return array $col
	 **/

	static public function get_col($sql, $args = null) {
		$dbs = self::query($sql, $args);

		return $dbs->get_col();
	} # get_col()
	
	
	/**
	 * get_var()
	 *
	 * @param string $sql
	 * @param array $args
	 * @return mixed $result
	 **/

	static public function get_var($sql, $args = null) {
		$dbs = self::query($sql, $args);

		return $dbs->get_var();
	} # get_var()
} # db


/**
 * dbs
 *
 * @package DB
 **/

class dbs extends PDOStatement {
	/**
	 * exec()
	 *
	 * @param array $args
	 * @return void
	 **/

	public function exec($args = null) {
		if ( $this->execute($args) === false  ) {
			$error = $this->errorInfo();

			if ( isset($error[2]) )
				throw new exception($error[2]);
		}
	} # exec()
	
	
	/**
	 * num_rows()
	 *
	 * @return int $num_rows
	 **/

	public function num_rows() {
		return $this->rowCount();
	} # num_rows()
	
	
	/**
	 * num_cols()
	 *
	 * @return void
	 **/

	public function num_cols() {
		return $this->columnCount();
	} # num_cols()
	
	
	/**
	 * get_results()
	 *
	 * @param array $args
	 * @return array $results
	 **/

	public function get_results($args = null) {
		if ( isset($args) )
			$this->execute($args);

		return $this->fetchAll(PDO::FETCH_OBJ);
	} # get_results()


	/**
	 * get_row()
	 *
	 * @param array $args
	 * @return array $row
	 **/

	public function get_row($args = null) {
		if ( isset($args) )
			$this->execute($args);

		return $this->fetch(PDO::FETCH_OBJ);
	} # get_row()
	
	
	/**
	 * get_col()
	 *
	 * @param array $args
	 * @return array $col
	 **/

	public function get_col($args = null) {
		if ( isset($args) )
			$this->execute($args);

		return $this->fetchAll(PDO::FETCH_COLUMN);
	} # get_col()
	
	
	/**
	 * get_var()
	 *
	 * @param array $args
	 * @return mixed $var
	 **/

	public function get_var($args = null) {
		if ( isset($args) )
			$this->execute($args);

		return $this->fetchColumn();
	} # get_var()
} # dbs
?>