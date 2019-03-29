#include <sys/time.h>
#include <time.h>
#include <sys/stat.h>
#include <unistd.h>
#include <stdarg.h>
#include <stdio.h>
#include <stdlib.h>
#include <syslog.h>
#include "sky_syslog.h"

void cnm_printf( const char* fmt, ... )
{
	struct stat  filebuf;
	char tunnelBuf[65535] = { 0 };
	static int g_msgLen  = 0;
	int len = 0;
	va_list varg;
	FILE*  fd  = NULL;
	int  ret  = 0;
	static char logPath[128]    = { 0 };
	struct timeval tv;
	time_t  timer;
	struct tm* tblock = NULL;

	sprintf( logPath, "%s", SKY_SYSLOG_PATH );

	fd = fopen( logPath, "at+" );
	if ( !fd )
	{
		return;
	}

	if( !g_msgLen )
	{
		stat( logPath, &filebuf);
		g_msgLen += filebuf.st_size;
	}

	gettimeofday( &tv, NULL );

	timer = tv.tv_sec;
	tblock = localtime( &timer );
	len = sprintf( tunnelBuf, "[%04d-%02d-%02d %02d:%02d:%02d::%03d]", 
		tblock->tm_year + 1900, tblock->tm_mon + 1, tblock->tm_mday, tblock->tm_hour,
		tblock->tm_min, tblock->tm_sec, (int)( tv.tv_usec ) / 1000 );

	va_start( varg, fmt );
	len += vsnprintf( tunnelBuf + len, 2048 - len, fmt, varg );
	va_end( varg );

	if( len > 0 )
	{
		if (tunnelBuf[len - 1] != '\n')
			tunnelBuf[len++] = '\n';
		
		ret = fwrite( tunnelBuf, sizeof( char ), len, fd );
	}
	
	fclose( fd );
	g_msgLen += ret;
	if ( g_msgLen >= SKY_SYSLOG_MAX_SIZE )
	{
		remove( logPath );
		syslog( LOG_USER | LOG_INFO,  "%s, remove %s \n", __FUNCTION__, SKY_SYSLOG_PATH );     
		g_msgLen = 0;
	}
	return;
}


