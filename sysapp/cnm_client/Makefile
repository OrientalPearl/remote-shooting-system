TARGET=cnm_client

CFLAGS=-Wall -O   -I../../include
CFLAGS2=-Wall -O -lpthread 

SRC=$(wildcard *.c)
OBJS=$(patsubst %.c,%.o,$(wildcard *.c))

$(TARGET):$(OBJS)
	$(CC) $(OBJS) $(CFLAGS2) -o $@

%.o: %.c
	$(CC) $(CFLAGS) -c $< -o $@

clean:
	rm -rf *.o $(TARGET) 

