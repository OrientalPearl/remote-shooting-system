#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <syslog.h>
#include <signal.h>
#include <fcntl.h>
#include <unistd.h>
#include <pthread.h>
#include <sys/socket.h>  
#include <netinet/in.h>  
#include <arpa/inet.h> 
#include <sys/ioctl.h>
#include <linux/sockios.h>
#include <linux/socket.h>
#include <linux/tcp.h>
#include <unistd.h>
#include <errno.h>
#include <sys/socket.h>
#include <arpa/inet.h>
#include <string.h>
#include <sys/time.h>
#include <linux/netlink.h>
#include <netdb.h>
#include <sys/types.h>
#include <sys/stat.h>
#include <sys/select.h>
#include <pthread.h>
#include <sys/types.h>
#include <sys/msg.h>
#include <sys/ipc.h>   
#include <stddef.h>
#include <dirent.h>


#include "sky_common_macro.h"
#include "cnm_client.h"
#include "cnm_common.h"

int link_ok = 0;
int cent_sockfd = -1;

int ysf_link_ok = 0;
int ysf_sockfd = -1;

char device_serial[32];
char device_aperture_range[1024];
char device_shutter_range[1024];
char device_iso_range[1024];
char server_password[128] = "WUxiang906";
char bwlimit_conf[128] = {0};

int ysf_notify_limit_success = 1;
int ysf_notify_plan_success = 1;
int ysf_notify_status_success = 1;
int ysf_notify_base_success = 1;
int ysf_notify_preview_success = 1;

int ysf_notify_preview_response = 0;

int cnm_notify_preview_success = 1;
int cnm_conf_init_success = 0;



int ysf_status;
int ysf_upload_status = 1;
int ysf_upload_limit_day = 0;
unsigned long long ysf_upload_size = 0;
time_t ysf_upload_check_time = 0;
int ysf_upload_overflow = 0;
int cnm_notify_upload_limit_day_warnning = 0;


s8 software_version[4];

pthread_mutex_t cent_sockfd_mutex;
pthread_mutex_t ysf_sockfd_mutex;

extern void client_timer_heardbeat(char *status);
extern void ysf_notify_limit(void);
extern void ysf_notify_plan(void);
extern void ysf_notify_base(void);
extern void ysf_notify_preview(void);



#if 1
int ysf_system(const char *func, int lino, char *cmd)
{
    int status = system(cmd);

    if (status == -1)
    {
        log_cnm("%s %d error cmd:%s\n", 
            func, lino, cmd);
    }
    else
    {  
        if (WIFEXITED(status))  
        {  
            if (0 == WEXITSTATUS(status))  
            {  
                return 0;
            }  
            else  
            {  
                log_cnm("%s %d errno %d cmd:%s\n", 
                    func, lino, WEXITSTATUS(status), cmd);
            }  
        }  
        else  
        {  
            log_cnm("%s %d exit [%d] cmd:%s\n", 
                    func, lino, WEXITSTATUS(status), cmd);
        }  
    }
                    
    return -1;
}

#define IS_CHAR(c) ( ( c >= 0x20 ) && ( c <= 'z' ) )
void print_hex2string( unsigned char* addr, unsigned int len, char* buffer )
{
    unsigned char* buf      = NULL;
    unsigned int i        = 0;
    int j        = 0;
    int loop_len = 16;
    int byte_len = 0;

    if( !addr )
    {
        return;
    }

    buf = (u8*)addr;

    byte_len = sprintf(buffer, "%s", "\r\nAddress     Data(hex)               ");
    byte_len += sprintf(buffer + byte_len, "%s", "                          ASCII\r\n" );

    for( i = 0; i < ( len >> 4 ); i++ )
    {
        byte_len += sprintf(buffer + byte_len, "[%p]: ", (addr+(i<<4)));
        for( j=0; j < loop_len; j++ )
        {
            byte_len += sprintf(buffer + byte_len, "%02x ", buf[(i<<4)+j] );
        }
        byte_len += sprintf(buffer + byte_len,  "  " );
        for( j=0; j<loop_len; j++ )
        {
            if( IS_CHAR(buf[i*16+j]) )
            {
                byte_len += sprintf(buffer + byte_len, "%1c", buf[(i<<4)+j] );
            }
            else
            {
                byte_len += sprintf(buffer + byte_len, "." );
            }
        }
        byte_len += sprintf(buffer + byte_len, "\r\n" );
    }

    loop_len = len & 0x1F;
    if( loop_len )
    {        
        byte_len += sprintf(buffer + byte_len, "[%p]: ", (addr + (i<<4)));
        for( j = 0; j < loop_len; j++ )
        {
            byte_len += sprintf(buffer + byte_len, "%02x ", buf[(i<<4)+j] );
        }

        for( j = 0; j < (16-loop_len); j++ )
        {
            byte_len += sprintf(buffer + byte_len, "   " );
        }
        byte_len += sprintf(buffer + byte_len, "  " );
        for( j = 0; j < loop_len; j++ )
        {
            if( IS_CHAR(buf[i*16+j]) )
            {
                byte_len += sprintf(buffer + byte_len, "%1c", buf[(i<<4)+j] );
            }
            else
            {
                byte_len += sprintf(buffer + byte_len, "." );
            }
        }

        for( j = 0; j < ( 16 - loop_len ); j++ )
        {
            byte_len += sprintf(buffer + byte_len, " " );
        }
        byte_len += sprintf(buffer + byte_len, "\r\n" ); 
    }

    return;
}


void signal_set( int signo, void ( *func ) ( int ) )
{
	int                 ret;
	struct sigaction    sig;
	struct sigaction    osig;

	sig.sa_handler = func;
	sigemptyset( &sig.sa_mask );
	sig.sa_flags = 0;
#ifdef SA_RESTART
	sig.sa_flags |= SA_RESTART;
#endif /* SA_RESTART */

	ret = sigaction( signo, &sig, &osig );
}


void pid_exit(int sig)
{
	log_cnm("sig exit %d\n", sig);

    if (-1 != cent_sockfd)
        close(cent_sockfd);

	exit(sig);
}

void connect_down( int sig )
{
	log_cnm("sigpipe recv\n");
	link_ok = 0;
}

void signal_init( void )
{
	signal_set(SIGINT, pid_exit); 
	signal_set(SIGTERM, pid_exit);
	signal_set(SIGTSTP, pid_exit);
	signal_set(SIGPIPE, connect_down);
}

int init_daemon (int nochdir, int noclose)
{
	pid_t pid;

	pid = fork ();

	/* In case of fork is error. */
	if (pid < 0)
	{
		perror ("fork");
		return -1;
	}

	/* In case of this is parent process. */
	if (pid != 0)
	{
		exit (0);
	}

	/* Become session leader and get pid. */
	pid = setsid();

	if (pid < -1)
	{
		perror ("setsid");
		return -1;
	}

	/* Change directory to root. */
	if (! nochdir)
	{
		chdir ("/");
	}

	/* File descriptor close. */
	if (! noclose)
	{
		int fd;

		fd = open("/dev/null",O_RDWR,0);
		if (fd != -1)
		{
			dup2 (fd, STDIN_FILENO);
			dup2 (fd, STDOUT_FILENO);
			dup2 (fd, STDERR_FILENO);
			if (fd > 2)
			{
				close (fd);
			}
		}
	}

	umask (0027);
	return 0;
}

#endif

#if 2
/* client build send data to server, function */
int client_build_item(char *buf,int type,
    int resolve_type, char *data, int data_len)
{
    struct cnm_data *cnm_data = (struct cnm_data *)buf;

	cnm_data->header.type = type;
	cnm_data->header.resolve_type = resolve_type;
	cnm_data->header.reserved = 0;
	cnm_data->header.data_len = data_len;
    strncpy(cnm_data->header.serial, device_serial, sizeof(cnm_data->header.serial));
	
	if (data)
	    memcpy(cnm_data->data, data, data_len);

    return (data_len + sizeof(struct cnm_data_header));
}

int tlv_put_item(char *buf,int type, char *data, int data_len)
{
    CNM_ATTRIBUTE *attr = (CNM_ATTRIBUTE *)buf;

    attr->attribute = type;
    attr->length = data_len + 4;
    memcpy(attr->data, data, data_len);

    return attr->length;
}

#endif

#if 3
int device_serial_init(void)
{
#ifdef DEBUG
    strcpy(device_serial, "0001");
    return 0;
#else
    int len = 0;
    FILE *fp = fopen(YSF_SERIAL_FILE, "r");
    if (!fp)
        return -1;

    fgets(device_serial, sizeof(device_serial)-1, fp);

    fclose(fp);

    len = strlen(device_serial);

    while(--len && (device_serial[len] == '\r' || device_serial[len] == '\n'))
        device_serial[len] = 0;

    if (!device_serial[0])
        return -1;

    log_cnm("device_serial %s\n", device_serial);
        
    return 0;
#endif
}

int upload_size_init(void)
{
    char buffer[256] = {0};
    FILE *fp = fopen(YSF_UPLOAD_SIZE_FILE, "r");
    if (!fp)
    {
        ysf_upload_check_time = time(NULL);
        snprintf(buffer, sizeof(buffer)-1, "echo %u %llu  > %s",
            ysf_upload_check_time, ysf_upload_size, YSF_UPLOAD_SIZE_FILE);
        system(buffer);
        return 0;
    }

    fgets(buffer, sizeof(buffer)-1, fp);

    fclose(fp);

    sscanf(buffer, "%u %llu", &ysf_upload_check_time, &ysf_upload_size);

    log_cnm("ysf_upload_size %llu ysf_upload_check_time %u\n", ysf_upload_size, ysf_upload_check_time);
        
    return 0;
}

void upload_size_update(void)
{
    static time_t last_check_time = 0;
    static unsigned long long last_record_size = 0;
    struct tm* tblock;
	int last_day = 0;
    int update = 0;
    
    time_t now = time(NULL);

    if (last_check_time && last_record_size == ysf_upload_size
        && difftime(now, last_check_time) < 60)
    {
        return;
    }

    if (last_record_size != ysf_upload_size)
    {
        last_record_size = ysf_upload_size;
        update = 1;
    }

    last_check_time = now;
        
	tblock = localtime( &ysf_upload_check_time );
	last_day = tblock->tm_mday;
	
    tblock = localtime( &now );

    if (tblock->tm_mday != last_day)
    {
        ysf_upload_size = 0;
        ysf_upload_overflow = 0;
        cnm_notify_upload_limit_day_warnning = 0;
        ysf_upload_check_time = now;
        last_record_size = 0;
        update = 1;
    }

    if (update)
    {
        FILE *fp = fopen(YSF_UPLOAD_SIZE_FILE, "w+");
        if (fp)
        {
            fprintf(fp, "%u %llu", ysf_upload_check_time, ysf_upload_size);
            fclose(fp);
        }
    }
	
}


int device_params_init(void)
{
#ifdef DEBUG
    strcpy(device_aperture_range, "1.8, 2, 2.8, 4, 5.6, 8, 11, 16, 22");
    strcpy(device_shutter_range, "30, 10, 2, 1, 1/2, 1/10, 1/50, 1/100, 1/1000");
    strcpy(device_iso_range, "100, 200, 400, 800, 1600, 3200");
    return 0;
#else
    char line[1024];
    int len;
    
    FILE *fp = fopen(YSF_PARAMS_FILE, "r");
    if (!fp)
        return -1;

    while(NULL != fgets(line, sizeof(line)-1, fp))
    {
        if (!strcmp(line, "[params]"))
            continue;

        if (line[0] == 'a')
        {
            strncpy(device_aperture_range, &line[4], sizeof(device_aperture_range)-1);
        }
        else if (line[0] == 's')
        {
            strncpy(device_shutter_range, &line[4], sizeof(device_shutter_range)-1);   
        }
        else if (line[0] == 'i')
        {
            strncpy(device_iso_range, &line[6], sizeof(device_iso_range)-1);
        }
    }

    fclose(fp);

    len = strlen(device_aperture_range);

    while(--len && (device_aperture_range[len] == '\r' || device_aperture_range[len] == '\n'))
        device_aperture_range[len] = 0;

    len = strlen(device_shutter_range);

    while(--len && (device_shutter_range[len] == '\r' || device_shutter_range[len] == '\n'))
        device_shutter_range[len] = 0;

    len = strlen(device_iso_range);

    while(--len && (device_iso_range[len] == '\r' || device_iso_range[len] == '\n'))
        device_iso_range[len] = 0;

    log_cnm("device_aperture_range %s\n", 
        device_aperture_range);
    log_cnm("device_shutter_range %s\n", 
        device_shutter_range);
    log_cnm("device_iso_range %s\n", 
        device_iso_range);

    if (!device_aperture_range[0] || !device_shutter_range[0] || !device_iso_range[0])
        return -1;
    
    return 0;
#endif
}
#endif

#if 4
int send_update_response_to_server(void)
{
	char send_buf[128] = { 0 };
    struct cnm_packet *send_pkt = (struct cnm_packet *)send_buf;
    int send_len = 0;

    if (!link_ok)
    {
        log_cnm("%s %d link status error.\n", __FUNCTION__, __LINE__);
        return -1;
    }
	
    send_pkt->total_len += client_build_item(send_pkt->data, CNM_UPDATE_SOFTWARE_RESPONSE,
        CNM_RESOLVE_TYPE_BINARY, NULL, 0);  

    send_len = send_pkt->total_len + sizeof(send_pkt->total_len);

    pthread_mutex_lock(&cent_sockfd_mutex);
    if(send_len != send(cent_sockfd, (void *)send_buf, send_len, 0))
    {
        log_cnm("%s %d send to server error. ERROR:%s\n", __FUNCTION__, __LINE__, strerror(errno));
        link_ok = 0;
        pthread_mutex_unlock(&cent_sockfd_mutex);
		return -1;
    }
    pthread_mutex_unlock(&cent_sockfd_mutex);
    
	return 0;
}

int send_update_action_to_server(void)
{
	char send_buf[128] = { 0 };
    struct cnm_packet *send_pkt = (struct cnm_packet *)send_buf;
    int send_len = 0;

    if (!link_ok)
    {
        log_cnm("%s %d link status error.\n", __FUNCTION__, __LINE__);
        return -1;
    }
	
    send_pkt->total_len += client_build_item(send_pkt->data, CNM_UPDATE_SOFTWARE_ACTION,
        CNM_RESOLVE_TYPE_BINARY, NULL, 0);  

    send_len = send_pkt->total_len + sizeof(send_pkt->total_len);

    pthread_mutex_lock(&cent_sockfd_mutex);
    if(send_len != send(cent_sockfd, (void *)send_buf, send_len, 0))
    {
        log_cnm("%s %d send to server error. ERROR:%s\n", __FUNCTION__, __LINE__, strerror(errno));
        link_ok = 0;
        pthread_mutex_unlock(&cent_sockfd_mutex);
		return -1;
    }

    pthread_mutex_unlock(&cent_sockfd_mutex);
	return 0;
}

void *update_software(void *arg)
{
    struct cnm_update_item_data *update_date = (struct cnm_update_item_data*)arg;

    ysf_system(__FUNCTION__, __LINE__, "rm -rf /tmp/firmware.tar.gz");

	while(0 != send_update_response_to_server())
	{
		sleep(1);
	}
    
    log_cnm("auto update begin cmd %s.\n",update_date->update_cmd);

    //ftpget software
    ysf_system(__FUNCTION__, __LINE__, update_date->update_cmd);

	while(0 != send_update_action_to_server())
	{
		sleep(1);
	}

    ysf_system(__FUNCTION__, __LINE__, "mkdir -p /home/ysf/fimware");
    ysf_system(__FUNCTION__, __LINE__, "tar zxf /tmp/firmware.tar.gz -C /home/ysf/fimware/");
    ysf_system(__FUNCTION__, __LINE__, "/home/ysf/fimware/update.sh");

    free(arg);
    return NULL;
}

int update_software_action(char *cmd)
{
    pthread_t tid;

    struct cnm_update_item_data *update_date = (struct cnm_update_item_data *)malloc(sizeof(struct cnm_update_item_data));

    if (!update_date)
        return -1;

    memcpy(update_date, cmd, sizeof(struct cnm_update_item_data));
    
    if (0 != pthread_create(&tid, NULL, update_software, (void *)update_date))
    {
        log_cnm("%s %d create update_software thread error.\n", __FUNCTION__, __LINE__);
        return -1;
    }

    return 0;
}
#endif

#if 5
int send_limit_sync_response_to_server(void)
{
	char send_buf[128] = { 0 };
    struct cnm_packet *send_pkt = (struct cnm_packet *)send_buf;
    int send_len = 0;

    if (!link_ok)
    {
        log_cnm("%s %d link status error.\n", __FUNCTION__, __LINE__);
        return -1;
    }
	
    send_pkt->total_len += client_build_item(send_pkt->data, CNM_LIMIT_SYNC_ACTION,
        CNM_RESOLVE_TYPE_BINARY, NULL, 0);  

    send_len = send_pkt->total_len + sizeof(send_pkt->total_len);

    pthread_mutex_lock(&cent_sockfd_mutex);
    if(send_len != send(cent_sockfd, (void *)send_buf, send_len, 0))
    {
        log_cnm("%s %d send to server error. ERROR:%s\n", __FUNCTION__, __LINE__, strerror(errno));
        link_ok = 0;
        pthread_mutex_unlock(&cent_sockfd_mutex);
		return -1;
    }
    pthread_mutex_unlock(&cent_sockfd_mutex);
    
	return 0;
}


void *sync_limit(void *arg)
{
    struct cnm_sync_item_data *update_date = (struct cnm_sync_item_data*)arg;
    char *cmd = NULL;

    cmd = update_date->sync_cmd;

    ysf_system(__FUNCTION__, __LINE__, "rm -rf /tmp/.limit.ini");

    ysf_system(__FUNCTION__, __LINE__, cmd);
    
    if (0 == access("/tmp/.limit.ini", 0))
    {
        ysf_system(__FUNCTION__, __LINE__, "mv -f /tmp/.limit.ini /home/ysf/.camera/limit.ini");
        ysf_notify_limit_success = 0;
        ysf_notify_limit();
    }

    free(arg);
    return NULL;
}

int sync_limit_action(char *cmd)
{
    pthread_t tid;

    struct cnm_sync_item_data *item = (struct cnm_sync_item_data *)malloc(sizeof(struct cnm_sync_item_data));
    if (!item)
        return -1;

    memcpy(item, cmd, sizeof(struct cnm_sync_item_data));
    
    if (0 != pthread_create(&tid, NULL, sync_limit, (void *)item))
    {
        log_cnm("%s %d create sync_limit thread error.\n", __FUNCTION__, __LINE__);
        return -1;
    }

    return 0;
}
#endif

#if 6
int send_tasks_sync_response_to_server(void)
{
	char send_buf[128] = { 0 };
    struct cnm_packet *send_pkt = (struct cnm_packet *)send_buf;
    int send_len = 0;

    if (!link_ok)
    {
        log_cnm("%s %d link status error.\n", __FUNCTION__, __LINE__);
        return -1;
    }
	
    send_pkt->total_len += client_build_item(send_pkt->data, CNM_TASKS_SYNC_ACTION,
        CNM_RESOLVE_TYPE_BINARY, NULL, 0);  

    send_len = send_pkt->total_len + sizeof(send_pkt->total_len);

    pthread_mutex_lock(&cent_sockfd_mutex);
    if(send_len != send(cent_sockfd, (void *)send_buf, send_len, 0))
    {
        log_cnm("%s %d send to server error. ERROR:%s\n", __FUNCTION__, __LINE__, strerror(errno));
        link_ok = 0;
        pthread_mutex_unlock(&cent_sockfd_mutex);
		return -1;
    }
    pthread_mutex_unlock(&cent_sockfd_mutex);
    
	return 0;
}


void *sync_tasks(void *arg)
{
    struct cnm_sync_item_data *update_date = (struct cnm_sync_item_data*)arg;
    char *cmd = NULL;

    cmd = update_date->sync_cmd;

    ysf_system(__FUNCTION__, __LINE__, "rm -rf /tmp/.plan.csv");

    ysf_system(__FUNCTION__, __LINE__, cmd);

    if (0 == access("/tmp/.plan.csv", 0))
    {
        ysf_system(__FUNCTION__, __LINE__, "mv -f /tmp/.plan.csv /home/ysf/.camera/plan.csv");

        ysf_notify_plan_success = 0;
        
        ysf_notify_plan();
    }

    free(arg);
    return NULL;
}

int sync_tasks_action(char *cmd)
{
    pthread_t tid;

    struct cnm_sync_item_data *item = (struct cnm_sync_item_data *)malloc(sizeof(struct cnm_sync_item_data));
    if (!item)
        return -1;

    memcpy(item, cmd, sizeof(struct cnm_sync_item_data));
    
    if (0 != pthread_create(&tid, NULL, sync_tasks, (void *)item))
    {
        log_cnm("%s %d create tasks_limit thread error.\n", __FUNCTION__, __LINE__);
        return -1;
    }

    return 0;
}
#endif

#if 7
int send_base_sync_response_to_server(void)
{
	char send_buf[128] = { 0 };
    struct cnm_packet *send_pkt = (struct cnm_packet *)send_buf;
    int send_len = 0;

    if (!link_ok)
    {
        log_cnm("%s %d link status error.\n", __FUNCTION__, __LINE__);
        return -1;
    }
	
    send_pkt->total_len += client_build_item(send_pkt->data, CNM_BASE_SYNC_ACTION,
        CNM_RESOLVE_TYPE_BINARY, NULL, 0);  

    send_len = send_pkt->total_len + sizeof(send_pkt->total_len);

    pthread_mutex_lock(&cent_sockfd_mutex);
    if(send_len != send(cent_sockfd, (void *)send_buf, send_len, 0))
    {
        log_cnm("%s %d send to server error. ERROR:%s\n", __FUNCTION__, __LINE__, strerror(errno));
        link_ok = 0;
        pthread_mutex_unlock(&cent_sockfd_mutex);
		return -1;
    }
    pthread_mutex_unlock(&cent_sockfd_mutex);
    
	return 0;
}


void *base_tasks(void *arg)
{
    struct cnm_sync_item_data *update_date = (struct cnm_sync_item_data*)arg;
    char *cmd = NULL;

    cmd = update_date->sync_cmd;

    ysf_system(__FUNCTION__, __LINE__, "rm -rf /tmp/.reference.ini");

    ysf_system(__FUNCTION__, __LINE__, cmd);

    if (0 == access("/tmp/.reference.ini", 0))
    {
        ysf_system(__FUNCTION__, __LINE__, "mv -f /tmp/.reference.ini /home/ysf/.camera/reference.ini");

        ysf_notify_base_success = 0;
        
        ysf_notify_base();
    }
    
    free(arg);
    return NULL;
}

int sync_base_action(char *cmd)
{
    pthread_t tid;

    struct cnm_sync_item_data *item = (struct cnm_sync_item_data *)malloc(sizeof(struct cnm_sync_item_data));
    if (!item)
        return -1;

    memcpy(item, cmd, sizeof(struct cnm_sync_item_data));
    
    if (0 != pthread_create(&tid, NULL, base_tasks, (void *)item))
    {
        log_cnm("%s %d create tasks_limit thread error.\n", __FUNCTION__, __LINE__);
        return -1;
    }

    return 0;
}
#endif

#if 8
int send_preview_sync_response_to_server(void)
{
	char send_buf[128] = { 0 };
    struct cnm_packet *send_pkt = (struct cnm_packet *)send_buf;
    int send_len = 0;

    if (!link_ok)
    {
        log_cnm("%s %d link status error.\n", __FUNCTION__, __LINE__);
        return -1;
    }
	
    send_pkt->total_len += client_build_item(send_pkt->data, CNM_PREVIEW_SYNC_RESPONSE,
        CNM_RESOLVE_TYPE_BINARY, NULL, 0);  

    send_len = send_pkt->total_len + sizeof(send_pkt->total_len);

    pthread_mutex_lock(&cent_sockfd_mutex);
    if(send_len != send(cent_sockfd, (void *)send_buf, send_len, 0))
    {
        log_cnm("%s %d send to server error. ERROR:%s\n", __FUNCTION__, __LINE__, strerror(errno));
        link_ok = 0;
        pthread_mutex_unlock(&cent_sockfd_mutex);
		return -1;
    }
    pthread_mutex_unlock(&cent_sockfd_mutex);
    
	return 0;
}

int send_preview_finish_to_server(void)
{
	char send_buf[128] = { 0 };
    struct cnm_packet *send_pkt = (struct cnm_packet *)send_buf;
    int send_len = 0;

    if (cnm_notify_preview_success)
        return 0;

    if (!link_ok)
    {
        log_cnm("%s %d link status error.\n", __FUNCTION__, __LINE__);
        return -1;
    }
	
    send_pkt->total_len += client_build_item(send_pkt->data, CNM_PREVIEW_SYNC_ACTION,
        CNM_RESOLVE_TYPE_BINARY, NULL, 0);  

    send_len = send_pkt->total_len + sizeof(send_pkt->total_len);

    pthread_mutex_lock(&cent_sockfd_mutex);
    if(send_len != send(cent_sockfd, (void *)send_buf, send_len, 0))
    {
        log_cnm("%s %d send to server error. ERROR:%s\n", __FUNCTION__, __LINE__, strerror(errno));
        link_ok = 0;
        pthread_mutex_unlock(&cent_sockfd_mutex);
		return -1;
    }
    pthread_mutex_unlock(&cent_sockfd_mutex);
    
	return 0;
}


int send_upload_warnning_to_server(void)
{
	char send_buf[128] = { 0 };
    struct cnm_packet *send_pkt = (struct cnm_packet *)send_buf;
    int send_len = 0;

    if (!cnm_notify_upload_limit_day_warnning)
        return 0;

    if (!link_ok)
    {
        log_cnm("%s %d link status error.\n", __FUNCTION__, __LINE__);
        return -1;
    }
	
    send_pkt->total_len += client_build_item(send_pkt->data, CNM_UPLOAD_LIMIT_DAY_WARNNING_NOTIFY,
        CNM_RESOLVE_TYPE_BINARY, NULL, 0);  

    send_len = send_pkt->total_len + sizeof(send_pkt->total_len);

    pthread_mutex_lock(&cent_sockfd_mutex);
    if(send_len != send(cent_sockfd, (void *)send_buf, send_len, 0))
    {
        log_cnm("%s %d send to server error. ERROR:%s\n", __FUNCTION__, __LINE__, strerror(errno));
        link_ok = 0;
        pthread_mutex_unlock(&cent_sockfd_mutex);
		return -1;
    }
    pthread_mutex_unlock(&cent_sockfd_mutex);
    
	return 0;
}


int send_conf_request_to_server(void)
{
	char send_buf[128] = { 0 };
    struct cnm_packet *send_pkt = (struct cnm_packet *)send_buf;
    int send_len = 0;

    if (cnm_conf_init_success)
        return 0;

    if (!link_ok)
    {
        log_cnm("%s %d link status error.\n", __FUNCTION__, __LINE__);
        return -1;
    }
	
    send_pkt->total_len += client_build_item(send_pkt->data, CNM_CONF_REQUSET,
        CNM_RESOLVE_TYPE_BINARY, NULL, 0);  

    send_len = send_pkt->total_len + sizeof(send_pkt->total_len);

    pthread_mutex_lock(&cent_sockfd_mutex);
    if(send_len != send(cent_sockfd, (void *)send_buf, send_len, 0))
    {
        log_cnm("%s %d send to server error. ERROR:%s\n", __FUNCTION__, __LINE__, strerror(errno));
        link_ok = 0;
        pthread_mutex_unlock(&cent_sockfd_mutex);
		return -1;
    }
    pthread_mutex_unlock(&cent_sockfd_mutex);
    
	return 0;
}


int send_bwlimit_notify_finish_to_server(void)
{
	char send_buf[128] = { 0 };
    struct cnm_packet *send_pkt = (struct cnm_packet *)send_buf;
    int send_len = 0;

    if (!link_ok)
    {
        log_cnm("%s %d link status error.\n", __FUNCTION__, __LINE__);
        return -1;
    }
	
    send_pkt->total_len += client_build_item(send_pkt->data, CNM_BWLIMIT_SYNC_RESPONSE,
        CNM_RESOLVE_TYPE_BINARY, NULL, 0);  

    send_len = send_pkt->total_len + sizeof(send_pkt->total_len);

    pthread_mutex_lock(&cent_sockfd_mutex);
    if(send_len != send(cent_sockfd, (void *)send_buf, send_len, 0))
    {
        log_cnm("%s %d send to server error. ERROR:%s\n", __FUNCTION__, __LINE__, strerror(errno));
        link_ok = 0;
        pthread_mutex_unlock(&cent_sockfd_mutex);
		return -1;
    }
    pthread_mutex_unlock(&cent_sockfd_mutex);
    
	return 0;
}

int send_upload_limit_day_notify_finish_to_server(void)
{
	char send_buf[128] = { 0 };
    struct cnm_packet *send_pkt = (struct cnm_packet *)send_buf;
    int send_len = 0;

    if (!link_ok)
    {
        log_cnm("%s %d link status error.\n", __FUNCTION__, __LINE__);
        return -1;
    }
	
    send_pkt->total_len += client_build_item(send_pkt->data, CNM_UPLOAD_LIMIT_DAY_SYNC_RESPONSE,
        CNM_RESOLVE_TYPE_BINARY, NULL, 0);  

    send_len = send_pkt->total_len + sizeof(send_pkt->total_len);

    pthread_mutex_lock(&cent_sockfd_mutex);
    if(send_len != send(cent_sockfd, (void *)send_buf, send_len, 0))
    {
        log_cnm("%s %d send to server error. ERROR:%s\n", __FUNCTION__, __LINE__, strerror(errno));
        link_ok = 0;
        pthread_mutex_unlock(&cent_sockfd_mutex);
		return -1;
    }
    pthread_mutex_unlock(&cent_sockfd_mutex);
    
	return 0;
}


void *preview_tasks(void *arg)
{
    struct cnm_sync_item_data *update_date = (struct cnm_sync_item_data*)arg;
    char *cmd = NULL;
    char cmd2[1024] = { 0 };

    cmd = update_date->sync_cmd;

    ysf_system(__FUNCTION__, __LINE__, "rm -rf /tmp/.preview.ini");

    ysf_system(__FUNCTION__, __LINE__, cmd);

    if (0 == access("/tmp/.preview.ini", 0))
    {
        ysf_system(__FUNCTION__, __LINE__, "mv -f /tmp/.preview.ini /home/ysf/.camera/preview.ini");

        ysf_notify_preview_success = 0;
        
        ysf_notify_preview();
    }

    ysf_notify_preview_response = 0;
    while(!ysf_notify_preview_response)
        sleep(1);

    snprintf(cmd2, sizeof(cmd2)-1, "/usr/bin/sshpass -p %s rsync -auvzP "
        " -e \"ssh -p 22 -o "
        "StrictHostKeyChecking=no\" /tmp/preview.jpeg "
        "root@%s:/%s/%s/preview/", 
        server_password, 
        SERVER_DOMAIN, CNM_SERVER_PHOTOS_PATH, 
        device_serial);

    if (0 == ysf_system(__FUNCTION__, __LINE__, cmd2))
    {
        cnm_notify_preview_success = 0;
        send_preview_finish_to_server();
    }

    free(arg);
    return NULL;
}

int sync_preview_action(char *cmd)
{
    pthread_t tid;

    struct cnm_sync_item_data *item = (struct cnm_sync_item_data *)malloc(sizeof(struct cnm_sync_item_data));
    if (!item)
        return -1;

    memcpy(item, cmd, sizeof(struct cnm_sync_item_data));
    
    if (0 != pthread_create(&tid, NULL, preview_tasks, (void *)item))
    {
        log_cnm("%s %d create preview_tasks thread error.\n", __FUNCTION__, __LINE__);
        return -1;
    }

    return 0;
}

#endif

#if 96
void ysf_request_status(void)
{
    char send_buf[128] = { 0 };
    int send_len = sizeof(struct ysf_msg_header_t);
    struct ysf_msg_header_t *msg_header = (struct ysf_msg_header_t *)send_buf;
    
    if (!ysf_link_ok)
        return;

    msg_header->start_flag = 0x68;
    msg_header->code = 0x06;
    msg_header->reason = 0x01;
    msg_header->length = sizeof(struct ysf_msg_header_t) - offsetof(struct ysf_msg_header_t, length);

    pthread_mutex_lock(&ysf_sockfd_mutex);
    if(send_len != send(ysf_sockfd, (void *)send_buf, send_len, 0))
    {
        log_cnm("%s %d send to server error. ERROR:%s\n", __FUNCTION__, __LINE__, strerror(errno));
        ysf_link_ok = 0;
    }

    pthread_mutex_unlock(&ysf_sockfd_mutex);
}

void ysf_notify_preview(void)
{
    static time_t last_send_time;
    
    char send_buf[128] = { 0 };
    int send_len = sizeof(struct ysf_msg_header_t);
    struct ysf_msg_header_t *msg_header = (struct ysf_msg_header_t *)send_buf;

    if (ysf_notify_preview_success)
        return;
    
    if (!ysf_link_ok)
        return;

    if (difftime(time(NULL), last_send_time) < 10)
        return;

    msg_header->start_flag = 0x68;
    msg_header->code = 03;
    msg_header->reason = 01;
    msg_header->length = sizeof(struct ysf_msg_header_t) - offsetof(struct ysf_msg_header_t, length);

    pthread_mutex_lock(&ysf_sockfd_mutex);
    if(send_len != send(ysf_sockfd, (void *)send_buf, send_len, 0))
    {
        log_cnm("%s %d send to server error. ERROR:%s\n", __FUNCTION__, __LINE__, strerror(errno));
        ysf_link_ok = 0;
    }
    else
    {
        last_send_time = time(NULL);
    }
    pthread_mutex_unlock(&ysf_sockfd_mutex);
}


void ysf_notify_base(void)
{
    static time_t last_send_time;
    
    char send_buf[128] = { 0 };
    int send_len = sizeof(struct ysf_msg_header_t);
    struct ysf_msg_header_t *msg_header = (struct ysf_msg_header_t *)send_buf;

    if (ysf_notify_base_success)
        return;
    
    if (!ysf_link_ok)
        return;

    if (difftime(time(NULL), last_send_time) < 10)
        return;

    msg_header->start_flag = 0x68;
    msg_header->code = 04;
    msg_header->reason = 02;
    msg_header->length = sizeof(struct ysf_msg_header_t) - offsetof(struct ysf_msg_header_t, length);

    pthread_mutex_lock(&ysf_sockfd_mutex);
    if(send_len != send(ysf_sockfd, (void *)send_buf, send_len, 0))
    {
        log_cnm("%s %d send to server error. ERROR:%s\n", __FUNCTION__, __LINE__, strerror(errno));
        ysf_link_ok = 0;
    }
    else
    {
        last_send_time = time(NULL);
    }
    pthread_mutex_unlock(&ysf_sockfd_mutex);
}


void ysf_notify_plan(void)
{
    static time_t last_send_time;
    
    char send_buf[128] = { 0 };
    int send_len = sizeof(struct ysf_msg_header_t);
    struct ysf_msg_header_t *msg_header = (struct ysf_msg_header_t *)send_buf;

    if (ysf_notify_plan_success)
        return;
    
    if (!ysf_link_ok)
        return;

    if (difftime(time(NULL), last_send_time) < 10)
        return;

    msg_header->start_flag = 0x68;
    msg_header->code = 01;
    msg_header->reason = 02;
    msg_header->length = sizeof(struct ysf_msg_header_t) - offsetof(struct ysf_msg_header_t, length);

    pthread_mutex_lock(&ysf_sockfd_mutex);
    if(send_len != send(ysf_sockfd, (void *)send_buf, send_len, 0))
    {
        log_cnm("%s %d send to server error. ERROR:%s\n", __FUNCTION__, __LINE__, strerror(errno));
        ysf_link_ok = 0;
    }
    else
    {
        last_send_time = time(NULL);
    }
    pthread_mutex_unlock(&ysf_sockfd_mutex);
}

void ysf_notify_status(void)
{
    static time_t last_send_time;
    int send_len = 0;
    
    char send_buf[128] = { 0 };
    struct ysf_msg_header_t *msg_header = (struct ysf_msg_header_t *)send_buf;

    if (ysf_notify_status_success)
        return;
    
    if (!ysf_link_ok)
        return;

    if (difftime(time(NULL), last_send_time) < 10)
        return;

    msg_header->start_flag = 0x68;
    msg_header->code = 05;
    msg_header->reason = 02;
    msg_header->length = sizeof(struct ysf_msg_header_t) - offsetof(struct ysf_msg_header_t, length) + 1;
    msg_header->data[0] = ysf_status;

    send_len = msg_header->length + sizeof(msg_header->start_flag);
    
    pthread_mutex_lock(&ysf_sockfd_mutex);
    if(send_len != send(ysf_sockfd, (void *)send_buf, send_len, 0))
    {
        log_cnm("%s %d send to server error. ERROR:%s\n", __FUNCTION__, __LINE__, strerror(errno));
        ysf_link_ok = 0;
    }
    else
    {
        last_send_time = time(NULL);
    }
    pthread_mutex_unlock(&ysf_sockfd_mutex);
}


void ysf_notify_limit(void)
{
    static time_t last_send_time;
    
    char send_buf[128] = { 0 };
    int send_len = sizeof(struct ysf_msg_header_t);
    struct ysf_msg_header_t *msg_header = (struct ysf_msg_header_t *)send_buf;

    if (ysf_notify_limit_success)
        return;
    
    if (!ysf_link_ok)
        return;

    if (difftime(time(NULL), last_send_time) < 10)
        return;

    msg_header->start_flag = 0x68;
    msg_header->code = 02;
    msg_header->reason = 02;
    msg_header->length = sizeof(struct ysf_msg_header_t) - offsetof(struct ysf_msg_header_t, length);

    pthread_mutex_lock(&ysf_sockfd_mutex);
    if(send_len != send(ysf_sockfd, (void *)send_buf, send_len, 0))
    {
        log_cnm("%s %d send to server error. ERROR:%s\n", __FUNCTION__, __LINE__, strerror(errno));
        ysf_link_ok = 0;
    }
    else
    {
        last_send_time = time(NULL);
    }
    pthread_mutex_unlock(&ysf_sockfd_mutex);
}

void ysf_data_handle(struct ysf_msg_header_t *hdr, char *data, int len)
{
    //log_cnm("%s %d code %02x reason %02x\n",
    //    __FUNCTION__, __LINE__, hdr->code, hdr->reason);
    
    switch (hdr->code)
    {
    case 0x01:
        if (hdr->reason == 0x03)
        {
            ysf_notify_plan_success = 1;
            send_tasks_sync_response_to_server();
        }
        break;
    case 0x02:
        if (hdr->reason == 0x03)
        {
            ysf_notify_limit_success = 1;
            send_limit_sync_response_to_server();
        }
        break;
    case 0x03:
        if (hdr->reason == 0x03)
        {
            ysf_notify_preview_success = 1;
            send_preview_sync_response_to_server();
        }
        else if (hdr->reason == 0x05)
        {
            ysf_notify_preview_response = 1;
        }
        break;
    case 0x04:
        if (hdr->reason == 0x03)
        {
            ysf_notify_base_success = 1;
            send_base_sync_response_to_server();
        }
        break;
    case 0x05:
        {
            ysf_notify_status_success = 1;
        }
        break;
    case 0x06:
        if (hdr->reason == 0x03)
        {
            client_timer_heardbeat(data);
        }
        break;
    default:
        log_cnm("%s %d wrong data type:%d.\n", __FUNCTION__, __LINE__, hdr->code);
        return;
    }
}

/* client socket receive data from server, thread */
void *ysf_recv_data(void *arg)
{
    int fd = -1;
    int ret;
    int recv_err_count = 0;
    struct timeval tv;
	struct sockaddr_in server_addr;
    int data_len = 0;

    struct ysf_msg_header_t msg_header;

	fd_set rset;
	char data[2048] = { 0 };

create_sock:

    if (-1 != fd)
    {
        log_cnm("%s %d fd %d error.\n", __FUNCTION__, __LINE__, fd);
        sleep(5);
        close(fd);
        recv_err_count = 0;
        fd = -1;
        ysf_sockfd = -1;
    }

    if (-1 == (fd = socket(PF_INET, SOCK_STREAM, 0)))
    {
        log_cnm("%s %d create socket error. ERROR:%s\n", __FUNCTION__, __LINE__, strerror(errno));
        sleep(5);
        goto create_sock;
    }

    tv.tv_sec = 6;
    tv.tv_usec = 0;
    if (-1 == setsockopt(fd, SOL_SOCKET, SO_SNDTIMEO, (char *)&tv, sizeof(tv)))
    {
        log_cnm("%s %d set socket %d timeout option error. ERROR:%s\n", __FUNCTION__, __LINE__, fd, strerror(errno));
        goto create_sock;
    }

    memset(&server_addr, 0, sizeof(server_addr));
    server_addr.sin_family = AF_INET;
    server_addr.sin_port = htons(8000);
    server_addr.sin_addr.s_addr = 0x0100007f;

    if (-1 == connect(fd, (struct sockaddr *)&server_addr, sizeof(struct sockaddr)))
    {
        log_cnm("%s %d connect socket %d ip 0x%x port %d error. ERROR:%s\n", __FUNCTION__, __LINE__, fd, ntohl(server_addr.sin_addr.s_addr), ntohs(server_addr.sin_port), strerror(errno));
        goto create_sock;
    }

    ysf_sockfd = fd;
    ysf_link_ok = 1;

    while (ysf_link_ok)
    {
		if (recv_err_count > 30)
        {
            log_cnm("%s %d select socket %d error.\n", __FUNCTION__, __LINE__, fd);
            ysf_link_ok = 0;
            goto create_sock;
        }

		tv.tv_sec = 1;
		tv.tv_usec = 0;
        FD_ZERO(&rset);
        FD_SET(ysf_sockfd, &rset);

        ret = select(ysf_sockfd + 1, &rset, NULL, NULL, &tv);
        if (ret < 0)
        {
            log_cnm("%s %d select socket %d error. ERROR:%s\n", __FUNCTION__, __LINE__, fd, strerror(errno));
            ysf_link_ok = 0;
            goto create_sock;
        }

        if (ret == 0)
        {
            recv_err_count++;
            continue;
        }
        
        recv_err_count = 0;

        memset(&msg_header, 0, sizeof(msg_header));

        ret = recv(ysf_sockfd, (char *)&msg_header, sizeof(msg_header), MSG_WAITALL);
        if (ret != sizeof(msg_header))
        {
            log_cnm("%s %d recv socket ret %d error. ERROR:%s\n", __FUNCTION__, __LINE__, ret, strerror(errno));
            ysf_link_ok = 0;
            goto create_sock;
        }

        if (0)
        {
            char buffer[65535] = { 0 };
            print_hex2string((char *)&msg_header, sizeof(msg_header), buffer);
            log_cnm("%s %d\n%s\n", __FUNCTION__, __LINE__, buffer);
        }

        if (msg_header.start_flag != 0x68)
        {
            ysf_link_ok = 0;
            goto create_sock;
        }

        data_len = (unsigned int)msg_header.length - (sizeof(msg_header) - offsetof(struct ysf_msg_header_t, length));
        
        if (data_len < 0 || data_len > 256)
        {
            ysf_link_ok = 0;
            goto create_sock;
        }

        if (data_len != 0)
        {
            memset(&data, 0, sizeof(data));

            ret = recv(ysf_sockfd, (char *)&data, data_len, MSG_WAITALL);
            if (ret <= 0 || ret != data_len)
            {
                log_cnm("%s %d recv socket ret %d len %d error. ERROR:%s\n", __FUNCTION__, __LINE__, ret, data_len, strerror(errno));
                ysf_link_ok = 0;
                goto create_sock;
            }

            if (0)
            {
                char buffer[65535] = { 0 };
                print_hex2string(data, data_len, buffer);
                log_cnm("%s %d\n%s\n", __FUNCTION__, __LINE__, buffer);
            }
        }

        ysf_data_handle(&msg_header, data, data_len);
    }

    goto create_sock;
}


void *ysf_rsync_photo(void *arg)
{
    while(1)
    {
        int count = 0;
        do
        {
            char cipherFile[256] = {0};
        	char ciperOkFile[256] = {0};
        	char cmd[1024] = {0};
        	DIR *dp;
        	struct dirent *entry;

            if (ysf_upload_status || ysf_upload_overflow)
            {
                continue;
                sleep(1);
            }

            dp = opendir(CNM_CLIENT_PHOTOS_PATH);
        	if(!dp)
        		break;
        	while((entry = readdir(dp)) != NULL) 
        	{
        		if('.' == entry->d_name[0])
        			continue;

                snprintf(cipherFile, 255, "%s/%s", CNM_CLIENT_PHOTOS_PATH, entry->d_name);

                snprintf(cmd, sizeof(cmd)-1, "/usr/bin/sshpass -p %s rsync -auvzP "
                    "%s -e \"ssh -p 22 -o "
                    "StrictHostKeyChecking=no\" %s "
                    "root@%s:/%s/%s/raw/", 
                    server_password, 
                    bwlimit_conf,
                    cipherFile, 
                    SERVER_DOMAIN, CNM_SERVER_PHOTOS_PATH, 
                    device_serial);

                log_cnm("%s %d %s\n", __FUNCTION__, __LINE__,cmd);

                if (0 == ysf_system(__FUNCTION__, __LINE__, cmd))
                {
                    snprintf(ciperOkFile, 255, "%s/%s.ok", CNM_CLIENT_PHOTOS_PATH, entry->d_name);
        			snprintf(cmd, 255, "touch %s", ciperOkFile);

                    if (0 == ysf_system(__FUNCTION__, __LINE__, cmd))
                    {
                        snprintf(cmd, sizeof(cmd)-1, "/usr/bin/sshpass -p %s rsync -auvzP "
                            "-e \"ssh -p 22 -o "
                            "StrictHostKeyChecking=no\" "
                            "%s root@%s:/%s/%s/raw/", 
                            server_password, ciperOkFile,
                            SERVER_DOMAIN, CNM_SERVER_PHOTOS_PATH, 
                            device_serial);
                    
                        if (0 == ysf_system(__FUNCTION__, __LINE__, cmd))
                        {
                            struct stat st ;     
                            stat(cipherFile, &st);

                            ysf_upload_size += st.st_size;

                            //log_cnm("%s %d %s %llu\n", __FUNCTION__, __LINE__,cipherFile, ysf_upload_size);

                            if (ysf_upload_limit_day > 0)
                            {
                                if ((ysf_upload_size / 1024 / 1024) > ysf_upload_limit_day)
                                {
                                    ysf_upload_overflow = 1;
                                    cnm_notify_upload_limit_day_warnning = 1;
                                }
                            }
                            
                            remove(ciperOkFile);
                            remove(cipherFile);
                        }

                        count++;
                    }
                }
    
        		if (ysf_upload_status || ysf_upload_overflow)
                    break;
        	}
        	closedir(dp);
        }while(0);

        if (count == 0)
            sleep(1);
    }

    return NULL;
}

#endif

#if 97
void recv_data_handle(char *data, int len)
{
    struct cnm_data_header *data_header;
    unsigned int size = 0;

    data_header = (struct cnm_data_header *)data;

    if (strcmp(data_header->serial, device_serial))
        return;

    while ((size + sizeof(struct cnm_data_header)) <= len)
    {
        //log_cnm("%s %d type %d\n", __FUNCTION__, __LINE__, data_header->type);
        
        switch (data_header->type)
        {
        case CNM_CLIENT_MSG_RESPONSE:
            break;

        case CNM_UPDATE_SOFTWARE_REQUEST:
            update_software_action((char *)data_header + sizeof(*data_header));
            break;

        case CNM_TASKS_SYNC_REQUEST:
            sync_tasks_action((char *)data_header + sizeof(*data_header));
            break;

        case CNM_LIMIT_SYNC_REQUEST:
            sync_limit_action((char *)data_header + sizeof(*data_header));
            break;

        case CNM_UPDATE_STATUS_ACTION:
            {
                ysf_status = *(int *)((char *)data_header + sizeof(*data_header));
                ysf_notify_status_success = 0;
                ysf_notify_status();
            }
            break;

        case CNM_UPLOAD_STATUS_ACTION:
            {
                ysf_upload_status = *(int *)((char *)data_header + sizeof(*data_header));

                log_cnm("%s %d ysf_upload_status %d\n",
                    __FUNCTION__, __LINE__, ysf_upload_status);
            }
            break;
        case CNM_UPLOAD_LIMIT_DAY_WARNNING_RESPONSE:
            {
                cnm_notify_upload_limit_day_warnning = 0;
            }
            break;

        case CNM_BASE_SYNC_REQUEST:
            sync_base_action((char *)data_header + sizeof(*data_header));
            break;

        case CNM_PREVIEW_SYNC_REQUEST:
            sync_preview_action((char *)data_header + sizeof(*data_header));
            break;

        case CNM_BWLIMIT_SYNC_NOTIFY:
            {
                struct cnm_bwlimit_item_data *tmp = (struct cnm_bwlimit_item_data *)((char *)data_header + sizeof(*data_header));

                memset(bwlimit_conf, 0, sizeof(bwlimit_conf));
                if (tmp->bwlimit)
                {
                    snprintf(bwlimit_conf, sizeof(bwlimit_conf)-1,
                        "--bwlimit=%d", tmp->bwlimit);
                }

                send_bwlimit_notify_finish_to_server();
            }
            break;

        case CNM_UPLOAD_LIMIT_DAY_SYNC_NOTIFY:
            {
                struct cnm_upload_limit_day_item_data *tmp = (struct cnm_upload_limit_day_item_data *)((char *)data_header + sizeof(*data_header));

                ysf_upload_limit_day = tmp->upload_limit_day;
                
                send_upload_limit_day_notify_finish_to_server();

                if (!ysf_upload_limit_day || ((ysf_upload_size / 1024 / 1024) < ysf_upload_limit_day))
                {
                    ysf_upload_overflow = 0;
                    cnm_notify_upload_limit_day_warnning = 0;
                }

                log_cnm("%s %d ysf_upload_limit_day %d ysf_upload_size %llu overflow %d warnning %d\n",
                    __FUNCTION__, __LINE__, ysf_upload_limit_day, ysf_upload_size,
                    ysf_upload_overflow, cnm_notify_upload_limit_day_warnning);
            }
            break;

        case CNM_CONF_REQUSET_RESPONSE:
            {
                struct cnm_conf_item_data *tmp = (struct cnm_conf_item_data *)((char *)data_header + sizeof(*data_header));

                memset(bwlimit_conf, 0, sizeof(bwlimit_conf));
                if (tmp->bwlimit)
                {
                    snprintf(bwlimit_conf, sizeof(bwlimit_conf)-1,
                        "--bwlimit=%d", tmp->bwlimit);
                }

                ysf_upload_status = tmp->upload_status;
                ysf_upload_limit_day = tmp->upload_limit_day;
                memset(server_password, 0, sizeof(server_password));
                strncpy(server_password, tmp->passwd, sizeof(server_password));

                log_cnm("%s %d bwlimit %s server_password %s upload_status %d upload_limit_day %d\n",
                    __FUNCTION__, __LINE__, bwlimit_conf, server_password, ysf_upload_status, ysf_upload_limit_day);
                cnm_conf_init_success = 1;
            }
            break;

        default:
            log_cnm("%s %d wrong data type:%d.\n", __FUNCTION__, __LINE__, data_header->type);
            return;
        }

        size += sizeof(struct cnm_data_header) + data_header->data_len;
        data_header = (struct cnm_data_header *)(data + size);
    }
}

/* client socket receive data from server, thread */
void *client_recv_data(void *arg)
{
    int fd = -1;
    int ret;
    int recv_err_count = 0;
    int data_len;
    struct timeval tv;
	struct sockaddr_in server_addr;

	fd_set rset;
	char data[2048] = { 0 };

create_sock:

    if (-1 != fd)
    {
        log_cnm("%s %d fd %d error.\n", __FUNCTION__, __LINE__, fd);
        sleep(5);
        close(fd);
        recv_err_count = 0;
        fd = -1;
        cent_sockfd = -1;
    }

#ifndef DEBUG
    struct hostent *host;
    if (NULL == (host = gethostbyname(SERVER_DOMAIN)))
    {
        log_cnm("%s %d get host by name %s error. ERROR:%s\n", __FUNCTION__, __LINE__, SERVER_DOMAIN, strerror(errno));
        sleep(5);
        goto create_sock;
    }
#endif

    if (-1 == (fd = socket(PF_INET, SOCK_STREAM, 0)))
    {
        log_cnm("%s %d create socket error. ERROR:%s\n", __FUNCTION__, __LINE__, strerror(errno));
        sleep(5);
        goto create_sock;
    }

    tv.tv_sec = 6;
    tv.tv_usec = 0;
    if (-1 == setsockopt(fd, SOL_SOCKET, SO_SNDTIMEO, (char *)&tv, sizeof(tv)))
    {
        log_cnm("%s %d set socket %d timeout option error. ERROR:%s\n", __FUNCTION__, __LINE__, fd, strerror(errno));
        goto create_sock;
    }

    memset(&server_addr, 0, sizeof(server_addr));
    server_addr.sin_family = AF_INET;
    server_addr.sin_port = htons(CNM_PORT);
#ifdef DEBUG
    server_addr.sin_addr.s_addr = 0x5e1410ac;
#else
    server_addr.sin_addr.s_addr = *(unsigned int *)host->h_addr;
#endif

    if (-1 == connect(fd, (struct sockaddr *)&server_addr, sizeof(struct sockaddr)))
    {
        log_cnm("%s %d connect socket %d ip 0x%x port %d error. ERROR:%s\n", __FUNCTION__, __LINE__, fd, ntohl(server_addr.sin_addr.s_addr), ntohs(server_addr.sin_port), strerror(errno));
        goto create_sock;
    }

    cent_sockfd = fd;
    link_ok = 1;

    while (link_ok)
    {
		if (recv_err_count > CNM_SERVER_TIMEOUT_DEFAULT)
        {
            log_cnm("%s %d select socket %d error.\n", __FUNCTION__, __LINE__, fd);
            link_ok = 0;
            goto create_sock;
        }

		tv.tv_sec = 1;
		tv.tv_usec = 0;
        FD_ZERO(&rset);
        FD_SET(cent_sockfd, &rset);

        ret = select(cent_sockfd + 1, &rset, NULL, NULL, &tv);
        if (ret < 0)
        {
            log_cnm("%s %d select socket %d error. ERROR:%s\n", __FUNCTION__, __LINE__, fd, strerror(errno));
            link_ok = 0;
            goto create_sock;
        }

        if (ret == 0)
        {
            recv_err_count++;
            continue;
        }
        recv_err_count = 0;

        ret = recv(cent_sockfd, (char *)&data_len, 4, MSG_WAITALL);
        if (ret < 4 || data_len > 2048)
        {
            log_cnm("%s %d recv socket ret %d len %d error. ERROR:%s\n", __FUNCTION__, __LINE__, ret, data_len, strerror(errno));
            link_ok = 0;
            goto create_sock;
        }

        memset(&data, 0, sizeof(data));

        ret = recv(cent_sockfd, (char *)&data, data_len, MSG_WAITALL);
        if (ret <= 0 || ret != data_len)
        {
            log_cnm("%s %d recv socket ret %d len %d error. ERROR:%s\n", __FUNCTION__, __LINE__, ret, data_len, strerror(errno));
            link_ok = 0;
            goto create_sock;
        }

        recv_data_handle(data, data_len);
    }

    goto create_sock;
}

#endif

#if 98
void client_timer_heardbeat(char *status)
{
    char send_buf[65535] = { 0 };
    struct cnm_packet *send_pkt = (struct cnm_packet *)send_buf;
    struct cnm_data *send_data = (struct cnm_data *)send_pkt->data;
    int data_len = 0;
    int send_len = 0;
    
    char *s = status;
    char *token;
    char *p[32] = { 0 };
    int count = 0;
    int i;

    if (!link_ok)
    {
        log_cnm("%s %d link status error.\n", __FUNCTION__, __LINE__);
        return;
    }

    //log_cnm("%s %d data %s\n", __FUNCTION__, __LINE__, status);

    token = strsep( &s, "," );

    while( token != NULL )
    {
        p[count++] = token;
        token = strsep( &s, "," );
    }

    if (0)
    {
        log_cnm("%s %d count %d\n", __FUNCTION__, __LINE__, count);

        for (i = 0; i < count; i++)
            log_cnm("%s %d p[%d]=%s\n", __FUNCTION__, __LINE__, i, p[i]);
    }

    if (count != 10) {
        log_cnm("%s %d error data %s\n", __FUNCTION__, __LINE__, status);
        return;
    }

    int run = atoi(p[0]);    
    data_len += tlv_put_item(send_data->data + data_len,
        CNM_DATA_TYPE_TASKS_STATUS, (char *)&run, 4);
    run = atoi(p[1]);
    data_len += tlv_put_item(send_data->data + data_len,
        CNM_DATA_TYPE_ELECTRICITY, (char *)&run, 4);
    run = atoi(p[2]);
    data_len += tlv_put_item(send_data->data + data_len,
        CNM_DATA_TYPE_CAMERA_CONNECTION, (char *)&run, 4);
    
    data_len += tlv_put_item(send_data->data + data_len,
        CNM_DATA_TYPE_APERTURE_CURRENT, p[3], strlen(p[3]));
    data_len += tlv_put_item(send_data->data + data_len,
        CNM_DATA_TYPE_SHUTTER_CURRENT, p[4], strlen(p[4]));
    data_len += tlv_put_item(send_data->data + data_len,
        CNM_DATA_TYPE_ISO_CURRENT, p[5], strlen(p[5]));
    data_len += tlv_put_item(send_data->data + data_len,
        CNM_DATA_TYPE_LAST_PHOTO_TIME, p[6], strlen(p[6]));
    data_len += tlv_put_item(send_data->data + data_len,
        CNM_DATA_TYPE_NEXT_PHOTO_TIME, p[7], strlen(p[7]));
    data_len += tlv_put_item(send_data->data + data_len,
        CNM_DATA_TYPE_TEMPERATURE, p[8], strlen(p[8]));
    data_len += tlv_put_item(send_data->data + data_len,
        CNM_DATA_TYPE_HUMITURE, p[9], strlen(p[9]));

    data_len += tlv_put_item(send_data->data + data_len,
        CNM_DATA_TYPE_VERSION, (char *)software_version, 4);
    data_len += tlv_put_item(send_data->data + data_len,
        CNM_DATA_TYPE_APERTURE_RANGE, device_aperture_range, strlen(device_aperture_range));
    data_len += tlv_put_item(send_data->data + data_len,
        CNM_DATA_TYPE_SHUTTER_RANGE, device_shutter_range, strlen(device_shutter_range));
    data_len += tlv_put_item(send_data->data + data_len,
        CNM_DATA_TYPE_ISO_RANGE, device_iso_range, strlen(device_iso_range));
    data_len += tlv_put_item(send_data->data + data_len,
        CNM_DATA_TYPE_UPLOAD_STATUS, (char *)&ysf_upload_status, 4);

    send_pkt->total_len = client_build_item(send_pkt->data, CNM_DEVICE_SYS_STAT_INFO,
        CNM_RESOLVE_TYPE_TLV, NULL, data_len);  
    
    send_len = send_pkt->total_len + sizeof(send_pkt->total_len);

    pthread_mutex_lock(&cent_sockfd_mutex);
    if(send_len != send(cent_sockfd, (void *)send_buf, send_len, 0))
    {
        log_cnm("%s %d send to server error. ERROR:%s\n", __FUNCTION__, __LINE__, strerror(errno));
        link_ok = 0;
    }
    pthread_mutex_unlock(&cent_sockfd_mutex);
}
#endif

#if 99
int software_version_init(void)
{
    char line[256] = { 0 };
    int vv[4] = { 0 };
    
    FILE *fp = fopen(YSF_VERSION_FILE, "r");
    if (!fp)
        return -1;

    if (NULL != fgets(line, sizeof(line)-1, fp))
    {
        if (4 != sscanf(line, "%d.%d.%d.%d", &vv[0], &vv[1], &vv[2], &vv[3]))
        {
            fclose(fp);
            return -1;
        }
    }

    fclose(fp);
    
    software_version[0] = vv[0];
    software_version[1] = vv[1];
    software_version[2] = vv[2];
    software_version[3] = vv[3];

    return 0;
}
#endif

#if 100

int main(int arg, char **argc)
{
	pthread_t recv_tid;
    pthread_t ysf_recv_tid;
    pthread_t rsync_tid;
    int time_10s = 0;

    signal_init();
	init_daemon(0, 0);

#ifndef DEBUG
    while (0 != access(YSF_SERVER_INIT_FLAG, 0))
    {
        sleep(1);
        log_cnm("watting for server init.\n");
    }
#endif

    log_cnm("cnm client init begin.\n");

    if (0 != upload_size_init())
    {
        log_cnm("%s %d init upload size error.\n", __FUNCTION__, __LINE__);
        return -1;
    }

    if (0 != device_serial_init())
    {
        log_cnm("%s %d init device serial error.\n", __FUNCTION__, __LINE__);
        return -1;
    }   

    if (0 != device_params_init())
    {
        log_cnm("%s %d init device params error.\n", __FUNCTION__, __LINE__);
        return -2;
    }

    pthread_mutex_init(&cent_sockfd_mutex, NULL);
    pthread_mutex_init(&ysf_sockfd_mutex, NULL);

    if (0 != software_version_init())
    {
        log_cnm("%s %d init device version error.\n", __FUNCTION__, __LINE__);
        return -3;
    }
    
    log_cnm("version:%d.%d.%d.%d\n",software_version[0],software_version[1],
        software_version[2],software_version[3]);

	if (0 != pthread_create(&recv_tid, NULL, client_recv_data, NULL))
    {
        log_cnm("%s %d create sock recv thread error.\n", __FUNCTION__, __LINE__);
        return -5;
    }

    if (0 != pthread_create(&ysf_recv_tid, NULL, ysf_recv_data, NULL))
    {
        log_cnm("%s %d create local sock recv thread error.\n", __FUNCTION__, __LINE__);
        return -5;
    }

    if (0 != pthread_create(&rsync_tid, NULL, ysf_rsync_photo, NULL))
    {
        log_cnm("%s %d create local sock recv thread error.\n", __FUNCTION__, __LINE__);
        return -5;
    }

    log_cnm("cnm client init end.\n");

    while (1)
    {
        sleep(1);

        if (++time_10s >= 10)
        {
            ysf_request_status();
            ysf_notify_limit();
            ysf_notify_status();
            ysf_notify_plan();
            ysf_notify_base();
            ysf_notify_preview();
            send_preview_finish_to_server();
            send_conf_request_to_server();
            send_upload_warnning_to_server();
            upload_size_update();
            time_10s = 0;
        }
    }

    pthread_join(recv_tid, NULL);
    pthread_join(ysf_recv_tid, NULL);
    pthread_join(rsync_tid, NULL);
    
    log_cnm("cnm client err %s %d.\n",__FUNCTION__,__LINE__);
	return 0;
}
#endif
