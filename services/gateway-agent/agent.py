import time
import json
import paho.mqtt.client as mqtt
from pymodbus.client import ModbusTcpClient
import os
from dotenv import load_dotenv

load_dotenv(dotenv_path='../../.env')

MQTT_HOST = os.getenv("MQTT_HOST", "localhost")
MQTT_PORT = int(os.getenv("MQTT_PORT", 1883))
TENANT_ID = "tenant-1"
DEVICE_ID = "gateway-1"

# Modbus Configuration
MODBUS_HOST = "localhost"
MODBUS_PORT = 502

client = mqtt.Client()

def on_connect(client, userdata, flags, rc):
    print(f"Connected to MQTT broker with result code {rc}")

client.on_connect = on_connect
client.connect(MQTT_HOST, MQTT_PORT, 60)
client.loop_start()

modbus_client = ModbusTcpClient(MODBUS_HOST, port=MODBUS_PORT)

while True:
    if modbus_client.connect():
        # Read holding registers 0-9
        result = modbus_client.read_holding_registers(0, 10)
        if not result.isError():
            data = {
                "device_time": time.strftime("%Y-%m-%dT%H:%M:%SZ", time.gmtime()),
                "values": {
                    f"register_{i}": val for i, val in enumerate(result.registers)
                }
            }
            topic = f"tenant/{TENANT_ID}/device/{DEVICE_ID}/data"
            client.publish(topic, json.dumps(data))
            print(f"Published to {topic}: {data}")
        modbus_client.close()
    
    time.sleep(5)
