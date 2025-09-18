<?php
require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Load .env file using the correct constant __DIR__
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();

    // Database connection details
    $servername = $_ENV['DB_HOST'];
    $username   = $_ENV['DB_USER'];
    $password   = $_ENV['DB_PASS'];
    $port       = $_ENV['DB_PORT'];
    $dbname     = $_ENV['DB_NAME'];

    // Create a connection to the database
    $conn = new mysqli($servername, $username, $password, $dbname, $port);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Get form data
    $userEmail = $_POST['email'];
    $userName = $_POST['name'];

    // Validate email format
    if (filter_var($userEmail, FILTER_VALIDATE_EMAIL)) { 
        
        // Send email using PHPMailer
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = $_ENV['SMTP_USER'];
            $mail->Password   = $_ENV['SMTP_PASS'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = 465;

            //Recipients
            $mail->setFrom('shana.githu@strathmore.edu', 'BBIT 2.2');
            $mail->addAddress($userEmail, $userName);

            //Content
            $mail->isHTML(true);
            $mail->Subject = 'Welcome to BBIT 2.2! Account Verification';
            $mail->Body    = "Hello " . htmlspecialchars($userName) . ",<br><br>" . 
                             "You requested an account on BBIT 2.2.<br><br>" . 
                             "In order to use this account you need to <a href='#'>Click Here</a> to complete the registration process.<br><br>" . 
                             "Regards,<br>Systems Admin<br>BBIT 2.2";
            
            // Send the email
            $mail->send();
            echo 'Message has been sent successfully!';

            // Insert user into the database
            $stmt = $conn->prepare("INSERT INTO app_users (name, email) VALUES (?, ?)");
            $stmt->bind_param("ss", $userName, $userEmail);

            if ($stmt->execute()) {
                echo "<br>User registered successfully in the database.";
                $stmt = $conn->prepare("SELECT * FROM app_users");

                echo"<br> Current users in the database:>";
                $stmt->execute();
                $result = $stmt->get_result();
                while( $row = $result->fetch_assoc() ) {
                    echo "Name: " . htmlspecialchars(string: $row['name']). "Email: " . htmlspecialchars(string: $row['email']). "<br>";
                }
            } else {
                echo "<br>Error: " . $stmt->error;
            }
            $stmt->close();
            
        } catch (Exception $e) {
            echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
        
    } else {
        echo "Invalid email address."; 
    }

    // Close the database connection
    $conn->close();
}

?>
