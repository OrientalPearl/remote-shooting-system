
CURDIR := $(shell pwd)
PRODUCT_CODE_BASE = $(CURDIR)
CUR_TIME=`date "+%Y%m%d.%H%M%S"`

v=1.0.0.0

sysapp_exist=$(shell if [ -d $(CURDIR)/sysapp ]; then echo "exist"; else echo "don't exist"; fi)
client_exist=$(shell if [ -d $(CURDIR)/sysapp/cnm_client ]; then echo "exist"; else echo "don't exist"; fi)

.PHONY:all
all: sysapp install

.PHONY: sysapp
sysapp:
ifeq ($(sysapp_exist),exist)
	make -C $(CURDIR)/sysapp/cnm_server
	cp $(CURDIR)/sysapp/cnm_server/cnm_server $(CURDIR)/image/
	make -C $(CURDIR)/sysapp/systemmonitor
	cp $(CURDIR)/sysapp/systemmonitor/appmonitor $(CURDIR)/image/
endif

.PHONY: sysapp-clean
sysapp-clean:
ifeq ($(sysapp_exist),exist)
	cd $(CURDIR)/sysapp/cnm_server && make clean
	cd $(CURDIR)/sysapp/systemmonitor && make clean
endif

.PHONY: client
client:
ifeq ($(client_exist),exist)
	make -C $(CURDIR)/sysapp/cnm_client
	cp $(CURDIR)/sysapp/cnm_client/cnm_client $(CURDIR)/image/
	make -C $(CURDIR)/sysapp/systemmonitor client=1
	cp $(CURDIR)/sysapp/systemmonitor/appmonitor $(CURDIR)/image/
endif

.PHONY: client-clean
client-clean:
ifeq ($(sysapp_exist),exist)
	cd $(CURDIR)/sysapp/cnm_client && make clean
	cd $(CURDIR)/sysapp/systemmonitor && make clean
endif

.PHONY: clean
clean: sysapp-clean

.PHONY: install
install:
	rm -rf $(PRODUCT_CODE_BASE)/build
	mkdir -p $(PRODUCT_CODE_BASE)/build
	cp $(PRODUCT_CODE_BASE)/image/cnm_server $(PRODUCT_CODE_BASE)/build/
	cp -af $(PRODUCT_CODE_BASE)/mail $(PRODUCT_CODE_BASE)/build/
	echo CNMFirmware-$(v) build $(CUR_TIME) > $(PRODUCT_CODE_BASE)/build/Firmware_version
	cp $(PRODUCT_CODE_BASE)/image/upload.sh $(PRODUCT_CODE_BASE)/build/	
	cp -af $(PRODUCT_CODE_BASE)/page $(PRODUCT_CODE_BASE)/build/cms
	find $(PRODUCT_CODE_BASE)/build/cms -name .svn | xargs rm -rf	
	chmod +x $(PRODUCT_CODE_BASE)/build/cnm_server
	chmod +x $(PRODUCT_CODE_BASE)/build/upload.sh	
	cd $(PRODUCT_CODE_BASE)/build/ && tar zcf CNMFirmware.bin *
	rm $(PRODUCT_CODE_BASE)/image/CNMFirmware.bin -rf
	mv $(PRODUCT_CODE_BASE)/build/CNMFirmware.bin $(PRODUCT_CODE_BASE)/image/CNMFirmware.raw
	openssl enc -des -e -a -k ysf@2019 -in $(PRODUCT_CODE_BASE)/image/CNMFirmware.raw -out $(PRODUCT_CODE_BASE)/image/CNMFirmware.bin
	rm -rf $(PRODUCT_CODE_BASE)/image/CNMFirmware.raw
	
.PHONY: client-install
client-install:
	rm -rf $(PRODUCT_CODE_BASE)/image/Firmware_*
	rm -rf $(PRODUCT_CODE_BASE)/build
	mkdir -p $(PRODUCT_CODE_BASE)/build
	cp $(PRODUCT_CODE_BASE)/image/cnm_client $(PRODUCT_CODE_BASE)/build/
	cp $(PRODUCT_CODE_BASE)/image/appmonitor $(PRODUCT_CODE_BASE)/build/
	cp $(PRODUCT_CODE_BASE)/image/ysf_client.tar.gz $(PRODUCT_CODE_BASE)/build/
	echo $(v) > $(PRODUCT_CODE_BASE)/build/Firmware_version
	cp $(PRODUCT_CODE_BASE)/image/update.sh $(PRODUCT_CODE_BASE)/build/	
	chmod +x $(PRODUCT_CODE_BASE)/build/cnm_client
	chmod +x $(PRODUCT_CODE_BASE)/build/update.sh	
	cd $(PRODUCT_CODE_BASE)/build/ && tar zcf $(PRODUCT_CODE_BASE)/image/Firmware_$(v).tar.gz *

