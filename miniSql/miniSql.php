<?php 
namespace MiniSql;
use \PDO;
class MiniSql{
	// configuracion de la instancia
	private $process = [];
	// datos de preconfiguracion de coneccion
	private $config = [
		"engine"=> "mysql",
		"host"  => "localhost",
		"db"    => "",
		"user"  => "root",
		"pass"  => "",
		"port"  => "",
		"table" => ""
	];
	// estado inicial de la coneccion
	public $connect = false;
	// expreciones regulares de funciones
	private $regexp = [
		"function"  => "/([\w\d\-_\"\']+){1}\(([\w\d\-_\n\t\s\"\'.,<>=?!$%&]+){1}\)/",
	];
	// enseña la ultima query realizada
	public  $query   = "";
	/*
	la primera instancia es generica, llamar el metodo where, se vuelve a instanciar 
	pero mantiene la primera connecion, por lo que no se genera una nueva instancia PDO,
	*/
	public function __construct($config,$instance=null,$connect=null){
		$this->process = (object)$this->process;
		$this->regexp = (object)$this->regexp;
		foreach ($this->config as $key => $value) {
			if(isset($config[$key])){
				$this->config[$key]=$config[$key];
			}
		}
		$this->process->config = $config;
		$this->config  = (object)$this->config;
		if($instance){
			$this->process->where = $instance;
		}
		if($connect){
			$this->connect = $connect;
		}else{
			$this->getConnect();
		}
	}
	/*
	define la coneccion en post de la configuracion dada al cosntructor
	*/
	public function getConnect(){
		$c = $this->config;
		if(!$this->connect){
			$this->connect = new PDO("{$c->engine}:host={$c->host};dbname={$c->db}",$c->user,$c->pass);
		}
		return $this->connect;
	}
	/*
	determina el tipo de consulta.
	*/
	private function setSelect($op1=null,$op2=null,$op3=null){
		$query   = $this->process;
		$c = $this->config;
		$return  = false;
		$connect = false;
		if(isset($query->where)){
			$string = "";
			$string = $this->setWhere($query->where,$string);
			if(!!!$string){
				$query->where = false;
			}
		}
		if(isset($query->where)){
			if(isset($query->update)){

			}elseif(isset($query->remove)){
				
			}else{
				if($op1){
					$join   = implode($this->sanitate($op1,"string"),",");
					$return = "SELECT {$join} FROM {$c->table} WHERE {$string}";	
				}else{
					$return = "SELECT * FROM {$c->table} WHERE {$string}";
				}
				if($op2){
					$prepare = "";
					if(is_array($op2)){
						foreach ($op2 as $key => $value){
							$sort   = ($value>0)?"ASC":"DESC";
							$prepare.=(!!!$prepare)?" ORDER BY {$key} {$sort}":", {$key} {$sort}";
						}
						$return.= $prepare;
					}
				}
				if($op3){
					$return.=" LIMIT ".implode($op3,",");
				}
			}
		}else{
			if(isset($op1)){
				$join   = implode($this->sanitate($op1,"string"),",");
				$return = "SELECT {$join} FROM {$c->table}";
			}else{
				$return = "SELECT * FROM {$c->table}";
			}
		}
		return $this->exeQuery($return);
	}
	/*
	define un query de eliminacion
	*/
	private function setDelete(){
		$c = $this->config;
		if(isset($this->process->where)){
			$where = $this->setWhere($this->process->where);
			$query = "DELETE FROM {$c->table} WHERE {$where}";
		}else{
			$query = "DELETE FROM {$c->table}";
		}
		return $this->exeQuery($query);
	}
	/*
	define un query de añadir datos
	*/
	private function setInsert($query){
		$c = $this->config;
		$colName  = [];
		$colValue = [];
		foreach ($query as $key => $value) {
			array_push($colName,$this->sanitate($key,"string"));
			array_push($colValue,$this->sanitate($value,"string"));
		}
		$colName = implode($colName,", ");
		$colValue = implode($colValue,"', '");
		$query = "INSERT INTO {$c->table} ({$colName}) VALUES ('{$colValue}');";
		return $this->exeQuery($query);
	}
	/*
	define un query de Actualizacion
	*/
	private function setUpdate($query){
		$c = $this->config;
		$string = "";
		$where  = false;
		foreach ($query as $key => $value) {
			$colName  = $this->sanitate($key,"string");
			$colValue = $this->sanitate($value,"string");
			$string.= (!!$string)?", {$colName}='{$colValue}'":"{$colName}='{$colValue}'";
		}
		if(isset($this->process->where)){
			$where = $this->setWhere($this->process->where);
			$query = "UPDATE {$c->table} SET {$string} WHERE {$where}";
		}else{
			$query = "UPDATE {$c->table} SET {$string}";
		}
		return $this->exeQuery($query);
	}
	/*
	construye where en funcion del array
	*/
	private function setWhere($query,$string=""){
		if(is_array($query)){
			foreach ($query as $key => $value) {
				if(is_array($value)){
					$prepare = "";
					$prepare = $this->setWhere($value,$prepare);
					if(!!$prepare){
						$string.= (!!!$string)?"({$prepare}) ":" OR ({$prepare}) ";
					}
				}else{
					$prepare = "";
					$test = $this->setFunction($value);
					if(is_object($test)){
						switch ($test->type) {
							case 'not':
								if(strpos($test->arguments, "?")!==false){
									$val = str_replace("?", $key, $test->arguments);
									$prepare.="NOT {$val} ";
								}else if(!!$test->arguments){
									$prepare.="NOT {$key}='{$test->arguments}' ";
								}
							break;
							case 'in':
								$split = explode(",", $test->arguments);
								$join  = implode("','", $split);
								$prepare.= "{$key} IN ('{$join}')";
							break;
							case 'range':
								$split = explode("-", $test->arguments);
								$prepare.="{$key} BETWEEN '{$split[0]}' AND '{$split[1]}'";
							break;
							case 'like':
								$prepare.="{$key} LIKE '{$test->arguments}' ";
							break;
						}
						if(!!$prepare){
							$string.= (!!!$string)? $prepare : "AND {$prepare}";
						}
					}else{
						if(!!$value){
							$value  = $this->sanitate($value,"string");
							$string.= (!!!$string)?"{$key}='{$value}' ":"AND {$key}='{$value}' ";
						}
					}
				}
			}
		}
		return $string;
	}
	/*
	determina si un string adjuntado posee forma de funcion
	*/
	private function setFunction($str){
		if(preg_match($this->regexp->function, $str)){
			preg_match($this->regexp->function,$str,$resultado,PREG_OFFSET_CAPTURE);
			$value = $this->sanitate($resultado[2][0],"string");
			return (object)[
				"type"=>$resultado[1][0],
				"arguments"=>$value
			];
		}else{
			return $str;
		}
	}
	/*
	ejecuta y almacena el query para ser observado
	*/
	private function exeQuery($query){
		$this->query = $query;
		return $this->getConnect()->query($query);
	}
	/*
	sanea los datos que seran concadenados al a consulta
	*/
	private function sanitate($value,$type,$ouput=null){
		$array = [];
		if(is_array($value)){
			foreach ($value as $key => $value) {
				$array[$key]=$this->sanitate($value,$type);
			}
			$value = $array;
		}else{
			switch ($type) {
				case "number":
					return (is_numeric($value))?(float)$value:0;
				break;
				case "string":
					return addslashes(htmlentities(html_entity_decode($value)));
				break;
			}
		}
		return $value;
	}
	/*
	arma la consulta de selecion
	*/	
	public function select($op1=null,$op2=null,$op3=null){
		$return = false;
		$query  = $this->setSelect($op1,$op2,$op3);
		if($query){
			$return = [];
			foreach ($query as $value){
				$prepare = [];
				foreach ($value as $key => $val) {
					if(!is_integer($key)){
						$prepare[$key]=$val;
					}
				}
				array_push($return,$prepare);
			}
		}
		return $return;
	}
	/*
	arma la consulta de busqueda
	*/
	public function where($query=[],$append=false){
		return new self($this->process->config,$query,$this->connect);
	}
	/*
	arma la consulta de actualizacion
	*/
	public function update($query){
		$this->setUpdate($query);
		return $this;
	}
	/*
	arma la consulta de insertar
	*/
	public function insert($query){
		$this->setInsert($query);
		return $this;
	}
	/*
	arma la consulta de eliminacion
	*/
	public function delete(){
		$this->setDelete();
		return $this;
	}
	/*
	destruye la connecion
	*/
	public function __destruct(){
		$this->connect = null;
	}
}