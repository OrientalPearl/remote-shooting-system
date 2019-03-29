
#ifndef RUIJIE_SMP_2009_SSO_H
#define RUIJIE_SMP_2009_SSO_H

#pragma pack(push)
#pragma pack(4)

struct demo_msg_hdr
{
    unsigned char flag;
    unsigned char length;
    unsigned short seq;
    unsigned char code;
    unsigned char reason;
    unsigned char data[0];
};

#pragma pack(pop)

#endif
