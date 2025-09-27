<?php 
require_once 'includes/auth.php';

$error_message = '';

if (isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $user_type = $_POST['user_type'] ?? 'Patient';
    
    if (empty($email) || empty($password)) {
        $error_message = 'Please enter both email and password.';
    } else {
        $result = $auth->login($email, $password, $user_type);
        
        if ($result['success']) {
            
            switch ($user_type) {
                case 'Doctor':
                    header("Location: doctor_dashboard.php");
                    break;
                case 'Admin':
                    header("Location: admin_dashboard.php");
                    break;
                default:
                    header("Location: index.php");
                    break;
            }
            exit();
        } else {
            $error_message = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Pakenham Hospital</title>
   <link rel="stylesheet" href="/pms5/css/modern-style.css?v=1">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

</head>
<body>
    <div class="landing-container">
        <div class="card" style="max-width: 420px; margin: 0 auto;">
            
            <div class="text-center mb-6">
                <img src="images/healthcare.png" alt="Pakenham Hospital" style="width: 60px; height: 60px; margin: 0 auto 1rem; object-fit: contain;">
                <h1 style="color: var(--primary-800); margin-bottom: 0.5rem;">Welcome Back</h1>
                <p style="color: var(--gray-600);">Sign in to your Pakenham Hospital account</p>
            </div>

            
            <?php if ($error_message): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            
            <form method="POST" class="form-container" style="max-width: none; padding: 0;">
                <div class="form-group">
                    <label class="form-label">User Type</label>
                    <div class="input-group">
                        <select name="user_type" class="form-select" required>
                            <option value="Patient" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] === 'Patient') ? 'selected' : ''; ?>>Patient</option>
                            <option value="Doctor" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] === 'Doctor') ? 'selected' : ''; ?>>Doctor</option>
                            <option value="Admin" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] === 'Admin') ? 'selected' : ''; ?>>Administrator</option>
                        </select>
                        <div class="input-icon">
                            <i class="fas fa-user-tag"></i>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <div class="input-group">
                        <input type="email" name="email" class="form-input" placeholder="Enter your email" 
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                        <div class="input-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Password</label>
                    <div class="input-group">
                        <input type="password" name="password" class="form-input" placeholder="Enter your password" required>
                        <div class="input-icon">
                            <i class="fas fa-lock"></i>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <button type="submit" name="login" class="btn btn-primary btn-full">
                        <i class="fas fa-sign-in-alt"></i>
                        Sign In
                    </button>
                </div>
            </form>

            
            <div class="text-center mt-6" style="padding-top: 1.5rem; border-top: 1px solid var(--gray-200);">
                <p style="color: var(--gray-600); margin-bottom: 1rem;">
                    Don't have an account? 
                    <a href="register.php" style="color: var(--primary-600); font-weight: 600;">Create one here</a>
                </p>
                <a href="index.php" style="color: var(--gray-500); font-size: 0.875rem;">
                    <i class="fas fa-arrow-left"></i> Back to Home
                </a>
            </div>
        </div>
    </div>
</body>
</html>

<?php
if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $pass = $_POST['password'];

    $result = $conn->query("SELECT * FROM Patients WHERE email='$email'");
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (password_verify($pass, $row['password'])) {
            $_SESSION['patient_id'] = $row['patient_id'];
            header("Location: index.php");
        } else {
            echo "Invalid password!";
        }
    } else {
        echo "No user found!";
    }
}
?>
