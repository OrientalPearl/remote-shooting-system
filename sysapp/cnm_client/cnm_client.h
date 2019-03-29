#ifndef __CNM_CLIENT_H_
#define __CNM_CLIENT_H_

#include "cnm_config.h"
#include "sky_syslog.h"

#define CNM_SERVER_TIMEOUT_DEFAULT 60
#define CNM_SERVER_TRANSPARENT_THRESHOLD 300

#define log_cnm(fmt...) cnm_printf( fmt )


#define YSF_SERVER_INIT_FLAG "/tmp/.server_init"
#define YSF_SERIAL_FILE "/home/ysf/.camera/sn.txt"
#define YSF_PARAMS_FILE "/home/ysf/.camera/param.ini"
#define YSF_VERSION_FILE "/home/ysf/.cnm_client/Firmware_version"
#define YSF_UPLOAD_SIZE_FILE "/home/ysf/.cnm_client/.upload_size"

struct ysf_msg_header_t
{
    u8 start_flag;
    u8 length;
    u16 seq;
    u8 code;
    u8 reason;
    u8 data[0];
};

#define CNM_CLIENT_PHOTOS_PATH "/home/ysf/photos"


#endif

