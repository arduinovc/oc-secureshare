# OC-SecureShare

A lightweight PHP application designed to securely share passwords and sensitive information through one-time links.

## Features
End-to-end server-side encryption of secrets  
One-time or limited-access secret sharing  
Configurable expiration:  
  Time-based expiration  
  Access count expiration  
Automatic secret destruction after expiration  
Optional customer name and ticket number tracking  
Access logging and audit trail  
Clipboard-friendly secret generation  
Simple Bootstrap-based interface  
Self-hosted and database-independent architecture  
Installation wizard to:
  Generate the application encryption key  
  Create database tables  
  Initialize configuration  
  Automatically disable setup after installation  

## Use Cases
IT support password sharing  
Temporary administrator account delivery  
Secure transmission of Wi-Fi credentials  
Sharing API keys and access tokens  
Client-to-technician secret exchange   
Security  

Secrets are encrypted before storage and remain inaccessible without the server encryption key. Expired or exhausted links are automatically destroyed to minimize data exposure.  

## Requirements
PHP 8.1+  
MySQL / MariaDB  
Bootstrap 5  

## License  
MIT License  
