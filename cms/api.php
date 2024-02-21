<?php

// Enable CORS (Cross-Origin Resource Sharing) to allow requests from any origin
header('Access-Control-Allow-Origin: *');

// Start output buffering
ob_start();

// Set the default timezone to Asia/Manila
date_default_timezone_set('Asia/Manila');

// Establish a MySQL database connection
$GLOBALS['conn'] = mysqli_connect('127.0.0.1', 'sso_user', 'sso_user', 'sso');

// Ensure that the request is a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  // Retrieve the global connection variable
  $conn = $GLOBALS['conn'];

  // Read JSON data from the request
  $json_data = file_get_contents('php://input');
  $data = json_decode($json_data, true);

  // Check if the JSON decoding was successful
  if ($data === null) {
      // Respond with a 400 Bad Request if JSON is invalid
      http_response_code(400);
      echo json_encode(['error' => 'Invalid JSON']);
  } else {

    // Check the operation mode specified in the request data
    if ($data['mode'] == 'GENERATE') {
      // Generate an MD5 hash and echo it
      echo md5($data['hashdate']);
    }

    if ($data['mode'] == 'CHECK') {
      // Check if a user is logged in based on provided authentication information
      $userauth = $data['auth'];
      $loggedIn = false;

      $result = mysqli_query($conn, "select userauth from users where (userauth='$userauth')");
      while ($item = mysqli_fetch_object($result)) {
        $loggedIn = true;
      }

      if ($loggedIn == true) {
        echo 'LOGGEDIN';
      } else {
        echo 'EXPIRED';
      }
    }

    if ($data['mode'] == 'LOGIN') {
      // Validate user login credentials and update user authentication token
      $username = $data['username'];
      $password = $data['password'];
      $hashdata = md5($data['hashdate']);
      $hasUser = false;

      $result = mysqli_query($conn, "select username from users where (username='$username' and password='$password')");
      while ($item = mysqli_fetch_object($result)) {
        $hasUser = true;
      }

      if ($hasUser == true) {
        // Update user authentication token
        mysqli_query($conn, "update users set userauth = '$hashdata' where (username = '$username')");

        if (mysqli_error($conn) == '') {
          echo 'SUCCESS';
        } else {
          echo mysqli_error($conn);
        }

      } else {
        echo 'NOT_REGISTERED';
      }
    }

    if ($data['mode'] == 'LOGOUT') {
      // Logout user by resetting authentication token to 'X'
      $userauth = $data['auth'];

      $result = mysqli_query($conn, "select username from users where (userauth='$userauth')");
      while ($item = mysqli_fetch_object($result)) {
        mysqli_query($conn, "update users set userauth = 'X' where (username = '$item->username')");
      }

    }

    // Check if the request is for user registration
    if ($data['mode'] == 'REGISTER') {
      // Extract relevant data from the JSON and insert user information into the 'users' table
      $username = $data['username'];
      $password = $data['password'];
      $hashdata = md5($data['hashdate']);

      mysqli_query($conn, "insert into users set username = '$username', password = '$password', userauth = '$hashdata'");

      // Check if the user insertion was successful
      if (mysqli_affected_rows($conn) == 1) {
        echo 'SUCCESS';
      } else {
        // Respond with the MySQL error if user insertion failed
        echo mysqli_error($conn);
      }
    }

    if ($data['mode'] == 'GETUSERS') {
      // Retrieve and echo the list of usernames from the 'users' table
      $output = array();
      $i = 0;

      $result = mysqli_query($conn, "select username from users order by id desc");
      while ($item = mysqli_fetch_object($result)) {
        $output[$i]['username'] = $item->username;
        $i++;
      }

      header('Content-Type: application/json');
      echo json_encode($output);

    }

    if ($data['mode'] == 'REMOVE') {
      // Remove a user based on the provided username
      $username = $data['username'];

      mysqli_query($conn, "delete from users where (username = '$username')");

      if (mysqli_affected_rows($conn) == 1) {
        echo 'SUCCESS';
      } else {
        echo mysqli_error($conn);
      }
    }

  }
} else {
  // Respond with a 405 Method Not Allowed if the request method is not POST
  http_response_code(405);
  echo json_encode(['error' => 'Invalid request method']);
}

// Closing PHP tag
?>
