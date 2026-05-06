package influx

import (
	"context"
	"fmt"
	"ingestion/internal/models"
	"strconv"

	influxdb2 "github.com/influxdata/influxdb-client-go/v2"
	"github.com/influxdata/influxdb-client-go/v2/api"
)

type InfluxClient struct {
	client   influxdb2.Client
	writeAPI api.WriteAPIBlocking
	org      string
	bucket   string
}

func NewInfluxClient(url, token, org, bucket string) *InfluxClient {
	client := influxdb2.NewClient(url, token)
	writeAPI := client.WriteAPIBlocking(org, bucket)

	return &InfluxClient{
		client:   client,
		writeAPI: writeAPI,
		org:      org,
		bucket:   bucket,
	}
}

func (i *InfluxClient) WriteTelemetry(data models.TelemetryData) error {
	for key, rawValue := range data.Fields {
		fields := map[string]interface{}{
			"server_time": data.ServerTime.UnixMilli(),
		}

		switch value := rawValue.(type) {
		case float64:
			fields["value"] = value
		case float32:
			fields["value"] = float64(value)
		case int:
			fields["value"] = float64(value)
		case int32:
			fields["value"] = float64(value)
		case int64:
			fields["value"] = float64(value)
		case uint:
			fields["value"] = float64(value)
		case uint32:
			fields["value"] = float64(value)
		case uint64:
			fields["value"] = float64(value)
		case bool:
			if value {
				fields["value"] = 1.0
			} else {
				fields["value"] = 0.0
			}
			fields["str_value"] = strconv.FormatBool(value)
		case string:
			fields["str_value"] = value
		default:
			fields["str_value"] = fmt.Sprintf("%v", value)
		}

		point := influxdb2.NewPoint(
			"telemetry",
			map[string]string{
				"tenant_id": data.TenantID,
				"device_id": data.DeviceID,
				"param_key": key,
			},
			fields,
			data.DeviceTime,
		)

		if err := i.writeAPI.WritePoint(context.Background(), point); err != nil {
			return fmt.Errorf("error writing telemetry point for %s: %v", key, err)
		}
	}

	return nil
}

func (i *InfluxClient) Close() {
	i.client.Close()
}
