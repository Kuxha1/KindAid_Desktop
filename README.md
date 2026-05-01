# KindAid - Smart Contribution System & Social Impact Management System

**KindAid** is a comprehensive social impact platform that bridges the gap between those in need and those who can help. It serves as both a web dashboard and the backend API for a companion mobile application. The platform empowers communities through transparent fundraising, volunteer networking, needs requests, and AI-driven support.

## important note 

If you want to run this project Application(KindAid_Application) on your local machine you need XAMPP and for mobile application you need Android Studio. and your required to first setup the the desktop version and then mobile version. because the mobile version is depending on the desktop version and it uses the same database and it contain Apis for Mobile version(KindAid_Application).

## Features

- **Fundraisers & Donations:** Create and manage fundraisers, and track collected amounts towards goals.
- **Needs Board:** Users can post specific needs, and the community can step in to help.
- **Community Forum:** Share posts, updates, and engage with other volunteers and NGOs.
- **Interactive Locations:** Track and view geo-locations of impact areas and events.
- **AI Assistant:** Built-in Gemini AI chatbot to help users navigate the platform and find causes.
- **Secure Authentication:** User login, registration, and OTP-based password recovery via PHPMailer.
- **Admin Panel:** Dashboard to verify NGOs, manage users, and review reported content.
- **Mobile API Integration:** Seamlessly connects with the KindAid Flutter mobile app.

## Tech Stack

- **Frontend (Web):** HTML, PHP, Tailwind CSS, JavaScript
- **Backend:** PHP
- **Database:** MySQL
- **Integrations:** Google Gemini API (Chatbot), PHPMailer (SMTP Email)

## ⚙️ Installation & Setup Instructions

Follow these steps to run the KindAid project locally on your machine.

### Prerequisites
- [XAMPP](https://www.apachefriends.org/index.html) (or any other local server environment with PHP and MySQL).
- A modern web browser.

### Step 1: Download the Repository
1. Download or clone this repository to your local machine.
2. Move the project folder into your XAMPP `htdocs` directory:
   - Windows: `C:\xampp\htdocs\KindAid`
   - Mac: `/Applications/XAMPP/xamppfiles/htdocs/KindAid`
   - Linux: `/opt/lampp/htdocs/KindAid`

### Step 2: Database Setup
1. Open XAMPP Control Panel and start **Apache** and **MySQL**.
2. Open your browser and go to `http://localhost/phpmyadmin/`.
3. Click on **New** to create a new database and name it `kindaid`.
4. Select the `kindaid` database, click on the **Import** tab at the top.
5. Choose the file `info&database/kindaid.sql` from the project folder.
6. Click **Import** (or Go) at the bottom to import the tables and sample data.

### Step 3: Configure Environment Variables
You will need to add your own API keys and SMTP credentials for certain features to work locally.

1. **Email OTP (PHPMailer):**
   - Open `forgot_password.php` and `mobile_api/auth/forgot_password.php`.
   - Locate the `SMTP Configuration` section.
   - Replace `your-email-address@gmail.com` with your Gmail address.
   - Generate a 16-character [Google App Password](https://myaccount.google.com/apppasswords) and replace the placeholder password.

2. **Gemini AI Chatbot:**
   - Open `api_chatbot.php` and `chatbot.php`.
   - Locate `define('GEMINI_API_KEY', 'YOUR_API_KEY_HERE');`.
   - Replace `YOUR_API_KEY_HERE` with your actual Google Gemini API key.

>  **SECURITY WARNING:** Do not commit your real App Passwords or API Keys to GitHub. Always use placeholders (like `YOUR_API_KEY_HERE`) when pushing to a public repository to protect your accounts.

### Step 4: Run the Application
1. Open your browser and navigate to:
   ```
   http://localhost/KindAid/
   ```
   *(Adjust the URL if your folder is named differently, like `KindAid - Copy`)*
2. You should now see the login page or the dashboard.

## Directory Structure
- `/admin/` - Admin panel for managing users and verifications.
- `/assets/` - Static images, logos, and UI assets.
- `/mobile_api/` - API endpoints used by the Flutter mobile application.
- `/info&database/` - Database SQL files and project documentation.
- `/style/` - Custom CSS and Tailwind configuration.
- `/uploads/` - Directory for user-uploaded images and documents.

## More information
the project info will be found in the info&database folder

## License
This project is part of the KindAid System. Unauthorized distribution or commercial use is restricted.

Contact Info: proton.0900@gmail.com

