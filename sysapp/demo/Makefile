TARGET=demo

CC=gcc

SRC=$(wildcard *.c)
OBJS=$(patsubst %.c,%.o,$(wildcard *.c))

CFLAGS=-Wall -O -g -I/usr/include -I../../ace_include

$(TARGET):$(OBJS)
	$(CC) $(OBJS) $(CFLAGS2) -o $@

%.o: %.c
	$(CC) $(CFLAGS) -c $< -o $@

clean:
	rm -rf *.o $(TARGET)

