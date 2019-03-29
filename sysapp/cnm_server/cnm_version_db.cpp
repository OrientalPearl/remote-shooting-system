#include <unistd.h> 
#include <fcntl.h>
#include <dirent.h>
#include <stdio.h>
#include <string.h>
#include <time.h>
#include <sys/time.h>
#include <sys/socket.h>
#include <netinet/in.h>
#include <arpa/inet.h>


#include "cnm_version_db.h"

using namespace mysqlpp;

int g_cnm_dbg_version = 0;

CnmVersionDB::CnmVersionDB( )
{
	strcpy( mysql_host,   "localhost");
	strcpy( mysql_user,   "root");
	strcpy( mysql_passwd, "123456");
	mysql_port = 3306;

	memset( debug_str, 0, sizeof( debug_str ) );

	pthread_mutex_init( &cs, 0 );
}

CnmVersionDB::~CnmVersionDB( )
{
	if ( con.connected() )
	{
	    con.disconnect();
	}

	pthread_mutex_destroy( &cs );
}

int CnmVersionDB::SoftVersionUpdate(void)
{
	int   try_n = 1;

	Lock();

	do
	{
		Query query = con.query();
		query << "SELECT * FROM " << CNM_SERVER_DB << CENT_VERSION_TABLE;

		try
		{
			StoreQueryResult res  = query.store();
			if (res.num_rows())
			{
				strncpy(release_time, (char *)res[0]["release_time"].c_str(), sizeof(release_time));
				soft_version = ntohl(inet_addr((char *)res[0]["version"].c_str()));
				x86_soft_version = ntohl(inet_addr((char *)res[0]["x86_version"].c_str()));
				upgrade_limit = (u32)res[0]["upgrade_limit"];
				start_hour = (u16)res[0]["start_hour"];
				end_hour = (u16)res[0]["end_hour"];
				auto_update_enable = (u16)res[0]["auto_update_enable"];
			}
			else
			{
				memset(release_time, 0, sizeof(release_time));
				soft_version = 0;
				upgrade_limit = 0;
				start_hour = 0;
				end_hour = 0;
				auto_update_enable = false;
			}

			if (g_cnm_dbg_version)
				cnm_printf("%s %d, version:%08x, x86_version:%08x, release time:%s, upgrade_limit:%d, start_hour:%d, end_hour:%d, enable:%d\n",
					__FUNCTION__, __LINE__, soft_version, x86_soft_version, release_time, upgrade_limit, start_hour, end_hour, auto_update_enable);

			UnLock();		
			return 0;
		}
		catch (const BadQuery& e) 
		{
			sprintf( debug_str, " %s %d mysql error BadQuery: %d - %s", __FUNCTION__, __LINE__, con.errnum(), e.what() );
			cnm_printf( debug_str );

			if ( 0 < try_n )
			{
				if ( GetConnection() )
				{
					try_n--;
					continue;
				}
			}

			UnLock();
			return -1;
		}
		catch (const Exception& e) 
		{
			sprintf( debug_str, " %s %d mysql error Exception: %d - %s", __FUNCTION__, __LINE__, con.errnum(), e.what() );
			cnm_printf( debug_str );

			if ( 0 < try_n )
			{
				if ( GetConnection() )
				{
					try_n--;
					continue;
				}
			}

			UnLock();
			return -1;
		}
	}
	while(0);

	UnLock();
	return 0;
}

u32 CnmVersionDB::GetSoftVersion(void)
{
	return soft_version;
}

u32 CnmVersionDB::GetX86SoftVersion(void)
{
	return x86_soft_version;
}


bool CnmVersionDB::GetAutoUpdateEnable(void)
{
	return auto_update_enable;
}

u16 CnmVersionDB::GetUpdateStartHour(void)
{
	return start_hour;
}

u16 CnmVersionDB::GetUpdateEndHour(void)
{
	return end_hour;
}

u32 CnmVersionDB::GetUpdateUpgradeLimit(void)
{
	return upgrade_limit;
}

int CnmVersionDB::Init(void)
{
	if ( !GetConnection() )
	{
	    cnm_printf( " GetMySqlConnection() error! " );
	    return -2;
	}

	if ( !CreateTable() )
	{
	    cnm_printf( " CreateTable() error! " );
	    return -3;
	}

	AlterX86Version();

	memset(release_time, 0, sizeof(release_time));
	soft_version = 0;
	upgrade_limit = 0;
	start_hour = 0;
	end_hour = 0;
	auto_update_enable = false;

	timer_last_time = time(NULL);

	return 0;
}

int CnmVersionDB::Timer( void )
{
	if(difftime(time(NULL), timer_last_time) < 10)
		return 0;

	timer_last_time = time(NULL);

	return SoftVersionUpdate();
}

bool CnmVersionDB::GetConnection()
{
    int status = false;

    if ( con.connected() )
    {
        con.disconnect();
    }

    try
    {
        status = con.connect( "", mysql_host, mysql_user, mysql_passwd, mysql_port );
    }
    catch(const BadQuery& e)
    {
        sprintf( debug_str, " %s %d mysql error BadQuery: %d - %s", __FUNCTION__, __LINE__, con.errnum(), e.what() );
        cnm_printf( debug_str );
    }
    catch(const Exception& e)
    {
        sprintf( debug_str, " %s %d mysql error Exception: %d - %s", __FUNCTION__, __LINE__, con.errnum(), e.what() );
        cnm_printf( debug_str );
    }

    return status;
}

void CnmVersionDB::Lock()
{
    pthread_mutex_lock( &cs );
}

void CnmVersionDB::UnLock()
{
    pthread_mutex_unlock( &cs );
}

int CnmVersionDB::AlterX86Version(void)
{
	int   try_n = 0;
	
	Lock();

	do
	{
		Query query = con.query();

		query << "ALTER TABLE" << CNM_SERVER_DB << CENT_VERSION_TABLE
			<< "ADD `x86_version` varchar(64) COLLATE utf8_bin NOT NULL";
		 
		//cnm_printf("%s %d, query = .%s.\n", __FUNCTION__, __LINE__, query.str().c_str());
		
		try
		{
			query.exec();
			break;
		}
		catch (const BadQuery& e) 
		{
			//sprintf( debug_str, " %s %d mysql error BadQuery: %d - %s", __FUNCTION__, __LINE__, con.errnum(), e.what() );
			//cnm_printf( debug_str );

			if ( 0 < try_n )
			{
				if ( GetConnection() )
				{
				    try_n--;
				    continue;
				}
			}

			UnLock();
			return -1;
		}
		catch (const Exception& e) 
		{
			//sprintf( debug_str, " %s %d mysql error Exception: %d - %s", __FUNCTION__, __LINE__, con.errnum(), e.what() );
			//cnm_printf( debug_str );

			if ( 0 < try_n )
			{
			    if ( GetConnection() )
			    {
			        try_n--;
			        continue;
			    }
			}

			UnLock();
			return -1;
		}
	}while ( true );

	UnLock();
	return 0;
}


bool  CnmVersionDB::CreateTable( )
{
	Query query = con.query();

	query << "CREATE TABLE IF NOT EXISTS " << CNM_SERVER_DB << CENT_VERSION_TABLE << "("
		"`version` varchar(64) COLLATE utf8_bin NOT NULL,"
		"`x86_version` varchar(64) COLLATE utf8_bin NOT NULL,"
		"`release_time` timestamp NOT NULL,"
		"`auto_update_enable` BOOLEAN NOT NULL DEFAULT 0,"
		"`start_hour` SMALLINT NOT NULL DEFAULT 0 CHECK (start_hour >= 0 AND start_hour <= 23),"
		"`end_hour` SMALLINT NOT NULL DEFAULT 0 CHECK (end_hour >= 0 AND end_hour <= 23),"
		"`upgrade_limit` INT NOT NULL DEFAULT 0,"
		"PRIMARY KEY (`version`)"
		") ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin";

	//cnm_printf("%s %d, query = .%s.\n", __FUNCTION__, __LINE__, query.str().c_str());

	try
	{
		query.exec();
	}
	catch (const BadQuery& e) 
	{
		sprintf( debug_str, " %s %d mysql error BadQuery: %d - %s", __FUNCTION__, __LINE__, con.errnum(), e.what() );
		cnm_printf( debug_str );
		return false;
	}
	catch (const Exception& e) 
	{
		sprintf( debug_str, " %s %d mysql error Exception: %d - %s", __FUNCTION__, __LINE__, con.errnum(), e.what() );
		cnm_printf( debug_str );
		return false;
	}
	
    return true;
}

