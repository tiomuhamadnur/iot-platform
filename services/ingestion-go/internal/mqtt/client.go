package mqtt

import (
	"fmt"
	"log"
	"strings"

	mqtt "github.com/eclipse/paho.mqtt.golang"
)

type MQTTClient struct {
	client mqtt.Client
}

func NewMQTTClient(broker string, port int, username, password string) *MQTTClient {
	opts := mqtt.NewClientOptions()
	opts.AddBroker(fmt.Sprintf("tcp://%s:%d", broker, port))
	opts.SetUsername(username)
	opts.SetPassword(password)
	opts.SetClientID("ingestion-service")

	client := mqtt.NewClient(opts)
	if token := client.Connect(); token.Wait() && token.Error() != nil {
		log.Fatalf("Error connecting to MQTT: %v", token.Error())
	}

	return &MQTTClient{client: client}
}

func (m *MQTTClient) Subscribe(topic string, handler mqtt.MessageHandler) {
	if token := m.client.Subscribe(topic, 1, handler); token.Wait() && token.Error() != nil {
		log.Fatalf("Error subscribing to topic %s: %v", topic, token.Error())
	}
	fmt.Printf("Subscribed to topic: %s\n", topic)
}

func ExtractTopicInfo(topic string) (tenantID, deviceID string, err error) {
	// Topic format: tenant/{tenant_id}/device/{device_id}/data
	parts := strings.Split(topic, "/")
	if len(parts) < 5 {
		return "", "", fmt.Errorf("invalid topic format")
	}
	return parts[1], parts[3], nil
}
