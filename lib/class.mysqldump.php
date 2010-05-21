<?php

	Class MySQLDump{
	
		const DATA_ONLY = 1;
		const STRUCTURE_ONLY = 2;
		const ALL = 3;

		private $connection;
	
		public function __construct(Database $connection){
			$this->connection = $connection;
		}
		
		public function listTables($match=NULL, $include_addional_information=true){
			$query = 'SHOW TABLES' . (!is_null($match) ? " LIKE '{$match}'" : NULL);

			$rows = $this->connection->query(str_replace('%', '%%', $query));
			$rows->resultOutput = DatabaseResultIterator::RESULT_ARRAY;

			$result = array();

			foreach ($rows as $item){
				$table = current(array_values($item));
				if($include_addional_information === true){
					$result[$table]            = array();
					$result[$table]['fields']  = $this->__getTableFields($table);
					$result[$table]['indexes'] = $this->__getTableIndexes($table);
					$result[$table]['type']    = $this->__getTableType($table);
				}
				else{
					$result[] = $table;
				}
			}

			return $result;
		}
		
		public function export($match=NULL, $flag=self::ALL, $condition=NULL){
			$result = NULL;

			$tables = $this->listTables($match);

			foreach ($tables as $name => $info){
			
				if($flag == self::ALL || $flag == self::STRUCTURE_ONLY){
					$result .= PHP_EOL . "-- *** STRUCTURE: `{$name}` ***" . PHP_EOL;
					$result .= "DROP TABLE IF EXISTS `{$name}`;" . PHP_EOL;
					$result .= $this->__dumpTableSQL($name, $info['type'], $info['fields'], $info['indexes']);
				}
			
				if($flag == self::ALL || $flag == self::DATA_ONLY){
					
					$data = $this->__dumpTableData ($name, $info['fields'], $condition);
					if(strlen(trim($data)) == 0) continue;
					
					$result .= PHP_EOL . "-- *** DATA: `{$name}` ***" . PHP_EOL;
					if(strtoupper($info['type']) == 'INNODB'){
						$result .= 'SET FOREIGN_KEY_CHECKS = 0;' . PHP_EOL;
					}
					
					$result .= $data;
					
					if(strtoupper($info['type']) == 'INNODB'){
						$result .= 'SET FOREIGN_KEY_CHECKS = 1;' . PHP_EOL;
					}
				}
			}

			return $result;
		}
	
		private function __dumpTableData($name, $fields, $condition=NULL){
			$fieldList = join (', ', array_map (create_function ('$x', 'return "`$x`";'), array_keys ($fields)));
			
			$query = "SELECT {$fieldList} FROM `{$name}`";
			
			if(!is_null($condition)){
				$query .= ' WHERE ' . $condition;
			}
			
			$rows = $this->connection->query($query);

			$value = NULL;

			if($rows->length() <= 0) return NULL;

			foreach ($rows as $row){
				$value .= "INSERT INTO `{$name}` ({$fieldList}) VALUES (";
				$fieldValues = array();
			
				foreach ($fields as $fieldName => $info){
					$fieldValue = $row->$fieldName;

					if($info['null'] == 1 && strlen(trim($fieldValue)) == 0){
						$fieldValues[] = 'NULL';
					}
					
					elseif(substr($info['type'], 0, 4) == 'enum'){
						$fieldValues[] = "'{$fieldValue}'";
					}
					
					elseif(is_numeric ($fieldValue)){
						$fieldValues[] = $fieldValue;
					}
					
					else{
						$fieldValues[] = "'" . mysql_real_escape_string ($fieldValue) . "'";
					}
				}

				$value .= join (', ', $fieldValues) . ");" . PHP_EOL;

			}

			return $value;
		}
	
		private function __dumpTableSQL($table, $type, $fields, $indexes){

			$query = "SHOW CREATE TABLE `{$table}`";
			$result = $this->connection->query($query);
			$result->resultOutput = DatabaseResultIterator::RESULT_ARRAY;

			$result = array_values($result->current());
			return $result[1] . ";" . PHP_EOL;
		}
		
		private function __getTableType($table){
			$query = sprintf("SHOW TABLE STATUS LIKE '%s'", addslashes($table));
			$info = $this->connection->query($query);
			return $info->current()->Type;
		}

		private function __getTableFields($table){
			$result = array();
			$query  = "DESC `{$table}`";
			$fields = $this->connection->query($query);

			foreach ($fields as $field){
				$name    = $field->Field;
				$type    = $field->Type;
				$null    = (strtoupper($field->Null) == 'YES');
				$default = $field->Default;
				$extra   = $field->Extra;

				$field = array(
					'type'    => $type,
					'null'    => $null,
					'default' => $default,
					'extra'   => $extra
				);
			
				$result[$name] = $field;
			}

			return $result;
		}

		private function __getTableIndexes($table){
			$result  = array();
			$query   = "SHOW INDEX FROM `{$table}`";
			$indexes = $this->connection->query($query);

			foreach ($indexes as $index){
				$name     = $index->Key_name;
				$unique   = !$index->Non_unique;
				$column   = $index->Column_name;
				$sequence = $index->Seq_in_index;
				$length   = $index->Cardinality;

				if(!isset ($result[$name])){
					$result[$name] = array();
					$result[$name]['columns'] = array();
					if(strtoupper ($name) == 'PRIMARY'){
						$result[$name]['type'] = 'PRIMARY KEY';
					}
					elseif($unique){
						$result[$name]['type'] = 'UNIQUE';
					}
					else {
						$result[$name]['type'] = 'INDEX';
					}
				}

				$result[$name]['columns'][$sequence-1] = array('name' => $column, 'length' => $length);
			}

			return $result;
		}
	}
