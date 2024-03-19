<?php
	// DB Verbindungsparameter
	define("HOST", "");
	define("USER", "");
	define("PASSWORD", "");
	define("DATABASE", "");

	// Neuer MySQL Connect
	$mysqli = mysqli_connect(HOST, USER, PASSWORD, DATABASE);

	// Setze alle Anfragen auf UTF-8
	mysqli_query($mysqli, "SET NAMES 'utf8'");
?>
