#!/usr/bin/env python

# porttest5.py: decode binary format and clear text format. readline() does not work anymore as \n can be part of the payload. stable version.
# porttest6.py: indicate if binary or clear text format was detected (for debug purposes only)
# porttest7.py: use ISO week for file name generation, not python style week number
# porttest8.py: Error log and last log go into temporary tmpfs /var/tmp
# porttest9.py: don't log erroneous lines if status register does not contain DQD or CRL
# prttest10.py: experimental build for FEC decoding

# rename to tiny_receive.py
# 

import serial
import time, datetime
import sys
import os
import string
import struct

def record_filename(loctime):
    #th_week = int ( time.strftime('%W', loctime) ) + 1
    th_week= datetime.date.fromtimestamp(time.time()).isocalendar()[1]
    if th_week > 9:
        filename = '%s%s_%i_tinyrx_log.csv' % ( verzeichnis, time.strftime('%Y', loctime), th_week )
    else:
        filename = '%s%s_0%i_tinyrx_log.csv' % ( verzeichnis, time.strftime('%Y', loctime), th_week )
    return filename

def write_logfile(logfilename, msg):
    logfile=open(logfilename,'a')
    logfile.write(msg)
    logfile.write('\n')
    logfile.close()
    
def extract_data(key, results):
    key_result_pair =''
    if key in results.keys():
        key_result_pair = ",%s,%s" % (key , str(results[key]))
    return key_result_pair

hamming_codes = (0x15, 0x02, 0x49, 0x5e, 0x64, 0x73, 0x38, 0x2f, 0xd0, 0xc7, 0x8c, 0x9b, 0xa1, 0xb6, 0xfd, 0xea)
    
#decode a byte and return a decoded nibble - if possible
def decode_hamming(byte, codes = hamming_codes):
    if byte in codes:
        decoded_val = codes.index(byte)
        return decoded_val, 0
    else:
        solution = 0
        for code in codes:
            if bin(code ^ byte).count("1") <= 1:
                return codes.index(code), 1
        if solution == 0:
            return -1, 2

            

#decode an int and return a decoded byte
def decode_hamming_int (intval, codes = hamming_codes):
    biterrors = 0
    lownibble, biterror = decode_hamming(intval &0xff, codes)
    biterrors += biterror
    if biterror > 1:
        return -1, biterrors
    
    hinibble, biterror = decode_hamming(intval>>8, codes)
    biterrors += biterror
    if biterror > 1:
        return -1, biterrors
    
    return (hinibble<<4 | lownibble), (biterrors)


#get 2 ints from struct.unpack and calculate the value (16 bit int)    
def decode_hamming_2ints(int_tuple, codes = hamming_codes):
    biterrors =0
    highbyte, biterror  =  decode_hamming_int (int_tuple[0]&0xffff, codes)
    biterrors += biterror
    lowbyte, biterror  =  decode_hamming_int (int_tuple[1]&0xffff, codes)
    biterrors += biterror
    
    return highbyte<<8 | lowbyte, biterrors

#give me a string with 3 bytes and decode it into a 12-bit number
def decode_hamming_3_bytes(payload, codes = hamming_codes):
    val=0
    biterrors_total =0
    biterrors_max_per_byte =  0
    for i in range(0,3):
        val <<=4
        dec_val, biterrors = decode_hamming (ord(payload[i]), codes) # high byte, low nibble
        val = val | dec_val
        biterrors_total += biterrors
        if biterrors > biterrors_max_per_byte:
            biterrors_max_per_byte = biterrors
        
    return val, biterrors_total, biterrors_max_per_byte


def read_from_port (port):
    raw_line = port.read(port.inWaiting())
    if '\r\n' not in raw_line[-2:]:
        done = False
        while not done:
            if port.inWaiting() > 0:
                crlf = port.read(port.inWaiting())
                raw_line += crlf
                if '\r\n' in raw_line[-2:]: done = True
            else:
                time.sleep(.001) # 1 ms
    line = raw_line[:-2]
    return line

    
def decode_rfm12b_afc_drssi(sreg):
    afc= sreg & 0xf
    if sreg & 0x10:
        afc=-((afc^0xf)+1) # 2's complement
    return "a,%s,s,%s" % (afc, sreg>>8) #drssi bit
    
os.environ['TZ'] = 'Europe/Paris'
time.tzset()


#default filename for valid records
filename = "tinyRx_record.csv"

#filename for error packets
errorlogfile = "tinyRx_error.csv"

#filename for the last log
last_log_filename = 'last_tinytx_log.txt'

#default directory for logfiles
verzeichnis = '/var/www/logfiles/'

#default directory for temporary files
tmp_verzeichnis = '/var/tmp/'



#Oeffne Seriellen Port
port = serial.Serial('/dev/ttyAMA0',9600)

# timeout?
if not port.isOpen():
    print "cannot connect to serial port."
    sys.exit(1)

results={}
last_log_dict= {}

#fill last_log_dict with existing entries
fn = '%s%s' % (tmp_verzeichnis, last_log_filename)
f = open(fn, 'r')
thefile = f.read().split('\n')
f.close()
for line in thefile:
    sensor = line.split(',')
    if len(sensor) > 3:
        last_log_dict[sensor[4]] = line
        
#Main loop
while (True):    
    if port.inWaiting() > 0:
        time.sleep(0.1)
        line = read_from_port(port)
    else:
        time.sleep(0.1) # wait 100ms 
        continue
        
    #print line
    loctime = time.localtime(time.time())
    zeit = time.strftime('%a,%d.%m.%Y,%H:%M:%S', loctime)

    results.clear()

    isvalid = False
    cleartext = False

    # some experimental code 
    if "BAD-CRC," in line:
        msg = line.split(',')
        node =    msg[1]
        statreg = int(msg[2][2:], 16)
        payload = msg[3][5:] # format is : "data=xxyyzz" but we only want "xxyyzz"
    else:
        i= string.find(line, " ")
        if i>0:
            node = line[:i]
            msg  = line[i+1:]
            isvalid  = True
            if "v=" in msg and "&t=" in msg:
                cleartext = True
            i = string.rfind(msg, '&s=')  # i points to '&' in '&s='
            statreg = int(msg[i+3:], 16)
            payload = msg[:i]
        else:
            node ='0'
            msg=''
            print "no space in string:"
            continue

    logstring = "%s,n,%s,%s" % (zeit, node, decode_rfm12b_afc_drssi(statreg))
    is_valid_node = False
    
    if isvalid and cleartext:
        is_valid_node = True
        # old verbose output, php compatible
        vals = payload.split('&')
        for messung in vals: # build dictionary
            itemlst = messung.split('=')
            if len(itemlst) > 1:
                results[itemlst[0]] = itemlst[1]
                
        logstring = "%s%s%s%s%s" % (logstring, extract_data('v', results),
        extract_data('t', results),
        extract_data('h', results),
        extract_data('c', results))
        #print "LogString: %s" % (logstring)

    elif not cleartext:
        if node == '3' or node =='26':
            is_valid_node = True

        # binary mode: don't care if CRC valid or not
        datalen = len(payload) 
        print "Datenlaenge: %d" % datalen
        
        biterrors_total = 0
        biterrors_max_per_byte =0
        
        if datalen >=3:
            vcc, biterrors_total, biterrors_max_per_byte = decode_hamming_3_bytes(payload)
            #print "        VCC:  %i, %i biterrors" % (vcc, biterrors_total)
            logstring = "%s,v,%s" % (logstring, vcc)
            
        if datalen >=6:
            temp, biterrors, biterrors_max_pb = decode_hamming_3_bytes(payload[3:])
            biterrors_total += biterrors
            if biterrors_max_pb > biterrors_max_per_byte:
                biterrors_max_per_byte = biterrors_max_pb
            temp = (temp-1000)*4
            #print "Temperature:  %i, %i biterrors" % (temp, biterrors_total)
            logstring = "%s,t,%s" % (logstring, temp)
            
        if datalen >=8:
            hum=0
            for i in range(6,8):
                hum <<=4
                dec_val, biterrors = decode_hamming (ord(payload[i])) # high byte, low nibble
                hum = hum | dec_val
                biterrors_total += biterrors
                if biterrors > biterrors_max_per_byte:
                    biterrors_max_per_byte = biterrors
            hum = hum * 50
            #print "   Humidity:  %i, %i biterrors" % (hum, biterrors_total)
            logstring = "%s,h,%s" % (logstring, hum)
        if isvalid:
            if biterrors_max_per_byte == 0:
                logstring += ",f,OK"
            if biterrors_max_per_byte > 0:     # does happen when we remove CRC completely
                logstring += ",f,%i" % biterrors_total
        else:
            if biterrors_max_per_byte ==0: # BAD CRC but decoding was successful - the error is in the CRC!
                logstring += ",f,0"
            if 2 > biterrors_max_per_byte and biterrors_max_per_byte > 0:
                logstring += ",f,%i" % biterrors_total
            else:
                logstring += ",f,fail,%i" % biterrors_total
                
    print "LogString: %s" % logstring
    if is_valid_node:
        write_logfile(record_filename(loctime), logstring)
        
        #update file with latest records for each node
        last_log_dict[node] = logstring
        fn = '%s%s' % (tmp_verzeichnis, last_log_filename)
        last_log = open(fn,'w')
        for key in last_log_dict:
            last_log.write(last_log_dict[key])
            last_log.write('\n')
        last_log.close()
    else: 
        if statreg & 0xC0:
            errstr= "%s,%s" % (zeit,line)
            logfilename = '%s%s' % (tmp_verzeichnis, errorlogfile) 
            write_logfile(logfilename, errstr)
        else:
            pass # do nothing, forget received line
'''
    #look for BAD-CRC message, extract node and message
    if "BAD-CRC," not in line:
        i= string.find(line, " ")
        if i>0:
            node = line[:i]
            msg  = line[i+1:]
        else:
            node ='0'
            msg=''
            print "no space in string:"
            for sign in line:
                print ord(sign)
            continue
        s = "%s,n,%s" % (zeit, node)  

        # decide if clear text format or a binary format is received
        if "v=" in msg and "&t=" in msg: #clear text transmission format
            vals = msg.split('&')
            for messung in vals:
                itemlst = messung.split('=')
                if len(itemlst) > 1:
                    results[itemlst[0]] = itemlst[1]
            messungen = results.keys()

            if 's' in messungen: 
                si=int(results['s'], 16)
                afc= si&0xf
                if si&0x10:
                    afc=-((afc^0xf)+1) # 2's complement
                s = "%s,a,%s,s,%s" % (s, afc, si>>8) #drssi bit

            s = "%s%s" % (s, extract_data('v', results))
            s = "%s%s" % (s, extract_data('t', results))
            s = "%s%s" % (s, extract_data('h', results))
            s = "%s%s" % (s, extract_data('c', results))

        else: #binary format
            #binary format looks like this:<node><space><message>&s=<status_reg>
            #split off the status register which is transmitted in clear text.
            try:
                i = string.rfind(msg, '&s=')  # i points to '&' in '&s='
                stat_reg_str= msg[i+3:]
                if len(stat_reg_str) > 0:
                    si=int(stat_reg_str,16)
                    afc= si&0xf            #extract afc
                    if si&0x10: 
                        afc=-((afc^0xf)+1) # 2's complement
                    s = "%s,a,%s,s,%s" % (s, afc, si>>8) # extract drssi bit
                else:
                    print "no status byte detected"
                payload = msg[:i]
                results_b = list()
                datalen = len(payload) 
                print "Datenlaenge: %d" % datalen
                
                if datalen >=3:
                    print hex(ord(payload[0])), hex(ord(payload[1])), hex(ord(payload[2]))
                    vcc, biterrors_total, biterrors_max_per_byte_vcc = decode_hamming_3_bytes(payload)

                    print "        VCC:  %i, %i biterrors" % (vcc, biterrors_total)
                    s = "%s,v,%s" % (s,vcc)
                    
                if datalen >=6:
                    print hex(ord(payload[3])), hex(ord(payload[4])), hex(ord(payload[5]))
                    temp, biterrors_total, biterrors_max_per_byte_vcc = decode_hamming_3_bytes(payload[3:])

                    temp = (temp-1000)*4
                    print "Temperature:  %i, %i biterrors" % (temp, biterrors_total)
                    s = "%s,t,%s" % (s, temp)
                
                if datalen >=8:
                    print hex(ord(payload[6])), hex(ord(payload[7]))
                    hum=0
                    
                    for i in range(6,8):
                        hum <<=4
                        dec_val, biterrors = decode_hamming (ord(payload[i])) # high byte, low nibble
                        hum = hum | dec_val
                        biterrors_total += biterrors

                    hum = hum * 50
                    print "   Humidity:  %i, %i biterrors" % (hum, biterrors_total)
                    s = "%s,h,%s" % (s,hum)
                    
                s = "%s,f,ok" % s
            except:
                logfilename = '%s%s' % (tmp_verzeichnis, errorlogfile) 
                errstr = "%s,%s,%s,%s" % (s, sys.exc_info()[0], sys.exc_info()[1], sys.exc_info()[2])
                #write into log file
                write_logfile(logfilename, errstr)
                
        #write_logfile(record_filename(loctime), s)
        
        last_log_dict[node] = s
        fn = '%s%s' % (tmp_verzeichnis, last_log_filename)
        last_log = open(fn,'w')
        for key in last_log_dict:
            last_log.write(last_log_dict[key])
            last_log.write('\n')
        last_log.close()

    else: #BAD CRC
        #extract status register
        msg = line.split(',')
        statreg = int(msg[2][2:], 16)
        node =    msg[1]
        payload = msg[3][5:] # format is : "data=xxyyzz" but we only want "xxyyzz"

        if node == '3' or node =='26': #FEC encoded node with error, so we try to correct it
            # string looks like this: "BAD CRC,<node>,s=0xXY,data=xxxyyyzzz..."
            print "BAD-CRC - try to recover data from FEC codes"
            afc= statreg&0xf            #extract afc
            if statreg&0x10: 
                afc=-((afc^0xf)+1) # 2's complement
            s="%s,n,%s,a,%s,s,%s" %(zeit, node, afc, statreg>>8)
            
            datalen = len(payload)
            print "Datenlaenge: %d" % datalen
            biterrors_total =0
            biterrors_per_byte_vcc_max =0
            biterrors_per_byte_temp_max =0
            biterrors_per_byte_hum_max= 0
            
            if datalen >=3:
                print hex(ord(payload[0])), hex(ord(payload[1])), hex(ord(payload[2]))
                vcc=0
                
                for i in range(0,3):
                    vcc <<=4
                    dec_val, biterrors = decode_hamming (ord(payload[i]), hamming_codes) # high byte, low nibble
                    vcc = vcc | dec_val
                    biterrors_total += biterrors
                    if biterrors > biterrors_per_byte_vcc_max:
                        biterrors_per_byte_vcc_max = biterrors
                print "VCC calculated:  %i, %i biterrors, max %i biterrors in a byte" % (vcc, biterrors_total, biterrors_per_byte_vcc_max)
                s = "%s,v,%s" % (s,vcc)
                    
            if datalen >=6:
                print hex(ord(payload[3])), hex(ord(payload[4])), hex(ord(payload[5]))
                temp=0
                #biterrors_total =0
                for i in range(3,6):
                    temp <<=4
                    dec_val, biterrors = decode_hamming (ord(payload[i]), hamming_codes) 
                    temp = temp | dec_val
                    biterrors_total += biterrors
                    if biterrors > biterrors_per_byte_temp_max:
                        biterrors_per_byte_temp_max = biterrors
                temp = (temp-1000)*4
                print "Temperature:  %i, %i total biterrors, max %i biterrors in a byte" % (temp, biterrors_total, biterrors_per_byte_temp_max)   
                s = "%s,t,%s" % (s, temp)
            
            if datalen >=8:
                print hex(ord(payload[6])), hex(ord(payload[7]))
                hum=0
                
                for i in range(6,8):
                    hum <<=4
                    dec_val, biterrors = decode_hamming (ord(payload[i]), hamming_codes) # high byte, low nibble
                    hum = hum | dec_val
                    biterrors_total += biterrors
                    if biterrors > biterrors_per_byte_hum_max:
                        biterrors_per_byte_hum_max = biterrors
                hum = hum * 50
                print "Humidity:  %i, %i total biterrors, max %i biterrors in a byte" % (hum, biterrors_total, biterrors_per_byte_hum_max)
                s = "%s,h,%s" % (s,hum)
            
            if biterrors_per_byte_temp_max > 1 or biterrors_per_byte_vcc_max > 1 or biterrors_per_byte_hum_max > 1:
                #error!
                logfilename = '%s%s' % (tmp_verzeichnis, errorlogfile)
                s = "%s,f,fail" % s
                #write_logfile(logfilename, s)
            else:
                s = "%s,f,%i" % (s, biterrors_total)
                #write_logfile(record_filename(loctime),s)
                
                last_log_dict[node] = s
                fn = '%s%s' % (tmp_verzeichnis, last_log_filename)
                last_log = open(fn,'w')
                for key in last_log_dict:
                    last_log.write(last_log_dict[key])
                    last_log.write('\n')
                last_log.close()
                
        #store erroneous line, but only if CRL and/or DQD are set - if none is set, the data are total crap, not worth storing
        else: #node is not 3, so its not a FEC encoded message
            s= "%s,%s" % (zeit,line)
            if statreg & 0xC0:
                logfilename = '%s%s' % (tmp_verzeichnis, errorlogfile) 
                #write_logfile(logfilename, s)

            else:
                print "the following line wasn't stored!"
       
    
    print s
   
'''