<?php

class DB {
	
	private $handle;
	
	function __construct() {
		
		mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

		$this->handle = new mysqli(_DB_HOST, _DB_USER, _DB_PASSWORD, _DB_DATABASE);
		$this->handle->set_charset('utf8mb4');
	
	}
	
	function list($table, $columns, $condition, $conditionParameterTypes, $conditionParameterValues, $sortColumn, $sortDirection) {
		
		$request = $this->handle->stmt_init();
		$request->prepare('SELECT `' . implode('`,`', $columns) . '` FROM ' . $table . ' WHERE ' . $condition . ' ORDER BY `' . $sortColumn . '` ' . $sortDirection);
		if($conditionParameterTypes !== '') {
			$request->bind_param($conditionParameterTypes, ...$conditionParameterValues);
		}
		$request->execute();
		
		$result = $request->get_result();
		$rows = $result->fetch_all(MYSQLI_ASSOC);
		
		return $rows;
		
	}
	
	function get($table, $columns, $id) {
		
		$request = $this->handle->stmt_init();
		$request->prepare('SELECT `' . implode('`,`', $columns) . '` FROM ' . $table . ' WHERE `id` = ?');
		$request->bind_param('s', $id);
		$request->execute();
		
		$result = $request->get_result();
		$rows = $result->fetch_all(MYSQLI_ASSOC);
		
		if(count($rows) < 1) {
			throw new Exception('Id not found in database');
		}
		
		return $rows[0];
		
	}
	
	function getVersion($table, $id) {
		
		$request = $this->handle->stmt_init();
		$request->prepare('SELECT `version` FROM ' . $table . ' WHERE `id` = ?');
		$request->bind_param('s', $id);
		$request->execute();
		
		$result = $request->get_result();
		$rows = $result->fetch_all(MYSQLI_ASSOC);
		
		if(count($rows) < 1) {
			return NULL;
		}
		
		return intval($rows[0]['version']);
		
	}
	
	function put($table, $valueTypes, $values) {
		
		$currentVersion = $this->getVersion($table, $values['id']);
		$isUpdate = $currentVersion !== NULL;
		
		if($values['version'] !== $currentVersion) {
			throw new Exception('Record was changed meanwhile');
		}
		
		$values['version'] = time();
		
		$request = $this->handle->stmt_init();
		if($isUpdate) {
			$request->prepare('UPDATE ' . $table . ' SET `' . implode('` = ?,`', array_keys($values)) . '` = ? WHERE `id` = ?');
			$request->bind_param($valueTypes . 's', ...array_merge(array_values($values), array($values['id'])));
		} else {
			$request->prepare('INSERT INTO ' . $table . ' (`' . implode('`,`', array_keys($values)) . '`) VALUES (' . str_repeat('?,', count($values) - 1) . '?)');
			$request->bind_param($valueTypes, ...array_values($values));
		}
		$request->execute();
		
		$rowCount = $request->affected_rows;
		
		if($rowCount < 1) {
			throw new Exception('No record added to database');
		}
		
	}
	
	function delete($table, $id, $version) {
		
		if($version !== $this->getVersion($table, $id)) {
			throw new Exception('Record was changed meanwhile');
		}
		
		$request = $this->handle->stmt_init();
		$request->prepare('DELETE FROM ' . $table . ' WHERE `id` = ? LIMIT 1');
		$request->bind_param('s', $id);
		$request->execute();
		
		$rowCount = $request->affected_rows;
		
		if($rowCount < 1) {
			throw new Exception('Id not found in database');
		}
		
	}
	
}

?>