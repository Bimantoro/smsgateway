<?php 
	$server		= "localhost";
	$username	= "pat";
	$password	= "pat";
	$database	= "db_sms";

	$koneksi=mysql_connect($server,$username,$password);

	if(!$koneksi){
		echo "ada kesalahan saat menghubungkan dengan database";
	}else{
		mysql_select_db($database);
	}


 ?>