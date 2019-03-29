#include <stdio.h>
#include <string.h>
#include <stdlib.h>
#include <errno.h>
#include <pthread.h>
#include <unistd.h>
#include <openssl/md5.h>
#include <openssl/des.h>
#include <openssl/des_old.h>
#include <fcntl.h>
#include <sys/wait.h>
#include <sys/syscall.h>
#include <sys/ipc.h>
#include <sys/msg.h>
#include <sys/socket.h>
#include <netinet/in.h>
#include <arpa/inet.h>
#include <sys/types.h>
#include <sys/stat.h>
#include <stddef.h>
#include <dirent.h>


#include "cnm_server.h"
#include "cnm_db.h"
#include "cnm_version_db.h"


struct sky_list_head g_cnm_client_key_hash[SKY_KEY_HASH_MAX];
int  cnmNMMaxClients   = 60; 
CnmDB * g_cnm_db = NULL;
CnmVersionDB *g_cnm_version_db = NULL;
pthread_mutex_t g_cnm_key_hash_mutex;
int g_cnm_nm_status   = 0;
int g_cnm_nm_debug    = 0;
struct cnm_thread_info cnm_threads[CNM_DATA_HANDLE_THREAD_NUM];
struct cnm_update_soft_status_list cnm_update_status;
u8 auto_update_start_time = 22;
u8 auto_update_end_time = 4;


#ifdef DES_ENABLE
static int Our_DES_set_key_checked( const_DES_cblock *key, DES_key_schedule *schedule )
{
    if ( DES_is_weak_key( key ) )
    {
        return ( -2 );
    }

    DES_set_key_unchecked( key, schedule );
    return 0;
}


static void triple_des_cbc_key_expansion ( u32 subkeys[48][2], char* bptr_key, u32 decrypt )
{
    DES_key_schedule* ks          = (DES_key_schedule*) subkeys;
    DES_key_schedule* ks2         = (DES_key_schedule*)(subkeys + DES_SCHEDULE_SZ);
    DES_key_schedule* ks3         = (DES_key_schedule*)(subkeys + 2 * DES_SCHEDULE_SZ);
    char*             bptr_key2   = (char*)(bptr_key + DES_KEY_SZ );
    char*             bptr_key3   = bptr_key + 2 * DES_KEY_SZ;
    int               err         = 0;
    char              logbuf[256] = { 0 };

    if ( ( err = Our_DES_set_key_checked ( ( const_des_cblock*) bptr_key, ks ) ) != 0 )
    {
        sprintf ( logbuf, "%s %d, set key failed for ks error = %d\n", __FUNCTION__, __LINE__, err );
        cnm_printf( "%s\n",logbuf );
        return;
    }

    if ( ( err = Our_DES_set_key_checked ( ( const_des_cblock*) bptr_key2, ks2 ) ) != 0 )
    {
        sprintf ( logbuf, "%s %d, set key failed for ks2 error = %d\n", __FUNCTION__, __LINE__, err );
        cnm_printf( "%s\n", logbuf );
        return;
    }

    if ( ( err = Our_DES_set_key_checked ( ( const_des_cblock*) bptr_key3, ks3 ) ) != 0 )
    {
        sprintf ( logbuf, "%s %d, set key failed for ks3 error = %d\n", __FUNCTION__, __LINE__, err );
        cnm_printf( "%s\n", logbuf );
        return;
    }

    return;
}


static void triple_des_cbc_encrypt_buffer ( u32 subkeys[48][2], 
    char* bptr_previous_ciphertext_block, u8* bptr_buffer, u32 plaintext_length )
{
    DES_key_schedule* ks  = (DES_key_schedule*) subkeys;
    DES_key_schedule* ks2 = (DES_key_schedule*) ( subkeys + DES_SCHEDULE_SZ );
    DES_key_schedule* ks3 = (DES_key_schedule*) ( subkeys + 2 * DES_SCHEDULE_SZ );
    des_ede3_cbc_encrypt ( bptr_buffer, 
                           bptr_buffer, 
                           plaintext_length, 
                           *ks, 
                           *ks2, 
                           *ks3, 
                           (const_des_cblock*) bptr_previous_ciphertext_block, 
                           DES_ENCRYPT );
}


static void triple_des_cbc_decrypt_buffer ( u32 subkeys[48][2], 
    char* bptr_initialization_vector, u8* bptr_buffer, u32 ciphertext_length )
{
    DES_key_schedule* ks  = (DES_key_schedule*) subkeys;
    DES_key_schedule* ks2 = (DES_key_schedule*) ( subkeys + DES_SCHEDULE_SZ );
    DES_key_schedule* ks3 = (DES_key_schedule*) ( subkeys + 2 * DES_SCHEDULE_SZ );
    des_ede3_cbc_encrypt ( bptr_buffer, 
                           bptr_buffer, 
                           ciphertext_length, 
                           *ks, 
                           *ks2, 
                           *ks3, 
                           (const_des_cblock*) bptr_initialization_vector, 
                           DES_DECRYPT );
}



static void sg3DESSecBuf( u8 *bptr_playload, u32 payload_length, 
    char *sec_key, char *b_iv, u32 sec_flag )
{
    u32  triple_des_exp_key[48][2];
    char iv[8] = { 0 };
    u32  len   = 0;

    memset( triple_des_exp_key, 0, sizeof ( triple_des_exp_key ) );

    if ( !sec_flag )
    {
        memcpy( iv, b_iv, 8 );
        len = ( payload_length - 1 ) / 8 * 8 + 8;

        triple_des_cbc_key_expansion ( triple_des_exp_key, sec_key, DES_ENCRYPT );

        triple_des_cbc_encrypt_buffer ( triple_des_exp_key, iv, bptr_playload, len );
    }
    else
    {
        memcpy( iv, b_iv, 8 );
        len = ( payload_length - 1 ) / 8 * 8 + 8;

        triple_des_cbc_key_expansion ( triple_des_exp_key, sec_key, DES_DECRYPT );

        triple_des_cbc_decrypt_buffer ( triple_des_exp_key, iv, bptr_playload, len );
    }
    return;
}

void cnm_3desset( u8* input, u32 sec_length, u32 flag )
{
    char    time_sec_key[24]    =
    {
      0xac, 0xb8, 0x98, 0x37, 0x29, 0x16, 0x3f, 0x5b, 0x89, 0x98, 0x21, 0x25, 0x47, 0x28, 0x9F, 0xae, 0xa7, 0x65, 0xDB,
      0x9E, 0x37, 0x6B, 0xED, 0xA3
    };

    char    time_iv[8]          =
    {
      0x28, 0x67, 0xBC, 0x9B, 0xF4, 0xD7, 0x53, 0xE8
    };

    sg3DESSecBuf( input, sec_length, time_sec_key, time_iv, flag );
}
#endif

#if 2
int sky_system(const char *func, int lino, char *cmd)
{
    int status = system(cmd);

    if (status == -1)
    {
        cnm_printf("%s %d error cmd:%s\n", 
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
                cnm_printf("%s %d errno %d cmd:%s\n", 
                    func, lino, WEXITSTATUS(status), cmd);
            }  
        }  
        else  
        {  
            cnm_printf("%s %d exit [%d] cmd:%s\n", 
                    func, lino, WEXITSTATUS(status), cmd);
        }  
    }
                    
    return -1;
}

int cnm_daemon( int nochdir, int noclose )
{
    pid_t   pid;

    pid = fork();

    /* In case of fork is error. */
    if ( pid < 0 )
    {
        perror( "fork" );
        return -1;
    }

    /* In case of this is parent process. */
    if ( pid != 0 )
    {
        exit( 0 );
    }

    /* Become session leader and get pid. */
    pid = setsid();

    if ( pid < -1 )
    {
        perror( "setsid" );
        return -1;
    }

    /* Change directory to root. */
    if ( !nochdir )
    {
        chdir( "/" );
    }

    /* File descriptor close. */
    if ( !noclose )
    {
        int fd;

        fd = open( "/dev/null", O_RDWR, 0 );
        if ( fd != -1 )
        {
            dup2( fd, STDIN_FILENO );
            dup2( fd, STDOUT_FILENO );
            dup2( fd, STDERR_FILENO );
            if ( fd > 2 )
                close( fd );
        }
    }

    umask( 0027 );

    return 0;
}

void sigint(int sig)
{
    cnm_printf( "captrue signal %d \n", sig );
	g_cnm_nm_status = 0;
}

 
/* Signale wrapper. */
void signal_set (int signo, void (*func)(int))
{
    int ret;
    struct sigaction sig;
    struct sigaction osig;

    sig.sa_handler = func;
    sigemptyset(&sig.sa_mask);
    sig.sa_flags = 0;
#ifdef SA_RESTART
    sig.sa_flags |= SA_RESTART;
#endif /* SA_RESTART */

    ret = sigaction( signo, &sig, &osig );
}

void cnm_server_signal_init( void )
{
    signal_set( SIGINT, sigint ); 
    signal_set( SIGTERM, sigint );
    signal_set( SIGTSTP, sigint );
    signal_set( SIGPIPE, sigint );
}
#endif

#if 3
u32 cnm_client_key_hash(char *key)
{
	return (jhash((void *)key, strlen(key), 0) & SKY_KEY_HASH_MASK);
}

void _cnm_client_key_hash_add(struct cnm_client_list *p_client)
{
	u32 client_hash = cnm_client_key_hash(p_client->client_node.client_info.serial);

	list_add(&p_client->client_key_list, &g_cnm_client_key_hash[client_hash]);
}

struct cnm_client_list *_cnm_client_get_by_key(char *key)
{
	struct cnm_client_list *p_client = NULL;
	struct sky_list_head *p_list, *p_next;
	u32 client_hash;

	client_hash = cnm_client_key_hash(key);

	list_for_each_safe(p_list, p_next, &g_cnm_client_key_hash[client_hash])
	{
		p_client = (struct cnm_client_list *)(((char *)p_list) - offsetof(struct cnm_client_list, client_key_list));
		if (0 == strcmp(key, p_client->client_node.client_info.serial))
			break;
		p_client = NULL; 
	}

	return p_client;
}

void cnm_client_show(void)
{
	struct cnm_client_list *p_client = NULL;
	struct sky_list_head *p_list, *p_next;
	u32 client_hash;

	cnm_printf("%s start...\n", __FUNCTION__);
	cnm_printf("========================================\n");
	cnm_printf("       key         thread device_ip\n");
	cnm_printf("----------------------------------------\n");

	pthread_mutex_lock(&g_cnm_key_hash_mutex);

	for (client_hash = 0; client_hash < SKY_KEY_HASH_MAX; client_hash++)
	{
		list_for_each_safe(p_list, p_next, &g_cnm_client_key_hash[client_hash])
		{
			p_client = (struct cnm_client_list *)(((char *)p_list) - offsetof(struct cnm_client_list, client_key_list));

			cnm_printf("%18s %-6d  %08x\n",
				p_client->client_node.client_info.serial,
				p_client->thread_idx,
				p_client->client_node.client_info.cc_ip);
		}
	}

	pthread_mutex_unlock(&g_cnm_key_hash_mutex);

	cnm_printf("----------------------------------------\n");
	cnm_printf("%s end!\n", __FUNCTION__);
}

#endif

#if 4
int _cnm_client_del(struct cnm_client_list *p_client, bool update_db)
{	
    if (p_client->client_node.client_sid > 0)
    {
        close( p_client->client_node.client_sid );
        p_client->client_node.client_sid = -1;
    }

	if (update_db && p_client->client_node.client_info.serial[0])
	{		
	    if (0 != g_cnm_db->SetDeviceOffline(p_client->client_node.client_info.serial))
	    {
	        cnm_printf( "%s %d DelDevice %x %s fail!\n", __FUNCTION__, __LINE__, 
	                    p_client->client_node.client_info.cc_ip,
	                    p_client->client_node.client_info.serial);
	    }
	}

	if (p_client->client_fd_list.next && p_client->client_fd_list.prev)
		list_del(&p_client->client_fd_list);

	if (p_client->client_key_list.next && p_client->client_key_list.prev)
		list_del(&p_client->client_key_list);

	cnm_threads[p_client->thread_idx].fd_num--;

	free(p_client);

	return 0;
}

struct cnm_client_list* cnm_client_get_by_fd(struct cnm_thread_info* p_thread, int fd)
{
    struct cnm_client_list *p_client = NULL;
    struct sky_list_head *p_list, *p_next;
	
    u32 client_hash = fd & CNM_THREAD_FD_HASH_MASK;

	pthread_mutex_lock(&p_thread->fd_hash_table_lock);

    list_for_each_safe(p_list, p_next, &p_thread->fd_hash_table[client_hash])
    {
        p_client = (struct cnm_client_list *)p_list;

        if (fd == p_client->client_node.client_sid)
            break;

		p_client = NULL;
    }
	
	pthread_mutex_unlock(&p_thread->fd_hash_table_lock);

    return p_client;
}

void cnm_client_del_by_key(char *key)
{
	struct cnm_client_list* p_client;

	pthread_mutex_lock(&g_cnm_key_hash_mutex);

	p_client = _cnm_client_get_by_key(key);

	if (p_client)
	{
		p_client->no_update_db = true;
		list_del(&p_client->client_key_list);
		if (-1 != p_client->client_node.client_sid)
		{
			close(p_client->client_node.client_sid);
			p_client->client_node.client_sid = -1;
		}
	}
	
	pthread_mutex_unlock( &g_cnm_key_hash_mutex );
}

void cnm_client_update_task_seq(char *key, int seq)
{
	struct cnm_client_list* p_client;

	pthread_mutex_lock(&g_cnm_key_hash_mutex);

	p_client = _cnm_client_get_by_key(key);

	if (p_client)
	{
		p_client->tasks_sync_seq = seq;
	}
	
	pthread_mutex_unlock( &g_cnm_key_hash_mutex );
}

void cnm_client_update_limit_seq(char *key, int seq)
{
	struct cnm_client_list* p_client;

	pthread_mutex_lock(&g_cnm_key_hash_mutex);

	p_client = _cnm_client_get_by_key(key);

	if (p_client)
	{
		p_client->limit_sync_seq = seq;
	}
	
	pthread_mutex_unlock( &g_cnm_key_hash_mutex );
}

void cnm_client_update_bwlimit_seq(char *key, int seq, int bwlimit)
{
	struct cnm_client_list* p_client;

	pthread_mutex_lock(&g_cnm_key_hash_mutex);

	p_client = _cnm_client_get_by_key(key);

	if (p_client)
	{
		p_client->bwlimit_sync_seq = seq;
		p_client->bwlimit = bwlimit;
	}
	
	pthread_mutex_unlock( &g_cnm_key_hash_mutex );
}

void cnm_client_update_upload_limit_day_seq(char *key, int seq, int bwlimit)
{
	struct cnm_client_list* p_client;

	pthread_mutex_lock(&g_cnm_key_hash_mutex);

	p_client = _cnm_client_get_by_key(key);

	if (p_client)
	{
		p_client->upload_limit_day_sync_seq = seq;
		p_client->upload_limit_day = bwlimit;
	}
	
	pthread_mutex_unlock( &g_cnm_key_hash_mutex );
}


void cnm_client_update_base_seq(char *key, int seq)
{
	struct cnm_client_list* p_client;

	pthread_mutex_lock(&g_cnm_key_hash_mutex);

	p_client = _cnm_client_get_by_key(key);

	if (p_client)
	{
		p_client->base_sync_seq = seq;
	}
	
	pthread_mutex_unlock( &g_cnm_key_hash_mutex );
}

void cnm_client_update_preview_seq(char *key, int seq)
{
	struct cnm_client_list* p_client;

	pthread_mutex_lock(&g_cnm_key_hash_mutex);

	p_client = _cnm_client_get_by_key(key);

	if (p_client)
	{
		p_client->preview_sync_seq = seq;
	}
	
	pthread_mutex_unlock( &g_cnm_key_hash_mutex );
}

int cnm_client_add(struct cnm_client_list* p_client)
{
	u32 fd_idx = p_client->client_node.client_sid & CNM_THREAD_FD_HASH_MASK;
	u32 thread_idx = fd_idx % CNM_DATA_HANDLE_THREAD_NUM;
	struct epoll_event ev;
	struct cnm_client_list* p_old_client;
	
	p_old_client = cnm_client_get_by_fd(&cnm_threads[thread_idx],
		p_client->client_node.client_sid);

	if (p_old_client
		&& p_client->client_node.client_sid != p_old_client->client_node.client_sid)
	{
		p_old_client->no_update_db = true;
		if (p_old_client->client_node.client_info.serial[0])
		{
			pthread_mutex_lock(&g_cnm_key_hash_mutex);
			list_del(&p_old_client->client_key_list);
			pthread_mutex_unlock(&g_cnm_key_hash_mutex);
		}
		
		if (-1 != p_old_client->client_node.client_sid)
		{
			close(p_old_client->client_node.client_sid);
			p_old_client->client_node.client_sid = -1;
		}		
	}

	if (cnm_threads[thread_idx].fd_num >= CNM_DATA_HANDLE_MAX_FD_NUM)
		return -1;

	ev.events = EPOLLIN | EPOLLHUP | EPOLLERR;
	ev.data.fd = p_client->client_node.client_sid;
	if (epoll_ctl(cnm_threads[thread_idx].epollfd, EPOLL_CTL_ADD,
		p_client->client_node.client_sid, &ev) == -1) 
		return -2;

	pthread_mutex_lock(&cnm_threads[thread_idx].fd_hash_table_lock);

	list_add(&p_client->client_fd_list, &cnm_threads[thread_idx].fd_hash_table[fd_idx]);
	cnm_threads[thread_idx].fd_num++;
	p_client->thread_idx = thread_idx;	
	
	pthread_mutex_unlock(&cnm_threads[thread_idx].fd_hash_table_lock);

	return 0;
}


int send_client_data(struct cnm_client_list* p_client, char* data, int data_len, int data_type, int data_id)
{
	char cnm_send_buf[1500] = { 0 };
    struct cnm_packet *send_data = (struct cnm_packet *)cnm_send_buf;
    struct cnm_data *data_buf = (struct cnm_data *)send_data->data;
    int total_len = 0;

#if 0
    if (data_id)
    {
        data_buf->header.type      = htonl( CENT_CLIENT_COMMAND_ID );
        data_buf->header.data_len  = htons( sizeof( int ) );
        ( * ( (U32*)data_buf->data ) ) = htonl( data_id );

        data_buf = ( struct cent_data_swap* )( send_data->data + sizeof( struct cent_data_header ) + sizeof( int ) );
        total_len += sizeof( struct cent_data_header ) + sizeof( int );
    }
#endif

    data_buf->header.type = data_type;

    if (( 0 < data_len) && (NULL != data))
    {
        data_buf->header.resolve_type = CNM_RESOLVE_TYPE_STRING;
        data_buf->header.data_len = data_len;
        memcpy(data_buf->data, data, data_len);
    }
    total_len += sizeof(struct cnm_data_header) + data_len;
	
#ifdef DES_ENABLE
    total_len = ( total_len + 7 ) & 0xfffffff8;
    cent_3desset((u8*)send_data->data, total_len, 0);
#endif

	send_data->total_len = total_len;

    total_len += sizeof(send_data->total_len);

    if (total_len != send(p_client->client_node.client_sid, cnm_send_buf, total_len, MSG_DONTWAIT))
    {
        cnm_printf("%s %d send data %d is error!\n", __FUNCTION__, __LINE__, total_len);
        return -1;
    }

    return 0;
}


 
#endif


#if 5

int cnm_threads_init(void)
{
	int i, j;

	for (i = 0; i < CNM_DATA_HANDLE_THREAD_NUM; i++)
	{
		for (j = 0; j < CNM_THREAD_FD_HASH_SIZE; j++)
			SKY_INIT_LIST_HEAD(&cnm_threads[i].fd_hash_table[j]);
		cnm_threads[i].fd_num = 0;
		pthread_mutex_init(&cnm_threads[i].fd_hash_table_lock, NULL);
		cnm_threads[i].epollfd = epoll_create(CNM_DATA_HANDLE_MAX_FD_NUM);
		if (-1 == cnm_threads[i].epollfd)
		{
			cnm_printf("%s %d create epoll err!\n", __FUNCTION__, __LINE__);
			break;
		}
	}

	if (i >= CNM_DATA_HANDLE_THREAD_NUM)
		return 0;

	for (j = 0; j <= i; j++)
	{
		if (j != i)
			close(cnm_threads[j].epollfd);

		pthread_mutex_destroy(&cnm_threads[j].fd_hash_table_lock);
	}

	return -1;
}

void cnm_threads_fini(void)
{
	int i, j;
	sky_list_head *p_list, *p_next;
	struct cnm_client_list *p_client;


	for (i = 0; i < CNM_THREAD_FD_HASH_SIZE; i++)
	{
		pthread_mutex_lock(&cnm_threads[i].fd_hash_table_lock);		
		pthread_mutex_lock(&g_cnm_key_hash_mutex);
		for (j = 0; j < CNM_THREAD_FD_HASH_SIZE; j++)
		{
			list_for_each_safe(p_list, p_next, &cnm_threads[i].fd_hash_table[j])
			{
				p_client = (struct cnm_client_list *)((char *)p_list - offsetof(struct cnm_client_list, client_key_list));
				_cnm_client_del(p_client, true);
			}
		}		
		pthread_mutex_lock(&cnm_threads[i].fd_hash_table_lock);
		pthread_mutex_unlock(&g_cnm_key_hash_mutex);
		pthread_mutex_destroy(&cnm_threads[i].fd_hash_table_lock);
		
		close(cnm_threads[i].epollfd);
	}
}

int cnm_init( void )
{
    int i;
	int ret = 0;
	
	cnm_daemon(0, 0);
	cnm_server_signal_init();

	if (cnm_threads_init())
	{
		cnm_printf("%s %d cnm_threads_init err!\n", __FUNCTION__, __LINE__);
		return -1;
	}

	pthread_mutex_init(&g_cnm_key_hash_mutex, NULL);

	for (i = 0; i < SKY_KEY_HASH_MAX; i++)
		SKY_INIT_LIST_HEAD(&g_cnm_client_key_hash[i]);

	pthread_mutex_init(&cnm_update_status.lock, NULL);

	for (i = 0; i < CNM_UPDATE_HASH_SIZE; i++)
		SKY_INIT_LIST_HEAD(&cnm_update_status.hash_table[i]);

    g_cnm_db = new CnmDB();
    if (!g_cnm_db)
    {
    	ret = -3;
		goto err;
    }

    if (0 != g_cnm_db->Init())
    {
        cnm_printf( "%s %d cnm db init fail!\n", __FUNCTION__, __LINE__ );
        ret = -4;
		goto err;
    }

	g_cnm_version_db = new CnmVersionDB();
	if (!g_cnm_version_db)
	{
		ret = -7;
		goto err;
	}

	if (0 != g_cnm_version_db->Init())
	{
		cnm_printf( "%s %d cnm db init fail!\n", __FUNCTION__, __LINE__ );
        ret = -8;
		goto err;
	}
	
    g_cnm_nm_status = 1;
    return 0;

err:
	
	if (g_cnm_db)
    {
        delete g_cnm_db;
        g_cnm_db = NULL;
    }

	if (g_cnm_version_db)
	{
		delete g_cnm_version_db;
		g_cnm_version_db = NULL;
	}

	return ret;
}

int cnm_fini( void )
{
	struct sky_list_head *p_list, *p_next;
	int i;
	
	cnm_threads_fini();
	
   	pthread_mutex_destroy(&g_cnm_key_hash_mutex);

	pthread_mutex_lock(&cnm_update_status.lock);

	for (i = 0; i < CNM_UPDATE_HASH_SIZE; i++)
	{
		list_for_each_safe(p_list, p_next, &cnm_update_status.hash_table[i])
		{
			list_del(p_list);
			free(p_list);
		}
	}
	
	pthread_mutex_unlock(&cnm_update_status.lock);
   	pthread_mutex_destroy(&cnm_update_status.lock);
   
    if (g_cnm_db)
    {
        delete g_cnm_db;
        g_cnm_db = NULL;
    }

	if (g_cnm_version_db)
	{
		delete g_cnm_version_db;
		g_cnm_version_db = NULL;
	}

    return 0;
}

int createHostSocket(int *hostSocket)
{
    int opt = 1;
    
    struct timeval tv_out;
    struct sockaddr_in address;

    /* create the master socket and check it worked */
    if ((*hostSocket = socket(AF_INET, SOCK_STREAM, 0)) == 0)
    {
        cnm_printf("%s %d socket ERROR! errno %d:%s\n",
			__FUNCTION__, __LINE__, errno, strerror(errno));
        return -1;
    }

    /* set master socket to allow daemon to be restarted with connections active  */
    if (setsockopt(*hostSocket, SOL_SOCKET, SO_REUSEADDR,
               (char *) &opt, sizeof(opt)) < 0)
    {
        cnm_printf("%s %d setsockopt ERROR! errno %d:%s\n",
			__FUNCTION__, __LINE__, errno, strerror(errno));
        close( *hostSocket );
        return -2;
    }

	tv_out.tv_sec = 120;
	tv_out.tv_usec = 0;
    if (0 > (opt = setsockopt (*hostSocket, SOL_SOCKET, SO_RCVTIMEO,
			(char *)&tv_out, sizeof(tv_out))))
    {
        cnm_printf("%s %d, set sock opt fail %d! errno %d:%s\n",
			__FUNCTION__, __LINE__, opt, errno, strerror(errno));
        close( *hostSocket );
        return -3;
    }

	tv_out.tv_sec = 120;
	tv_out.tv_usec = 0;
    if (0 > (opt = setsockopt (*hostSocket, SOL_SOCKET, SO_SNDTIMEO,
			(char *)&tv_out, sizeof(tv_out))))
    {
        cnm_printf("%s %d, set sock opt fail %d! errno %d:%s\n",
			__FUNCTION__, __LINE__, opt, errno, strerror(errno));
        close( *hostSocket );
        return -4;
    }
    

    /* set up socket */
    memset(&address, 0, sizeof(address));
    address.sin_family = AF_INET;
    address.sin_addr.s_addr = INADDR_ANY;
    address.sin_port = htons(CNM_PORT);

    /* bind the socket to the cnm port */
    if (bind(*hostSocket, (struct sockaddr *) &address, sizeof(address)) < 0)
    {
        cnm_printf("%s %d bind ERROR! errno %d:%s\n",
			__FUNCTION__, __LINE__, errno, strerror(errno));
        close( *hostSocket );
        return -5;
    }

    /* minimal backlog to avoid DoS */
    if (listen(*hostSocket, cnmNMMaxClients) < 0)
    {
        cnm_printf("%s %d listen ERROR! errno %d:%s\n",
			__FUNCTION__, __LINE__, errno, strerror(errno));
        close( *hostSocket );
        return -6;
    }

    return 0;
} 

#endif

#if 6

s32 handle_client_update_key(struct cnm_client_list* p_client, char *key,
	struct cnm_thread_info* p_thread)
{
	struct cnm_client_list* p_client_re;

	if (!p_client->client_node.client_info.serial[0])
	{
		pthread_mutex_lock(&g_cnm_key_hash_mutex);
		p_client_re = _cnm_client_get_by_key(key);
		if (p_client_re)
		{
			p_client_re->no_update_db = true;
			list_del(&p_client_re->client_key_list);
			if (-1 != p_client_re->client_node.client_sid)
			{
				close(p_client_re->client_node.client_sid);
				p_client_re->client_node.client_sid = -1;
			}			
		}

		strncpy(p_client->client_node.client_info.serial,
			key, sizeof(p_client->client_node.client_info.serial) - 1);

		_cnm_client_key_hash_add(p_client);
		pthread_mutex_unlock(&g_cnm_key_hash_mutex);

#if 0
		if(0 != g_cnm_db->AddDevice(p_client->client_node.client_info.serial))
	    {
	        cnm_printf( "%s %d AddDevice %x %s fail!\n", __FUNCTION__, __LINE__, 
	                    p_client->client_node.client_info.cc_ip,
	                    p_client->client_node.client_info.serial);
		}		
#endif

		return 0;
		
	}
	else if (0 != strcmp(p_client->client_node.client_info.serial, key))
	{
		return -1;
	}

	return 0;	
}

#endif

#if 7

#endif

#if 8

void auto_update_cfg_restore(void)
{
	FILE *fp;
	int start_time;
	int end_time;
	
	if (0 != access(CNM_AUTO_UPDATE_CFG_FILE, 0))
	{
		fp = fopen(CNM_AUTO_UPDATE_CFG_FILE, "a+");
		if (!fp)
		{
			cnm_printf("%s %d, open %s err!\n", __FUNCTION__, __LINE__, CNM_AUTO_UPDATE_CFG_FILE);
			return;
		}

		fwrite("auto_update_start_hour:22\nauto_update_end_hour:4\n",
			sizeof(char), sizeof("auto_update_start_hour:22\nauto_update_end_hour:4\n"), fp);

		fclose(fp);

		auto_update_start_time = 22;
		auto_update_end_time = 4;
		return;
	}

	fp = fopen(CNM_AUTO_UPDATE_CFG_FILE, "r");
	if (!fp)
	{
		cnm_printf("%s %d, open %s err!\n", __FUNCTION__, __LINE__, CNM_AUTO_UPDATE_CFG_FILE);
		return;
	}

	fscanf(fp, "auto_update_start_hour:%d\n", &start_time);
	fscanf(fp, "auto_update_end_hour:%d\n", &end_time);

	fclose(fp);

	//cnm_printf("%s %d, start_time = %d, end_time = %d\n", __FUNCTION__, __LINE__, start_time, end_time);

	if (start_time >= 0 && start_time <= 23)
		auto_update_start_time = start_time;
	
	if (end_time >= 0 && end_time <= 23)
		auto_update_end_time = end_time;

	//cnm_printf("%s %d, start_time = %d, end_time = %d\n", __FUNCTION__, __LINE__, auto_update_start_time, auto_update_end_time);
	
	return;
}

/* client build send data to server, function */
int cnm_build_item(char *buf,int type,
    int resolve_type, char *data, int data_len, char *serial)
{
    struct cnm_data *cnm_data = (struct cnm_data *)buf;

	cnm_data->header.type = type;
	cnm_data->header.resolve_type = resolve_type;
	cnm_data->header.reserved = 0;
	cnm_data->header.data_len = data_len;
	strncpy(cnm_data->header.serial, serial, sizeof(cnm_data->header.serial)-1);
	if (data && data_len)
    	memcpy(cnm_data->data, data, data_len);

    return (data_len + sizeof(struct cnm_data_header));
}

int cnm_set_update_soft_status(char *serial, enum cnm_update_soft_status_type status)
{
	sky_list_head *p_list, *p_next;
	u32 hash_index;
	struct cnm_update_soft_status_node *p_node = NULL;
	int result = 0;
	
	hash_index = jhash(serial, strlen(serial), 0) & CNM_UPDATE_HASH_MASK;

	pthread_mutex_lock(&cnm_update_status.lock);

	list_for_each_safe(p_list, p_next, &cnm_update_status.hash_table[hash_index])
	{
		p_node = (struct cnm_update_soft_status_node *)p_list;

		if (0 == strcmp(p_node->serial, serial))
			break;

		p_node = NULL;
	}

	if (!p_node)
	{
		if (CNM_UPDATE_SOFT_UPDATE_ACTION == status)
			goto end;

		if (cnm_update_status.client_num > CNM_UPDATE_DEVICE_NUM_MAX)
			return -2;

		p_node = (struct cnm_update_soft_status_node *)malloc(sizeof(*p_node));
		if (!p_node)
		{
			cnm_printf("%s %d, malloc error!\n", __FUNCTION__, __LINE__);
			result = -1;
			goto end;
		}

		memset(p_node, 0, sizeof(*p_node));

		strncpy(p_node->serial, serial, sizeof(p_node->serial) - 1);
		p_node->status = status;
		list_add(&p_node->list, &cnm_update_status.hash_table[hash_index]);
		cnm_update_status.client_num++;
		p_node->last_use_time = time(NULL);
	}
	else
	{
		if (CNM_UPDATE_SOFT_UPDATE_ACTION == status)
		{
			list_del(&p_node->list);
			cnm_update_status.client_num--;
			free(p_node);
			goto end;
		}

		if (p_node->status < status)
		{
			p_node->status = status;
			p_node->last_use_time = time(NULL);
		}
		else
		{
			result = -3;
		}
	}

end:

	pthread_mutex_unlock(&cnm_update_status.lock);
	return result;
}

void cnm_update_soft_status_timeout_check(void)
{
	struct sky_list_head *p_list, *p_next;
	int i;
	time_t now = time(NULL);	
	struct cnm_update_soft_status_node *p_node = NULL;
	
	pthread_mutex_lock(&cnm_update_status.lock);

	for (i = 0; i < CNM_UPDATE_HASH_SIZE; i++)
	{
		list_for_each_safe(p_list, p_next, &cnm_update_status.hash_table[i])
		{
			p_node = (struct cnm_update_soft_status_node *)p_list;

			if (difftime(now, p_node->last_use_time) >= CNM_UPDATE_TIMEOUT)
			{
				list_del(&p_node->list);
				cnm_update_status.client_num--;
				free(p_node);
			}
		}
	}

	pthread_mutex_unlock(&cnm_update_status.lock);

}

int cnm_send_tasks_sync_request(struct cnm_client_list *p_client, char *serial)
{
	struct cnm_sync_item_data update_item;
	char send_buf[256] = { 0 };
    struct cnm_packet *send_pkt = (struct cnm_packet *)send_buf;
    int send_len = 0;

	if (-1 == p_client->client_node.client_sid)
		return -1;

	memset(&update_item, 0, sizeof(update_item));

	snprintf(update_item.sync_cmd, sizeof(update_item.sync_cmd)-1,
		"wget -q -t 2 -T 30 http://%s/conf/%s/plan.csv -O /tmp/.plan.csv",
		SERVER_DOMAIN, serial);

	send_pkt->total_len = cnm_build_item(send_pkt->data,
            CNM_TASKS_SYNC_REQUEST, CNM_RESOLVE_TYPE_BINARY, (char *)&update_item, 
            sizeof(update_item), p_client->client_node.client_info.serial);   

	send_len = send_pkt->total_len + sizeof(send_pkt->total_len);

    if(send_len != send(p_client->client_node.client_sid, (void *)send_buf, send_len, MSG_DONTWAIT))
    {
		cnm_printf("%s %d send data %d is error!\n", __FUNCTION__, __LINE__, send_len);
		return -2;
    }

	return 0;
}

int cnm_send_limit_sync_request(struct cnm_client_list *p_client, char *serial)
{
	struct cnm_sync_item_data update_item;
	char send_buf[256] = { 0 };
    struct cnm_packet *send_pkt = (struct cnm_packet *)send_buf;
    int send_len = 0;

	if (-1 == p_client->client_node.client_sid)
		return -1;

	memset(&update_item, 0, sizeof(update_item));

	snprintf(update_item.sync_cmd, sizeof(update_item.sync_cmd)-1,
		"wget -q -t 2 -T 30 http://%s/conf/%s/limit.ini -O /tmp/.limit.ini",
		SERVER_DOMAIN, serial);

	send_pkt->total_len = cnm_build_item(send_pkt->data,
            CNM_LIMIT_SYNC_REQUEST, CNM_RESOLVE_TYPE_BINARY, (char *)&update_item, 
            sizeof(update_item), p_client->client_node.client_info.serial);   

	send_len = send_pkt->total_len + sizeof(send_pkt->total_len);

    if(send_len != send(p_client->client_node.client_sid, (void *)send_buf, send_len, MSG_DONTWAIT))
    {
		cnm_printf("%s %d send data %d is error!\n", __FUNCTION__, __LINE__, send_len);
		return -2;
    }

	return 0;
}

int cnm_send_base_sync_request(struct cnm_client_list *p_client, char *serial)
{
	struct cnm_sync_item_data update_item;
	char send_buf[256] = { 0 };
    struct cnm_packet *send_pkt = (struct cnm_packet *)send_buf;
    int send_len = 0;

	if (-1 == p_client->client_node.client_sid)
		return -1;

	memset(&update_item, 0, sizeof(update_item));

	snprintf(update_item.sync_cmd, sizeof(update_item.sync_cmd)-1,
		"wget -q -t 2 -T 30 http://%s/conf/%s/reference.ini -O /tmp/.reference.ini",
		SERVER_DOMAIN, serial);

	//cnm_printf("%s %d sync_cmd %s\n", __FUNCTION__, __LINE__, update_item.sync_cmd);

	send_pkt->total_len = cnm_build_item(send_pkt->data,
            CNM_BASE_SYNC_REQUEST, CNM_RESOLVE_TYPE_BINARY, (char *)&update_item, 
            sizeof(update_item), p_client->client_node.client_info.serial);   

	send_len = send_pkt->total_len + sizeof(send_pkt->total_len);

    if(send_len != send(p_client->client_node.client_sid, (void *)send_buf, send_len, MSG_DONTWAIT))
    {
		cnm_printf("%s %d send data %d is error!\n", __FUNCTION__, __LINE__, send_len);
		return -2;
    }

	return 0;
}

int cnm_send_preview_sync_request(struct cnm_client_list *p_client, char *serial)
{
	struct cnm_sync_item_data update_item;
	char send_buf[256] = { 0 };
    struct cnm_packet *send_pkt = (struct cnm_packet *)send_buf;
    int send_len = 0;

	if (-1 == p_client->client_node.client_sid)
		return -1;

	memset(&update_item, 0, sizeof(update_item));

	snprintf(update_item.sync_cmd, sizeof(update_item.sync_cmd)-1,
		"wget -q -t 2 -T 30 http://%s/conf/%s/preview.ini -O /tmp/.preview.ini",
		SERVER_DOMAIN, serial);

	send_pkt->total_len = cnm_build_item(send_pkt->data,
            CNM_PREVIEW_SYNC_REQUEST, CNM_RESOLVE_TYPE_BINARY, (char *)&update_item, 
            sizeof(update_item), p_client->client_node.client_info.serial);   

	send_len = send_pkt->total_len + sizeof(send_pkt->total_len);

    if(send_len != send(p_client->client_node.client_sid, (void *)send_buf, send_len, MSG_DONTWAIT))
    {
		cnm_printf("%s %d send data %d is error!\n", __FUNCTION__, __LINE__, send_len);
		return -2;
    }

	return 0;
}

int send_bwlimit_sync_notify(struct cnm_client_list *p_client, char *serial)
{
	struct cnm_bwlimit_item_data update_item;
	char send_buf[256] = { 0 };
    struct cnm_packet *send_pkt = (struct cnm_packet *)send_buf;
    int send_len = 0;

	if (-1 == p_client->client_node.client_sid)
		return -1;

	memset(&update_item, 0, sizeof(update_item));
	update_item.bwlimit = p_client->bwlimit;

	send_pkt->total_len = cnm_build_item(send_pkt->data,
            CNM_BWLIMIT_SYNC_NOTIFY, CNM_RESOLVE_TYPE_BINARY, (char *)&update_item, 
            sizeof(update_item), p_client->client_node.client_info.serial);   

	send_len = send_pkt->total_len + sizeof(send_pkt->total_len);

    if(send_len != send(p_client->client_node.client_sid, (void *)send_buf, send_len, MSG_DONTWAIT))
    {
		cnm_printf("%s %d send data %d is error!\n", __FUNCTION__, __LINE__, send_len);
		return -2;
    }

	return 0;
}

int send_upload_limit_day_sync_notify(struct cnm_client_list *p_client, char *serial)
{
	struct cnm_upload_limit_day_item_data update_item;
	char send_buf[256] = { 0 };
    struct cnm_packet *send_pkt = (struct cnm_packet *)send_buf;
    int send_len = 0;

	if (-1 == p_client->client_node.client_sid)
		return -1;

	memset(&update_item, 0, sizeof(update_item));
	update_item.upload_limit_day = p_client->upload_limit_day;

	send_pkt->total_len = cnm_build_item(send_pkt->data,
            CNM_UPLOAD_LIMIT_DAY_SYNC_NOTIFY, CNM_RESOLVE_TYPE_BINARY, (char *)&update_item, 
            sizeof(update_item), p_client->client_node.client_info.serial);   

	send_len = send_pkt->total_len + sizeof(send_pkt->total_len);

    if(send_len != send(p_client->client_node.client_sid, (void *)send_buf, send_len, MSG_DONTWAIT))
    {
		cnm_printf("%s %d send data %d is error!\n", __FUNCTION__, __LINE__, send_len);
		return -2;
    }

	return 0;
}


int send_conf_response(struct cnm_client_list *p_client)
{
	struct cnm_conf_item_data update_item;
	char send_buf[256] = { 0 };
    struct cnm_packet *send_pkt = (struct cnm_packet *)send_buf;
    int send_len = 0;

	if (-1 == p_client->client_node.client_sid)
		return -1;

	memset(&update_item, 0, sizeof(update_item));
	if (1 != g_cnm_db->GetConfig(p_client->client_node.client_info.serial,
		&update_item.bwlimit, &update_item.upload_limit_day, &update_item.upload_status))
	{
		return -1;
	}
	
	strncpy(update_item.passwd, SERVER_PASSWORD, sizeof(update_item.passwd));

	send_pkt->total_len = cnm_build_item(send_pkt->data,
            CNM_CONF_REQUSET_RESPONSE, CNM_RESOLVE_TYPE_BINARY, (char *)&update_item, 
            sizeof(update_item), p_client->client_node.client_info.serial);   

	send_len = send_pkt->total_len + sizeof(send_pkt->total_len);

    if(send_len != send(p_client->client_node.client_sid, (void *)send_buf, send_len, MSG_DONTWAIT))
    {
		cnm_printf("%s %d send data %d is error!\n", __FUNCTION__, __LINE__, send_len);
		return -2;
    }

	return 0;
}


int cnm_send_update_request(struct cnm_client_list *p_client, u32 version, const char *cmd)
{
	struct cnm_update_item_data update_item;
	char send_buf[256] = { 0 };
    struct cnm_packet *send_pkt = (struct cnm_packet *)send_buf;
    int send_len = 0;

	if (-1 == p_client->client_node.client_sid)
		return -1;

	memset(&update_item, 0, sizeof(update_item));
	update_item.version = version;
	strncpy(update_item.update_cmd, cmd, sizeof(update_item.update_cmd) - 1);

	send_pkt->total_len = cnm_build_item(send_pkt->data,
            CNM_UPDATE_SOFTWARE_REQUEST, CNM_RESOLVE_TYPE_BINARY, (char *)&update_item, 
            sizeof(update_item), p_client->client_node.client_info.serial);   

	send_len = send_pkt->total_len + sizeof(send_pkt->total_len);

    if(send_len != send(p_client->client_node.client_sid, (void *)send_buf, send_len, MSG_DONTWAIT))
    {
		cnm_printf("%s %d send data %d is error!\n", __FUNCTION__, __LINE__, send_len);
		return -2;
    }

	return 0;
}

int client_software_check(struct cnm_client_list* p_client,	struct cnm_thread_info* p_thread)
{
	u32 new_version;
	struct tm *local;
	u32 old_version = ntohl(p_client->client_node.client_info.version);
	time_t t;
	struct in_addr;
	char cmd[256] = { 0 };
	u8 start_hour;
	u8 end_hour;

	if (!g_cnm_version_db->GetAutoUpdateEnable()
		|| cnm_update_status.client_num > g_cnm_version_db->GetUpdateUpgradeLimit())
		return 0;
	
	t = time(NULL);
	local = localtime(&t);

	start_hour = g_cnm_version_db->GetUpdateStartHour();
	end_hour = g_cnm_version_db->GetUpdateEndHour();

	//cnm_printf("%s %d start_hour %d end_hour %d\n", __FUNCTION__, __LINE__, start_hour, end_hour);

	if (start_hour <= end_hour)
	{
		if (local->tm_hour < start_hour	|| local->tm_hour > end_hour)
			return 0;
	}
	else
	{
		if (!(local->tm_hour <= start_hour && local->tm_hour >= end_hour))
			return 0;
	}

	//cnm_printf("%s %d old_version %08x serial %s\n", __FUNCTION__, __LINE__, old_version, p_client->client_node.client_info.serial);

	if (!p_client->client_node.client_info.serial[0] 
 		|| !old_version)
		return 0;

	new_version = g_cnm_version_db->GetSoftVersion();

	
	//cnm_printf("%s %d new_version %08x\n", __FUNCTION__, __LINE__, new_version);
 
	if (old_version >= new_version)
		return 0;

	if (0 != cnm_set_update_soft_status(p_client->client_node.client_info.serial, CNM_UPDATE_SOFT_SEND_REQUEST)
		|| !g_cnm_db->GetAutoUpgrade(p_client->client_node.client_info.serial))
	{
		return -1;
	}
 	
	sprintf(cmd, "wget -q -t 2 -T 30 http://%s/firmware/%d.%d.%d.%d.tar.gz -O /tmp/firmware.tar.gz",
		SERVER_DOMAIN, (u8)(new_version >> 24 & 0xff),
		(u8)(new_version >> 16 & 0xff),
		(u8)(new_version >> 8 & 0xff),
		(u8)(new_version & 0xff));

	//cnm_printf("%s %d cmd %s\n", __FUNCTION__, __LINE__, cmd);

	if ( 0 == cnm_send_update_request(p_client, new_version, cmd))
	{
		p_client->update_soft = true;
		return 0;
	}

	return -2;
}

void cnm_update_list_show(void)
{
	struct sky_list_head *p_list, *p_next;
	int i;
	struct cnm_update_soft_status_node *p_node;

	cnm_printf("%s start...\n", __FUNCTION__);
	cnm_printf("=========================\n");
	cnm_printf("       key         status\n");
	cnm_printf("-------------------------\n");

	pthread_mutex_lock(&cnm_update_status.lock);
	
	for (i = 0; i < CNM_UPDATE_HASH_SIZE; i++)
	{
		list_for_each_safe(p_list, p_next, &cnm_update_status.hash_table[i])
		{
			p_node = (struct cnm_update_soft_status_node *)p_list;
			cnm_printf("%-18s %-6d\n", p_node->serial, p_node->status);
		}
	}
	
	pthread_mutex_unlock(&cnm_update_status.lock);

	cnm_printf("-------------------------\n");
}
#endif

#if 9
int send_upload_warnning_response_to_client(struct cnm_client_list *p_client)
{
	char send_buf[256] = { 0 };
    struct cnm_packet *send_pkt = (struct cnm_packet *)send_buf;
    int send_len = 0;

	if (-1 == p_client->client_node.client_sid)
		return -1;

	send_pkt->total_len = cnm_build_item(send_pkt->data,
            CNM_UPLOAD_LIMIT_DAY_WARNNING_RESPONSE, CNM_RESOLVE_TYPE_BINARY, NULL, 
            0, p_client->client_node.client_info.serial);   

	send_len = send_pkt->total_len + sizeof(send_pkt->total_len);

    //cnm_printf("%s %d status %d\n", __FUNCTION__, __LINE__, status);

    if(send_len != send(p_client->client_node.client_sid, (void *)send_buf, send_len, MSG_DONTWAIT))
    {
		cnm_printf("%s %d send data %d is error!\n", __FUNCTION__, __LINE__, send_len);
		return -2;
    }

	return 1;
}


int send_status_to_client(struct cnm_client_list *p_client, int status)
{
	char send_buf[256] = { 0 };
    struct cnm_packet *send_pkt = (struct cnm_packet *)send_buf;
    int send_len = 0;

	if (-1 == p_client->client_node.client_sid)
		return -1;

	send_pkt->total_len = cnm_build_item(send_pkt->data,
            CNM_UPDATE_STATUS_ACTION, CNM_RESOLVE_TYPE_BINARY, (char *)&status, 
            sizeof(status), p_client->client_node.client_info.serial);   

	send_len = send_pkt->total_len + sizeof(send_pkt->total_len);

    //cnm_printf("%s %d status %d\n", __FUNCTION__, __LINE__, status);

    if(send_len != send(p_client->client_node.client_sid, (void *)send_buf, send_len, MSG_DONTWAIT))
    {
		cnm_printf("%s %d send data %d is error!\n", __FUNCTION__, __LINE__, send_len);
		return -2;
    }

	return 1;
}

int send_upload_status_to_client(struct cnm_client_list *p_client, int status)
{
	char send_buf[256] = { 0 };
    struct cnm_packet *send_pkt = (struct cnm_packet *)send_buf;
    int send_len = 0;

	if (-1 == p_client->client_node.client_sid)
		return -1;

	send_pkt->total_len = cnm_build_item(send_pkt->data,
            CNM_UPLOAD_STATUS_ACTION, CNM_RESOLVE_TYPE_BINARY, (char *)&status, 
            sizeof(status), p_client->client_node.client_info.serial);   

	send_len = send_pkt->total_len + sizeof(send_pkt->total_len);

    //cnm_printf("%s %d status %d\n", __FUNCTION__, __LINE__, status);

    if(send_len != send(p_client->client_node.client_sid, (void *)send_buf, send_len, MSG_DONTWAIT))
    {
		cnm_printf("%s %d send data %d is error!\n", __FUNCTION__, __LINE__, send_len);
		return -2;
    }

	return 1;
}

int send_tasks_to_client(struct cnm_client_list *p_client)
{
	time_t now = time(NULL);

	if (-1 == p_client->client_node.client_sid 
		|| !p_client->client_node.client_info.serial[0])
		return -1;

	//等待同步60秒
	if (p_client->task_sync_timeout 
		&& difftime(now, p_client->task_sync_timeout) < 60)
		return 0;

	//同步完成时会将task_sync_timeout 设置为0，否则为同步失败，需要重新下发同步命令
	if (p_client->tasks_sync_seq == p_client->tasks_synced_seq && !p_client->task_sync_timeout)
		return 0;
	
	if (0 == cnm_send_tasks_sync_request(p_client, p_client->client_node.client_info.serial))
	{
		p_client->task_sync_timeout = now;
		//tasks_sync_seq 可能在同步时再次发生变化，所以这里先将tasks_synced_seq赋值，用timeout辅助判断是否同步完成
		p_client->tasks_synced_seq  = p_client->tasks_sync_seq;
	}

	return 0;	
}

int send_limits_to_client(struct cnm_client_list *p_client)
{
	time_t now = time(NULL);

	if (-1 == p_client->client_node.client_sid 
		|| !p_client->client_node.client_info.serial[0])
		return -1;

	//等待同步60秒
	if (p_client->limit_sync_timeout 
		&& difftime(now, p_client->limit_sync_timeout) < 60)
		return 0;

	//同步完成时会将task_sync_timeout 设置为0，否则为同步失败，需要重新下发同步命令
	if (p_client->limit_sync_seq == p_client->limit_synced_seq && !p_client->limit_sync_timeout)
		return 0;
	
	if (0 == cnm_send_limit_sync_request(p_client, p_client->client_node.client_info.serial))
	{
		p_client->limit_sync_timeout = now;
		//tasks_sync_seq 可能在同步时再次发生变化，所以这里先将tasks_synced_seq赋值，用timeout辅助判断是否同步完成
		p_client->limit_synced_seq  = p_client->limit_sync_seq;
	}

	return 0;	
}

int send_bwlimit_to_client(struct cnm_client_list *p_client)
{
	time_t now = time(NULL);

	if (-1 == p_client->client_node.client_sid 
		|| !p_client->client_node.client_info.serial[0])
		return -1;

	//等待同步60秒
	if (p_client->bwlimit_sync_timeout 
		&& difftime(now, p_client->bwlimit_sync_timeout) < 60)
		return 0;

	//同步完成时会将task_sync_timeout 设置为0，否则为同步失败，需要重新下发同步命令
	if (p_client->bwlimit_sync_seq == p_client->bwlimit_synced_seq && !p_client->bwlimit_sync_timeout)
		return 0;
	
	if (0 == send_bwlimit_sync_notify(p_client, p_client->client_node.client_info.serial))
	{
		p_client->bwlimit_sync_timeout = now;
		//tasks_sync_seq 可能在同步时再次发生变化，所以这里先将tasks_synced_seq赋值，用timeout辅助判断是否同步完成
		p_client->bwlimit_synced_seq  = p_client->bwlimit_sync_seq;
	}

	return 0;	
}

int send_upload_limit_day_to_client(struct cnm_client_list *p_client)
{
	time_t now = time(NULL);

	if (-1 == p_client->client_node.client_sid 
		|| !p_client->client_node.client_info.serial[0])
		return -1;

	//等待同步60秒
	if (p_client->upload_limit_day_sync_timeout 
		&& difftime(now, p_client->upload_limit_day_sync_timeout) < 60)
		return 0;

	//同步完成时会将task_sync_timeout 设置为0，否则为同步失败，需要重新下发同步命令
	if (p_client->upload_limit_day_sync_seq == p_client->upload_limit_day_synced_seq && !p_client->upload_limit_day_sync_timeout)
		return 0;
	
	if (0 == send_upload_limit_day_sync_notify(p_client, p_client->client_node.client_info.serial))
	{
		p_client->upload_limit_day_sync_timeout = now;
		//tasks_sync_seq 可能在同步时再次发生变化，所以这里先将tasks_synced_seq赋值，用timeout辅助判断是否同步完成
		p_client->upload_limit_day_synced_seq  = p_client->upload_limit_day_sync_seq;
	}

	return 0;	
}

int send_base_to_client(struct cnm_client_list *p_client)
{
	time_t now = time(NULL);

	if (-1 == p_client->client_node.client_sid 
		|| !p_client->client_node.client_info.serial[0])
		return -1;

	//等待同步60秒
	if (p_client->base_sync_timeout 
		&& difftime(now, p_client->base_sync_timeout) < 60)
		return 0;

	//同步完成时会将task_sync_timeout 设置为0，否则为同步失败，需要重新下发同步命令
	if (p_client->base_sync_seq == p_client->base_synced_seq && !p_client->base_sync_timeout)
		return 0;
	
	if (0 == cnm_send_base_sync_request(p_client, p_client->client_node.client_info.serial))
	{
		p_client->base_sync_timeout = now;
		//tasks_sync_seq 可能在同步时再次发生变化，所以这里先将tasks_synced_seq赋值，用timeout辅助判断是否同步完成
		p_client->base_synced_seq  = p_client->base_sync_seq;
	}

	return 0;	
}

int send_preview_to_client(struct cnm_client_list *p_client)
{
	time_t now = time(NULL);

	if (-1 == p_client->client_node.client_sid 
		|| !p_client->client_node.client_info.serial[0])
		return -1;

	//等待同步60秒
	if (p_client->preview_sync_timeout 
		&& difftime(now, p_client->preview_sync_timeout) < 60)
		return 0;

	//同步完成时会将task_sync_timeout 设置为0，否则为同步失败，需要重新下发同步命令
	if (p_client->preview_sync_seq == p_client->preview_synced_seq && !p_client->preview_sync_timeout)
		return 0;
	
	if (0 == cnm_send_preview_sync_request(p_client, p_client->client_node.client_info.serial))
	{
		p_client->preview_sync_timeout = now;
		//tasks_sync_seq 可能在同步时再次发生变化，所以这里先将tasks_synced_seq赋值，用timeout辅助判断是否同步完成
		p_client->preview_synced_seq  = p_client->preview_sync_seq;
	}

	return 0;	
}

#endif

#if 10

void cnm_dbg_status(void)
{
	FILE *fp;
	
	if (0 != access(CNM_DGB_FILE, 0))
	{
		fp = fopen(CNM_DGB_FILE, "a+");
		if (!fp)
		{
			cnm_printf("%s %d, open %s err!\n", __FUNCTION__, __LINE__, CNM_DGB_FILE);
			return;
		}

		fwrite("g_cnm_nm_debug:0\n",
			sizeof(char), sizeof("g_cnm_nm_debug:0\n"), fp);

		fclose(fp);

		g_cnm_nm_debug = 0;
		return;
	}

	fp = fopen(CNM_DGB_FILE, "r");
	if (!fp)
	{
		cnm_printf("%s %d, open %s err!\n", __FUNCTION__, __LINE__, CNM_DGB_FILE);
		return;
	}

	fscanf(fp, "g_cnm_nm_debug:%u\n", &g_cnm_nm_debug);

	fclose(fp);
	
	return;
}

#endif

#if 11
void *ysf_convert_photo(void *arg)
{
    while(g_cnm_nm_status)
    {
        int count = 0;
        do
        {
            char cipherFile[256] = {0};
        	char ciperOkFile[256] = {0};
			char ciperRawFile[256] = {0};
			char path[256] = {0};
        	char cmd[1024] = {0};
        	DIR *dp, *dp1;
        	struct dirent *entry, *entry1;

            dp = opendir(CNM_SERVER_PHOTOS_PATH);
        	if(!dp)
        	{
        		cnm_printf("%s %d open dir %s error!\n", __FUNCTION__, __LINE__, CNM_SERVER_PHOTOS_PATH);
        		break;
        	}
			
        	while(g_cnm_nm_status && (entry = readdir(dp)) != NULL) 
        	{
        		if('.' == entry->d_name[0])
        			continue;

				struct stat statbuf;

				snprintf(path, 255, "%s/%s/raw", CNM_SERVER_PHOTOS_PATH, entry->d_name);
				
		        stat(path,&statbuf);
		        if(!S_ISDIR(statbuf.st_mode)) 
		        	continue;

				dp1 = opendir(path);
	        	if(!dp1)
	        	{
	        		cnm_printf("%s %d open dir %s error!\n", __FUNCTION__, __LINE__, CNM_SERVER_PHOTOS_PATH);
	        		continue;
	        	}

				while(g_cnm_nm_status && (entry1 = readdir(dp1)) != NULL) 
	        	{
	        		if('.' == entry1->d_name[0])
	        			continue;

					int len = strlen(entry1->d_name);
					if (len <= 7)
						continue;

					if (strcmp(&entry1->d_name[len - 3], ".ok"))
						continue;

					strncpy(ciperOkFile, entry1->d_name, strlen(entry1->d_name)-7);
					strncpy(ciperRawFile, entry1->d_name, strlen(entry1->d_name)-3);
					
	                snprintf(cmd, sizeof(cmd)-1, "dcraw -c -e %s/%s/raw/%s > %s/%s/jpg/%s.jpeg",
						CNM_SERVER_PHOTOS_PATH, entry->d_name, ciperRawFile,
						CNM_SERVER_PHOTOS_PATH, entry->d_name, ciperOkFile);
	                
	        		if (0 == sky_system(__FUNCTION__, __LINE__, cmd))
	        		{
	        			snprintf(cipherFile, 255, "%s/%s", path, entry1->d_name);
	                	remove(cipherFile);
						count++;
	        		}
				}
				closedir(dp1);
        	}
        	closedir(dp);
        }while(0);

        if (count == 0)
            sleep(1);
    }

    return NULL;
}

#endif

#if 12
void *disk_check_timer(void *arg)
{
	sleep(10);
    while(g_cnm_nm_status)
    {
		if (!g_cnm_db)
			continue;

		g_cnm_db->DiskCheckTimer();
		sleep(1);
    }

    return NULL;
}

#endif


#if 99
void cnm_print_data( const u8* data, int data_len )
{
    char str[65535] = { 0 };
    int  i       = 0;

    while ( ( i + 10 ) < data_len )
    {
        cnm_printf( "%02x %02x %02x %02x %02x %02x %02x %02x %02x %02x\n", 
                    data[i], data[i + 1], data[i + 2], data[i + 3],
                    data[i + 4], data[i + 5], data[i + 6],
                    data[i + 7], data[i + 8], data[i + 9] );
        i += 10;
    }

    while ( i < data_len )
    {
        sprintf( str + strlen( str ), "%02x ", data[i] );
        i++;
    }

	sprintf( str + strlen( str ), "\n");

    cnm_printf( str );
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


int parse_heardbeat_body(char *data, int data_len, 
	struct cnm_client_info *result, int *need_update_status,
	int *need_upload_status)
{
	char attr_string[1024];

	CNM_ATTRIBUTE *attr = (CNM_ATTRIBUTE *)data;

	memset(result->data_field_start, 0, 
		offsetof(struct cnm_client_info, data_field_end) - 
		offsetof(struct cnm_client_info, data_field_start));

	while ( ((unsigned long)attr < ((unsigned long)data + data_len)))
    {
        if (!attr->length)
        {
            break;
        }
        if (attr->length <= 4 || attr->length - 4 >= 1024)
        {
            goto next_attr;
        }
        memset(attr_string, 0x00, sizeof(attr_string));
        memcpy(attr_string, attr->data, attr->length - 4);

        if (CNM_DATA_TYPE_VERSION == attr->attribute)
        {
        	result->version = *(unsigned int *)attr_string;
        }
        else if (CNM_DATA_TYPE_APERTURE_CURRENT == attr->attribute)
        {
            strncpy(result->aperture_current,
				attr_string,
				sizeof(result->aperture_current)-1);
        }
		else if (CNM_DATA_TYPE_SHUTTER_CURRENT == attr->attribute)
        {
            strncpy(result->shutter_current, 
				attr_string,
				sizeof(result->shutter_current)-1);
        }
		else if (CNM_DATA_TYPE_ISO_CURRENT == attr->attribute)
        {
            strncpy(result->iso_current, 
				attr_string,
				sizeof(result->iso_current)-1);
        }
		else if (CNM_DATA_TYPE_APERTURE_RANGE == attr->attribute)
        {
            strncpy(result->aperture_range, 
				attr_string,
				sizeof(result->aperture_range)-1);
        }
		else if (CNM_DATA_TYPE_SHUTTER_RANGE == attr->attribute)
        {
            strncpy(result->shutter_range,
				attr_string,
				sizeof(result->shutter_range)-1);
        }
		else if (CNM_DATA_TYPE_ISO_RANGE == attr->attribute)
        {
            strncpy(result->iso_range, 
				attr_string,
				sizeof(result->iso_range)-1);
        }
		else if (CNM_DATA_TYPE_TASKS_STATUS == attr->attribute)
        {
        	result->tasks_status = g_cnm_db->GetTasksStatus(result->serial);
			
            *need_update_status = (int)(result->tasks_status != *(unsigned int *)attr_string);
        }
		else if (CNM_DATA_TYPE_UPLOAD_STATUS == attr->attribute)
        {
        	result->upload_status = g_cnm_db->GetUploadStatus(result->serial);
			
            *need_upload_status = (int)(result->upload_status != *(unsigned int *)attr_string);
        }
		else if (CNM_DATA_TYPE_LAST_PHOTO_TIME == attr->attribute)
        {
        	strncpy(result->last_photo_time, 
				attr_string,
				sizeof(result->last_photo_time)-1);
        }
		else if (CNM_DATA_TYPE_NEXT_PHOTO_TIME == attr->attribute)
        {
       		strncpy(result->next_photo_time, 
				attr_string,
				sizeof(result->next_photo_time)-1);
        }
		else if (CNM_DATA_TYPE_ELECTRICITY == attr->attribute)
        {
        	result->electricity = *(unsigned int *)attr_string;
        }
		else if (CNM_DATA_TYPE_HUMITURE == attr->attribute)
        {
            strncpy(result->humiture, 
				attr_string,
				sizeof(result->humiture)-1);
        }
		else if (CNM_DATA_TYPE_CAMERA_CONNECTION == attr->attribute)
        {
        	result->camera_connection = *(unsigned int *)attr_string;
        }
		else if (CNM_DATA_TYPE_TEMPERATURE == attr->attribute)
        {
            strncpy(result->temperature, 
				attr_string,
				sizeof(result->temperature)-1);
        }

next_attr:
        attr = (CNM_ATTRIBUTE*)((char*) attr+attr->length);
    }

	int electricity = 0, camera_connection = 0;
	
	if (g_cnm_db->GetEventStatus(result->serial, &electricity, &camera_connection))
	{
		if (electricity != result->electricity)
		{
			g_cnm_db->AddEvent(result->serial, 
				result->electricity ? "city power connection restored" : "city power connection anomaly",
				result->electricity ? 2 : 1);
		}

		if (camera_connection != result->camera_connection)
		{
			g_cnm_db->AddEvent(result->serial, 
				result->camera_connection ? "camera connection restored" : "camera connection anomaly",
				result->camera_connection ? 4 : 3);
		}
	}

	return 0;
}

int set_recv_data(const char* recv_data, u32 data_len,
	struct cnm_client_list* p_client, struct cnm_thread_info* p_thread)
{
    struct cnm_client_list *p_client_list = p_client;
    struct cnm_client_node *p_client_node = NULL;
    struct cnm_data *data_header = NULL;
    u32 size = 0;
	
#ifdef DES_ENABLE
    cnm_3desset( ( u8* )recv_data, data_len, 1 );
#endif

    data_header = ( struct cnm_data *)recv_data;

    if (g_cnm_nm_debug & 0x01)
    {
    	char buffer[65535] = { 0 };
    	print_hex2string((u8 *)recv_data, data_len, buffer);
    	cnm_printf("%s %d buffer %s\n",
    		__FUNCTION__, __LINE__, buffer);
    }

    p_client_node = &p_client_list->client_node;

	if (g_cnm_nm_debug & 0x8000)
		cnm_print_data((u8 *)recv_data, data_len);

    while ((size + sizeof(struct cnm_data_header)) <= data_len)
    {
    	if (0 != handle_client_update_key(p_client, data_header->header.serial, p_thread))
				return -1;	
		
        switch (data_header->header.type)
        {
			case CNM_DEVICE_SYS_STAT_INFO:
	            {
					int need_update_status = 0;
					int need_upload_status = 0;
					
					if (CNM_RESOLVE_TYPE_TLV != data_header->header.resolve_type)
					{
						cnm_printf("%s %d, recv heardbeat type err!\n", __FUNCTION__, __LINE__);
						goto next_item;
					}
					
					if (0 != parse_heardbeat_body(data_header->data, data_header->header.data_len, 
						&p_client->client_node.client_info, &need_update_status, &need_upload_status))
					{
						cnm_printf("%s %d, parse_heardbeat_body err!\n", __FUNCTION__, __LINE__);
						goto next_item;
					}

					if (0 != g_cnm_db->UpdateDevice(&p_client->client_node.client_info))
				    {
				        cnm_printf( "%s %d UpdateDevice %s fail!\n", __FUNCTION__, __LINE__, 
				                    p_client->client_node.client_info.serial);
				    }

                    //cnm_printf("%s %d need_update_status %d need_upload_status %d\n", __FUNCTION__, __LINE__, need_update_status, need_upload_status);

					if (need_update_status)
						send_status_to_client(p_client, p_client->client_node.client_info.tasks_status);

					if (need_upload_status)
						send_upload_status_to_client(p_client, p_client->client_node.client_info.upload_status);
            	}
	            break;
			case CNM_UPDATE_SOFTWARE_RESPONSE:
				{
					cnm_set_update_soft_status(p_client->client_node.client_info.serial,
						CNM_UPDATE_SOFT_REQUEST_ACK);
				}
				break;
			case CNM_UPDATE_SOFTWARE_ACTION:
				{
					cnm_set_update_soft_status(p_client->client_node.client_info.serial,
						CNM_UPDATE_SOFT_UPDATE_ACTION);
				}
				break;
			case CNM_TASKS_SYNC_ACTION:
				{
					p_client->task_sync_timeout = 0;
                    //cnm_printf("%s %d CNM_TASKS_SYNC_ACTION\n", __FUNCTION__, __LINE__);
					g_cnm_db->UpdateTaskSyncedSeq(p_client->client_node.client_info.serial,
						p_client->tasks_synced_seq);
				}
				break;
			case CNM_LIMIT_SYNC_ACTION:
				{
					p_client->limit_sync_timeout = 0;
                    //cnm_printf("%s %d CNM_LIMIT_SYNC_ACTION\n", __FUNCTION__, __LINE__);
					g_cnm_db->UpdateLimitSyncedSeq(p_client->client_node.client_info.serial,
						p_client->limit_synced_seq);
				}
				break;
            case CNM_BASE_SYNC_ACTION:
                {
                    p_client->base_sync_timeout = 0;
                    //cnm_printf("%s %d CNM_BASE_SYNC_ACTION\n", __FUNCTION__, __LINE__);
					g_cnm_db->UpdateBaseSyncedSeq(p_client->client_node.client_info.serial,
						p_client->base_synced_seq);
                }
                break;
            case CNM_PREVIEW_SYNC_RESPONSE:
                {
					p_client->preview_sync_timeout = 0;
                    //cnm_printf("%s %d CNM_PREVIEW_SYNC_RESPONSE\n", __FUNCTION__, __LINE__);
					g_cnm_db->UpdatePreviewSyncedSeq(p_client->client_node.client_info.serial,
						p_client->preview_synced_seq, 1);
                }
                break;
            case CNM_PREVIEW_SYNC_ACTION:
                {
                    //cnm_printf("%s %d CNM_PREVIEW_SYNC_ACTION\n", __FUNCTION__, __LINE__);
					g_cnm_db->UpdatePreviewSyncedSeq(p_client->client_node.client_info.serial,
						0, 2);
                }
                break;
			case CNM_BWLIMIT_SYNC_RESPONSE:
				{
                    p_client->bwlimit_sync_timeout = 0;
                    //cnm_printf("%s %d CNM_BWLIMIT_SYNC_RESPONSE\n", __FUNCTION__, __LINE__);
					g_cnm_db->UpdateBwlimitSyncedSeq(p_client->client_node.client_info.serial,
						p_client->bwlimit_synced_seq);
                }
                break;
			case CNM_UPLOAD_LIMIT_DAY_SYNC_RESPONSE:
				{
                    p_client->upload_limit_day_sync_timeout = 0;
                    //cnm_printf("%s %d CNM_BWLIMIT_SYNC_RESPONSE\n", __FUNCTION__, __LINE__);
					g_cnm_db->UpdateUploadLimitDaySyncedSeq(p_client->client_node.client_info.serial,
						p_client->upload_limit_day_synced_seq);
				}
				break;
			case CNM_CONF_REQUSET:
				{
					//cnm_printf("%s %d CNM_CONF_REQUSET\n", __FUNCTION__, __LINE__);
					send_conf_response(p_client);
				}
				break;
			case CNM_UPLOAD_LIMIT_DAY_WARNNING_NOTIFY:
				{
					g_cnm_db->AddEvent(p_client->client_node.client_info.serial,
						"", 9);
					send_upload_warnning_response_to_client(p_client);
					break;
				}
            default:
                cnm_printf( "%s %d wrong data type:%d \n", __FUNCTION__, __LINE__, data_header->header.type );
                break;
        }

next_item:
        size += sizeof(struct cnm_data_header) + data_header->header.data_len;
        data_header = (struct cnm_data *)(recv_data + size);
    }

	return 0;
}


static void *data_handle_thread(void *arg)
{
	struct cnm_thread_info* thread_info = (struct cnm_thread_info *)arg;
	time_t time_now;
	int num_rcv;
	u32 data_len;
	int i;
	struct sky_list_head *p_list, *p_next;
	struct cnm_client_list *p_client;
	char buf[CNM_MAX_RECV_BUF];	
	struct epoll_event ev, events[CNM_EPOLL_MAX_EVENT];
	int nfds;
	int thread_id = syscall(__NR_gettid);

	if (!thread_info)
		return NULL;

	while (g_cnm_nm_status)
	{
		if (!thread_info->fd_num)
		{
			sleep(CNM_SELECT_TIMEOUT);
            continue;
		}

		time_now = time(NULL);

		pthread_mutex_lock(&thread_info->fd_hash_table_lock);

		for (i = 0; i < CNM_THREAD_FD_HASH_SIZE; i++)
		{
			list_for_each_safe(p_list, p_next, &thread_info->fd_hash_table[i])
			{
				p_client = (struct cnm_client_list *)p_list;

				if (time_now < p_client->client_node.client_time)
                {
                    p_client->client_node.client_time = time_now;
                }
				
				if (-1 == p_client->client_node.client_sid
					|| time_now >= (p_client->client_node.client_time + CNM_CLIENT_TIMEOUT))
				{
					pthread_mutex_lock(&g_cnm_key_hash_mutex);
					_cnm_client_del(p_client, !p_client->no_update_db);
					pthread_mutex_unlock(&g_cnm_key_hash_mutex);
					continue;
				}

				send_tasks_to_client(p_client);
				send_limits_to_client(p_client);
                send_base_to_client(p_client);
                send_preview_to_client(p_client);
				send_bwlimit_to_client(p_client);
				send_upload_limit_day_to_client(p_client);
			}
		}		
		
		pthread_mutex_unlock(&thread_info->fd_hash_table_lock);

		if (!thread_info->fd_num)
			continue;

		nfds = epoll_wait(thread_info->epollfd, events, CNM_EPOLL_MAX_EVENT, 1000);/*ms*/

		if (-1 == nfds)
		{
			continue;
		}

		time_now = time(NULL);

		for (i = 0; i < nfds; i++)
		{
			p_client = cnm_client_get_by_fd(thread_info, events[i].data.fd);

			if (!p_client)
				continue;
			
			if (events[i].events & EPOLLERR || events[i].events & EPOLLHUP)
	            goto close_client;

			num_rcv = recv(p_client->client_node.client_sid, (char*)&data_len, sizeof(data_len), MSG_WAITALL);
            if (0 >= num_rcv || CNM_MAX_RECV_BUF < data_len)
	            goto close_client;


            num_rcv = recv(p_client->client_node.client_sid, buf, data_len, MSG_WAITALL);
            if (0 >= num_rcv || (u32)num_rcv != data_len)
	            goto close_client;


			if (1 != client_software_check(p_client, thread_info)
				&& 0 != send_client_data(p_client, NULL, 0, CNM_CLIENT_MSG_RESPONSE, 0))
				 	goto close_client;
			

            p_client->client_node.client_time = time_now;

			if (g_cnm_nm_debug & 0x01)
				cnm_printf( "%s %d thread_id %d: recv from mac %s ip %08x socket %d:data_len = %u num_rcv = %d \n",
                    __FUNCTION__, __LINE__, thread_id, p_client->client_node.client_info.serial,
                    p_client->client_node.client_info.cc_ip,
                    p_client->client_node.client_sid,
                    data_len, num_rcv );

            if (0 == set_recv_data(buf, data_len, p_client, thread_info))
        	{
				ev.events = EPOLLIN | EPOLLHUP | EPOLLERR;
				ev.data.fd = p_client->client_node.client_sid;
				if (epoll_ctl(thread_info->epollfd, EPOLL_CTL_MOD,
					p_client->client_node.client_sid, &ev) == -1) 
				{
					cnm_printf("%s %d, mod epoll err!\n", __FUNCTION__, __LINE__);
				}					
            	continue;
        	}

close_client:
			pthread_mutex_lock(&thread_info->fd_hash_table_lock);	
			pthread_mutex_lock(&g_cnm_key_hash_mutex);
			_cnm_client_del(p_client, !p_client->no_update_db);
			pthread_mutex_unlock(&g_cnm_key_hash_mutex);
			pthread_mutex_unlock(&thread_info->fd_hash_table_lock);				
		}				
	}

	return NULL;
}

static void* cnm_recv_socket( void* )
{
    fd_set fset;
    int client_sid;
	int ret;
    int server_sid = -1;
    struct timeval tv;
    socklen_t addrsize;
    struct sockaddr_in client_addr;
    u32 client_ip;
    struct cnm_client_list *p_client;

start:
	
	if (-1 != server_sid)
	{
		close(server_sid);
		server_sid = -1;
		sleep(10);
		cnm_printf("%s %d, bind socket reset\n", __FUNCTION__, __LINE__);
	}
	
    if (createHostSocket(&server_sid) < 0)
    {
        cnm_printf( "%s %d createHostSocket error!\n", __FUNCTION__, __LINE__ );
        goto start;
    }
    
    cnm_printf ("%s %d cnm_server create listen socket %d OK!\n", __FUNCTION__, __LINE__, server_sid);
	
    while (g_cnm_nm_status)
    {
        FD_ZERO( &fset );
        FD_SET( server_sid, &fset );

        tv.tv_sec  = CNM_SELECT_TIMEOUT;
        tv.tv_usec = 0;
		
        if ((ret = select(server_sid + 1, &fset, NULL, NULL, &tv)) <= 0)
        {
			if (ret < 0)
			{
				cnm_printf("%s %d select ERROR %d! errno %d:%s\n",
					__FUNCTION__, __LINE__, ret, errno, strerror(errno));
				goto start;
			}

			continue;
		}            

        if (!(FD_ISSET(server_sid, &fset)))
            continue;

        addrsize = sizeof(client_addr);
        client_sid = accept(server_sid, (struct sockaddr *) &client_addr, &addrsize);
		
        if (g_cnm_nm_debug & 0x04)
            cnm_printf ("%s %d client sock %d ip=%08x\n",
            	__FUNCTION__, __LINE__, client_sid, ntohl(client_addr.sin_addr.s_addr));

        if (client_sid <= 0)
        {
	        cnm_printf("%s %d accept ERROR! errno %d:%s\n",
				__FUNCTION__, __LINE__, errno, strerror(errno));
            goto start;
        }

		client_ip = ntohl(client_addr.sin_addr.s_addr);

        p_client = (struct cnm_client_list *)malloc(sizeof(struct cnm_client_list));
        if (!p_client)
        {
            cnm_printf("%s %d malloc error!\n", __FUNCTION__, __LINE__);
            close( client_sid );
            continue;
        }
		
        memset(p_client, 0, sizeof(*p_client));
		p_client->client_node.client_sid = client_sid;
        p_client->client_node.client_info.cc_ip = client_ip;
        p_client->client_node.client_time = time( NULL );

	    if(0 != (ret = cnm_client_add(p_client)))
	    {
	    	cnm_printf("%s %d cnm_client_add err %d\n", __FUNCTION__, __LINE__, ret);
	    	close( client_sid );
			free(p_client);
		}
    }

	if (-1 != server_sid)
	    close( server_sid );
    return NULL;
}


static void* cnm_web_socket( void* )
{
    int sockfd;
    socklen_t addr_len = sizeof(struct sockaddr_in);
    u16 port=50000; 
    struct sockaddr_in addr_server, from_addr; 
    char recv_buf[65535];
    int recv_len;
    sockfd=socket(AF_INET,SOCK_DGRAM,0); 
    if(sockfd<0) 
    { 
        return NULL; 
    }        
    bzero(&addr_server,sizeof(struct sockaddr_in)); 
    addr_server.sin_family=AF_INET; 
    addr_server.sin_addr.s_addr=htonl(INADDR_ANY); 
    addr_server.sin_port=htons(port); 
    if(bind(sockfd,(struct sockaddr *)&addr_server,sizeof(struct sockaddr_in))<0) 
    { 
        close(sockfd);
        return NULL; 
    }
         
    fd_set readfds;

    fcntl(sockfd, F_SETFD, FD_CLOEXEC);

    while(g_cnm_nm_status)
    {
        FD_ZERO(&readfds);
        FD_SET(sockfd, &readfds);
        struct timeval tv = {1,0};
        if(0 < select(sockfd+1, &readfds, NULL, NULL, &tv))
        {
            if (FD_ISSET(sockfd, &readfds))
            {
                /* Receive bytes from client */
                recv_len = recvfrom(sockfd, recv_buf, sizeof(recv_buf), 0, (struct sockaddr*)&from_addr,&addr_len);
                if ( recv_len  > 0 )
                {
                    g_cnm_db->UpdatePreview();
                }
            }
            
        }
    }

    return NULL;
}
#endif


#if 100

int main(void)
{
    pthread_t p_dev_id;
    pthread_t p_web_id;
	pthread_t p_convert_id;
	pthread_t disk_check_id;
    pthread_attr_t attr;
    int ret = 0;
	int i;	
	time_t last = 0;
	
	pthread_t data_handle[CNM_DATA_HANDLE_THREAD_NUM];

    if (0 != cnm_init())
    {
        cnm_printf("%s %d cnm_init fail!\n", __FUNCTION__, __LINE__);
        return -1;
    }

    /* 100M stack */
    pthread_attr_init(&attr);
    pthread_attr_setstacksize(&attr, 100 * 1024 * 1024);

    do
    {
        for (i = 0; i < CNM_DATA_HANDLE_THREAD_NUM; i++)
        {
        	if (0 != pthread_create(&data_handle[i], &attr, data_handle_thread,
				(void *)&cnm_threads[i]))
	        {
	            ret = -2;
	            break;
	        }
		}

        if (0 != pthread_create(&p_dev_id, &attr, cnm_recv_socket, NULL))
        {
            ret = -3;
            break;
        }

        if (0 != pthread_create(&p_web_id, &attr, cnm_web_socket, NULL))
        {
            ret = -4;
            break;
        }  

		if (0 != pthread_create(&p_convert_id, &attr, ysf_convert_photo, NULL))
        {
            ret = -4;
            break;
        }  


		if (0 != pthread_create(&disk_check_id, &attr, disk_check_timer, NULL))
        {
            ret = -5;
            break;
        }  
		

		while(g_cnm_nm_status)
		{
			sleep(1);
			cnm_dbg_status();
			g_cnm_db->Timer();
			g_cnm_version_db->Timer();
			cnm_update_soft_status_timeout_check();
			
			if (g_cnm_nm_debug & 0x02)
			{
				if (difftime(time(NULL), last) > 10)
				{
					last = time(NULL);
					cnm_client_show();
					cnm_update_list_show();
				}
			}
		}

		for (i = 0; i < CNM_DATA_HANDLE_THREAD_NUM; i++)
        {
        	pthread_join(data_handle[i], NULL);
		}

		pthread_join(disk_check_id, NULL);
		pthread_join(p_convert_id, NULL);
		pthread_join(p_dev_id, NULL);
		pthread_join(p_web_id, NULL);
	}
	while(false);
	
    cnm_fini();

    return ret;
}
#endif
