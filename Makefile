doc:
	doxygen Doxyfile

publish: pubpilou

pubpilou:
	rsync -avz . root@cas-erasme.erasme.lan:/var/www/cas/ --exclude .git* --exclude doc/

pubmb:
	rsync -avz . root@cas-erasme.erasme.lan:/var/www/cm/ --exclude .git* --exclude doc/
