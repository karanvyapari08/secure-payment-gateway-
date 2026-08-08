# Secure Payment Gateway (OWASP Top 10 Demo)

A full-stack PHP + MySQL project demonstrating secure implementation patterns
that remediate common OWASP Top 10 and API Security Top 10 issues.

## Folder Structure
```
secure-payment-gateway/
├── config/
│   └── db.php              # PDO connection (prepared statements only)
├── includes/
│   ├── auth.php            # Session security, RBAC, ownership checks
│   ├── csrf.php            # CSRF token generation/verification
│   └── functions.php       # Validation + business logic helpers
├── assets/css/style.css
├── index.php
├── register.php
├── login.php
├── dashboard.php
├── wallet.php
├── payment.php
├── transactions.php
├── refund.php
├── admin.php
├── logout.php
├── schema.sql
└── .htaccess
```

## Setup
1. Create the database:
   ```
   mysql -u root -p < schema.sql
   ```
2. Update credentials in `config/db.php` (DB_USER, DB_PASS).
3. Generate a real bcrypt hash for the seeded admin instead of the placeholder:
   ```php
   <?php echo password_hash('YourNewPassword!1', PASSWORD_BCRYPT);
   ```
   Update the `users` table with this hash.
4. Serve with PHP's built-in server for local testing:
   ```
   php -S localhost:8000
   ```
   (Use Apache/Nginx + HTTPS in any real deployment — cookies are flagged `secure`, so HTTP-only local testing may require temporarily relaxing that flag in `includes/auth.php`.)

## OWASP / API Security Top 10 → Where It's Addressed

| Risk | File(s) | Mitigation |
|---|---|---|
| A03 SQL Injection | `config/db.php`, all pages | PDO with `ATTR_EMULATE_PREPARES = false`; every query uses bound parameters |
| A03 XSS | `includes/auth.php` (`e()`), all views | All dynamic output passed through `htmlspecialchars()` |
| A01 Broken Access Control / IDOR | `wallet.php`, `refund.php`, `transactions.php` | Every direct object reference scoped by `user_id` in the SQL `WHERE` clause, never trusted from client input |
| A01 Broken Access Control (RBAC) | `admin.php`, `includes/auth.php` | `requireRole('admin')` enforced server-side before any data is rendered |
| CSRF | `includes/csrf.php`, all POST forms | Per-session random token, `hash_equals()` comparison, required on every state-changing request |
| A07 Identification & Auth Failures | `login.php`, `includes/functions.php` | bcrypt hashing, account lockout after 5 failed attempts, generic error messages (no user enumeration), session regeneration on login, idle timeout |
| A04 Insecure Design (business logic) | `refund.php`, `payment.php` | Row-level locking (`FOR UPDATE`) to prevent race conditions, refund window limits, duplicate-refund prevention, idempotency keys to block payment replay |
| A02 Cryptographic Failures | `login.php`, `.htaccess` | Passwords hashed with bcrypt, `Strict-Transport-Security` header, secure/HttpOnly/SameSite cookies |
| A05 Security Misconfiguration | `.htaccess` | Security headers (CSP, X-Frame-Options, nosniff), directory listing disabled, `.sql` files blocked from web access |
| A09 Security Logging Failures | `includes/auth.php` (`logAudit`) | All logins, payments, and refunds written to `audit_log` with timestamp + IP |
| API1 Broken Object Level Authorization | `wallet.php`, `refund.php` | No object ID accepted from client is ever queried without a `user_id` filter |

## Notes
- This is a teaching/demo scaffold, not a PCI-DSS-certified payment processor. For real payment processing, integrate a licensed gateway (Razorpay, Stripe, etc.) rather than handling raw card/wallet funds yourself.
- Replace the placeholder admin password hash in `schema.sql` before use.
- Consider adding: rate limiting at the web-server level, WAF rules, 2FA, and automated dependency scanning (e.g. `composer audit`) for a production-grade version.
