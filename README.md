# Domain Checker

A web-based application to monitor domain names, check their availability, and track expiration dates using WHOIS lookups.

## Features

- **Domain Monitoring**: Track multiple domains in one place
- **WHOIS Lookup**: Get detailed domain information including:
  - Domain availability status
  - Expiration date
  - Raw WHOIS data
- **Clean Dashboard**: View all domains with their status at a glance
- **Automatic Updates**: Scheduled checks to keep domain information up-to-date
- **Responsive Design**: Works on desktop and mobile devices

## Requirements

- PHP 8.0 or higher
- MySQL 5.7 or higher / MariaDB 10.2 or higher
- Web server (Apache/Nginx)
- Composer (for dependency management)

## Installation

1. Clone the repository:
   ```bash
   git clone https://github.com/0xNajmul/domain-checker.git
   cd domain-checker
   ```

2. Install dependencies:
   ```bash
   composer install
   ```

3. Import the database schema:
   ```bash
   mysql -u username -p database_name < db/domains.sql
   ```

4. Configure the database connection in `includes/config.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'domainchecker');
   define('DB_USER', 'your_username');
   define('DB_PASS', 'your_password');
   ```

5. Set up a web server to point to the project directory

## Usage

1. Access the application through your web browser
2. Add domains using the search form
3. View domain status and details in the dashboard
4. Set up a cron job for automatic updates:
   ```
   * * * * * php /path/to/domain-checker/cron/check_domains.php
   ```
   Or on Windows, use the provided batch file:
   ```
   schtasks /create /sc minute /mo 5 /tn "Domain Checker" /tr "C:\path\to\domain-checker\cron\run_domain_check.bat"
   ```

## File Structure

```
.
├── assets/                 # Static assets
│   ├── css/               # Stylesheets
│   └── js/                # JavaScript files
├── cron/                  # Scheduled tasks
│   ├── check_domains.php  # Cron job script
│   └── run_domain_check.bat # Windows task scheduler script
├── db/                    # Database files
│   └── domains.sql        # Database schema
├── includes/              # PHP includes
│   ├── config.php         # Configuration
│   ├── db.php             # Database connection
│   ├── footer.php         # Footer template
│   ├── header.php         # Header template
│   ├── utils.php          # Utility functions
│   └── whois.php          # WHOIS lookup functionality
├── add_domain.php         # Add new domain
├── delete_domain.php      # Remove domain
├── index.php              # Main application
└── update_database.php    # Manual database update
```

## Dependencies

- Bootstrap 5.3.0
- Font Awesome 6.0.0
- jQuery 3.6.0

## License

This project is open-source and available under the [MIT License](LICENSE).

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## Support

For support, please open an issue in the GitHub repository.
