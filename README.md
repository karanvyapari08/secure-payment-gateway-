# 💳 Secure Payment Gateway

A secure PHP-based payment gateway implementing authentication, role-based access control, secure transaction processing, and layered web application security.

This project provides a simulated payment environment with wallet management, payments, refunds, transaction tracking, replay protection, rate limiting, and security auditing.

> **Note:** This is an educational/simulated payment gateway and does not process real financial transactions.

---

## ✨ Features

### 👤 User Features

- User Registration & Login
- Secure Authentication
- Wallet Management
- Make Payments
- Payment Confirmation
- Transaction History
- Refund Processing
- Profile Management
- Secure Logout

### 👨‍💼 Admin Features

- Admin Authentication
- Role-Based Access Control
- User Management
- Transaction Monitoring
- Audit Log Monitoring
- Payment & Refund Management
- Dashboard & Statistics

---

## 🔐 Security Features

| Security Control | Implementation |
|---|---|
| SQL Injection Protection | PDO Prepared Statements |
| XSS Protection | Output Encoding |
| CSRF Protection | CSRF Tokens |
| Access Control | RBAC + Resource Ownership Validation |
| Authentication Security | Password Hashing + Session Security |
| Brute-Force Protection | IP Rate Limiting + Account Lockout |
| Replay Protection | Idempotency Keys |
| Duplicate Payment Protection | Idempotency + Transaction Validation |
| Race-Condition Protection | Database Transactions + Row Locking |
| Session Security | HttpOnly + Secure + SameSite Cookies |
| Session Protection | Session Regeneration + Timeout |
| Security Headers | CSP + Security Headers |
| Security Monitoring | Audit Logging |

---

## 💳 Payment Security Workflow

```text
User Login
    ↓
Authentication & Session Validation
    ↓
Payment Request
    ↓
CSRF Validation
    ↓
Input & Amount Validation
    ↓
Idempotency Key Validation
    ↓
Wallet Row Lock
    ↓
Balance Verification
    ↓
Atomic Balance Update
    ↓
Transaction Creation
    ↓
Audit Logging
    ↓
Payment Confirmation
```

---

## 🔄 Replay & Duplicate Payment Protection

Payment requests use idempotency keys to prevent duplicate processing of the same payment request.

```text
Payment Request
      ↓
Generate Idempotency Key
      ↓
Check Existing Transaction
      ↓
Already Processed?
   ↙          ↘
 YES           NO
 ↓             ↓
Reject       Process
              ↓
       Store Transaction
```

---

## ⚡ Transaction & Concurrency Security

Payment processing uses database transactions and row-level locking to maintain wallet and transaction consistency.

- Database transactions
- `SELECT ... FOR UPDATE`
- Atomic wallet balance updates
- Balance validation
- Transaction commit and rollback
- Concurrent payment protection

---

## 🔐 Authentication & Access Control

- Password hashing
- Secure session management
- Session ID regeneration
- Session timeout
- HttpOnly cookies
- Secure cookies
- SameSite cookie policy
- CSRF protection
- Role-Based Access Control
- Resource ownership validation
- Account lockout
- IP-based rate limiting
- Generic authentication error messages

---

## 📊 Audit Logging

Security-sensitive operations are recorded through centralized audit logging.

Logged activities include:

- Successful login
- Failed login
- Successful payment
- Failed payment
- Payment reference
- User ID
- IP address
- Security-relevant actions

---

## 🧪 Security Testing

Security controls can be assessed using industry-standard web security testing tools:

- Burp Suite
- OWASP ZAP

Testing areas include:

- Authentication
- Authorization
- Input validation
- XSS protection
- CSRF protection
- Rate limiting
- Replay protection
- Duplicate payment protection
- Transaction integrity
- Concurrent payment handling

---

## 🛠️ Tech Stack

- PHP
- MySQL
- HTML5
- CSS3
- Apache
- XAMPP
- phpMyAdmin
- Burp Suite
- OWASP ZAP
- Git & GitHub

---

## 📂 Project Structure

```text
secure-payment-gateway/
│
├── config/
│   └── db.php
│
├── includes/
│   ├── auth.php
│   ├── csrf.php
│   ├── functions.php
│   └── security_headers.php
│
├── assets/
│   └── css/
│
├── login.php
├── register.php
├── logout.php
├── dashboard.php
├── payment.php
├── refund.php
├── transactions.php
│
├── database/
│   └── schema.sql
│
├── screenshots/
│
└── README.md
```

---

## 🚀 Installation

### 1. Clone the Repository

```bash
git clone YOUR_REPOSITORY_URL
```

### 2. Move the Project

Copy the project into:

```text
xampp/htdocs/
```

### 3. Create the Database

Create a MySQL database and import:

```text
database/schema.sql
```

### 4. Configure Database

Update the database credentials in:

```text
config/db.php
```

### 5. Start XAMPP

Start:

- Apache
- MySQL

### 6. Open the Application

```text
http://localhost/secure-payment-gateway/
```

---

## 📷 Screenshots

### 🔐 Login

Add login screenshot here.

### 📊 Dashboard

Add dashboard screenshot here.

### 💳 Payment

Add payment screenshot here.

### 🧾 Transaction History

Add transaction screenshot here.

### 🔄 Refund

Add refund screenshot here.

### 🛡️ Security Testing

Add Burp Suite / OWASP ZAP screenshots here.

---

## 🎯 Security Concepts Demonstrated

- Secure Authentication
- Role-Based Access Control
- Resource Ownership Validation
- Secure Session Management
- CSRF Protection
- XSS Protection
- SQL Injection Prevention
- IP-Based Rate Limiting
- Account Lockout
- Replay Protection
- Idempotent Payment Processing
- Race-Condition Mitigation
- Transaction Integrity
- Security Headers
- Audit Logging

---

## ⚠️ Disclaimer

This project is developed for educational, portfolio, and security-testing purposes.

It is a simulated payment environment and is not intended to process real financial transactions or store real payment credentials.

---

## 👨‍💻 Developer

**Karan Vyapari**

Cybersecurity Analyst

GitHub: YOUR_GITHUB_PROFILE

LinkedIn: www.linkedin.com/in/karanvyapari
