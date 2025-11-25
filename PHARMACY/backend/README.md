# 🏥 Pharmacy Management System

A comprehensive, modern pharmacy management system with role-based access control for Admin, Pharmacist, Supplier, and Customer users.

## 🚀 Quick Start

### Prerequisites
- XAMPP (Apache + MySQL + PHP)
- Web browser

### Installation Steps

1. **Start XAMPP**
   - Open XAMPP Control Panel
   - Start Apache and MySQL services

2. **Database Setup**
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Create a new database named `pms_db`
   - Import the `database/pms_db.sql` file

3. **Access the System**
   - **Main Website**: http://localhost/pharmacy/public/landing.php
   - **Login Page**: http://localhost/pharmacy/public/login.php
   - **Registration**: http://localhost/pharmacy/public/register.php

## 🔐 Default Login Credentials

- **Admin**: `admin` / `admin123`

## 👥 User Roles & Features

### 🔧 Admin
- **Dashboard**: System overview with statistics
- **User Management**: Create, activate/deactivate, delete users
- **Reports**: Comprehensive system reports
- **Full Access**: All system features

### 💊 Pharmacist
- **Dashboard**: Sales-focused dashboard with alerts
- **Sales Processing**: Handle customer transactions
- **Prescription Management**: Review uploaded prescriptions
- **Inventory Management**: View and manage medicines
- **Sales History**: Track all transactions

### 🚚 Supplier
- **Dashboard**: Order management and statistics
- **Product Catalog**: Add and manage products
- **Order Fulfillment**: Track and update order status
- **Sales Analytics**: View performance reports with charts
- **Profile Management**: Update company information

### 👤 Customer
- **Dashboard**: Prescription and order tracking
- **Prescription Upload**: Upload prescriptions for review
- **Order History**: View past purchases
- **Profile Management**: Update personal information

## 🎨 Features

### ✨ Modern UI/UX
- Bootstrap 5 with custom styling
- Responsive design for all devices
- Smooth animations and transitions
- Professional gradient themes
- Interactive charts and graphs

### 🔒 Security
- Role-based access control (RBAC)
- Session management with timeout
- Password hashing
- SQL injection prevention
- Input sanitization

### 📊 Analytics
- Real-time dashboard statistics
- Sales performance tracking
- Inventory alerts (low stock, expiry)
- Order status tracking
- Interactive charts for suppliers

### 📱 Responsive Design
- Mobile-first approach
- Works on desktop, tablet, and mobile
- Touch-friendly interface
- Optimized for all screen sizes

## 🗂️ File Structure

```
pharmacy/
├── config/
│   ├── config.php          # System configuration
│   └── db.php              # Database connection
├── database/
│   └── pms_db.sql          # Database schema and sample data
├── includes/
│   ├── auth.php            # Authentication functions
│   ├── rbac.php            # Role-based access control
│   ├── helpers.php         # Helper functions
│   ├── header.php          # Common header
│   └── footer.php          # Common footer
├── modules/
│   ├── dashboard/          # Role-specific dashboards
│   ├── users/              # User management
│   ├── suppliers/          # Supplier features
│   ├── customers/          # Customer features
│   ├── medicines/          # Medicine management
│   ├── sales/              # Sales processing
│   ├── prescriptions/      # Prescription management
│   └── reports/            # Reporting system
├── public/
│   ├── landing.php         # Public landing page
│   ├── login.php           # Login page
│   ├── register.php        # Registration page
│   ├── index.php           # Main entry point
│   └── assets/
│       ├── css/
│       │   └── styles.css  # Custom styling
│       └── js/
│           └── app.js      # JavaScript functions
└── setup_check.php         # System setup verification
```

## 🔧 Configuration

### Database Configuration
Edit `config/db.php` to match your database settings:
```php
$host = '127.0.0.1';
$dbname = 'pms_db';
$username = 'root';
$password = '';
```

### System Configuration
Edit `config/config.php` for system settings:
```php
return [
    'db_host' => '127.0.0.1',
    'db_name' => 'pms_db',
    'db_user' => 'root',
    'db_pass' => '',
    'base_url' => '/pharmacy/public',
    'session_timeout_minutes' => 30,
    'app_name' => 'Pharmacy Management System',
    'version' => '1.0.0'
];
```

## 🧪 Testing the System

1. **Setup Verification**
   - Visit: http://localhost/pharmacy/setup_check.php
   - Verify all components are working

2. **Admin Testing**
   - Login as admin
   - Create test users for each role
   - Test user management features

3. **Role Testing**
   - Test each role's specific features
   - Verify access restrictions work properly
   - Test responsive design on different devices

## 📈 Sample Data

The system comes with sample data including:
- Admin user account
- Sample suppliers
- Sample medicines with batches
- Sample customers
- Sample notifications

## 🛠️ Customization

### Adding New Features
1. Create new modules in the `modules/` directory
2. Update navigation in `includes/header.php`
3. Add role permissions in `includes/rbac.php`
4. Update database schema as needed

### Styling
- Modify `public/assets/css/styles.css` for custom styling
- CSS uses CSS variables for easy theme customization
- Responsive breakpoints are included

## 🐛 Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Verify MySQL is running in XAMPP
   - Check database credentials in `config/db.php`
   - Ensure `pms_db` database exists

2. **Permission Denied**
   - Check file permissions
   - Ensure web server has read access

3. **Session Issues**
   - Clear browser cookies
   - Check session configuration in PHP

4. **Styling Issues**
   - Clear browser cache
   - Verify CSS file is loading correctly

## 📞 Support

For issues or questions:
1. Check the setup verification page
2. Review the troubleshooting section
3. Verify all prerequisites are met

## 🎯 Next Steps

After setup:
1. Create additional user accounts
2. Add your pharmacy's medicines
3. Configure suppliers
4. Customize the system for your needs
5. Train staff on their respective dashboards

---

**🎉 Your Pharmacy Management System is ready to use!**

Access it at: **http://localhost/pharmacy/public/landing.php**