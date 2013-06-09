#!/usr/local/bin/php -q
<?php
//include( <path to app.php> );

use php\core\ConsoleException;
use php\util\Hashtable;
use php\ConsoleApp;
use php\sql\ConnectionFactory;

class QueryCompBuilder extends ImawebConsoleApp {
	protected $foreground_colors = array(
			'black' 		=>'0;30', 'dark_gray' 		=>'1;30',
			'blue' 			=>'0;34', 'light_blue' 		=>'1;34',
			'green' 		=>'0;32', 'light_green' 	=>'1;32',
			'cyan' 			=>'0;36', 'light_cyan' 		=>'1;36',
			'red'	 		=>'0;31', 'light_red' 		=>'1;31',
			'purple' 		=>'0;35', 'light_purple' 	=>'1;35',
			'brown' 		=>'0;33', 'yellow' 		=>'1;33',
			'light_gray'		=>'0;37', 'white' 		=>'1;37'
			);

	protected function myPrintf ($format, $string, $colour = 'light_green') {
		return sprintf("\033[%sm".$format,$this->foreground_colors[$colour],$string);
	}

	protected function main( $argc, $argv ) {
		//add arguments
		$this->addArg('DB1*','First Database');
		$this->addArg('TB1*','First Database Table');
		$this->addArg('DB2*','Second Database');
		$this->addArg('TB2*','Second Database Table');
		$this->addOpt('s|showresults','Show Results');
		$this->addOpt('i|interactive','Show Results Interactively');
		$this->addOpt('f|fields:','Fields to use, comma separated');
		$this->addOpt('w|where:' ,'Adding where clause in query, the where clause must be quoted ');

		$this->getopts($argc, $argv, 2);

		$db1 = $this->arg('DB1');
		$tb1 = $this->arg('TB1');
		$db2 = $this->arg('DB2');
		$tb2 = $this->arg('TB2');

		//build table names
		$one = $db1.'__'.$tb1;
		$two = $db2.'__'.$tb2;
		
		//we take fields_one as reference to compare but must use lesser number of fields
		if (!$this->opt('f')) {
			$fields_one = $this->getTableFields($db1,$tb1);
			$fields_two = $this->getTableFields($db2,$tb2);

			$field_count = min(count($fields_one), count($fields_two));
			$field_list = array_slice($fields_one, 0, $field_count);

			$f1 = array();
			foreach ($field_list as $index => $f) {
				$f1[] = $this->buildFieldName($fields_one[$index], $one, $f);
			}
			$f2 = array();
			foreach ($field_list as $index => $f) {
				$f2[] = $this->buildFieldName($fields_two[$index], $two, $f);
			}
		} else {
			$field_list = explode(',',$this->optArg('f'));
			$f1 = array();
			foreach ($field_list as $index => $f) {
				$f1[] = $this->buildFieldName($f, $one, $f);
			}
			$f2 = array();
			foreach ($field_list as $index => $f) {
				$f2[] = $this->buildFieldName($f, $two, $f);
			}
		}
		
		$w1 = $w2 =  "";
		
		if($this->optArg('w')){
			$w1 = $w2 =  $this->optArg('w');
		}


		$query = sprintf($this->getSimpleQuery()
			, implode(',',$field_list)
			, $one
			, implode(',',$f1)
			, $db1
			, $tb1
			, $one
			, $w1
			, $two
			, implode(',',$f2)
			, $db2
			, $tb2
			, $two
			, $w2
			, implode(',',$field_list)
			, implode(',',$field_list)
			);
		$conn = ConnectionFactory::getDefault();
		
		var_dump($conn);
		
		$stmt = $conn->prepare($query);
	
		echo $stmt->getQuery().PHP_EOL;
		
		$res = $stmt->execute();

		echo sprintf ('[%-50s - %50s]%30s' , $db1.'.'.$tb1,$db2.'.'.$tb2,$res->selectedRows() ) . PHP_EOL;

		if ($this->opt('s') && !$this->opt('i')) {
			echo sprintf("%-30s",'tablename')."-";
			foreach ($field_list as $f)
				echo sprintf("%-30s",$f)."-";
			echo PHP_EOL;
			foreach ($res as $r) {
				echo sprintf("%-30s",$r->tablename)."-";
				foreach ($field_list as $f)
					echo sprintf("%-30s",$r->$f)."-";
				echo PHP_EOL;
			}
			echo PHP_EOL.PHP_EOL;
		} if ($this->opt('i')) {
			$this->interactivePrint( $res, $field_list );
		}

	}

	protected function buildFieldName($field, $table, $fieldName) {
		return $table.'.'.$field.' as '.$fieldName;
	}

	protected function getSimpleQuery() {
		return 'SELECT MIN(NiceSqlDiff) AS tablename, %s FROM ('.PHP_EOL.
				' SELECT \'%s\' AS NiceSqlDiff, %s FROM %s.%s AS %s %s'.PHP_EOL.
				' UNION ALL '.PHP_EOL.
				' SELECT \'%s\' AS NiceSqlDiff, %s FROM %s.%s AS %s %s '.PHP_EOL.
				' ) AS temp_table GROUP BY '.PHP_EOL.
				' %s HAVING COUNT(*) = 1 ORDER BY %s ;'.PHP_EOL;
	}

	protected function getTableFields( $database, $table ) {
		$conn = ConnectionFactory::getDefault();
		$sql = "select COLUMN_NAME from information_schema.COLUMNS where TABLE_SCHEMA = :database_name and TABLE_NAME = :table_name";
		$stmt = $conn->prepare($sql);
		$stmt->bind(':database_name',$database);
		$stmt->bind(':table_name',$table);
		$res = $stmt->execute();
		$ret = array();
		foreach ($res as $r) {
			$ret[] = $r->COLUMN_NAME;
		}

		return $ret;
	}

	protected function interactivePrint( $set ,$fields) {
		/*
		foreach ($set as $s)
		{
			$key = $set->key();
			foreach ($fields as $f)
			{
				printf("\033[%sm", $this->foreground_colors['light_green']);
				$n = $set->next();
				if ( $n && $n->$f != $s->$f ) {
					print $this->myPrintf("%-15s",$s->$f,"red").$this->myPrintf("%-15s",$n->$f,"red").PHP_EOL;
				} elseif ( $n) {
					print $this->myPrintf("%-20s",$f.' -> ');
					print $this->myPrintf("%-15s",$s->$f).$this->myPrintf("%-15s",$n->$f).PHP_EOL;
				} else {
					print $this->myPrintf("%-20s",$f.' -> ');
					print $this->myPrintf("%-4s",$s->$f,'red').PHP_EOL;
				}
				printf("\033[%sm", $this->foreground_colors['light_green']);
				//$s = $set->prev();
			}
			echo PHP_EOL;
			$what = $this->readc('?');
			if ($what == 'q') {
				echo 'GOODBYE'.PHP_EOL;
				exit(0);
			} else if ($what == 'p') {
				//$pos--;
				//$set->prev();
			} else if ($what == 'n') {
			}
		}
		echo PHP_EOL;*/




		for ($pos = 0;$pos < $set->selectedRows(); $pos++) 
		{
			$set->rewind();
			$set->seek($pos);
			$s = clone $set->next();
			if ($pos < $set->selectedRows()+1)
				$n = clone $set->next();
			print $this->myPrintf("%-40s",'table -> ','blue');
			if ( $s ) print $this->myPrintf("%-45s",$s->tablename,"blue");
			if ( $n ) print $this->myPrintf("%-45s",$n->tablename,"blue");
			echo PHP_EOL;
			foreach ($fields as $f)
			{
				printf("\033[%sm", $this->foreground_colors['light_green']);
				if ( $n && $n->$f != $s->$f ) {
					print $this->myPrintf("%-40s",$f.' -> ');
					print $this->myPrintf("%-45s",$s->$f,"red").$this->myPrintf("%-45s",$n->$f,"red").PHP_EOL;
				} elseif ( $n ) {
					print $this->myPrintf("%-40s",$f.' -> ');
					print $this->myPrintf("%-45s",$s->$f).$this->myPrintf("%-45s",$n->$f).PHP_EOL;
				} else {
					print $this->myPrintf("%-40s",$f.' -> ');
					print $this->myPrintf("%-45s",$s->$f,'red').PHP_EOL;
				}
				printf("\033[%sm", $this->foreground_colors['light_green']);
			}
			echo PHP_EOL;
			unset ($s);
			unset ($n);
			$what = $this->readc('?');
			if ($what == 'q') {
				echo 'GOODBYE'.PHP_EOL;
				exit(0);
			} else if ($what == 'p') {
				$pos--;
			} else /*if ($what == 'n')*/ {
			}
		}
		echo PHP_EOL;
	}
}

QueryCompBuilder::run();
