# TinyTxRx
My own library to use TinyTx nodes, receive packets on Raspberry Pi and store them into a db.
The nodes used are from this german raspberry pi forum project:
http://www.forum-raspberrypi.de/Thread-messen-steuern-regeln-batteriebetriebene-funk-sensoren

The TX nodes are called "TinyTx4"
The RX nodes are called "TinyRX4"

The aim of this library is to provide:
- full compatibility code for RFM12B AND RFM69CW RF modules on the same network
- The Receive nodes will be using RFM69CW RF modules
- The RF driver will be using binary encoding of data
- The data are additionally FEC encoded (forward eror correction) instead of CRC
- the data decoding is done by the receiving node.
- a python script running 24/7 on a raspberry pi which is connected to a TinyTx4 node (sic!) configured as receiver
- received data are written into a simple data base. 
- a python script that visualises the latest temperature and humidity data
- a small web site project to access the data and paint graphs in the web browser, using the jpgraph library
