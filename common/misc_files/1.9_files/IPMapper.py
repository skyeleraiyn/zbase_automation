import Constants 
import asyncore, socket
import logging
import simplejson as json

class Connector(asyncore.dispatcher):

        def __init__(self,mySock):
                asyncore.dispatcher.__init__(self,sock=mySock)
                self.writeBuf = ""
                self.otherConnection = ""
                self.closed = False

        def setRemote(self, obj):
                self.otherConnection = obj

        def writeable(self):
                if len(writeBuf):
                        return True
                else:
                        return False

        def handle_write(self):
                self.send(self.writeBuf)
                self.writeBuf = ""

        def handle_read(self):
                data = self.recv(1024)
                self.otherConnection.writeBuf = data

        def handle_close(self):
                if self.closed == False:
                        self.close()
                        self.closed = True
                        self.otherConnection.handle_close()
                return False


class IPMapper(asyncore.dispatcher):

	def __init__(self):
		self.Logger = logging.getLogger('IPMAPPER')
		self.Logger.setLevel(logging.DEBUG)
		
		handler = logging.FileHandler(Constants.IPMAPPER_LOG_FILE,'w')
		handler.setLevel(logging.DEBUG)
		formatter = logging.Formatter('%(asctime)s - %(name)s - %(levelname)s - %(message)s')
		handler.setFormatter(formatter)
		self.Logger.addHandler(handler)
						
		self.mapList = {}
	
		self.moxiStart = int(Constants.MOXI_START_PORT)
			
		self.vbaSeq = int(Constants.VBA_START_IP)
		self.moxiSeq = int(Constants.MOXI_START_IP)

		self.Logger.debug("Started IPMapper")
		asyncore.dispatcher.__init__(self)
		self.create_socket(socket.AF_INET,socket.SOCK_STREAM)
		self.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
		self.bind(('',Constants.IPMAPPER_PORT))
		self.listen(5)

	def handle_accept(self):
		TesttoIP, addr = self.accept()
		IPtoVBS = socket.socket(socket.AF_INET,socket.SOCK_STREAM)
		
		if(addr[1] < self.moxiStart):
			bindIP = '127.0.0.' + str(self.vbaSeq)
			self.vbaSeq += 1
		else:
			bindIP = '127.0.0.' + str(self.moxiSeq)
			self.moxiSeq += 1

		IPtoVBS.bind((bindIP,0))
		IPtoVBS.connect(('127.0.0.1',Constants.VBS_PORT))

		log = "Started " + str(bindIP) + " TO vbs for Port " + str(addr[1])
		self.Logger.debug(log)

		self.mapList[str(addr[1])] = bindIP

		mapfile = open(Constants.IPMAPPER_MAP_FILE,'w')
		mapfile.write(json.dumps(self.mapList))
		mapfile.close()

		TesttoIPobj = Connector(TesttoIP)
		IPtoVBSobj = Connector(IPtoVBS)

		TesttoIPobj.setRemote(IPtoVBSobj)
		IPtoVBSobj.setRemote(TesttoIPobj)

if __name__ == '__main__':
	start = IPMapper()
	asyncore.loop()
