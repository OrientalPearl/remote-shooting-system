#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <semaphore.h>
#include <signal.h> 
#include <sys/msg.h> 
#include <sys/time.h> 
#include <time.h> 
#include <ctype.h> 
#include <sys/socket.h>
#include <arpa/inet.h>
#include <linux/netlink.h> 
#include <errno.h> 
#include <fcntl.h>
#include <unistd.h>
#include <syslog.h>
#include <libintl.h>
#include <locale.h>
#include <iconv.h>
#include <fcntl.h>
#include <errno.h>
#include<sys/types.h>
#include<sys/stat.h>
#include <stddef.h>


#include "demo.h"


int g_debug_level = 0;
int g_daemon = 1;

#define demo_printf(fmt...) \
    do \
    { \
        if (g_daemon) \
            syslog( LOG_USER | LOG_INFO, fmt); \
        else \
            printf(fmt); \
    }while(0); 

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

    buf = (unsigned char*)addr;

    byte_len = sprintf(buffer, "%s", "\r\nAddress     Data(hex)               ");
    byte_len += sprintf(buffer + byte_len, "%s", "                          ASCII\r\n" );

    for( i = 0; i < ( len >> 4 ); i++ )
    {
        byte_len += sprintf(buffer + byte_len, "[%p]: ", addr+(i<<4));
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
        byte_len += sprintf(buffer + byte_len, "[%p]: ", addr + (i<<4));
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

void signal_set (int signo, void (*func)(int))
{
  int ret;
  struct sigaction sig;
  struct sigaction osig;

  sig.sa_handler = func;
  sigemptyset (&sig.sa_mask);
  sig.sa_flags = 0;
#ifdef SA_RESTART
  sig.sa_flags |= SA_RESTART;
#endif /* SA_RESTART */

  ret = sigaction (signo, &sig, &osig);
}

void sigint(int sig)
{
    demo_printf("smp 2009 sso server exit, signal is %d\n", sig);
    exit(0);
}

/* Initialization of signal handles. */
void signal_init(void)
{
    signal_set(SIGINT, sigint);
    signal_set(SIGTSTP, sigint);
    signal_set(SIGKILL, sigint);
    signal_set(SIGTERM, sigint);
    signal_set(SIGUSR1, sigint);
    signal_set(SIGSEGV, sigint);
    signal_set(SIGPIPE, sigint);
    signal_set(SIGFPE, sigint);
    signal_set(SIGABRT, sigint);
}

int daemon (int nochdir, int noclose)
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

int demo_msg_handle(struct demo_msg_hdr *msg_hdr, char *msg_body, int msg_body_len, int fd)
{
    char send_buf[1400] = { 0 };
    struct demo_msg_hdr *send_pkt = (struct demo_msg_hdr *)send_buf;
    send_pkt->flag = 0x68;
    send_pkt->code = msg_hdr->code;
    send_pkt->reason = 3;
    
    demo_printf("%s %d code %d reason %d\n", __FUNCTION__, __LINE__, msg_hdr->code, msg_hdr->reason);

    switch (msg_hdr->code)
    {
        case 0x01:
        case 0x02:
        case 0x03:
        case 0x04:
        case 0x05:
            {
                send_pkt->length = sizeof(struct demo_msg_hdr) - offsetof(struct demo_msg_hdr, length);
                send(fd, (void *)send_buf, send_pkt->length + offsetof(struct demo_msg_hdr, length), 0);
            }
            break;
        case 0x06:
            {
                strcpy((char *)send_pkt->data, "1,1,1,8,1/200,100,18:05:00,21:00:00,28.5,50%");
                send_pkt->length = sizeof(struct demo_msg_hdr) - offsetof(struct demo_msg_hdr, length) + strlen((char *)send_pkt->data);
                send(fd, (void *)send_buf, send_pkt->length + offsetof(struct demo_msg_hdr, length), 0);
            }
            break;
        default:
            return 0;
            break;
    }

    return 0;
}

void demo_msg_recv(int fd, int server_fd)
{
	fd_set rset;
    int ret;
    struct demo_msg_hdr msg_hdr;
    char msg_body[1400];
    int msg_body_len;
	struct timeval tv;

	while (1)
	{
        FD_ZERO(&rset);
        FD_SET(fd, &rset);
        tv.tv_sec = 5;
        tv.tv_usec = 0;

        ret = select(fd + 1, &rset, NULL, NULL, &tv);
        if (0 > ret)
        {
            if (g_debug_level > 1)
                demo_printf("%s %d select from tcp socket %d error. ERROR:%s\n", __FUNCTION__, __LINE__, fd, strerror(errno));
            break;
        }
        else if (0 == ret)
        {
            FD_ZERO(&rset);
            FD_SET(server_fd, &rset);
            tv.tv_sec = 0;
            tv.tv_usec = 0;

            if (select(server_fd + 1, &rset, NULL, NULL, &tv))
            {
                if (g_debug_level > 1)
                    demo_printf("%s %d new connection coming on fd %d close current fd %d.\n", __FUNCTION__, __LINE__, server_fd, fd);
                break;
            }
            continue;
        }

        /* receive message header */
        memset(&msg_hdr, 0, sizeof(msg_hdr));
        ret = recv(fd, &msg_hdr, sizeof(msg_hdr), MSG_DONTWAIT);
        if (0 > ret)
        {
            /* errno, agian.. */
            if (g_debug_level > 1)
                demo_printf("%s %d recv from tcp socket %d error. ERROR:%s\n", __FUNCTION__, __LINE__, fd, strerror(errno));
            break;
        }
        else if (0 == ret)
        {
            /* client close */
            if (g_debug_level > 1)
                demo_printf("%s %d tcp socket %d closed.\n", __FUNCTION__, __LINE__, fd);
            break;
        }

        if (sizeof(msg_hdr) != ret)
        {
            if (g_debug_level > 1)
                demo_printf("%s %d recv from tcp socket len %d error, need %lu.\n", __FUNCTION__, __LINE__, ret, sizeof(msg_hdr));
            break;
        }

        if (0x68 != msg_hdr.flag)
        {
            if (g_debug_level > 1)
                demo_printf("%s %d recv msg header flag 0x%x error.\n", __FUNCTION__, __LINE__, msg_hdr.flag);
            break;
        }

        if (g_debug_level > 4)
        {
            char buffer[65535] = { 0 };
            print_hex2string((unsigned char*)&msg_hdr, sizeof(msg_hdr), buffer);
            printf("%s\n", buffer);
        }

        msg_body_len = (unsigned int)msg_hdr.length - (sizeof(msg_hdr) - offsetof(struct demo_msg_hdr, length));

        if (msg_body_len < 0 || msg_body_len > sizeof(msg_body))
        {
            if (g_debug_level > 1)
                demo_printf("%s %d recv msg body len %d error.\n", __FUNCTION__, __LINE__, msg_body_len);
            break;
        }

        if (msg_body_len > 0)
        {
            /* receive message body */
            memset(&msg_body, 0, sizeof(msg_body));
            ret = recv(fd, &msg_body, msg_body_len, MSG_DONTWAIT);
            if (0 > ret)
            {
                /* errno, agian.. */
                if (g_debug_level > 1)
                    demo_printf("%s %d recv from tcp socket %d error. ERROR:%s\n", __FUNCTION__, __LINE__, fd, strerror(errno));
                break;
            }
            else if (0 == ret)
            {
                /* client close */
                if (g_debug_level > 1)
                    demo_printf("%s %d tcp socket %d closed.\n", __FUNCTION__, __LINE__, fd);
                break;
            }

            
            if (g_debug_level > 4)
            {
                char buffer[65535] = { 0 };
                print_hex2string((unsigned char *)msg_body, ret, buffer);
                printf("%s\n", buffer);
            }

            if (msg_body_len != ret)
            {
                if (g_debug_level > 1)
                    demo_printf("%s %d recv from tcp socket len %d error, need %u.\n", __FUNCTION__, __LINE__, ret, msg_body_len);
                break;
            }
        }

        if (0 != demo_msg_handle(&msg_hdr, msg_body, msg_body_len, fd))
        {
            if (g_debug_level > 1)
                demo_printf("%s %d smp 2009 message handle error.\n", __FUNCTION__, __LINE__);
            break;
        }
	}
}

unsigned short demo_listen_port(void)
{
    return htons(8000);
}

int main(int argc, char **argv)
{
	int server_fd = -1;
	int newfd = -1;
    int bind_times = 0;
	socklen_t socksize;
	struct sockaddr_in server_addr;
	struct sockaddr_in client_addr;

    const char *opts = "dF";

    int done = 0;
    while(!done)
    {
        char c;
        switch(c = getopt(argc, argv, opts))
        {
            case -1:
                done = 1;
                break;
            case 'd':
                g_debug_level++;
                break;
            case 'F':
                g_daemon = 0;
                break;
            default:
                exit(1);
                break;
        }
    }

    if (g_daemon)
        daemon(0, 0);
    
    signal_init();

	if (-1 == (server_fd = socket(PF_INET, SOCK_STREAM, 0)))
	{
        if (g_debug_level > 0)
		    demo_printf("%s %d create socket error. ERROR:%s\n", __FUNCTION__, __LINE__, strerror(errno));
        return -1;
	}

	memset(&server_addr, 0, sizeof(server_addr));
	server_addr.sin_family = AF_INET;
	server_addr.sin_port = demo_listen_port();
	server_addr.sin_addr.s_addr = INADDR_ANY;

bind_retry:
    if (-1 == bind(server_fd, (struct sockaddr *)&server_addr, sizeof(server_addr))) 
    {
        if (bind_times < 12)
        {
            bind_times++;
            if (g_debug_level > 0)
                demo_printf("%s %d bind socket %d error, retry %d. ERROR:%s\n", __FUNCTION__, __LINE__, server_fd, bind_times, strerror(errno));
            sleep(10);
            goto bind_retry;
        }

        if (g_debug_level > 0)
            demo_printf("%s %d bind socket %d error. ERROR:%s\n", __FUNCTION__, __LINE__, server_fd, strerror(errno));
        goto out;
    }

	/* Keep max 100 pending connections */
	if (-1 == listen(server_fd, 100))
	{
        if (g_debug_level > 0)
		    demo_printf("%s %d listen socket %d error. ERROR:%s\n", __FUNCTION__, __LINE__, server_fd, strerror(errno));
        goto out;
	}

	socksize = sizeof(struct sockaddr_in);

	while (1)
	{
		if (-1 == (newfd = accept(server_fd, (struct sockaddr *)&client_addr, &socksize)))
		{
            if (g_debug_level > 0)
			    demo_printf("%s %d accept socket %d error. ERROR:%s\n", __FUNCTION__, __LINE__, server_fd, strerror(errno));
			sleep(1);
			continue;
		}

        int size = 8388608*20;
        int status = setsockopt(newfd, SOL_SOCKET, SO_RCVBUFFORCE, &size, sizeof(int));
        if(0 != status)
        {
            if (g_debug_level > 0)
                demo_printf("%s %d set SO_RCVBUFFORCE err. errno %d\n", __FUNCTION__, __LINE__, errno);
        }

         if (g_debug_level > 0)
		    demo_printf("%s %d ###info:accept new socket %d from ip 0x%x port %u.\n", __FUNCTION__, __LINE__, 
		        newfd, ntohl(client_addr.sin_addr.s_addr), ntohs(client_addr.sin_port));
         
        demo_msg_recv(newfd, server_fd);

        if (g_debug_level > 0)
		    demo_printf("%s %d ###info:close new socket %d from ip 0x%x port %u.\n", __FUNCTION__, __LINE__, newfd, ntohl(client_addr.sin_addr.s_addr), ntohs(client_addr.sin_port));

        close(newfd);
	}

out:
    close(server_fd);
    return -1;
}
