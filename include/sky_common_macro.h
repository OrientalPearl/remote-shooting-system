#ifndef _SKY_COMMON_MACRO_H_
#define _SKY_COMMON_MACRO_H_

#if defined(__KERNEL__)

#else
#include <stdio.h>
#include <stdlib.h>
#include <syslog.h>

#ifndef s8
typedef signed char s8;
#endif

#ifndef u8
typedef unsigned char u8;
#endif

#ifndef s16
typedef signed short s16;
#endif

#ifndef u16
typedef unsigned short u16;
#endif

#ifndef s32
typedef signed int s32;
#endif

#ifndef u32
typedef unsigned int u32;
#endif

#ifndef s64
typedef signed long long s64;
#endif

#ifndef u64
typedef unsigned long long u64;
#endif

#endif

#ifndef __KERNEL__
struct sky_list_head {
	struct sky_list_head *next, *prev;
};

#define LIST_SEARCH(head, cmpfn, type, args...)		\
({							\
	const struct sky_list_head *__i = (head);		\
							\
	do {						\
		__i = __i->next;			\
		if (__i == (head)) {			\
			__i = NULL;			\
			break;				\
		}					\
	} while (!cmpfn((const type)__i , ## args));	\
	(type)__i;					\
})

static inline void SKY_INIT_LIST_HEAD(struct sky_list_head *list)
{
    list->next = list;
    list->prev = list;
}

#define LIST_EACH(pos, head) \
    for (pos = (head)->next; pos != (head); pos = pos->next)

#define nt_list_for_each_safe(pos, n, head) \
    for (pos = (head)->next, n = pos->next; pos != (head); pos = n, n = pos->next)

static inline void __sky_list_add(struct sky_list_head *newone,
                  struct sky_list_head *prev,
                  struct sky_list_head *next)
{
    next->prev = newone;
    newone->next = next;
    newone->prev = prev;
    prev->next = newone;
}

static inline void list_add(struct sky_list_head *newone, struct sky_list_head *head)
{
    __sky_list_add(newone, head->prev, head);
}

static inline void __sky_list_del(struct sky_list_head * prev, struct sky_list_head * next)
{
	next->prev = prev;
	prev->next = next;
}

static inline void list_del(struct sky_list_head *entry)
{
	__sky_list_del(entry->prev, entry->next);
	entry->next = 0;
	entry->prev = 0;
}

#define list_for_each_safe(pos, pnext, head) \
	for (pos = (head)->next, pnext = pos->next; pos != (head); \
	     pos = pnext, pnext = pos->next)
#endif



#define sky_printf( fmt... ) syslog( LOG_USER | LOG_INFO,  fmt )

#define SKY_NETLINK           30

typedef int   SKY_STATUS;
#define SKY_ERROR              -1
#define SKY_OK                    0

#define MAX_WEBCHAT_SERVER_IP_NUM 20

enum _nl_id_eum
{
	SKY_NL_TEST                                                 =1,
	SKY_NL_CNM_SET_PID,
	SKY_NL_CNM_SET_WEBCHAT_SERVER_IP,
};

#define NMR_FTOK_FILE      "/usr/private/cnm_server"
#define NMR_IPC_MSG_TYPE   1

typedef enum _nmr_msg_type
{
	NMR_MSG_ONLINE = 1,
    NMR_MSG_OFFLINE,
    NMR_MSG_MAX
}nmr_msg_type_t;

#define CONF_BASE_PATH "/var/www/html/conf/"
//#ifdef DEBUG
#if 0
#define SERVER_DOMAIN "172.16.20.94:9090"
#else
#define SERVER_DOMAIN "www.ysf-tech.com.cn"
#endif

#endif

