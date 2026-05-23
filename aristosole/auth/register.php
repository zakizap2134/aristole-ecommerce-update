<?php
// =====================================================
// Registration Page - Uses password_hash() for security
// =====================================================

require_once __DIR__ . '/../config/db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($full_name) || empty($email) || empty($password)) {
        $error = "Please fill in all fields.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        // Check if email exists
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);
        
        if ($stmt->fetch()) {
            $error = "Email already registered.";
        } else {
            // password_hash() - Requirement
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            // Prepared Statement
            $sql = "INSERT INTO users (full_name, email, password_hash, role) VALUES (:full_name, :email, :password_hash, 'customer')";
            $stmt = $pdo->prepare($sql);
            
            try {
                $stmt->execute([
                    ':full_name' => $full_name,
                    ':email' => $email,
                    ':password_hash' => $password_hash
                ]);
                $success = "Registration successful! You can now login.";
            } catch (PDOException $e) {
                $error = "Registration failed. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register - AristoSole</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f0f2f5; }
        .container { max-width: 500px; margin: 50px auto; padding: 20px; }
        .card { background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .card-header { background: #2c3e50; color: white; padding: 15px; border-radius: 10px 10px 0 0; text-align: center; }
        .card-body { padding: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        button { background: #27ae60; color: white; padding: 12px; border: none; border-radius: 5px; cursor: pointer; width: 100%; font-size: 16px; }
        button:hover { background: #219a52; }
        .alert-danger { padding: 10px; background: #e74c3c; color: white; border-radius: 5px; margin-bottom: 15px; text-align: center; }
        .alert-success { padding: 10px; background: #27ae60; color: white; border-radius: 5px; margin-bottom: 15px; text-align: center; }
        .text-center { text-align: center; margin-top: 15px; }
        a { color: #3498db; text-decoration: none; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h3>👟 Create AristoSole Account</h3>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert-success"><?php echo $success; ?> <a href="login.php" style="color: white;">Login here</a></div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="full_name" required>
                    </div>
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label>Password (min 6 characters)</label>
                        <input type="password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label>Confirm Password</label>
                        <input type="password" name="confirm_password" required>
                    </div>
                    <button type="submit">Register</button>
                </form>
                <div class="text-center">
                    <a href="login.php">Already have an account? Login</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>