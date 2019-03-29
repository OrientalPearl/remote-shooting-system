#ifndef _CNM_VERSION_DB_H_
#define _CNM_VERSION_DB_H_

#include "mysql++.h"
#include <pthread.h>
#include "sky_common_macro.h"
#include "sky_common_struct.h"
#include "cnm_common.h"
#include "cnm_server.h"

#define CENT_VERSION_TABLE     ".`wrt_version`"

using namespace mysqlpp;

class CnmVersionDB
{
public:
	CnmVersionDB( void );
	~CnmVersionDB( void );
	int Init( void );
	int Timer (void);
    u32 GetSoftVersion(void);
    u32 GetX86SoftVersion(void);
    bool GetAutoUpdateEnable(void);
    u16 GetUpdateStartHour(void);
    u16 GetUpdateEndHour(void);
    u32 GetUpdateUpgradeLimit(void);
    
private:
    int SoftVersionUpdate(void);    
    int AlterX86Version(void);

private:
	Connection con;
	pthread_mutex_t  cs;  

	char  debug_str[256];

	char  mysql_host[128];
	char  mysql_user[36];
	char  mysql_passwd[36];
	short mysql_port;
	time_t timer_last_time;

    u32 x86_soft_version;
    u32 soft_version;
    char release_time[64];
    u16 start_hour;
    u16 end_hour;
    bool auto_update_enable;
    u32 upgrade_limit;
    
	void  Lock( );
	void  UnLock( );
	bool  GetConnection( );
	bool  CreateTable( );
};

#endif
