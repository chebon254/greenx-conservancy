<?php

	$errorMSG = "";

	// FIRSTNAME
	if (empty($_POST["fname"])) {
		$errorMSG = "First Name is required. ";
	} else {
		$fname = filter_var($_POST["fname"], FILTER_SANITIZE_STRING);
	}

	// LASTNAME
	if (empty($_POST["lname"])) {
		$errorMSG = "Last Name is required. ";
	} else {
		$lname = filter_var($_POST["lname"], FILTER_SANITIZE_STRING);
	}

	// EMAIL
	if (empty($_POST["email"])) {
		$errorMSG .= "Email is required. ";
	} else {
		$email = filter_var($_POST["email"], FILTER_SANITIZE_EMAIL);
		if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			$errorMSG .= "Invalid email format. ";
		}
	}

	// PHONE
	if (empty($_POST["phone"])) {
		$errorMSG .= "Phone is required. ";
	} else {
		$phone = filter_var($_POST["phone"], FILTER_SANITIZE_STRING);
	}

	// MESSAGE
	if (empty($_POST["message"])) {
		$errorMSG .= "Message is required. ";
	} else {
		$message = filter_var($_POST["message"], FILTER_SANITIZE_STRING);
	}

	$subject = 'Contact Inquiry from Website';

	$EmailTo = "maryokeloconservancy@gmail.com";
    
	// prepare email body text
	$Body = "";
	$Body .= "fname: ";
	$Body .= $fname;
	$Body .= "\n";
	$Body .= "lname: ";
	$Body .= $lname;
	$Body .= "\n";
	$Body .= "Email: ";
	$Body .= $email;
	$Body .= "\n";
	$Body .= "Phone: ";
	$Body .= $phone;
	$Body .= "\n";
	$Body .= "Message: ";
	$Body .= $message;
	$Body .= "\n";

	// send email with proper headers
	$headers = "From: " . $email . "\r\n";
	$headers .= "Reply-To: " . $email . "\r\n";
	$headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
	$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

	$success = @mail($EmailTo, $subject, $Body, $headers);

	// redirect to success page
	if ($success && $errorMSG == ""){
	   echo "success";
	}else{
		if($errorMSG == ""){
			echo "Something went wrong :(";
		} else {
			echo $errorMSG;
		}
	}

?>