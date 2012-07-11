Configuration of the Federation Server
=====================================

1) install simplesamlphp
=====================================
*Download the latested version of simplesamlphp (version 1.9.0)
*unzip the file and put it in a folder of your choice 
*in apache 2 add the following lines to sites-available configuration
 files ->  Alias /saml /path/to/simplesamlphp-1.9.0/www
* for more details on the installation visit the documentation at
* http://simplesamlphp.org/docs/1.9/simplesamlphp-install

2) add configurations from the git directory
====================================
#git/php-cas-server/configsaml contains the  following files,
   # authsource.php  contains the configurations of ENT(laclasse) metadata
    #config.php      contains the configurations of phpsimplesaml like paths, admin(password), ..
    #saml20-idp-remote contians the (Academie de lyon) Metadata (2 profiles agent/Eleve-parent)

cd /path/to/simplesamlphp-1.x.y
cp  git/configsaml/authsource.php  config/authsource.php
cp  git/configsaml/config.php  config/config.php
cp  git/configsaml/saml20-idp-remote.php metadata/saml20-idp-remote.php

#delete the by-default contents y.
#change the configuration in the config/config.php  like basedir and
#passowrds ..

3) test the connection and obtain ENT metadata
====================================
to assure  correct simplesamlphp installation·
test the page https://server.on.which.simplesamlphp.is.installed/saml/


4) ENT metadata
====================================
#authsource.php file  in the simplesamlphp  config directory  contains the
#configurations of provided services by the ENT ( service parent/eleve,
#service agent)
# to see examples of the configured services browse the authsource.php
#certificates used in the metadata needs to be added  to the cert directory in the
#  simplesamlphp
#  ENT metadata to be sent the academie can be obtained by clicking the federation tab on the test
page  and then (show metadata)
# important note: the obtained metadata must be manipulated to be
# literaly conform to the academy server. for that see  the form of the
# metadata used for the develpement phase in the ENTmetadata.zip


Academie de lyon metadata
===================================
#saml20-idp-remote file in the simplesamlphp  metadata directory contains the
#configuration of the (academie de lyon) metadata.
#we find two metadata configuration (agent, parent/eleve)

5)Certificates
===================================
# Certificates are used to define The ENT metadata in the simplesamlphp/config/authsource.php file
#generate and add certificates to the /cert directory in the simplesamlphp.
# Let your configuration (in authsource.php) point to certificates' names in /cert directory
# for example: to generate a self signed certificates follow these steps:
openssl genrsa -des3 -out server.key 1024 
openssl rsa -in server.key -out server.pem
openssl req -new -key server.key -out server.csr
openssl x509 -req -days 9999 -in server.csr -signkey server.key -out server.crt
# 

6) Add simplesamlphp path and authentication sources (agent portal,parent portal ) to cas server configuration
==================================
# add  /path/to/simplesamlphp-1.9.0/www to the config.inc.php file
# add the url of the ENT server to config.inc.php 
# add the url of the cas server to config.inc.php 
# add the url of the inscription page to config.inc.php
# add the names of authentication(defined in /simplesamlphp/config/authsources.php) sources to config.inc.php
