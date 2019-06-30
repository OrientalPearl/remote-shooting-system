mkdir -p /home/ysf/.cnm_client
cp -af /home/ysf/fimware/Firmware_version /home/ysf/.cnm_client
cp -af /home/ysf/.cnm_client/cnm_client /home/ysf/.cnm_client/cnm_client.bak
killall -9 cnm_client
cp -af /home/ysf/fimware/cnm_client /home/ysf/.cnm_client/cnm_client
cp -af /home/ysf/fimware/appmonitor /home/ysf/.cnm_client/appmonitor
#sleep 5
#/home/ysf/.cnm_client/cnm_client
#/home/ysf/app/start.sh
#/home/ysf/app/update.sh
rm -rf /home/ysf/fimware
sudo sync
sudo reboot