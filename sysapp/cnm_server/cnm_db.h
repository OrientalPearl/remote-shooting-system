#ifndef _CNM_DB_H_
#define _CNM_DB_H_

#include "mysql++.h"
#include <pthread.h>
#include "sky_common_macro.h"
#include "sky_common_struct.h"
#include "cnm_common.h"
#include "cnm_server.h"

#define CENT_DEVICE_TABLE     ".`wrt_ysf_device`"
#define CENT_TASKS_TABLE      ".`wrt_ysf_task`"
#define CENT_DEVICE_EVENT_LOG ".`wrt_ysf_event_log`"

using namespace mysqlpp;

class CnmDB
{
public:
	CnmDB( void );
	~CnmDB( void );
	int Init( void );
	int Timer (void);
    void DiskCheckTimer(void);
    bool CheckConnection(void);
	int UpdateDevice(struct cnm_client_info *client_info);
    int AddDevice(const char *serial);
    int GetAutoUpgrade(const char *serial);
    int GetBwlimit(const char *serial);
    int GetConfig(const char *serial, int *bwlimit, int *upload_limit_day, int *upload_status);
    int UpdateTaskSyncedSeq(char *serial, int seq); 
    int UpdateLimitSyncedSeq(char *serial, int seq);
    int UpdateBaseSyncedSeq(char *serial, int seq);
    int UpdateBwlimitSyncedSeq(char *serial, int seq);
    int UpdateUploadLimitDaySyncedSeq(char *serial, int seq);
    int UpdatePreviewSyncedSeq(char *serial, int seq, int status);
    int GetEventStatus(const char *serial, int *electricity, int *camera_connection);
    int SetDeviceOffline(char *serial);
    int GetUploadStatus(const char *serial);
    void AddEvent(const char *serial, const char *event, int err_code);
    int GetTasksStatus(const char *serial);
    void UpdatePreview(void);
private:
    int InitDevice(void);
    bool CnmDBInit( );
    int CfgUpdateTimer(void);

    void  Lock( );
	void  UnLock( );
	bool  GetConnection( );
	bool  CreateTable( );
    
private:
	Connection con;
	pthread_mutex_t  cs;

	char  debug_str[256];

	char  mysql_host[128];
	char  mysql_user[36];
	char  mysql_passwd[36];
	short mysql_port;
	time_t timer_last_time;
    time_t disk_timer_last_time;
};

#endif
