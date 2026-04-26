<?php
namespace Canvastack\Canvastack\Library\Constants;

/**
 * Privilege Constants
 * 
 * Defines privilege levels using bitwise flags for efficient storage and checking.
 * Multiple privileges can be combined using bitwise OR (|) operator.
 * 
 * @example
 * // Grant read and write privileges
 * $privileges = PrivilegeConstants::READ | PrivilegeConstants::WRITE;
 * 
 * // Check if user has read privilege
 * if ($privileges & PrivilegeConstants::READ) {
 *     // User can read
 * }
 */
class PrivilegeConstants {
    // Privilege Flags (bitwise)
    public const READ   = 8;  // 1000 in binary
    public const WRITE  = 4;  // 0100 in binary (insert)
    public const MODIFY = 2;  // 0010 in binary (update)
    public const DELETE = 1;  // 0001 in binary
    
    // Privilege Names
    public const PRIVILEGE_NAMES = [
        self::READ   => 'read',
        self::WRITE  => 'insert',
        self::MODIFY => 'update',
        self::DELETE => 'delete',
    ];
    
    // Privilege Labels
    public const PRIVILEGE_LABELS = [
        self::READ   => 'Read',
        self::WRITE  => 'Insert',
        self::MODIFY => 'Update',
        self::DELETE => 'Delete',
    ];
    
    // Privilege Contexts
    public const INDEX_PRIVILEGE = 'index_privilege';
    public const ADMIN_PRIVILEGE = 'admin_privilege';
    
    /**
     * Get privilege name from flag
     * 
     * @param int $flag Privilege flag (8, 4, 2, or 1)
     * @return string|null Privilege name (read, insert, update, delete) or null if invalid
     * 
     * @example
     * PrivilegeConstants::getName(8); // Returns 'read'
     * PrivilegeConstants::getName(4); // Returns 'insert'
     * PrivilegeConstants::getName(99); // Returns null
     */
    public static function getName(int $flag): ?string {
        return self::PRIVILEGE_NAMES[$flag] ?? null;
    }
    
    /**
     * Get privilege label from flag
     * 
     * @param int $flag Privilege flag (8, 4, 2, or 1)
     * @return string|null Privilege label (Read, Insert, Update, Delete) or null if invalid
     * 
     * @example
     * PrivilegeConstants::getLabel(8); // Returns 'Read'
     * PrivilegeConstants::getLabel(4); // Returns 'Insert'
     * PrivilegeConstants::getLabel(99); // Returns null
     */
    public static function getLabel(int $flag): ?string {
        return self::PRIVILEGE_LABELS[$flag] ?? null;
    }
    
    /**
     * Validate privilege flag
     * 
     * @param int $flag Privilege flag to validate
     * @return bool True if valid privilege flag, false otherwise
     * 
     * @example
     * PrivilegeConstants::isValid(8); // Returns true
     * PrivilegeConstants::isValid(99); // Returns false
     */
    public static function isValid(int $flag): bool {
        return isset(self::PRIVILEGE_NAMES[$flag]);
    }
    
    /**
     * Get all privilege flags
     * 
     * @return array Array of all privilege flags [8, 4, 2, 1]
     * 
     * @example
     * $flags = PrivilegeConstants::getAllFlags();
     * // Returns [8, 4, 2, 1]
     */
    public static function getAllFlags(): array {
        return [self::READ, self::WRITE, self::MODIFY, self::DELETE];
    }
    
    /**
     * Check if privilege set includes specific privilege
     * 
     * Uses bitwise AND to check if a specific privilege is included in a privilege set.
     * 
     * @param int $privilegeSet Combined privilege flags (e.g., 12 = READ | WRITE)
     * @param int $privilege Privilege to check (e.g., READ)
     * @return bool True if privilege is included in the set
     * 
     * @example
     * // Check if user has read privilege
     * $userPrivileges = 12; // READ (8) | WRITE (4)
     * PrivilegeConstants::hasPrivilege($userPrivileges, PrivilegeConstants::READ); // Returns true
     * PrivilegeConstants::hasPrivilege($userPrivileges, PrivilegeConstants::DELETE); // Returns false
     */
    public static function hasPrivilege(int $privilegeSet, int $privilege): bool {
        return ($privilegeSet & $privilege) === $privilege;
    }
}
