<?php

require "miniSql/miniSql.php";

$table = new MiniSql\MiniSql([
	"db"	 => "blog",
	"table"  => "articulos",
	"user"   => "root",
	"pass"   => ""
]);

$ejemplo = $table->where([
	"id"=>"1"
]);
	
echo json_encode($ejemplo->select());