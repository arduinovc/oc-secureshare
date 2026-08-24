# Office Center - SecureShare

![SecureShare](/img/logo.png "SecureShareLogo") 
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

## Screenshots (WiP)
![Setup Wizzard](/img/setup.png "Setup Wizzard") 
![Generate token](/img/generate.png "Generate Page") 
![Share token](/img/share.png "Share Page") 


## Planned Features
Implement multilingual support (French and English).  
Add a language switcher in the user interface.  
User authentication for create.php and admin.php.  
Automatic removal of expired tokens.  
Add .htaccess security rules to protect application files and sensitive resources.  
Hide the secret by default on the retrieval page.  
Add a "Show Secret" button to temporarily reveal the secret.  
Add a "Copy Secret" button to easily copy the secret to the clipboard.  
Implement automatic cleanup of expired and consumed secrets from the database.  
Display the SecureShare logo alongside the customer logo for improved branding and user trust.  

## Requirements
PHP 8.1+  
MySQL / MariaDB  
Bootstrap 5  

## License  
MIT License  


