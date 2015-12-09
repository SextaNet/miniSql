<?php

require "simple/simple.php";

$table = new Simple\Simple([
	"db"	 => "test",
	"table"  => "ejemplo",
	"user"   => "root",
	"pass"   => ""
]);

$where_1 = $table->where([
	["name" => "like(%m%)"],
	["name" => "like(%j%)"],
	["age"  => "range(0-50)"]
]);

$data = json_encode( $where_1->select() );

echo "
	{$data}
	<br><hr><br>
	{$where_1->query}
";

	
