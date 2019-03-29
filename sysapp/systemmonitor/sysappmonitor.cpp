/***********************************************************************
* (c) Copyright 2001-2009, Photonic Bridges, All Rights Reserved.
* THIS IS UNPUBLISHED PROPRIETARY SOURCE CODE OF PHOTONIC BRIDGES, INC.
* The copyright notice above does not evidence any actual or intended
* publication of such source code. 
*
*  Subsystem:   XXX
*  File:        sysappmonitro.cpp
*  Author:      ke.xiong
*  Description: monitor app when it down do something according a list
*
*************************************************************************/

#include <fcntl.h>
#include <sys/stat.h>
#include <sys/ioctl.h>
#include <sys/param.h> 
#include <sys/types.h>
#include <sys/utsname.h>
#include <sys/resource.h>
#include <dirent.h>
#include <stdint.h>
#include <errno.h>
#include <malloc.h>
#include <memory.h>
#include <unistd.h>
#include "stdio.h"
#include "stdlib.h"
#include <stddef.h>
#include <string.h>
#include <limits.h>
#include <signal.h>  
#include <time.h>
#include <stdarg.h>
#include <sys/io.h>
#include <sys/time.h> 
#include <time.h> 
#include <sys/ipc.h>
#include <sys/sem.h>
#include <sys/shm.h>
#include <sys/msg.h>
#include <sys/types.h>
#include <pthread.h>




#define PRONAME_MAX      256
#define PROCOMMIND_MAX   256
#define WECHAT_TIMEOUT          3
#define TIME_LEN             100
#define MAX_LOGFILE_SIZE     ( 2 << 20 ) /*2M*/

#ifdef CNM_CLIENT
#define SYSAPPLIST           "/home/ysf/.cnm_client/sysappmonitor.conf"
#else
#define SYSAPPLIST           "/usr/private/sysappmonitor.conf"
#endif
#define SYSAPPLOG            "/var/log/sysappmonitor"
#define SAVE_PS_FILE         "/tmp/ps_saved"
#define DMIDECODE_TEMP_FILE  "/tmp/dmidecode_file"
#define SAVE_PS_CGI_FILE     "/tmp/ps_cgi_saved"
#define CNM_SERVER_UPLOAD_FLAG "/var/www/html/download/cnm_server_flag"

int   g_user_set_bypass     = 0;


enum OPT_TAGS
{
    OPT_RESTART         = 1,
    OPT_REBOOT,
    OPT_NOP,
};

typedef struct procps_sysapp
{
    char            app_name[PRONAME_MAX];
    char            app_cmd[PROCOMMIND_MAX];
    int             app_exception_handle;
    unsigned        app_pid;
    int             app_restart_count;

    /*0  not run ,  1  is running */
    int             app_status;

    /*0  is not run before monitor, 1  is run before monitor*/
    int             app_init_status;
}procps_sysapp;


typedef struct mode_info
{
    char    mod_name[PRONAME_MAX];
    char    mod_pathname[PROCOMMIND_MAX];
    int     mod_exception_handle;
    int     mod_status;
    int     mod_init_status;
}mode_info;



int g_cpld_version_new = 0;


#if 1
const char* getcurdate( long* ioSec )
{
    static time_t   m_time; 
    struct tm*      pNow                = NULL; 
    static char     tmpDate[TIME_LEN]   = { 0 }; 

    memset( &m_time, 0, sizeof( time_t ) );
    m_time = time( NULL ); 
    if ( NULL != ioSec )
    {
        *ioSec = m_time;
    } 

    pNow = localtime( &m_time ); 
    memset( tmpDate, 0, sizeof( tmpDate ) ); 
    sprintf( tmpDate,
             "%4d-%02d-%02d %02d:%02d:%02d",
             pNow->tm_year + 1900,
             pNow->tm_mon + 1,
             pNow->tm_mday,
             pNow->tm_hour,
             pNow->tm_min,
             pNow->tm_sec ); 

    return tmpDate;
}

long get_file_size( const char* filename )
{
    struct stat f_stat  = { 0 };

    if ( stat( filename, &f_stat ) == -1 )
    {
        return -1;
    }

    return ( long ) f_stat.st_size;
}


void monitor_printf( const char* fmt )
{
    char    curtime[TIME_LEN]   = { 0 };
    FILE*   fp                  = NULL;
    long    ace_time            = 0;
    long    file_size           = 0;

    strcpy( curtime, getcurdate( &ace_time ) ); 
    file_size = get_file_size( SYSAPPLOG );

    if ( file_size >= MAX_LOGFILE_SIZE )
    {
        fp = fopen( SYSAPPLOG, "w+" );
    }
    else
    {
        fp = fopen( SYSAPPLOG, "a+" );
    }

    if ( NULL == fp )
    {
        fp = fopen( SYSAPPLOG, "w+" );
    }

    if ( NULL != fp )
    {
        fprintf( fp, " %s : %s\n", curtime, fmt );
    } 

    fflush( fp );
    fclose( fp );
}



int getSystemTime(char *name) 
{ 
	time_t timer; 
	struct tm* t_tm; 

	time(&timer); 
	t_tm = localtime(&timer); 
	sprintf(name,"%4d-%02d-%02d  %02d:%02d:%02d", t_tm->tm_year+1900,t_tm->tm_mon+1, t_tm->tm_mday, t_tm->tm_hour, t_tm->tm_min, t_tm->tm_sec);

	return 0; 
}


unsigned findPID( char* processcmd )
{
    FILE*        fp           = NULL;
    char         buf[256]     = { 0 };
    char         fmtstr[256]  = { 0 };
    unsigned int PID = 0; 
    unsigned int PRI = 0;
    char         CMD[256]     = { 0 };
    char         cmdline[256] = { 0 };

    sprintf( cmdline, "/bin/ps  -A  -o pid -o pri -o cmd > %s", SAVE_PS_FILE );
    system( cmdline );

    fp = fopen( SAVE_PS_FILE, "r" );
    if ( !fp )
    {
        sprintf( fmtstr, "error: no such file %s", SAVE_PS_FILE );
        monitor_printf( fmtstr );
        return -1;
    }

    while ( fgets( buf, sizeof( buf ), fp ) != 0 )
    {
        if ( sscanf( buf, "%d %d %[^\r\n]", &PID, &PRI, CMD ) != 3 )
        {
            continue;
        }

        if ( strcmp( CMD, processcmd ) == 0 )
        {
            fclose( fp );
            return PID ;
        }
    }

    fclose( fp );
    return 0;
}


int init_procps_sysapp( procps_sysapp* ace_sysapp, const char* conffile, int* num )
{
    FILE*   fp              = NULL;
    int     i               = 0;
    char    buffer[1024]    = { 0 }; 
    char    name[256]       = { 0 };
    char    equalmark       = '\0' ;
    char    tmp[256]        = { 0 };
    char    fmtstr[256]     = { 0 };
    char    tag[256]        = { 0 };

    fp = fopen( conffile, "r" );
    if ( !fp )
    {
        sprintf( fmtstr, "error, open %s fail!\n", conffile );
        monitor_printf( fmtstr );
        return -1;
    }

    while ( 0 != fgets( buffer, sizeof( buffer ), fp ) )
    {
        if ( '#' == buffer[0] )
        {
            continue;
        }

        if ( sscanf( buffer, "%s %c %[^\r\n]", name, &equalmark, tmp ) != 3 )
        {
            continue;
        }

        if ( ( strcmp( name, "NAME" ) == 0 ) &&
             ( equalmark == '=' ) &&
             ( strlen( tmp ) != 0 ) )
        {
            strcpy( ace_sysapp[i].app_name, tmp );
            memset( buffer, 0, sizeof( buffer ) );
            if ( 0 != fgets( buffer, sizeof( buffer ), fp ) )
            {
                sscanf( buffer, "%[^\r\n]", ace_sysapp[i].app_cmd );
                memset( buffer, 0, sizeof( buffer ) );
                if ( 0 != fgets( buffer, sizeof( buffer ), fp ) )
                {
                    sscanf( buffer, "%[^\r\n]", tag );
                    if ( !strcmp( tag, "restart" ) )
                    {
                        ace_sysapp[i].app_exception_handle = OPT_RESTART;
                    }
                    else if ( !strcmp( tag, "reboot" ) )
                    {
                        ace_sysapp[i].app_exception_handle = OPT_REBOOT;
                    }
                    else
                    {
                        ace_sysapp[i].app_exception_handle = OPT_NOP;
                    }

                    ace_sysapp[i].app_status = 0;
                    ace_sysapp[i].app_restart_count = 0;
                    i++;
                }
            }
        }
    }

    *num = i;
    fclose( fp );
    return 0;
}







int sysappmonitor_daemon( int nochdir, int noclose )
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
            {
                close( fd );
            }
        }
    }

    umask( 0027 );
    return 0;
}


#endif






int sysapp_exit( void )
{
    monitor_printf( "appmonitor exit now" );

    exit( 0 );
}

void sigint( int sig )
{
    sysapp_exit();
}

void sigtstp( int sig )
{
    sysapp_exit();
}

/* Signale wrapper. */
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

/* Initialization of signal handles. */
void signal_init()
{
    signal_set( SIGINT, sigint );
    signal_set( SIGTSTP, sigtstp );
    signal_set( SIGKILL, sigint );
    signal_set( SIGTERM, sigint );
}





int select_sleep(long time_s, long time_ms) 
{
    struct timeval tv;

    tv.tv_sec = time_s ;
    tv.tv_usec = time_ms ;

    return select(0, NULL, NULL, NULL, &tv);
}


int change_process_priority(int prio)
{
	errno = 0; //it is necessary to clear the external variable errno prior to the call.
	int oldprio = getpriority(PRIO_PROCESS, 0);
	if (errno)
	{
		oldprio = 0;
		printf ("%s: getpriority failed (errno= %d)\n", __FUNCTION__, errno);
	}
	else
	{
		//printf ("%s: original priority is %d\n", __FUNCTION__, oldprio);
	}
	//printf ("%s: setting priority to %d ... ", __FUNCTION__, prio);
	if (setpriority(PRIO_PROCESS, 0, prio) == -1)
	{
		printf ("[FAILED]\n");
	}
	else
	{
		printf ("[OK]\n");
	}

	return oldprio;
}




int main( int argc, char** argv )
{
    int             i               = 0;
    int             pronum          = 0;
    //int             modnum          = 0;
    //int             ret             = 0;
    //int             cpu_use_count   = 0;
    procps_sysapp   sysapp[20];
    char            fmtstr[256]     = { 0 };
    struct rlimit   limit           = { 0 };
    //int             sleepCount      = 0;
    FILE*           fp              = NULL;
    unsigned int    PID             = 0; 
    unsigned int    PRI             = 0;
    char            CMD[256]        = { 0 };
    char            cmdline[256]    = { 0 };
    pthread_attr_t attr;
    //int            rc = 0;
    static unsigned int     clear_core_count = 0;
	static unsigned int     upload_flag_count = 0;
    static unsigned int      time_count       = 0;
    //static unsigned int      sync_hwclock_count = 0;
	int cnm_server_flag  = 0;

    //sleep( 5 );
    sysappmonitor_daemon( 0, 0 );

    change_process_priority(-20);

    pthread_attr_init(&attr);

    /*4M maybe enough*/
    pthread_attr_setstacksize(&attr, 4 * 1024 * 1024);


    sleep(1);


    signal_init();

    memset( sysapp, 0, sizeof( sysapp ) );

    /*set core dump infinity*/
    limit.rlim_cur = RLIM_INFINITY;
    limit.rlim_max = RLIM_INFINITY;
    if ( setrlimit( RLIMIT_CORE, &limit ) )
    {
        monitor_printf( "set limit failed" );
    }

    /*make sure all process started before itself start*/
    sleep( 60 );

    /*read config file from local*/
    init_procps_sysapp( sysapp, SYSAPPLIST, &pronum );


    for ( i = 0; i < pronum; i++ )
    {
        sysapp[i].app_pid = findPID( sysapp[i].app_cmd );
        sysapp[i].app_restart_count = 0;
        if ( !sysapp[i].app_pid )
        {
            sprintf( fmtstr, "%s is not start", sysapp[i].app_name );
            monitor_printf( fmtstr );
            sysapp[i].app_init_status = 0;
        }
        else
        {
            sysapp[i].app_init_status = 1;
        }
    }


    while ( 1 )
    {
        sleep( 3 );
        time_count++;

        if( time_count > 10 )
        {
            time_count = 0;

            sprintf( cmdline, "/bin/ps  -A  -o pid -o pri -o cmd > %s", SAVE_PS_FILE );
            system( cmdline );
            fp = fopen( SAVE_PS_FILE, "r" );
            if ( !fp )
            {
                sprintf( fmtstr, "error: no such file %s", SAVE_PS_FILE );
                monitor_printf( fmtstr );
                continue;
            }

            for ( i = 0; i < pronum; i++ )
            {
                sysapp[i].app_status = 0;
            }

            while ( fgets( cmdline, sizeof( cmdline ), fp ) != 0 )
            {
                if ( sscanf( cmdline, "%d %d %[^\r\n]", &PID, &PRI, CMD ) != 3 )
                {
                    continue;
                }

                if ( '/' != CMD[0] )
                {
                    continue;
                }

                for ( i = 0; i < pronum; i++ )
                {
                    if ( 0 != strcmp( CMD, sysapp[i].app_cmd ) )
                    {
                        continue;
                    }
                    sysapp[i].app_init_status = 1;
                    sysapp[i].app_status = 1;
                }
            }
            fclose( fp );

            for ( i = 0; i < pronum; i++ )
            {
                if ( sysapp[i].app_init_status == sysapp[i].app_status )
                {
                    continue;
                }

                switch ( sysapp[i].app_exception_handle )
                	{

                    case OPT_RESTART:
                         sprintf( fmtstr, "Restart %s, times=%d \n", sysapp[i].app_name, sysapp[i].app_restart_count + 1 );
                         monitor_printf( fmtstr );

                         system(sysapp[i].app_cmd);

                         if ( !sysapp[i].app_pid )
                         {
                             sysapp[i].app_restart_count++;
                         }

                         if ( sysapp[i].app_restart_count < WECHAT_TIMEOUT )
                         {
                             break;
                         }

                         break;

                    default:
                         break;
                }
            }

#ifndef CNM_CLIENT
            /* 定期清理core文件，防止意外产生过多占用系统盘空间 */
            if( clear_core_count++ > 1000 )
            {
                clear_core_count = 0;
		/* 保留最近5个corefile */
                
                //system( "rm -f `ls -t1 $(find /root/corefile -name 'core-*' -type f)| awk '{if(NR>5){print $0}}'`" );
                system("rm -f /mnt/core-*");
            }

            //if( upload_flag_count++ > 10 )
            {
                upload_flag_count = 0;
				upload_flag_count = 0;
				fp = fopen( CNM_SERVER_UPLOAD_FLAG, "r" );
	            if ( !fp )
	            {
	                sprintf( fmtstr, "error: no such file %s", CNM_SERVER_UPLOAD_FLAG );
	                monitor_printf( fmtstr );
	            }
				else
				{

				    fgets( cmdline, sizeof( cmdline ), fp );
	                sscanf( cmdline, "%d[^\r\n]", &cnm_server_flag );

					if(1 == cnm_server_flag )
					{
					    system("cp /usr/private/download/CNMFirmware.bin  /usr/private/download/CNMFirmware.bin.bak");
    	                system("cp /var/www/html/download/CNMFirmware.bin.new  /usr/private/download/CNMFirmware.bin");
					    system("openssl enc -des -d -a -k ysf@2019 -in /usr/private/download/CNMFirmware.bin -out /usr/private/download/CNMFirmware.bin.raw");
						system("rm -rf /usr/private/download/fimware");
						system("mkdir /usr/private/download/fimware");
					    system("tar zxf /usr/private/download/CNMFirmware.bin.raw -C /usr/private/download/fimware");
                        system("/usr/private/download/fimware/upload.sh");
						system("echo 0 > /var/www/html/download/cnm_server_flag");
					}
					//printf("line: %s ,flag :%d\n",cmdline,cnm_server_flag);

		            fclose( fp );

				}
            }
#endif
        }
    }

    pthread_attr_destroy( &attr );
}


