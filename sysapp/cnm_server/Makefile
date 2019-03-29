TARGET=cnm_server
#CC=g++
CFLAGS=-Wall -O -I../../include/ -I./mysqlpp -I./mysql
CFLAGS2=-Wall -O  -lpthread -lssl -L./lib/ -lmysqlpp

SRC=$(wildcard *.cpp)
SRC+=$(wildcard *.c)
OBJS=$(patsubst %.cpp,%.o,$(wildcard *.cpp))
OBJS+=$(patsubst %.c,%.o,$(wildcard *.c))

all: $(TARGET) map

$(TARGET):$(OBJS)
	$(CXX) $(OBJS) $(CFLAGS2) -o $@

%.o: %.cpp
	$(CXX) $(CFLAGS) -c $< -o $@

clean:
	rm -rf *.o $(TARGET)
	
map:
	nm -e $(TARGET) | grep -v '\(compiled\)\|\(\.o$$\)\|\( [aUw] \)\|\(\.\.ng$$\)\|\(LASH[RL]DI\)' | sort > $(TARGET).map
