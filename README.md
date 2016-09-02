# RPIPWRMAN
Power Management using Raspberry based on RPIrrigate by bobvann
(just some customization on web interface)

- Download required packages

        sudo apt-get install git lighttpd php5-common php5-cgi php5 php-pear php5-sqlite rpi.gpio sqlite3

- Get the main code

        git clone https://github.com/flavio70/RPIPWRMAN.git

- Enable php on lighttpd

        sudo lighttpd-enable-mod fastcgi
        sudo lighttpd-enable-mod fastcgi-php

- Create the folder /srv/rpirrigate

        sudo mkdir -p /srv/rpirrigate

- Copy the main code folders data, daemon e web

        sudo cp -R RPIPWRMAN/data /srv/rpirrigate
        sudo cp -R RPIPWRMAN/daemon /srv/rpirrigate
        sudo cp -R RPIPWRMAN/web /srv/rpirrigate

- Give the rights

        sudo chown -R www-data:www-data /srv/rpirrigate
        sudo chmod -R 775 /srv/rpirrigate

- Create the log files with correct rights

        sudo mkdir /var/log/rpirrigate
        sudo touch /var/log/rpirrigate/status.log
        sudo touch /var/log/rpirrigate/error.log
        sudo chown -R www-data:www-data /var/log/rpirrigate
        sudo chmod -R 775 /var/log/rpirrigate

- Copy the logrotate file with correct rights

        sudo cp RPIPWRMAN/install/logrotate.erb /etc/logrotate.d/rpirrigate
        sudo chmod 755 /etc/logrotate.d/rpirrigate
        sudo chown root:root /etc/logrotate.d/rpirrigate

- Copy init.d files in /etc/init.d with correct rights

        sudo cp RPIPWRMAN/install/init.d.erb /etc/init.d/rpirrigate
        sudo chmod 755 /etc/init.d/rpirrigate
        sudo chown root:root /etc/init.d/rpirrigate

- Change the lighttpd's document root and port (if needed):

        sudo nano /etc/lighttpd/lighttpd.conf

  *Modify the following row (if needed):*
  
        server.port   = 80    →   server.port    = 667
  
  *And this:*
  
        server.document-root = “/var/www”   →  server.document-root = “/srv/rpirrigate/web”
  
  *Save and quit ( CTRL+X, Y, INVIO)*

- Enable the services

        sudo systemctl enable rpirrigate

- Last setting for the RPIPWRMAN user that run the services

        sudo chsh -s /bin/sh www-data

- Start the services

        sudo service rpirrigate start
        sudo service lighttpd restart

we can connect now to the RPIPWRMAN GUI using a web browser (hostname -I to discover the RPI IP address).
Default user and password are 'admin', 'admin'



 
