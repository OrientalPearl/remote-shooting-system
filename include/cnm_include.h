#ifndef __NETCENTER_H
#define __NETCENTER_H


/*************************************************
集中网管的设计：
1，集中网管使用tcp长连接，打开保活定时器选项，默认端口12586，
每10秒发送一次报文，服务器5分钟内没有收到，或者tcp连接断开，则认为连接状态丢失，服务器收到之后，
应用段是否给确认（tcp本身有确认）则根据实际测试情况来决定。数据采用3des加密，密钥两边都可以配置。
2，数据格式，没有信息都采用这种格式：数据类型(4字节)+数据长度(2字节)+数据解析类型(1字节，0:整型，1字符串)+预留(1字节)+数据内容，
整型都用网络字节序。
3，传输的数据包括：
设备ip地址，4字节
时间戳，年月日时分秒，6字节
sequence，4字节，每次加1，从0开始
网点名称：最大128字节，NX集中网管，网点名称标明最大长度，(英文字符最大127个，中文字符最大42个) 
上行流量：4字节，以kbps为单位
下行流量：4字节，以kbps为单位
协议格式
| type   | data_len |  obligage type |  resolve  |   data      |
| 4 byte | 2 byte   |      1 byte    |   1 byte  |   any byte  |


1  packet
| data_len | ip  | sequence | time | dev name | up speed | down speed |


**************************************************/

enum centrall_data_type
{
    NX_DEV_IP_TYPE = 1,
    NX_DEV_TIME_TYPE,
    NX_DEV_SEQUENCE_TYPE,
    NX_DEV_NAME_TYPE,
    NX_DEV_UP_SPEED_TYPE,
    NX_DEV_DOWN_SPEED_TYPE,
    NX_DEV_MAC_TYPE,
    NX_DEV_WEB_SSL_TYPE,
    NX_DEV_WEB_PORT_TYPE,
    NX_DEV_SERVER_KEEPLIVE_TYPE,
};


#define NX_DEV_IP_LEN                 4
#define NX_DEV_TIME_LEN               4
#define NX_DEV_SEQUENCE_LEN           4
#define NX_DEV_NAME_LEN               128
#define NX_DEV_UP_SPEED_LEN           4
#define NX_DEV_DOWN_SPEED_LEN         4
#define NX_DEV_MAC_LEN                6
#define NX_DEV_WEB_SSL_LEN            4
#define NX_DEV_WEB_PORT_LEN           4
#define NX_DEV_SERVER_KEEPLIVE_LEN    4

#ifndef ACE_PORT_INFO_FILE
#define ACE_PORT_INFO_FILE                "/home/nt_port_info.dat"
#endif

#ifndef CENT_INIT_CONFIG_FILE
#define CENT_INIT_CONFIG_FILE              "/etc/cent.conf"
#endif

#ifndef CENT_SERVER_PORT
#define CENT_SERVER_PORT                12586
#endif

#ifndef CENT_SERVER_TIMEOUT_DEFAULT
#define CENT_SERVER_TIMEOUT_DEFAULT     60
#endif

typedef struct swap_data_header
{
    U32 type;
    U16 data_len;
    U8  obligage;
    U8  resolve;
} SWAP_DATA_HEADER;

typedef struct cent_swap_data_to_server
{
    SWAP_DATA_HEADER  header;
    char              data[1];
}CENT_SWAP_DATA_TO_SERVER;


typedef struct cent_swap_data
{
    U32   data_len;    /* socket send real data len */
    char  data[1];     /* socket send real data     */
}CENT_SWAP_DATA;

typedef struct cent_system_data
{
    S8  name[128];
    S8  server_ip[32];
}CENT_SYSTEM_DATA;


#endif

