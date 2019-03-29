#ifndef __NETCENTER_H
#define __NETCENTER_H


/*************************************************
�������ܵ���ƣ�
1����������ʹ��tcp�����ӣ��򿪱��ʱ��ѡ�Ĭ�϶˿�12586��
ÿ10�뷢��һ�α��ģ�������5������û���յ�������tcp���ӶϿ�������Ϊ����״̬��ʧ���������յ�֮��
Ӧ�ö��Ƿ��ȷ�ϣ�tcp������ȷ�ϣ������ʵ�ʲ�����������������ݲ���3des���ܣ���Կ���߶��������á�
2�����ݸ�ʽ��û����Ϣ���������ָ�ʽ����������(4�ֽ�)+���ݳ���(2�ֽ�)+���ݽ�������(1�ֽڣ�0:���ͣ�1�ַ���)+Ԥ��(1�ֽ�)+�������ݣ�
���Ͷ��������ֽ���
3����������ݰ�����
�豸ip��ַ��4�ֽ�
ʱ�����������ʱ���룬6�ֽ�
sequence��4�ֽڣ�ÿ�μ�1����0��ʼ
�������ƣ����128�ֽڣ�NX�������ܣ��������Ʊ�����󳤶ȣ�(Ӣ���ַ����127���������ַ����42��) 
����������4�ֽڣ���kbpsΪ��λ
����������4�ֽڣ���kbpsΪ��λ
Э���ʽ
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

