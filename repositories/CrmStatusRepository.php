<?php

/**
 * Repository for CRM entry status and audit history tables.
 * Encapsulates all $wpdb database interactions for status lifecycle.
 */
class CrmStatusRepository
{
    private readonly wpdb $db;
    private readonly string $statusTable;
    private readonly string $historyTable;

    public function __construct(?wpdb $db = null)
    {
        global $wpdb;
        $this->db = $db ?? $wpdb;
        $this->statusTable = $this->db->prefix . 'crm_entry_status';
        $this->historyTable = $this->db->prefix . 'crm_entry_status_history';
    }

    /**
     * Get the current status key for an entry.
     */
    public function getCurrentStatusKey(int $entryId): string
    {
        if ($entryId <= 0) {
            return '';
        }

        $val = $this->db->get_var(
            $this->db->prepare("SELECT status_key FROM {$this->statusTable} WHERE entry_id = %d", $entryId)
        );

        return $val ? (string) $val : '';
    }

    /**
     * Get the full status row for an entry.
     */
    public function getStatusRow(int $entryId): ?array
    {
        if ($entryId <= 0) {
            return null;
        }

        $row = $this->db->get_row(
            $this->db->prepare("SELECT * FROM {$this->statusTable} WHERE entry_id = %d", $entryId),
            ARRAY_A
        );

        return $row ?: null;
    }

    /**
     * Set or update the status of an entry and log the audit history.
     */
    public function setStatus(
        int $entryId,
        string $statusKey,
        string $statusLabel,
        string $note = '',
        ?int $courseId = null,
        ?string $startDate = null,
        ?string $endDate = null
    ): bool {
        if ($entryId <= 0) {
            return false;
        }

        $userId = get_current_user_id();
        $now = current_time('mysql');
        $formId = 60468;

        // 1. Audit History Insert
        $this->db->insert(
            $this->historyTable,
            [
                'entry_id'     => $entryId,
                'form_id'      => $formId,
                'status_key'   => $statusKey,
                'status_label' => $statusLabel,
                'status_date'  => $now,
                'note'         => $note,
                'created_by'   => $userId,
            ],
            ['%d', '%d', '%s', '%s', '%s', '%s', '%d']
        );

        // 2. Current Status Upsert
        $exists = $this->db->get_var(
            $this->db->prepare("SELECT entry_id FROM {$this->statusTable} WHERE entry_id = %d", $entryId)
        );

        $statusData = [
            'form_id'      => $formId,
            'status_key'   => $statusKey,
            'status_label' => $statusLabel,
            'status_date'  => $now,
            'note'         => $note,
            'updated_by'   => $userId,
        ];
        $statusFmt = ['%d', '%s', '%s', '%s', '%s', '%d'];

        if ($courseId !== null) {
            $statusData['course_id'] = $courseId;
            $statusFmt[] = '%d';
        }
        if ($startDate !== null) {
            $statusData['course_start_date'] = $startDate;
            $statusFmt[] = '%s';
        }
        if ($endDate !== null) {
            $statusData['course_end_date'] = $endDate;
            $statusFmt[] = '%s';
        }

        if ($exists) {
            return (bool) $this->db->update(
                $this->statusTable,
                $statusData,
                ['entry_id' => $entryId],
                $statusFmt,
                ['%d']
            );
        }

        $statusData['entry_id'] = $entryId;
        array_unshift($statusFmt, '%d');

        return (bool) $this->db->insert($this->statusTable, $statusData, $statusFmt);
    }

    /**
     * Add an entry to the status history log without changing the main status (e.g. test mail sent).
     */
    public function addHistory(int $entryId, string $statusKey, string $statusLabel, string $note = '', int $formId = 60468): bool
    {
        if ($entryId <= 0) {
            return false;
        }

        $userId = get_current_user_id();

        return (bool) $this->db->insert(
            $this->historyTable,
            [
                'entry_id'     => $entryId,
                'form_id'      => $formId,
                'status_key'   => $statusKey,
                'status_label' => $statusLabel,
                'status_date'  => current_time('mysql'),
                'note'         => $note,
                'created_by'   => $userId,
            ],
            ['%d', '%d', '%s', '%s', '%s', '%s', '%d']
        );
    }

    /**
     * Fetch status history for an entry in descending order.
     */
    public function getHistory(int $entryId): array
    {
        if ($entryId <= 0) {
            return [];
        }

        return $this->db->get_results(
            $this->db->prepare(
                "SELECT * FROM {$this->historyTable} WHERE entry_id = %d ORDER BY status_date DESC, id DESC",
                $entryId
            ),
            ARRAY_A
        ) ?: [];
    }

    /**
     * Fetch multiple entry status rows in a single batch query.
     */
    public function getStatusesForEntries(array $entryIds): array
    {
        $sanitizedIds = array_filter(array_map('absint', $entryIds));
        if (empty($sanitizedIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($sanitizedIds), '%d'));
        $query = $this->db->prepare(
            "SELECT * FROM {$this->statusTable} WHERE entry_id IN ($placeholders)",
            $sanitizedIds
        );

        $results = $this->db->get_results($query, ARRAY_A);
        $keyed = [];
        if (!empty($results)) {
            foreach ($results as $row) {
                $keyed[$row['entry_id']] = $row;
            }
        }
        return $keyed;
    }

    /**
     * Save course snapshot dates for an entry to freeze inquiry metadata.
     */
    public function saveCourseSnapshot(int $entryId, ?int $courseId = null, ?string $startDate = null, ?string $endDate = null): bool
    {
        if ($entryId <= 0) {
            return false;
        }

        $row = $this->getStatusRow($entryId);
        $updateData = [];
        $updateFmt  = [];

        if ($courseId !== null) {
            $updateData['course_id'] = $courseId;
            $updateFmt[] = '%d';
        }
        if ($startDate !== null) {
            $updateData['course_start_date'] = $startDate;
            $updateFmt[] = '%s';
        }
        if ($endDate !== null) {
            $updateData['course_end_date'] = $endDate;
            $updateFmt[] = '%s';
        }

        if (empty($updateData)) {
            return false;
        }

        if ($row) {
            return (bool) $this->db->update(
                $this->statusTable,
                $updateData,
                ['entry_id' => $entryId],
                $updateFmt,
                ['%d']
            );
        }

        $updateData['entry_id']     = $entryId;
        $updateData['status_key']   = 'neu';
        $updateData['status_label'] = 'Neu eingegangen';
        $updateData['status_date']  = current_time('mysql');
        $updateData['changed_by']   = 'System';
        array_unshift($updateFmt, '%d', '%s', '%s', '%s', '%s');

        return (bool) $this->db->insert($this->statusTable, $updateData, $updateFmt);
    }

    /**
     * Get frozen snapshot dates for an entry.
     */
    public function getCourseSnapshot(int $entryId): ?array
    {
        if ($entryId <= 0) {
            return null;
        }

        $row = $this->db->get_row(
            $this->db->prepare(
                "SELECT course_id, course_start_date, course_end_date FROM {$this->statusTable} WHERE entry_id = %d",
                $entryId
            ),
            ARRAY_A
        );

        if (!$row || (empty($row['course_id']) && empty($row['course_start_date']) && empty($row['course_end_date']))) {
            return null;
        }

        return [
            'course_id'  => !empty($row['course_id']) ? (int) $row['course_id'] : null,
            'start_date' => !empty($row['course_start_date']) ? $row['course_start_date'] : null,
            'end_date'   => !empty($row['course_end_date']) ? $row['course_end_date'] : null,
        ];
    }
}
