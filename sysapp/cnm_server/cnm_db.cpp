#include <unistd.h> 
#include <fcntl.h>
#include <dirent.h>
#include <stdio.h>
#include <string.h>
#include <time.h>
#include <sys/time.h>
#include <sys/stat.h>


#include "cnm_db.h"

extern void cnm_client_update_task_seq(char *key, int seq);
extern void cnm_client_update_limit_seq(char *key, int seq);
extern void cnm_client_update_base_seq(char *key, int seq);
extern void cnm_client_update_preview_seq(char *key, int seq);
extern void cnm_client_update_bwlimit_seq(char *key, int seq, int bwlimit);
extern void cnm_client_update_upload_limit_day_seq(char *key, int seq, int bwlimit);

using namespace mysqlpp;

CnmDB::CnmDB( )
{
	strcpy( mysql_host,   "localhost");
	strcpy( mysql_user,   "root");
	strcpy( mysql_passwd, "123456");
	mysql_port = 3306;

	memset( debug_str, 0, sizeof( debug_str ) );

	pthread_mutex_init( &cs, 0 );
}

CnmDB::~CnmDB( )
{
	if ( con.connected() )
	{
	    con.disconnect();
	}

	pthread_mutex_destroy( &cs );
}

int CnmDB::InitDevice(void)
{
	int   try_n = 1;

	if (false == CheckConnection())
		return -1;
	
	Lock();

	do
	{
		Query query = con.query();

		query << "UPDATE " << CNM_SERVER_DB << CENT_DEVICE_TABLE << " SET `device_ip` = 0";
				
		try
		{
			query.exec();
			break;
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
	}while ( true );

	UnLock();
	return 0;
}

int CnmDB::CfgUpdateTimer( void )
{
	int   try_n = 1;

	if (false == CheckConnection())
		return -1;

	Lock();

	do
	{
		Query query = con.query();
		query << "SELECT `serial`, `tasks_sync_seq` FROM " << CNM_SERVER_DB << CENT_DEVICE_TABLE
				<< " WHERE `tasks_sync_seq`!=`tasks_synced_seq`";

		try
		{
			StoreQueryResult res  = query.store();
			int rows = (int)res.num_rows();

			for (int i = 0; i < rows; i++)
			{
				Query query1 = con.query();

				query1 << "SELECT * FROM " << CNM_SERVER_DB << CENT_TASKS_TABLE
					<< " WHERE `serial`='" << res[i]["serial"].c_str() << "'";

				
				//cnm_printf("%s %d, query = .%s.\n", __FUNCTION__, __LINE__, query1.str().c_str());

				try
				{
					StoreQueryResult res1 = query1.store();

					int rows1 = (int)res1.num_rows();

					char filename[256] = { 0 };
					snprintf(filename, sizeof(filename)-1,
						"%s/%s/plan.csv", CONF_BASE_PATH, res[i]["serial"].c_str());
					
					FILE *fp = fopen(filename, "w+");

					if (!fp)
					{
						//cnm_printf( "%s %d open file %s error!\n", 
						//	__FUNCTION__, __LINE__, filename );
						continue;
					}

					fprintf(fp, "TYPE,A,S,ISO,START_TIME,NUMBER,INTERVAL\n");

					//cnm_printf("%s %d, rows1 = .%d.\n", __FUNCTION__, __LINE__, rows1);

					for (int i1 = 0; i1 < rows1; i1++)
					{
						fprintf(fp, "%s,%s,%s,%s,%s,%s,%s\n",
							atoi(res1[i1]["type"].c_str()) == 1 ? "M" : "A",
							res1[i1]["aperture"].c_str(),
							res1[i1]["shutter"].c_str(),
							res1[i1]["iso"].c_str(),
							res1[i1]["shooting_time"].c_str(),
							res1[i1]["shooting_number"].c_str(),
							res1[i1]["shooting_interval"].c_str());
					}

					fclose(fp);

					cnm_client_update_task_seq((char *)res[i]["serial"].c_str(),
						atoi((char *)res[i]["tasks_sync_seq"].c_str()));
				}
				catch (const BadQuery& e) 
				{
					sprintf( debug_str, " %s %d mysql error BadQuery: %d - %s", __FUNCTION__, __LINE__, con.errnum(), e.what() );
					cnm_printf( debug_str );
					continue;
				}
				catch (const Exception& e) 
				{
					sprintf( debug_str, " %s %d mysql error Exception: %d - %s", __FUNCTION__, __LINE__, con.errnum(), e.what() );
					cnm_printf( debug_str );
					continue;
				}
			}
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

			break;
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

			break;
		}
	}
	while(0);

	try_n = 1;
	
	do
	{
		Query query = con.query();
		query << "SELECT `serial`, `limits_sync_seq`, `aperture_max`, `aperture_min`, `shutter_max`, `shutter_min`, `iso_max`, `iso_min`FROM " << CNM_SERVER_DB << CENT_DEVICE_TABLE
				<< " WHERE `limits_sync_seq`!=`limits_synced_seq`";

		try
		{
			StoreQueryResult res  = query.store();
			int rows = (int)res.num_rows();

			for (int i = 0; i < rows; i++)
			{
				char filename[256] = { 0 };
				snprintf(filename, sizeof(filename)-1,
					"%s/%s/limit.ini", CONF_BASE_PATH, res[i]["serial"].c_str());
				
				FILE *fp = fopen(filename, "w+");

				if (!fp)
				{
					cnm_printf( "%s %d open file %s error!\n", 
						__FUNCTION__, __LINE__, filename );
					continue;
				}

				fprintf(fp, "[limit]\nMax_A=%s\nMin_A=%s\nMax_S=%s\nMin_S=%s\nMax_ISO=%s\nMin_ISO=%s\n",
					res[i]["aperture_max"].c_str(),
					res[i]["aperture_min"].c_str(),
					res[i]["shutter_max"].c_str(),
					res[i]["shutter_min"].c_str(),
					res[i]["iso_max"].c_str(),
					res[i]["iso_min"].c_str());

				fclose(fp);

				cnm_client_update_limit_seq((char *)res[i]["serial"].c_str(),
					atoi((char *)res[i]["limits_sync_seq"].c_str()));
			}
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

			break;
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

			break;
		}
	}
	while(0);

    try_n = 1;
	
	do
	{
		Query query = con.query();
		query << "SELECT `serial`, `base_sync_seq`, `aperture_base`, `shutter_base`, `iso_base` FROM " << CNM_SERVER_DB << CENT_DEVICE_TABLE
				<< " WHERE `base_sync_seq`!=`base_synced_seq`";

		//cnm_printf("%s %d, query = .%s.\n", __FUNCTION__, __LINE__, query.str().c_str());

		try
		{
			StoreQueryResult res  = query.store();
			int rows = (int)res.num_rows();

			for (int i = 0; i < rows; i++)
			{
				char filename[256] = { 0 };
				snprintf(filename, sizeof(filename)-1,
					"%s/%s/reference.ini", CONF_BASE_PATH, res[i]["serial"].c_str());
				
				FILE *fp = fopen(filename, "w+");

				if (!fp)
				{
					cnm_printf( "%s %d open file %s error!\n", 
						__FUNCTION__, __LINE__, filename );
					continue;
				}

				fprintf(fp, "[reference]\na = %s\ns = %s\niso = %s\ntime = %lu\n",
					res[i]["aperture_base"].c_str(),
					res[i]["shutter_base"].c_str(),
					res[i]["iso_base"].c_str(),
					time(NULL));

				fclose(fp);

				cnm_client_update_base_seq((char *)res[i]["serial"].c_str(),
					atoi((char *)res[i]["base_sync_seq"].c_str()));
			}
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

			break;
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

			break;
		}
	}
	while(0);

	try_n = 1;
	
	do
	{
		Query query = con.query();
		query << "SELECT `serial`, `bwlimit_sync_seq`, `bwlimit` FROM " << CNM_SERVER_DB << CENT_DEVICE_TABLE
				<< " WHERE `bwlimit_sync_seq`!=`bwlimit_synced_seq`";

		try
		{
			StoreQueryResult res  = query.store();
			int rows = (int)res.num_rows();

			for (int i = 0; i < rows; i++)
			{
				cnm_client_update_bwlimit_seq((char *)res[i]["serial"].c_str(),
					atoi((char *)res[i]["bwlimit_sync_seq"].c_str()),
					atoi((char *)res[i]["bwlimit"].c_str()));
			}
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

			break;
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

			break;
		}
	}
	while(0);

	try_n = 1;
	
	do
	{
		Query query = con.query();
		query << "SELECT `serial`, `upload_limit_day_sync_seq`, `upload_limit_day` FROM " << CNM_SERVER_DB << CENT_DEVICE_TABLE
				<< " WHERE `upload_limit_day_sync_seq`!=`upload_limit_day_synced_seq`";

		try
		{
			StoreQueryResult res  = query.store();
			int rows = (int)res.num_rows();

			for (int i = 0; i < rows; i++)
			{
				cnm_client_update_upload_limit_day_seq((char *)res[i]["serial"].c_str(),
					atoi((char *)res[i]["upload_limit_day_sync_seq"].c_str()),
					atoi((char *)res[i]["upload_limit_day"].c_str()));
			}
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

			break;
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

			break;
		}
	}
	while(0);

	UnLock();

    UpdatePreview();
	return 0;
}

int CnmDB::Init(void)
{
	if ( !GetConnection() )
	{
	    cnm_printf( " GetMySqlConnection() error! " );
	    return -1;
	}

	if ( !CnmDBInit() )
	{
		cnm_printf( " CnmDBInit() error! " );
		return -2;
	}

	if ( !CreateTable() )
	{
	    cnm_printf( " CreateTable() error! " );
	    return -3;
	}

	InitDevice();

	timer_last_time = time(NULL);

	return 0;
}

int CnmDB::Timer( void )
{
	if(difftime(time(NULL), timer_last_time) < 10)
		return 0;

	timer_last_time = time(NULL);

	return CfgUpdateTimer();
}

void CnmDB::DiskCheckTimer( void )
{
	if(difftime(time(NULL), disk_timer_last_time) < 10)
		return;

	disk_timer_last_time = time(NULL);
	
	do
	{
		Lock();
		
		Query query = con.query();
		query << "SELECT `upload_status`, `disk_waring`, `disk_size`, `serial` FROM " << CNM_SERVER_DB << CENT_DEVICE_TABLE;

		//cnm_printf("%s %d, query = .%s.\n", __FUNCTION__, __LINE__, query.str().c_str());

		try
		{
			StoreQueryResult res  = query.store();
			UnLock();
			
			int rows = (int)res.num_rows();

			for (int i = 0; i < rows; i++)
			{
				char filename[256] = { 0 };
				snprintf(filename, sizeof(filename)-1,
					"%s/%s/raw", CNM_SERVER_PHOTOS_PATH, res[i]["serial"].c_str());

				if (access(filename, 0) != 0)
					continue;

				char cmd[256] = { 0 };
				snprintf(cmd, sizeof(cmd)-1,
					"du -sm %s | awk '{print $1}'", filename);
				
				FILE *fp = popen(cmd, "r");

				if (!fp)
				{
					cnm_printf( "%s %d popen cmd %s error!\n", 
						__FUNCTION__, __LINE__, cmd );
					continue;
				}

				int size = 0;
				int update = 0;
				int disk_waring = 0;
				int upload_status = 0;

				fscanf(fp, "%d", &size);
				pclose(fp);

				int limit_size = atoi(res[i]["disk_size"].c_str());
				
				if (!limit_size)
				{
					if (atoi((char *)res[i]["disk_waring"].c_str()) != 0
						|| atoi((char *)res[i]["upload_status"].c_str()) != 0)
					{
						update = 1;
					}
				}
				else
				{
					float percent = (float)size / limit_size;
					
					if (!!atoi((char *)res[i]["disk_waring"].c_str()) != (percent >= 0.9))
					{
						update = true;
						AddEvent(res[i]["serial"].c_str(), "", percent >= 0.9 ? 5 : 6);
					}
					
					if (!!atoi((char *)res[i]["upload_status"].c_str()) != ((limit_size - size) < 50))
					{
						update = true;
						
						AddEvent(res[i]["serial"].c_str(), "", (limit_size - size) < 50 ? 7 : 8);
					}

					if (update)
					{
						upload_status = (limit_size - size) < 50;
						disk_waring = (percent >= 0.9);
					}					
				}

				if (update)
				{
					Query query = con.query();

					query << "UPDATE " << CNM_SERVER_DB << CENT_DEVICE_TABLE 
						<< " SET `disk_waring`=" << disk_waring
						<< ", `upload_status`=" << upload_status
						<< " WHERE `serial`='" << res[i]["serial"].c_str() << "'";

					//cnm_printf("%s %d, query = .%s.\n", __FUNCTION__, __LINE__, query.str().c_str());
					
					Lock();
					
					try
					{
						query.exec();
					}
					catch (const BadQuery& e) 
					{
						sprintf( debug_str, " %s %d mysql error BadQuery: %d - %s", __FUNCTION__, __LINE__, con.errnum(), e.what() );
						cnm_printf( debug_str );
						UnLock();
						continue;
					}
					catch (const Exception& e) 
					{
						sprintf( debug_str, " %s %d mysql error Exception: %d - %s", __FUNCTION__, __LINE__, con.errnum(), e.what() );
						cnm_printf( debug_str );
						UnLock();
						continue;
					}

					UnLock();
				}
				
				#if 0
				if (size <= limit_size)
					continue;

				snprintf(cmd, sizeof(cmd)-1,
					"ls -alh %s | awk '{print $5,$9}'", filename);

				fp = popen(cmd, "r");

				if (!fp)
				{
					cnm_printf( "%s %d popen cmd %s error!\n", 
						__FUNCTION__, __LINE__, cmd );
					continue;
				}

				char buffer[128] = { 0 };

			    while ( fgets( buffer, sizeof( buffer ), fp ) != 0 )
			    {
			    	int file_size = 0;
					char file[64] = { 0 };
			        if ( sscanf( buffer, "%dM %[^\r\n]", &file_size, file ) != 2)
			            continue;

					if (file[0] == '.')
						continue;

					char filepath[256] = { 0 };
					snprintf(filepath, sizeof(filepath)-1,
						"%s/%s", filename, file);
					remove(filepath);


					int file_name_len = strlen(file);
					if (file_name_len > 4)
					{
						filename[file_name_len - 4] = 0;
						
						snprintf(filepath, sizeof(filepath)-1,
							"%s/%s", filename, file);
						remove(filepath);
					}

			        size -= file_size;

					//cnm_printf("%s %d rm %s size %d\n",
					//	__FUNCTION__, __LINE__, filepath, file_size);

					if (size <= limit_size)
						break;
			    }

				pclose(fp);	
				#endif
			}

			break;
		}
		catch (const BadQuery& e) 
		{
			sprintf( debug_str, " %s %d mysql error BadQuery: %d - %s", __FUNCTION__, __LINE__, con.errnum(), e.what() );
			cnm_printf( debug_str );
			UnLock();
			break;
		}
		catch (const Exception& e) 
		{
			sprintf( debug_str, " %s %d mysql error Exception: %d - %s", __FUNCTION__, __LINE__, con.errnum(), e.what() );
			cnm_printf( debug_str );
			UnLock();
			break;
		}
	}
	while(0);

}

void CnmDB::UpdatePreview(void)
{
    int try_n = 1;

    Lock();
	
	do
	{
		Query query = con.query();
		query << "SELECT `serial`, `preview_sync_seq`, `aperture_preview`, `shutter_preview`, `iso_preview` FROM " << CNM_SERVER_DB << CENT_DEVICE_TABLE
				<< " WHERE `preview_sync_seq`!=`preview_synced_seq` and `status_preview`=0";

		//cnm_printf("%s %d, query = .%s.\n", __FUNCTION__, __LINE__, query.str().c_str());

		try
		{
			StoreQueryResult res  = query.store();
			int rows = (int)res.num_rows();

			for (int i = 0; i < rows; i++)
			{
				char filename[256] = { 0 };
				snprintf(filename, sizeof(filename)-1,
					"%s/%s/preview.ini", CONF_BASE_PATH, res[i]["serial"].c_str());
				
				FILE *fp = fopen(filename, "w+");

				if (!fp)
				{
					cnm_printf( "%s %d open file %s error!\n", 
						__FUNCTION__, __LINE__, filename );
					continue;
				}

				fprintf(fp, "[preview]\na = %s\ns = %s\niso = %s\n",
					res[i]["aperture_preview"].c_str(),
					res[i]["shutter_preview"].c_str(),
					res[i]["iso_preview"].c_str());

				fclose(fp);

				cnm_client_update_preview_seq((char *)res[i]["serial"].c_str(),
					atoi((char *)res[i]["preview_sync_seq"].c_str()));
			}
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

			break;
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

			break;
		}
	}
	while(0);

	UnLock();
}

int CnmDB::GetAutoUpgrade(const char *serial)
{
	int auto_upgrade = 0;

	if (false == CheckConnection())
		return -1;
	
	Lock();

	do
	{
		Query query = con.query();
		query << "SELECT `auto_upgrade` FROM " << CNM_SERVER_DB << CENT_DEVICE_TABLE
				<< " WHERE `serial`='" << serial << "'";

		try
		{
			StoreQueryResult res  = query.store();
			
			if ((int)res.num_rows() == 1)
			{
				auto_upgrade =  atoi((char *)res[0]["auto_upgrade"].c_str());
			}
		}
		catch (const BadQuery& e) 
		{
			sprintf( debug_str, " %s %d mysql error BadQuery: %d - %s", __FUNCTION__, __LINE__, con.errnum(), e.what() );
			cnm_printf( debug_str );
		}
		catch (const Exception& e) 
		{
			sprintf( debug_str, " %s %d mysql error Exception: %d - %s", __FUNCTION__, __LINE__, con.errnum(), e.what() );
			cnm_printf( debug_str );
		}
	}while ( 0 );

    UnLock();

	return auto_upgrade;
}

void CnmDB::AddEvent(const char *serial, const char *event, int err_code)
{
	char cmd[256] = { 0 };

	snprintf(cmd, sizeof(cmd)-1, "/bin/php /usr/private/mail.php %s %d &",
		serial, err_code);

	//cnm_printf("%s %d cmd = .%s.\n", __FUNCTION__, __LINE__, cmd);
	
	system(cmd);
	
	return;
	
	if (false == CheckConnection())
		return;
	
	Lock();

	do
	{
		Query query = con.query();
		query << "INSERT INTO  " << CNM_SERVER_DB << CENT_DEVICE_EVENT_LOG
			<< "(`serial`,`event`) VALUES ('" 
			<< serial << "','" << event << "')";

		try
		{
			query.execute();
		}
		catch (const BadQuery& e) 
		{
			sprintf( debug_str, " %s %d mysql error BadQuery: %d - %s", __FUNCTION__, __LINE__, con.errnum(), e.what() );
			cnm_printf( debug_str );
		}
		catch (const Exception& e) 
		{
			sprintf( debug_str, " %s %d mysql error Exception: %d - %s", __FUNCTION__, __LINE__, con.errnum(), e.what() );
			cnm_printf( debug_str );
		}
	}while ( 0 );

    UnLock();
	return;
}

int CnmDB::GetConfig(const char *serial, int *bwlimit, int *upload_limit_day, int *upload_status)
{
	int result = 0;

	if (false == CheckConnection())
		return -1;
	
	Lock();

	do
	{
		Query query = con.query();
		query << "SELECT `bwlimit`, `upload_limit_day`, `upload_status` FROM " << CNM_SERVER_DB << CENT_DEVICE_TABLE
				<< " WHERE `serial`='" << serial << "'";

		try
		{
			StoreQueryResult res  = query.store();
			
			if ((int)res.num_rows() == 1)
			{
				*bwlimit =  atoi((char *)res[0]["bwlimit"].c_str());
				*upload_limit_day =  atoi((char *)res[0]["upload_limit_day"].c_str());
				*upload_status =  atoi((char *)res[0]["upload_status"].c_str());
				result = 1;
			}
		}
		catch (const BadQuery& e) 
		{
			sprintf( debug_str, " %s %d mysql error BadQuery: %d - %s", __FUNCTION__, __LINE__, con.errnum(), e.what() );
			cnm_printf( debug_str );
		}
		catch (const Exception& e) 
		{
			sprintf( debug_str, " %s %d mysql error Exception: %d - %s", __FUNCTION__, __LINE__, con.errnum(), e.what() );
			cnm_printf( debug_str );
		}
	}while ( 0 );

    UnLock();

	return result;
}


int CnmDB::GetBwlimit(const char *serial)
{
	int auto_upgrade = 0;

	if (false == CheckConnection())
		return -1;
	
	Lock();

	do
	{
		Query query = con.query();
		query << "SELECT `bwlimit` FROM " << CNM_SERVER_DB << CENT_DEVICE_TABLE
				<< " WHERE `serial`='" << serial << "'";

		try
		{
			StoreQueryResult res  = query.store();
			
			if ((int)res.num_rows() == 1)
			{
				auto_upgrade =  atoi((char *)res[0]["bwlimit"].c_str());
			}
		}
		catch (const BadQuery& e) 
		{
			sprintf( debug_str, " %s %d mysql error BadQuery: %d - %s", __FUNCTION__, __LINE__, con.errnum(), e.what() );
			cnm_printf( debug_str );
		}
		catch (const Exception& e) 
		{
			sprintf( debug_str, " %s %d mysql error Exception: %d - %s", __FUNCTION__, __LINE__, con.errnum(), e.what() );
			cnm_printf( debug_str );
		}
	}while ( 0 );

    UnLock();

	return auto_upgrade;
}

int CnmDB::GetEventStatus(const char *serial, int *electricity, int *camera_connection)
{
	int status = 0;

	if (false == CheckConnection())
		return -1;
	
	Lock();

	do
	{
		Query query = con.query();
		query << "SELECT `electricity`, `camera_connection` FROM " << CNM_SERVER_DB << CENT_DEVICE_TABLE
				<< " WHERE `device_ip` != 0 and `serial`='" << serial << "'";

        //cnm_printf("%s %d, query = .%s.\n", __FUNCTION__, __LINE__, query.str().c_str());

		try
		{
			StoreQueryResult res  = query.store();
			
			if ((int)res.num_rows() == 1)
			{
				*electricity =  atoi((char *)res[0]["electricity"].c_str());
				*camera_connection =  atoi((char *)res[0]["camera_connection"].c_str());
				status = 1;
			}
		}
		catch (const BadQuery& e) 
		{
			sprintf( debug_str, " %s %d mysql error BadQuery: %d - %s", __FUNCTION__, __LINE__, con.errnum(), e.what() );
			cnm_printf( debug_str );
		}
		catch (const Exception& e) 
		{
			sprintf( debug_str, " %s %d mysql error Exception: %d - %s", __FUNCTION__, __LINE__, con.errnum(), e.what() );
			cnm_printf( debug_str );
		}
	}while ( 0 );

    UnLock();

    //cnm_printf( "%s %d status %d\n", __FUNCTION__, __LINE__, status );
    
	return status;
}

int CnmDB::GetTasksStatus(const char *serial)
{
	int status = 0;

	if (false == CheckConnection())
		return -1;
	
	Lock();

	do
	{
		Query query = con.query();
		query << "SELECT `tasks_status` FROM " << CNM_SERVER_DB << CENT_DEVICE_TABLE
				<< " WHERE `serial`='" << serial << "'";

        //cnm_printf("%s %d, query = .%s.\n", __FUNCTION__, __LINE__, query.str().c_str());

		try
		{
			StoreQueryResult res  = query.store();
			
			if ((int)res.num_rows() == 1)
			{
				status =  atoi((char *)res[0]["tasks_status"].c_str());
			}
		}
		catch (const BadQuery& e) 
		{
			sprintf( debug_str, " %s %d mysql error BadQuery: %d - %s", __FUNCTION__, __LINE__, con.errnum(), e.what() );
			cnm_printf( debug_str );
		}
		catch (const Exception& e) 
		{
			sprintf( debug_str, " %s %d mysql error Exception: %d - %s", __FUNCTION__, __LINE__, con.errnum(), e.what() );
			cnm_printf( debug_str );
		}
	}while ( 0 );

    UnLock();

    //cnm_printf( "%s %d status %d\n", __FUNCTION__, __LINE__, status );
    
	return status;
}

int CnmDB::GetUploadStatus(const char *serial)
{
	int status = 0;

	if (false == CheckConnection())
		return -1;
	
	Lock();

	do
	{
		Query query = con.query();
		query << "SELECT `upload_status` FROM " << CNM_SERVER_DB << CENT_DEVICE_TABLE
				<< " WHERE `serial`='" << serial << "'";

        //cnm_printf("%s %d, query = .%s.\n", __FUNCTION__, __LINE__, query.str().c_str());

		try
		{
			StoreQueryResult res  = query.store();
			
			if ((int)res.num_rows() == 1)
			{
				status =  atoi((char *)res[0]["upload_status"].c_str());
			}
		}
		catch (const BadQuery& e) 
		{
			sprintf( debug_str, " %s %d mysql error BadQuery: %d - %s", __FUNCTION__, __LINE__, con.errnum(), e.what() );
			cnm_printf( debug_str );
		}
		catch (const Exception& e) 
		{
			sprintf( debug_str, " %s %d mysql error Exception: %d - %s", __FUNCTION__, __LINE__, con.errnum(), e.what() );
			cnm_printf( debug_str );
		}
	}while ( 0 );

    UnLock();

    //cnm_printf( "%s %d status %d\n", __FUNCTION__, __LINE__, status );
    
	return status;
}


int CnmDB::AddDevice(const char *serial)
{
	int   try_n = 1;

	if (false == CheckConnection())
		return -1;
	
	Lock();

	do
	{
		Query query = con.query();

		query << "INSERT INTO " << CNM_SERVER_DB << CENT_DEVICE_TABLE
			<< "(`serial`) VALUES (" 
			<< serial << ")"
			<< " ON DUPLICATE KEY UPDATE `last_heardbeat_time`=" << time(NULL);

		//cnm_printf("%s %d, query = .%s.\n", __FUNCTION__, __LINE__, query.str().c_str());
		
		try
		{
			query.exec();
			break;
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
	}while ( true );

	UnLock();
	return 0;
}

bool CnmDB::GetConnection()
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

bool CnmDB::CheckConnection()
{
	if (con.connected())
		return true;

	return GetConnection();
}
	
int CnmDB::UpdateTaskSyncedSeq(char *serial, int seq)
{
	int   try_n = 1;

	if (false == CheckConnection())
		return -1;
	
	Lock();

	do
	{
		Query query = con.query();

		query << "UPDATE " << CNM_SERVER_DB << CENT_DEVICE_TABLE 
			<< " SET `tasks_synced_seq`=" << seq
			<< " WHERE `serial`='" << serial << "'";

		//cnm_printf("%s %d, query = .%s.\n", __FUNCTION__, __LINE__, query.str().c_str());
		
		try
		{
			query.exec();
			break;
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
	}while ( true );

	UnLock();
	return 0;
}

int CnmDB::UpdateUploadLimitDaySyncedSeq(char *serial, int seq)
{
	int   try_n = 1;

	if (false == CheckConnection())
		return -1;
	
	Lock();

	do
	{
		Query query = con.query();

		query << "UPDATE " << CNM_SERVER_DB << CENT_DEVICE_TABLE 
			<< " SET `upload_limit_day_synced_seq`=" << seq
			<< " WHERE `serial`='" << serial << "'";

		//cnm_printf("%s %d, query = .%s.\n", __FUNCTION__, __LINE__, query.str().c_str());
		
		try
		{
			query.exec();
			break;
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
	}while ( true );

	UnLock();
	return 0;
}

int CnmDB::UpdateBwlimitSyncedSeq(char *serial, int seq)
{
	int   try_n = 1;

	if (false == CheckConnection())
		return -1;
	
	Lock();

	do
	{
		Query query = con.query();

		query << "UPDATE " << CNM_SERVER_DB << CENT_DEVICE_TABLE 
			<< " SET `bwlimit_synced_seq`=" << seq
			<< " WHERE `serial`='" << serial << "'";

		//cnm_printf("%s %d, query = .%s.\n", __FUNCTION__, __LINE__, query.str().c_str());
		
		try
		{
			query.exec();
			break;
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
	}while ( true );

	UnLock();
	return 0;
}

int CnmDB::UpdateBaseSyncedSeq(char *serial, int seq)
{
	int   try_n = 1;

	if (false == CheckConnection())
		return -1;
	
	Lock();

	do
	{
		Query query = con.query();

		query << "UPDATE " << CNM_SERVER_DB << CENT_DEVICE_TABLE 
			<< " SET `base_synced_seq`=" << seq
			<< " WHERE `serial`='" << serial << "'";

		//cnm_printf("%s %d, query = .%s.\n", __FUNCTION__, __LINE__, query.str().c_str());
		
		try
		{
			query.exec();
			break;
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
	}while ( true );

	UnLock();
	return 0;
}

int CnmDB::UpdatePreviewSyncedSeq(char *serial, int seq, int status)
{
	int   try_n = 1;

	if (false == CheckConnection())
		return -1;
	
	Lock();

	do
	{
		Query query = con.query();

        if (seq)
        {
    		query << "UPDATE " << CNM_SERVER_DB << CENT_DEVICE_TABLE 
    			<< " SET `preview_synced_seq`=" << seq
    			<< " , `status_preview`=" << status
    			<< " WHERE `serial`='" << serial << "'";
        }
        else
        {
            query << "UPDATE " << CNM_SERVER_DB << CENT_DEVICE_TABLE 
    			<< " SET `status_preview`=" << status
    			<< " WHERE `serial`='" << serial << "'";
        }

		//cnm_printf("%s %d, query = .%s.\n", __FUNCTION__, __LINE__, query.str().c_str());
		
		try
		{
			query.exec();
			break;
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
	}while ( true );

	UnLock();
	return 0;
}

int CnmDB::UpdateLimitSyncedSeq(char *serial, int seq)
{
	int   try_n = 1;

	if (false == CheckConnection())
		return -1;
	
	Lock();

	do
	{
		Query query = con.query();

		query << "UPDATE " << CNM_SERVER_DB << CENT_DEVICE_TABLE 
			<< " SET `limits_synced_seq`=" << seq
			<< " WHERE `serial`='" << serial << "'";

		//cnm_printf("%s %d, query = .%s.\n", __FUNCTION__, __LINE__, query.str().c_str());
		
		try
		{
			query.exec();
			break;
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
	}while ( true );

	UnLock();
	return 0;
}

int CnmDB::SetDeviceOffline(char *serial)
{
	int   try_n = 1;

	if (false == CheckConnection())
		return -1;
	
	Lock();

	do
	{
		Query query = con.query();

		query << "UPDATE " << CNM_SERVER_DB << CENT_DEVICE_TABLE 
			<< " SET `device_ip`=0" 
			<< " WHERE `serial`='" << serial << "'";

		//cnm_printf("%s %d, query = .%s.\n", __FUNCTION__, __LINE__, query.str().c_str());
		
		try
		{
			query.exec();
			break;
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
	}while ( true );

	UnLock();
	
	AddEvent(serial, "", 11);
	return 0;
}


int CnmDB::UpdateDevice(struct cnm_client_info *client_info)
{
	int   try_n = 1;
	char version[sizeof("255.255.255.255")] = {0};
	int create_dir = 0;

	sprintf(version, "%d.%d.%d.%d",
		client_info->version & 0xff,
		(client_info->version >> 8) & 0xff,
		(client_info->version >> 16) & 0xff,
		(client_info->version >> 24) & 0xff);

	char conf[256] = { 0 };
	sprintf(conf, "%s%s", CONF_BASE_PATH, client_info->serial);

	if (access(conf, 0) != 0)
	{
		mkdir(conf, S_IRWXU);
		create_dir = 1;
	}

	snprintf(conf, sizeof(conf)-1, "%s/%s", 
		CNM_SERVER_PHOTOS_PATH, client_info->serial);

	if (access(conf, 0) != 0)
	{
		mkdir(conf, S_IRWXU);
		create_dir = 1;
	}

	snprintf(conf, sizeof(conf)-1, "%s/%s/raw", 
		CNM_SERVER_PHOTOS_PATH, client_info->serial);

	if (access(conf, 0) != 0)
	{
		mkdir(conf, S_IRWXU);
		create_dir = 1;
	}

	snprintf(conf, sizeof(conf)-1, "%s/%s/jpg", 
		CNM_SERVER_PHOTOS_PATH, client_info->serial);

	if (access(conf, 0) != 0)
	{
		mkdir(conf, S_IRWXU);
		create_dir = 1;
	}

	snprintf(conf, sizeof(conf)-1, "%s/%s/preview", 
		CNM_SERVER_PHOTOS_PATH, client_info->serial);

	if (access(conf, 0) != 0)
	{
		mkdir(conf, S_IRWXU);
		create_dir = 1;
	}
	
	if (create_dir)
	{
		sprintf(conf, "chmod 777 %s%s -R", CONF_BASE_PATH, client_info->serial);
		system(conf);
	}	

	if (false == CheckConnection())
		return -1;
	
	Lock();

	do
	{
		Query query = con.query();

		query << "UPDATE " << CNM_SERVER_DB << CENT_DEVICE_TABLE 
			<< " SET `aperture_current`='" << client_info->aperture_current << "'"
			<< " ,`shutter_current`='" << client_info->shutter_current << "'"
			<< " ,`iso_current`='" << client_info->iso_current << "'"
			<< " ,`aperture_range`='" << client_info->aperture_range << "'"
			<< " ,`shutter_range`='" << client_info->shutter_range << "'"
			<< " ,`iso_range`='" << client_info->iso_range << "'"
			<< " ,`last_photo_time`='" << client_info->last_photo_time << "'"
			<< " ,`next_photo_time`='" << client_info->next_photo_time << "'"
			<< " ,`electricity`=" << client_info->electricity
			<< " ,`humiture`='" << client_info->humiture << "'"
			<< " ,`temperature`='" << client_info->temperature << "'"
			<< " ,`camera_connection`=" << client_info->camera_connection
			<< " ,`version`='" << version << "'"
			<< " ,`day_flow_used`='" << client_info->today_used << "M'"
			<< " ,`device_ip`=" << client_info->cc_ip
			<< " ,`last_heardbeat_time`=date_format(now(), '%Y-%m-%d %H:%i:%s')"
			<< " WHERE `serial`='" << client_info->serial << "'";

		//cnm_printf("%s %d, query = .%s.\n", __FUNCTION__, __LINE__, query.str().c_str());
		
		try
		{
			query.exec();
			break;
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
	}while ( true );

	UnLock();
	
	if (client_info->add_online_log)
	{
		client_info->add_online_log = 0;
		AddEvent(client_info->serial, "", 10);
		
	}
	return 0;
}

void CnmDB::Lock()
{
    pthread_mutex_lock( &cs );
}

void CnmDB::UnLock()
{
    pthread_mutex_unlock( &cs );
}

bool  CnmDB::CreateTable( )
{
	Query query = con.query();

	query << "CREATE TABLE IF NOT EXISTS " << CNM_SERVER_DB << CENT_DEVICE_TABLE << "("
    	"  `serial` varchar(32) COLLATE utf8_bin DEFAULT '' COMMENT '设备唯一标识',"
		"  `version` varchar(64) COLLATE utf8_bin DEFAULT '' COMMENT '固件版本号',"
		"  `area` varchar(255) COLLATE utf8_bin DEFAULT '' COMMENT '安装地址',"
		"  `remark` text COLLATE utf8_bin COMMENT '备注',"
		"  `create_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',"
		"  `user_id` int(11) DEFAULT NULL COMMENT '直接管理员id',"
		"  `user_name` varchar(64) COLLATE utf8_bin NOT NULL,"
		"  `haffman_key` varbinary(64) NOT NULL,"
		"  `device_ip` int(11) unsigned DEFAULT '0' COMMENT '设备ip，以此判断设备是否在线',"
		"  `aperture_current` varchar(64) COLLATE utf8_bin DEFAULT '' COMMENT '当前A',"
		"  `shutter_current` varchar(64) COLLATE utf8_bin DEFAULT '' COMMENT '当前S',"
		"  `iso_current` varchar(64) COLLATE utf8_bin DEFAULT '' COMMENT '当前ISO',"
		"  `aperture_base` varchar(64) COLLATE utf8_bin DEFAULT '' COMMENT '基准A',"
		"  `shutter_base` varchar(64) COLLATE utf8_bin DEFAULT '' COMMENT '基准S',"
		"  `iso_base` varchar(64) COLLATE utf8_bin DEFAULT '' COMMENT '基准ISO',"
		"  `aperture_range` varchar(1024) COLLATE utf8_bin NOT NULL COMMENT 'A范围',"
		"  `shutter_range` varchar(1024) COLLATE utf8_bin NOT NULL COMMENT 'S范围',"
		"  `iso_range` varchar(1024) COLLATE utf8_bin NOT NULL COMMENT 'ISO范围',"
		"  `aperture_max` varchar(64) COLLATE utf8_bin NOT NULL COMMENT 'A自动拍摄范围最大值',"
		"  `aperture_min` varchar(64) COLLATE utf8_bin NOT NULL COMMENT 'A自动拍摄范围最小值',"
		"  `shutter_max` varchar(64) COLLATE utf8_bin NOT NULL COMMENT 'S自动拍摄范围最大值',"
		"  `shutter_min` varchar(64) COLLATE utf8_bin NOT NULL COMMENT 'S自动拍摄范围最小值',"
		"  `iso_max` varchar(1024) COLLATE utf8_bin NOT NULL COMMENT 'ISO自动拍摄范围最大值',"
		"  `iso_min` varchar(1024) COLLATE utf8_bin NOT NULL COMMENT 'ISO自动拍摄范围最小值',"
		"  `status_preview` int(1) unsigned NOT NULL DEFAULT '0' COMMENT '0 无动作 1 正在生成 2 已生成',"
		"  `aperture_preview` varchar(64) COLLATE utf8_bin NOT NULL COMMENT '预览A',"
		"  `shutter_preview` varchar(64) COLLATE utf8_bin NOT NULL COMMENT '预览A',"
		"  `iso_preview` varchar(64) COLLATE utf8_bin NOT NULL COMMENT '预览ISO',"
		"  `last_photo_time` varchar(64) COLLATE utf8_bin DEFAULT '' COMMENT '最后拍摄时间',"
		"  `next_photo_time` varchar(64) COLLATE utf8_bin DEFAULT '' COMMENT '下次拍摄时间',"
		"  `tasks_status` int(1) unsigned NOT NULL DEFAULT '0' COMMENT '1 开始拍摄 0 停止拍摄',"
		"  `upload_status` int(1) unsigned NOT NULL DEFAULT '0' COMMENT '1 停止上传 0 正常上传',"
		"  `disk_waring` int(1) unsigned NOT NULL DEFAULT '0' COMMENT '1 已告警 0 未告警',"
		"  `electricity` int(1) unsigned NOT NULL DEFAULT '0' COMMENT '市电状态 1 正常 0 异常',"
		"  `camera_connection` int(1) unsigned NOT NULL DEFAULT '0' COMMENT'相机连接状态 1 正常 0 异常',"
		"  `humiture` varchar(64) COLLATE utf8_bin DEFAULT '' COMMENT '湿度',"
		"  `temperature` varchar(64) COLLATE utf8_bin DEFAULT '' COMMENT '温度',"
		"  `auto_upgrade` int(1) unsigned NOT NULL DEFAULT '0' COMMENT '1自动升级 0不自动升级',"
		"  `last_heardbeat_time` TIMESTAMP NOT NULL COMMENT '最后一次心跳的时间', "
		"  `bwlimit` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '上次速率控制KB/s',"	
		"  `upload_limit_day` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '每天上传总量控制MB',"	
		"  `disk_size` int(16) unsigned NOT NULL DEFAULT '1000' COMMENT '磁盘大小，单位M',"	
		"  `tasks_sync_seq` int(1) unsigned NOT NULL DEFAULT '0' COMMENT '拍摄任务seq',"
		"  `tasks_synced_seq` int(1) unsigned NOT NULL DEFAULT '0' COMMENT '已经同步拍摄任务seq',"
		"  `base_sync_seq` int(1) unsigned NOT NULL DEFAULT '0' COMMENT 'base seq',"
		"  `base_synced_seq` int(1) unsigned NOT NULL DEFAULT '0' COMMENT '已经同步 base seq',"
		"  `limits_sync_seq` int(1) unsigned NOT NULL DEFAULT '0' COMMENT 'limits seq',"
		"  `limits_synced_seq` int(1) unsigned NOT NULL DEFAULT '0' COMMENT '已经同步 limits seq',"
		"  `preview_sync_seq` int(1) unsigned NOT NULL DEFAULT '0' COMMENT 'preview seq',"
		"  `preview_synced_seq` int(1) unsigned NOT NULL DEFAULT '0' COMMENT '已经同步 preview seq',"
		"  `bwlimit_sync_seq` int(1) unsigned NOT NULL DEFAULT '0' COMMENT '上传速率控制 KB seq',"
		"  `bwlimit_synced_seq` int(1) unsigned NOT NULL DEFAULT '0' COMMENT '已经同步 上传速率控制 KB seq',"
		"  `upload_limit_day_sync_seq` int(1) unsigned NOT NULL DEFAULT '0' COMMENT '每天上传控制 MB seq',"
		"  `upload_limit_day_synced_seq` int(1) unsigned NOT NULL DEFAULT '0' COMMENT '已经同步 每天上传控制 MB seq',"
		"  PRIMARY KEY (`serial`),"
		"  UNIQUE KEY `haffman_key` (`haffman_key`)"
	") ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;";
	
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

	query.reset();	
    query << "CREATE TABLE IF NOT EXISTS " << CNM_SERVER_DB << CENT_TASKS_TABLE << "("
    	"  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,"
		"  `type` int(1) unsigned NOT NULL DEFAULT '0' COMMENT '1 手动 0 自动',"
		"  `serial` varchar(32) COLLATE utf8_bin DEFAULT '' COMMENT '设备标识',"
		"  `user_id` int(11) DEFAULT NULL COMMENT '直接管理员id',"
		"  `user_name` varchar(64) COLLATE utf8_bin NOT NULL,"
		"  `aperture` varchar(64) COLLATE utf8_bin NOT NULL,"
		"  `shutter` varchar(64) COLLATE utf8_bin NOT NULL,"
		"  `iso` varchar(64) COLLATE utf8_bin NOT NULL,"
		"  `shooting_time` varchar(64) COLLATE utf8_bin NOT NULL,"
		"  `shooting_number` varchar(64) COLLATE utf8_bin NOT NULL,"
		"  `shooting_interval` varchar(64) COLLATE utf8_bin NOT NULL,"
		"  PRIMARY KEY (`id`)"
		") ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;";

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

	query.reset();
	query << "CREATE TABLE IF NOT EXISTS " << CNM_SERVER_DB << CENT_DEVICE_EVENT_LOG << "("
    	"  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,"
		"  `serial` varchar(32) COLLATE utf8_bin DEFAULT '' COMMENT '设备标识',"
		"  `event` varchar(256) COLLATE utf8_bin NOT NULL,"
		"  `user_id` int(11) DEFAULT NULL COMMENT '直接管理员id',"
		"  `time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,"
		"  PRIMARY KEY (`id`)"
		") ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;";

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

bool  CnmDB::CnmDBInit( )
{
	Query query = con.query();

	query << "CREATE DATABASE IF NOT EXISTS " << CNM_SERVER_DB;

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


