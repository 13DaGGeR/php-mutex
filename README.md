Simple MutEx lock implementation. 

Gives you the easy way to check that your script runs as the only instance.

#### Basics
1. try to lock, if ok -> run your program, else stop
2. unlock when finished the job

#### Usage as php library

#### Usage as linux command
install:

    ./install
lock:

    mutex lock some_action
unlock:

    mutex unlock some_action


