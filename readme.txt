Configuration of  the Federation Server:

//-------------install simplesamlphp----------------------------//
*Download the latested version of simplesamlphp (version 1.9.0)
*unzip the file and put it in a folder of your choice 
*in apache 2 add the following lines to sites-available configuration
 files ->  Alias /saml /path/to/simplesamlphp-1.9.0/www
* for more details on the installation visit the documentation at
* http://simplesamlphp.org/docs/1.9/simplesamlphp-install

//--------------add configurations from the git directory--------//
cd /path/to/simplesamlphp-1.x.y
cp -rv git/configsaml/authsource.php  config/authsource.php
cp -rv git/configsaml/config.php  config/config.php

cp -rv git/configsaml/saml20-idp-remote.php metadata/saml20-idp-remote.php
delete the by default contents of other files in metadata directory.

change the configuration in the config/config.php  file

//------------test the connection and obtain ENT metadata------//
   40 #to assure  correct simplesamlphp installation·
   41 test the page https://server.on.which.simplesamlphp.is.installed/saml


//---------------ENT metadata----------------------------------------//
#authsource.php file  in the simplesamlphp  config directory  contains the
#configurations of provided services by the ENT ( service parent/eleve,
#service agent)
# to see examples of the configured services browse the authsource.php
#certificates used in the metadata needs to be added  to the cert directory in the
#  simplesamlphp
#  ENT metadata tobe sent the academie can be obtained  by clicking the federation tab on the test
page  and then (show metadata)
# important note: the obtained metadata must be manipulated to be
# literaly conform to the academy server ' for that use the same form of the
# metadata used for the develpement phase in the ENTmetadata.zip


//---------------Academie de lyon metadata---------------------------//
#saml20-idp-remote file in the simplesamlphp  metadata directory contains the
#configuration of the (academie de lyon) metadata.
#we find two metadata configuration ( agent, parent/eleve)
//----------------certificates---------------------------------------//
#generate and add certificates to the cert directory in the simplesamlphp


//----add simplesamlphp path to cas server configuration-------//
# add  /path/to/simplesamlphp-1.9.0/www to the config.inc.php file
# add the url of the ENT server to config.inc.php 
# add the url of the cas server to config.inc.php 
# add the url of the inscription page to config.inc.php

