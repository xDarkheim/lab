<?php
namespace App\Lib;

use PDO;
use PDOException;

class SettingsManager {
    private PDO $conn;
    private array $settingsCache = [];

    public function __construct(Database $db_handler) {
        $this->conn = $db_handler->getConnection();
        $this->loadAllSettings();
    }

    private function loadAllSettings(): void {
        if (!$this->conn) {
            error_log("SettingsManager: Database connection not available.");
            $this->settingsCache = $this->getDefaultFallbackSettings();
            return;
        }
        try {
            $stmt = $this->conn->query("SELECT setting_name, setting_value FROM site_settings");
            $this->settingsCache = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $this->settingsCache[$row['setting_name']] = $row['setting_value'];
            }
        } catch (PDOException $e) {
            error_log("SettingsManager: Failed to load site settings from DB - " . $e->getMessage());
            $this->settingsCache = $this->getDefaultFallbackSettings();
        }
    }
    
    private function getDefaultFallbackSettings(): array {

        return [
            'site_name' => 'WebEngine Default',
            'site_tagline' => 'Default Tagline',
            'admin_email' => 'admin@example.com',
        ];
    }

    public function getSetting(string $name, $default = null) {
        return $this->settingsCache[$name] ?? $default;
    }

    public function getAllSettings(): array {
        return $this->settingsCache;
    }

    public function updateSetting(string $name, string $value): bool {
        if (!$this->conn) {
            error_log("SettingsManager: Database connection not available for updateSetting.");
            return false;
        }
        try {
            $sql = "INSERT INTO site_settings (setting_name, setting_value) VALUES (:name, :value)
                    ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)";
            $stmt = $this->conn->prepare($sql);
            $result = $stmt->execute(['name' => $name, 'value' => $value]);
            if ($result) {
                $this->settingsCache[$name] = $value;
            }
            return $result;
        } catch (PDOException $e) {
            error_log("SettingsManager: Failed to update setting '{$name}' - " . $e->getMessage());
            return false;
        }
    }

    public function updateSettings(array $settingsToUpdate): bool {
        if (!$this->conn) {
            error_log("SettingsManager: Database connection not available for updateSettings.");
            return false;
        }
        $all_successful = true;
        $this->conn->beginTransaction();

        try {
            $sql = "INSERT INTO site_settings (setting_name, setting_value) VALUES (:name, :value)
                    ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)";
            $stmt = $this->conn->prepare($sql);

            foreach ($settingsToUpdate as $name => $value) {
                if (!$stmt->execute(['name' => $name, 'value' => (string)$value])) {
                    $all_successful = false;
                    break;
                }
                $this->settingsCache[$name] = (string)$value;
            }

            if ($all_successful) {
                $this->conn->commit();
            } else {
                $this->conn->rollBack();
                error_log("SettingsManager: Transaction rolled back due to an error updating settings.");
            }
            return $all_successful;
        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log("SettingsManager: Failed to update multiple settings - " . $e->getMessage());
            return false;
        }
    }
}