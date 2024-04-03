# Winback Interface

Winback-assist is a management platform to manage Winback devices connected on the server.

Please check that at least PHP 8.0 is installed on your device and check that php command is available on your terminal with `php -v`.
Check also that you have Composer, MySQL and Symfony installed and available.

Before running the application, follow these steps:
- download this folder in your device
- import the database in your database service
- run `composer install` to install the dependencies
- the Ressource folder is a sensitive data so it is not present by default. If you have the Ressource archive on your device, please export it in the public directory.
- Update the .env files accordingly to your environment variables

## How to use:

### Start Server:
Open a terminal and run command: ```symfony console app:tcpserver```

This will create a new server and connect with devices.
If the server starts correctly, it prints server connexion information, device information and commands received.

### Start Interface:
Open a terminal and run command: ```symfony start:server```
With specific port:
```symfony server:start --port=8080```
