TARGET=appmonitor
PWD       := $(shell pwd)

CXX=g++

CFLAGS=-Wall -O

ifeq ($(client),1)
CFLAGS += -DCNM_CLIENT
endif

CFLAGS2= -lpthread

SRC=$(wildcard *.cpp)
OBJS=$(patsubst %.cpp,%.o,$(wildcard *.cpp))

$(TARGET):$(OBJS)
	$(CXX) $(OBJS) $(CFLAGS2) -o $@

%.o: %.cpp
	$(CXX) $(CFLAGS) -c $< -o $@

clean:
	rm -rf *.o $(TARGET)

