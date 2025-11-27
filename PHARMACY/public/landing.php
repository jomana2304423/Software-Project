<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pharmacy Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/styles.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-hospital"></i> Pharmacy Management System
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="login.php">
                    <i class="bi bi-box-arrow-in-right"></i> Login
                </a>
                <a class="nav-link" href="register.php">
                    <i class="bi bi-person-plus"></i> Register
                </a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section text-white py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold mb-4">Modern Pharmacy Management</h1>
                    <p class="lead mb-4">Streamline your pharmacy operations with our comprehensive management system. Handle inventory, prescriptions, sales, and more with ease.</p>
                    <div class="d-flex gap-3">
                        <a href="login.php" class="btn btn-light btn-lg">
                            <i class="bi bi-box-arrow-in-right"></i> Login
                        </a>
                        <a href="register.php" class="btn btn-outline-light btn-lg">
                            <i class="bi bi-person-plus"></i> Get Started
                        </a>
                    </div>
                </div>
                <div class="col-lg-6 text-center">
                    <i class="bi bi-hospital" style="font-size: 15rem; opacity: 0.3;"></i>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-5">
        <div class="container">
            <div class="row text-center mb-5">
                <div class="col-12">
                    <h2 class="display-5 fw-bold">Complete Pharmacy Solution</h2>
                    <p class="lead text-muted">Everything you need to manage your pharmacy efficiently</p>
                </div>
            </div>
            
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card feature-card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <i class="bi bi-capsule text-primary" style="font-size: 3rem;"></i>
                            <h5 class="card-title mt-3">Inventory Management</h5>
                            <p class="card-text">Track medicines, batches, expiry dates, and stock levels with automated alerts for low stock and expiring items.</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card feature-card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <i class="bi bi-file-medical text-success" style="font-size: 3rem;"></i>
                            <h5 class="card-title mt-3">Prescription Management</h5>
                            <p class="card-text">Upload, review, and process prescriptions digitally. Customers can upload prescriptions and track their status.</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card feature-card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <i class="bi bi-cart-check text-info" style="font-size: 3rem;"></i>
                            <h5 class="card-title mt-3">Sales Processing</h5>
                            <p class="card-text">Process sales transactions, generate invoices, and maintain complete sales history with detailed reporting.</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card feature-card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <i class="bi bi-truck text-warning" style="font-size: 3rem;"></i>
                            <h5 class="card-title mt-3">Supplier Management</h5>
                            <p class="card-text">Manage supplier relationships, track purchase orders, and maintain supplier product catalogs.</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card feature-card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <i class="bi bi-graph-up text-danger" style="font-size: 3rem;"></i>
                            <h5 class="card-title mt-3">Analytics & Reports</h5>
                            <p class="card-text">Generate comprehensive reports on sales, inventory, and performance metrics for informed decision making.</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card feature-card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <i class="bi bi-shield-check text-secondary" style="font-size: 3rem;"></i>
                            <h5 class="card-title mt-3">Role-Based Access</h5>
                            <p class="card-text">Secure access control with different dashboards for Admin, Pharmacist, Supplier, and Customer roles.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- User Roles Section -->
    <section class="bg-light py-5">
        <div class="container">
            <div class="row text-center mb-5">
                <div class="col-12">
                    <h2 class="display-5 fw-bold">Designed for Everyone</h2>
                    <p class="lead text-muted">Different interfaces for different needs</p>
                </div>
            </div>
            
            <div class="row g-4">
                <div class="col-md-3">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <i class="bi bi-gear-fill text-primary" style="font-size: 3rem;"></i>
                            <h5 class="card-title mt-3">Admin</h5>
                            <p class="card-text">Complete system control, user management, reports, and system configuration.</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <i class="bi bi-person-badge text-success" style="font-size: 3rem;"></i>
                            <h5 class="card-title mt-3">Pharmacist</h5>
                            <p class="card-text">Process sales, manage prescriptions, view inventory, and handle customer interactions.</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <i class="bi bi-truck text-warning" style="font-size: 3rem;"></i>
                            <h5 class="card-title mt-3">Supplier</h5>
                            <p class="card-text">Manage product catalog, fulfill orders, track sales performance, and update inventory.</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <i class="bi bi-person text-info" style="font-size: 3rem;"></i>
                            <h5 class="card-title mt-3">Customer</h5>
                            <p class="card-text">Upload prescriptions, track order status, view order history, and manage profile.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-5">
        <div class="container">
            <div class="row justify-content-center text-center">
                <div class="col-lg-8">
                    <h2 class="display-5 fw-bold mb-4">Ready to Get Started?</h2>
                    <p class="lead mb-4">Join thousands of pharmacies already using our system to streamline their operations.</p>
                    <div class="d-flex gap-3 justify-content-center">
                        <a href="register.php" class="btn btn-primary btn-lg">
                            <i class="bi bi-person-plus"></i> Create Account
                        </a>
                        <a href="login.php" class="btn btn-outline-primary btn-lg">
                            <i class="bi bi-box-arrow-in-right"></i> Login
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><i class="bi bi-hospital"></i> Pharmacy Management System</h5>
                    <p class="text-muted">Streamlining pharmacy operations for better healthcare delivery.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="text-muted mb-0">&copy; 2024 Pharmacy Management System. All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/app.js"></script>
    
    <script>
    // Enhanced landing page interactions
    document.addEventListener('DOMContentLoaded', function() {
        // Animate cards on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-in');
                }
            });
        }, observerOptions);
        
        // Observe all cards
        document.querySelectorAll('.card').forEach(card => {
            observer.observe(card);
        });
        
        // Add hover effects to feature cards
        document.querySelectorAll('.card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-10px)';
                this.style.transition = 'all 0.3s ease';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });
        
        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
        
        // Add typing effect to hero title
        const heroTitle = document.querySelector('.display-4');
        if (heroTitle) {
            const text = heroTitle.textContent;
            heroTitle.textContent = '';
            let i = 0;
            
            function typeWriter() {
                if (i < text.length) {
                    heroTitle.textContent += text.charAt(i);
                    i++;
                    setTimeout(typeWriter, 100);
                }
            }
            
            setTimeout(typeWriter, 500);
        }
    });
    </script>
    
    <style>
    /* Landing page specific styles */
    .hero-section {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        position: relative;
        overflow: hidden;
    }
    
    .hero-section::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="75" cy="75" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="50" cy="10" r="0.5" fill="rgba(255,255,255,0.05)"/><circle cx="10" cy="60" r="0.5" fill="rgba(255,255,255,0.05)"/><circle cx="90" cy="40" r="0.5" fill="rgba(255,255,255,0.05)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
        opacity: 0.3;
    }
    
    .feature-card {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        border: none;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
    
    .feature-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15);
    }
    
    .feature-card .bi {
        transition: all 0.3s ease;
    }
    
    .feature-card:hover .bi {
        transform: scale(1.2) rotate(5deg);
    }
    
    .animate-in {
        animation: slideInUp 0.6s ease-out;
    }
    
    @keyframes slideInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .navbar {
        backdrop-filter: blur(10px);
        background: rgba(13, 110, 253, 0.95) !important;
    }
    
    .btn {
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }
    
    .btn::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
        transition: left 0.5s;
    }
    
    .btn:hover::before {
        left: 100%;
    }
    
    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
    }
    
    /* Pulse animation for CTA buttons */
    .btn-primary {
        animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
        0% {
            box-shadow: 0 0 0 0 rgba(13, 110, 253, 0.7);
        }
        70% {
            box-shadow: 0 0 0 10px rgba(13, 110, 253, 0);
        }
        100% {
            box-shadow: 0 0 0 0 rgba(13, 110, 253, 0);
        }
    }
    </style>
</body>
</html>

