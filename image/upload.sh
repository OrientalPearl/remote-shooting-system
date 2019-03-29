cp -af /usr/private/download/fimware/cnm_server /usr/private/cnm_server.new
cp -af /usr/private/download/fimware/mail/* /usr/private/
cp -af /usr/private/cnm_server /usr/private/cnm_server.bak
killall -9 cnm_server
cp -af /usr/private/cnm_server.new /usr/private/cnm_server
/usr/private/cnm_server
rm -rf /var/www/html/cms
cp -af /usr/private/download/fimware/cms /var/www/html/cms
chmod 777 /var/www/html/cms
cp -af /usr/private/download/fimware/Firmware_version /usr/private/Firmware_version
rm -rf /usr/private/download/fimware