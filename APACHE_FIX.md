# Apache Configuration Fix for Restructured Project

## Step 1: Update Apache DocumentRoot

SSH into EC2 and edit Apache config:
```bash
sudo nano /etc/apache2/sites-available/sanjivanitechfest.awscc.tech.conf
```

Change:
```apache
DocumentRoot /var/www/html
```

To:
```apache
DocumentRoot /var/www/html/public
```

Update Directory section:
```apache
<Directory /var/www/html/public>
    AllowOverride All
    Require all granted
</Directory>
```

Reload Apache:
```bash
sudo systemctl reload apache2
```

## Step 2: Clean EC2 Repository State

```bash
cd /var/www/html
sudo git fetch origin
sudo git reset --hard origin/main
```

## Step 3: Deployment is Already Fixed

The deployment script now uses `git reset --hard` which will force sync on every push.
