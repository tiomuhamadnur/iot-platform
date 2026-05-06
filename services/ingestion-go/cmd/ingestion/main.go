package main

import (
	"encoding/json"
	"fmt"
	"log"
	"os"
	"os/signal"
	"strconv"
	"syscall"
	"time"

	"ingestion/internal/alerts"
	"ingestion/internal/influx"
	"ingestion/internal/models"
	"ingestion/internal/mqtt"

	"context"
	"database/sql"
	paho "github.com/eclipse/paho.mqtt.golang"
	"github.com/joho/godotenv"
	_ "github.com/lib/pq"
	"github.com/redis/go-redis/v9"
)

func envWithFallback(primary, fallback string) string {
	value := os.Getenv(primary)
	if value != "" {
		return value
	}

	return os.Getenv(fallback)
}

func main() {
	ctx := context.Background()
	// Load environment variables
	err := godotenv.Load("../../.env")
	if err != nil {
		log.Println("No .env file found, using system environment variables")
	}

	mqttHost := os.Getenv("MQTT_HOST")
	mqttPortStr := os.Getenv("MQTT_PORT")
	mqttPort, _ := strconv.Atoi(mqttPortStr)
	mqttUser := os.Getenv("MQTT_USERNAME")
	mqttPass := os.Getenv("MQTT_PASSWORD")

	influxURL := envWithFallback("INFLUXDB_URL", "INFLUX_URL")
	influxToken := envWithFallback("INFLUXDB_TOKEN", "INFLUX_TOKEN")
	influxOrg := envWithFallback("INFLUXDB_ORG", "INFLUX_ORG")
	influxBucket := envWithFallback("INFLUXDB_BUCKET", "INFLUX_BUCKET")

	redisHost := os.Getenv("REDIS_HOST")
	redisPort := os.Getenv("REDIS_PORT")
	if redisHost == "" {
		redisHost = "localhost"
	}
	if redisPort == "" {
		redisPort = "6379"
	}

	// Initialize Redis Client
	rdb := redis.NewClient(&redis.Options{
		Addr: fmt.Sprintf("%s:%s", redisHost, redisPort),
	})

	// Initialize PostgreSQL Client
	dbUser := os.Getenv("DB_USERNAME")
	dbPass := os.Getenv("DB_PASSWORD")
	dbName := os.Getenv("DB_DATABASE")
	dbHost := os.Getenv("DB_HOST")
	if dbHost == "" {
		dbHost = "localhost"
	}
	connStr := fmt.Sprintf("host=%s user=%s password=%s dbname=%s sslmode=disable", dbHost, dbUser, dbPass, dbName)
	db, err := sql.Open("postgres", connStr)
	if err != nil {
		log.Fatalf("Error connecting to postgres: %v", err)
	}
	defer db.Close()

	// Initialize InfluxDB Client
	influxClient := influx.NewInfluxClient(influxURL, influxToken, influxOrg, influxBucket)
	defer influxClient.Close()

	// Initialize Alert Engine
	alertEngine := alerts.NewAlertEngine(db)

	// Initialize MQTT Client
	mqttClient := mqtt.NewMQTTClient(mqttHost, mqttPort, mqttUser, mqttPass)

	// Subscribe to telemetry topic
	// Format: tenant/{tenant_id}/device/{device_id}/data
	topic := "tenant/+/device/+/data"
	mqttClient.Subscribe(topic, func(client paho.Client, msg paho.Message) {
		tenantID, deviceID, err := mqtt.ExtractTopicInfo(msg.Topic())
		if err != nil {
			log.Printf("Error extracting topic info: %v", err)
			return
		}

		var payload models.TelemetryPayload
		if err := json.Unmarshal(msg.Payload(), &payload); err != nil {
			log.Printf("Error parsing JSON payload: %v", err)
			return
		}

		values := payload.NormalizeValues()
		if payload.DeviceTime == "" {
			log.Printf("Invalid payload from %s/%s: missing device_time", tenantID, deviceID)
			return
		}

		if len(values) == 0 {
			log.Printf("Invalid payload from %s/%s: values is empty", tenantID, deviceID)
			return
		}

		deviceTime, err := time.Parse(time.RFC3339, payload.DeviceTime)
		if err != nil {
			log.Printf("Error parsing device time: %v", err)
			deviceTime = time.Now()
		}

		telemetryData := models.TelemetryData{
			TenantID:   tenantID,
			DeviceID:   deviceID,
			DeviceTime: deviceTime,
			ServerTime: time.Now(),
			Fields:     values,
		}

		log.Printf("Received data from %s/%s: %v", tenantID, deviceID, values)

		if err := influxClient.WriteTelemetry(telemetryData); err != nil {
			log.Printf("Error writing to InfluxDB: %v", err)
		}

		// Process Alerts
		alertEngine.ProcessTelemetry(tenantID, deviceID, values)

		// Publish to Redis for realtime updates
		payloadJSON, _ := json.Marshal(map[string]interface{}{
			"tenant_id":   tenantID,
			"device_id":   deviceID,
			"values":      values,
			"device_time": telemetryData.DeviceTime,
			"server_time": telemetryData.ServerTime,
		})
		rdb.Publish(ctx, "telemetry", payloadJSON)
	})

	// Subscribe to ACK topic
	// Format: tenant/{tenant_id}/device/{device_id}/ack
	ackTopic := "tenant/+/device/+/ack"
	mqttClient.Subscribe(ackTopic, func(client paho.Client, msg paho.Message) {
		var ack struct {
			CommandID int    `json:"command_id"`
			Status    string `json:"status"`
		}
		if err := json.Unmarshal(msg.Payload(), &ack); err != nil {
			log.Printf("Error parsing ACK payload: %v", err)
			return
		}

		log.Printf("Received ACK for command %d: %s", ack.CommandID, ack.Status)

		_, err := db.Exec("UPDATE commands SET status = $1, ack_at = $2 WHERE id = $3", ack.Status, time.Now(), ack.CommandID)
		if err != nil {
			log.Printf("Error updating command status: %v", err)
		}
	})

	// Wait for termination signal
	sigChan := make(chan os.Signal, 1)
	signal.Notify(sigChan, syscall.SIGINT, syscall.SIGTERM)
	<-sigChan

	fmt.Println("Shutting down...")
}
