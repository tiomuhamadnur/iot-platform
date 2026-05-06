package main

import (
	"encoding/json"
	"fmt"
	"math/rand"
	"os"
	"sync"
	"time"

	mqtt "github.com/eclipse/paho.mqtt.golang"
)

func main() {
	numDevices := 100
	messagesPerDevice := 10
	mqttHost := os.Getenv("MQTT_HOST")
	if mqttHost == "" {
		mqttHost = "localhost"
	}
	mqttPort := os.Getenv("MQTT_PORT")
	if mqttPort == "" {
		mqttPort = "1883"
	}
	broker := fmt.Sprintf("tcp://%s:%s", mqttHost, mqttPort)

	var wg sync.WaitGroup
	wg.Add(numDevices)

	for i := 0; i < numDevices; i++ {
		go func(id int) {
			defer wg.Done()

			deviceID := fmt.Sprintf("device-%d", id)
			tenantID := "tenant-1"
			topic := fmt.Sprintf("tenant/%s/device/%s/data", tenantID, deviceID)

			opts := mqtt.NewClientOptions().AddBroker(broker).SetClientID(deviceID)
			client := mqtt.NewClient(opts)
			if token := client.Connect(); token.Wait() && token.Error() != nil {
				fmt.Printf("Error connecting device %d: %v\n", id, token.Error())
				return
			}
			defer client.Disconnect(250)

			for j := 0; j < messagesPerDevice; j++ {
				payload := map[string]interface{}{
					"device_time": time.Now().Format(time.RFC3339),
					"values": map[string]interface{}{
						"temperature": 20 + rand.Float64()*10,
						"humidity":    40 + rand.Float64()*20,
					},
				}
				payloadJSON, _ := json.Marshal(payload)
				client.Publish(topic, 0, false, payloadJSON)
				time.Sleep(1 * time.Second)
			}
		}(i)
	}

	wg.Wait()
	fmt.Println("Load simulation finished.")
}
