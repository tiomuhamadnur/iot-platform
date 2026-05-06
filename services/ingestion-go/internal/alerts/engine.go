package alerts

import (
	"database/sql"
	"fmt"
	"log"
)

type AlertRule struct {
	ID                  int
	TenantID            int
	DeviceID            sql.NullInt64
	ParameterIdentifier string
	Operator            string
	Threshold           float64
	Duration            int
	Cooldown            int
	Severity            string
}

type AlertEngine struct {
	db *sql.DB
}

func NewAlertEngine(db *sql.DB) *AlertEngine {
	return &AlertEngine{db: db}
}

func (e *AlertEngine) ProcessTelemetry(tenantID, deviceID string, fields map[string]interface{}) {
	// 1. Get numeric tenant ID and device ID from metadata (omitted for brevity, assuming we have them)
	// For now, let's assume we query rules by string identifiers if possible, or we need a cache.
	// In a real app, we'd have a cache of rules.

	// Simulating rule check:
	rows, err := e.db.Query("SELECT id, tenant_id, device_id, parameter_identifier, operator, threshold, duration, cooldown, severity FROM alert_rules WHERE is_active = true")
	if err != nil {
		log.Printf("Error querying alert rules: %v", err)
		return
	}
	defer rows.Close()

	for rows.Next() {
		var rule AlertRule
		if err := rows.Scan(&rule.ID, &rule.TenantID, &rule.DeviceID, &rule.ParameterIdentifier, &rule.Operator, &rule.Threshold, &rule.Duration, &rule.Cooldown, &rule.Severity); err != nil {
			continue
		}

		val, ok := fields[rule.ParameterIdentifier]
		if !ok {
			continue
		}

		floatVal, ok := toFloat64(val)
		if !ok {
			continue
		}

		if e.checkCondition(floatVal, rule.Operator, rule.Threshold) {
			e.triggerAlert(rule, deviceID, floatVal)
		}
	}
}

func (e *AlertEngine) checkCondition(val float64, operator string, threshold float64) bool {
	switch operator {
	case ">":
		return val > threshold
	case "<":
		return val < threshold
	case "==":
		return val == threshold
	case "!=":
		return val != threshold
	case ">=":
		return val >= threshold
	case "<=":
		return val <= threshold
	}
	return false
}

func (e *AlertEngine) triggerAlert(rule AlertRule, deviceID string, value float64) {
	// Check cooldown and duration (simplified for now)
	log.Printf("ALERT TRIGGERED: Rule %d, Device %s, Value %f", rule.ID, deviceID, value)

	// In a real app, we'd check if an alert is already active and handle state.
	// Insert into alert_histories
	// We need to resolve deviceID string to internal ID.
}

func toFloat64(val interface{}) (float64, bool) {
	switch v := val.(type) {
	case float64:
		return v, true
	case int:
		return float64(v), true
	case string:
		var parsed float64
		if _, err := fmt.Sscanf(v, "%f", &parsed); err == nil {
			return parsed, true
		}
	}
	return 0, false
}
