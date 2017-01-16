#!/usr/bin/env python

import time
import sys
import os
import string


filename = "/var/tmp/last_tinytx_log.txt"


def load_file_and_print(filename):
    f = open(filename, 'r')
    thefile = f.read().split('\n')
    f.close()

    ausgaben = {}
    os.system("clear")
    loctime = time.localtime(time.time())
    zeit = time.strftime('%a,%d.%m.%Y,%H:%M:%S', loctime)
    print zeit
    print "day |   date     |   time   | node | afc  | sig. | vcc  | temp | hum"
    print "----------------------------------------------------------------------"
    for line in thefile:
        sensor = line.split(',')
        if len(sensor) > 3:
            ausgabe = "%s | %s | %s" % (sensor[0], sensor[1], sensor[2])
            node = int(sensor[4])
            if node == 26:
                ausgabe = ausgabe = "%s | Labo" % (ausgabe)
            elif node == 1:
                ausgabe = ausgabe = "%s | Pool" % (ausgabe)
            elif node == 2:
                ausgabe = ausgabe = "%s |   9a" % (ausgabe)
            elif node == 3:
                ausgabe = ausgabe = "%s |  Gge" % (ausgabe)
            elif node == 17:
                ausgabe = ausgabe = "%s | Wozi" % (ausgabe)
            elif node == 27:
                ausgabe = ausgabe = "%s |  Bad" % (ausgabe)
            else:
                ausgabe = "%s | %4s" % (ausgabe, sensor[4])
            
            for i in range(6, len(sensor), 2):
                ausgabe = "%s | %4s" % (ausgabe, sensor[i])
            #print ausgabe
            try:
                node = int(sensor[4])
                ausgaben[node] = ausgabe
            except:
                pass


    for key in sorted(ausgaben.keys()):
        print ausgaben[key]
###############################################################################################
os.environ['TZ'] = 'Europe/Paris'
time.tzset()

secs = 0
if len(sys.argv) > 1:
    secs = int(sys.argv[1])

last_modified = os.path.getmtime(filename)        
load_file_and_print(filename)

while (secs>0):
    modified = os.path.getmtime(filename) 
    if modified > last_modified:
        last_modified = modified
        load_file_and_print(filename)
    time.sleep(secs)
        