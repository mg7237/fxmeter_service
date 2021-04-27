<?php

// $result = $conn->query('SELECT');
// $data = $result->fetch_assoc();

function getConnectionObj () {
	$servername = "localhost";
	$username = "root";
	$password = "";
	$dbName = "store_subscription";

	// Create connection
	$conn = new mysqli($servername, $username, $password, $dbName);

	// Check connection
	if ($conn->connect_error) {
	    die("Connection failed: " . $conn->connect_error);
	}

	return $conn;
}

?>