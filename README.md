# Midterm_Web
This project is a secure and user friendly account management system built with PHP and MySql.

# Features
- User registration with:
    - Username
    - Email
    - Password & confirmation
- Email validation
- Secure password hasing 
- Unique token and token expiration handling (1 hour)
- Token clean up after verifying
- Forgot password, changing password
- 2FA (google authencator), with a way to enable/disable it
- Deleting the account

# Prerequisites
Since our code uses XAMPP mysql and composer without them the code will not function, please download it from their respective website
- XAMPP (https://www.apachefriends.org/download.html)
- composer (https://getcomposer.org/download/)
- Git (https://git-scm.com/downloads/win)

# Installation & Setup
1 **Clone the repository**
- Find the htdocs in the xampp installation folder and make a "Midterm" folder (case sensitive)
- Open your terminal and navigate to the "Midterm" folder

```bash
git clone https://github.com/perk27/Midterm_Web.git
```

2. Install Composer libraries
```bash
composer require phpmailer/phpmailer
composer require spomky-labs/otphp
composer require endroid/qr-code
```
Run these commands in the terminal one by one to download them.

3. Database Setup
- First find the "midterm.sql" in the folder 
- Second Open XAMPP, Find Module "MySQL" click start and click admin.
- Find the import section and import the sql file.

4. Configure database credentials (Optional)
- Since this code uses the default xampp credentials, there's not need to change.
- However, if you have your own credentials, naviagte to "db_connection.php" you can change it there.

5. Update PHPMailer credentials (Optional)
- In this demo we have provided an Email with the account with the app password for sending out verifying mails
- If you want to change this however, navigate to "signup.php" and "email_verfication.php" and find

```php
$mail -> Username = 'your-email@gmail.com';
$mail -> Password = 'your-app-password';
```

# Running the project
- First open xampp and start the 'Apache' and 'MySQL' module if you haven't
- If everything goes well goes to this link: http://localhost/Midterm/signup.php
- Then you can create an account log in, verify email, 2fa. You can do most account management things

# Created By

- Class 23k50201

- Le Huu Thanh - 523K0026
- Huynh Nhat Tien - 523K0027
- Pham Minh Hieu - 523V0001