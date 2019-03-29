#ifndef _SKY_COMMON_STRUCT_H_
#define _SKY_COMMON_STRUCT_H_

#include "sky_common_macro.h"

typedef struct nmr_msg_body {
    nmr_msg_type_t type;
    char devmac[18];
    char usrmac[18];
    u32 flow;
    time_t stime;
    time_t etime;
}nmr_msg_body_t;

typedef struct nmr_msg {
    long type;
    nmr_msg_body_t body;
}nmr_msg_t;

#endif

