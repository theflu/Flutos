# Flutos
A flat file photo album

## Setup
1. Clone repo
2. Make ```lib``` and ```albums``` writable by the web user ie: apache or www-data
3. Create ```.htaccess``` file
4. Point webserver to ```public_html```

### Example .htaccess
```
RewriteEngine on
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php?q=$1&p=$2 [L,NC,QSA]
```
