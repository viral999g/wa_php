import websocket
import json
import time
import binascii
from Crypto import Random

try:
    import thread
except ImportError:
    import _thread as thread

def authProcess(ws, user_mobile):
    remoteJid = user_mobile + "@s.whatsapp.com"
    request_body = ["auth", {"type": "new"}, {"remoteJid":remoteJid}]
    ws.send(json.dumps(request_body))

def on_message(ws, message):
    print(message)



def on_error(ws, error):
    print(error)


def on_close(ws):
    print("### closed ###")




def sendTextMessage(ws, to_user, message):
    messageId = "3EB0"+str(binascii.hexlify(Random.get_random_bytes(8)).upper().decode("utf-8"))
    request_body = ["action", {"add": "relay"}, [{"message": {"conversation": message}, "key": {
        "remoteJid": to_user, "fromMe": True, "id": messageId}, "messageTimestamp": str(int(time.time()))}]]
    ws.send(json.dumps(request_body))

def sendTextMessageToGroup(ws, to_group, message):
    messageId = "3EB0"+str(binascii.hexlify(Random.get_random_bytes(8)).upper().decode("utf-8"))
    request_body = ["action", {"add": "relay"}, [{"message": {"conversation": message}, "key": {
        "remoteJid": to_group, "fromMe": True, "id": messageId}, "messageTimestamp": str(int(time.time()))}]]
    ws.send(json.dumps(request_body))

def addmember(ws):
    ts =  str(int(time.time()))

    request_body = [ 
        "Chat", 
        {
            "cmd" : "action",
            "data" : [ 
                "add", 
                "917069852821@c.us", 
                {
                    "participants" : [ 
                        "919971033011@c.us",
                        "919971033012@c.us",
                        "919971033013@c.us",
                        "919971033014@c.us",
                    ]
                }
            ],
            "id" : "917069852821-1569845878@g.us"
        }
    ]

    ws.send(json.dumps(request_body))


def createGroup(ws):
    ts =  str(int(time.time()))
    request_body = [
  "Chat",
  {
    "cmd": "action",
    "data": [
      "create",
      "917069852821@c.us",
      {
        "admins": [
          "917069852821@c.us"
        ],
        "creation": ts,
        "regulars": [
          "919428284313@c.us",
          "917069852822@c.us"
        ],
        "s_o": "917069852821@c.us",
        "s_t": ts,
        "subject": "New Group 4",
        "superadmins": [
          "917069852821@c.us"
        ]
      }
    ],
    "id": "917069852821-" + str(ts) + "@g.us"
  }
]

    ws.send(json.dumps(request_body))

def create_profile(ws):
  for i in range(1, 5):
    request_body = ["create_profile", "www.whatsapp.com", str(i)]
    ws.send(json.dumps(request_body))

def send_presence(ws):
  for i in range(1, 5):
    request_body = ["Presence", "942828431" + str(i), str(i), "1"]
    ws.send(json.dumps(request_body))

def get_user_list(ws):
  request_body = ["get_users", "https://web.whatsapp.com", "1"]
  ws.send(json.dumps(request_body))

def on_open(ws):
    def run(*args):
        authProcess(ws, "917069852821")
        # sendTextMessageToGroup(ws, "917069852821-1567074911@g.us", "Test msg Group")
        # sendTextMessage(ws, "919428284313@s.whatsapp.com", "Test msg")
        # createGroup(ws)
        # addmember(ws)
        send_presence(ws)
        # get_user_list(ws)
        # create_profile(ws)
        print("thread terminating...")
    thread.start_new_thread(run, ())


def on_auth(ws):
    print(auto)


if __name__ == "__main__":
    websocket.enableTrace(True)
    ws = websocket.WebSocketApp("ws://localhost:9011/",
                                on_message=on_message,
                                on_error=on_error,
                                on_close=on_close)
    # ws.on_auth = on_auth
    ws.on_open = on_open
    ws.run_forever()
