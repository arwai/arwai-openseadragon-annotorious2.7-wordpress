<?php
namespace ARWAI\Data;
// This new class isolates all database interactions.



class AnnotationRepository {
    private $wpdb;
    private $table_name;
    private $history_table_name;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $wpdb->prefix . 'annotorious_data';
        $this->history_table_name = $wpdb->prefix . 'annotorious_history';
    }

    public function get(int $attachment_id) {
        $results = $this->wpdb->get_results(
            $this->wpdb->prepare("SELECT annotation_data FROM {$this->table_name} WHERE attachment_id = %d", $attachment_id),
            ARRAY_A
        );
        
        return array_reduce($results, function ($carry, $row) {
            $decoded = json_decode($row['annotation_data'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $carry[] = $decoded;
            }
            return $carry;
        }, []);
    }

    public function add(string $annotation_json) {
        $annotation = json_decode($annotation_json, true);
        if (!$annotation) return false;
        
        $attachment_id = $this->get_attachment_id_from_annotation($annotation);
        $annotation_id = $annotation['id'] ?? '';
        if (!$attachment_id || !$annotation_id) return false;

        if (isset($annotation['body'][0]['value'])) {
            $annotation['body'][0]['value'] = wp_kses_post($annotation['body'][0]['value']);
        }
        
        $this->log_to_history($annotation_id, $attachment_id, 'created', $annotation);

        return $this->wpdb->insert($this->table_name, [
            'annotation_id_from_annotorious' => $annotation_id,
            'attachment_id' => $attachment_id,
            'annotation_data' => wp_json_encode($annotation),
        ], ['%s', '%d', '%s']);
    }

    public function delete(string $annotation_id, string $annotation_json) {
        $annotation = json_decode($annotation_json, true);
        if (!$annotation) return false;

        $attachment_id = $this->get_attachment_id_from_annotation($annotation);
        if (!$attachment_id) return false;

        $existing = $this->wpdb->get_row($this->wpdb->prepare("SELECT annotation_data FROM {$this->table_name} WHERE annotation_id_from_annotorious = %s AND attachment_id = %d", $annotation_id, $attachment_id), ARRAY_A);
        if ($existing) {
            $this->log_to_history($annotation_id, $attachment_id, 'deleted', json_decode($existing['annotation_data'], true));
        }

        return $this->wpdb->delete($this->table_name, ['annotation_id_from_annotorious' => $annotation_id, 'attachment_id' => $attachment_id], ['%s', '%d']);
    }

    public function update(string $annotation_id, string $annotation_json) {
        $annotation = json_decode($annotation_json, true);
        if (!$annotation) return false;

        $attachment_id = $this->get_attachment_id_from_annotation($annotation);
        if (!$attachment_id) return false;
        
        if (isset($annotation['body'][0]['value'])) {
            $annotation['body'][0]['value'] = wp_kses_post($annotation['body'][0]['value']);
        }
        
        $updated = $this->wpdb->update($this->table_name, ['annotation_data' => wp_json_encode($annotation)], ['annotation_id_from_annotorious' => $annotation_id, 'attachment_id' => $attachment_id], ['%s'], ['%s', '%d']);

        if ($updated) {
            $this->log_to_history($annotation_id, $attachment_id, 'updated', $annotation);
        }
        return $updated;
    }

    public function get_history(int $attachment_id = 0, string $annotation_id = '') {
        // ... (Logic to query history table) ...
    }
    
    private function get_attachment_id_from_annotation(array $annotation) {
        $image_url = $annotation['target']['source'] ?? '';
        return empty($image_url) ? 0 : attachment_url_to_postid($image_url);
    }
    
    private function log_to_history(string $annotation_id, int $attachment_id, string $action, array $annotation) {
        $this->wpdb->insert($this->history_table_name, [
            'annotation_id_from_annotorious' => $annotation_id,
            'attachment_id' => $attachment_id,
            'action_type' => $action,
            'annotation_data_snapshot' => wp_json_encode($annotation),
            'user_id' => get_current_user_id(),
        ], ['%s', '%d', '%s', '%s', '%d']);
    }
}