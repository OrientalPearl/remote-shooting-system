#ifndef _SKY_SYSLOG_H_
#define _SKY_SYSLOG_H_

#include "cnm_config.h"

#define SKY_SYSLOG_PATH              "/var/log/cnm_server.log"

#define SKY_SYSLOG_MAX_SIZE          1024 * 1024 * 20

extern void cnm_printf(const char* fmt, ... );

#endif

