package models

import (
	"encoding/json"
	"time"
)

type TelemetryPayload struct {
	DeviceTime string                 `json:"device_time"`
	Seq        *int64                 `json:"seq,omitempty"`
	Values     map[string]interface{} `json:"values"`
	Data       map[string]interface{} `json:"data,omitempty"`
}

type TelemetryData struct {
	TenantID   string
	DeviceID   string
	DeviceTime time.Time
	ServerTime time.Time
	Fields     map[string]interface{}
}

func (p *TelemetryPayload) NormalizeValues() map[string]interface{} {
	if len(p.Values) > 0 {
		return p.Values
	}

	return p.Data
}

func (p *TelemetryPayload) UnmarshalJSON(data []byte) error {
	type alias TelemetryPayload

	var payload alias
	if err := json.Unmarshal(data, &payload); err != nil {
		return err
	}

	*p = TelemetryPayload(payload)
	if len(p.Values) == 0 && len(p.Data) > 0 {
		p.Values = p.Data
	}

	return nil
}
