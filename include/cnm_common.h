#ifndef _CNM_COMMON_H_
#define _CNM_COMMON_H_

#include "cnm_config.h"

#define CNM_PORT 51233

/*
 *********************************************************
 *                         通讯数据包格式定义
 *********************************************************
 * 
 *                               ---------------------------------
 *  封包格式:    | total_len | cnm_data | cnm_data | ...... |
 *                               ---------------------------------
 *                                ----------------------
 *  cnm_data :            | cnm_data_header | data |       
 *                                ----------------------
 *                                -----------------------------------------
 * cnm_data_header : | type |data_len |resolve_type |resevered |serial |
 *                                -----------------------------------------
 *
 * data :TLV数据 
 * note:其中的数据均使用主机序
 *********************************************************
 */

#define AD_TOKEN_LEN 128
#define WECHAT_NO_LEN 128
#define AUTH_TYPE_LEN 32

#pragma pack (push, 1)

struct cnm_packet
{
    u32 total_len;
    char data[1];
};

struct cnm_data_header
{
    u32 type;
    u16 data_len;
    u8 resolve_type;
    u8 reserved;
    char serial[32];
};

struct cnm_data
{
    struct cnm_data_header header;
    char data[1];
};

struct user_log_data
{
	//long online_time;
    unsigned int user_type;
	unsigned int up_byte;
	unsigned int dn_byte;
    unsigned int ct_count;
	unsigned char mac[6];
};

struct cnm_heardbeat
{
    char ssid[64];
    u32 cpu_use_rate;
    u32 mem_use_rate;
    u32 version;
    u32 traffic_up;
    u32 traffic_dn;
    u32 session_total;
    u32 session_new;
    u32 user_num;
};

struct cnm_update_item_data
{
	char update_cmd[128];
	u32 version;	
};

struct cnm_bwlimit_item_data
{
	int bwlimit;	
};

struct cnm_upload_limit_day_item_data
{
	int upload_limit_day;	
};


struct cnm_conf_item_data
{
	int bwlimit;	
    char passwd[128];
    int upload_limit_day;
    int upload_status;
};


struct cnm_sync_item_data
{
	char sync_cmd[128];
};


struct cnm_wb_user_node
{
    u8 user_type;// 2 black user 3 white user 1 auth user
    u8 op;//0 add, other del
    u8 user_mac[6];
};

struct cnm_wb_user_notify
{
    u16 total_len;
    u16 node_num;    
    char node[1];
};

struct cnm_token_and_wechat_no_notify
{
    char token[AD_TOKEN_LEN];
    char wechat_no[WECHAT_NO_LEN];
    char auth_type[AUTH_TYPE_LEN];
};

#pragma pack (pop)

enum user_bwlist_type
{
    USER_BWLIST_TYPE_NONE,
    USER_BWLIST_TYPE_AUTH,
    USER_BWLIST_TYPE_BLACKLIST,
    USER_BWLIST_TYPE_WHITELIST
};

enum cnm_exchange_id
{
    CNM_CLIENT_MSG_RESPONSE = 1,
    CNM_DEVICE_SYS_STAT_INFO,
    CNM_UPDATE_SOFTWARE_REQUEST,
    CNM_UPDATE_SOFTWARE_RESPONSE,
    CNM_UPDATE_SOFTWARE_ACTION,
    
    CNM_TASKS_SYNC_REQUEST,
    CNM_TASKS_SYNC_ACTION,
    CNM_LIMIT_SYNC_REQUEST,
    CNM_LIMIT_SYNC_ACTION,
    CNM_UPDATE_STATUS_ACTION,
    
    CNM_BASE_SYNC_REQUEST,
    CNM_BASE_SYNC_ACTION,
    CNM_PREVIEW_SYNC_REQUEST,
    CNM_PREVIEW_SYNC_RESPONSE,
    CNM_PREVIEW_SYNC_ACTION,

    CNM_BWLIMIT_SYNC_NOTIFY,
    CNM_BWLIMIT_SYNC_RESPONSE,

    CNM_CONF_REQUSET,
    CNM_CONF_REQUSET_RESPONSE,
    CNM_UPLOAD_STATUS_ACTION,

    CNM_UPLOAD_LIMIT_DAY_SYNC_NOTIFY,
    CNM_UPLOAD_LIMIT_DAY_SYNC_RESPONSE,

    CNM_UPLOAD_LIMIT_DAY_WARNNING_NOTIFY,
    CNM_UPLOAD_LIMIT_DAY_WARNNING_RESPONSE,
};

enum cnm_resolve_type
{
    CNM_RESOLVE_TYPE_INTERGER,
    CNM_RESOLVE_TYPE_STRING,
    CNM_RESOLVE_TYPE_BINARY,
    CNM_RESOLVE_TYPE_TLV,
};

typedef struct _cnm_attribute_t 
{
    unsigned short attribute;
    unsigned short length;
    unsigned char data[1];
} CNM_ATTRIBUTE;

enum cnm_data_type
{
    CNM_DATA_TYPE_VERSION,
    CNM_DATA_TYPE_APERTURE_CURRENT,
    CNM_DATA_TYPE_SHUTTER_CURRENT,
    CNM_DATA_TYPE_ISO_CURRENT,
    
    CNM_DATA_TYPE_APERTURE_RANGE,
    CNM_DATA_TYPE_SHUTTER_RANGE,
    CNM_DATA_TYPE_ISO_RANGE,

    CNM_DATA_TYPE_TASKS_STATUS,
    CNM_DATA_TYPE_LAST_PHOTO_TIME,
    CNM_DATA_TYPE_NEXT_PHOTO_TIME,

    CNM_DATA_TYPE_ELECTRICITY,
    CNM_DATA_TYPE_TEMPERATURE,
    CNM_DATA_TYPE_HUMITURE,
    CNM_DATA_TYPE_CAMERA_CONNECTION,
	CNM_DATA_TYPE_UPLOAD_STATUS,
};

#define CNM_SERVER_PHOTOS_PATH "/mnt/photos"


#endif
