# Tickonix
Self-hosted / on-prem ticket reservation system. Intentionally kept as simple as possible. Currently only available with German localization.

## System Requirements
This app runs on a classical LAMP stack (Linux, Apache Webserver, MySQL/MariaDB, PHP 7+).

The PHP PEAR mail library is required (package `php-mail` and `php-mail-mime` on Debian/Ubuntu).

## Features
- multi-event capable with individual reservation maxima, individual event title and start/end time
- captcha validation to reduce spam
- generates individual codes per reservation
- sends email with QR code and ICS calendar file
- optional self-service reservation cancellation link for visitors
- voucher codes to grant specific people access to special events
- maximum number of reservations per email address configurable
- admin area with event management, reservation overview and checkin/checkout (scan the code at the entrance)
  - a QR code scanner can be used for that

## Installation
1. Create a database on your MySQL/MariaDB server and import the database schema from `sql/SCHEMA.sql`.
2. Copy all files onto your webserver.
3. Create the config file `config.php` by copying the example `config.php.example`.
   - Set all config values as you like. Read the comments in the example file for more information how to configure them.
4. Make sure that your mail system is correctly set up on your server so that the app can send invitation mails.
   - On managed servers, this is probably already done by your hosting provider.
5. (optional) If you are using this for a private event, you probably want to lock the access to this webapp with a [.htaccess/.htpasswd file](https://wiki.selfhtml.org/wiki/Webserver/htaccess/Zugriffskontrolle).

## Usage
1. Open `admin.php` in your webbrowser and log in with the username/password you chose in the installation step.
2. Create events and (optional) vouchers for them.
3. Now, visitors can make ticket reservations by opening to the root folder of the web app (`index.php`) in their browser.
4. When the time of the event come, you open `check.php` and scan the (QR) codes at the entrance.

## Custom Design
You can set a custom background image by placing a file `bg-custom.{jpg|png}` inside the `/img` dir. A custom logo image will be displayed if a file `logo-custom.{jpg|png}` is found. Custom CSS can be injected into the page by placing a file called `custom.css` inside the `/css` dir.

## Support & Cloud-Hosting
You do not have an own web server, you need support with installation or operation or want a special development for your needs? Please [contact me](https://georg-sieber.de/?page=impressum).

## Acknowledgements
[php-qrcode](https://github.com/psyon/php-qrcode) library by pyson (MIT license)
