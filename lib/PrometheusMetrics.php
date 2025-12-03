<?php
/**
 * Prometheus Metrics Helper
 * Simple Prometheus metrics collection for PHP microservices
 */

class PrometheusMetrics {
    private static $metrics = [];
    private static $startTime = null;

    public static function init() {
        self::$startTime = microtime(true);
    }

    public static function counter($name, $value = 1, $labels = []) {
        $key = self::buildKey($name, $labels);
        if (!isset(self::$metrics[$key])) {
            self::$metrics[$key] = ['type' => 'counter', 'value' => 0, 'labels' => $labels];
        }
        self::$metrics[$key]['value'] += $value;
    }

    public static function gauge($name, $value, $labels = []) {
        $key = self::buildKey($name, $labels);
        self::$metrics[$key] = ['type' => 'gauge', 'value' => $value, 'labels' => $labels];
    }

    public static function histogram($name, $value, $labels = []) {
        $key = self::buildKey($name, $labels);
        if (!isset(self::$metrics[$key])) {
            self::$metrics[$key] = ['type' => 'histogram', 'values' => [], 'labels' => $labels];
        }
        self::$metrics[$key]['values'][] = $value;
    }

    public static function timing($name, $labels = []) {
        return function() use ($name, $labels) {
            $duration = (microtime(true) - self::$startTime) * 1000; // ms
            self::histogram($name, $duration, $labels);
        };
    }

    private static function buildKey($name, $labels) {
        if (empty($labels)) {
            return $name;
        }
        $labelStr = implode(',', array_map(function($k, $v) {
            return "$k=$v";
        }, array_keys($labels), $labels));
        return "$name{$labelStr}";
    }

    public static function render() {
        header('Content-Type: text/plain; charset=utf-8');
        $output = "# HELP inventory_tracker Inventory Tracker Metrics\n";
        $output .= "# TYPE inventory_tracker counter\n\n";

        // Add request metrics
        self::counter('http_requests_total', 1, ['method' => $_SERVER['REQUEST_METHOD'], 'path' => $_SERVER['REQUEST_URI']]);
        
        // Add response time
        if (self::$startTime) {
            $duration = (microtime(true) - self::$startTime) * 1000;
            self::gauge('request_duration_ms', $duration, ['path' => $_SERVER['REQUEST_URI']]);
        }

        // Render all metrics
        foreach (self::$metrics as $key => $metric) {
            $type = $metric['type'];
            $labels = $metric['labels'];
            
            if ($type === 'counter' || $type === 'gauge') {
                $output .= self::formatMetric($key, $metric['value']);
            } elseif ($type === 'histogram') {
                $values = $metric['values'];
                if (!empty($values)) {
                    $count = count($values);
                    $sum = array_sum($values);
                    $output .= self::formatMetric($key . '_count', $count);
                    $output .= self::formatMetric($key . '_sum', $sum);
                    $output .= self::formatMetric($key . '_bucket', $count, 'le="100"');
                }
            }
        }

        echo $output;
    }

    private static function formatMetric($name, $value, $extraLabels = '') {
        $labelStr = $extraLabels ? '{' . $extraLabels . '}' : '';
        return $name . $labelStr . ' ' . $value . "\n";
    }
}

// Initialize on first use
PrometheusMetrics::init();
