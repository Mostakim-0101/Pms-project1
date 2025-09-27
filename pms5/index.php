<?php 
require_once 'includes/auth.php';


$patient_name = '';
$patient_stats = [];
if($auth->isLoggedIn()) {
    $user = $auth->getCurrentUser();
    $patient_id = $user['user_id'];
    $patient_name = $user['full_name'];
    
    
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM Appointments WHERE patient_id = ?");
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $patient_stats['total_appointments'] = $result->fetch_assoc()['total'];
    
    $stmt = $conn->prepare("SELECT COUNT(*) as upcoming FROM Appointments WHERE patient_id = ? AND appointment_date >= CURDATE() AND status IN ('Scheduled', 'Confirmed')");
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $patient_stats['upcoming_appointments'] = $result->fetch_assoc()['upcoming'];
    
    $stmt = $conn->prepare("SELECT COUNT(*) as waitlist FROM Waitlist WHERE patient_id = ? AND status = 'Waiting'");
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $patient_stats['waitlist_count'] = $result->fetch_assoc()['waitlist'];
    
    $stmt = $conn->prepare("SELECT COUNT(*) as open_tickets FROM SupportTickets WHERE patient_id = ? AND status IN ('Open', 'In_Progress')");
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $patient_stats['open_tickets'] = $result->fetch_assoc()['open_tickets'];
    
    $stmt = $conn->prepare("SELECT COUNT(*) as attendance FROM Attendance WHERE patient_id = ? AND status = 'Present'");
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $patient_stats['attendance_count'] = $result->fetch_assoc()['attendance'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pakenham Hospital - Patient Management System</title>
    <link rel="stylesheet" href="/pms5/css/modern-style.css?v=1">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

</head>
<body>
    <?php if($auth->isLoggedIn()): 
        
        $user = $auth->getCurrentUser();
        if ($user['user_type'] === 'Doctor') {
            header("Location: doctor_dashboard.php");
            exit();
        } elseif ($user['user_type'] === 'Admin') {
            header("Location: admin_dashboard.php");
            exit();
        }
    ?>
        
        <div class="dashboard-container">
            
            <nav class="sidebar">
                <div class="logo-section">
                    <img src="images/logo.jpeg" alt="Pakenham Hospital" class="logo" style="width: 40px; height: 40px; object-fit: contain;">
                    <h2>Pakenham Hospital</h2>
                </div>
                
                <ul class="nav-menu">
                    <li class="nav-item">
                        <a href="index.php" class="nav-link active">
                            <i class="fas fa-home icon"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="book_appointment.php" class="nav-link">
                            <i class="fas fa-calendar-plus icon"></i>
                            <span>Book Appointment</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="my_appointments.php" class="nav-link">
                            <i class="fas fa-calendar-check icon"></i>
                            <span>My Appointments</span>
                            <?php if($patient_stats['upcoming_appointments'] > 0): ?>
                                <span class="badge"><?php echo $patient_stats['upcoming_appointments']; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a href="attendance.php" class="nav-link">
                            <i class="fas fa-user-check icon"></i>
                            <span>Attendance</span>
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a href="disciplinary.php" class="nav-link">
                            <i class="fas fa-exclamation-triangle icon"></i>
                            <span>Disciplinary tracking</span>
                        </a>
                    </li>
                </ul>
            </nav>

            
            <main class="main-content">
                
                <div class="page-header">
                    <div>
                        <h1 class="page-title">Welcome- <?php echo htmlspecialchars($patient_name); ?>!</h1>
                        <p class="page-subtitle"></p>
                    </div>
                    <a href="logout.php" class="btn btn-error">
                        <i class="fas fa-sign-out-alt"></i>
                        Logout
                    </a>
                </div>

                
                <div class="stats-grid">
                    <div class="stat-card animate-fadeIn">
                        <div class="stat-header">
                            <div class="stat-icon">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                        </div>
                        <div class="stat-content">
                            <h3>Upcoming Appointments</h3>
                            <div class="stat-number"><?php echo $patient_stats['upcoming_appointments']; ?></div>
                            <div class="stat-change positive">
                                <i class="fas fa-arrow-up"></i>
                                Next appointment scheduled
                            </div>
                        </div>
                    </div>

                    <div class="stat-card animate-fadeIn">
                        <div class="stat-header">
                            <div class="stat-icon">
                                <i class="fas fa-history"></i>
                            </div>
                        </div>
                        <div class="stat-content">
                            <h3>Total Appointments</h3>
                            <div class="stat-number"><?php echo $patient_stats['total_appointments']; ?></div>
                            <div class="stat-change positive">
                                <i class="fas fa-chart-line"></i>
                                All time visits
                            </div>
                        </div>
                    </div>

                    <div class="stat-card animate-fadeIn">
                        <div class="stat-header">
                            <div class="stat-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                        </div>
                        
                        </div>
                    </div>
                </div>

                
                <div class="card animate-fadeIn">
                    <div class="card-header">
                        <h2 class="card-title">Quick Actions</h2>
                    </div>
                    <div class="card-body">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                            <a href="book_appointment.php" class="btn btn-primary btn-full">
                                <i class="fas fa-calendar-plus"></i>
                                Book New Appointment
                            </a>
                            <a href="my_appointments.php" class="btn btn-secondary btn-full">
                                <i class="fas fa-list"></i>
                                View All Appointments
                            </a>
                            
                        </div>
                    </div>
                </div>

                
                <div class="card animate-fadeIn">
                    <div class="card-header">
                        <h2 class="card-title">Recent Activity</h2>
                    </div>
                    <div class="card-body">
                        <?php
                        $recent_appointments = $conn->query("
                            SELECT a.*, d.full_name as doctor_name, d.specialty 
                            FROM Appointments a 
                            JOIN Doctors d ON a.doctor_id = d.doctor_id 
                            WHERE a.patient_id = '$patient_id' 
                            ORDER BY a.appointment_date DESC 
                            LIMIT 5
                        ");
                        
                        if($recent_appointments->num_rows > 0): ?>
                            <div class="table-container">
                                <table class="modern-table">
                                    <thead>
                                        <tr>
                                            <th>Doctor</th>
                                            <th>Specialty</th>
                                            <th>Date & Time</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($appointment = $recent_appointments->fetch_assoc()): ?>
                                            <tr>
                                                <td>
                                                    <div class="patient-info">
                                                        <div class="patient-avatar"><?php echo substr($appointment['doctor_name'], 0, 2); ?></div>
                                                        <div class="patient-details">
                                                            <div class="patient-name"><?php echo htmlspecialchars($appointment['doctor_name']); ?></div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><?php echo htmlspecialchars($appointment['specialty']); ?></td>
                                                <td><?php echo date('M j, Y', strtotime($appointment['appointment_date'])); ?> at <?php echo date('g:i A', strtotime($appointment['appointment_time'])); ?></td>
                                                <td>
                                                    <span class="status-badge status-<?php echo strtolower($appointment['status']); ?>">
                                                        <?php echo $appointment['status']; ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
        <?php else: ?>
                            <div class="text-center" style="padding: 2rem;">
                                <i class="fas fa-calendar-times" style="font-size: 3rem; color: var(--gray-400); margin-bottom: 1rem;"></i>
                                <p style="color: var(--gray-600);">No appointments found. <a href="book_appointment.php">Book your first appointment</a></p>
                            </div>
        <?php endif; ?>
    </div>
</div>
            </main>
        </div>
    <?php else: ?>
        
        <div class="professional-landing">
            
            <header class="landing-header">
                <div class="header-container">
                    <div class="header-logo">
                        <img src="images/logo.jpeg" alt="Pakenham Hospital" class="logo-img">
                        <span class="logo-text">Pakenham Hospital</span>
                    </div>
                    <nav class="header-nav">
                        <a href="#services" class="nav-link">Services</a>
                        <a href="#about" class="nav-link">About</a>
                        <a href="#contact" class="nav-link">Contact</a>
                        <a href="login.php" class="btn btn-outline btn-sm">Sign in</a>
                    </nav>
                </div>
            </header>

            
            <!-- HERO: full-width slideshow -->
<section class="hero-section" id="top">
  <!-- slides -->
  <div class="hero-slides" id="heroSlides">
    <img src="images/service1.jpeg" alt="Doctor consulting patient" class="active" loading="lazy">
    <img src="images/logo.jpeg" alt="Nurse caring for patient" loading="lazy">
    <img src="assets/images/hero3.jpg" alt="Modern hospital facility" loading="lazy">
  </div>

  <!-- overlay content -->
  <div class="hero-overlay">
    <h1 class="hero-heading">Feeling Unwell? Book a consultation Today!</h1>
    <p class="hero-tagline">Your health matters. Schedule and check up now!</p>

    <div class="hero-cta">
      <a href="register.php" class="btn btn-primary btn-lg">
        <i class="fas fa-user-plus"></i> Create Account
      </a>
      <a href="login.php" class="btn btn-secondary btn-lg">
        <i class="fas fa-sign-in-alt"></i> Patient Portal
      </a>
    </div>
  </div>

  <!-- dots -->
  <div class="hero-dots" id="heroDots" role="tablist" aria-label="Slide indicators">
    <button class="dot is-active" aria-label="Go to slide 1"></button>
    <button class="dot" aria-label="Go to slide 2"></button>
    <button class="dot" aria-label="Go to slide 3"></button>
  </div>
</section>

            
            <!-- Our Services (image slider) -->
<section class="services-section" id="services">
  <div class="services-container">
    <div class="section-header">
      <h2>Our Services</h2>
    </div>

    <div class="services-slider" aria-label="Our services images">
      <div class="services-track" id="servicesTrack">
        <img src="images/service1.jpeg" alt="Emergency & Acute Care" loading="lazy">
        <img src="images/logo.jpeg" alt="Inpatient & Recovery" loading="lazy">
        <img src="assets/images/service3.jpg" alt="Diagnostics & Imaging" loading="lazy">
      </div>

      <div class="services-dots" id="servicesDots" role="tablist" aria-label="Slide indicators">
        <button class="dot is-active" aria-label="Go to slide 1"></button>
        <button class="dot" aria-label="Go to slide 2"></button>
        <button class="dot" aria-label="Go to slide 3"></button>
      </div>
    </div>
  </div>
</section>


            
           <!-- News & Updates (replaces stats-section) -->
<section class="news-section">
  <div class="news-container">
    <h2 class="section-title">News &amp; Updates</h2>
    <p class="section-subtitle">Latest from Pakenham Hospital.</p>

    <div class="news-grid">
      <!-- Card 1 -->
      <article class="news-card">
        <img src="images/logo.jpeg" alt="Flu vaccination clinic" class="news-image">
        <div class="news-body">
          <h3 class="news-title">2025 Flu Vaccination Clinics Open</h3>
          <p class="news-excerpt">Walk-in and booked sessions now available across our campuses.</p>
          <a class="news-link" href="#">Read more</a>
        </div>
      </article>

      <!-- Card 2 -->
      <article class="news-card">
        <img src="images/logo.jpeg" alt="Telehealth expansion" class="news-image">
        <div class="news-body">
          <h3 class="news-title">Telehealth Expanded to New Specialties</h3>
          <p class="news-excerpt">Follow-ups and mental health consults now online.</p>
          <a class="news-link" href="#">Read more</a>
        </div>
      </article>

      <!-- Card 3 -->
      <article class="news-card">
        <img src="images/logo.jpeg" alt="New MRI machine" class="news-image">
        <div class="news-body">
          <h3 class="news-title">New MRI Suite Arrives</h3>
          <p class="news-excerpt">Faster, quieter scans with next-gen imaging technology.</p>
          <a class="news-link" href="#">Read more</a>
        </div>
      </article>

      <!-- Card 4 -->
      <article class="news-card">
        <img src="images/logo.jpeg" alt="Healthy hearts program" class="news-image">
        <div class="news-body">
          <h3 class="news-title">Healthy Hearts Program</h3>
          <p class="news-excerpt">Free workshops on heart health and nutrition.</p>
          <a class="news-link" href="#">Read more</a>
        </div>
      </article>
    </div>
  </div>
</section>

          
            
          <div class="footer-bottom">
  <p>© All rights reserved — Pakenham Hospital</p>
  <span class="dot">•</span>
  <a href="#top">Back to top</a>
  <span class="dot">•</span>
  <a href="/pms/about.php">About us</a>
  <span class="dot">•</span>
  <a href="/pms/contacts.php">Contact us</a>
</div>
        </div>
    <?php endif; ?>

   <script>
(function () {
  const track = document.getElementById('heroSlides');
  const dotsWrap = document.getElementById('heroDots');
  if (!track || !dotsWrap) return;

  const slides = Array.from(track.querySelectorAll('img'));
  const dots = Array.from(dotsWrap.querySelectorAll('.dot'));
  let i = 0, timer;

  function show(n) {
    i = (n + slides.length) % slides.length;
    slides.forEach((s, k) => s.classList.toggle('active', k === i));
    dots.forEach((d, k) => d.classList.toggle('is-active', k === i));
  }

  function start() { stop(); timer = setInterval(() => show(i + 1), 5000); }
  function stop()  { if (timer) clearInterval(timer); }

  dots.forEach((d, k) => d.addEventListener('click', () => { show(k); start(); }));

  show(0);
  start();
})();
</script>



</body>
</html>


