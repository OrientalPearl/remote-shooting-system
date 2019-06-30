#ifndef _CNM_SERVER_H_
#define _CNM_SERVER_H_

#include <sys/epoll.h>

#include "sky_common_macro.h"
#include "sky_common_struct.h"
#include "cnm_common.h"
#include "sky_syslog.h"


#define __jhash_mix(a, b, c) \
{ \
  a -= b; a -= c; a ^= (c>>13); \
  b -= c; b -= a; b ^= (a<<8); \
  c -= a; c -= b; c ^= (b>>13); \
  a -= b; a -= c; a ^= (c>>12);  \
  b -= c; b -= a; b ^= (a<<16); \
  c -= a; c -= b; c ^= (b>>5); \
  a -= b; a -= c; a ^= (c>>3);  \
  b -= c; b -= a; b ^= (a<<10); \
  c -= a; c -= b; c ^= (b>>15); \
}

#define JHASH_GOLDEN_RATIO	0x9e3779b9

static inline u32 jhash(const void *key, u32 length, u32 initval)
{
	u32 a, b, c, len;
	const u8 *k = (const u8 *)key;

	len = length;
	a = b = JHASH_GOLDEN_RATIO;
	c = initval;

	while (len >= 12) {
		a += (k[0] +((u32)k[1]<<8) +((u32)k[2]<<16) +((u32)k[3]<<24));
		b += (k[4] +((u32)k[5]<<8) +((u32)k[6]<<16) +((u32)k[7]<<24));
		c += (k[8] +((u32)k[9]<<8) +((u32)k[10]<<16)+((u32)k[11]<<24));

		__jhash_mix(a,b,c);

		k += 12;
		len -= 12;
	}

	c += length;
	switch (len) {
	case 11: c += ((u32)k[10]<<24);
	case 10: c += ((u32)k[9]<<16);
	case 9 : c += ((u32)k[8]<<8);
	case 8 : b += ((u32)k[7]<<24);
	case 7 : b += ((u32)k[6]<<16);
	case 6 : b += ((u32)k[5]<<8);
	case 5 : b += k[4];
	case 4 : a += ((u32)k[3]<<24);
	case 3 : a += ((u32)k[2]<<16);
	case 2 : a += ((u32)k[1]<<8);
	case 1 : a += k[0];
	};

	__jhash_mix(a,b,c);

	return c;
}

#define CNM_CLIENT_TIMEOUT     300        /*  seconds  */
#define CNM_SELECT_TIMEOUT     1          /*  seconds  */

#define SKY_KEY_HASH_MAX       256
#define SKY_KEY_HASH_MASK      (SKY_KEY_HASH_MAX - 1)

#define CNM_CLIENT_CMD_LEN     1024

#define CNM_MAX_RECV_BUF       ( 4 * 1024 * 1024 )

#define CNM_THREAD_FD_HASH_SIZE  0x100
#define CNM_THREAD_FD_HASH_MASK (CNM_THREAD_FD_HASH_SIZE - 1)
#define CNM_DATA_HANDLE_THREAD_NUM 0x8
#define CNM_DATA_HANDLE_MAX_FD_NUM 256
#define CNM_EPOLL_MAX_EVENT 100

#define CNM_UPDATE_HASH_SIZE 256
#define CNM_UPDATE_HASH_MASK (CNM_UPDATE_HASH_SIZE - 1)
#define CNM_UPDATE_TIMEOUT 1800
#define CNM_UPDATE_DEVICE_NUM_MAX 256

#define CNM_AUTO_UPDATE_CFG_FILE "/usr/private/auto_update.cfg"
#define CNM_DGB_FILE "/home/config/current/cnm_dbg.cfg"

#define CNM_SERVER_DB   "`ysf`"

struct cnm_client_info
{
    char serial[32];
    u32 tasks_status;
    u32 cc_ip;
    u32 upload_status;

    char data_field_start[0];
    
    char aperture_current[64];
    char shutter_current[64];
    char iso_current[64];

    char aperture_range[512];
    char shutter_range[512];
    char iso_range[512];

    char last_photo_time[32];
    char next_photo_time[32];

    char temperature[64];
    char humiture[64];

    int electricity;
    int camera_connection;
    
    u32 version;

    char data_field_end[0];
};

struct cnm_client_data
{
    u32 cc_up_kbps;
    u32 cc_down_kbps;
    char cc_session[64];
    char cc_ips[64];
    char cc_users[64];
    char cc_cpu[64];
    char cc_temperature[64];
    char cc_bw_rate[64];
    char cc_mm_rate[64];
};

struct cnm_client_cmd
{
    sky_list_head cmd_list;
    u32 cmd_id;
    char cmd_str[CNM_CLIENT_CMD_LEN];
    time_t cmd_start;
};

struct cnm_client_node
{
    int client_sid;
    u32 client_seq;
    time_t client_time;
    struct cnm_client_info client_info;
};

struct cnm_client_list
{
    struct sky_list_head client_fd_list;  /*¹ÒÔØsockfd hash*/
    struct sky_list_head client_key_list; /*¹ÒÔØkey hash*/
    struct cnm_client_node client_node;
    time_t wbu_time;
    time_t wbu_timeout;
    bool no_update_db;
	bool update_soft;
    int thread_idx;
    bool no_update_wb;

    int tasks_synced_seq;
    int tasks_sync_seq;
    time_t task_sync_timeout;

    int limit_synced_seq;
    int limit_sync_seq;
    time_t limit_sync_timeout;

    int base_synced_seq;
    int base_sync_seq;
    time_t base_sync_timeout;

    int preview_synced_seq;
    int preview_sync_seq;
    time_t preview_sync_timeout;

    int bwlimit;
    int bwlimit_synced_seq;
    int bwlimit_sync_seq;
    time_t bwlimit_sync_timeout;

    int upload_limit_day;
    int upload_limit_day_synced_seq;
    int upload_limit_day_sync_seq;
    time_t upload_limit_day_sync_timeout;
};

struct cnm_thread_info
{
    struct sky_list_head fd_hash_table[CNM_THREAD_FD_HASH_SIZE];
    s32 fd_num;
    int epollfd;
    pthread_mutex_t fd_hash_table_lock;
};



enum cnm_update_soft_status_type
{
    CNM_UPDATE_SOFT_SEND_REQUEST,
	CNM_UPDATE_SOFT_REQUEST_ACK,
	CNM_UPDATE_SOFT_UPDATE_ACTION,
};


struct cnm_update_soft_status_node
{
	struct sky_list_head list;
	char serial[32];
	enum cnm_update_soft_status_type status;
	time_t last_use_time;
};

struct cnm_update_soft_status_list
{
	struct sky_list_head hash_table[CNM_UPDATE_HASH_SIZE];
	u32 client_num;
	pthread_mutex_t lock;
};


#define SERVER_PASSWORD "ysf@Little-Star-2019"

extern void cnm_print_data( const u8* data, int data_len );
#endif
