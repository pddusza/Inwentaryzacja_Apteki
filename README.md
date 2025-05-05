# Pharmacy Inventory Manager (Inwentaryzacja Apteki)

Welcome to **Inwentaryzacja Apteki**, a straightforward web app designed to help small pharmacies—or anyone who cares about keeping medicines organized—track and manage inventory across multiple storage cabinets/locations.

## What’s Inside?

* **User Accounts**: Create your own login, so each team member can securely access the system.
* **Cabinet Control**: Set up as many cabinets (or "storage units") as you need. Invite colleagues or request access to existing cabinets.
* **Roles & Permissions**: Assign roles—like Admin or Editor or more—so everyone sees and does exactly what they should.
* **Easy Inventory Tracking**: Quickly add new medications, update quantities as stock goes in or out, and never lose sight of low supplies.
* **Join Requests**: When someone asks to join your cabinet, approve or deny with a single click.
* **Reports & History**: View past usage, spot trends, and print or export reports to keep regulators—or your boss—happy. (Simplified Implementation)

## Technology Stack

Built with:

* **PHP** for server-side logic
* **MySQL** for storing all your data
* **HTML/CSS** for a clean, responsive interface

## Getting Started

1. **Download or Clone**

   ```bash
   git clone https://github.com/pddusza/Inwentaryzacja_Apteki.git
   cd Inwentaryzacja_Apteki
   ```

2. **Upload to Your Hosting**
   Copy the entire project folder onto any PHP-capable web server (e.g., shared hosting, VPS, or a Docker container).

3. **Prepare the Database**

   * Create a new MySQL database (e.g., `Apteka`).
   * Import the `schema.sql` file to build all tables and relationships:

     ```bash
     mysql -h your_mysql_host -u your_username -p your_database_name < schema.sql
     ```

   Or import via phpMyAdmin, MySQL Workbench, or another GUI—just make sure `schema.sql` runs successfully.

4. **Configure the App**
   Open `config.php` and fill in your database details:

   ```php
   define('DB_HOST', 'your_mysql_host');
   define('DB_NAME', 'Apteka');
   define('DB_USER', 'your_mysql_username');
   define('DB_PASS', 'your_mysql_password');
   ```

   > **Tip**: If you’re on a managed hosting service, they usually provide these credentials in your control panel.

5. **Set File Permissions**
   Ensure your webserver user (e.g., `www-data`) can read and write in the project directory.

6. **Launch & Enjoy**
   Point your browser to `http://your-domain.com/` (or wherever you uploaded it). You’ll see the login screen—just register and you’re in!

## How to Use

1. **Register** a new user or **log in** with existing credentials.
2. **Create** a new cabinet or **send a join request** to an existing cabinet.
3. **Assign roles** to users within a cabinet (e.g., admin, editor).
5. **Add medications** to your cabinet, including name, quantity, expiration date and description.
6. **Record usage** whenever medications are dispensed (sold or disposed of) or restocked.
7. **View reports** to monitor inventory history and usage trends. Export CSVs, or print for audits (Unreleased).

## Dashboard landing page
![image](https://github.com/user-attachments/assets/5bdd7d0d-cb9f-44bc-8ce8-5985894d9447)

## Simplified File Overview

```
├── schema.sql           # SQL script: run this on your MySQL host
├── config.php           # Update your DB connection here
├── *.php                # Core application files (authentication, cabinets, meds, reports)
└── index.html           # Entry point / login form
```


## Contributing

Contributions are welcome! Feel free to open issues or submit pull requests for bug fixes and enhancements.

## License

This project is released under the MIT License—feel free to use, modify, and distribute. **Please remember to include the original copyright notice and attribution when you do.**

